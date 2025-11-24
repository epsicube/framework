<?php

declare(strict_types=1);

use modules\FilamentExtendable\Facades\FilamentExtendable;
use modules\FilamentExtendable\FilamentExtendableServiceProvider;
use UniGale\FilamentExtendable\FilamentExtendableManager;

test('Ensure provider boot without error', function (): void {
    expect($this->app->providerIsLoaded(FilamentExtendableServiceProvider::class))->toBe(true);
});

test('Ensure manager exists in container and it`s a singleton', function (): void {
    // Check that the manager is bound in the container
    expect($this->app->bound(FilamentExtendableManager::class))->toBeTrue();

    // Retrieve two instances of the manager from the container
    $instance1 = $this->app->make(FilamentExtendableManager::class);
    $instance2 = $this->app->make(FilamentExtendableManager::class);

    // Assert that both instances are the same (singleton)
    expect(spl_object_id($instance1))->toBe(spl_object_id($instance2));
});

test('Ensure facade resolves the same singleton instance', function (): void {
    $instanceFromContainer = $this->app->make(FilamentExtendableManager::class);
    $instanceFromFacade = FilamentExtendable::getFacadeRoot();
    expect(spl_object_id($instanceFromContainer))->toBe(spl_object_id($instanceFromFacade));
});

test('Ensure flush method clears all table modifiers correctly', function (): void {
    $manager = FilamentExtendable::getFacadeRoot();

    $manager->addTableModifier('test-table', fn (): null => null)
        ->addSchemaModifier('test-schema', fn (): null => null);

    expect($manager->getTableModifiers('test-table'))->toHaveCount(1)
        ->and($manager->getSchemaModifiers('test-schema'))->toHaveCount(1);

    $manager->flush();

    expect($manager->getTableModifiers('test'))->toBeEmpty()
        ->and($manager->getSchemaModifiers('test'))->toBeEmpty();
});
