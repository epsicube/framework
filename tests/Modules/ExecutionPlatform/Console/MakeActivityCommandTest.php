<?php

use Illuminate\Support\Facades\File;

it('can generate an activity', function () {
    $activityName = 'TestActivity';
    $filePath = base_path('modules/ExecutionPlatform/Activities/' . $activityName . '.php');

    if (File::exists($filePath)) {
        File::delete($filePath);
    }

    $this->artisan('make:activity', ['name' => $activityName])
        ->assertExitCode(0);

    expect(File::exists($filePath))->toBeTrue();

    $content = File::get($filePath);
    expect($content)->toContain('namespace EpsicubeModules\ExecutionPlatform\Activities;')
        ->toContain('class TestActivity implements Activity')
        ->toContain("return 'test_activity';");

    File::delete($filePath);
});
