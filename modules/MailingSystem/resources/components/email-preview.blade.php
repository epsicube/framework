@php
    $rawMessage = $getRecord()->raw_message;
    $htmlContent = '';

    if ($rawMessage) {
        try {
            $parser = new \PhpMimeMailParser\Parser();
            $parser->setText($rawMessage);

            $htmlContent = $parser->getMessageBody('html') ?: $parser->getMessageBody();
            $headers = $parser->getHeaders();

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

    /* Custom Theme Toggle */
    .epsicube-mail-preview .heading-wrapper {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    .epsicube-mail-preview .label, .epsicube-mail-preview .theme-switch {
        flex: 0 0 140px;
    }

    .epsicube-mail-preview .label {
        justify-content: flex-start;
    }

    .epsicube-mail-preview .theme-switch {
        justify-content: flex-end;
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
        let rawHtml = `{!! addslashes($htmlContent) !!}`;
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

        {{--        <x-slot name="afterHeader">--}}
        {{--            <div class="theme-switch" @click="isDark = !isDark; $nextTick(() => updateIframeMode())">--}}
        {{--                <span class="switch-label" x-text="isDark ? '🌙 Dark Mode' : '☀️ Light Mode'"></span>--}}
        {{--                <div class="switch-track" :class="isDark ? 'active' : ''">--}}
        {{--                    <div class="switch-dot"></div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        </x-slot>--}}

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
    </x-filament::section>
</div>