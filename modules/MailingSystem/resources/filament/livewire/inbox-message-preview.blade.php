<div class="mailing-inbox__preview-panel">
    @if ($selected['error'] ?? null)
        <div class="mailing-inbox__callout mailing-inbox__callout--danger">
            <div class="mailing-inbox__callout-title">{{ __('Unable to load message') }}</div>
            {{ $selected['error'] }}
        </div>
    @else
        <div class="mailing-inbox__email-preview">
            @if ($selected['raw_message'])
                @include('epsicube-mail::filament.partials.email-viewer', [
                    'eml' => $selected['raw_message'],
                    'contained' => false,
                    'previewKey' => 'inbox-email-preview-' . $this->messageUid,
                ])
            @else
                <div class="mailing-inbox__callout">{{ $selected['body'] }}</div>
            @endif
        </div>
    @endif
</div>
