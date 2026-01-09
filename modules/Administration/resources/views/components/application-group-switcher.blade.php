@php
    use EpsicubeModules\Administration\Contracts\ApplicationGroup;
    use EpsicubeModules\Administration\Enums\Icons;
    use Filament\Navigation\NavigationGroup;
    use Filament\Support\Enums\Width;
    use Filament\Support\Icons\Heroicon;

    /** @var array<int,NavigationGroup> $applicationGroups */
    $activeGroup = collect($applicationGroups)
        ->firstWhere(fn(NavigationGroup $g)=>$g->isActive())
        ?->getItems()->first()?->getGroup();
@endphp

@if(!empty($applicationGroups))
    <x-filament::dropdown style="margin-left: calc(var(--spacing) * 4);" placement="bottom-start">
        <x-slot name="trigger">
            <div style="display: flex; align-items: center; gap: calc(var(--spacing) * 2);">
                <x-filament::icon-button :icon="Icons::APPLICATION" color="neutral"/>
                <p style="font-weight: var(--font-weight-bold); font-size: var(--text-lg);">
                    {{$activeGroup ? $activeGroup->getApplicationLabel(): filament()->getCurrentPanel()->getBrandName()}}
                </p>
            </div>

        </x-slot>

        <div style="display: grid; gap: 0.25em; grid-template-columns: repeat({{min(count($applicationGroups),3)}}, minmax(0, 1fr)); padding: 0.25em;">
            @foreach ($applicationGroups as $group)
                @php($item = $group->getItems()->first())
                <div @class(['fi-topbar-item', 'fi-active' => $group->isActive()])>
                    <a href="{{ $item->getUrl() }}" class="fi-topbar-item-btn"
                       style="display: flex; flex-direction: column; gap: var(--spacing)">
                        <x-filament::icon :icon="$item->getGroup()->getApplicationIcon()"
                                          :size="\Filament\Support\Enums\IconSize::TwoExtraLarge"/>
                        <span style="font-size: var(--text-xs);">{{ $item->getGroup()->getApplicationLabel() }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    </x-filament::dropdown>
@endif

