<?php

declare(strict_types=1);

namespace Epsicube\Support\Facades;

use Composer\InstalledVersions;
use Epsicube\Foundation\Managers\EpsicubeManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Facade;
use OutOfBoundsException;
use RuntimeException;

class Epsicube extends Facade
{
    public static string $accessor = EpsicubeManager::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function resolveComposerVersion(string ...$packages): string
    {
        foreach ($packages as $package) {
            try {
                $version = InstalledVersions::getPrettyVersion($package);
                if (! empty($version)) {
                    return $version;
                }
            } catch (OutOfBoundsException $e) {
                continue;
            }
        }
        throw new RuntimeException(sprintf('Could not resolve composer version for packages: %s.', implode(', ', $packages)));
    }

    public static function version(): string
    {
        return static::resolveComposerVersion('epsicube/foundation', 'epsicube/framework');
    }

    public static function callArtisanCommand(string $command): ProcessResult
    {
        /** @var EpsicubeManager $manager */
        $manager = static::getFacadeRoot();

        return $manager->callArtisanCommand($command);
    }

    public static function clearCache(): ProcessResult
    {
        /** @var EpsicubeManager $manager */
        $manager = static::getFacadeRoot();

        return $manager->clearCache();
    }

    public static function generateCache(): ProcessResult
    {
        /** @var EpsicubeManager $manager */
        $manager = static::getFacadeRoot();

        return $manager->generateCache();
    }

    public static function terminateWorker(): ProcessResult
    {
        /** @var EpsicubeManager $manager */
        $manager = static::getFacadeRoot();

        return $manager->terminateWorker();
    }

    /**
     * @param  string|class-string<Command>  $command
     */
    public static function addWorkCommand(string $key, string $command): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $command): void {
            $manager->addWorkCommand($key, $command);
        });
    }

    /**
     * @param  string|class-string<Command>  $command
     */
    public static function addInstallCommand(string $key, string $command): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $command): void {
            $manager->addInstallCommand($key, $command);
        });
    }

    public static function optimizes(string $key, ?string $optimizeCmd = null, ?string $clearCmd = null): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $optimizeCmd, $clearCmd): void {
            if ($optimizeCmd) {
                $manager->addOptimizeCommand($key, $optimizeCmd);
            }
            if ($clearCmd) {
                $manager->addClearCommand($key, $optimizeCmd);
            }
        });
    }
}
