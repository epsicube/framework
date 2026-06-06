<?php

declare(strict_types=1);

use Epsicube\Tests\Modules\JsonRpcServer\Fixtures\RegisterProcedureModule;
use Epsicube\Tests\Modules\JsonRpcServer\Fixtures\TestProcedure;
use EpsicubeModules\JsonRpcServer\Facades\Procedures;
use EpsicubeModules\JsonRpcServer\JsonRpcServerModule;
use EpsicubeModules\JsonRpcServer\Registries\ProceduresRegistry;

test('json rpc server module declares expected metadata and endpoint option schema', function () {
    $this->configureModules([
        JsonRpcServerModule::class,
    ]);

    $module = $this->module('core::json-rpc-server');
    $endpoint = $module->options->property('endpoint');

    expect($module->identifier)->toBe('core::json-rpc-server')
        ->and($module->identity->name)->toBe('JSON-RPC Server')
        ->and(array_keys($module->options->properties()))->toBe(['endpoint'])
        ->and($endpoint)->not->toBeNull()
        ->and($endpoint?->isOptional())->toBeTrue()
        ->and($endpoint?->getDefault())->toBe('/rpc/v1/endpoint');
});

test('json rpc server registers its procedures registry and facade when enabled', function () {
    $this->configureModules([
        JsonRpcServerModule::class,
        RegisterProcedureModule::class,
    ], [
        'core::json-rpc-server'              => true,
        'tests::json-rpc-procedure-provider' => true,
    ]);

    $registry = $this->app->make(ProceduresRegistry::class);

    expect($this->app->make('jsonrpc-procedures'))->toBe($registry)
        ->and(Procedures::all())->toHaveKey('tests.ping')
        ->and(Procedures::get('tests.ping'))->toBeInstanceOf(TestProcedure::class);
});

test('json rpc server declares the rpc route with the configured default endpoint when enabled', function () {
    $this->configureModules([
        JsonRpcServerModule::class,
        RegisterProcedureModule::class,
    ], [
        'core::json-rpc-server'              => true,
        'tests::json-rpc-procedure-provider' => true,
    ]);

    $route = $this->app->make('router')->getRoutes()->getByName('rpc.endpoint');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe('rpc/v1/endpoint')
        ->and($route?->methods())->toContain('POST')
        ->and($route?->defaults['procedures'] ?? null)->toBe([
            TestProcedure::class,
        ]);
});

test('json rpc server does not register registry bindings or routes while disabled', function () {
    $this->configureModules([
        JsonRpcServerModule::class,
    ]);

    expect($this->app->make('router')->getRoutes()->getByName('rpc.endpoint'))->toBeNull();
});
