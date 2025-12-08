<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Epsicube\Support\Facades\Options;
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
        $this
            ->discoverResources(in: __DIR__.'/Resources', for: __NAMESPACE__.'\\Resources')
            ->discoverPages(in: __DIR__.'/Pages', for: __NAMESPACE__.'\\Pages')
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

        // Options configurations
        $this->path(Options::get('core::administration', 'path'));
        $this->domain(Options::get('core::administration', 'domain'));

        $this->brandName(Options::get('core::administration', 'brand-name'));
        $this->spa(Options::get('core::administration', 'spa'));
        $this->topNavigation(Options::get('core::administration', 'top-navigation'));
        $this->maxContentWidth(Width::Full);
    }
}
