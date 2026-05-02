<?php

declare(strict_types=1);

namespace Epsicube\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;

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

        return $workspace;
    }

    public static function boot(string $workspace): object
    {
        $app = require $workspace.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public static function bootForParallelProcess(): object
    {
        $token = (string) ($_SERVER['TEST_TOKEN'] ?? 'parallel');

        return self::boot(self::createWorkspace('parallel-'.$token));
    }
}
