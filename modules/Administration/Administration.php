<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Closure;
use Epsicube\Support\Facades\Modules;
use EpsicubeModules\Administration\Features\PanelApplicationNavigation;
use Filafly\Icons\Phosphor\PhosphorIcons;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Administration
{
    public static string $identifier = 'epsicube-administration';

    public static function configureUsing(Closure $modifyUsing, ?Closure $during = null, bool $isImportant = false): mixed
    {
        return Panel::configureUsing(function (Panel $panel) use (&$modifyUsing) {
            if ($panel->getId() !== static::$identifier) {
                return null;
            }

            return $modifyUsing($panel);
        }, $during, $isImportant);
    }

    public static function configure(Panel $panel): Panel
    {
        AdministrationOptions::all();

        return $panel
            ->id(static::$identifier)
            ->discoverPages(in: __DIR__.'/Pages', for: __NAMESPACE__.'\\Pages')
            ->discoverWidgets(in: __DIR__.'/Widgets', for: __NAMESPACE__.'\\Widgets')
            ->domain(AdministrationOptions::domain())
            ->path(AdministrationOptions::path())
            // Inject brand name, or default logo
            ->brandName(AdministrationOptions::brandName())
            ->unless(AdministrationOptions::brandName(), function (Panel $panel) {
                $panel->brandLogo(fn () => view('epsicube-administration::components.default-logo'))
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
            ])->when(AdministrationOptions::hasApplicationNavigation(), function (Panel $panel) {
                new PanelApplicationNavigation($panel)->configure();
            })->when(! empty(Modules::getBootstrapLogs()), function (Panel $panel) {
                $panel->renderHook(PanelsRenderHook::PAGE_START, function (): HtmlString {
                    $description = '';
                    foreach (Modules::getBootstrapLogs() as $module => $items) {
                        $description .= "**{$module}**: ".implode(', ', $items)."  \n";
                    }

                    return new HtmlString(Blade::render('
                    <div style="margin-block-start: 2rem;">
                        <x-filament::callout icon="heroicon-o-exclamation-circle" color="danger">
                            <x-slot name="heading"> '.__('Module Bootstrapping Error').' </x-slot>
                            <x-slot name="description">'.str($description)->markdown().'</x-slot>
                        </x-filament::callout>
                    </div>
                '));
                });
            });
    }
}
