<?php

declare(strict_types=1);

use EpsicubeModules\Administration\Administration;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;

test('registered panel instanceof "AdminPanel"', function () {
    $panel = Filament::getPanel(config('epsicube.administration.id'));
    expect($panel)->not->toBeNull()->toBeInstanceOf(Administration::class);
});

test('admin panel configuration is applied to panel', function (string $configKey, mixed $expected, Closure $extractor) {
    config()->set("epsicube.{$configKey}", $expected);
    $panel = Filament::getPanel(config('epsicube.administration.id'));
    expect($panel)->not->toBeNull()->and($extractor($panel))->toBe($expected);
})->with([
    'id'         => ['central-panel.id', fake()->domainWord(), fn (Administration $panel) => $panel->getId()],
    'brand_name' => ['central-panel.brand_name', fake()->uuid(), fn (Administration $panel) => $panel->getBrandName()],
    'path'       => ['central-panel.path', fake()->uuid(), fn (Administration $panel) => $panel->getPath()],
    'domain'     => ['central-panel.domain', fake()->domainName(), fn (Administration $panel) => array_last($panel->getDomains())],
    'colors'     => ['central-panel.colors', ['primary' => Color::generatePalette('#0e2121')], fn (Administration $panel) => $panel->getColors()],
]);

test('set admin panel configuration using environment variables', function (string $configKey, string $envKey) {
    $original = getenv($envKey);

    try {
        $expected = fake()->uuid();
        putenv("{$envKey}={$expected}");
        $this->refreshApplication();
        expect(config("epsicube.{$configKey}"))->toBe($expected);
    } finally {
        if ($original === false) {
            putenv($envKey);
        } else {
            putenv("{$envKey}={$original}");
        }
    }
})->with([
    'id'         => ['central-panel.id', 'epsicube_ADMIN_PANEL_ID'],
    'path'       => ['central-panel.path', 'epsicube_ADMIN_PANEL_PATH'],
    'domain'     => ['central-panel.domain', 'epsicube_ADMIN_PANEL_DOMAIN'],
    'brand_name' => ['central-panel.brand_name', 'epsicube_ADMIN_PANEL_BRAND_NAME'],
]);

test('applies the callback passed to Panel::configureUsing', function () {
    $expected = 'Rebranded test panel';
    Administration::configureUsing(fn (Administration $panel) => $panel->brandName($expected));
    $panel = Filament::getPanel(config('epsicube.administration.id'));
    expect($panel?->getBrandName())->toBe($expected);
});

test('configureUsing callback overrides admin panel configuration', function () {
    $expected = 'Callback Brand';
    config()->set('epsicube.administration.brand_name', 'Configured Brand');
    Administration::configureUsing(fn (Administration $panel) => $panel->brandName($expected));
    expect(Filament::getPanel(config('epsicube.administration.id'))?->getBrandName())->toBe($expected);
});
