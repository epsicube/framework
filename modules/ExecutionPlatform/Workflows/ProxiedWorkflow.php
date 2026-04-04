<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Workflows;

use Generator;
use Illuminate\Support\Str;
use React\Promise\PromiseInterface;
use Workflow\SignalMethod;
use Workflow\Webhook;
use Workflow\Workflow;

use function Workflow\activity;
use function Workflow\await;

class ProxiedWorkflow extends Workflow
{
    protected array $signals = [];

    public function execute(): Generator
    {
        $acceptUrl = $this->webhookUrl('accept');
        $denyUrl = $this->webhookUrl('deny');

        $result = ['status' => []];
        $result['mail'] = yield $this->activity('epsicube-mail::send-mail', [
            'mailer_id'              => 1,
            'template'               => '_html',
            'subject'                => 'Demande approbation',
            'to'                     => ['alan.colant@uni-deal.com'],
            'template_configuration' => [
                'content' => '
                    Une demande est requise:<br/>
                    <a href="'.$acceptUrl.'">ACCEPTER</a>
                    <a href="'.$denyUrl.'">REFUSER</a>
                ',
            ],
        ]);

        $processedCount = 0;
        while (true) {
            yield await(fn () => count($this->signals) > $processedCount);

            $currentSignal = $this->signals[$processedCount];

            $result['status'][] = [$currentSignal, \Workflow\now()];
            $processedCount++;

            if ($currentSignal === 'accepted' || $processedCount >= 5) {
                break;
            }
        }

        return $result;
    }

    public function activity(string $identifier, array $configuration = []): PromiseInterface
    {
        return activity(ProxiedActivity::class, $identifier, $configuration);
    }

    #[SignalMethod]
    #[Webhook]
    public function accept(): void
    {
        $this->signals[] = 'accepted';
    }

    #[SignalMethod]
    #[Webhook]
    public function deny(): void
    {
        $this->signals[] = 'denied';
    }

    public function webhookUrl(string $signalMethod = ''): string
    {
        $workflow = Str::kebab(class_basename($this->storedWorkflow->class));

        if ($signalMethod === '') {
            return route("workflows.start.{$workflow}");
        }

        $signal = Str::kebab($signalMethod);

        return route("workflows.signal.{$workflow}.{$signal}", [
            'workflowId' => $this->storedWorkflow->id,
        ]);
    }
}
