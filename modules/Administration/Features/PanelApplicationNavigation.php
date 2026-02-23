<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Features;

use EpsicubeModules\Administration\AdministrationOptions;
use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
use EpsicubeModules\Administration\Enums\ApplicationGroup;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Facades\FilamentColor;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;

class PanelApplicationNavigation
{
    public function __construct(
        protected Panel $panel
    ) {}

    public function configure(): void
    {
        $this->panel
            ->navigation(fn (NavigationBuilder $builder) => $this->customNavigation($builder))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, function () {
                return new HtmlString(<<<'HTML'
                    <style>
                        .fi-topbar-start:has(.ec-application-icon) .fi-logo { margin-right: calc(var(--spacing) * 4); }
                        .fi-topbar-start:has(.ec-application-icon) a:has(.fi-logo) { pointer-events: none; }
                        
                        :root {
                            transition: --primary-50, --primary-100, --primary-200, --primary-300, 
                                        --primary-400, --primary-500, --primary-600, --primary-700, 
                                        --primary-800, --primary-900, --primary-950;
                            transition-duration: 0.3s;
                            transition-timing-function: ease-in-out;
                        }
                    </style>
                HTML);
            })
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, function () {
                if (! (Filament::getCurrentPanel()?->hasTopNavigation() ?? false)) {
                    return null;
                }

                return view('epsicube-administration::components.application-group-switcher', [
                    'groupedApplications' => $this->getGroupedNavigationApplications(),
                ]);
            })
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, function () {
                return view('epsicube-administration::components.application-group-switcher', [
                    'groupedApplications' => $this->getGroupedNavigationApplications(),
                ]);
            })
            ->renderHook(PanelsRenderHook::BODY_START, function () {
                $groupedApps = $this->getGroupedNavigationApplications();
                $activeApp = collect($groupedApps)->first(
                    fn (array $appGroup) => $appGroup['isActive'](),
                    array_first($groupedApps)
                );

                $appGroup = $activeApp['applicationGroup'] ?? null;

                if (! ($appGroup instanceof HasColor)) {
                    return null;
                }

                $color = $appGroup->getColor();

                if (is_string($color)) {
                    $color = FilamentColor::getColors()[$color] ?? Color::hex($color);
                }

                if (! is_array($color)) {
                    return null;
                }

                $styles = collect($color)
                    ->map(fn ($value, $shade) => "--primary-{$shade}: {$value} !important;")
                    ->implode("\n            ");

                return new HtmlString(<<<HTML
                    <style wire:key="ec-dynamic-primary-color">
                        :root {
                            {$styles}
                        }
                    </style>
                HTML);
            });
    }

    public function customNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $groupedApplications = $this->getGroupedNavigationApplications();

        if (! AdministrationOptions::hasApplicationNavigation()) {
            return $builder->groups(collect($groupedApplications)->pluck('navigationGroups')->flatten(1)->all());
        }

        $activeApplicationGroup = collect($groupedApplications)->first(
            fn (array $appGroup) => $appGroup['isActive'](),
            array_first($groupedApplications)
        );

        if ($activeApplicationGroup['applicationGroup'] !== ApplicationGroup::EXTRAS) {
            foreach ($activeApplicationGroup['navigationGroups'] as $g) {
                $g->label(null);
            }
        }

        return $builder->groups($activeApplicationGroup['navigationGroups']);
    }

    public function getGroupedNavigationApplications(): array
    {
        $applications = [];

        foreach ($this->getInitialNavigationGroups() as $navigationGroup) {
            $items = $navigationGroup->getItems();

            if ($items instanceof Arrayable) {
                $items = $items->toArray();
                $navigationGroup->items(array_values($items));
            }

            $applicationGroup = null;

            foreach ($items as $item) {
                $applicationGroup = $item->getGroup();
                if ($applicationGroup instanceof ApplicationGroupContract) {
                    break;
                }
            }

            $applicationGroup ??= ApplicationGroup::EXTRAS;
            $key = $applicationGroup->getLabel();

            $applications[$key] ??= [
                'applicationGroup' => $applicationGroup,
                'isActive'         => function () use (&$applications, $key): bool {
                    foreach ($applications[$key]['navigationGroups'] as $group) {
                        if ($group->isActive()) {
                            return true;
                        }
                    }

                    return false;
                },
                'navigationGroups' => [],
            ];

            $applications[$key]['navigationGroups'][] = $navigationGroup;
        }

        return collect($applications)->sortBy(
            fn (array $group) => $group['applicationGroup']->getApplicationSort() ?? 0,
        )->all();
    }

    protected function getInitialNavigationGroups(): array
    {
        // Use Closure::bind to access protected properties
        return (function () {
            /** @var Panel $this */
            $initial = $this->navigationBuilder;
            try {
                $this->navigationBuilder = true;

                return $this->getNavigation();
            } finally {
                $this->navigationBuilder = $initial;
            }
        })->call($this->panel);
    }
}
