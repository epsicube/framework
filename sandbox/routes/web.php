<?php

declare(strict_types=1);

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $t = new \App\Modules\TestIsModule(app());
    $module = $t->module();

    $versions = array_map(function (\Epsicube\Support\Modules\Module $module) {
        return $module->status === \Epsicube\Support\Enums\ModuleStatus::ENABLED ? $module->version : false;
    }, \Epsicube\Support\Facades\Modules::all());
    dd(
        AdministrationIntegration::handle(...),
        //        $module->identity,
        //        $module->requirements,
        //        $module->supports,
        //        $module->options,
        $module->requirements->check(),
        $module->dependencies->check($versions),
        $module->supports->check(),

        //        $module->supports->resolve(),
        //        $module->supports->names(),
    );

    return view('welcome');
});
