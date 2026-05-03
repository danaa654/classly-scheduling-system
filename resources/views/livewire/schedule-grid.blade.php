<div 
    x-data="scheduleGridApp()" 
    x-init="initializeGrid()" 
    class="relative w-full h-full bg-white/40 dark:bg-slate-900/30 backdrop-blur-sm rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden shadow-sm"
>
    {{-- SCROLLABLE GRID CONTAINER --}}
    <div class="overflow-auto custom-scrollbar relative w-full h-full">
        
        {{-- MAIN GRID STRUCTURE --}}
        <div class="relative inline-block w-full min-h-full">
            
            {{-- BASE GRID: TIME SLOTS + DAYS --}}
            <div class="grid gap-0 auto-rows-[45px]" style="grid-template-columns: 6rem repeat(6, 1fr);">
                
                {{-- HEADER: TIME LABEL --}}
                <div class="sticky top-0 left-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[11px] font-black uppercase border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                    Time
                </div>

                {{-- HEADER: DAY LABELS --}}
                @foreach(['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $dayLabel)
                    <div class="sticky top-0 z-30 h-14 flex items-center justify-center bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 text-white text-[11px] font-black uppercase border-r-2 border-b-2 border-slate-700 dark:border-slate-600">
                        {{ $dayLabel }}
                    </div>
                @endforeach

                {{-- TIME SLOTS & DAY CELLS --}}
                @foreach($displaySlots as $slotIndex => $slot)
                    {{-- TIME LABEL COLUMN --}}
                    <div class="sticky left-0 z-20 h-[45px] flex items-center justify-center text-[11px] font-black uppercase border-r-2 border-b border-slate-300 dark:border-slate-700 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 text-slate-700 dark:text-slate-300 px-1">
                        {{ $slot['display'] }}
                    </div>

                    {{-- DAY CELLS (6 columns) --}}
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $dayIndex => $dayFull)
                        @php
                            $isLunch = $this->isLunchBreakTime($slot['start'], $slot['end']);
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
                                        showSuccessNotification('{{ substr($dayFull, 0, 3) }}', '{{ $slot['display'] }}', subCode);
                                    }
                                "
                            @endif
                        >
                            @if($isLunch)
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="text-[9px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-tight">LUNCH</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

           {{-- ABSOLUTE POSITIONED SCHEDULE BLOCKS LAYER --}}
<div class="absolute inset-0 pointer-events-none" style="top: 3.5rem;">
    @foreach($schedules as $schedule)
        @php
            $subject = $schedule->subject;
            if (!$subject) continue;

            // 1. Parse times precisely
            $startTime = \Carbon\Carbon::parse($schedule->start_time);
            $endTime = \Carbon\Carbon::parse($schedule->end_time);
            $gridStart = \Carbon\Carbon::parse('07:00:00');
            
            // 2. Calculate raw slot position
            $minutesFromStart = $gridStart->diffInMinutes($startTime);
            $rawSlotIndex = $minutesFromStart / 30;

            /**
             * FIX: ACCOUNT FOR THE 12:00 PM - 1:00 PM GAP
             * If the schedule starts at or after 1:00 PM (13:00), 
             * we must subtract 2 slots (60 minutes) from the index 
             * because the visual grid skips the 12:00-1:00 hour.
             */
            $slotIndex = $rawSlotIndex;
            if ($startTime->hour >= 13) {
                $slotIndex -= 2; 
            }
            
            // 3. Calculate height
            $durationMinutes = $startTime->diffInMinutes($endTime);
            $slotsSpanned = $durationMinutes / 30;
            $heightPx = ($slotsSpanned * 45) - 4;

            // 4. Day Index
            $dayFull = $schedule->day;
            $dayIndex = array_search($dayFull, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
            
            // 5. Column positioning
            $oneDayColumn = "((100% - 6rem) / 6)";
            
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
            
            // 6. Colors
            $colorMap = [
                'CCS' => ['light' => 'bg-yellow-400/85 text-yellow-950', 'dark' => 'dark:bg-yellow-600/80 dark:text-yellow-50', 'border' => 'border-l-yellow-600'],
                'CTE' => ['light' => 'bg-blue-400/85 text-blue-950', 'dark' => 'dark:bg-blue-600/80 dark:text-blue-50', 'border' => 'border-l-blue-600'],
                'COC' => ['light' => 'bg-purple-400/85 text-purple-950', 'dark' => 'dark:bg-purple-600/80 dark:text-purple-50', 'border' => 'border-l-purple-600'],
                'SHTM' => ['light' => 'bg-orange-400/85 text-orange-950', 'dark' => 'dark:bg-orange-600/80 dark:text-orange-50', 'border' => 'border-l-orange-600'],
            ];
            
            $colors = $colorMap[$subject->department] ?? [
                'light' => 'bg-slate-400/85 text-slate-950', 'dark' => 'dark:bg-slate-600/80 dark:text-slate-50', 'border' => 'border-l-slate-600'
            ];

            $instructor = $subject->faculty?->name ?? 'N/A';
            $startTimeDisplay = $startTime->format('g:i A');
            $endTimeDisplay = $endTime->format('g:i A');
        @endphp

        <div 
            class="absolute pointer-events-auto z-20 rounded-r-lg border-2 border-r-slate-400 border-t-slate-300 border-b-slate-300 shadow-lg backdrop-blur-sm transition-all hover:shadow-2xl hover:z-40 group/card overflow-hidden flex flex-col items-center justify-center p-1.5 cursor-pointer 
                   {{ $colors['light'] }} {{ $colors['dark'] }} {{ $colors['border'] }} border-l-4"
            style="
                /* Vertical: Use the adjusted slotIndex */
                top: calc({{ $slotIndex }} * 45px);
                
                /* Horizontal Positioning */
                left: calc(6rem + ({{ $dayIndex }} * {!! $oneDayColumn !!}) + ({!! $oneDayColumn !!} * {{ $offsetFactor }}));
                
                /* Width */
                width: calc({!! $oneDayColumn !!} * {{ $widthFactor }});
                
                /* Height */
                height: {{ $heightPx }}px;
                
                z-index: {{ 20 + $myPosition }};
            "
            wire:key="schedule-{{ $schedule->id }}"
            @mouseenter="showSchedulePopover($event, {
                code: '{{ $subject->subject_code }}',
                description: '{{ addslashes($subject->description ?? '') }}',
                edp: '{{ $schedule->edp_code ?? 'N/A' }}',
                section: '{{ $schedule->section ?? 'N/A' }}',
                instructor: '{{ addslashes($instructor) }}',
                time: '{{ $startTimeDisplay }} - {{ $endTimeDisplay }}',
                day: '{{ $dayFull }}'
            })"
            @mouseleave="hideSchedulePopover()"
        >
            <div class="text-center w-full space-y-0.5 pointer-events-none">
                <div class="text-[10px] sm:text-[12px] font-black uppercase leading-tight line-clamp-2">
                    {{ $subject->subject_code }}
                </div>
                <div class="text-[8px] font-semibold leading-tight opacity-90">
                    {{ $startTimeDisplay }}<br>{{ $endTimeDisplay }}
                </div>
                <div class="text-[7px] font-bold leading-tight opacity-75 truncate">
                    {{ strtoupper($selectedRoomType ?? 'LEC') }}
                </div>
            </div>

            {{-- OVERLAP BADGE --}}
            @if($totalOverlaps > 1)
                <div class="absolute top-0.5 right-0.5 text-[6px] font-black bg-red-500/90 text-white px-1 py-0.5 rounded-full border border-red-600 w-4 h-4 flex items-center justify-center">
                    {{ $totalOverlaps }}
                </div>
            @endif

            {{-- SECTION BADGE --}}
            <div class="absolute top-0.5 left-0.5 text-[6px] font-black bg-white/30 dark:bg-black/30 px-1 py-0.5 rounded border border-current/40 uppercase">
                S{{ $schedule->section ?? 'N/A' }}
            </div>

            {{-- QUICK DELETE (HOVER) --}}
            <button 
                wire:click.stop="removeAssignment({{ $schedule->id }})"
                wire:confirm="Remove this schedule?"
                class="absolute bottom-0.5 right-0.5 opacity-0 group-hover/card:opacity-100 bg-red-500/80 hover:bg-red-600 text-white rounded p-0.5 transition-all shadow-sm"
            >
                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
    @endforeach
</div>

            {{-- CURRENT TIME INDICATOR (RED LINE) --}}
            @php
                $now = \Carbon\Carbon::now();
                $gridStart = \Carbon\Carbon::parse('07:00:00');
                $gridEnd = \Carbon\Carbon::parse('18:00:00');
                
                if ($now >= $gridStart && $now < $gridEnd) {
                    $elapsedMinutes = $gridStart->diffInMinutes($now);
                    $topPercent = ($elapsedMinutes / 660) * 100;
                } else {
                    $topPercent = -1;
                }
            @endphp

            @if($topPercent >= 0)
                <div class="absolute left-0 right-0 pointer-events-none z-15" style="top: calc(3.5rem + {{ $topPercent }}% * (100vh - 3.5rem));">
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

{{-- COMPACT SCHEDULE DETAIL POPOVER --}}
<div 
    id="schedulePopover" 
    class="hidden fixed z-[9999] w-72 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 p-3 backdrop-blur-sm pointer-events-auto"
    style="max-width: calc(100vw - 16px);"
>
    <div class="space-y-2">
        {{-- HEADER --}}
        <div class="flex items-start justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-700">
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-black uppercase text-slate-900 dark:text-white line-clamp-1" id="popCode"></div>
                <div class="text-[9px] font-semibold text-slate-600 dark:text-slate-300 mt-0.5 line-clamp-1" id="popDesc"></div>
            </div>
            <span class="text-[7px] font-bold px-2 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 whitespace-nowrap flex-shrink-0" id="popType"></span>
        </div>

        {{-- INFO GRID (COMPACT) --}}
        <div class="grid grid-cols-2 gap-2 text-[9px]">
            <div>
                <span class="text-slate-500 dark:text-slate-400 font-bold block text-[7px] mb-0.5">EDP</span>
                <span class="font-black text-slate-900 dark:text-slate-100 block truncate text-[8px]" id="popEdp">—</span>
            </div>
            <div>
                <span class="text-slate-500 dark:text-slate-400 font-bold block text-[7px] mb-0.5">Section</span>
                <span class="font-black text-slate-900 dark:text-slate-100 block text-[8px]" id="popSection">—</span>
            </div>
        </div>

        {{-- SCHEDULE INFO (COMPACT) --}}
        <div class="bg-slate-50 dark:bg-slate-900/50 rounded p-2 space-y-1 text-[8px]">
            <div class="flex justify-between font-bold">
                <span class="text-slate-600 dark:text-slate-400">Time:</span>
                <span class="text-slate-900 dark:text-slate-100" id="popTime">—</span>
            </div>
            <div class="flex justify-between font-bold">
                <span class="text-slate-600 dark:text-slate-400">Day:</span>
                <span class="text-slate-900 dark:text-slate-100" id="popDay">—</span>
            </div>
            <div class="flex justify-between font-bold">
                <span class="text-slate-600 dark:text-slate-400">Instructor:</span>
                <span class="text-slate-900 dark:text-slate-100 truncate ml-1" id="popInstructor">—</span>
            </div>
        </div>

        {{-- OVERLAP WARNING --}}
        <div id="popOverlapWarning" class="hidden bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-700/50 rounded p-1.5 text-[8px] text-amber-800 dark:text-amber-200">
            <span class="font-bold">⚠️ Overlap: </span><span id="popOverlapText"></span>
        </div>
    </div>
</div>

{{-- SUCCESS NOTIFICATION --}}
<div 
    id="successNotification"
    class="hidden fixed bottom-4 right-4 z-50 bg-green-500 text-white px-3 py-2 rounded-lg shadow-lg font-semibold text-xs animate-pulse"
></div>

<script>
    function scheduleGridApp() {
        return {
            initializeGrid() {
                console.log('Schedule grid initialized');
            },

            showSchedulePopover(event, data) {
                const popover = document.getElementById('schedulePopover');
                if (!popover) return;

                // Populate popover fields
                document.getElementById('popCode').textContent = data.code;
                document.getElementById('popDesc').textContent = data.description || 'No description';
                document.getElementById('popType').textContent = data.type;
                document.getElementById('popEdp').textContent = data.edp;
                document.getElementById('popSection').textContent = `S${data.section}`;
                document.getElementById('popInstructor').textContent = data.instructor;
                document.getElementById('popTime').textContent = data.time;
                document.getElementById('popDay').textContent = data.day;

                // Show overlap warning if applicable
                const overlapWarning = document.getElementById('popOverlapWarning');
                if (data.totalOverlaps > 1) {
                    document.getElementById('popOverlapText').textContent = 
                        `${data.totalOverlaps} classes overlap (Slot ${data.position + 1}/${data.totalOverlaps})`;
                    overlapWarning.classList.remove('hidden');
                } else {
                    overlapWarning.classList.add('hidden');
                }

                // Position popover relative to viewport
                let x = event.clientX + 10;
                let y = event.clientY - 120;

                // Prevent popover from going off-screen (right edge)
                const popoverWidth = 288; // w-72 = 288px
                if (x + popoverWidth > window.innerWidth) {
                    x = window.innerWidth - popoverWidth - 10;
                }

                // Prevent popover from going off-screen (left edge)
                if (x < 10) {
                    x = 10;
                }

                // Prevent popover from going off-screen (top edge)
                if (y < 10) {
                    y = event.clientY + 10;
                }

                // Prevent popover from going off-screen (bottom edge)
                const popoverHeight = 280;
                if (y + popoverHeight > window.innerHeight) {
                    y = window.innerHeight - popoverHeight - 10;
                }

                popover.style.display = 'block';
                popover.style.left = x + 'px';
                popover.style.top = y + 'px';
            },

            hideSchedulePopover() {
                const popover = document.getElementById('schedulePopover');
                if (popover) {
                    popover.style.display = 'none';
                }
            }
        };
    }

    function showSuccessNotification(day, time, code) {
        const notif = document.getElementById('successNotification');
        if (!notif) return;
        
        notif.textContent = `✓ ${code} → ${day} ${time}`;
        notif.classList.remove('hidden');
        
        setTimeout(() => {
            notif.classList.add('hidden');
        }, 2500);
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