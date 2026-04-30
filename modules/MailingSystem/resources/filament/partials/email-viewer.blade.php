@php
    use PhpMimeMailParser\Attachment;
    use PhpMimeMailParser\Parser;

    $eml ??= $rawMessage ?? (isset($getRecord) ? $getRecord()->raw_message : null);
    $emlStream ??= null;
    $contained ??= true;
    $viewerKey = $previewKey ?? (is_string($eml) && $eml !== '' ? 'email-viewer-'.md5($eml) : null);
    $htmlContent = '';
    $headers = [];
    $messageHeader = [];
    $attachments = [];
    $error = null;
    $iframeCsp = "default-src 'none'; base-uri 'none'; connect-src 'none'; font-src data:; form-action 'none'; frame-src 'none'; img-src data: blob:; media-src data: blob:; object-src 'none'; script-src 'none'; style-src 'unsafe-inline'; style-src-elem 'unsafe-inline';";
    $remoteIframeCsp = "default-src 'none'; base-uri 'none'; connect-src 'none'; font-src data: https: http:; form-action 'none'; frame-src 'none'; img-src data: blob: https: http:; media-src data: blob: https: http:; object-src 'none'; script-src 'none'; style-src 'unsafe-inline' https: http:; style-src-elem 'unsafe-inline' https: http:;";
    $iframeCspMeta = '<meta http-equiv="Content-Security-Policy" content="'.e($iframeCsp).'">';
    $remoteIframeCspMeta = '<meta http-equiv="Content-Security-Policy" content="'.e($remoteIframeCsp).'">';

    try {
        $parser = new Parser();

        if (is_resource($emlStream)) {
            $parser->setStream($emlStream);
        } elseif (filled($eml)) {
            $parser->setText((string) $eml);
        }

        if (is_resource($emlStream) || filled($eml)) {
            $htmlContent = $parser->getMessageBody('htmlEmbedded') ?: $parser->getMessageBody('html') ?: nl2br(e($parser->getMessageBody()));
            $headers = $parser->getHeaders();
            $messageHeader = [
                'subject' => $parser->getHeader('subject') ?: __('No subject'),
                'from' => $parser->getHeader('from'),
                'to' => $parser->getHeader('to'),
                'date' => $parser->getHeader('date'),
            ];
            $attachments = $parser->getAttachments();
        }
    } catch (Throwable $e) {
        report($e);
        $error = $e->getMessage();
        $htmlContent = '<html><body>'.e($error).'</body></html>';
    }
@endphp

@once
    <style>
        .epsicube-mail-viewer {
            --emv-border: var(--gray-200);
            --emv-panel: #ffffff;
            --emv-panel-soft: var(--gray-50);
            --emv-panel-strong: var(--gray-100);
            --emv-text: var(--gray-950);
            --emv-muted: var(--gray-500);
            --emv-muted-strong: var(--gray-700);
            --emv-primary: var(--primary-600);
            --emv-primary-soft: var(--primary-50);
            --emv-danger: var(--danger-600);
            --emv-danger-soft: var(--danger-50);
            color: var(--emv-text);
        }

        .dark .epsicube-mail-viewer {
            --emv-border: rgba(255, 255, 255, 0.1);
            --emv-panel: color-mix(in oklab, var(--gray-900) 86%, transparent);
            --emv-panel-soft: color-mix(in oklab, var(--gray-900) 70%, transparent);
            --emv-panel-strong: color-mix(in oklab, var(--gray-800) 82%, transparent);
            --emv-text: var(--gray-50);
            --emv-muted: var(--gray-400);
            --emv-muted-strong: var(--gray-300);
            --emv-primary: var(--primary-400);
            --emv-primary-soft: color-mix(in oklab, var(--primary-400) 12%, transparent);
            --emv-danger: var(--danger-400);
            --emv-danger-soft: color-mix(in oklab, var(--danger-400) 12%, transparent);
        }

        .epsicube-mail-viewer__shell {
            background: var(--emv-panel);
            border: 1px solid var(--emv-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }

        .epsicube-mail-viewer--flush .epsicube-mail-viewer__shell {
            border: 0;
            border-radius: 0;
        }

        .epsicube-mail-viewer__toolbar {
            align-items: center;
            background: var(--emv-panel-soft);
            border-bottom: 1px solid var(--emv-border);
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(160px, 1fr) auto auto;
            padding: 12px 16px;
        }

        .epsicube-mail-viewer__title {
            color: var(--emv-text);
            font-size: 15px;
            font-weight: 700;
        }

        .epsicube-mail-viewer__tabs {
            background: var(--emv-panel-strong);
            border: 1px solid var(--emv-border);
            border-radius: var(--radius-lg);
            display: flex;
            gap: 2px;
            padding: 3px;
        }

        .epsicube-mail-viewer__tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: calc(var(--radius-lg) - 2px);
            color: var(--emv-muted);
            display: inline-flex;
            font-size: 12px;
            font-weight: 600;
            justify-content: center;
            min-height: 30px;
            min-width: 76px;
            padding: 5px 10px;
        }

        .epsicube-mail-viewer__tab:hover,
        .epsicube-mail-viewer__tab.is-active {
            background: var(--emv-panel);
            color: var(--emv-text);
        }

        .epsicube-mail-viewer__theme {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--emv-muted-strong);
            display: inline-flex;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            min-height: 32px;
            padding: 0;
        }

        .epsicube-mail-viewer__remote-banner {
            align-items: center;
            background: var(--emv-primary-soft);
            border-bottom: 1px solid color-mix(in oklab, var(--emv-primary) 24%, transparent);
            color: var(--emv-primary);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 20px;
        }

        .epsicube-mail-viewer__remote-copy {
            color: var(--emv-muted-strong);
            font-size: 13px;
        }

        .epsicube-mail-viewer__remote-button {
            background: var(--emv-panel);
            border: 1px solid color-mix(in oklab, var(--emv-primary) 28%, transparent);
            border-radius: var(--radius-lg);
            color: var(--emv-primary);
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            flex: 0 0 auto;
            min-height: 32px;
            padding: 5px 12px;
        }

        .epsicube-mail-viewer__remote-button:hover {
            border-color: var(--emv-primary);
        }

        .epsicube-mail-viewer__theme-track {
            background: var(--gray-300);
            border-radius: 999px;
            height: 18px;
            position: relative;
            width: 34px;
        }

        .dark .epsicube-mail-viewer__theme-track {
            background: var(--gray-700);
        }

        .epsicube-mail-viewer__theme-track.is-active {
            background: var(--emv-primary);
        }

        .epsicube-mail-viewer__theme-dot {
            background: #ffffff;
            border-radius: 999px;
            height: 14px;
            left: 2px;
            position: absolute;
            top: 2px;
            transition: transform 0.18s ease;
            width: 14px;
        }

        .epsicube-mail-viewer__theme-track.is-active .epsicube-mail-viewer__theme-dot {
            transform: translateX(16px);
        }

        .epsicube-mail-viewer__frame-scroll {
            background: var(--emv-panel);
            overflow-x: auto;
            padding: 16px;
        }

        .epsicube-mail-viewer--flush .epsicube-mail-viewer__frame-scroll {
            padding: 0;
        }

        .epsicube-mail-viewer__message-header {
            background: var(--emv-panel);
            border-bottom: 1px solid var(--emv-border);
            padding: 18px 20px;
        }

        .epsicube-mail-viewer__message-heading {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .epsicube-mail-viewer__subject {
            color: var(--emv-text);
            font-size: 18px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
        }

        .epsicube-mail-viewer__headers-link {
            background: transparent;
            border: 0;
            color: var(--emv-primary);
            cursor: pointer;
            flex: 0 0 auto;
            font-size: 13px;
            font-weight: 700;
            padding: 2px 0;
            text-decoration: none;
        }

        .epsicube-mail-viewer__headers-link:hover {
            text-decoration: underline;
        }

        .epsicube-mail-viewer__summary {
            color: var(--emv-muted);
            display: grid;
            gap: 4px;
            font-size: 13px;
            margin-top: 10px;
        }

        .epsicube-mail-viewer__summary strong {
            color: var(--emv-muted-strong);
            font-weight: 700;
        }

        .epsicube-mail-viewer__frame {
            background: #ffffff;
            border: 1px solid var(--emv-border);
            border-radius: var(--radius-lg);
            box-sizing: content-box;
            margin: 0 auto;
            overflow: hidden;
            transition: width 0.2s ease, height 0.2s ease, background-color 0.2s ease;
        }

        .epsicube-mail-viewer--flush .epsicube-mail-viewer__frame {
            border-left: 0;
            border-radius: 0;
            border-right: 0;
        }

        .epsicube-mail-viewer__frame iframe {
            background: transparent;
            border: 0;
            display: block;
            height: 100%;
            width: 100%;
        }

        .epsicube-mail-viewer__meta {
            border-top: 1px solid var(--emv-border);
            padding: 12px 16px 16px;
        }

        .epsicube-mail-viewer--flush .epsicube-mail-viewer__meta {
            padding: 12px 0 0;
        }

        .epsicube-mail-viewer__details {
            background: var(--emv-panel-soft);
            border: 1px solid var(--emv-border);
            border-radius: var(--radius-lg);
            margin-top: 12px;
            overflow: hidden;
        }

        .epsicube-mail-viewer__details summary {
            color: var(--emv-text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 11px 14px;
        }

        .epsicube-mail-viewer__table-wrap {
            overflow-x: auto;
        }

        .epsicube-mail-viewer__table {
            border-collapse: collapse;
            width: 100%;
        }

        .epsicube-mail-viewer__table th,
        .epsicube-mail-viewer__table td {
            border-top: 1px solid var(--emv-border);
            font-size: 13px;
            padding: 10px 14px;
            text-align: left;
            vertical-align: top;
        }

        .epsicube-mail-viewer__table th {
            color: var(--emv-muted);
            font-weight: 700;
        }

        .epsicube-mail-viewer__table td {
            color: var(--emv-muted-strong);
            word-break: break-word;
        }

        .epsicube-mail-viewer__table td:first-child {
            color: var(--emv-text);
            font-weight: 600;
        }

        .epsicube-mail-viewer__download {
            color: var(--emv-primary);
            font-weight: 700;
            text-decoration: none;
        }

        .epsicube-mail-viewer__download:hover {
            text-decoration: underline;
        }

        .epsicube-mail-viewer__modal-backdrop {
            align-items: center;
            background: rgba(0, 0, 0, 0.42);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 50;
        }

        .epsicube-mail-viewer__modal {
            background: var(--emv-panel);
            border: 1px solid var(--emv-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-height: min(760px, calc(100vh - 48px));
            max-width: 920px;
            overflow: hidden;
            width: min(920px, 100%);
        }

        .epsicube-mail-viewer__modal-header {
            align-items: center;
            background: var(--emv-panel-soft);
            border-bottom: 1px solid var(--emv-border);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 14px 16px;
        }

        .epsicube-mail-viewer__modal-title {
            color: var(--emv-text);
            font-size: 15px;
            font-weight: 800;
            margin: 0;
        }

        .epsicube-mail-viewer__modal-close {
            background: transparent;
            border: 0;
            color: var(--emv-muted-strong);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 4px 0;
        }

        .epsicube-mail-viewer__modal-close:hover {
            color: var(--emv-primary);
        }

        .epsicube-mail-viewer__modal-body {
            max-height: calc(min(760px, 100vh - 48px) - 54px);
            overflow: auto;
        }

        .epsicube-mail-viewer__error {
            background: var(--emv-danger-soft);
            border: 1px solid color-mix(in oklab, var(--emv-danger) 30%, transparent);
            border-radius: var(--radius-lg);
            color: var(--emv-danger);
            font-size: 13px;
            margin: 16px;
            padding: 12px 14px;
        }

        @media (max-width: 900px) {
            .epsicube-mail-viewer__toolbar {
                grid-template-columns: 1fr;
            }

            .epsicube-mail-viewer__tabs {
                justify-content: stretch;
            }

            .epsicube-mail-viewer__tab {
                flex: 1 1 0;
                min-width: 0;
            }

            .epsicube-mail-viewer__message-header {
                padding: 16px;
            }

            .epsicube-mail-viewer__message-heading {
                display: grid;
            }

            .epsicube-mail-viewer__headers-link {
                justify-self: start;
            }

            .epsicube-mail-viewer__remote-banner {
                align-items: flex-start;
                display: grid;
                padding: 12px 16px;
            }
        }
    </style>
@endonce

<div
    @if ($viewerKey)
        wire:key="{{ $viewerKey }}"
    @endif
    x-data="{
        activeTab: 'desktop',
        isDark: false,
        loadRemoteContent: false,
        showHeaders: false,
        hasRemoteContent: false,
        blobUrl: null,
        htmlContent: @js($htmlContent),
        cspMeta: @js($iframeCspMeta),
        remoteCspMeta: @js($remoteIframeCspMeta),

        init() {
            this.isDark = document.documentElement.classList.contains('dark');
            this.initBlob();
        },

        initBlob() {
            if (this.blobUrl) {
                URL.revokeObjectURL(this.blobUrl);
                this.blobUrl = null;
            }

            const defaultStyle = (this.loadRemoteContent ? this.remoteCspMeta : this.cspMeta) + `
                <style>
                    html, body {
                        background: ${this.isDark ? '#18181b' : '#ffffff'};
                        color: ${this.isDark ? '#fafafa' : '#111827'};
                        color-scheme: ${this.isDark ? 'dark' : 'light'};
                        font-family: ui-sans-serif, system-ui, sans-serif;
                        margin: 0;
                        padding: 0;
                    }
                    body {
                        box-sizing: border-box;
                    }
                </style>`;

            const preparedHtml = this.prepareHtml(this.htmlContent);
            const finalHtml = preparedHtml.includes('<head>')
                ? preparedHtml.replace('<head>', '<head>' + defaultStyle)
                : defaultStyle + preparedHtml;

            this.blobUrl = URL.createObjectURL(new Blob([finalHtml], { type: 'text/html' }));
        },

        enableRemoteContent() {
            this.loadRemoteContent = true;
            this.initBlob();
        },

        isRemoteUrl(value) {
            if (! value) return false;

            try {
                const url = new URL(value, window.location.href);

                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (error) {
                return false;
            }
        },

        stripRemoteCss(value) {
            return String(value || '')
                .replace(/@import\s+(url\()?['\x22]?https?:\/\/[^;'\x22)]+['\x22]?\)?\s*;?/gi, '')
                .replace(/url\(\s*(['\x22]?)https?:\/\/[^)]*\1\s*\)/gi, 'none');
        },

        prepareHtml(rawHtml) {
            const parser = new DOMParser();
            const document = parser.parseFromString(rawHtml || '', 'text/html');
            let foundRemoteContent = false;

            document
                .querySelectorAll('script, iframe, frame, object, embed, applet, form, input, button, meta[http-equiv=\'refresh\']')
                .forEach((element) => element.remove());

            document.querySelectorAll('link[href]').forEach((element) => {
                if (! this.isRemoteUrl(element.getAttribute('href'))) return;

                foundRemoteContent = true;

                if (! this.loadRemoteContent) {
                    element.removeAttribute('href');
                }
            });

            document.querySelectorAll('img[src], source[src], image[href], image[xlink\\:href]').forEach((element) => {
                for (const attribute of ['src', 'href', 'xlink:href']) {
                    const value = element.getAttribute(attribute);

                    if (! this.isRemoteUrl(value)) continue;

                    foundRemoteContent = true;

                    if (! this.loadRemoteContent) {
                        element.removeAttribute(attribute);
                    }
                }
            });

            document.querySelectorAll('[srcset]').forEach((element) => {
                if (! /https?:\/\//i.test(element.getAttribute('srcset') || '')) return;

                foundRemoteContent = true;

                if (! this.loadRemoteContent) {
                    element.removeAttribute('srcset');
                }
            });

            document.querySelectorAll('[background]').forEach((element) => {
                if (! this.isRemoteUrl(element.getAttribute('background'))) return;

                foundRemoteContent = true;

                if (! this.loadRemoteContent) {
                    element.removeAttribute('background');
                }
            });

            if (! this.loadRemoteContent) {
                document.querySelectorAll('[style]').forEach((element) => {
                    const style = element.getAttribute('style') || '';

                    if (! /https?:\/\//i.test(style)) return;

                    foundRemoteContent = true;
                    element.setAttribute('style', this.stripRemoteCss(style));
                });

                document.querySelectorAll('style').forEach((element) => {
                    const css = element.textContent || '';

                    if (! /https?:\/\//i.test(css)) return;

                    foundRemoteContent = true;
                    element.textContent = this.stripRemoteCss(css);
                });
            } else {
                document.querySelectorAll('[style], style').forEach((element) => {
                    if (/https?:\/\//i.test(element.getAttribute('style') || element.textContent || '')) {
                        foundRemoteContent = true;
                    }
                });
            }

            this.hasRemoteContent = foundRemoteContent;

            return '<!doctype html>' + document.documentElement.outerHTML;
        },

        dimensions() {
            if (this.activeTab === 'mobile') return { width: '322px', height: '570px' };
            if (this.activeTab === 'tablet') return { width: '768px', height: '1024px' };

            return { width: 'calc(100% - 2px)', maxWidth: '960px', height: '760px' };
        },

        destroy() {
            if (this.blobUrl) URL.revokeObjectURL(this.blobUrl);
        },
    }"
    @class([
        'epsicube-mail-viewer',
        'epsicube-mail-viewer--flush' => ! $contained,
    ])
>
    <div class="epsicube-mail-viewer__shell">
        <div class="epsicube-mail-viewer__toolbar">
            <div class="epsicube-mail-viewer__title">{{ __('Email preview') }}</div>

            <div class="epsicube-mail-viewer__tabs" aria-label="{{ __('Preview size') }}">
                <button type="button" class="epsicube-mail-viewer__tab" x-bind:class="{ 'is-active': activeTab === 'mobile' }" x-on:click="activeTab = 'mobile'">
                    {{ __('Mobile') }}
                </button>
                <button type="button" class="epsicube-mail-viewer__tab" x-bind:class="{ 'is-active': activeTab === 'tablet' }" x-on:click="activeTab = 'tablet'">
                    {{ __('Tablet') }}
                </button>
                <button type="button" class="epsicube-mail-viewer__tab" x-bind:class="{ 'is-active': activeTab === 'desktop' }" x-on:click="activeTab = 'desktop'">
                    {{ __('Desktop') }}
                </button>
            </div>

            <button type="button" class="epsicube-mail-viewer__theme" x-on:click="isDark = ! isDark; initBlob()">
                <span x-text="isDark ? @js(__('Dark')) : @js(__('Light'))"></span>
                <span class="epsicube-mail-viewer__theme-track" x-bind:class="{ 'is-active': isDark }">
                    <span class="epsicube-mail-viewer__theme-dot"></span>
                </span>
            </button>

        </div>

        @if ($error)
            <div class="epsicube-mail-viewer__error">{{ $error }}</div>
        @endif

        @if ($messageHeader)
            <div class="epsicube-mail-viewer__message-header">
                <div class="epsicube-mail-viewer__message-heading">
                    <h2 class="epsicube-mail-viewer__subject">{{ $messageHeader['subject'] }}</h2>

                    @if (count($headers) > 0)
                        <button type="button" class="epsicube-mail-viewer__headers-link" x-on:click="showHeaders = true">
                            {{ __('View headers') }}
                        </button>
                    @endif
                </div>
                <div class="epsicube-mail-viewer__summary">
                    @if ($messageHeader['from'])
                        <div><strong>{{ __('From') }}:</strong> {{ $messageHeader['from'] }}</div>
                    @endif
                    @if ($messageHeader['to'])
                        <div><strong>{{ __('To') }}:</strong> {{ $messageHeader['to'] }}</div>
                    @endif
                    @if ($messageHeader['date'])
                        <div><strong>{{ __('Date') }}:</strong> {{ $messageHeader['date'] }}</div>
                    @endif
                </div>
            </div>
        @endif

        <div
            class="epsicube-mail-viewer__remote-banner"
            x-show="hasRemoteContent && ! loadRemoteContent"
            style="display: none;"
        >
            <div class="epsicube-mail-viewer__remote-copy">
                {{ __('Remote images and styles are blocked for your protection.') }}
            </div>
            <button type="button" class="epsicube-mail-viewer__remote-button" x-on:click="enableRemoteContent()">
                {{ __('Load remote content') }}
            </button>
        </div>

        <div class="epsicube-mail-viewer__frame-scroll">
            <div
                class="epsicube-mail-viewer__frame"
                x-bind:style="{
                    width: dimensions().width,
                    maxWidth: dimensions().maxWidth || null,
                    height: dimensions().height,
                    backgroundColor: isDark ? 'var(--gray-900)' : '#ffffff',
                }"
            >
                <iframe
                    x-ref="mailIframe"
                    x-bind:src="blobUrl"
                    allow=""
                    x-bind:csp="loadRemoteContent ? @js($remoteIframeCsp) : @js($iframeCsp)"
                    credentialless
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    sandbox
                ></iframe>
            </div>
        </div>

        @if (count($headers) > 0)
            <div
                class="epsicube-mail-viewer__modal-backdrop"
                x-show="showHeaders"
                x-transition.opacity
                x-on:click.self="showHeaders = false"
                x-on:keydown.escape.window="showHeaders = false"
                style="display: none;"
            >
                <div class="epsicube-mail-viewer__modal" role="dialog" aria-modal="true" aria-label="{{ __('Headers') }}">
                    <div class="epsicube-mail-viewer__modal-header">
                        <h3 class="epsicube-mail-viewer__modal-title">{{ __('Headers') }}</h3>
                        <button type="button" class="epsicube-mail-viewer__modal-close" x-on:click="showHeaders = false">
                            {{ __('Close') }}
                        </button>
                    </div>
                    <div class="epsicube-mail-viewer__modal-body">
                        <div class="epsicube-mail-viewer__table-wrap">
                            <table class="epsicube-mail-viewer__table">
                                <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Value') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($headers as $header => $value)
                                    <tr>
                                        <td>{{ $header }}</td>
                                        <td>
                                            @if (is_array($value))
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                            @else
                                                {{ (string) $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (count($attachments) > 0)
            <div class="epsicube-mail-viewer__meta">
                <details class="epsicube-mail-viewer__details">
                    <summary>{{ __('Attachments') }}</summary>
                    <div class="epsicube-mail-viewer__table-wrap">
                        <table class="epsicube-mail-viewer__table">
                            <thead>
                            <tr>
                                <th>{{ __('Filename') }}</th>
                                <th>{{ __('Size') }}</th>
                                <th>{{ __('Download') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($attachments as $attachment)
                                @php
                                    /** @var Attachment $attachment */
                                    $stream = $attachment->getStream();
                                    $size = null;

                                    if (is_resource($stream)) {
                                        $stats = fstat($stream);
                                        $size = $stats['size'] ?? null;
                                    }

                                    $filename = $attachment->getFilename() ?: __('Unnamed attachment');
                                    $contentType = $attachment->getContentType() ?: 'application/octet-stream';
                                    $content = $attachment->getContent();
                                    $contentBase64 = $content !== '' ? base64_encode($content) : null;
                                @endphp
                                <tr>
                                    <td>{{ $filename }}</td>
                                    <td>{{ $size !== null ? \Illuminate\Support\Number::fileSize($size, 2) : __('Unknown') }}</td>
                                    <td>
                                        @if ($contentBase64)
                                            <a
                                                class="epsicube-mail-viewer__download"
                                                download="{{ $filename }}"
                                                href="data:{{ $contentType }};base64,{{ $contentBase64 }}"
                                            >
                                                {{ __('Download') }}
                                            </a>
                                        @else
                                            {{ __('Unavailable') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        @endif
    </div>
</div>
