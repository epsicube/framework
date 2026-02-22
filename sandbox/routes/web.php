<?php

declare(strict_types=1);

use App\Modules\TestIsModule;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $t = new TestIsModule(app());
    $module = $t->module();

    $versions = array_map(function (Module $module) {
        return $module->status === ModuleStatus::ENABLED ? $module->version : false;
    }, Modules::all());
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
