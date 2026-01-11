@php use EpsicubeModules\Administration\View\Components\ApplicationGroupIcon;use Filament\Schemas\View\Components\IconComponent;use Illuminate\View\ComponentAttributeBag;use function Filament\Support\generate_icon_html; @endphp
@once
    @push('styles')
        <style>
            .ec-application-icon {
                display: flex;
                align-items: center;

                padding: calc(var(--spacing) * 0.5);
                border-radius: var(--radius-lg);

                border-width: 1px;
                border-style: solid;

                background-color: var(--color-50, var(--color-white));
                border-color: var(--color-200, var(--color-gray-200));
            }

            .ec-application-icon.fi-size-lg, .ec-application-icon.fi-size-xl {
                border-radius: var(--radius-xl);
                padding: calc(var(--spacing));
            }

            .ec-application-icon.fi-size-2xl {
                border-radius: var(--radius-xl);
                padding: calc(var(--spacing) * 2);
            }

            .ec-application-icon:where(.dark, .dark *) {
                background-color: transparent;
                border-color: color-mix(in oklab, var(--color-400, var(--color-gray-400)) 30%, transparent);
            }

            /* Selected variant */

            .ec-application-icon.ec-active {
                background-color: color-mix(in oklab, var(--color-600, var(--color-gray-600)) 70%, transparent);
                color: var(--color-100, var(--color-gray-100));
                border-color: transparent;
            }

            .ec-application-icon.ec-active:where(.dark, .dark *) {
                background-color: color-mix(in oklab, var(--color-400, var(--color-gray-400)) 70%, transparent);
            }
        </style>
    @endpush
@endonce

<span {{$attributes->color(IconComponent::class, $color)->class(['ec-application-icon', 'fi-size-'.$size->value, 'ec-active' => $active])}}>
    {{generate_icon_html($icon, size: $size)}}
</span>
