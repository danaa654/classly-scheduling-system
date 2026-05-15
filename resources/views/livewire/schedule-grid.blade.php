<div 
    x-data="scheduleGridApp()" 
    x-init="initializeGrid()" 
    class="relative flex h-full min-h-0 w-full min-w-0 flex-col overflow-hidden rounded-xl border border-slate-300 bg-white/40 shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/30"
>
    @php
        $activeDays = array_values($days ?? []);
        $activeDayCount = max(1, count($activeDays));
        $timeColumnWidthRem = 7;
        $minimumDayColumnWidthRem = $activeDayCount >= 7 ? 10 : ($activeDayCount >= 6 ? 9.5 : 8.75);
        $minimumGridWidthRem = $timeColumnWidthRem + ($activeDayCount * $minimumDayColumnWidthRem);
        $minimumGridWidth = "max(100%, {$minimumGridWidthRem}rem)";
        $dayColumnWidth = "((100% - {$timeColumnWidthRem}rem) / {$activeDayCount})";
    @endphp

    {{-- SCROLLABLE GRID CONTAINER --}}
    <div class="schedule-grid-scroll custom-scrollbar relative min-h-0 w-full flex-1 overflow-auto overscroll-contain pb-16">
        
        {{-- MAIN GRID STRUCTURE --}}
        <div class="relative inline-block min-h-full pb-14" style="--time-col: {{ $timeColumnWidthRem }}rem; --day-min: {{ $minimumDayColumnWidthRem }}rem; width: {{ $minimumGridWidth }}; min-width: {{ $minimumGridWidth }};">
            
            {{-- BASE GRID: TIME SLOTS + DAYS --}}
            <div class="grid gap-0 auto-rows-[45px]" style="grid-template-columns: var(--time-col) repeat({{ $activeDayCount }}, minmax(var(--day-min), 1fr));">
                
                {{-- HEADER: TIME LABEL --}}
                <div class="sticky top-0 left-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[11px] font-black uppercase border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                    Time
                </div>

                {{-- HEADER: DAY LABELS --}}
                @foreach($activeDays as $dayFull)
                    <div class="sticky top-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[11px] font-black uppercase border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                        {{ $dayLabels[$dayFull] ?? strtoupper(substr($dayFull, 0, 3)) }}
                    </div>
                @endforeach

                {{-- TIME SLOTS & DAY CELLS --}}
                @foreach($displaySlots as $slotIndex => $slot)
                    {{-- TIME LABEL COLUMN --}}
                    <div class="sticky left-0 z-20 h-[45px] flex items-center justify-center text-[11px] font-black uppercase border-r-2 border-b border-slate-300 dark:border-slate-700 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 text-slate-700 dark:text-slate-300 px-1">
                        {{ $slot['display'] }}
                    </div>

                    {{-- DAY CELLS --}}
                    @foreach($activeDays as $dayIndex => $dayFull)
                        @php
                            $isLunch = $slot['isLunch'] ?? false;
                            $cellKey = "{$dayFull}-{$slot['start']}";
                        @endphp

                        <div 
                            class="relative h-[45px] border-r border-b border-slate-300 dark:border-slate-700 transition-colors
                                   {{ $isLunch 
                                       ? 'bg-slate-300/50 dark:bg-slate-700/40 cursor-not-allowed' 
                                       : 'bg-white/30 dark:bg-slate-800/20 hover:bg-blue-50/40 dark:hover:bg-blue-900/20 cursor-move' }}"
                            wire:key="cell-{{ $cellKey }}"
                            data-day="{{ $dayFull }}"
                            data-day-short="{{ substr($dayFull, 0, 3) }}"
                            data-start="{{ $slot['start'] }}"
                            data-slot-index="{{ $slotIndex }}"
                            @if(!$isLunch)
                                @dragover.prevent="
                                    $el.classList.add('ring-2', 'ring-blue-500', 'bg-blue-100/60', 'dark:bg-blue-900/30');
                                "
                                @dragleave.prevent="
                                    $el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/60', 'dark:bg-blue-900/30');
                                "
                                @drop.prevent="
                                    $el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/60', 'dark:bg-blue-900/30');
                                    const subId = event.dataTransfer.getData('subject_id');
                                    const subCode = event.dataTransfer.getData('subject_code');
                                    if(subId) { 
                                        $wire.assignSubject(subId, '{{ $dayFull }}', '{{ $slot['start'] }}');
                                    }
                                "
                            @endif
                        >
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- ABSOLUTE POSITIONED SCHEDULE BLOCKS LAYER --}} 
            <div class="absolute inset-x-0 pointer-events-none overflow-visible" style="top: 3.5rem; bottom: 3.5rem;">
                
                {{-- LUNCH BREAK BAR (MINIMALIST FULL-WIDTH) --}}
                @php
                    // Calculate lunch break position based on dayStart
                    $gridStart = \Carbon\Carbon::parse($dayStart);
                    $lunchStart = \Carbon\Carbon::parse($lunchStart);
                    $lunchEnd = \Carbon\Carbon::parse($lunchEnd);
                    
                    $minutesFromStart = $gridStart->diffInMinutes($lunchStart);
                    
                    // Handle case where lunch break is before grid start
                    if ($minutesFromStart < 0) {
                        $lunchSlotIndex = 0;
                        $lunchHeightPx = 0;
                    } else {
                        $lunchSlotIndex = $minutesFromStart / 30;
                        $lunchDurationMinutes = $lunchStart->diffInMinutes($lunchEnd);
                        $lunchSlotsSpanned = $lunchDurationMinutes / 30;
                        $lunchHeightPx = ($lunchSlotsSpanned * 45) - 4;
                    }
                @endphp

                @if($lunchHeightPx > 0)
                    <div 
                        class="absolute left-0 right-0 pointer-events-none flex items-center justify-center bg-slate-200/60 dark:bg-slate-700/50 backdrop-blur-sm border-t-2 border-b-2 border-slate-400 dark:border-slate-600"
                        style="
                            top: calc(({{ $lunchSlotIndex }} * 45px) + 2px);
                            left: var(--time-col);
                            right: 0;
                            height: {{ $lunchHeightPx }}px;
                            z-index: 10;
                        "
                    >
                        <span class="text-[11px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                            LUNCH BREAK
                        </span>
                    </div>
                @endif

                {{-- SCHEDULE CARDS --}}
                @foreach($schedules as $schedule)
                    @php
                        $subject = $schedule->subject;
                        if (!$subject) continue;

                        // 1. Parse times precisely
                        $startTime = \Carbon\Carbon::parse($schedule->start_time);
                        $endTime = \Carbon\Carbon::parse($schedule->end_time);
                        $gridStart = \Carbon\Carbon::parse($dayStart);
                        $gridEnd = \Carbon\Carbon::parse($dayEnd);
                        
                        // 2. Calculate raw slot position (30-min increments from dayStart)
                        $minutesFromStart = $gridStart->diffInMinutes($startTime);
                        $rawSlotIndex = $minutesFromStart / 30;
                        $slotIndex = $rawSlotIndex;
                        
                        // 3. Calculate height (45px per 30-min slot)
                        $durationMinutes = $startTime->diffInMinutes($endTime);
                        $slotsSpanned = $durationMinutes / 30;
                        
                        // Calculate the maximum available height from start to grid end
                        $minutesToGridEnd = $startTime->diffInMinutes($gridEnd);
                        $maxSlotsAvailable = $minutesToGridEnd / 30;
                        
                        // Use the smaller of calculated slots or max available slots
                        $actualSlotsSpanned = min($slotsSpanned, $maxSlotsAvailable);
                        
                        // Height: 45px per slot minus 4px gap
                        $heightPx = ($actualSlotsSpanned * 45) - 4;
                        
                        // Ensure minimum height visibility
                        $heightPx = max(40, $heightPx);

                        // 4. Day Index for Horizontal placement
                        $dayFull = $schedule->day;
                        $dayIndex = array_search($dayFull, $activeDays, true);
                        if ($dayIndex === false) continue;
                        
                        // 5. Column positioning math
                        $oneDayColumn = $dayColumnWidth;
                        
                        // Calculate Overlaps for side-by-side display
                        $overlappingSchedules = $this->getSchedulesAtSlot($dayFull, $schedule->start_time, $schedule->end_time);
                        $totalOverlaps = count($overlappingSchedules);
                        
                        $myPosition = 0;
                        foreach ($overlappingSchedules as $index => $sched) {
                            if ($sched['id'] === $schedule->id) {
                                $myPosition = $index;
                                break;
                            }
                        }

                        $widthFactor = (1 / $totalOverlaps);
                        $offsetFactor = ($myPosition / $totalOverlaps);
                        
                        // 6. Color Mapping based on Department
                        $colorMap = [
                            'CCS'  => ['light' => 'bg-yellow-400/85 text-yellow-950', 'dark' => 'dark:bg-yellow-600/80 dark:text-yellow-50', 'border' => 'border-l-yellow-600'],
                            'IT'   => ['light' => 'bg-yellow-400/85 text-yellow-950', 'dark' => 'dark:bg-yellow-600/80 dark:text-yellow-50', 'border' => 'border-l-yellow-600'],
                            'ACT'  => ['light' => 'bg-yellow-400/85 text-yellow-950', 'dark' => 'dark:bg-yellow-600/80 dark:text-yellow-50', 'border' => 'border-l-yellow-600'],
                            'CTE'  => ['light' => 'bg-blue-400/85 text-blue-950', 'dark' => 'dark:bg-blue-600/80 dark:text-blue-50', 'border' => 'border-l-blue-600'],
                            'ED'   => ['light' => 'bg-blue-400/85 text-blue-950', 'dark' => 'dark:bg-blue-600/80 dark:text-blue-50', 'border' => 'border-l-blue-600'],
                            'COC'  => ['light' => 'bg-purple-400/85 text-purple-950', 'dark' => 'dark:bg-purple-600/80 dark:text-purple-50', 'border' => 'border-l-purple-600'],
                            'FB'   => ['light' => 'bg-purple-400/85 text-purple-950', 'dark' => 'dark:bg-purple-600/80 dark:text-purple-50', 'border' => 'border-l-purple-600'],
                            'LD'   => ['light' => 'bg-purple-400/85 text-purple-950', 'dark' => 'dark:bg-purple-600/80 dark:text-purple-50', 'border' => 'border-l-purple-600'],
                            'QD'   => ['light' => 'bg-purple-400/85 text-purple-950', 'dark' => 'dark:bg-purple-600/80 dark:text-purple-50', 'border' => 'border-l-purple-600'],
                            'SHTM' => ['light' => 'bg-orange-400/85 text-orange-950', 'dark' => 'dark:bg-orange-600/80 dark:text-orange-50', 'border' => 'border-l-orange-600'],
                            'HM'   => ['light' => 'bg-orange-400/85 text-orange-950', 'dark' => 'dark:bg-orange-600/80 dark:text-orange-50', 'border' => 'border-l-orange-600'],
                            'TM'   => ['light' => 'bg-orange-400/85 text-orange-950', 'dark' => 'dark:bg-orange-600/80 dark:text-orange-50', 'border' => 'border-l-orange-600'],
                        ];
                        
                        $colors = $colorMap[$subject->department] ?? [
                            'light' => 'bg-slate-400/85 text-slate-950', 'dark' => 'dark:bg-slate-600/80 dark:text-slate-50', 'border' => 'border-l-slate-600'
                        ];

                        $instructor = $this->cleanScheduleText($schedule->faculty?->full_name ?? 'Unassigned');
                        $subjectCode = $this->cleanScheduleText($subject->subject_code ?? 'N/A');
                        $subjectDescription = $this->cleanScheduleText($subject->description ?? 'No subject name');
                        $subjectType = $this->cleanScheduleText($subject->type ?? 'N/A');
                        $roomName = $this->cleanScheduleText($schedule->room?->room_name ?? $selectedRoomName ?? 'No room');
                        $canDeleteSchedule = $this->canDeleteSchedule($schedule->id);
                        $startTimeDisplay = $startTime->format('g:i A');
                        $endTimeDisplay = $endTime->format('g:i A');
                    @endphp

                    <div 
                        class="schedule-block absolute pointer-events-auto z-20 rounded-lg border border-black/10 shadow-sm backdrop-blur-sm transition-all duration-150 hover:-translate-y-0.5 hover:shadow-xl hover:z-50 group/card overflow-hidden ring-1 ring-white/30 dark:ring-white/10
                               {{ $colors['light'] }} {{ $colors['dark'] }} {{ $colors['border'] }} border-l-4"
                        style="
                            /* Vertical: Aligns with the 45px rows */
                            top: calc(({{ $slotIndex }} * 45px) + 2px);
                            
                            /* Horizontal: Places class in correct day column */
                            left: calc(var(--time-col) + ({{ $dayIndex }} * {!! $oneDayColumn !!}) + ({!! $oneDayColumn !!} * {{ $offsetFactor }}) + 2px);
                            
                            /* Width: Shrinks if there are overlaps */
                            width: calc(({!! $oneDayColumn !!} * {{ $widthFactor }}) - 4px);
                            
                            /* Height: Spans multiple slots based on duration, clamped to grid */
                            height: {{ $heightPx }}px;
                            
                            z-index: {{ 20 + $myPosition }};
                        "
                        wire:key="schedule-{{ $schedule->id }}"
                        @mouseenter="showSchedulePopover($event, $el, {
                            scheduleId: @js($schedule->id),
                            canDelete: @js($canDeleteSchedule),
                            code: @js($subjectCode),
                            description: @js($subjectDescription),
                            edp: @js($this->cleanScheduleText($subject->edp_code ?? 'N/A')),
                            section: @js($schedule->section ?? 'N/A'),
                            instructor: @js($instructor),
                            room: @js($roomName),
                            type: @js($subjectType),
                            department: @js($this->cleanScheduleText($subject->department ?? 'N/A')),
                            major: @js($this->cleanScheduleText($subject->major ?? 'N/A')),
                            status: @js($schedule->status ?? 'partial'),
                            time: @js($startTimeDisplay . ' - ' . $endTimeDisplay),
                            day: @js($dayFull)
                        })"
                        @focusin="showSchedulePopover($event, $el, {
                            scheduleId: @js($schedule->id),
                            canDelete: @js($canDeleteSchedule),
                            code: @js($subjectCode),
                            description: @js($subjectDescription),
                            edp: @js($this->cleanScheduleText($subject->edp_code ?? 'N/A')),
                            section: @js($schedule->section ?? 'N/A'),
                            instructor: @js($instructor),
                            room: @js($roomName),
                            type: @js($subjectType),
                            department: @js($this->cleanScheduleText($subject->department ?? 'N/A')),
                            major: @js($this->cleanScheduleText($subject->major ?? 'N/A')),
                            status: @js($schedule->status ?? 'partial'),
                            time: @js($startTimeDisplay . ' - ' . $endTimeDisplay),
                            day: @js($dayFull)
                        })"
                        @mouseleave="schedulePopoverLeave()"
                        tabindex="0"
                    >
                        {{-- SCHEDULE CARD CONTENT --}}
                        <div class="flex h-full w-full flex-col items-center justify-center gap-0.5 overflow-hidden bg-white/25 px-2 py-1.5 text-center leading-tight pointer-events-none dark:bg-black/15">
                            <div class="w-full truncate text-[7px] font-black uppercase text-current opacity-80 sm:text-[8px]">
                                {{ $subjectType }} | S{{ $schedule->section ?? 'N/A' }}
                            </div>
                            
                            <div class="w-full truncate text-[11px] font-black uppercase text-current sm:text-[12px]">
                                {{ $subjectCode }}
                            </div>

                            <div class="w-full truncate text-[7px] font-bold opacity-80">
                                EDP: {{ $this->cleanScheduleText($subject->edp_code ?? 'N/A') }}
                            </div>

                            <div class="w-full truncate text-[8px] font-bold opacity-90">
                                {{ $subjectDescription }}
                            </div>

                            <div class="w-full truncate text-[8px] font-bold opacity-90 sm:text-[9px]">
                                {{ $startTimeDisplay }} - {{ $endTimeDisplay }}
                            </div>

                            <div class="w-full truncate text-[7px] font-bold opacity-80">
                                {{ $roomName }} | {{ $instructor }}
                            </div>
                        </div>

                        {{-- OVERLAP BADGE --}}
                        @if($totalOverlaps > 1)
                            <div class="absolute top-0.5 right-0.5 text-[6px] font-black bg-red-500/90 text-white px-1 py-0.5 rounded-full border border-red-600 w-4 h-4 flex items-center justify-center flex-shrink-0">
                                {{ $totalOverlaps }}
                            </div>
                        @endif

                        {{-- SECTION BADGE --}}
                        <div class="absolute top-0.5 left-0.5 text-[6px] font-black bg-white/30 dark:bg-black/30 px-1 py-0.5 rounded border border-current/40 uppercase flex-shrink-0">
                            S{{ $schedule->section ?? 'N/A' }}
                        </div>

                        {{-- QUICK DELETE (HOVER) --}}
                        <button 
                            wire:click.stop="removeAssignment({{ $schedule->id }})"
                            wire:confirm="Remove this schedule?"
                            @disabled(!$canDeleteSchedule)
                            class="absolute bottom-0.5 right-0.5 opacity-0 group-hover/card:opacity-100 bg-red-500/80 hover:bg-red-600 text-white rounded p-0.5 transition-all shadow-sm flex-shrink-0"
                        >
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                    </div>
                @endforeach

                {{-- CURRENT TIME INDICATOR (RED LINE) --}}
                @php
                    $now = \Carbon\Carbon::now();
                    $gridStart = \Carbon\Carbon::parse($dayStart);
                    $gridEnd = \Carbon\Carbon::parse($dayEnd);
                    
                    if ($now >= $gridStart && $now < $gridEnd) {
                        // Calculate elapsed minutes from grid start
                        $elapsedMinutes = $gridStart->diffInMinutes($now);
                        // Calculate which 30-minute slot and position within that slot
                        $slotIndex = floor($elapsedMinutes / 30);
                        $minutesWithinSlot = $elapsedMinutes % 30;
                        // Calculate exact pixel position: each slot is 45px
                        $topPx = ($slotIndex * 45) + ($minutesWithinSlot / 30 * 45);
                    } else {
                        $topPx = -1;
                    }
                @endphp

                @if($topPx >= 0)
                    <div class="absolute left-0 right-0 pointer-events-none z-15" style="top: {{ $topPx }}px;">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1 bg-red-500 shadow-lg"></div>
                            <span class="text-[10px] font-black text-white bg-red-500 px-2 py-0.5 rounded-sm whitespace-nowrap">
                                {{ $now->format('h:i A') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SCHEDULE DETAIL POPOVER --}}
    <div 
        id="schedulePopover" 
        class="schedule-popover hidden fixed z-[9999] w-[26rem] max-h-[min(32rem,calc(100vh-2rem))] overflow-y-auto rounded-2xl border border-white/60 bg-white/85 p-4 shadow-2xl shadow-slate-950/20 backdrop-blur-2xl pointer-events-auto opacity-0 scale-95 transition-all duration-150 dark:border-slate-700/80 dark:bg-slate-900/90"
        style="max-width: calc(100vw - 16px);"
        @mouseenter="keepSchedulePopover()"
        @mouseleave="schedulePopoverLeave()"
    >
        <div class="space-y-3">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-700">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-black uppercase text-slate-900 dark:text-white line-clamp-1" id="popCode"></div>
                    <div class="mt-1 text-[11px] font-semibold leading-snug text-slate-600 dark:text-slate-300 line-clamp-2" id="popDesc"></div>
                </div>
                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-700 dark:bg-slate-700 dark:text-slate-200" id="popType"></span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[10px]">
                <div class="rounded-lg bg-slate-50 p-2 dark:bg-slate-950/50">
                    <span class="block text-[8px] font-black uppercase tracking-widest text-slate-500">EDP</span>
                    <span class="mt-1 block truncate font-black text-slate-900 dark:text-slate-100" id="popEdp">-</span>
                </div>
                <div class="rounded-lg bg-slate-50 p-2 dark:bg-slate-950/50">
                    <span class="block text-[8px] font-black uppercase tracking-widest text-slate-500">Section</span>
                    <span class="mt-1 block font-black text-slate-900 dark:text-slate-100" id="popSection">-</span>
                </div>
                <div class="rounded-lg bg-slate-50 p-2 dark:bg-slate-950/50">
                    <span class="block text-[8px] font-black uppercase tracking-widest text-slate-500">Type</span>
                    <span class="mt-1 block font-black text-slate-900 dark:text-slate-100" id="popType2">-</span>
                </div>
                <div class="rounded-lg bg-slate-50 p-2 dark:bg-slate-950/50">
                    <span class="block text-[8px] font-black uppercase tracking-widest text-slate-500">Day</span>
                    <span class="mt-1 block font-black text-slate-900 dark:text-slate-100" id="popDay">-</span>
                </div>
            </div>

            <div class="space-y-2 rounded-xl bg-slate-50 p-3 text-[11px] dark:bg-slate-950/50">
                <div class="flex items-start justify-between gap-3 font-bold">
                    <span class="shrink-0 text-slate-500 dark:text-slate-400">Time</span>
                    <span class="text-right text-slate-900 dark:text-slate-100" id="popTime">-</span>
                </div>
                <div class="flex items-start justify-between gap-3 font-bold">
                    <span class="shrink-0 text-slate-500 dark:text-slate-400">Instructor</span>
                    <span class="min-w-0 text-right text-slate-900 dark:text-slate-100" id="popInstructor">-</span>
                </div>
                <div class="flex items-start justify-between gap-3 font-bold">
                    <span class="shrink-0 text-slate-500 dark:text-slate-400">Room</span>
                    <span class="min-w-0 text-right text-slate-900 dark:text-slate-100" id="popRoom">-</span>
                </div>
            </div>

            <div class="flex gap-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                <button
                    type="button"
                    @click="$wire.requestScheduleEdit(activeScheduleId); hideSchedulePopover(true)"
                    :disabled="!activeCanDelete"
                    class="flex-1 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-45 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-300">
                    Edit
                </button>
                <button
                    type="button"
                    @click="if (confirm('Remove this schedule?')) { $wire.removeAssignment(activeScheduleId); hideSchedulePopover(true) }"
                    :disabled="!activeCanDelete"
                    class="flex-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-45 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-300">
                    Remove
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function scheduleGridApp() {
        return {
            activeScheduleId: null,
            activeCanDelete: false,
            popoverHideTimer: null,

            initializeGrid() {
                console.log('Schedule grid initialized');
            },

            showSchedulePopover(event, anchor, data) {
                const popover = document.getElementById('schedulePopover');
                if (!popover) return;

                this.keepSchedulePopover();
                this.activeScheduleId = data.scheduleId || null;
                this.activeCanDelete = Boolean(data.canDelete);

                const setText = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = value || 'N/A';
                };

                setText('popCode', data.code);
                setText('popDesc', data.description || 'No description');
                setText('popEdp', data.edp);
                setText('popSection', `S${data.section || 'N/A'}`);
                setText('popInstructor', data.instructor);
                setText('popRoom', data.room);
                setText('popTime', data.time);
                setText('popDay', data.day);
                setText('popType', data.type);
                setText('popType2', data.type);

                popover.style.display = 'block';
                popover.style.opacity = '0';
                popover.style.transform = 'translateY(4px) scale(0.96)';
                popover.style.left = '0px';
                popover.style.top = '0px';

                requestAnimationFrame(() => {
                    const anchorRect = anchor.getBoundingClientRect();
                    const popoverRect = popover.getBoundingClientRect();
                    const gap = 14;
                    const margin = 12;
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;
                    const popoverWidth = Math.min(popoverRect.width || 320, viewportWidth - (margin * 2));
                    const popoverHeight = Math.min(popoverRect.height || 240, viewportHeight - (margin * 2));

                    const candidates = [
                        {
                            x: anchorRect.right + gap,
                            y: anchorRect.top + (anchorRect.height / 2) - (popoverHeight / 2),
                        },
                        {
                            x: anchorRect.left - popoverWidth - gap,
                            y: anchorRect.top + (anchorRect.height / 2) - (popoverHeight / 2),
                        },
                        {
                            x: anchorRect.left + (anchorRect.width / 2) - (popoverWidth / 2),
                            y: anchorRect.bottom + gap,
                        },
                        {
                            x: anchorRect.left + (anchorRect.width / 2) - (popoverWidth / 2),
                            y: anchorRect.top - popoverHeight - gap,
                        },
                    ];

                    const fits = ({ x, y }) => (
                        x >= margin
                        && y >= margin
                        && x + popoverWidth <= viewportWidth - margin
                        && y + popoverHeight <= viewportHeight - margin
                    );

                    let { x, y } = candidates.find(fits) || candidates[0];

                    if (viewportWidth < 640) {
                        x = (viewportWidth - popoverWidth) / 2;
                        y = anchorRect.bottom + gap;
                    }

                    x = Math.max(margin, Math.min(x, viewportWidth - popoverWidth - margin));
                    y = Math.max(margin, Math.min(y, viewportHeight - popoverHeight - margin));

                    popover.style.left = x + 'px';
                    popover.style.top = y + 'px';
                    popover.style.maxWidth = popoverWidth + 'px';
                    popover.style.opacity = '1';
                    popover.style.transform = 'translateY(0) scale(1)';
                });
            },

            keepSchedulePopover() {
                if (this.popoverHideTimer) {
                    clearTimeout(this.popoverHideTimer);
                    this.popoverHideTimer = null;
                }
            },

            schedulePopoverLeave() {
                this.keepSchedulePopover();
                this.popoverHideTimer = setTimeout(() => this.hideSchedulePopover(), 140);
            },

            hideSchedulePopover(force = false) {
                const popover = document.getElementById('schedulePopover');
                if (popover) {
                    popover.style.opacity = '0';
                    popover.style.transform = 'translateY(4px) scale(0.96)';
                    setTimeout(() => {
                        if (force || popover.style.opacity === '0') {
                            popover.style.display = 'none';
                        }
                    }, 120);
                }
            }
        };
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .schedule-grid-scroll {
        scrollbar-gutter: stable;
    }
    
    @media (prefers-color-scheme: dark) {
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    }

    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .animate-pulse {
        animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>


