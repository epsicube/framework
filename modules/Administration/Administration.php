<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Epsicube\Support\Facades\Options;
use EpsicubeModules\Administration\Features\PanelApplicationNavigation;
use Filafly\Icons\Phosphor\PhosphorIcons;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel as FilamentPanel;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            (new PanelApplicationNavigation($this))->configure();
        }
    }
}
