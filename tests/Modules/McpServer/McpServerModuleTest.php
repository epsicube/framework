<?php

declare(strict_types=1);

use Epsicube\Support\Facades\Options;
use Epsicube\Tests\Modules\McpServer\Fixtures\TestResource;
use Epsicube\Tests\Modules\McpServer\Fixtures\TestTool;
use EpsicubeModules\McpServer\Facades\Resources;
use EpsicubeModules\McpServer\Facades\Tools;
use EpsicubeModules\McpServer\Mcp\Servers\McpServer;
use EpsicubeModules\McpServer\McpServerModule;
use EpsicubeModules\McpServer\Registries\ResourcesRegistry;
use EpsicubeModules\McpServer\Registries\ToolsRegistry;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('mcp server module declares expected metadata and default options', function () {
    $this->configureModules([
        McpServerModule::class,
    ], [
        'core::mcp-server' => true,
    ]);

    $module = $this->module('core::mcp-server');
    $instructionsStub = file_get_contents(__DIR__.'/../../../modules/McpServer/resources/stubs/INSTRUCTIONS.md');

    expect($module->identifier)->toBe('core::mcp-server')
        ->and($module->identity->name)->toBe('MCP Server')
        ->and(array_keys($module->options->properties()))->toBe(['name', 'version', 'instructions'])
        ->and(Options::get('core::mcp-server', 'name'))->toBe(config('app.name').' internal MCP Server')
        ->and(Options::get('core::mcp-server', 'version'))->toBe($module->version)
        ->and(Options::get('core::mcp-server', 'instructions'))->toBe($instructionsStub);
});

test('mcp server builds its runtime context from registered tools resources and options', function () {
    $this->configureModules([
        McpServerModule::class,
    ], [
        'core::mcp-server' => true,
    ]);

    Tools::register(new TestTool);
    Resources::register(new TestResource);

    $server = new McpServer(new FakeTransporter);
    $context = $server->createContext();

    $tool = $context->tools()->sole();
    $resource = $context->resources()->sole();

    expect($this->app->make(ToolsRegistry::class))->toBeInstanceOf(ToolsRegistry::class)
        ->and($this->app->make(ResourcesRegistry::class))->toBeInstanceOf(ResourcesRegistry::class)
        ->and(Tools::get('tests::server-health'))->toBeInstanceOf(TestTool::class)
        ->and(Resources::get('tests::ops-handbook'))->toBeInstanceOf(TestResource::class)
        ->and($context->serverName)->toBe(Options::get('core::mcp-server', 'name'))
        ->and($context->serverVersion)->toBe(Options::get('core::mcp-server', 'version'))
        ->and($context->instructions)->toBe(Options::get('core::mcp-server', 'instructions'))
        ->and($tool->name())->toBe('tests-server-health')
        ->and($resource->name())->toBe('tests-ops-handbook')
        ->and($resource->uri())->toBe('file://resources/tests::ops-handbook')
        ->and((string) $resource->handle()->content())->toContain('Ops Handbook');
});
