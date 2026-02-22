<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules\Conditions;

use Epsicube\Support\Concerns\Condition;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostgresqlExtensions extends Condition
{
    /** @var string[] */
    private array $extensions;

    /** @var string[] */
    private array $missing = [];

    private ?string $error = null;

    public function __construct(string|array $extensions, private readonly ?string $connection = null)
    {
        $this->extensions = (array) $extensions;
    }

    public function group(): string
    {
        return 'Environment';
    }

    public function name(): string
    {
        return 'PostgreSQL Extensions';
    }

    public function check(): bool
    {
        try {
            $db = DB::connection($this->connection);

            $found = $db->table('pg_extension')
                ->whereIn('extname', $this->extensions)
                ->pluck('extname')
                ->toArray();

            $this->missing = array_diff($this->extensions, $found);

            return empty($this->missing);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();

            return false;
        }
    }

    public function successMessage(): string
    {
        return sprintf('PostgreSQL extensions [%s] are active.', implode(', ', $this->extensions));
    }

    public function failMessage(): string
    {
        if ($this->error) {
            return "PostgreSQL extension check failed: {$this->error}";
        }

        return sprintf('Missing PostgreSQL extensions: [%s].', implode(', ', $this->missing));
    }
}
