<x-filament-panels::page>
    @php
        $accounts = $this->accounts();
        $state = $this->mailboxState();
        $messages = $state['messages'];
        $pagination = $state['pagination'];
    @endphp

    <style>
        .mailing-inbox {
            --mail-border: var(--gray-200);
            --mail-panel: #ffffff;
            --mail-panel-soft: var(--gray-50);
            --mail-text: var(--gray-950);
            --mail-muted: var(--gray-500);
            --mail-muted-strong: var(--gray-700);
            --mail-primary: var(--primary-600);
            --mail-primary-soft: var(--primary-50);
            --mail-danger: var(--danger-600);
            --mail-danger-soft: var(--danger-50);
            color: var(--mail-text);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .dark .mailing-inbox {
            --mail-border: rgba(255, 255, 255, 0.1);
            --mail-panel: color-mix(in oklab, var(--gray-900) 86%, transparent);
            --mail-panel-soft: color-mix(in oklab, var(--gray-900) 70%, transparent);
            --mail-text: var(--gray-50);
            --mail-muted: var(--gray-400);
            --mail-muted-strong: var(--gray-300);
            --mail-primary: var(--primary-400);
            --mail-primary-soft: color-mix(in oklab, var(--primary-400) 10%, transparent);
            --mail-danger: var(--danger-400);
            --mail-danger-soft: color-mix(in oklab, var(--danger-400) 10%, transparent);
        }

        .mailing-inbox__toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 320px) minmax(260px, 1fr);
            gap: 12px;
        }

        .mailing-inbox__search-row {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .mailing-inbox__button {
            background: var(--mail-panel-soft);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-lg);
            color: var(--mail-muted-strong);
            font-size: 14px;
            min-height: 38px;
            padding: 7px 12px;
        }

        .mailing-inbox__button:hover {
            background: var(--mail-primary-soft);
            color: var(--mail-primary);
        }

        .mailing-inbox__field {
            background: var(--mail-panel);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-xl);
            padding: 12px;
        }

        .mailing-inbox__label {
            color: var(--mail-muted);
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0;
            margin-bottom: 6px;
        }

        .mailing-inbox__control {
            background: var(--mail-panel-soft);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-lg);
            color: var(--mail-text);
            display: block;
            font-size: 14px;
            min-height: 38px;
            outline: none;
            padding: 7px 10px;
            width: 100%;
        }

        .mailing-inbox__control:focus {
            border-color: var(--mail-primary);
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--mail-primary) 20%, transparent);
        }

        .mailing-inbox__callout {
            background: var(--mail-panel);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-xl);
            color: var(--mail-muted);
            padding: 18px;
        }

        .mailing-inbox__callout-title {
            color: var(--mail-text);
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .mailing-inbox__callout--danger {
            background: var(--mail-danger-soft);
            border-color: color-mix(in oklab, var(--mail-danger) 30%, transparent);
            color: var(--mail-danger);
        }

        .mailing-inbox__layout {
            background: var(--mail-panel);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-xl);
            display: grid;
            grid-template-columns: minmax(280px, 380px) minmax(0, 1fr);
            min-height: 620px;
            overflow: hidden;
        }

        .mailing-inbox__list {
            border-right: 1px solid var(--mail-border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .mailing-inbox__messages {
            flex: 1 1 auto;
            min-height: 0;
        }

        .mailing-inbox__message-button {
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--mail-border);
            color: var(--mail-text);
            cursor: pointer;
            display: block;
            padding: 14px 16px;
            text-align: left;
            width: 100%;
        }

        .mailing-inbox__message-button:hover,
        .mailing-inbox__message-button--selected {
            background: var(--mail-primary-soft);
        }

        .mailing-inbox__message-top {
            align-items: baseline;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .mailing-inbox__from,
        .mailing-inbox__subject {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mailing-inbox__from {
            font-size: 14px;
            font-weight: 700;
        }

        .mailing-inbox__date {
            color: var(--mail-muted);
            flex: 0 0 auto;
            font-size: 12px;
        }

        .mailing-inbox__subject {
            color: var(--mail-muted);
            font-size: 13px;
            margin-top: 5px;
        }

        .mailing-inbox__message-button--unread .mailing-inbox__from,
        .mailing-inbox__message-button--unread .mailing-inbox__subject {
            color: var(--mail-text);
            font-weight: 800;
        }

        .mailing-inbox__empty {
            color: var(--mail-muted);
            font-size: 14px;
            padding: 24px;
        }

        .mailing-inbox__pagination {
            align-items: center;
            border-top: 1px solid var(--mail-border);
            display: flex;
            flex: 0 0 auto;
            gap: 8px;
            justify-content: space-between;
            padding: 12px;
        }

        .mailing-inbox__pagination-meta {
            color: var(--mail-muted);
            font-size: 12px;
        }

        .mailing-inbox__pagination-actions {
            display: flex;
            gap: 8px;
        }

        .mailing-inbox__button[disabled] {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .mailing-inbox__button[disabled]:hover {
            background: var(--mail-panel-soft);
            color: var(--mail-muted-strong);
        }

        .mailing-inbox__preview {
            background: var(--mail-panel);
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .mailing-inbox__preview-panel {
            background: var(--mail-panel);
            min-width: 0;
        }

        .mailing-inbox__select-placeholder {
            align-items: center;
            color: var(--mail-muted);
            display: flex;
            font-size: 14px;
            justify-content: center;
            min-height: 620px;
            padding: 24px;
            text-align: center;
        }

        .mailing-inbox__preview-loading {
            align-items: center;
            color: var(--mail-muted);
            display: flex;
            gap: 10px;
            justify-content: center;
            min-height: 620px;
            padding: 24px;
        }

        .mailing-inbox__loader {
            animation: mailing-inbox-spin 1s linear infinite;
            border: 2px solid var(--mail-border);
            border-radius: 999px;
            border-top-color: var(--mail-primary);
            height: 18px;
            width: 18px;
        }

        @keyframes mailing-inbox-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .mailing-inbox__email-preview {
            background: var(--mail-panel);
            padding: 0;
        }

        .mailing-inbox__email-preview .epsicube-mail-viewer {
            --emv-border: var(--mail-border);
            --emv-panel: var(--mail-panel);
            --emv-panel-soft: var(--mail-panel-soft);
            --emv-text: var(--mail-text);
            --emv-muted: var(--mail-muted);
            --emv-muted-strong: var(--mail-muted-strong);
            --emv-primary: var(--mail-primary);
            --emv-primary-soft: var(--mail-primary-soft);
        }

        @media (max-width: 900px) {
            .mailing-inbox__toolbar,
            .mailing-inbox__layout {
                grid-template-columns: 1fr;
            }

            .mailing-inbox__list {
                border-bottom: 1px solid var(--mail-border);
                border-right: 0;
                max-height: 360px;
            }

            .mailing-inbox__select-placeholder {
                min-height: 280px;
            }
        }
    </style>

    <div class="mailing-inbox">
        <div class="mailing-inbox__toolbar">
            <div class="mailing-inbox__field">
                <label class="mailing-inbox__label" for="inbox-account">{{ __('Account') }}</label>
                <select id="inbox-account" wire:model.live="accountId" class="mailing-inbox__control">
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mailing-inbox__field">
                <label class="mailing-inbox__label" for="inbox-search">{{ __('Search subject or sender') }}</label>
                <div class="mailing-inbox__search-row">
                    <input id="inbox-search" type="search" wire:model.live.debounce.500ms="search" class="mailing-inbox__control" />
                    <button type="button" wire:click="clearSearch" class="mailing-inbox__button">
                        {{ __('Reset') }}
                    </button>
                </div>
            </div>
        </div>

        @if ($state['error'])
            <div class="mailing-inbox__callout mailing-inbox__callout--danger">
                <div class="mailing-inbox__callout-title">{{ __('Unable to load inbox') }}</div>
                {{ $state['error'] }}
            </div>
        @elseif ($accounts->isEmpty())
            <div class="mailing-inbox__callout">
                <div class="mailing-inbox__callout-title">{{ __('No active inbox account configured') }}</div>
                {{ __('Create or activate an IMAP account before opening the inbox.') }}
            </div>
        @else
            <div class="mailing-inbox__layout">
                <div class="mailing-inbox__list">
                    <div class="mailing-inbox__messages">
                        @forelse ($messages as $summary)
                            @if ($summary['uid'])
                                <button
                                    type="button"
                                    wire:key="inbox-message-{{ $pagination['current_page'] }}-{{ $summary['uid'] }}"
                                    wire:click="selectMessage({{ $summary['uid'] }})"
                                    @class([
                                        'mailing-inbox__message-button',
                                        'mailing-inbox__message-button--selected' => $messageUid === $summary['uid'],
                                        'mailing-inbox__message-button--unread' => ! $summary['seen'],
                                    ])
                                >
                                    <div class="mailing-inbox__message-top">
                                        <span class="mailing-inbox__from">{{ $summary['from'] }}</span>
                                        @if ($summary['date'])
                                            <span class="mailing-inbox__date">{{ $summary['date'] }}</span>
                                        @endif
                                    </div>

                                    <div class="mailing-inbox__subject">{{ $summary['subject'] }}</div>
                                </button>
                            @endif
                        @empty
                            <div class="mailing-inbox__empty">{{ __('No messages found.') }}</div>
                        @endforelse
                    </div>

                    <div class="mailing-inbox__pagination">
                        <div class="mailing-inbox__pagination-meta">
                            @if ($pagination['total'] > 0)
                                {{ $pagination['from'] }}-{{ $pagination['to'] }} / {{ $pagination['total'] }}
                            @else
                                {{ __('No messages') }}
                            @endif
                        </div>

                        <div class="mailing-inbox__pagination-actions">
                            <button
                                type="button"
                                wire:click="previousPage"
                                class="mailing-inbox__button"
                                @disabled(! $pagination['has_previous'])
                            >
                                {{ __('Previous') }}
                            </button>

                            <button
                                type="button"
                                wire:click="nextPage"
                                class="mailing-inbox__button"
                                @disabled(! $pagination['has_more'])
                            >
                                {{ __('Next') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mailing-inbox__preview" wire:key="inbox-preview-{{ $messageUid ?? 'empty' }}">
                    @if ($messageUid && $accountId)
                        @livewire(
                            \EpsicubeModules\MailingSystem\Integrations\Administration\Livewire\InboxMessagePreview::class,
                            [
                                'accountId' => $accountId,
                                'messageUid' => $messageUid,
                            ],
                            key("inbox-preview-{$accountId}-{$messageUid}")
                        )
                    @else
                        <div class="mailing-inbox__select-placeholder">{{ __('Select a message.') }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
