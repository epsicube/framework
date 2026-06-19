<?php

declare(strict_types=1);

namespace Epsicube\Tests;

use Epsicube\Foundation\Managers\OptionsManager;
use Epsicube\Support\Exceptions\BootstrapEpsicubeException;
use Epsicube\Support\Facades\Options;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Throwable;

final class TestApplicationFactory
{
    public static function createWorkspace(?string $suffix = null): string
    {
        $files = new Filesystem;
        $workspace = sys_get_temp_dir().'/epsicube-tests/'.($suffix ?? bin2hex(random_bytes(8)));

        $files->deleteDirectory($workspace);
        $files->copyDirectory(__DIR__.'/Fixtures/Application', $workspace);

        $files->replace(
            $workspace.'/artisan',
            str_replace(
                '__EPSICUBE_VENDOR_AUTOLOAD__',
                addslashes(dirname(__DIR__).'/vendor/autoload.php'),
                $files->get($workspace.'/artisan'),
            ),
        );
        $files->replace($workspace.'/.env', '');

        $files->ensureDirectoryExists($workspace.'/bootstrap/cache');
        $files->ensureDirectoryExists($workspace.'/storage/framework/cache/data');
        $files->ensureDirectoryExists($workspace.'/storage/framework/sessions');
        $files->ensureDirectoryExists($workspace.'/storage/framework/views');
        $files->ensureDirectoryExists($workspace.'/storage/logs');
        $files->ensureDirectoryExists($workspace.'/vendor/composer');
        $files->copy(dirname(__DIR__).'/vendor/composer/installed.json', $workspace.'/vendor/composer/installed.json');

        return $workspace;
    }

    public static function boot(string $workspace): object
    {
        $app = self::makeApplication($workspace);

        try {
            $app->make(Kernel::class)->bootstrap();
        } catch (Throwable $e) {
            if (! self::shouldRecoverFromFallback($e)) {
                throw $e;
            }

            self::bootProviders($app);
            self::primeCoreDatabase($app);
            self::resetOptions($app);

            $app = self::makeApplication($workspace);
            $app->make(Kernel::class)->bootstrap();
        }

        return $app;
    }

    public static function bootForParallelProcess(): object
    {
        $token = (string) ($_SERVER['TEST_TOKEN'] ?? 'parallel');

        return self::boot(self::createWorkspace('parallel-'.$token));
    }

    public static function resetOptions(?Application $app): void
    {
        if ($app !== null && $app->bound('foundation-options')) {
            $options = $app->make('foundation-options');

            if ($options instanceof OptionsManager) {
                $options->flush();
            }
        }

        if ($app !== null) {
            $app->forgetInstance('foundation-options');
            $app->forgetInstance(Options::$accessor);
        }

        Options::clearResolvedInstance('foundation-options');
        Options::clearResolvedInstance(Options::$accessor);
    }

    private static function makeApplication(string $workspace): Application
    {
        /** @var Application $app */
        $app = require $workspace.'/bootstrap/app.php';
        self::restoreInMemoryDatabase($app);
        self::resetOptions($app);

        return $app;
    }

    private static function restoreInMemoryDatabase(Application $app): void
    {
        if (RefreshDatabaseState::$inMemoryConnections === []) {
            return;
        }

        $app->resolving('db', function ($database) use ($app): void {
            $connection = $app['config']->get('database.default');

            if (! is_string($connection)) {
                return;
            }

            if ($app['config']->get("database.connections.{$connection}.database") !== ':memory:') {
                return;
            }

            $pdo = RefreshDatabaseState::$inMemoryConnections[$connection] ?? null;
            if ($pdo === null) {
                return;
            }

            $database->connection($connection)->setPdo($pdo)->setReadPdo($pdo);
        });
    }

    private static function shouldRecoverFromFallback(Throwable $e): bool
    {
        return $e instanceof BootstrapEpsicubeException;
    }

    private static function bootProviders(Application $app): void
    {
        $app->bootstrapWith([BootProviders::class]);
    }

    private static function primeCoreDatabase(Application $app): void
    {
        $kernel = $app->make(Kernel::class);
        $kernel->call('migrate', ['--force' => true]);
        $kernel->setArtisan(null);

        $connection = $app['config']->get('database.default');

        if (! is_string($connection)) {
            return;
        }

        if ($app['config']->get("database.connections.{$connection}.database") !== ':memory:') {
            return;
        }

        $pdo = $app->make('db')->connection($connection)->getPdo();

        if ($pdo === null) {
            return;
        }

        RefreshDatabaseState::$inMemoryConnections[$connection] = $pdo;
    }
}
