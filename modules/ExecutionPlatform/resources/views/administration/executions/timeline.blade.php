@php
    use Carbon\CarbonInterval;
    use Carbon\CarbonInterface;
    use EpsicubeModules\ExecutionPlatform\Enums\ExecutionStatus;
    use EpsicubeModules\ExecutionPlatform\Enums\WorkflowEventType;
    use Illuminate\Support\Carbon;

    $state = $getState() ?? [];
    /** @var ExecutionStatus|null $workflowStatus */
    $workflowStatus = is_array($state) && array_key_exists('status', $state) ? $state['status'] : null;
    $executionCreatedAtRaw = is_array($state) && array_key_exists('execution_created_at', $state)
        ? $state['execution_created_at']
        : null;
    $executionCreatedAt = $executionCreatedAtRaw instanceof CarbonInterface
        ? Carbon::instance($executionCreatedAtRaw)
        : (filled($executionCreatedAtRaw) ? Carbon::parse((string) $executionCreatedAtRaw) : null);
    /** @var array<int, array{id:int,type:WorkflowEventType,target: ?string,index: ?int,tick: ?int,payload:mixed,created_at: ?string}> $events */
    $events = is_array($state) && array_key_exists('events', $state) ? ($state['events'] ?? []) : $state;

    $eventTone = static function (WorkflowEventType $type): string {
        return match ($type) {
            WorkflowEventType::WorkflowStarted,
            WorkflowEventType::ActivityStarted,
            WorkflowEventType::SignalReceived,
            WorkflowEventType::TimerStarted => 'info',
            WorkflowEventType::ActivityCompleted,
            WorkflowEventType::TimerFired,
            WorkflowEventType::SignalConsumed => 'success',
            WorkflowEventType::ActivityAttemptFailed,
            WorkflowEventType::ActivityFailed => 'danger',
            WorkflowEventType::WorkflowCancelledRequested,
            WorkflowEventType::CancellationExceptionInjected => 'warning',
            default => 'neutral',
        };
    };

    $eventCategory = static function (WorkflowEventType $type): string {
        return match ($type) {
            WorkflowEventType::ActivityScheduled,
            WorkflowEventType::ActivityStarted,
            WorkflowEventType::ActivityCompleted,
            WorkflowEventType::ActivityAttemptFailed,
            WorkflowEventType::ActivityFailed => 'activity',
            WorkflowEventType::SignalReceived,
            WorkflowEventType::SignalConsumed => 'signal',
            WorkflowEventType::TimerStarted,
            WorkflowEventType::TimerFired => 'timer',
            default => 'workflow',
        };
    };

    $eventShape = static function (string $category): string {
        return match ($category) {
            'workflow' => '▲',
            'activity' => '■',
            'timer' => '◆',
            default => '●',
        };
    };

    $toneFromFilamentColor = static function (string $color): string {
        return match ($color) {
            'success' => 'success',
            'danger' => 'danger',
            'warning' => 'warning',
            'primary', 'info' => 'info',
            default => 'neutral',
        };
    };

    $categoryLabels = [
        'workflow' => 'Workflow',
        'activity' => 'Activity',
        'signal' => 'Signal',
        'timer' => 'Timer',
    ];

    $normalized = array_values(array_map(static function (array $event) use ($eventTone, $eventCategory, $eventShape): array {
        $time = filled($event['created_at'] ?? null) ? Carbon::parse((string) $event['created_at']) : null;
        /** @var WorkflowEventType $type */
        $type = $event['type'];
        $category = $eventCategory($type);

        return [
            'id' => (int) $event['id'],
            'type' => $type,
            'type_label' => $type->value,
            'target' => (string) ($event['target'] ?? '-'),
            'index' => $event['index'] ?? '-',
            'tick' => $event['tick'] ?? '-',
            'payload' => filled($event['payload'] ?? null) ? json_encode($event['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-',
            'relative_label' => '-',
            'relative_detail' => '-',
            'timestamp_us' => $time ? (int) $time->format('Uu') : null,
            'tone' => $eventTone($type),
            'category' => $category,
            'shape' => $eventShape($category),
        ];
    }, $events));

    $count = count($normalized);
    $timeValues = array_values(array_filter(
        array_map(static fn (array $event): ?int => $event['timestamp_us'], $normalized),
        static fn (?int $us): bool => $us !== null
    ));
    $minTime = ! empty($timeValues) ? min($timeValues) : null;
    $executionStartUs = $executionCreatedAt ? (int) $executionCreatedAt->format('Uu') : $minTime;

    $formatIntervalPrecise = static function (int $microseconds): string {
        return CarbonInterval::microsecond(max(0, $microseconds))->cascade()->forHumans([
            'minimumUnit' => 'microsecond',
            'maximumUnit' => 'hour',
            'short'       => true,
            'parts'       => 2,
        ]);
    };

    $formatIntervalCompact = static function (int $microseconds): string {
        return CarbonInterval::microsecond(max(0, $microseconds))->cascade()->forHumans([
            'minimumUnit' => 'microsecond',
            'maximumUnit' => 'hour',
            'short'       => true,
            'parts'       => 1,
        ]);
    };

    foreach ($normalized as $i => &$event) {
        $event['idx'] = $i;
        // Keep the axis readable: positions are based on event order, not elapsed time.
        // Relative time is still shown in labels/tooltips.
        $event['position'] = $count <= 1 ? 8.0 : 8.0 + (($i / ($count - 1)) * 84.0);

        if ($event['timestamp_us'] !== null && $executionStartUs !== null) {
            $relativeDurationUs = max(0, $event['timestamp_us'] - $executionStartUs);
            $event['relative_label'] = $formatIntervalCompact($relativeDurationUs);
            $event['relative_detail'] = $formatIntervalPrecise($relativeDurationUs);
        }
    }
    unset($event);

    $formatDuration = static function (?int $startUs, ?int $endUs) use ($formatIntervalCompact): string {
        if ($startUs === null || $endUs === null || $endUs < $startUs) {
            return '-';
        }

        return $formatIntervalCompact($endUs - $startUs);
    };

    $makeSegments = static function (
        array $events,
        WorkflowEventType $startType,
        array $endTypes,
        string $segmentTone = 'success',
        array $errorEndTypes = [],
        string $runningTone = 'info',
        bool $runningAnimated = true
    ) use ($formatDuration): array {
        $open = [];
        $segments = [];

        foreach ($events as $event) {
            $key = ($event['index'] !== '-') ? 'idx:'.$event['index'] : 'target:'.($event['target'] ?? '-').'|seq:'.$event['idx'];

            if ($event['type'] === $startType) {
                $open[$key] = $event;
                continue;
            }

            if (! in_array($event['type'], $endTypes, true)) {
                continue;
            }

            if (! isset($open[$key])) {
                continue;
            }

            $start = $open[$key];
            $segments[] = [
                'start' => min($start['position'], $event['position']),
                'end' => max($start['position'], $event['position']),
                'tone' => in_array($event['type'], $errorEndTypes, true) ? 'danger' : $segmentTone,
                'running' => false,
                'label' => $formatDuration($start['timestamp_us'] ?? null, $event['timestamp_us'] ?? null),
            ];

            unset($open[$key]);
        }

        foreach ($open as $start) {
            $segments[] = [
                'start' => $start['position'],
                'end' => 92.0,
                'tone' => $runningTone,
                'running' => true,
                'animated' => $runningAnimated,
                'label' => __('running...'),
            ];
        }

        return $segments;
    };

    $workflowToneByStatus = $toneFromFilamentColor($workflowStatus?->getColor() ?? 'gray');
    $isWorkflowPending = in_array($workflowStatus, [ExecutionStatus::QUEUED, ExecutionStatus::SCHEDULED, ExecutionStatus::PROCESSING], true);

    $workflowSegments = [];
    if ($count > 0) {
        $workflowSegments[] = [
            'start' => $normalized[0]['position'],
            'end' => $normalized[$count - 1]['position'],
            'tone' => $workflowToneByStatus,
            'running' => $isWorkflowPending,
            'animated' => $isWorkflowPending,
        ];
    }

    $activityGroups = [];
    foreach ($normalized as $event) {
        if ($event['category'] !== 'activity') {
            continue;
        }

        $key = ($event['index'] !== '-') ? 'idx:'.$event['index'] : 'target:'.$event['target'].'|seq:'.$event['idx'];

        if (! isset($activityGroups[$key])) {
            $activityGroups[$key] = [
                'scheduled' => null,
                'started' => [],
                'attempt_failed' => [],
                'completed' => null,
                'failed' => null,
            ];
        }

        match ($event['type']) {
            WorkflowEventType::ActivityScheduled => $activityGroups[$key]['scheduled'] = $event,
            WorkflowEventType::ActivityStarted => $activityGroups[$key]['started'][] = $event,
            WorkflowEventType::ActivityAttemptFailed => $activityGroups[$key]['attempt_failed'][] = $event,
            WorkflowEventType::ActivityCompleted => $activityGroups[$key]['completed'] = $event,
            WorkflowEventType::ActivityFailed => $activityGroups[$key]['failed'] = $event,
            default => null,
        };
    }

    $activityGroupRows = [];
    $rowCounter = 0;
    foreach (array_keys($activityGroups) as $activityGroupKey) {
        $activityGroupRows[$activityGroupKey] = $rowCounter++;
    }

    $activitySegments = [];
    $activityRetryMarkers = [];
    $activityRetryCount = 0;
    $activityRowSpacing = 22;
    $activityRowCenter = 18;

    foreach ($activityGroups as $groupKey => $group) {
        $scheduled = $group['scheduled'];
        $started = $group['started'];
        $attemptFailed = $group['attempt_failed'];
        $completed = $group['completed'];
        $failed = $group['failed'];
        $row = $activityGroupRows[$groupKey] ?? 0;
        $rowCenter = $activityRowCenter + ($row * $activityRowSpacing);
        $segmentTop = $rowCenter - 5;

        $activityRetryCount += count($attemptFailed);

        foreach ($attemptFailed as $retryIndex => $retryEvent) {
            $activityRetryMarkers[] = [
                'position' => $retryEvent['position'],
                'attempt' => $retryIndex + 1,
                'row' => $row,
                'top' => $rowCenter - 9,
            ];
        }

        $terminal = $failed ?? $completed;
        $segmentStartEvent = $scheduled ?? ($started[0] ?? ($attemptFailed[0] ?? $terminal));
        $segmentEndEvent = $terminal ?? ($started[count($started) - 1] ?? $segmentStartEvent);

        if (! $segmentStartEvent || ! $segmentEndEvent) {
            continue;
        }

        $isFinished = (bool) $terminal;
        $tone = $failed ? 'danger' : ($completed ? 'success' : 'info');
        $startUs = $segmentStartEvent['timestamp_us'] ?? null;
        $endUs = $isFinished ? ($terminal['timestamp_us'] ?? null) : ($segmentEndEvent['timestamp_us'] ?? null);

        $activitySegments[] = [
            'group_key' => $groupKey,
            'start' => $segmentStartEvent['position'],
            'end' => $isFinished ? $segmentEndEvent['position'] : 92.0,
            'tone' => $tone,
            'running' => ! $isFinished,
            'animated' => ! $isFinished,
            'label' => $isFinished ? $formatDuration($startUs, $endUs) : __('running...'),
            'row' => $row,
            'top' => $segmentTop,
        ];
    }

    usort($activitySegments, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);
    $activityLaneRows = max(1, count($activityGroupRows));
    $activityLaneHeight = max(44, 18 + (($activityLaneRows - 1) * $activityRowSpacing) + 18);

    foreach ($normalized as &$event) {
        if ($event['category'] !== 'activity') {
            continue;
        }

        $groupKey = ($event['index'] !== '-')
            ? 'idx:'.$event['index']
            : 'target:'.$event['target'].'|seq:'.$event['idx'];

        $row = $activityGroupRows[$groupKey] ?? 0;
        $rowCenter = $activityRowCenter + ($row * $activityRowSpacing);
        $event['activity_row'] = $row;
        $event['activity_point_top'] = $rowCenter - 7;
    }
    unset($event);

    usort($activityRetryMarkers, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

    $signalSegments = [];
    $timerSegments = $makeSegments($normalized, WorkflowEventType::TimerStarted, [WorkflowEventType::TimerFired], 'warning', [], 'warning');

    $laneSegments = [
        'workflow' => $workflowSegments,
        'activity' => $activitySegments,
        'signal' => $signalSegments,
        'timer' => $timerSegments,
    ];

    $laneStats = [];
    foreach ($categoryLabels as $categoryKey => $unusedLabel) {
        $laneEvents = array_values(array_filter($normalized, static fn (array $event): bool => $event['category'] === $categoryKey));
        $first = $laneEvents[0] ?? null;
        $last = ! empty($laneEvents) ? $laneEvents[count($laneEvents) - 1] : null;
        $laneStats[$categoryKey] = [
            'count' => count($laneEvents),
            'range' => $first && $last ? $first['relative_label'].' - '.$last['relative_label'] : '-',
            'duration' => $first && $last ? $formatDuration($first['timestamp_us'], $last['timestamp_us']) : '-',
            'retries' => $categoryKey === 'activity' ? $activityRetryCount : 0,
        ];
    }

    $laneTone = static function (string $categoryKey) use ($workflowToneByStatus): string {
        return match ($categoryKey) {
            'workflow' => $workflowToneByStatus,
            'activity' => 'success',
            'timer' => 'warning',
            default => 'neutral',
        };
    };

    $laneData = [];
    foreach ($categoryLabels as $categoryKey => $categoryLabel) {
        $laneData[] = [
            'key' => $categoryKey,
            'label' => $categoryLabel,
            'tone' => $laneTone($categoryKey),
            'stats' => $laneStats[$categoryKey],
            'segments' => $laneSegments[$categoryKey] ?? [],
            'show_rail' => $categoryKey === 'signal' || empty($laneSegments[$categoryKey]),
            'stacked' => $categoryKey === 'activity',
            'height' => $categoryKey === 'activity' ? $activityLaneHeight : null,
        ];
    }

    $globalStart = $normalized[0] ?? null;
    $globalEnd = $count > 0 ? $normalized[$count - 1] : null;
    $globalDuration = $globalStart && $globalEnd ? $formatDuration($globalStart['timestamp_us'], $globalEnd['timestamp_us']) : '-';
    $activeEvent = $normalized[0];
@endphp

@assets
<style>
    .ep-tl {
        --ep-label-col: 116px;
        --ep-track-pad-x: 14px;
        --ep-axis-center-y: 35px;
        --ep-lane-center-y: 20px;
        --ep-bg: #ffffff;
        --ep-bg-soft: #f8fafc;
        --ep-text: #0f172a;
        --ep-muted: #64748b;
        --ep-border: #dbe4ef;
        --ep-rail: #c8d4e3;
        --ep-primary: #2563eb;
        --ep-info: #0284c7;
        --ep-success: #059669;
        --ep-danger: #dc2626;
        --ep-warning: #d97706;
        --ep-neutral: #64748b;
    }

    .dark .ep-tl {
        --ep-bg: #0f172a;
        --ep-bg-soft: #111827;
        --ep-text: #e2e8f0;
        --ep-muted: #94a3b8;
        --ep-border: #334155;
        --ep-rail: #475569;
        --ep-primary: #60a5fa;
    }

    .ep-tl__empty {
        font-size: 13px;
        color: var(--ep-muted);
    }

    .ep-tl__viewport {
        overflow-x: auto;
        padding: 6px 2px 12px;
    }

    .ep-tl__timeline {
        min-width: 100%;
    }

    .ep-tl__summary {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .ep-tl__summary-item,
    .ep-tl__legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--ep-border);
        border-radius: 999px;
        background: var(--ep-bg-soft);
        color: var(--ep-muted);
        font-size: 11px;
        padding: 2px 8px;
    }

    .ep-tl__legend {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }

    .ep-tl__shape {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--ep-neutral);
        color: #fff;
        font-size: 8px;
        line-height: 1;
        font-weight: 700;
    }

    .ep-tl__shape.is-info {
        background: var(--ep-info);
    }

    .ep-tl__shape.is-success {
        background: var(--ep-success);
    }

    .ep-tl__shape.is-warning {
        background: var(--ep-warning);
    }

    .ep-tl__shape.is-danger {
        background: var(--ep-danger);
    }

    .ep-tl__axis-row,
    .ep-tl__lane {
        display: grid;
        grid-template-columns: var(--ep-label-col) 1fr;
        min-width: 100%;
    }

    .ep-tl__axis-row {
        border: 1px solid var(--ep-border);
        border-radius: 10px;
        background: var(--ep-bg);
        overflow: hidden;
        margin-bottom: 6px;
    }

    .ep-tl__axis-label,
    .ep-tl__lane-label {
        padding: 6px 8px;
        border-right: 1px solid var(--ep-border);
        background: var(--ep-bg-soft);
        color: var(--ep-muted);
        font-size: 10px;
    }

    .ep-tl__axis-label {
        font-size: 11px;
    }

    .ep-tl__lane-label-title {
        color: var(--ep-text);
        font-size: 11px;
        margin-bottom: 2px;
    }

    .ep-tl__lane-label-meta {
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ep-tl__track {
        position: relative;
        height: 66px;
        padding-inline: var(--ep-track-pad-x);
        box-sizing: border-box;
    }

    .ep-tl__track::before {
        content: "";
        position: absolute;
        left: var(--ep-track-pad-x);
        right: var(--ep-track-pad-x);
        top: calc(var(--ep-axis-center-y) - 1px);
        height: 2px;
        border-radius: 2px;
        background: linear-gradient(90deg, var(--ep-rail), var(--ep-border));
    }

    .ep-tl__track::after,
    .ep-tl__lane-track::after {
        content: "";
        position: absolute;
        top: 4px;
        bottom: 4px;
        left: clamp(0%, var(--ep-hover-x, 0%), 100%);
        width: 2px;
        margin-left: -1px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--ep-primary) 72%, transparent);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--ep-bg) 68%, transparent);
        opacity: 0;
        pointer-events: none;
        transition: opacity .12s ease;
        z-index: 2;
    }

    .ep-tl.is-hovering .ep-tl__track::after,
    .ep-tl.is-hovering .ep-tl__lane-track::after {
        opacity: 1;
    }

    .ep-tl__label {
        position: absolute;
        top: 2px;
        transform: translateX(-50%);
        font-size: 10px;
        color: var(--ep-muted);
        white-space: nowrap;
        z-index: 4;
    }

    .ep-tl__point {
        appearance: none;
        -webkit-appearance: none;
        position: absolute;
        top: calc(var(--ep-axis-center-y) - 11px);
        width: 22px;
        height: 22px;
        padding: 0;
        margin-left: -11px;
        border: 2px solid var(--ep-border);
        border-radius: 999px;
        background: var(--ep-bg);
        color: var(--ep-neutral);
        cursor: pointer;
        transition: transform .14s ease, border-color .14s ease, box-shadow .14s ease;
        z-index: 5;
    }

    .ep-tl__point::before {
        content: "";
        position: absolute;
        inset: 4px;
        background: currentColor;
        border-radius: 999px;
    }

    .ep-tl__point::after {
        content: attr(data-shape);
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ep-bg);
        font-size: 7px;
        line-height: 1;
        font-weight: 700;
        pointer-events: none;
    }

    .ep-tl__point:hover {
        transform: translateY(-1px);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .13);
    }

    .ep-tl__point:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .22);
    }

    .ep-tl__point.is-active {
        border-color: var(--ep-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
    }

    .ep-tl__point.is-info {
        color: var(--ep-info);
    }

    .ep-tl__point.is-success {
        color: var(--ep-success);
    }

    .ep-tl__point.is-danger {
        color: var(--ep-danger);
    }

    .ep-tl__point.is-warning {
        color: var(--ep-warning);
    }

    .ep-tl__point.is-neutral {
        color: var(--ep-neutral);
    }

    .ep-tl__lanes {
        border: 1px solid var(--ep-border);
        border-radius: 10px;
        background: var(--ep-bg);
        overflow: hidden;
    }

    .ep-tl__lane:not(:last-child) {
        border-bottom: 1px solid var(--ep-border);
    }

    .ep-tl__lane-track {
        position: relative;
        min-height: 46px;
        padding: 8px var(--ep-track-pad-x);
        box-sizing: border-box;
    }

    .ep-tl__lane-track::before {
        content: "";
        position: absolute;
        left: var(--ep-track-pad-x);
        right: var(--ep-track-pad-x);
        top: var(--ep-lane-center-y);
        height: 1px;
        background: var(--ep-border);
    }

    .ep-tl__lane-track--stacked::before,
    .ep-tl__lane-track--no-rail::before {
        display: none;
    }

    .ep-tl__segment {
        --ep-seg-rgb: 100, 116, 139;
        position: absolute;
        top: calc(var(--ep-lane-center-y) - 5px);
        height: 10px;
        border-radius: 999px;
        background: rgba(var(--ep-seg-rgb), 0.16);
        border: 1px solid rgba(var(--ep-seg-rgb), 0.38);
        pointer-events: none;
        z-index: 1;
        overflow: visible;
    }

    .ep-tl__segment.is-info {
        --ep-seg-rgb: 2, 132, 199;
    }

    .ep-tl__segment.is-success {
        --ep-seg-rgb: 5, 150, 105;
    }

    .ep-tl__segment.is-danger {
        --ep-seg-rgb: 220, 38, 38;
    }

    .ep-tl__segment.is-warning {
        --ep-seg-rgb: 217, 119, 6;
    }

    .ep-tl__segment.is-neutral {
        --ep-seg-rgb: 100, 116, 139;
    }

    .ep-tl__segment.is-running {
        border-style: dashed;
        border-color: rgba(var(--ep-seg-rgb), 0.65);
    }

    .ep-tl__segment.is-animated {
        background-image: repeating-linear-gradient(
                -45deg,
                rgba(var(--ep-seg-rgb), 0.38) 0,
                rgba(var(--ep-seg-rgb), 0.38) 6px,
                rgba(var(--ep-seg-rgb), 0.12) 6px,
                rgba(var(--ep-seg-rgb), 0.12) 12px
        );
        background-size: 24px 100%;
        animation: ep-tl-stripes .8s linear infinite;
    }

    .ep-tl__segment-label {
        position: absolute;
        left: calc(100% + 6px);
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        line-height: 1.2;
        color: var(--ep-text);
        padding: 2px 6px;
        border-radius: 999px;
        border: 1px solid rgba(var(--ep-seg-rgb), 0.45);
        background: color-mix(in srgb, var(--ep-bg) 80%, rgba(var(--ep-seg-rgb), 0.28));
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        pointer-events: none;
        white-space: nowrap;
        z-index: 8;
    }

    .ep-tl__retry-marker {
        position: absolute;
        top: 3px;
        width: 2px;
        height: 26px;
        margin-left: -1px;
        border-radius: 999px;
        background: rgba(220, 38, 38, 0.75);
        pointer-events: none;
        z-index: 4;
    }

    .ep-tl__retry-marker::after {
        content: "";
        position: absolute;
        top: -3px;
        left: -2px;
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: rgba(220, 38, 38, 0.9);
    }

    @keyframes ep-tl-stripes {
        from {
            background-position: 0 0;
        }
        to {
            background-position: 24px 0;
        }
    }

    .ep-tl__point--mini {
        top: calc(var(--ep-lane-center-y) - 7px);
        width: 14px;
        height: 14px;
        margin-left: -7px;
        border-width: 1px;
        z-index: 3;
    }

    .ep-tl__point--mini::before {
        inset: 3px;
    }

    .ep-tl__point--mini::after {
        font-size: 0;
    }

    .ep-tl__panel {
        border: 1px solid var(--ep-border);
        border-radius: 12px;
        background: var(--ep-bg);
        padding: 10px 12px;
    }

    .ep-tl__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .ep-tl__badge {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--ep-border);
        border-radius: 999px;
        padding: 2px 10px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.4;
        color: var(--ep-neutral);
        background: var(--ep-bg-soft);
    }

    .ep-tl__badge.is-info {
        color: var(--ep-info);
    }

    .ep-tl__badge.is-success {
        color: var(--ep-success);
    }

    .ep-tl__badge.is-danger {
        color: var(--ep-danger);
    }

    .ep-tl__badge.is-warning {
        color: var(--ep-warning);
    }

    .ep-tl__event-id {
        font-size: 12px;
        color: var(--ep-muted);
    }

    .ep-tl__meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px 12px;
    }

    .ep-tl__k {
        font-size: 11px;
        color: var(--ep-muted);
        margin-bottom: 2px;
    }

    .ep-tl__v {
        font-size: 12px;
        color: var(--ep-text);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ep-tl__payload {
        margin-top: 8px;
        border: 1px solid var(--ep-border);
        border-radius: 8px;
        background: var(--ep-bg-soft);
        padding: 8px;
        font-size: 11px;
        line-height: 1.35;
        color: var(--ep-text);
        max-height: 156px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }

    @media (max-width: 900px) {
        .ep-tl {
            --ep-label-col: 102px;
        }

        .ep-tl__meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endassets

@if (empty($normalized))
    <div class="ep-tl__empty">{{ __('No events yet.') }}</div>
@else
    <div class="ep-tl js-ep-tl-root" data-active-index="0">
        <script type="application/json" class="js-ep-tl-events">@json($normalized)</script>
        <div class="ep-tl__viewport">
            <div class="ep-tl__timeline">
                <div class="ep-tl__summary">
                    <span class="ep-tl__summary-item">{{ __('Events') }}: {{ $count }}</span>
                    <span class="ep-tl__summary-item">{{ __('Status') }}: {{ $workflowStatus?->value ?? '-' }}</span>
                    <span class="ep-tl__summary-item">{{ __('Scale') }}: {{ __('event-linear') }}</span>
                    <span class="ep-tl__summary-item">{{ __('Window') }}: {{ __('t+0') }} - {{ $globalEnd['relative_label'] ?? '-' }}</span>
                    <span class="ep-tl__summary-item">{{ __('Elapsed') }}: {{ $globalDuration }}</span>
                </div>

                <div class="ep-tl__legend">
                    @foreach ($laneData as $lane)
                        <span class="ep-tl__legend-item">
                            <span class="ep-tl__shape is-{{ $lane['tone'] }}">{{ $eventShape($lane['key']) }}</span>
                            {{ __($lane['label']) }}
                        </span>
                    @endforeach
                </div>

                <div class="ep-tl__axis-row">
                    <div class="ep-tl__axis-label">{{ __('Timeline') }}</div>
                    <div>
                        <div class="ep-tl__track">
                            @foreach ($normalized as $i => $event)
                                @php($showLabel = $count <= 8 || $i === 0 || $i === $count - 1 || $i % max(1, (int) floor($count / 5)) === 0)
                                <button
                                        type="button"
                                        class="ep-tl__point is-{{ $event['tone'] }} {{ $i === 0 ? 'is-active' : '' }}"
                                        style="left: {{ $event['position'] }}%;"
                                        data-event-index="{{ $i }}"
                                        data-shape="{{ $event['shape'] }}"
                                        title="#{{ $event['id'] }} {{ $event['type_label'] }} - {{ $event['relative_detail'] }}"
                                        aria-label="#{{ $event['id'] }} {{ $event['type_label'] }}"
                                ></button>
                                @if ($showLabel)
                                    <span class="ep-tl__label"
                                          style="left: {{ $event['position'] }}%;">{{ $event['relative_label'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ep-tl__lanes">
                    @foreach ($laneData as $lane)
                        <div class="ep-tl__lane">
                            <div class="ep-tl__lane-label">
                                <div class="ep-tl__lane-label-title">{{ __($lane['label']) }}
                                    ({{ $lane['stats']['count'] }})
                                </div>
                                <div class="ep-tl__lane-label-meta">{{ __('t+0') }}
                                    - {{ explode(' - ', $lane['stats']['range'])[1] ?? '-' }}</div>
                                <div class="ep-tl__lane-label-meta">Δ {{ $lane['stats']['duration'] }}</div>
                                @if ($lane['key'] === 'activity')
                                    <div class="ep-tl__lane-label-meta">{{ __('Retries') }}
                                        : {{ $lane['stats']['retries'] }}</div>
                                @endif
                            </div>
                            <div
                                    class="ep-tl__lane-track {{ $lane['stacked'] ? 'ep-tl__lane-track--stacked' : '' }} {{ ! $lane['show_rail'] ? 'ep-tl__lane-track--no-rail' : '' }}"
                                    @if ($lane['height'])
                                        style="min-height: {{ $lane['height'] }}px;"
                                    @endif
                            >
                                @foreach ($lane['segments'] as $segment)
                                    @php($rawWidth = max(0, $segment['end'] - $segment['start']))
                                    @php($width = $lane['stacked'] ? max(1.4, $rawWidth) : max(0.4, $rawWidth))
                                    @php($left = $segment['start'])
                                    @if ($width > $rawWidth)
                                        @php($left = max(0, min(100 - $width, $segment['start'] - (($width - $rawWidth) / 2))))
                                    @endif
                                    <span
                                            class="ep-tl__segment is-{{ $segment['tone'] }} {{ ! empty($segment['running']) ? 'is-running' : '' }} {{ ! empty($segment['animated']) ? 'is-animated' : '' }}"
                                            style="left: {{ $left }}%; width: {{ $width }}%; {{ $lane['stacked'] ? 'top: '.($segment['top'] ?? 12).'px;' : '' }}"
                                    >
                                        @if (! empty($segment['label']) && $segment['label'] !== '-')
                                            <span class="ep-tl__segment-label">{{ $segment['label'] }}</span>
                                        @endif
                                    </span>
                                @endforeach

                                @if ($lane['key'] === 'activity')
                                    @foreach ($activityRetryMarkers as $retryMarker)
                                        <span
                                                class="ep-tl__retry-marker"
                                                style="left: {{ $retryMarker['position'] }}%; top: {{ $retryMarker['top'] }}px; height: 16px;"
                                                title="{{ __('Retry #:attempt', ['attempt' => $retryMarker['attempt']]) }}"
                                        ></span>
                                    @endforeach
                                @endif

                                @foreach ($normalized as $i => $event)
                                    @continue($event['category'] !== $lane['key'])
                                    <button
                                            type="button"
                                            class="ep-tl__point ep-tl__point--mini is-{{ $event['tone'] }} {{ $i === 0 ? 'is-active' : '' }}"
                                            style="left: {{ $event['position'] }}%; {{ $lane['stacked'] ? 'top: '.($event['activity_point_top'] ?? 8).'px;' : '' }}"
                                            data-event-index="{{ $i }}"
                                            data-shape="{{ $event['shape'] }}"
                                            title="#{{ $event['id'] }} {{ $event['type_label'] }} - {{ $event['relative_detail'] }}"
                                            aria-label="#{{ $event['id'] }} {{ $event['type_label'] }}"
                                    ></button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="ep-tl__panel">
            <div class="ep-tl__head">
                <span class="ep-tl__badge js-ep-tl-type is-{{ $activeEvent['tone'] }}">{{ $activeEvent['type_label'] }}</span>
                <span class="ep-tl__event-id js-ep-tl-id">#{{ $activeEvent['id'] }}</span>
            </div>

            <div class="ep-tl__meta">
                <div>
                    <div class="ep-tl__k">{{ __('Time') }}</div>
                    <div class="ep-tl__v js-ep-tl-time">{{ $activeEvent['relative_detail'] }}</div>
                </div>
                <div>
                    <div class="ep-tl__k">{{ __('Target') }}</div>
                    <div class="ep-tl__v js-ep-tl-target">{{ $activeEvent['target'] }}</div>
                </div>
                <div>
                    <div class="ep-tl__k">{{ __('Index') }}</div>
                    <div class="ep-tl__v js-ep-tl-index">{{ $activeEvent['index'] }}</div>
                </div>
                <div>
                    <div class="ep-tl__k">{{ __('Tick') }}</div>
                    <div class="ep-tl__v js-ep-tl-tick">{{ $activeEvent['tick'] }}</div>
                </div>
            </div>

            <pre class="ep-tl__payload js-ep-tl-payload">{{ $activeEvent['payload'] }}</pre>
        </div>

        @script
        <script>
            (function () {
                if (!window.epWorkflowTimelineInitRoot) {
                    window.epWorkflowTimelineInitRoot = (root) => {
                        if (!(root instanceof HTMLElement) || root.dataset.epTlBound === '1') {
                            return;
                        }

                        const eventsNode = root.querySelector('.js-ep-tl-events');
                        if (!(eventsNode instanceof HTMLScriptElement)) {
                            return;
                        }

                        let events = [];

                        try {
                            events = JSON.parse(eventsNode.textContent ?? '[]');
                        } catch (error) {
                            console.error('Failed to parse timeline events.', error);
                            return;
                        }

                        if (!Array.isArray(events) || events.length === 0) {
                            return;
                        }

                        const points = root.querySelectorAll('.ep-tl__point');
                        const type = root.querySelector('.js-ep-tl-type');
                        const id = root.querySelector('.js-ep-tl-id');
                        const time = root.querySelector('.js-ep-tl-time');
                        const target = root.querySelector('.js-ep-tl-target');
                        const index = root.querySelector('.js-ep-tl-index');
                        const tick = root.querySelector('.js-ep-tl-tick');
                        const payload = root.querySelector('.js-ep-tl-payload');
                        const interactiveTracks = root.querySelectorAll('.ep-tl__track, .ep-tl__lane-track');
                        const axisTrack = root.querySelector('.ep-tl__track');
                        const tones = ['is-info', 'is-success', 'is-danger', 'is-warning', 'is-neutral'];
                        let activeIndex = Number(root.dataset.activeIndex ?? 0);
                        let lockedIndex = null;

                        const setActive = (eventIndex) => {
                            const item = events[eventIndex];
                            if (!item || !type || !id || !time || !target || !index || !tick || !payload) {
                                return;
                            }

                            activeIndex = eventIndex;
                            root.dataset.activeIndex = String(eventIndex);

                            points.forEach((point) => {
                                point.classList.toggle('is-active', Number(point.dataset.eventIndex) === eventIndex);
                            });

                            tones.forEach((tone) => type.classList.remove(tone));
                            type.classList.add(`is-${item.tone}`);

                            type.textContent = item.type_label;
                            id.textContent = `#${item.id}`;
                            time.textContent = item.relative_detail ?? '-';
                            target.textContent = item.target ?? '-';
                            index.textContent = String(item.index ?? '-');
                            tick.textContent = String(item.tick ?? '-');
                            payload.textContent = item.payload ?? '-';
                        };

                        const getPreviousIndexFromPosition = (position) => {
                            let previousIndex = 0;

                            for (let i = 0; i < events.length; i += 1) {
                                if ((events[i].position ?? 0) <= position) {
                                    previousIndex = i;
                                    continue;
                                }

                                break;
                            }

                            return previousIndex;
                        };

                        const updateHoverPosition = (clientX) => {
                            if (!(axisTrack instanceof HTMLElement)) {
                                return;
                            }

                            const rect = axisTrack.getBoundingClientRect();
                            if (!rect.width) {
                                return;
                            }

                            const offsetX = Math.max(0, Math.min(rect.width, clientX - rect.left));
                            const position = (offsetX / rect.width) * 100;
                            root.style.setProperty('--ep-hover-x', `${position}%`);
                            root.classList.add('is-hovering');

                            if (lockedIndex !== null) {
                                return;
                            }

                            setActive(getPreviousIndexFromPosition(position));
                        };

                        const clearHoverPosition = () => {
                            root.classList.remove('is-hovering');
                        };

                        const focusPoint = (eventIndex) => {
                            const point = root.querySelector(`.ep-tl__point[data-event-index="${eventIndex}"]`);
                            if (point instanceof HTMLElement) {
                                point.focus({preventScroll: true});
                            }
                        };

                        const moveSelection = (offset) => {
                            const nextIndex = Math.max(0, Math.min(events.length - 1, activeIndex + offset));
                            if (nextIndex === activeIndex) {
                                return;
                            }

                            setActive(nextIndex);
                            focusPoint(nextIndex);
                        };

                        root.addEventListener('click', (event) => {
                            const targetNode = event.target;
                            if (!(targetNode instanceof Element)) {
                                return;
                            }

                            const button = targetNode.closest('.ep-tl__point');
                            if (!button || !root.contains(button)) {
                                lockedIndex = null;

                                const hoveredTrack = targetNode.closest('.ep-tl__track, .ep-tl__lane-track');
                                if (hoveredTrack instanceof HTMLElement && axisTrack instanceof HTMLElement) {
                                    const rect = axisTrack.getBoundingClientRect();
                                    if (rect.width) {
                                        const offsetX = Math.max(0, Math.min(rect.width, event.clientX - rect.left));
                                        const position = (offsetX / rect.width) * 100;
                                        setActive(getPreviousIndexFromPosition(position));
                                    }
                                }

                                return;
                            }

                            const nextIndex = Number(button.dataset.eventIndex);
                            lockedIndex = nextIndex;
                            setActive(nextIndex);
                        });

                        root.addEventListener('keydown', (event) => {
                            if (event.key === 'ArrowLeft') {
                                event.preventDefault();
                                moveSelection(-1);
                            }

                            if (event.key === 'ArrowRight') {
                                event.preventDefault();
                                moveSelection(1);
                            }
                        });

                        interactiveTracks.forEach((track) => {
                            track.addEventListener('mousemove', (event) => {
                                updateHoverPosition(event.clientX);
                            });

                            track.addEventListener('mouseenter', (event) => {
                                updateHoverPosition(event.clientX);
                            });
                        });

                        root.addEventListener('mousemove', (event) => {
                            const targetNode = event.target;
                            if (targetNode instanceof Element && targetNode.closest('.ep-tl__track, .ep-tl__lane-track')) {
                                return;
                            }

                            clearHoverPosition();
                        });

                        root.addEventListener('mouseleave', () => {
                            clearHoverPosition();
                        });

                        setActive(activeIndex);
                        root.dataset.epTlBound = '1';
                    };
                }

                if (!window.epWorkflowTimelineInitAll) {
                    window.epWorkflowTimelineInitAll = (scope = document) => {
                        scope.querySelectorAll('.js-ep-tl-root').forEach((root) => {
                            window.epWorkflowTimelineInitRoot(root);
                        });
                    };
                }

                window.epWorkflowTimelineInitAll();
            })();
        </script>
        @endscript
    </div>
@endif
