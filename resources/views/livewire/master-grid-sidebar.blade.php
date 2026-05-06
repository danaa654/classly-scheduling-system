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
            <span class="text-[8px] font-bold text-white bg-blue-600 dark:bg-blue-700 px-1.5 py-0.5 rounded-full">{{ count($subjects ?? []) }}</span>
        </div>

        {{-- FILTERS --}}
        <div class="px-2.5 py-2 border-b-2 border-slate-300 dark:border-slate-700 space-y-1.5 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm flex-shrink-0 max-h-[35%] overflow-y-auto custom-scrollbar">
            {{-- Department Filter --}}
            <select 
                wire:model.live="selectedDept"
                class="w-full text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                <option value="">ALL DEPARTMENTS</option>
                @if($hasFullAccess ?? false)
                    <option value="CCS">CCS - College of Computer Studies</option>
                    <option value="CTE">CTE - College of Teacher Education</option>
                    <option value="COC">COC - College of Criminology</option>
                    <option value="SHTM">SHTM - School of Hospitality & Tourism</option>
                @else
                    @php
                        $userDept = auth()->user()?->department ?? '';
                    @endphp
                    <option value="{{ $userDept }}">{{ $userDept }}</option>
                @endif
            </select>

            {{-- Year & Major Row --}}
            <div class="flex gap-1">
                <select 
                    wire:model.live="selectedYear" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">YEAR</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
                <select 
                    wire:model.live="selectedMajor" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">MAJOR</option>
                    @if(isset($departmentMajors) && $selectedDept && isset($departmentMajors[$selectedDept]))
                        @foreach($departmentMajors[$selectedDept] as $major)
                            <option value="{{ $major }}">{{ $major }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Section & Type Row --}}
            <div class="flex gap-1">
                <select 
                    wire:model.live="selectedSection" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">SECTION</option>
                    <option value="A">Section A</option>
                    <option value="B">Section B</option>
                    <option value="C">Section C</option>
                </select>
                <select 
                    wire:model.live="selectedType" 
                    class="flex-1 text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm">
                    <option value="">TYPE</option>
                    <option value="Major">Major</option>
                    <option value="Minor">Minor</option>
                </select>
            </div>

            {{-- Search Input --}}
            <input 
                type="text"
                wire:model.live.debounce.300ms="searchSubject"
                placeholder="SEARCH..."
                class="w-full text-[8px] px-2 py-1 rounded-md border-2 border-slate-300 dark:border-slate-700 font-bold bg-white/70 dark:bg-slate-700/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 transition-all backdrop-blur-sm uppercase">
        </div>

        {{-- SUBJECTS LIST --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
            @forelse($subjects ?? [] as $subject)
                @php
                    $deptColorMap = [
                        'CCS' => 'bg-yellow-100/75 dark:bg-yellow-900/50 border-yellow-300 dark:border-yellow-700 text-yellow-900 dark:text-yellow-100 hover:bg-yellow-200/75 dark:hover:bg-yellow-900/70',
                        'CTE' => 'bg-blue-100/75 dark:bg-blue-900/50 border-blue-300 dark:border-blue-700 text-blue-900 dark:text-blue-100 hover:bg-blue-200/75 dark:hover:bg-blue-900/70',
                        'COC' => 'bg-purple-100/75 dark:bg-purple-900/50 border-purple-300 dark:border-purple-700 text-purple-900 dark:text-purple-100 hover:bg-purple-200/75 dark:hover:bg-purple-900/70',
                        'SHTM' => 'bg-orange-100/75 dark:bg-orange-900/50 border-orange-300 dark:border-orange-700 text-orange-900 dark:text-orange-100 hover:bg-orange-200/75 dark:hover:bg-orange-900/70',
                    ];
                    $color = $deptColorMap[$subject->department ?? ''] ?? 'bg-slate-100/75 dark:bg-slate-800/50 border-slate-300 dark:border-slate-600';

                    $scheduledCount = $this->getScheduledCount($subject->id) ?? 0;
                    $remainingMeetings = $this->getRemainingMeetings($subject) ?? 0;
                    $remainingHours = $this->getRemainingHoursDecimal($subject->id) ?? 0;
                    $progressPercent = ($subject->meetings_per_week > 0) ? ($scheduledCount / $subject->meetings_per_week) * 100 : 0;
                    
                    // Determine type indicator - use 'type' column
                    $typeValue = strtolower($subject->type ?? 'major');
                    $isMinor = $typeValue === 'minor';
                    $isMajor = $typeValue === 'major' || empty($subject->type);
                    
                    $dotColor = $isMinor ? 'bg-green-500' : ($isMajor ? 'bg-red-500' : 'bg-gray-400');
                    $typeText = $isMinor ? 'MINOR' : ($isMajor ? 'MAJOR' : 'UNKNOWN');
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
                        
                        {{-- TYPE INDICATOR (Top-right with blinking dot) --}}
                        <div class="absolute top-2 right-2 flex items-center gap-1.5 bg-white/40 dark:bg-black/40 px-1.5 py-0.5 rounded-full border border-current/30 backdrop-blur-sm">
                            {{-- Blinking Dot --}}
                            <div class="w-2 h-2 rounded-full {{ $dotColor }} animate-pulse shadow-lg" 
                                 style="box-shadow: 0 0 8px {{ $isMinor ? 'rgba(34, 197, 94, 0.6)' : ($isMajor ? 'rgba(239, 68, 68, 0.6)' : 'rgba(107, 114, 128, 0.6)') }}">
                            </div>
                            {{-- Type Text --}}
                            <span class="text-[7px] font-black uppercase tracking-tight {{ $isMinor ? 'text-green-700 dark:text-green-400' : ($isMajor ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-400') }}">
                                {{ $typeText }}
                            </span>
                        </div>

                        {{-- SUBJECT CODE & SECTION BADGE --}}
                        <div class="flex items-start justify-between gap-1 mb-0.5 pr-20">
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

                        {{-- COMPACT METADATA --}}
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
                            <div class="font-black uppercase mb-1">
                                {{ $subject->subject_code }}
                                <span class="ml-1 text-[6.5px] {{ $isMinor ? 'text-green-400' : ($isMajor ? 'text-red-400' : 'text-gray-400') }}">
                                    • {{ $typeText }}
                                </span>
                            </div>
                            <div class="text-[6.5px] opacity-85 mb-1 leading-tight">{{ Str::limit($subject->description, 35) }}</div>
                            <div class="space-y-0.5 border-t-2 border-white/10 pt-0.5">
                                <span class="block">📍 EDP: {{ $subject->edp_code }}</span>
                                <span class="block">Total Duration: {{ $subject->duration_hours }}h</span>
                                <span class="block">Remaining: {{ $remainingHours }}h</span>
                                <span class="block">Sessions Left: {{ $remainingMeetings }}</span>
                                <span class="block mt-1 {{ $isMinor ? 'text-green-400' : ($isMajor ? 'text-red-400' : 'text-gray-400') }}">
                                    Type: {{ $typeText }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-8 opacity-40 font-black text-[8px] uppercase tracking-widest">
                    No Subjects Available
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
         x-data="{ noRoomSelected: !@json($selectedRoomId ?? null) }"
         @room-first-warning.window="noRoomSelected = true; setTimeout(() => noRoomSelected = false, 2000)">
        
        {{-- HEADER --}}
        <div class="h-12 px-3 py-2 border-b-2 border-slate-300 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-purple-500/20 to-purple-600/20 dark:from-purple-900/40 dark:to-purple-800/40 backdrop-blur-sm flex-shrink-0">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-4 bg-purple-600 dark:bg-purple-400 rounded-full"></div>
                <h3 class="text-[9px] font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">Rooms</h3>
            </div>
            <span class="text-[8px] font-bold text-white bg-purple-600 dark:bg-purple-700 px-1.5 py-0.5 rounded-full">{{ count($rooms ?? []) }}</span>
        </div>

        {{-- ROOMS LIST --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
            @forelse($rooms ?? [] as $room)
                @php
                    $isSelected = ($selectedRoomId ?? null) == $room->id;
                    $isLecture = strtoupper($room->type ?? '') === 'LECTURE';
                    
                    $cardClasses = $isSelected
                        ? "ring-4 ring-blue-600 scale-[1.05] shadow-2xl brightness-110 z-10 border-blue-400 bg-white/90 dark:bg-slate-700/90"
                        : ($isLecture 
                            ? "bg-emerald-100/40 dark:bg-emerald-900/20 border-emerald-300/50 hover:bg-emerald-100/60" 
                            : "bg-slate-700/40 dark:bg-slate-800/40 border-slate-600/50 hover:bg-slate-700/60");
                @endphp

                <div wire:click="selectRoom({{ $room->id }})"
                     class="relative p-3 border-2 rounded-xl cursor-pointer transition-all duration-300 backdrop-blur-md group {{ $cardClasses }}"
                     :class="{ 'animate-pulse ring-2 ring-blue-400/50': noRoomSelected }">
                    
                    {{-- Status Badge for Selected Room --}}
                    @if($isSelected)
                        <div class="absolute -top-2 -right-2 bg-blue-600 text-white text-[8px] font-black px-2 py-0.5 rounded-full shadow-lg border border-white animate-bounce">
                            ACTIVE
                        </div>
                    @endif

                    {{-- Header: Name & Type Badge --}}
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex flex-col">
                            <h4 class="font-black text-[11px] uppercase tracking-tighter {{ $isSelected ? 'text-blue-600 dark:text-blue-400' : '' }}">
                                {{ $room->room_name }}
                            </h4>
                            <span class="text-[8px] font-bold opacity-60 uppercase">{{ $room->type }}</span>
                        </div>
                        
                        <span class="text-[7px] font-black px-1.5 py-0.5 rounded-md border {{ $isLecture ? 'bg-emerald-500/20 border-emerald-500 text-emerald-700 dark:text-emerald-300' : 'bg-slate-500/20 border-slate-400 text-slate-100' }}">
                            {{ $isLecture ? 'LEC' : 'LAB' }}
                        </span>
                    </div>

                    {{-- Stats: Capacity & Progress --}}
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-end text-[8px] font-bold">
                            <div class="flex items-center gap-1 opacity-70">
                                <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>CAP: {{ $room->capacity }}</span>
                            </div>
                            <span class="{{ ($room->utilization ?? 0) > 80 ? 'text-rose-500' : '' }}">{{ $room->utilization ?? 0 }}%</span>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="h-1.5 w-full bg-black/10 dark:bg-white/10 rounded-full overflow-hidden p-[1px]">
                            <div class="h-full rounded-full transition-all duration-700 ease-out {{ $isLecture ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.5)]' }}" 
                                 style="width: {{ $room->utilization ?? 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 opacity-40 font-black text-[8px] uppercase tracking-widest">
                    No Rooms Available
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    function validateAndHighlight() {
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

    /* Enhanced blinking dot animation */
    .animate-pulse {
        animation: pulse-enhanced 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse-enhanced {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
</style>
