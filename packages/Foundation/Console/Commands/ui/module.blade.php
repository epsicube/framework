@php
    use Epsicube\Support\Concerns\Condition;use Epsicube\Support\Enums\ConditionState;
    use Epsicube\Support\Enums\ModuleStatus;use Epsicube\Support\Modules\Module;

    /**@var Module $module */
    $globalFailed = !$module->requirements->passes();
    $groupedConditions = [];
    foreach ($module->requirements->conditions as $condition) {
        $groupedConditions[$condition->group()][] = $condition;
    }

    $globalColor = $globalFailed ? 'red' : (!empty($groupedConditions) ? 'green' : 'gray');
    $globalStatus = $globalFailed ? 'FAILED' : 'PASSED';
@endphp

<div class="m-1">
    <div class="flex justify-between">
        <span>
            <span class="font-bold text-green mr-1">MODULE</span>
            <span class="text-gray italic">{{ $module->identifier }}</span>
        </span>
        <span class="font-bold text-{{  $module->status===ModuleStatus::ENABLED ? 'green' : 'red' }}">
            {{ $module->status===ModuleStatus::ENABLED  ? 'ENABLED' : 'DISABLED' }}
        </span>
    </div>
    <div class="flex text-gray">
        <span class="flex-1 content-repeat-['─']"></span>
    </div>

    <div class="mt-1">
        <div class="flex">
            <span class="text-gray font-bold w-12">Name:</span>
            <span class="text-white ml-1">{{ $module->identity->name }}</span>
        </div>
        <div class="flex">
            <span class="text-gray font-bold w-12">Author:</span>
            <span class="text-white ml-1">{{ $module->identity->author }}</span>
        </div>
        <div class="flex">
            <span class="text-gray font-bold w-12">Version:</span>
            <span class="text-white ml-1">{{ $module->version }}</span>
        </div>
    </div>

    @if(!empty($module->identity->description))
        <div class="mt-1">
            <div class="text-gray font-bold">Description:</div>
            <div class="text-white">{{ $module->identity->description }}</div>
        </div>
    @endif

    @if(count($groupedConditions) > 0)
        <div class="mt-2">
            <div class="flex justify-between">
                <span class="font-bold text-green">REQUIREMENTS</span>
                <span class="font-bold text-{{ $globalColor }}">{{ $globalStatus }}</span>
            </div>
            <div class="flex text-gray">
                <span class="flex-1 content-repeat-['─']"></span>
            </div>

            <div class="ml-1">
                @foreach($groupedConditions as $groupName => $groupConditions)
                    @php
                        $groupFailed = collect($groupConditions)->contains(fn(Condition $c) => $c->resultState === ConditionState::INVALID);
                        $gColor = $groupFailed ? 'red' : 'green';
                        $gStatus = $groupFailed ? 'FAILED' : 'PASSED';
                    @endphp

                    <div class="flex mt-1">
                        <span class="font-bold text-white">{{ $groupName }}</span>
                        <span class="flex-1 content-repeat-['.'] text-gray mx-1"></span>
                        <span class="font-bold text-{{ $gColor }}">{{ $gStatus }}</span>
                    </div>

                    @foreach($groupConditions as $condition)
                        @php
                            /**@var Condition $condition*/
                                $state = $condition->resultState;
                                $color = match($state) {
                                    ConditionState::VALID => 'green',
                                    ConditionState::INVALID => 'red',
                                    ConditionState::SKIPPED => 'gray',
                                };
                                $prefix = $loop->last ? '└──' : '├──';
                        @endphp
                        <div class="flex">
                            <span class="text-gray mr-1">{{ $prefix }}</span>
                            <span class="text-white font-bold">[{{ $condition->name() }}]</span>
                            @if($condition->getMessage())
                                <span class="text-{{ $color }} italic ml-1">{{ $condition->getMessage() }}</span>
                            @endif
                            <span class="flex-1 content-repeat-['.'] text-gray mx-1"></span>
                            <span class="font-bold text-{{ $color }}">{{ $state->name }}</span>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif
</div>
