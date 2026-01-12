<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Support\Facades\Options;

class AdministrationOptions
{
    public static function definition(): array
    {
        return [
            'enable-modules-manager' => BooleanProperty::make()
                ->title(__('Enable Module Manager'))
                ->description(__('Enables the integrated module management system, allowing modules to be enabled, disabled, or configured.'))
                ->optional()
                ->default(true),

            'brand-name' => StringProperty::make()
                ->title(__('Brand Name'))
                ->description(__('Defines the display name used throughout the administration interface.'))
                ->nullable()
                ->optional()
                ->default(null),

            'spa' => BooleanProperty::make()
                ->title(__('Single Page Application'))
                ->description(__('Enables SPA mode to improve navigation performance and reduce full page reloads.'))
                ->optional()
                ->default(true),

            'top-navigation' => BooleanProperty::make()
                ->title(__('Top Navigation'))
                ->description(__('Displays the primary navigation bar at the top instead of the sidebar.'))
                ->optional()
                ->default(true),

            'application-navigation' => BooleanProperty::make()
                ->title(__('Application Navigation'))
                ->description(__('Restricts visible navigation items to the current application and injects an application switcher instead of the logo.'))
                ->optional()
                ->default(true),

            'path' => StringProperty::make()
                ->title(__('Path'))
                ->description(__('Defines the subpath where the administration panel is served (e.g. /admin or /dashboard).'))
                ->optional()
                ->default('/admin'),

            'domain' => StringProperty::make()
                ->title(__('Domain'))
                ->description(__('Restricts the administration panel to a specific domain. Leave empty to allow all domains.'))
                ->nullable()
                ->optional()
                ->default(null),
        ];
    }

    public static function all(): array
    {
        return Options::all('core::administration');
    }

    public static function isModulesManagerEnabled(): bool
    {
        return Options::get('core::administration', 'enable-modules-manager');
    }

    public static function brandName(): ?string
    {
        return Options::get('core::administration', 'brand-name');
    }

    public static function isSpaEnabled(): bool
    {
        return Options::get('core::administration', 'spa');
    }

    public static function hasTopNavigation(): bool
    {
        return Options::get('core::administration', 'top-navigation');
    }

    public static function hasApplicationNavigation(): bool
    {
        return Options::get('core::administration', 'application-navigation');
    }

    public static function path(): string
    {
        return Options::get('core::administration', 'path');
    }

    public static function domain(): ?string
    {
        return Options::get('core::administration', 'domain');
    }
}
