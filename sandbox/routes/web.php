<?php

declare(strict_types=1);

use App\Modules\TestIsModule;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
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
        $module->supports->check(),

        //        $module->supports->resolve(),
        //        $module->supports->names(),
    );

    return view('welcome');
});

Route::get('/mail', function () {
    // SENDING
    try {
        $execution = Activities::run('epsicube-mail::send-mail', [
            'mailer_id' => 1,
            'template'  => '_html',

            'subject' => 'Test des développeurs',
            'to'      => ['alan.colant@uni-deal.com'],
            //            'cc' => ['fabrice.fetsch@uni-deal.com'],
            'bcc'                    => ['contact@quix-labs.com'],
            'template_configuration' => [
                'content' => "
                    <h1>Voici le contenu du mail de test</h1>
                    <h2>Si vous le recevez par erreur, merci de l'ignorer</h2>
                ",
            ],

        ]);
    } catch (Exception $e) {
        dd($e);
    }

    return response()->json($execution->toArray());
});
