<?php

declare(strict_types=1);

arch('it will not use dev functions')->expect([
    'dd',
    'ddd',
    'dump',
    'env',
    'ray',
])->not->toBeUsed();

test('configuration files are serializable', function ($configPath) {
    expect(file_exists($configPath))->toBeTrue();

    $config = require $configPath;

    $exported = var_export($config, true);
    expect($exported)->toBeString()->not->toBeEmpty();

    $reconstructed = null;
    eval('$reconstructed = '.$exported.';');
    expect($reconstructed)->toBe($config);
})->with(function () {
    return array_merge(
        glob(__DIR__.'/../config/*.php'),
        glob(__DIR__.'/../src/Core/Modules/*/config/*.php'),
    );

    return array_combine(array_map('basename', $files), $files);
})->skip(function () {
    $safeMode = ini_get('safe_mode');
    if (ini_get('safe_mode') && mb_strtolower($safeMode) !== 'off') {
        return false;
    }

    return in_array('eval', explode(',', (string) ini_get('disable_functions')), true);
}, 'safe_mode enabled or eval function disabled');
