<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Epsicube\Support\Concerns\Condition;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseDrivers extends Condition
{
    private array $drivers;

    private ?string $actualDriver = null;

    private ?string $error = null;

    public function __construct(string|array $drivers, private readonly ?string $connection = null)
    {
        $this->drivers = (array) $drivers;
    }

    public function group(): string
    {
        return 'Environment';
    }

    public function name(): string
    {
        return 'Database Driver';
    }

    public function check(): bool
    {
        try {
            $this->actualDriver = DB::connection($this->connection)->getDriverName();

            return in_array($this->actualDriver, $this->drivers, true);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();

            return false;
        }
    }

    public function successMessage(): string
    {
        return "Database driver '{$this->actualDriver}' is compatible.";
    }

    public function failMessage(): string
    {
        if ($this->error) {
            return "Could not determine database driver: {$this->error}";
        }

        $expected = implode(', ', $this->drivers);

        return "Database driver mismatch. Expected [{$expected}], but current connection uses '{$this->actualDriver}'.";
    }
}
