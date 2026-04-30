@php
    use PhpMimeMailParser\Attachment;
    use PhpMimeMailParser\Parser;

    $rawMessage = $getRecord()->raw_message;
    $htmlContent = '';
    $headers = [];
    $attachments = [];

    if ($rawMessage) {
        try {
            $parser = new Parser();
            $parser->setText($rawMessage);

            $htmlContent = $parser->getMessageBody('htmlEmbedded') ?: $parser->getMessageBody();
            $headers = $parser->getHeaders();
            $attachments = $parser->getAttachments();

        } catch (\Exception $e) {
            $htmlContent = "<html><body>Error: " . e($e->getMessage()) . "</body></html>";
        }
    }
@endphp

<style>
    /* Email Frame Wrapper */
    .epsicube-mail-preview .email-wrapper {
        border-radius: var(--radius-xl);
        overflow: hidden;
        border: 1px solid var(--gray-100);
        margin: 0 auto;
        box-shadow: var(--shadow-xl);
        max-width: 1920px; /* Safety for desktop */
        box-sizing: content-box;
        padding: 20px;
        transition: width 0.3s ease-in-out, height 0.3s ease-in-out;
    }

    .dark .epsicube-mail-preview .email-wrapper {
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .epsicube-mail-preview iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
        background: transparent;
    }

    .epsicube-mail-preview .email-wrapper-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 0.25rem;
    }

    /* Custom Theme Toggle */
    .epsicube-mail-preview .heading-wrapper {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    .epsicube-mail-preview .device-tabs {
        flex: 1;
        justify-content: center;
    }

    .epsicube-mail-preview .theme-switch {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        user-select: none;
        padding: 0.5rem 0;
    }

    .epsicube-mail-preview .switch-track {
        width: 2.4rem;
        height: 1.2rem;
        background-color: var(--gray-300);
        border-radius: 1rem;
        position: relative;
        transition: 0.3s;
    }

    .dark .epsicube-mail-preview .switch-track {
        background-color: var(--gray-700);
    }

    .epsicube-mail-preview .switch-track.active {
        background-color: var(--primary-600);
    }

    .epsicube-mail-preview .switch-dot {
        width: 0.9rem;
        height: 0.9rem;
        background-color: white;
        border-radius: 50%;
        position: absolute;
        top: 0.15rem;
        left: 0.15rem;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .epsicube-mail-preview .switch-track.active .switch-dot {
        transform: translateX(1.2rem);
    }

    .epsicube-mail-preview .switch-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: var(--gray-500);
    }

    .epsicube-mail-preview .device-tabs {
        border: none;
        padding: 0;
    }

    .epsicube-mail-preview .meta-section {
        margin-top: 1rem;
        overflow-x: auto;
    }

    .epsicube-mail-preview .meta-block {
        margin-top: 1rem;
    }

    .epsicube-mail-preview .meta-table {
        width: 100%;
        border-collapse: collapse;
    }

    .epsicube-mail-preview .meta-table th,
    .epsicube-mail-preview .meta-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--gray-200);
        vertical-align: top;
    }

    .dark .epsicube-mail-preview .meta-table th,
    .dark .epsicube-mail-preview .meta-table td {
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }

    .epsicube-mail-preview .meta-table th:last-child,
    .epsicube-mail-preview .meta-table td:last-child {
        text-align: right;
        white-space: nowrap;
    }

    .epsicube-mail-preview .meta-table thead th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: var(--gray-500);
    }

    .epsicube-mail-preview .meta-table tbody tr:last-child td {
        border-bottom: none;
    }

    .epsicube-mail-preview .meta-table td:first-child {
        font-weight: 500;
        color: var(--gray-950);
    }

    .dark .epsicube-mail-preview .meta-table td:first-child {
        color: rgba(255, 255, 255, 0.92);
    }

    .epsicube-mail-preview .meta-table td.meta-value {
        text-align: left;
        white-space: normal;
        word-break: break-word;
        color: var(--gray-600);
    }

    .dark .epsicube-mail-preview .meta-table td.meta-value {
        color: rgba(255, 255, 255, 0.72);
    }

</style>

<div x-data="{
    activeTab: 'desktop',
    isDark: false,
    blobUrl: null,

    init() {
        this.isDark = document.documentElement.classList.contains('dark');
        this.initBlob();
    },

    initBlob() {
        let rawHtml = `{!! htmlspecialchars($htmlContent,ENT_QUOTES) !!}`;
        const defaultStyle = `
            <style>
                html, body {
                    font-family: sans-serif;
                    margin: 0;
                    padding: 0;
                }
            </style>`;
        const finalHtml = rawHtml.includes('<head>')
            ? rawHtml.replace('<head>', '<head>' + defaultStyle)
            : defaultStyle + rawHtml;
        const blob = new Blob([finalHtml], { type: 'text/html' });
        this.blobUrl = URL.createObjectURL(blob);
    },

    updateIframeMode() {
        const iframe = this.$refs.mailIframe;
        if (!iframe || !iframe.contentDocument) return;
        iframe.contentDocument.documentElement.style.colorScheme = this.isDark ? 'dark' : 'light';
    },

    get dimensions() {
        if (this.activeTab === 'mobile') return { w: '322px', h: '570px' };
        if (this.activeTab === 'tablet') return { w: '768px', h: '1024px' };
        return { w: 'calc(100% - 40px)', h: '800px' }; // Height is 800px for desktop to maintain view
    }
}" class="epsicube-mail-preview">

    <x-filament::section compact>
        <x-slot name="heading">
            <div class="heading-wrapper">
                <p class="label">{{__('Email preview')}}</p>
                {{-- DEVICE SWITCH --}}
                <x-filament::tabs :contained="true" class="device-tabs">
                    <x-filament::tabs.item
                            alpine-active="activeTab === 'mobile'" x-on:click="activeTab = 'mobile'"
                            icon="heroicon-m-device-phone-mobile"
                    />

                    <x-filament::tabs.item
                            alpine-active="activeTab === 'tablet'" x-on:click="activeTab = 'tablet'"
                            icon="heroicon-m-device-tablet"
                    />

                    <x-filament::tabs.item
                            alpine-active="activeTab === 'desktop'" x-on:click="activeTab = 'desktop'"
                            icon="heroicon-m-computer-desktop"/>
                </x-filament::tabs>

                {{--  THEME SWITCH --}}

                <div class="theme-switch" @click="isDark = !isDark; $nextTick(() => updateIframeMode())">
                    <span class="switch-label" x-text="isDark ? '🌙 Dark Mode' : '☀️ Light Mode'"></span>
                    <div class="switch-track" :class="isDark ? 'active' : ''">
                        <div class="switch-dot"></div>
                    </div>
                </div>
            </div>
        </x-slot>

        <div class="email-wrapper-scroll">
            <div class="email-wrapper"
                 :style="{
                    width: dimensions.w,
                    height: dimensions.h,
                    backgroundColor: isDark ? '#18181b' : '#ffffff'
                 }">
                <iframe
                        x-ref="mailIframe"
                        :src="blobUrl"
                        sandbox="allow-popups allow-popups-to-escape-sandbox allow-scripts allow-same-origin"
                        @load="updateIframeMode()"
                ></iframe>
            </div>
        </div>

        @if(count($headers) > 0)
            <x-filament::section
                    class="meta-block"
                    compact
                    collapsible
                    collapsed
                    heading="{{ __('Headers') }}"
            >
                <div class="meta-section">
                    <table class="meta-table headers-table">
                        <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Value') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($headers as $header => $value)
                            <tr>
                                <td>{{ $header }}</td>
                                <td class="meta-value">
                                    @if(is_array($value))
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
            </x-filament::section>
        @endif

        @if(count($attachments) > 0)
            <x-filament::section
                    class="meta-block"
                    compact
                    collapsible
                    collapsed
                    heading="{{ __('Attachments') }}"
            >
                <div class="meta-section">
                    <table class="meta-table attachments-table">
                        <thead>
                        <tr>
                            <th>{{ __('Filename') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Download') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($attachments as $attachment)
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
                                <td>
                                    {{ $size !== null ? \Illuminate\Support\Number::fileSize($size, 2) : __('Unknown') }}
                                </td>
                                <td>
                                    @if($contentBase64)
                                        <x-filament::link
                                                icon="heroicon-m-arrow-down-tray"
                                                download="{{ $filename }}"
                                                href="data:{{ $contentType }};base64,{{ $contentBase64 }}"
                                        >
                                            {{ __('Download') }}
                                        </x-filament::link>
                                    @else
                                        <span>{{ __('Unavailable') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </x-filament::section>
</div>
