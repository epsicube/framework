@php
    use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
    use EpsicubeModules\Administration\Enums\ApplicationGroup;use EpsicubeModules\Administration\Enums\Icons;
    use Filament\Navigation\NavigationGroup;
    use Filament\Support\Colors\Color;use Filament\Support\Contracts\HasColor;use Filament\Support\Enums\IconSize;use Filament\Support\Enums\Width;
    use Filament\Support\Icons\Heroicon;use Filament\Support\View\Components\BadgeComponent;use Illuminate\View\ComponentAttributeBag;use function Filament\Support\generate_href_html;use function Filament\Support\generate_icon_html;

    /** @var array<string, array{
     *     applicationGroup: ApplicationGroupContract,
     *     isActive: Closure(): bool,
     *     navigationGroups: NavigationGroup[]
     * }> $groupedApplications
     */

    $activeGroup = collect($groupedApplications)->firstWhere(fn(array $g)=>$g['isActive']());
@endphp

@once
    @push('styles')
        <style>
            .ec-application-logo {
                font-size: var(--text-xl);
                line-height: var(--text-xl--line-height);
                font-weight: var(--font-weight-bold);
            }

            .ec-topbar-nav-groups {
                display: grid;
                padding: calc(var(--spacing) * 2);
            }

            .ec-application-item {
                border-radius: var(--radius-xl);
                padding: calc(var(--spacing) * 2);
            }

            .ec-application-item .fi-dropdown-list-item-label {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: calc(var(--spacing) * 2);
            }

            .ec-application-item .ec-application-item-label {
                font-size: var(--text-sm);
                line-height: var(--text-sm--line-height);
                font-weight: var(--font-weight-medium);
            }
        </style>
    @endpush
@endonce

@if(!empty($groupedApplications))
    <x-filament::dropdown placement="bottom-start" :teleport="true" :width="Width::ExtraSmall">
        <x-slot name="trigger">
            <div style="display: flex; align-items: center; gap: calc(var(--spacing) * 2);">
                <x-epsicube-administration-application-group-icon
                        :application-group="$activeGroup ? $activeGroup['applicationGroup']: ApplicationGroup::EXTRAS"
                        :fallback="Icons::APPLICATIONS_SWITCH"
                        :size="IconSize::Large"
                        :active="$activeGroup&&$activeGroup['applicationGroup']->getApplicationIcon()!==null"/>
                <p class="ec-application-logo" style="margin-left: 0;">
                    {{ $activeGroup ? $activeGroup['applicationGroup']->getLabel() : ApplicationGroup::EXTRAS->getLabel() }}
                </p>
            </div>
        </x-slot>

        <ul class="ec-topbar-nav-groups"
            style=" grid-template-columns: repeat({{min(count($groupedApplications),3)}}, minmax(0, 1fr));">
            @foreach ($groupedApplications as $group)
                @php
                    $firstItem = array_first(array_first($group['navigationGroups'])->getItems());
                    $color = $group['applicationGroup'] instanceof HasColor ? $group['applicationGroup']->getColor() : null;
                    $active = $group['isActive']();
                @endphp

                <x-filament::dropdown.list.item
                        tag="a" :href="$firstItem->getUrl()"
                        :color="$color" class="ec-application-item">
                    <x-epsicube-administration-application-group-icon
                            :size="IconSize::TwoExtraLarge"
                            :fallback="Icons::APPLICATIONS_SWITCH"
                            :application-group="$group['applicationGroup']" :active="$active"/>
                    <span class="ec-application-item-label">{{ $group['applicationGroup']->getLabel() }}</span>
                </x-filament::dropdown.list.item>
            @endforeach
        </ul>
    </x-filament::dropdown>
@endif

