<?php

declare(strict_types=1);

use App\Modules\TestIsModule;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use EpsicubeModules\ExecutionPlatform\Workflows\ProxiedWorkflow;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Workflow\WorkflowStub;

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
Route::get('/flow', function () {

    $workflow = WorkflowStub::make(ProxiedWorkflow::class);
    $workflowId = $workflow->id();

    dump($workflow, $workflowId);
    $workflow->start();

    while ($workflow->running()) {
        sleep(1);
    }

    dump($workflow->output(), $workflow->status(), $workflow->exceptions());

});
Route::get('/mail', function () {
    // key: 7435b85cd75831a283d25838bbcd9881
    // pkey: c94f617c94d2a6705512c73ba6c3b738
    try {
        $execution = Activities::run('epsicube-mail::send-mail', [
            'mailer_id' => 2,
            'template'  => '_blank',
            'subject'   => 'SUJET avec é accent ',
            'to'        => [
                'alan.colant@uni-deal.com',
                //            'alan.colant+2@uni-deal.com',
            ],
            'cc' => [
                //            'alan.colant@outlook.fr',
                //            'alancolant@gmail.com',
            ],
            'bcc' => [
                'alan.colant@outlook.fr',
            ],
            'template_configuration' => [
                'content' => "Contenu d'un mail pour tester la delivrabilité\navec\ndes sauts de ligne é",
            ],
        ]);
    } catch (ValidationException $exception) {
        dd($exception);
    }

    return response()->json($execution->toArray());
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
