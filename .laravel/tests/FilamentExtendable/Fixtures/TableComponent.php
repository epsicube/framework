<?php

declare(strict_types=1);

namespace UniGale\Tests\FilamentExtendable\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;

class TableComponent extends Component implements HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;

    public static function make(): static
    {
        return new static;
    }
}
