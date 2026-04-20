<div class="h-full"> {{-- Single Root Wrapper to prevent Livewire Error --}}
    <div class="flex h-full overflow-hidden bg-[#F8FAFC] dark:bg-slate-950 transition-colors duration-500">
        
        <aside x-show="subjectsOpen" 
               class="w-72 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col shrink-0 shadow-2xl z-20 transition-colors">
            
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 z-30">
                <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 mb-3 uppercase tracking-[0.25em] flex items-center gap-2">
                    <span class="w-1.5 h-3.5 bg-blue-600 rounded-full"></span> Subject Catalog
                </h3>
                
                <div class="space-y-2">
                    <select wire:model.live="selectedDept" class="w-full text-[10px] font-black p-2.5 rounded-xl border-none bg-slate-100 dark:bg-slate-800 dark:text-slate-200 uppercase focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <option value="">All Departments</option>
                        <option value="CCS">CCS</option>
                        <option value="SHTM">SHTM</option>
                        <option value="CTE">CTE</option>
                        <option value="COC">COC</option>
                    </select>

                    <div class="flex gap-2">
                        <select wire:model.live="selectedYear" class="w-16 text-[10px] p-2.5 rounded-xl border-none font-black bg-slate-100 dark:bg-slate-800 dark:text-slate-200 uppercase">
                            <option value="">YR</option>
                            <option value="1">1st</option>
                            <option value="2">2nd</option>
                            <option value="3">3rd</option>
                            <option value="4">4th</option>
                        </select>

                        <select wire:model.live="selectedMajor" 
                                @disabled(!$selectedDept)
                                class="flex-1 text-[10px] p-2.5 rounded-xl border-none font-black bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 disabled:opacity-30 uppercase">
                            <option value="">Majors</option>
                            @if($selectedDept == 'CCS')
                                <option value="IT">BSIT</option>
                                <option value="ACT">ACT</option>
                            @elseif($selectedDept == 'SHTM')
                                <option value="HM">BSHM</option>
                                <option value="TM">BSTM</option>
                            @endif
                        </select>
                    </div>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-[10px]">🔍</span>
                        <input type="text" wire:model.live.debounce.300ms="searchSubject" placeholder="Search Code..." 
                               class="w-full pl-8 pr-3 py-2.5 rounded-xl border-none bg-slate-100 dark:bg-slate-800 dark:text-slate-200 placeholder:text-slate-400 font-bold text-[10px] uppercase">
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar max-h-[920px]">
                @forelse($subjects as $subject)
                    @php
                        $dept = explode('-', $subject->edp_code)[0] ?? 'GEN';
                        $cardTheme = match($dept) {
                            'CCS'  => 'border-amber-200 bg-amber-50/40 text-amber-900 dark:text-amber-200 dark:bg-amber-900/10 dark:border-amber-900/30',
                            'SHTM' => 'border-orange-200 bg-orange-50/40 text-orange-900 dark:text-orange-200 dark:bg-orange-900/10 dark:border-orange-900/30',
                            default => 'border-slate-200 bg-slate-50/50 text-slate-900 dark:text-slate-200 dark:bg-slate-800/50 dark:border-slate-700',
                        };
                    @endphp

                    <div wire:key="subject-{{ $subject->id }}" 
                        draggable="true" 
                        @dragstart="event.dataTransfer.setData('subject_id', {{ $subject->id }})"
                        class="min-h-[105px] p-4 rounded-[1.5rem] border-2 shadow-sm transition-all hover:border-blue-400 hover:scale-[1.02] cursor-grab active:cursor-grabbing flex flex-col justify-between {{ $cardTheme }}">
                        
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-black uppercase px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-black/5 shadow-sm">
                                {{ $subject->subject_code }}
                            </span>
                            <span class="text-[8px] font-black text-blue-600 dark:text-blue-400 italic underline decoration-blue-500/20">
                                {{ $subject->edp_code }}
                            </span>
                        </div>

                        <h5 class="text-[11px] font-black leading-tight uppercase line-clamp-2 my-2 tracking-tight">
                            {{ $subject->description }}
                        </h5>

                        <div class="flex justify-between items-center pt-2 border-t border-black/5 dark:border-white/5">
                            <div class="flex gap-2 items-center">
                                <div class="h-2 w-2 rounded-full {{ $subject->is_major_type ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]' : 'bg-emerald-500' }}"></div>
                                <span class="text-[9px] font-bold opacity-70">UNITS: {{ $subject->units }}</span>
                            </div>
                            <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter">
                                {{ $subject->units - ($subject->scheduled_hours ?? 0) }}H Remaining
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-20 text-center opacity-30 text-[10px] font-black uppercase tracking-[0.3em]">No Subjects Found</div>
                @endforelse
            </div>
        </aside>

        <aside x-show="roomsOpen" 
               class="w-72 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col shrink-0 shadow-2xl z-20 transition-colors">
            
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/50 sticky top-0 z-30">
                <h3 class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-[0.25em] flex items-center gap-2 mb-4">
                    <span class="w-1.5 h-3.5 bg-purple-600 rounded-full"></span> Available Rooms
                </h3>

                <div class="flex p-1.5 bg-slate-200/50 dark:bg-slate-800 rounded-2xl gap-1 shadow-inner">
                    <button wire:click="$set('roomType', '')" 
                            class="flex-1 text-[9px] font-black py-2 rounded-xl uppercase transition-all {{ $roomType == '' ? 'bg-white dark:bg-slate-700 shadow-md text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">
                        All
                    </button>
                    <button wire:click="$set('roomType', 'LECTURE')" 
                            class="flex-1 text-[9px] font-black py-2 rounded-xl uppercase transition-all {{ $roomType == 'LECTURE' ? 'bg-white dark:bg-slate-700 shadow-md text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">
                        Lecture
                    </button>
                    <button wire:click="$set('roomType', 'LAB')" 
                            class="flex-1 text-[9px] font-black py-2 rounded-xl uppercase transition-all {{ $roomType == 'LAB' ? 'bg-white dark:bg-slate-700 shadow-md text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">
                        Lab
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar max-h-[920px]">
                @foreach($rooms as $room)
                    <div wire:click="selectRoom({{ $room->id }})" 
                        class="group cursor-pointer min-h-[110px] p-4 border-2 rounded-[2rem] transition-all duration-300 flex flex-col justify-between
                            {{ $selectedRoomId == $room->id 
                                ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 ring-1 ring-blue-500' 
                                : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-800/50 hover:border-slate-300 dark:hover:border-slate-600 shadow-sm' }}">
                        
                        <div class="flex justify-between items-start">
                            <div class="min-w-0">
                                <h4 class="font-black text-slate-800 dark:text-slate-100 uppercase text-[12px] tracking-tight truncate leading-none">
                                    {{ $room->room_name }}
                                </h4>
                                <div class="flex items-center gap-1 mt-1.5">
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md {{ $room->type == 'LAB' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/40' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/40' }}">
                                        {{ $room->type }}
                                    </span>
                                    <span class="text-[8px] text-slate-400 font-bold uppercase tracking-widest italic">• CAP: {{ $room->capacity }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-700 px-2 py-1.5 rounded-xl border border-black/5">
                                 <div class="h-2 w-2 rounded-full {{ $room->utilization >= 90 ? 'bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.6)]' : 'bg-emerald-500' }}"></div>
                                 <span class="text-[9px] font-black text-slate-700 dark:text-slate-200">{{ $room->utilization }}%</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="relative w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden border border-black/5 shadow-inner">
                                <div class="h-full {{ $room->utilization >= 90 ? 'bg-red-500' : 'bg-blue-600 dark:bg-indigo-500' }} transition-all duration-1000 ease-out" 
                                     style="width: {{ $room->utilization }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1.5">
                                 <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Load Index</span>
                                 <span class="text-[8px] font-black text-slate-500 dark:text-indigo-400 uppercase tracking-widest">{{ 100 - $room->utilization }}% Free Slots</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>