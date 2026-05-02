<?php

declare(strict_types=1);

namespace Epsicube\Tests;

use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Filesystem $files;

    protected string $workspace;

    protected function setUp(): void
    {
        $this->files = new Filesystem;
        $this->workspace = TestApplicationFactory::createWorkspace();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    /**
     * @param  list<class-string>  $modules
     * @param  array<string, bool>  $state
     */
    protected function configureModules(array $modules, array $state = []): void
    {
        $this->writePhpFile($this->workspace.'/bootstrap/modules.php', $modules);

        $this->writePhpFile($this->workspace.'/bootstrap/modules-activation.php', $state);

        $this->refreshApplication();
    }

    /**
     * @return array<string, bool>
     */
    protected function readActivationState(): array
    {
        $path = $this->workspace.'/bootstrap/modules-activation.php';

        /** @var array<string, bool> $state */
        $state = $this->files->getRequire($path);

        return $state;
    }

    protected function module(string $identifier): Module
    {
        return Modules::get($identifier);
    }

    protected function moduleStatus(string $identifier): ModuleStatus
    {
        return $this->module($identifier)->status;
    }

    protected function bootstrapLogs(?string $identifier = null): array
    {
        return Modules::getBootstrapLogs($identifier);
    }

    public function createApplication()
    {
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        return TestApplicationFactory::boot($this->workspace);
    }

    protected function writePhpFile(string $path, mixed $payload): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->replace($path, "<?php\n\nreturn ".var_export($payload, true).";\n");
    }
}
