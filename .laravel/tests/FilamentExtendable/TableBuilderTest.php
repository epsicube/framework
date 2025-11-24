<?php

declare(strict_types=1);

use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use modules\FilamentExtendable\Exceptions\TableGroupNotFoundException;
use UniGale\FilamentExtendable\Builders\TableBuilder;
use UniGale\FilamentExtendable\Enums\InsertPosition;
use UniGale\Tests\FilamentExtendable\Fixtures\TableComponent;

beforeEach(function (): void {
    $this->table = Table::make(TableComponent::make());
    $this->builder = new TableBuilder($this->table);
});

test('can push columns at the end of root', function (): void {
    $this->table->columns([TextColumn::make('email')]);
    $this->builder->pushColumns([TextColumn::make('name')], position: InsertPosition::AFTER);

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['email', 'name']);
});

test('can push columns at the start of root', function (): void {
    $this->table->columns([TextColumn::make('email')]);
    $this->builder->pushColumns([TextColumn::make('name')], position: InsertPosition::BEFORE);

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['name', 'email']);
});

test('can insert before a specific column at root', function (): void {
    $this->table->columns([
        TextColumn::make('email'),
        TextColumn::make('lastname'),
    ]);

    $this->builder->pushColumns(
        [TextColumn::make('firstname')],
        InsertPosition::BEFORE,
        targetKey: 'lastname'
    );

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['email', 'firstname', 'lastname']);
});

test('can insert after a specific column at root', function (): void {
    $this->table->columns([
        TextColumn::make('email'),
        TextColumn::make('lastname'),
    ]);

    $this->builder->pushColumns(
        [TextColumn::make('middlename')],
        InsertPosition::AFTER,
        targetKey: 'email'
    );

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['email', 'middlename', 'lastname']);
});

test('can push component when target column not exists', function (): void {
    $this->table->columns([TextColumn::make('name')]);
    $this->builder->pushColumns(
        [TextColumn::make('name_before')], InsertPosition::BEFORE, targetKey: 'missing'
    );
    $this->builder->pushColumns(
        [TextColumn::make('name_after')], InsertPosition::AFTER, targetKey: 'missing'
    );

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['name_before', 'name', 'name_after']);
});

test('ensure named column are respected as target key', function (): void {
    $this->table->columns([
        TextColumn::make('name'),
        TextColumn::make('name')->name('name_alias'),
    ]);
    $this->builder->pushColumns(
        [TextColumn::make('name_before')], InsertPosition::BEFORE, targetKey: 'name_alias'
    );

    $keys = array_keys($this->table->getColumns());
    expect($keys)->toBe(['name', 'name_before', 'name_alias']);
});

test('inserting columns preserves existing layout components', function (): void {
    // Initialize table with a Stack layout containing two text columns
    $this->table->columns([
        Stack::make([
            TextColumn::make('first_name'),
            TextColumn::make('last_name'),
        ]),
    ]);

    // Insert a new column before the 'last_name' column
    $this->builder->pushColumns(
        [TextColumn::make('middle_name')],
        InsertPosition::BEFORE,
        targetKey: 'last_name'
    );

    // Verify the columns are flattened correctly
    $visibleColumnKeys = array_keys($this->table->getColumns());
    expect($visibleColumnKeys)->toBe(['first_name', 'middle_name', 'last_name']);

    // Retrieve the layout structure
    $layout = $this->table->getColumnsLayout();

    // Assert there is one top-level layout component and it's a Stack
    expect($layout)->toHaveCount(1);
    expect($layout[0])->toBeInstanceOf(Stack::class);

    // Check the Stack's internal structure
    /** @var Stack $stack */
    $stack = $layout[0];
    $innerComponents = $stack->getComponents();

    $innerKeys = array_map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null, $innerComponents);
    expect($innerKeys)->toBe(['first_name', 'middle_name', 'last_name']);
});

test('inserting columns preserves existing column groups', function (): void {
    // Initialize table with a ColumnGroup containing two text columns
    $this->table->columns([
        ColumnGroup::make('group', [
            TextColumn::make('first_name'),
            TextColumn::make('last_name'),
        ]),
    ]);

    // Insert a new column before the 'last_name' column inside the group
    $this->builder->pushColumns(
        [TextColumn::make('middle_name')],
        InsertPosition::BEFORE,
        targetKey: 'last_name',
        targetGroup: 'group'
    );

    $groups = $this->table->getColumnGroups();
    expect($groups)->toHaveCount(1)->toHaveKey('group')
        ->and($groups['group'])->toBeInstanceOf(ColumnGroup::class);

    /** @var ColumnGroup $group */
    $group = $groups['group'];
    $groupKeys = array_keys($group->getColumns());
    expect($groupKeys)->toBe(['first_name', 'middle_name', 'last_name']);
});

test('Ensure pushed columns respect target group and are inserted correctly', function (): void {
    // Initial setup with two groups
    $this->table->columns([
        ColumnGroup::make('group', [
            TextColumn::make('name'),
            TextColumn::make('email'),
        ]),
        ColumnGroup::make('group2', [
            TextColumn::make('email_verified_at'),
            TextColumn::make('created_at'),
        ]),
    ]);

    // Push 'created_at' before 'email' in 'group'
    $this->builder->pushColumns([
        TextColumn::make('created_at'),
    ], InsertPosition::BEFORE, 'email', 'group');

    // Push 'updated_at' before 'email_verified_at' in 'group2'
    $this->builder->pushColumns([
        TextColumn::make('updated_at'),
    ], InsertPosition::BEFORE, 'email_verified_at', 'group2');

    // Retrieve groups
    $group1 = $this->table->getColumnGroup('group');
    $group2 = $this->table->getColumnGroup('group2');

    // Get column keys per group
    $group1Keys = array_keys($group1->getColumns());
    $group2Keys = array_keys($group2->getColumns());

    // Assertions
    expect($group1Keys)->toBe(['name', 'created_at', 'email']);
    expect($group2Keys)->toBe(['updated_at', 'email_verified_at', 'created_at']);
});

test('throws exception when target group not found', function (): void {
    $this->builder->pushColumns([TextColumn::make('email')], targetGroup: 'nonexistent');
})->throws(TableGroupNotFoundException::class);

// REMOVING Columns
test('can remove columns', function (): void {
    $this->table->columns([
        TextColumn::make('phone'),
        TextColumn::make('email'),
        TextColumn::make('name'),
    ]);

    $this->builder->removeColumns(['phone', 'name']);
    $keys = array_keys($this->table->getColumns());

    expect($keys)->toBe(['email']);
});

test('can remove alias columns', function (): void {
    $this->table->columns([
        TextColumn::make('phone'),
        TextColumn::make('email'),
        TextColumn::make('name')->name('name_alias'),
    ]);

    $this->builder->removeColumns(['phone', 'name_alias']);
    $keys = array_keys($this->table->getColumns());

    expect($keys)->toBe(['email']);
});

test('remove column silently ignore if column not exists', function (): void {
    $this->table->columns([TextColumn::make('email')]);

    $this->builder->removeColumns(['user.email']); // group does not exist
    $keys = array_keys($this->table->getColumns());

    expect($keys)->toBe(['email']);
});
