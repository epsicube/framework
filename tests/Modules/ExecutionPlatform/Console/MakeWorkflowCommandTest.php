<?php

use Illuminate\Support\Facades\File;

it('can generate a workflow', function () {
    $workflowName = 'TestWorkflow';
    $filePath = base_path('modules/ExecutionPlatform/Workflows/' . $workflowName . '.php');

    if (File::exists($filePath)) {
        File::delete($filePath);
    }

    $this->artisan('make:workflow', ['name' => $workflowName])
        ->assertExitCode(0);

    expect(File::exists($filePath))->toBeTrue();

    $content = File::get($filePath);
    expect($content)->toContain('namespace EpsicubeModules\ExecutionPlatform\Workflows;')
        ->toContain('class TestWorkflow implements Workflow')
        ->toContain("return 'test_workflow';");

    File::delete($filePath);
});
