<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use modules\FilamentExtendable\Builders\SchemaBuilder;
use modules\FilamentExtendable\Facades\FilamentExtendable;
use UniGale\FilamentExtendable\Builders\TableBuilder;
use UniGale\Tests\FilamentExtendable\Fixtures\SchemaComponent;
use UniGale\Tests\FilamentExtendable\Fixtures\TableComponent;

test('Ensure schema can be extended at runtime', function (): void {
    static $identifier = 'test-schema';
    FilamentExtendable::addSchemaModifier($identifier, function (SchemaBuilder $schemaBuilder): void {
        $schemaBuilder->pushComponents([TextInput::make('password')]);
    });

    $schema = FilamentExtendable::processSchema(
        schema: Schema::make(SchemaComponent::make())->components([TextInput::make('name')]),
        identifier: $identifier
    );

    $keys = array_keys($schema->getFlatComponents());
    expect($keys)->toBe(['name', 'password']);
});

test('Ensure schema modifiers are executed in order', function (): void {
    static $identifier = 'test-schema-order';

    FilamentExtendable::addSchemaModifier($identifier, function (SchemaBuilder $schemaBuilder): void {
        $schemaBuilder->pushComponents([TextInput::make('password_20')]);
    }, 20);

    FilamentExtendable::addSchemaModifier($identifier, function (SchemaBuilder $schemaBuilder): void {
        $schemaBuilder->pushComponents([TextInput::make('password_10')]);
    }, 10);

    $schema = FilamentExtendable::processSchema(
        schema: Schema::make(SchemaComponent::make())->components([TextInput::make('name')]),
        identifier: $identifier
    );

    $keys = array_keys($schema->getFlatComponents());
    expect($keys)->toBe(['name', 'password_10', 'password_20']);
});

test('Ensure schema modifiers are not stacked out of context', function (int $iter): void {
    static $identifier = 'test-schema-stack';
    FilamentExtendable::addSchemaModifier($identifier, function (SchemaBuilder $schemaBuilder) use ($iter): void {
        $schemaBuilder->pushComponents([TextInput::make("password_iter_{$iter}")]);
    }, $iter);

    $schema = FilamentExtendable::processSchema(schema: Schema::make(SchemaComponent::make()), identifier: $identifier);

    $keys = array_keys($schema->getFlatComponents());
    expect($keys)->toBe(["password_iter_{$iter}"]);
})->with(range(0, 2));

test('Ensure table can be extended at runtime', function (): void {
    static $identifier = 'test-table';
    FilamentExtendable::addTableModifier($identifier, function (TableBuilder $tableBuilder): void {
        $tableBuilder->pushColumns([TextColumn::make('password')]);
    });

    $table = FilamentExtendable::processTable(
        table: Table::make(TableComponent::make())->columns([TextColumn::make('name')]),
        identifier: $identifier
    );

    $keys = array_keys($table->getColumns());
    expect($keys)->toBe(['name', 'password']);
});

test('Ensure table modifiers are executed in order', function (): void {
    static $identifier = 'test-table';
    FilamentExtendable::addTableModifier($identifier, function (TableBuilder $tableBuilder): void {
        $tableBuilder->pushColumns([TextColumn::make('password_20')]);
    }, 20);
    FilamentExtendable::addTableModifier($identifier, function (TableBuilder $tableBuilder): void {
        $tableBuilder->pushColumns([TextColumn::make('password_10')]);
    }, 10);

    $table = FilamentExtendable::processTable(
        table: Table::make(TableComponent::make())->columns([TextColumn::make('name')]),
        identifier: $identifier
    );

    $keys = array_keys($table->getColumns());
    expect($keys)->toBe(['name', 'password_10', 'password_20']);
});

test('Ensure table modifiers are not stacked out of context', function (int $iter): void {
    static $identifier = 'test-table-stack';
    FilamentExtendable::addTableModifier($identifier, function (TableBuilder $tableBuilder) use ($iter): void {
        $tableBuilder->pushColumns([TextColumn::make("password_iter_{$iter}")]);
    }, $iter);

    $table = FilamentExtendable::processTable(table: Table::make(TableComponent::make()), identifier: $identifier);

    $keys = array_keys($table->getColumns());
    expect($keys)->toBe(["password_iter_{$iter}"]);
})->with(range(0, 2));
