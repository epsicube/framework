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
    }
}
