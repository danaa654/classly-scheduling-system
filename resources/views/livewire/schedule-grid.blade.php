<div class="w-full h-full bg-white/40 dark:bg-slate-900/30 rounded-xl border-2 border-slate-300 dark:border-slate-700 shadow-sm backdrop-blur-sm overflow-hidden flex flex-col"
     x-data="gridDragDrop()"
     @dragstart.window="handleDragStart($event)"
     @dragend.window="handleDragEnd($event)">
    
    {{-- GRID WRAPPER WITH HORIZONTAL SCROLL --}}
    <div class="overflow-x-auto overflow-y-auto custom-scrollbar flex-1">
        {{-- CSS GRID: 1 time col + 6 day cols --}}
        <div class="grid grid-cols-[6rem_1fr_1fr_1fr_1fr_1fr_1fr] gap-0 w-full min-w-max bg-white/20 dark:bg-slate-900/20">
            
            {{-- HEADER: TIME COLUMN --}}
            <div class="sticky top-0 z-30 h-14 bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 border-r-2 border-slate-600 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                <span class="text-[11px] font-black text-white uppercase tracking-tight">Time</span>
            </div>

            {{-- HEADER: DAY COLUMNS --}}
            @foreach($days as $day)
                <div class="sticky top-0 z-30 h-14 bg-gradient-to-b from-slate-900 to-slate-800 dark:from-slate-950 dark:to-slate-900 border-r-2 border-slate-600/50 dark:border-slate-700/50 flex items-center justify-center">
                    <span class="text-[11px] font-black text-white uppercase tracking-wide">{{ $day }}</span>
                </div>
            @endforeach

            {{-- GRID BODY: TIME SLOTS --}}
            @foreach($displaySlots as $slotIndex => $slot)
                {{-- TIME LABEL (LEFT COLUMN) --}}
                <div class="sticky left-0 z-20 h-[45px] bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 border-r-2 border-slate-300 dark:border-slate-700 border-b-2 border-slate-300 dark:border-slate-700 flex items-center justify-center flex-shrink-0 px-1">
                    <span class="text-[12px] font-black text-slate-900 dark:text-slate-100 uppercase tracking-tight text-center leading-tight">
                        {{ $slot['display'] }}
                    </span>
                </div>

                {{-- DAY CELLS (DROPPABLE) --}}
                @foreach($days as $dayIndex => $day)
                    @php
                        $dayMap = ['MON' => 'Monday', 'TUE' => 'Tuesday', 'WED' => 'Wednesday', 'THU' => 'Thursday', 'FRI' => 'Friday', 'SAT' => 'Saturday'];
                        $fullDay = $dayMap[$day] ?? $day;

                        // Check if this slot is during lunch break
                        $isLunchTime = in_array($slot['start'], array_column($lunchSlots, 'start'));

                        // Find all overlapping schedules
                        $overlappingSchedules = $schedules->filter(function($s) use ($fullDay, $slot) {
                            return $s->day === $fullDay && 
                                   Carbon\Carbon::parse($s->start_time) < Carbon\Carbon::parse($slot['end']) && 
                                   Carbon\Carbon::parse($s->end_time) > Carbon\Carbon::parse($slot['start']);
                        });

                        // Find schedule that STARTS at this time
                        $scheduleStarting = $overlappingSchedules->firstWhere('start_time', $slot['start']);
                    @endphp

                    {{-- EMPTY OR OCCUPIED CELL --}}
                    <div 
                        class="relative h-[45px] {{ $isLunchTime ? 'bg-amber-200/40 dark:bg-amber-900/50 border-amber-300 dark:border-amber-700' : 'bg-white/40 dark:bg-slate-800/30 hover:bg-blue-50/60 dark:hover:bg-blue-900/20' }} border-r-2 border-slate-300 dark:border-slate-700 border-b-2 border-slate-300 dark:border-slate-700 transition-colors group/slot"
                        data-day="{{ $fullDay }}"
                        data-start="{{ $slot['start'] }}"
                        @if(!$isLunchTime)
                            @dragover.prevent="
                                if($event.dataTransfer.types.includes('subject_id')) {
                                    $el.classList.add('ring-2', 'ring-blue-500', 'bg-blue-100/70', 'dark:bg-blue-900/40');
                                }
                            "
                            @dragleave.prevent="
                                $el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/70', 'dark:bg-blue-900/40');
                            "
                            @drop.prevent="
                                $el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/70', 'dark:bg-blue-900/40');
                                const subId = event.dataTransfer.getData('subject_id');
                                if(subId) { 
                                    $wire.assignSubject(subId, '{{ $fullDay }}', '{{ $slot['start'] }}'); 
                                }
                            "
                        @endif>

                        {{-- LUNCH BREAK INDICATOR --}}
                        @if($isLunchTime)
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span class="text-[9px] font-black text-amber-900 dark:text-amber-200 opacity-70 uppercase tracking-tight">🍽️ LUNCH</span>
                            </div>
                        @else
                            {{-- BACKGROUND SHADE FOR CONTINUATION ROWS --}}
                            @if($overlappingSchedules->count() > 0 && !$scheduleStarting)
                                @php
                                    $shadeSub = $overlappingSchedules->first()->subject;
                                    $shadeColorMap = [
                                        'CCS' => 'bg-yellow-500/25 dark:bg-yellow-500/30',
                                        'CTE' => 'bg-blue-500/25 dark:bg-blue-500/30',
                                        'COC' => 'bg-purple-500/25 dark:bg-purple-500/30',
                                        'SHTM' => 'bg-orange-500/25 dark:bg-orange-500/30',
                                    ];
                                    $shadeBg = $shadeColorMap[$shadeSub->department] ?? 'bg-slate-500/15';
                                @endphp
                                <div class="absolute inset-0 {{ $shadeBg }} pointer-events-none"></div>
                            @endif

                            {{-- SUBJECT CARD (STARTS HERE) --}}
                            @if($scheduleStarting)
                                @php
                                    $subject = $scheduleStarting->subject;
                                    
                                    $brickCount = ceil((
                                        Carbon\Carbon::parse($scheduleStarting->end_time)->diffInMinutes(
                                            Carbon\Carbon::parse($scheduleStarting->start_time)
                                        )
                                    ) / 30);

                                    $colorMap = [
                                        'CCS' => [
                                            'light' => 'bg-yellow-400/90 text-yellow-900 border-yellow-600 border-l-4 border-l-yellow-700',
                                            'dark' => 'dark:bg-yellow-600/85 dark:text-yellow-50 dark:border-yellow-600 dark:border-l-yellow-400'
                                        ],
                                        'CTE' => [
                                            'light' => 'bg-blue-400/90 text-blue-900 border-blue-600 border-l-4 border-l-blue-700',
                                            'dark' => 'dark:bg-blue-600/85 dark:text-blue-50 dark:border-blue-600 dark:border-l-blue-400'
                                        ],
                                        'COC' => [
                                            'light' => 'bg-purple-400/90 text-purple-900 border-purple-600 border-l-4 border-l-purple-700',
                                            'dark' => 'dark:bg-purple-600/85 dark:text-purple-50 dark:border-purple-600 dark:border-l-purple-400'
                                        ],
                                        'SHTM' => [
                                            'light' => 'bg-orange-400/90 text-orange-900 border-orange-600 border-l-4 border-l-orange-700',
                                            'dark' => 'dark:bg-orange-600/85 dark:text-orange-50 dark:border-orange-600 dark:border-l-orange-400'
                                        ],
                                    ];
                                    $colors = $colorMap[$subject->department] ?? [
                                        'light' => 'bg-slate-400/90 text-slate-900 border-slate-600 border-l-4 border-l-slate-700',
                                        'dark' => 'dark:bg-slate-600/85 dark:text-slate-50 dark:border-slate-600 dark:border-l-slate-400'
                                    ];
                                    
                                    $cardHeight = ($brickCount * 45) + ($brickCount - 1);
                                @endphp

                                <div 
                                    class="absolute inset-x-0 top-0 z-20 {{ $colors['light'] }} {{ $colors['dark'] }} border-2 rounded-r-lg shadow-md backdrop-blur-sm flex flex-col p-1 group/card transition-all hover:shadow-lg hover:z-40 overflow-hidden cursor-pointer"
                                    style="height: {{ $cardHeight }}px;"
                                    @mouseenter="
                                        showTooltip(event, {
                                            code: '{{ $subject->subject_code }}',
                                            description: '{{ addslashes($subject->description) }}',
                                            edp: '{{ $subject->edp_code }}',
                                            section: '{{ $scheduleStarting->section }}',
                                            room: '{{ $selectedRoomName }}',
                                            time: '{{ $this->formatTime12h($scheduleStarting->start_time) }}-{{ $this->formatTime12h($scheduleStarting->end_time) }}',
                                            duration: '{{ $subject->duration_hours }}h'
                                        })
                                    "
                                    @mouseleave="hideTooltip()">

                                    {{-- COMPACT CARD CONTENT --}}
                                    <div class="flex items-start justify-between gap-1 h-full min-h-0">
                                        {{-- LEFT: CODE & EDP --}}
                                        <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                                            <span class="text-[10px] font-black uppercase leading-tight truncate">
                                                {{ $subject->subject_code }}
                                            </span>
                                            <span class="text-[8px] font-bold leading-tight truncate opacity-85">
                                                {{ $subject->edp_code }}
                                            </span>
                                            <span class="text-[7px] font-semibold opacity-70 leading-tight">
                                                {{ $this->formatTime12h($scheduleStarting->start_time) }}-{{ $this->formatTime12h($scheduleStarting->end_time) }}
                                            </span>
                                        </div>

                                        {{-- RIGHT: SECTION BADGE & REMOVE BTN --}}
                                        <div class="flex flex-col items-end gap-0.5 flex-shrink-0">
                                            <span class="text-[7px] font-black bg-white/20 dark:bg-black/20 px-1 py-0 rounded border border-current/30 uppercase">
                                                S{{ $scheduleStarting->section }}
                                            </span>
                                            <button 
                                                wire:click="removeAssignment({{ $scheduleStarting->id }})" 
                                                wire:confirm="Remove this schedule?"
                                                class="opacity-0 group-hover/card:opacity-100 text-current/80 hover:text-current hover:scale-110 transition-all flex-shrink-0 text-[8px]">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

{{-- FLOATING TOOLTIP POPOVER --}}
<div id="subjectTooltip" 
     class="hidden fixed z-[300] bg-slate-900/95 dark:bg-slate-950 text-white rounded-2xl shadow-2xl border-2 border-slate-700 dark:border-slate-600 backdrop-blur-xl p-3 w-64 pointer-events-none"
     style="min-width: 280px;">
    <div class="space-y-2">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-[11px] font-black uppercase leading-tight mb-0.5" id="tooltipCode"></div>
                <div class="text-[9px] font-bold opacity-80 leading-tight" id="tooltipDescription"></div>
            </div>
        </div>
        
        <div class="border-t border-white/10 pt-2 space-y-1">
            <div class="flex justify-between text-[8px] font-semibold">
                <span class="text-slate-400">📍 EDP Code:</span>
                <span id="tooltipEdp"></span>
            </div>
            <div class="flex justify-between text-[8px] font-semibold">
                <span class="text-slate-400">📚 Section:</span>
                <span id="tooltipSection"></span>
            </div>
            <div class="flex justify-between text-[8px] font-semibold">
                <span class="text-slate-400">🏢 Room:</span>
                <span id="tooltipRoom"></span>
            </div>
            <div class="flex justify-between text-[8px] font-semibold">
                <span class="text-slate-400">⏰ Time:</span>
                <span id="tooltipTime"></span>
            </div>
            <div class="flex justify-between text-[8px] font-semibold">
                <span class="text-slate-400">📊 Duration:</span>
                <span id="tooltipDuration"></span>
            </div>
        </div>
    </div>
</div>

<script>
    function gridDragDrop() {
        return {
            handleDragStart(event) {
                // Check if room is selected
                if (!@json($selectedRoomId)) {
                    event.preventDefault();
                    // Dispatch Livewire warning
                    Livewire.dispatch('validateRoomSelection');
                }
            },
            handleDragEnd(event) {
                // Cleanup
            }
        }
    }

    function showTooltip(event, data) {
        const tooltip = document.getElementById('subjectTooltip');
        if (!tooltip) return;

        // Populate tooltip data
        document.getElementById('tooltipCode').textContent = data.code;
        document.getElementById('tooltipDescription').textContent = data.description;
        document.getElementById('tooltipEdp').textContent = data.edp;
        document.getElementById('tooltipSection').textContent = data.section;
        document.getElementById('tooltipRoom').textContent = data.room;
        document.getElementById('tooltipTime').textContent = data.time;
        document.getElementById('tooltipDuration').textContent = data.duration;

        // Position tooltip near mouse
        tooltip.style.display = 'block';
        tooltip.style.left = Math.min(event.clientX + 10, window.innerWidth - 300) + 'px';
        tooltip.style.top = Math.max(event.clientY - 80, 10) + 'px';
    }

    function hideTooltip() {
        const tooltip = document.getElementById('subjectTooltip');
        if (tooltip) tooltip.style.display = 'none';
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    
    @media (prefers-color-scheme: dark) {
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748B; }
    }
</style>