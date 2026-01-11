<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
use EpsicubeModules\Administration\Enums\ApplicationGroup;
use Filafly\Icons\Phosphor\PhosphorIcons;
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
        AdministrationOptions::all(); // <- preload all options to avoid n+1

        $this
            ->discoverPages(in: __DIR__.'/Pages', for: __NAMESPACE__.'\\Pages')
            ->domain(AdministrationOptions::domain())
            ->path(AdministrationOptions::path())
            // Inject brand name, or default logo
            ->brandName(AdministrationOptions::brandName())
            ->unless(AdministrationOptions::brandName(), function (self $panel) {
                $panel->brandLogo(view('epsicube-administration::components.default-logo'))
                    ->brandLogoHeight('2rem');
            })
            ->spa(AdministrationOptions::isSpaEnabled())
            ->topNavigation(AdministrationOptions::hasTopNavigation())
            ->maxContentWidth(Width::Full)
            ->unsavedChangesAlerts()
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
            ])
            ->plugins([
                PhosphorIcons::make(),
            ]);

        // Custom application scoped navigation
        if (AdministrationOptions::hasApplicationNavigation()) {
            $this->navigation($this->customNavigation(...))
                ->renderHook(PanelsRenderHook::STYLES_AFTER, function () {
                    return new HtmlString('<style>
                        .fi-topbar-start:has(.ec-application-icon) .fi-logo{margin-right:calc(var(--spacing) * 4);}
                        .fi-topbar-start:has(.ec-application-icon) a:has(.fi-logo){pointer-events: none;}
                   </style>');
                })->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, function () {
                    if (! (Filament::getCurrentPanel()?->hasTopNavigation() ?? false)) {
                        return null;
                    }

                    return view('epsicube-administration::components.application-group-switcher', [
                        'groupedApplications' => $this->getGroupedNavigationApplications(),
                    ]);
                })->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, function () {
                    return view('epsicube-administration::components.application-group-switcher', [
                        'groupedApplications' => $this->getGroupedNavigationApplications(),
                    ]);
                });
        }
    }

    /**
     * Génération finale de la navigation Filament
     */
    public function customNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $groupedApplications = $this->getGroupedNavigationApplications();
        if (! AdministrationOptions::hasApplicationNavigation()) {
            return $builder->groups(collect($groupedApplications)->pluck('navigationGroups')->flatten(1)->all());
        }

        // Detect active 'application group'
        $activeApplicationGroup = collect($groupedApplications)->first(
            fn (array $appGroup) => $appGroup['isActive'](),
            array_first($groupedApplications) // <- keep default to ensure url can be generated for '/'
        );

        // Flatten items to avoid collapsing, except for the 'EXTRAS' application group
        if ($activeApplicationGroup !== ApplicationGroup::EXTRAS) {
            foreach ($activeApplicationGroup['navigationGroups'] as $g) {
                $g->label(null); // <- filament handles 'null' label as 'non-grouped' items
            }
        }

        return $builder->groups($activeApplicationGroup['navigationGroups']);
    }

    /**
     * Returns the navigation items grouped by their associated ApplicationGroup.
     * Items without an ApplicationGroup are automatically assigned to ApplicationGroup::OTHER
     *
     * @return array<string, array{
     *     applicationGroup: ApplicationGroupContract,
     *     isActive: Closure(): bool,
     *     navigationGroups: NavigationGroup[]
     * }>
     */
    protected function getGroupedNavigationApplications(): array
    {
        $applications = [];

        foreach ($this->getInitialNavigationGroups() as $navigationGroup) {
            $items = $navigationGroup->getItems();

            if ($items instanceof Arrayable) {
                $items = $items->toArray();
                $navigationGroup->items(array_values($items)); // <- fix filament issue
            }

            /** @var ApplicationGroupContract|null $applicationGroup */
            $applicationGroup = null;

            // Detect and configure ApplicationGroup from items (if any)
            foreach ($items as $item) {
                $applicationGroup = $item->getGroup();
                if ($applicationGroup instanceof ApplicationGroupContract) {
                    break;
                }
            }

            // Fallback when no ApplicationGroup is defined
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
            $applications[$key]['isActive'] ??= $navigationGroup->isActive();
        }

        return collect($applications)->sortBy(
            fn (array $group) => $group['applicationGroup']->getApplicationSort() ?? 0, // <- '0' ensure appear after Dashboards
        )->all();
    }

    /**
     * @return array<NavigationGroup>
     */
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
}
