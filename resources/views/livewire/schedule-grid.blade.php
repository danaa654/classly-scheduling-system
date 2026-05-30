<div 
    x-data="scheduleGridApp()" 
    x-init="initializeGrid()" 
    class="relative flex h-full min-h-0 w-full min-w-0 flex-col overflow-hidden rounded-xl border border-slate-300 bg-white/40 shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/30"
>
    @php
        $activeDays = array_values($days ?? []);
        $activeDayCount = max(1, count($activeDays));
        $timeColumnWidthRem = 5.5;
        $minimumDayColumnWidthRem = $activeDayCount >= 7 ? 6.5 : ($activeDayCount >= 6 ? 7 : 7.5);
        $minimumGridWidthRem = $timeColumnWidthRem + ($activeDayCount * $minimumDayColumnWidthRem);
        $dayColumnWidth = "((100% - {$timeColumnWidthRem}rem) / {$activeDayCount})";
    @endphp

    {{-- SCROLLABLE GRID CONTAINER --}}
    <div class="schedule-grid-scroll custom-scrollbar relative min-h-0 w-full flex-1 overflow-auto overscroll-contain">
        
        {{-- MAIN GRID STRUCTURE --}}
        <div class="relative w-full h-full min-h-full" style="--time-col: {{ $timeColumnWidthRem }}rem; --day-min: {{ $minimumDayColumnWidthRem }}rem; min-width: {{ $minimumGridWidthRem }}rem;">
            
            {{-- BASE GRID: TIME SLOTS + DAYS --}}
            <div class="grid gap-0 auto-rows-[45px]" style="grid-template-columns: var(--time-col) repeat({{ $activeDayCount }}, minmax(var(--day-min), 1fr));">
                
                {{-- HEADER: TIME LABEL --}}
                <div class="sticky top-0 left-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[13px] font-black uppercase tracking-widest border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                    Time
                </div>

                {{-- HEADER: DAY LABELS --}}
                @foreach($activeDays as $dayFull)
                    <div class="sticky top-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[13px] font-black uppercase tracking-widest border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                        {{ $dayLabels[$dayFull] ?? strtoupper(substr($dayFull, 0, 3)) }}
                    </div>
                @endforeach

                {{-- TIME SLOTS & DAY CELLS --}}
                @foreach($displaySlots as $slotIndex => $slot)
                    {{-- TIME LABEL COLUMN --}}
                    <div class="sticky left-0 z-20 h-[45px] flex flex-col items-center justify-center border-r-2 border-b border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 text-slate-700 dark:text-slate-300 px-1">
                        <span class="text-[10px] font-black uppercase leading-tight text-center">
                            {{ \Carbon\Carbon::parse($slot['start'])->format('g:i A') }} -
                        </span>
                        <span class="text-[10px] font-black uppercase leading-tight text-center">
                            {{ \Carbon\Carbon::parse($slot['end'])->format('g:i A') }}
                        </span>
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
                        :class="activeScheduleId && activeScheduleId !== @js($schedule->id) ? 'opacity-40 saturate-50' : ''"
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
                        @click.stop="toggleSchedulePanel({
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
                        tabindex="0"
                        @keydown.enter.stop="toggleSchedulePanel({
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
                    >
                        {{-- SCHEDULE CARD CONTENT --}}
                        <div class="flex h-full w-full flex-col items-center justify-center gap-1 overflow-hidden bg-white/25 px-2 py-1.5 text-center leading-tight pointer-events-none dark:bg-black/15">
                            <div class="w-full truncate text-[10px] font-black uppercase text-current opacity-80 sm:text-[11px]">
                                {{ $subjectType }} | S{{ $schedule->section ?? 'N/A' }}
                            </div>
                            
                            <div class="w-full truncate text-[13px] font-black uppercase text-current sm:text-[14px]">
                                {{ $subjectCode }}
                            </div>

                            <div class="w-full truncate text-[10px] font-bold opacity-80">
                                EDP: {{ $this->cleanScheduleText($subject->edp_code ?? 'N/A') }}
                            </div>

                            <div class="w-full line-clamp-2 text-[11px] font-bold opacity-90">
                                {{ $subjectDescription }}
                            </div>

                            <div class="w-full truncate text-[11px] font-bold opacity-90 sm:text-[12px]">
                                {{ $startTimeDisplay }} - {{ $endTimeDisplay }}
                            </div>

                            <div class="w-full line-clamp-2 text-[10px] font-bold opacity-80">
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

    {{-- SCHEDULE DETAIL SIDE PANEL --}}
    {{-- Rendered inside the grid container; slides in from the right over the grid --}}
    <div 
        id="scheduleDetailPanel"
        x-show="panelOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        @click.outside="closePanel()"
        class="absolute top-0 right-0 bottom-0 z-[200] flex w-[17rem] max-w-[85vw] flex-col overflow-hidden rounded-l-2xl border-l-2 border-slate-200 bg-white/95 shadow-2xl shadow-slate-950/20 backdrop-blur-2xl dark:border-slate-700 dark:bg-slate-900/95"
        style="display: none;"
    >
        {{-- Panel Header --}}
        <div class="flex shrink-0 items-start justify-between gap-2 border-b border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[8px] font-black uppercase tracking-widest"
                          :class="{
                              'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300': panelData.type && panelData.type.toLowerCase().includes('major'),
                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300': panelData.type && panelData.type.toLowerCase().includes('minor'),
                              'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': !panelData.type || (!panelData.type.toLowerCase().includes('major') && !panelData.type.toLowerCase().includes('minor'))
                          }"
                          x-text="panelData.type || 'N/A'"></span>
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[8px] font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300" 
                          x-text="'S' + (panelData.section || 'N/A')"></span>
                </div>
                <div class="mt-1.5 text-sm font-black uppercase leading-tight text-slate-900 dark:text-white" x-text="panelData.code"></div>
                <div class="mt-0.5 text-[11px] font-semibold leading-snug text-slate-500 dark:text-slate-400" x-text="panelData.description"></div>
            </div>
            <button 
                @click="closePanel()"
                class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                title="Close">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        {{-- Panel Body --}}
        <div class="custom-scrollbar flex-1 overflow-y-auto p-4">
            <div class="space-y-3">

                {{-- EDP + Day grid --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 p-2.5 dark:bg-slate-950/60">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">EDP Code</span>
                        <span class="mt-1 block truncate text-[11px] font-black text-slate-900 dark:text-slate-100" x-text="panelData.edp"></span>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-2.5 dark:bg-slate-950/60">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Day</span>
                        <span class="mt-1 block text-[11px] font-black text-slate-900 dark:text-slate-100" x-text="panelData.day"></span>
                    </div>
                </div>

                {{-- Time --}}
                <div class="flex items-center gap-3 rounded-xl bg-blue-50 p-3 dark:bg-blue-950/30">
                    <svg class="h-4 w-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div>
                        <span class="block text-[8px] font-black uppercase tracking-widest text-blue-500 dark:text-blue-400">Time</span>
                        <span class="text-[12px] font-black text-blue-900 dark:text-blue-100" x-text="panelData.time"></span>
                    </div>
                </div>

                {{-- Instructor --}}
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-950/60">
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <div class="min-w-0">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Instructor</span>
                        <span class="block truncate text-[11px] font-black text-slate-800 dark:text-slate-100" x-text="panelData.instructor || 'Unassigned'"></span>
                    </div>
                </div>

                {{-- Room --}}
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-950/60">
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <div class="min-w-0">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Room</span>
                        <span class="block truncate text-[11px] font-black text-slate-800 dark:text-slate-100" x-text="panelData.room || 'No room'"></span>
                    </div>
                </div>

                {{-- Department / Major --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 p-2.5 dark:bg-slate-950/60">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Dept</span>
                        <span class="mt-1 block text-[11px] font-black text-slate-900 dark:text-slate-100" x-text="panelData.department || '—'"></span>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-2.5 dark:bg-slate-950/60">
                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Major</span>
                        <span class="mt-1 block text-[11px] font-black text-slate-900 dark:text-slate-100" x-text="panelData.major || '—'"></span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Panel Footer: Remove Button --}}
        <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/60">
            <button
                type="button"
                x-show="panelData.canDelete"
                @click="if (confirm('Remove this schedule?')) { $wire.removeAssignment(activeScheduleId); closePanel(); }"
                class="w-full rounded-xl border border-red-200 bg-red-50 py-2.5 text-[10px] font-black uppercase tracking-widest text-red-700 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-950/50">
                Remove Schedule
            </button>
            <p x-show="!panelData.canDelete" class="text-center text-[10px] font-semibold text-slate-400 dark:text-slate-600">
                Schedule cannot be removed
            </p>
        </div>
    </div>
</div>

<script>
    function scheduleGridApp() {
        return {
            activeScheduleId: null,
            activeCanDelete: false,
            panelOpen: false,
            panelData: {},

            initializeGrid() {
                // Close panel when clicking on the grid background
                document.addEventListener('click', (e) => {
                    if (!this.panelOpen) return;
                    const panel = document.getElementById('scheduleDetailPanel');
                    if (panel && !panel.contains(e.target) && !e.target.closest('.schedule-block')) {
                        this.closePanel();
                    }
                });
            },

            toggleSchedulePanel(data) {
                // If clicking the same card, toggle close
                if (this.panelOpen && this.activeScheduleId === data.scheduleId) {
                    this.closePanel();
                    return;
                }

                this.activeScheduleId = data.scheduleId || null;
                this.activeCanDelete = Boolean(data.canDelete);
                this.panelData = {
                    ...data,
                    canDelete: Boolean(data.canDelete),
                };
                this.panelOpen = true;
            },

            closePanel() {
                this.panelOpen = false;
                this.activeScheduleId = null;
                this.panelData = {};
            },
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

    /* Dim inactive cards when panel is open */
    .schedule-block {
        transition: opacity 0.15s ease, filter 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    }

    /* Active/selected card ring */
    .schedule-block.is-active {
        ring: 2px;
        ring-color: white;
        box-shadow: 0 0 0 2px white, 0 0 0 4px rgb(59 130 246 / 0.7);
        z-index: 100 !important;
    }
</style>