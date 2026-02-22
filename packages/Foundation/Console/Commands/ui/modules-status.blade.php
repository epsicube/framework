@php
    use Epsicube\Support\Enums\ConditionState;use Epsicube\Support\Enums\ModuleStatus;
    /** @var \Epsicube\Support\Modules\Module[] $modules */
@endphp

<div class="m-1 mb-2">
    <table style="box">
        <thead>
        <tr border="1">
            <th class="font-bold text-cyan">Identifier</th>
            <th class="font-bold text-yellow">Name</th>
            <th class="font-bold text-white w-50">Description</th>
            <th class="font-bold text-magenta">Author</th>
            <th class="font-bold text-green">Version</th>
            <th class="font-bold text-white">Status</th>
            <th class="font-bold text-white text-right">Requirements</th>
            <th class="font-bold text-white text-right">Dependencies</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($modules as $module)
            @php
                $statusColor = 'red';
                $statusText  = 'DISABLED';
                if ($module->mustUse) {
                    $statusColor = 'yellow';
                    $statusText = 'MUST-USE';
                } elseif ($module->status===ModuleStatus::ENABLED) {
                    $statusColor = 'green';
                    $statusText = 'ENABLED';
                }

                $total = $passed = $failed=0;
                foreach ($module->requirements->conditions as $condition) {
                    $state = $condition->run();

                    if ($state !== ConditionState::SKIPPED) {
                        $total++;
                        if ($state === ConditionState::VALID) {
                            $passed++;
                        } else {
                            $failed++;
                        }
                    }
                }

                $hasFailed = $failed > 0;
                $reqColor = $hasFailed ? 'red' : ($total > 0 ? 'green' : 'gray');
                $reqStatus = $hasFailed ? 'FAILED' : 'PASSED';
            @endphp
            <tr>
                <td class="text-cyan font-bold">{{ $module->identifier }}</td>
                <td class="text-yellow">{{ $module->identity->name }}</td>
                <td class="text-gray italic truncate w-60">
                    {{ $module->identity->description ?? '' }}
                </td>
                <td class="text-magenta">{{ $module->identity->author }}</td>
                <td class="text-green">{{ $module->version }}</td>
                <td class="text-{{ $statusColor }} font-bold">{{ $statusText }}</td>
                <td class="text-right">
                    @if($total > 0)
                        <div>
                            <span class="text-{{ $reqColor }} font-bold">{{ $reqStatus }}</span>
                            <span class="text-gray ml-1">({{ $passed }}/{{ $total }})</span>
                        </div>
                    @else
                        <span class="text-gray italic">NONE</span>
                    @endif
                </td>
                <td class="text-right">
                    @if(count($module->dependencies->modules) > 0)
                        <ol>
                            @foreach($module->dependencies->modules as $module => $version)
                                <li class="text-white">
                                    {{$module}} [{{$version}}]
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <span class="text-gray italic">NONE</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
