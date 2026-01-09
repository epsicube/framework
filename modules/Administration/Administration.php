<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\Contracts\ApplicationGroup;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Panel as FilamentPanel;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Administration extends FilamentPanel
{
    protected function setUp(): void
    {
        $this
            ->discoverPages(in: __DIR__.'/Pages', for: __NAMESPACE__.'\\Pages')
            ->discoverClusters(in: __DIR__.'/Clusters', for: __NAMESPACE__.'\\Clusters')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);

        $options = Options::all('core::administration');
        // Options configurations
        if ($path = $options['path']) {
            $this->path($path);
        }
        $this->domain($options['domain'] ?? null);
        $this->brandName(fn () => $options['brand-name'] ?? null);
        $this->spa(fn () => $options['spa'] ?? false);
        $this->topNavigation(fn () => $options['top-navigation'] ?? false);
        $this->maxContentWidth(Width::Full);
        $this->sidebarWidth('15rem');

        // Custom navigation (ApplicationGroup scoping) - BETA theme
        $this->topNavigation();
        $this->navigation(
            fn (NavigationBuilder $builder) => $this->customNavigation($builder, $this->getInitialNavigationGroups())
        );

        $this->renderHook(PanelsRenderHook::HEAD_END, function () {
            if (Filament::getCurrentPanel()->hasTopNavigation()) {
                return new HtmlString('<style>a:has(.fi-logo){display:none;}</style>');
            }

            return null;
        });

        $this->renderHook(PanelsRenderHook::TOPBAR_LOGO_BEFORE, function () {
            if (Filament::getCurrentPanel()->hasTopNavigation()) {
                return view('epsicube-administration::components.application-group-switcher', [
                    'applicationGroups' => $this->getApplicationGroups(),
                ]);
            }

            return null;
        });
        $this->renderHook(PanelsRenderHook::SIDEBAR_START, function () {
            if (Filament::getCurrentPanel()->hasTopNavigation()) {
                return view('epsicube-administration::components.application-group-switcher', [
                    'applicationGroups' => $this->getApplicationGroups(),
                ]);
            }

            return null;
        });
    }

    /**
     * Customize navigation to handle ApplicationGroup visibility.
     *
     * - ApplicationGroup is always hidden unless active
     * - If an ApplicationGroup is active, only its items are shown (flattened)
     * - Other groups are visible only when no ApplicationGroup is active
     */
    protected function customNavigation(NavigationBuilder $builder, array $initialGroups): NavigationBuilder
    {
        // Detect active ApplicationGroup
        /** @var NavigationGroup|null $activeNavigationGroup */
        $activeNavigationGroup = collect($initialGroups)->first(function (NavigationGroup $group) {
            $items = $group->getItems();

            if ($items instanceof Arrayable) {
                $items = $items->toArray();
            }

            if (empty($items)) {
                return false;
            }

            return array_first($items)?->getGroup() instanceof ApplicationGroup && $group->isActive();
        });

        // ApplicationGroup active: show only its flattened items
        if ($activeNavigationGroup) {
            $childItems = $activeNavigationGroup->getItems();
            if ($childItems instanceof Arrayable) {
                $childItems = $childItems->toArray();
            }

            return $builder->items(array_values($childItems));

        }

        // No ApplicationGroup active: remove all ApplicationGroups
        $filteredGroups = collect(array_values($initialGroups))->filter(function (NavigationGroup $group): bool {
            $items = $group->getItems();

            if ($items instanceof Arrayable) {
                $items = array_values($items->toArray());
                $group->items($items); // reset as an array (Filament internal issue)
            }

            return ! (array_first($items)?->getGroup() instanceof ApplicationGroup);
        })->all();

        // Return initial when all groups were deleted to prevent infinite redirect on '/'
        if (empty($filteredGroups)) {
            return $builder->groups($initialGroups);
        }

        // Apply filtered navigation
        return $builder->groups($filteredGroups);
    }

    protected function getInitialNavigationGroups(): array
    {
        $initial = $this->navigationBuilder;
        try {
            $this->navigationBuilder = true;

            return $this->getNavigation();
        } finally {
            $this->navigationBuilder = $initial;
        }
    }

    protected function getApplicationGroups(): array
    {
        return array_values(array_filter($this->getInitialNavigationGroups(), function (NavigationGroup $group) {
            $items = $group->getItems();

            if ($items instanceof Arrayable) {
                $items = $items->toArray();
            }

            return array_first($items)?->getGroup() instanceof ApplicationGroup;
        }));
    }
}
