<div class="flex h-full overflow-hidden bg-[#F8FAFC]">
    <aside x-show="subjectsOpen" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           class="w-60 bg-white border-l border-slate-200 flex flex-col shrink-0 shadow-xl z-20">
        
        <div class="p-3 border-b border-slate-100 bg-white sticky top-0 z-30">
            <h3 class="text-[9px] font-black text-slate-400 mb-2 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-3 bg-blue-600 rounded-full"></span> Subjects
            </h3>
            
            <div class="space-y-1.5">
                <select wire:model.live="selectedDept" class="w-full text-[9px] font-bold p-1.5 rounded-lg border-slate-200 bg-slate-50 focus:ring-blue-500 uppercase">
                    <option value="">DEPARTMENTS</option>
                    <option value="CCS">CCS</option>
                    <option value="SHTM">SHTM</option>
                    <option value="CTE">CTE</option>
                    <option value="COC">COC</option>
                </select>

                <div class="flex gap-1">
                    <select wire:model.live="selectedYear" class="w-16 text-[9px] p-1.5 rounded-lg border-slate-200 font-bold bg-slate-50">
                        <option value="">YR</option>
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                        <option value="3">3rd</option>
                        <option value="4">4th</option>
                    </select>

                    <select wire:model.live="selectedMajor" class="flex-1 text-[9px] p-1.5 rounded-lg border-slate-200 font-bold bg-violet-50 text-violet-700">
                        <option value="">MAJORS</option>
                        @if($selectedDept == 'SHTM')
                            <option value="HM">HM</option>
                            <option value="TM">TM</option>
                        @elseif($selectedDept == 'COC')
                            <option value="FB">FB</option>
                            <option value="QD">QD</option>
                            <option value="LD">LD</option>
                        @endif
                    </select>
                </div>
                <input type="text" wire:model.live.debounce.300ms="searchSubject" placeholder="Search..." class="w-full text-[9px] p-1.5 rounded-lg border-slate-200 bg-slate-50">
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            @forelse($subjects as $subject)
                @php
                    $parts = explode('-', $subject->edp_code);
                    $year = $parts[2] ?? '1';
                    $dept = $parts[0] ?? 'GEN';

                    $cardClasses = match($dept) {
                        'CCS'  => 'bg-yellow-50 border-yellow-100 text-yellow-900',
                        'CTE'  => 'bg-blue-50 border-blue-100 text-blue-900',
                        'COC'  => 'bg-purple-50 border-purple-100 text-purple-900',
                        'SHTM' => 'bg-orange-50 border-orange-100 text-orange-900',
                        default => 'bg-slate-50 border-slate-200 text-slate-900',
                    };
                @endphp

                <div wire:key="subject-{{ $subject->id }}" 
                    draggable="true" 
                    @dragstart="event.dataTransfer.setData('subject_id', {{ $subject->id }})"
                    class="p-2 rounded-lg border shadow-sm transition-all hover:border-slate-400 cursor-grab active:cursor-grabbing {{ $cardClasses }}">
                    
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[8px] font-black uppercase px-1 rounded bg-white/80 border border-black/5">
                            {{ $subject->subject_code }}
                        </span>
                        <div class="flex items-center gap-1">
                            <span class="text-[6px] font-black uppercase opacity-60">
                                {{ $subject->is_major_type ? 'Mjr' : 'Mnr' }}
                            </span>
                            <div class="h-1 w-1 rounded-full {{ $subject->is_major_type ? 'bg-red-500' : 'bg-green-500' }}"></div>
                        </div>
                    </div>

                    <h5 class="text-[9px] font-black leading-tight uppercase line-clamp-2">
                        {{ $subject->description }}
                    </h5>

                    <div class="flex justify-between items-center mt-1 pt-1 border-t border-black/5 opacity-60">
                        <div class="flex flex-col">
                            <span class="text-[7px] font-bold">YR {{ $year }} | {{ $subject->units }}U</span>
                            {{-- Show remaining hours so user knows how many more times they can drag --}}
                            <span class="text-[6px] font-medium italic">
                                {{ $subject->units - ($subject->scheduled_hours ?? 0) }}h left
                            </span>
                        </div>
                        <span class="text-[7px] font-black italic">{{ $subject->edp_code }}</span>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center opacity-30 text-[8px] font-black uppercase">
                    No Results
                </div>
            @endforelse
        </div>
    </aside>

    <aside x-show="roomsOpen" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           class="w-60 bg-white border-l border-slate-200 flex flex-col shrink-0 shadow-xl z-20">
        
        <div class="p-3 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-[9px] font-black uppercase text-slate-400 tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-3 bg-purple-600 rounded-full"></span> Facilities
            </h3>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            @foreach($rooms as $room)
                <div wire:click="selectRoom({{ $room->id }})" 
                    class="group cursor-pointer p-3 border rounded-xl transition-all duration-200 
                        {{ $selectedRoomId == $room->id 
                            ? 'border-blue-500 bg-blue-50/50 shadow-sm ring-1 ring-blue-500' 
                            : 'border-slate-100 bg-white hover:border-blue-300 hover:shadow-md' }}">
                    
                    {{-- Room Header --}}
                    <div class="flex justify-between items-start mb-3">
                        <div class="min-w-0">
                            <h4 class="font-black text-slate-900 uppercase text-[10px] tracking-tight truncate leading-none">
                                {{ $room->room_name }}
                            </h4>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[7px] text-slate-500 font-bold uppercase tracking-wider">
                                    Cap: {{ $room->capacity }}
                                </span>
                                <span class="h-0.5 w-0.5 rounded-full bg-slate-300"></span>
                                <span class="text-[7px] font-black uppercase {{ $room->type == 'Lab' ? 'text-purple-600' : 'text-blue-600' }}">
                                    {{ $room->type }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Status Dot Indicator --}}
                        <div class="h-1.5 w-1.5 rounded-full {{ $room->utilization >= 90 ? 'bg-red-500 animate-ping' : ($room->utilization >= 70 ? 'bg-amber-400' : 'bg-emerald-400') }}"></div>
                    </div>

                    {{-- Utilization Progress Section --}}
                    @php
                        $load = $room->utilization;
                        $barColor = match(true) {
                            $load >= 90 => 'bg-red-600',
                            $load >= 70 => 'bg-amber-500',
                            default     => 'bg-emerald-500',
                        };
                    @endphp

                    <div class="space-y-1.5">
                        <div class="flex justify-between items-end leading-none">
                            <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Load Status</span>
                            <span class="text-[8px] font-black {{ $load >= 90 ? 'text-red-600' : 'text-slate-700' }}">
                                {{ $load }}%
                            </span>
                        </div>
                        
                        <div class="relative w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200/50">
                            <div class="{{ $barColor }} h-full transition-all duration-700 ease-out {{ $load >= 90 ? 'animate-pulse' : '' }}" 
                                style="width: {{ $load }}%">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </aside>
</div>

<script>
    function drag(ev, subjectId) {
        ev.dataTransfer.setData("subject_id", subjectId);
        ev.currentTarget.classList.add('opacity-40', 'scale-95');
    }
    document.addEventListener("dragend", function(event) {
        if(event.target.classList) {
            event.target.classList.remove('opacity-40', 'scale-95');
        }
    });
</script>