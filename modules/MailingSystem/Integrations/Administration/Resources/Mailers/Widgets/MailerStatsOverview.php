<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Widgets;

use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Models\Mailer;
use EpsicubeModules\MailingSystem\Models\Message;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MailerStatsOverview extends StatsOverviewWidget
{
    public Mailer $mailer;

    protected function getStats(): array
    {
        // 1. Nombre total d'envois (Campagnes / Outboxes)
        $outboxCount = $this->mailer->outboxes()->count();

        // 2. Statistiques par destinataire (Messages) en une seule requête
        $messageStats = Message::query()
            ->whereRelation('outbox', 'mailer_id', $this->mailer->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        $totalRecipients = $messageStats->sum();
        $delivered = $messageStats->get(MessageStatus::DELIVERED->value, 0);
        $failed = $messageStats->get(MessageStatus::BOUNCED->value, 0) + $messageStats->get(MessageStatus::DROPPED->value, 0);

        return [
            // Carte "Mails" (Entité Outbox)
            Stat::make(__('Campaigns Sent'), $outboxCount)
                ->description(__('Total mail batches processed'))
                ->icon('heroicon-m-paper-airplane'),

            // Carte "Destinataires" (Entité Message)
            Stat::make(__('Total Recipients'), $totalRecipients)
                ->description(__('Individual messages dispatched'))
                ->icon('heroicon-m-users'),

            // Carte "Délivrabilité" (Le ratio réel sur les messages)
            Stat::make(__('Delivery Success'), $delivered)
                ->description($totalRecipients > 0
                    ? number_format(($delivered / $totalRecipients) * 100, 1).'% '.__('of recipients reached')
                    : '0%'
                )
                ->color('success')
                ->icon('heroicon-m-check-badge'),

            // Carte "Échecs" (Regroupement Bounces/Dropped)
            Stat::make(__('Delivery Failures'), $failed)
                ->description(__('Bounces or dropped recipients'))
                ->color($failed > 0 ? 'danger' : 'gray')
                ->icon('heroicon-m-x-circle'),
        ];
    }
}
