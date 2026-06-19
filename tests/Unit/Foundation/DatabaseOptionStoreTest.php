<?php

declare(strict_types=1);

use Epsicube\Foundation\Utilities\DatabaseOptionStore;
use Epsicube\Schemas\Types\UndefinedValue;

test('database options store is functional when options table exists', function () {
    expect((new DatabaseOptionStore)->isFunctional())->toBeTrue();
});

test('database options store persists and retrieves json values', function () {
    $store = new DatabaseOptionStore;

    $store->set('enabled', true, 'tests::options');
    $store->set('settings', ['theme' => 'red'], 'tests::options');
    $store->set('empty', null, 'tests::options');

    expect($store->get('enabled', 'tests::options'))->toBeTrue()
        ->and($store->get('settings', 'tests::options'))->toBe(['theme' => 'red'])
        ->and($store->get('empty', 'tests::options'))->toBeNull()
        ->and($store->get('missing', 'tests::options'))->toBeInstanceOf(UndefinedValue::class)
        ->and($store->all('tests::options'))->toBe([
            'enabled'  => true,
            'settings' => ['theme' => 'red'],
            'empty'    => null,
        ]);
});
