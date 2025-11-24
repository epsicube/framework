<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use modules\FilamentExtendable\Builders\SchemaBuilder;
use modules\FilamentExtendable\Exceptions\SchemaGroupNotFoundException;
use UniGale\FilamentExtendable\Enums\InsertPosition;
use UniGale\Tests\FilamentExtendable\Fixtures\SchemaComponent;

beforeEach(function (): void {
    $this->schema = Schema::make(SchemaComponent::make());
    $this->builder = new SchemaBuilder($this->schema);
});

test('can push component at the end of root', function (): void {
    $this->schema->components([TextInput::make('email')]);
    $this->builder->pushComponents([TextInput::make('name')]);

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['email', 'name']);
});

test('can push component at the start of root', function (): void {
    $this->schema->components([TextInput::make('email')]);
    $this->builder->pushComponents([TextInput::make('name')], InsertPosition::BEFORE);

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['name', 'email']);
});

test('can insert before a specific component at root', function (): void {
    $this->schema->components([
        TextInput::make('email'),
        TextInput::make('lastname'),
    ]);

    $this->builder->pushComponents(
        [TextInput::make('firstname')],
        InsertPosition::BEFORE,
        targetKey: 'lastname'
    );

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['email', 'firstname', 'lastname']);
});

test('can insert after a specific component at root', function (): void {
    $this->schema->components([
        TextInput::make('email'),
        TextInput::make('lastname'),
    ]);

    $this->builder->pushComponents(
        [TextInput::make('middlename')],
        InsertPosition::AFTER,
        targetKey: 'email'
    );

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['email', 'middlename', 'lastname']);
});

test('can push component when target component not exists', function (): void {
    $this->schema->components([TextInput::make('name')]);
    $this->builder->pushComponents(
        [TextInput::make('name_before')], InsertPosition::BEFORE, targetKey: 'missing'
    );
    $this->builder->pushComponents(
        [TextInput::make('name_after')], InsertPosition::AFTER, targetKey: 'missing'
    );

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['name_before', 'name', 'name_after']);
});

test('can insert into nested group specific component', function (): void {
    $this->schema->components([
        Group::make([
            TextInput::make('lastname'),
            Group::make([
                TextInput::make('phone'),
            ])->key('personal'),
        ])->key('profile'),
    ]);

    $this->builder->pushComponents(
        [TextInput::make('firstname')],
        InsertPosition::BEFORE,
        targetKey: 'lastname',
        targetGroup: 'profile'
    );
    $this->builder->pushComponents(
        [TextInput::make('email')],
        targetGroup: 'profile.personal'
    );

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe([
        'profile', 'profile.firstname', 'profile.lastname',
        'profile.personal', 'profile.personal.phone', 'profile.personal.email',
    ]);
});

test('throws exception when target group not found', function (): void {
    $this->builder->pushComponents([TextInput::make('email')], targetGroup: 'nonexistent.group');
})->throws(SchemaGroupNotFoundException::class);

// REMOVING COMPONENTS

test('can remove root components', function (): void {
    $this->schema->components([
        TextInput::make('email'),
        TextInput::make('name'),
        Group::make()->key('test'),
    ]);

    $this->builder->removeComponents(['name']);
    $keys = array_keys($this->schema->getFlatComponents());

    expect($keys)->toBe(['email', 'test']);
});

test('can remove nested component using dot notation', function (): void {
    $this->schema->components([
        Group::make([
            TextInput::make('firstname'),
            TextInput::make('lastname'),
            Group::make([
                TextInput::make('email'),
                TextInput::make('phone'),
            ])->key('personal'),
        ])->key('user'),
    ]);

    $this->builder->removeComponents(['user.lastname', 'user.personal.phone']);

    $keys = array_keys($this->schema->getFlatComponents());
    expect($keys)->toBe(['user', 'user.firstname', 'user.personal', 'user.personal.email']);
});

test('remove component silently ignore if component not exists', function (): void {
    $this->schema->components([TextInput::make('email')]);

    $this->builder->removeComponents(['user.email']); // group does not exist
    $keys = array_keys($this->schema->getFlatComponents());

    expect($keys)->toBe(['email']);
});
