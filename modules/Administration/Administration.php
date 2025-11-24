<?php

declare(strict_types=1);

namespace UniGaleModules\Administration;

use Filament\Http\Middleware\Authenticate;
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
        //            ->authMiddleware([Authenticate::class])
        //            ->login()
        //            ->passwordReset()
        //            ->registration()
        //            ->profile();

        // Style configuration
        $this->maxContentWidth(Width::Full)
            ->sidebarFullyCollapsibleOnDesktop()
            ->spa();
    }
}
