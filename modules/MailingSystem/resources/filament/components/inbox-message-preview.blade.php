@php
    $key = $getKey();
    $accountId = $getState();
    $accountId = is_scalar($accountId) ? $accountId : null;
    $state = $getMailboxState();
@endphp

<div
    class="mailing-inbox"
    wire:key="mailing-inbox-{{ $key }}-{{ $accountId ?? 'none' }}"
    x-data
>
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
            background: var(--mail-panel);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-xl);
            color: var(--mail-text);
            display: grid;
            grid-template-columns: minmax(320px, 380px) minmax(0, 1fr);
            min-height: 620px;
            min-width: 0;
            overflow: hidden;
            width: 100%;
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

        .mailing-inbox__sidebar,
        .mailing-inbox__preview {
            min-width: 0;
        }

        .mailing-inbox__sidebar {
            border-right: 1px solid var(--mail-border);
            display: flex;
            flex-direction: column;
            min-height: 0;
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

        .mailing-inbox__button[disabled] {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .mailing-inbox__messages {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
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

        .mailing-inbox__empty,
        .mailing-inbox__select-placeholder,
        .mailing-inbox__loading,
        .mailing-inbox__preview-loading {
            color: var(--mail-muted);
            font-size: 14px;
            padding: 24px;
            text-align: center;
        }

        .mailing-inbox__select-placeholder,
        .mailing-inbox__preview-loading {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 620px;
            width: 100%;
        }

        .mailing-inbox__loading,
        .mailing-inbox__preview-loading {
            gap: 10px;
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

        .mailing-inbox__callout {
            background: var(--mail-panel);
            border: 1px solid var(--mail-border);
            border-radius: var(--radius-xl);
            color: var(--mail-muted);
            grid-column: 1 / -1;
            margin: 18px;
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

        .mailing-inbox__preview {
            background: var(--mail-panel);
            min-height: 620px;
        }

        .mailing-inbox__email-preview {
            background: var(--mail-panel);
            min-width: 0;
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
            .mailing-inbox {
                grid-template-columns: 1fr;
            }

            .mailing-inbox__sidebar {
                border-bottom: 1px solid var(--mail-border);
                border-right: 0;
                max-height: 440px;
            }

            .mailing-inbox__select-placeholder,
            .mailing-inbox__preview-loading {
                min-height: 280px;
            }
        }
    </style>

    @if (! $state['has_account'])
        <div class="mailing-inbox__callout">
            <div class="mailing-inbox__callout-title">{{ __('No active inbox account configured') }}</div>
            {{ __('Create or activate an IMAP account before opening the inbox.') }}
        </div>
    @elseif ($state['error'])
        <div class="mailing-inbox__callout mailing-inbox__callout--danger">
            <div class="mailing-inbox__callout-title">{{ __('Unable to load inbox') }}</div>
            {{ $state['error'] }}
        </div>
    @else
        <div class="mailing-inbox__sidebar">
            <div class="mailing-inbox__messages">
                @forelse ($state['messages'] as $message)
                    <button
                        type="button"
                        wire:key="inbox-message-{{ $state['pagination']['current_page'] }}-{{ $message['uid'] }}"
                        x-on:click="$wire.callSchemaComponentMethod(@js($key), 'selectMessage', { uid: {{ $message['uid'] }}, page: {{ $state['pagination']['current_page'] }} })"
                        @class([
                            'mailing-inbox__message-button',
                            'mailing-inbox__message-button--selected' => $state['message_uid'] === $message['uid'],
                            'mailing-inbox__message-button--unread' => ! $message['seen'],
                        ])
                    >
                        <div class="mailing-inbox__message-top">
                            <span class="mailing-inbox__from">{{ $message['from'] }}</span>
                            @if ($message['date'])
                                <span class="mailing-inbox__date">{{ $message['date'] }}</span>
                            @endif
                        </div>

                        <div class="mailing-inbox__subject">{{ $message['subject'] }}</div>
                    </button>
                @empty
                    <div class="mailing-inbox__empty">{{ __('No messages found.') }}</div>
                @endforelse
            </div>

            <div class="mailing-inbox__pagination">
                <div class="mailing-inbox__pagination-meta">
                    @if ($state['pagination']['total'] > 0)
                        {{ $state['pagination']['from'] }}-{{ $state['pagination']['to'] }} / {{ $state['pagination']['total'] }}
                    @else
                        {{ __('No messages') }}
                    @endif
                </div>

                <div class="mailing-inbox__pagination-actions">
                    <button
                        type="button"
                        x-on:click="$wire.callSchemaComponentMethod(@js($key), 'previousPage', { page: {{ $state['pagination']['current_page'] }} })"
                        class="mailing-inbox__button"
                        @disabled(! $state['pagination']['has_previous'])
                    >
                        {{ __('Previous') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="$wire.callSchemaComponentMethod(@js($key), 'nextPage', { page: {{ $state['pagination']['current_page'] }} })"
                        class="mailing-inbox__button"
                        @disabled(! $state['pagination']['has_more'])
                    >
                        {{ __('Next') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="mailing-inbox__preview">
            <div
                class="mailing-inbox__preview-loading"
                wire:loading.flex
                wire:target="callSchemaComponentMethod"
            >
                <div class="mailing-inbox__loader"></div>
                <span>{{ __('Loading message...') }}</span>
            </div>

            <div wire:loading.remove wire:target="callSchemaComponentMethod">
                @if ($state['message_error'])
                    <div class="mailing-inbox__callout mailing-inbox__callout--danger">
                        <div class="mailing-inbox__callout-title">{{ __('Unable to load message') }}</div>
                        {{ $state['message_error'] }}
                    </div>
                @elseif ($state['raw_message'])
                    <div class="mailing-inbox__email-preview">
                        {{ $getChildSchema('preview') }}
                    </div>
                @else
                    <div class="mailing-inbox__select-placeholder">{{ __('Select a message.') }}</div>
                @endif
            </div>
        </div>
    @endif
</div>
