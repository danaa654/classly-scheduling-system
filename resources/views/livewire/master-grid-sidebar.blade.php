{{-- Sidebar Container with Glassmorphism --}}
<div class="flex h-full bg-white/30 dark:bg-slate-900/30 border-l-2 border-slate-300 dark:border-slate-700 overflow-hidden backdrop-blur-md"
     @validateRoomSelection.window="validateAndHighlight()">

    {{-- SUBJECTS SIDEBAR --}}
    <div x-show="subjectsOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="-translate-x-full opacity-0"
         class="w-[280px] flex flex-col border-r-2 border-slate-300 dark:border-slate-700 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl overflow-hidden"
         @refreshGrid.window="$wire.$refresh()">

        {{-- HEADER --}}
        <div class="h-12 px-3 py-2 border-b-2 border-slate-300 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-blue-500/20 to-blue-600/20 dark:from-blue-900/40 dark:to-blue-800/40 backdrop-blur-sm flex-shrink-0">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-4 bg-blue-600 dark:bg-blue-400 rounded-full"></div>
                <h3 class="text-[9px] font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">Subjects</h3>
            </div>
            <span class="text-[8px] font-bold text-white bg-blue-600 dark:bg-blue-700 px-1.5 py-0.5 rounded-full">{{ count($subjects) }}</span>
        </div>

        {{-- FILTERS --}}
        <div class="px-2.5 py-2 border-b-2 border-slate-300 dark:border-slate-700 space-y-1.5 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm flex-shrink-0">
            <select 
                wire:model.live="selectedDept"
                class="w-full text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                <option value="">ALL DEPTS</option>
                <option value="CCS">CCS</option>
                <option value="CTE">CTE</option>
                <option value="COC">COC</option>
                <option value="SHTM">SHTM</option>
            </select>

            <div class="flex gap-1">
                <select 
                    wire:model.live="selectedYear" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">YEAR</option>
                    <option value="1">1st</option>
                    <option value="2">2nd</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                </select>
                <select 
                    wire:model.live="selectedMajor" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">MAJOR</option>
                </select>
            </div>

            <input 
                type="text"
                wire:model.live.debounce.300ms="searchSubject"
                placeholder="SEARCH..."
                class="w-full text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm uppercase">
        </div>

        {{-- SUBJECTS LIST --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
            @forelse($subjects as $subject)
                @php
                    $deptColorMap = [
                        'CCS' => 'bg-yellow-100/75 dark:bg-yellow-900/50 border-yellow-300 dark:border-yellow-700 text-yellow-900 dark:text-yellow-100 hover:bg-yellow-200/75 dark:hover:bg-yellow-900/70',
                        'CTE' => 'bg-blue-100/75 dark:bg-blue-900/50 border-blue-300 dark:border-blue-700 text-blue-900 dark:text-blue-100 hover:bg-blue-200/75 dark:hover:bg-blue-900/70',
                        'COC' => 'bg-purple-100/75 dark:bg-purple-900/50 border-purple-300 dark:border-purple-700 text-purple-900 dark:text-purple-100 hover:bg-purple-200/75 dark:hover:bg-purple-900/70',
                        'SHTM' => 'bg-orange-100/75 dark:bg-orange-900/50 border-orange-300 dark:border-orange-700 text-orange-900 dark:text-orange-100 hover:bg-orange-200/75 dark:hover:bg-orange-900/70',
                    ];
                    $color = $deptColorMap[$subject->department] ?? 'bg-slate-100/75 dark:bg-slate-800/50 border-slate-300 dark:border-slate-600';

                    $scheduledCount = $this->getScheduledCount($subject->id);
                    $remainingMeetings = $this->getRemainingMeetings($subject);
                    $remainingHours = $this->getRemainingHoursDecimal($subject->id);
                    $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
                    $brickCount = $this->calculateBrickCount($subject);
                    $progressPercent = ($subject->meetings_per_week > 0) ? ($scheduledCount / $subject->meetings_per_week) * 100 : 0;
                @endphp

                @if($remainingHours > 0)
                    <div 
                        draggable="true"
                        @dragstart="
                            event.dataTransfer.effectAllowed = 'copy';
                            event.dataTransfer.setData('subject_id', '{{ $subject->id }}');
                            if(!@json($selectedRoomId)) {
                                event.preventDefault();
                                $dispatch('room-first-warning');
                            }
                        "
                        @dragend="$event.target.style.opacity = '1'"
                        class="p-2 {{ $color }} border-2 rounded-lg hover:shadow-md hover:scale-[1.01] transition-all cursor-grab active:cursor-grabbing group relative backdrop-blur-sm"
                        style="@dragstart.self='this.style.opacity = 0.5'">
                        
                        {{-- SUBJECT CODE & SECTION BADGE --}}
                        <div class="flex items-start justify-between gap-1 mb-0.5">
                            <h4 class="font-black text-[10px] uppercase leading-tight">{{ $subject->subject_code }}</h4>
                            <span class="text-[7px] font-black bg-white/50 dark:bg-black/30 px-1 py-0 rounded border-2 border-current/30 uppercase flex-shrink-0">
                                S{{ $subject->section ?? 'A' }}
                            </span>
                        </div>

                        {{-- DESCRIPTION & EDP CODE --}}
                        <p class="text-[8.5px] font-medium opacity-80 mb-1 truncate leading-tight">{{ $subject->description }}</p>
                        <p class="text-[8px] font-bold opacity-70 mb-1 leading-tight">EDP: {{ $subject->edp_code }}</p>

                        {{-- PROGRESS BAR --}}
                        <div class="mb-1">
                            <div class="flex justify-between text-[7px] font-black uppercase opacity-60 mb-0.5">
                                <span>{{ $scheduledCount }}/{{ $subject->meetings_per_week }}</span>
                            </div>
                            <div class="h-1 bg-black/10 dark:bg-white/10 rounded-full overflow-hidden border-2 border-current/20">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-300"
                                     style="width: {{ $progressPercent }}%"></div>
                            </div>
                        </div>

                        {{-- COMPACT METADATA (HOURS FORMAT) --}}
                        <div class="flex items-center justify-between gap-1 text-[7px] font-bold">
                            <span class="opacity-70">{{ $subject->units }}U</span>
                            <span class="bg-white/30 dark:bg-black/30 px-1 py-0 rounded border-2 border-current/20 text-[6.5px]">
                                {{ $remainingHours }}h left
                            </span>
                            <span class="{{ $remainingMeetings > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $remainingMeetings }}/{{ $subject->meetings_per_week }}
                            </span>
                        </div>

                        {{-- TOOLTIP --}}
                        <div class="invisible group-hover:visible absolute left-full top-0 z-50 bg-slate-900 dark:bg-slate-950 text-white text-[7px] px-2 py-1 rounded-lg shadow-lg ml-1 w-40 pointer-events-none border-2 border-slate-700 dark:border-slate-600 backdrop-blur-sm">
                            <div class="font-black uppercase mb-0.5">{{ $subject->subject_code }}</div>
                            <div class="text-[6.5px] opacity-85 mb-1 leading-tight">{{ Str::limit($subject->description, 35) }}</div>
                            <div class="space-y-0.5 border-t-2 border-white/10 pt-0.5">
                                <span class="block">📍 EDP: {{ $subject->edp_code }}</span>
                                <span class="block">Total Duration: {{ $subject->duration_hours }}h</span>
                                <span class="block">Remaining: {{ $remainingHours }}h</span>
                                <span class="block">Sessions Left: {{ $remainingMeetings }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-8 opacity-40 font-black text-[8px] uppercase tracking-widest">
                    No Subjects
                </div>
            @endforelse
        </div>
    </div>

    {{-- ROOMS SIDEBAR --}}
    <div x-show="roomsOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         class="w-[260px] flex flex-col bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl overflow-hidden"
         x-data="{ noRoomSelected: !@json($selectedRoomId) }"
         @room-first-warning.window="noRoomSelected = true; setTimeout(() => noRoomSelected = false, 2000)">
        
        {{-- HEADER --}}
        <div class="h-12 px-3 py-2 border-b-2 border-slate-300 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-purple-500/20 to-purple-600/20 dark:from-purple-900/40 dark:to-purple-800/40 backdrop-blur-sm flex-shrink-0">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-4 bg-purple-600 dark:bg-purple-400 rounded-full"></div>
                <h3 class="text-[9px] font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">Rooms</h3>
            </div>
            <span class="text-[8px] font-bold text-white bg-purple-600 dark:bg-purple-700 px-1.5 py-0.5 rounded-full">{{ count($rooms) }}</span>
        </div>

        {{-- ROOMS LIST --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
            @foreach($rooms as $room)
                @php
                    $isLecture = strtoupper($room->type) === 'LECTURE';
                    $roomStyle = $isLecture 
                        ? 'bg-gradient-to-br from-emerald-100/75 to-emerald-50/75 dark:from-emerald-900/50 dark:to-emerald-800/50 border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-100 hover:bg-emerald-200/75 dark:hover:bg-emerald-900/70' 
                        : 'bg-gradient-to-br from-slate-700 to-slate-800 dark:from-slate-800 dark:to-slate-900 border-slate-600 dark:border-slate-700 text-white hover:from-slate-600 hover:to-slate-700 dark:hover:from-slate-700 dark:hover:to-slate-800';
                @endphp

                <div 
                    wire:click="selectRoom({{ $room->id }})"
                    class="p-2 {{ $roomStyle }} border-2 rounded-lg cursor-pointer hover:scale-[1.02] transition-all group backdrop-blur-sm
                            {{ $selectedRoomId == $room->id ? 'ring-4 ring-offset-2 dark:ring-offset-slate-900 ring-blue-600 shadow-lg glow-selected' : 'shadow-sm' }}"
                    :class="{ 'animate-pulse': noRoomSelected && @json($selectedRoomId) != {{ $room->id }} }">
                    
                    {{-- ROOM NAME & TYPE --}}
                    <div class="flex justify-between items-center mb-1">
                        <h4 class="font-black text-[10px] uppercase">{{ $room->room_name }}</h4>
                        <span class="text-[7px] font-black px-1 py-0 rounded border-2 border-current/30 {{ $isLecture ? 'bg-white/50 dark:bg-black/30 text-emerald-900 dark:text-emerald-100' : 'bg-slate-500 text-white' }} uppercase">
                            {{ $isLecture ? 'LEC' : 'LAB' }}
                        </span>
                    </div>

                    {{-- CAPACITY & UTILIZATION --}}
                    <div class="flex justify-between items-center text-[7px] font-bold mb-1 opacity-80">
                        <span>CAP: {{ $room->capacity }}</span>
                        <span class="font-black">{{ $room->utilization }}%</span>
                    </div>

                    {{-- UTILIZATION BAR --}}
                    <div class="h-1 w-full {{ $isLecture ? 'bg-emerald-200/50 dark:bg-emerald-900/30' : 'bg-slate-500/30' }} rounded-full overflow-hidden border-2 border-current/20">
                        <div class="h-full bg-gradient-to-r {{ $isLecture ? 'from-emerald-500 to-emerald-600' : 'from-blue-400 to-blue-500' }} rounded-full transition-all duration-300" 
                             style="width: {{ $room->utilization }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    function validateAndHighlight() {
        // Highlight rooms sidebar with pulse if no room selected
        const roomsSidebar = document.querySelector('[x-show="roomsOpen"]');
        if (roomsSidebar) {
            roomsSidebar.style.animation = 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite';
            setTimeout(() => {
                roomsSidebar.style.animation = 'none';
            }, 2000);
        }
    }
</script>

<style>
    .glow-selected {
        box-shadow: 0 0 20px rgba(37, 99, 235, 0.5);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 2px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    
    @media (prefers-color-scheme: dark) {
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748B; }
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
</style>