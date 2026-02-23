<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Utilities;

use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Modules\Module;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class FilesystemActivationDriver implements ActivationDriver
{
    protected array $state;

    protected array $mustUseModules = [];

    public function __construct(protected Filesystem $files, protected string $path) {}

    public function enable(Module $module): void
    {
        $this->ensureStateIsLoaded();

        $this->state[$module->identifier] = true;

        $this->saveState($this->state);
    }

    public function disable(Module $module): void
    {
        $this->ensureStateIsLoaded();

        $this->state[$module->identifier] = false;

        $this->saveState($this->state);
    }

    public function isEnabled(Module $module): bool
    {
        $this->ensureStateIsLoaded();

        return $this->state[$module->identifier] ?? false;
    }

    public function isMustUse(Module $module): bool
    {
        return isset($this->mustUseModules[$module->identifier]);
    }

    public function markAsMustUse(Module $module): void
    {
        $this->mustUseModules[$module->identifier] = true;
    }

    protected function ensureStateIsLoaded(): void
    {
        if (isset($this->state)) {
            return;
        }

        try {
            $data = $this->files->getRequire($this->path);
            $this->state = is_array($data) ? $data : [];
        } catch (FileNotFoundException) {
            $this->state = [];
        } catch (Throwable $e) {
            report($e);
            $this->state = [];
        }
    }

    protected function saveState(array $state): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));

        $this->state = $state;

        $contents = "<?php\n\nreturn ".var_export($this->state, true).";\n";

        $this->files->replace($this->path, $contents);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->path, true); // ensure next require don't use cache
        }
    }
}
