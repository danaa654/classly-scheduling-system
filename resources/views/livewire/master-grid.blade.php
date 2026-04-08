<div class="flex h-screen bg-[#F8FAFC] overflow-hidden" x-data="{ roomsOpen: true, subjectsOpen: true }">
    
    @include('sidebar')

    <main class="flex-1 flex flex-col min-w-0 relative">
        
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-10 shrink-0 shadow-sm">
            <div class="flex flex-col ml-[10%]">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">
                    Master <span class="text-blue-600">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Active Filter:</span>
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-black uppercase">
                        {{ $selectedRoomName ?? ($selectedDept ? $selectedDept . " " . ($selectedYear ?? '') . "-" . ($selectedMajor ?? '') : 'All Institutional Facilities') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 mr-4">
                    <button @click="subjectsOpen = !subjectsOpen" 
                            :class="subjectsOpen ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="subjectsOpen ? 'bg-blue-600' : 'bg-slate-300'" class="w-2 h-2 rounded-full"></span>
                        SUBJECTS
                    </button>
                    <button @click="roomsOpen = !roomsOpen" 
                            :class="roomsOpen ? 'bg-white text-purple-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="roomsOpen ? 'bg-purple-600' : 'bg-slate-300'" class="w-2 h-2 rounded-full"></span>
                        ROOMS
                    </button>
                </div>

                <button class="px-6 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-blue-200 active:scale-95">
                    ✨ Auto-Generate
                </button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <div class="flex-1 p-6 overflow-auto bg-[#F8FAFC] custom-scrollbar">
                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                    <table class="w-full border-collapse table-fixed min-w-[1000px]">
                        <thead>
                            <tr class="bg-slate-900 text-white">
                                <th class="p-5 border-r border-white/10 text-[10px] font-black uppercase w-36">Time</th>
                                @foreach($days as $day)
                                    <th class="p-5 border-r border-white/10 text-[10px] font-black uppercase tracking-widest">{{ strtoupper($day) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($timeSlots as $slot)
                            <tr class="divide-x divide-slate-200">
                                <td class="p-5 text-[10px] font-black text-slate-500 bg-slate-50/50 italic">{{ $slot }}</td>
                                @foreach($days as $day)
                                    <td class="p-2 h-36 relative transition-all hover:bg-blue-50/20"
                                        ondragover="event.preventDefault()"
                                        @drop="
                                            const subId = event.dataTransfer.getData('subjectId');
                                            if(subId) { $wire.assignSubject(subId, '{{ $day }}', '{{ $slot }}'); }
                                        ">
                                        
                                        @php 
                                            $booked = $schedules->where('day', $day)->where('time_slot', $slot)->first();
                                        @endphp

                                        @if($booked)
                                            <div class="h-full w-full bg-blue-600 text-white p-3 rounded-2xl shadow-lg relative group overflow-hidden">
                                                <div class="flex justify-between items-start">
                                                    <p class="text-[10px] font-black uppercase leading-tight">{{ $booked->subject->subject_code }}</p>
                                                    <span class="text-[7px] font-black bg-white/20 px-1 rounded uppercase">ROOM {{ $booked->room->room_name ?? 'N/A' }}</span>
                                                </div>
                                                <p class="text-[8px] font-bold opacity-90 mt-1 line-clamp-2 uppercase">{{ $booked->subject->description }}</p>
                                                
                                                <div class="absolute bottom-2 left-3">
                                                     <p class="text-[7px] font-black uppercase tracking-tighter opacity-70">
                                                        {{ $booked->subject->department }} | YR{{ $booked->subject->year_level }}
                                                     </p>
                                                </div>

                                                <button wire:click="removeAssignment({{ $booked->id }})" 
                                                        class="absolute top-1 right-1 bg-white/20 hover:bg-red-500 text-white w-5 h-5 rounded-lg opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center font-bold backdrop-blur-sm">✕</button>
                                            </div>
                                        @else
                                            <div class="h-full w-full border-2 border-dashed border-slate-100 rounded-2xl flex items-center justify-center text-slate-200 text-2xl font-black group-hover:border-blue-200 group-hover:text-blue-200 transition-colors">
                                                +
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <aside x-show="subjectsOpen" 
                   x-transition:enter="transition ease-out duration-300"
                   x-transition:enter-start="translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition ease-in duration-200"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="translate-x-full"
                   class="w-80 bg-white border-l border-slate-200 flex flex-col shrink-0 shadow-2xl">
                
                <div class="p-4 border-b border-slate-100 bg-white sticky top-0 z-10">
                    <h3 class="text-[10px] font-black text-slate-400 mb-3 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-4 bg-blue-600 rounded-full"></span> Subject Repository
                    </h3>
                    <div class="space-y-2">
                        <select wire:model.live="selectedDept" class="w-full text-[11px] font-bold p-2.5 rounded-xl border-slate-200 bg-slate-50 focus:ring-blue-500">
                            <option value="">ALL DEPARTMENTS</option>
                            <option value="CCS">CCS</option>
                            <option value="SHTM">SHTM</option>
                            <option value="CTE">CTE</option>
                            <option value="COC">COC</option>
                        </select>

                        <div class="flex gap-1">
                            <select wire:model.live="selectedYear" class="w-24 text-[10px] p-2.5 rounded-xl border-slate-200 font-bold bg-slate-50">
                                <option value="">YEAR</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>

                            <select wire:model.live="selectedMajor" class="flex-1 text-[10px] p-2.5 rounded-xl border-slate-200 font-bold bg-violet-50 text-violet-700 focus:ring-violet-500">
                                <option value="">ALL MAJORS</option>
                                @if($selectedDept == 'SHTM')
                                    <option value="HM">HM (Hospitality)</option>
                                    <option value="TM">TM (Tourism)</option>
                                @elseif($selectedDept == 'COC')
                                    <option value="FB">FB (Forensics Biology)</option>
                                    <option value="QD">QD (Quality Detection)</option>
                                    <option value="LD">LD (Lie Detection)</option>
                                @endif
                            </select>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="searchSubject" placeholder="Search Code or Title..." class="w-full text-[11px] p-2.5 rounded-xl border-slate-200 bg-slate-50">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                    @forelse($subjects as $subject)
                        @php
                            // Parse EDP Code segments
                            $parts = explode('-', $subject->edp_code);
                            $dept = $parts[0] ?? 'GEN';
                            $year = $parts[2] ?? '1';

                            $cardClasses = match($dept) {
                                'CCS'  => 'bg-yellow-50 border-yellow-200 text-yellow-900',
                                'CTE'  => 'bg-blue-50 border-blue-200 text-blue-900',
                                'COC'  => 'bg-purple-50 border-purple-200 text-purple-900',
                                'SHTM' => 'bg-orange-50 border-orange-200 text-orange-900',
                                default => 'bg-slate-50 border-slate-200 text-slate-900',
                            };
                        @endphp

                        <div wire:key="subject-{{ $subject->id }}" 
                            draggable="true" 
                            ondragstart="event.dataTransfer.setData('subjectId', {{ $subject->id }})"
                            class="p-4 rounded-2xl border shadow-sm mb-3 transition-all hover:scale-[1.01] cursor-grab active:cursor-grabbing {{ $cardClasses }}">
                            
                            <div class="mb-2">
                                <span class="text-[11px] font-black uppercase px-2 py-1 rounded-lg bg-white/70 border border-black/5">
                                    {{ $subject->subject_code }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-[8px] font-black uppercase tracking-tighter opacity-60">
                                    {{ $subject->is_major_type ? 'Major' : 'Minor' }}
                                </span>
                                <div class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 
                                        {{ $subject->is_major_type ? 'bg-red-400' : 'bg-green-400' }}"></span>
                                    
                                    <span class="relative inline-flex rounded-full h-2 w-2 
                                        {{ $subject->is_major_type ? 'bg-red-600' : 'bg-green-500' }}"></span>
                                </div>
                            </div>

                            <h5 class="text-[12px] font-black leading-tight uppercase mb-3">
                                {{ $subject->description }}
                            </h5>

                            <div class="flex justify-between items-center pt-2 border-t border-black/5">
                                <span class="text-[9px] font-bold opacity-60">
                                    YR {{ $year }} | {{ $subject->units }} UNITS
                                </span>
                                <span class="text-[9px] font-black opacity-30 italic">
                                    {{ $subject->edp_code }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center opacity-30 text-[10px] font-black uppercase tracking-widest">
                            No Subjects Loaded
                        </div>
                    @endforelse
                </div>
            </aside>

            <aside x-show="roomsOpen" 
                   x-transition:enter="transition ease-out duration-300"
                   x-transition:enter-start="translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition ease-in duration-200"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="translate-x-full"
                   class="w-80 bg-white border-l border-slate-200 flex flex-col shrink-0 shadow-2xl">
                
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-widest flex items-center gap-2">
                        <span class="w-2 h-4 bg-purple-600 rounded-full"></span> Facility Utilization
                    </h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                    @foreach($rooms as $room)
                        <div wire:click="selectRoom({{ $room->id }})" 
                            class="cursor-pointer p-4 border rounded-[1.5rem] transition-all duration-300 group
                                {{ $selectedRoomId == $room->id 
                                    ? 'border-blue-500 bg-blue-50 ring-4 ring-blue-100 shadow-lg -translate-y-1' 
                                    : 'border-slate-100 bg-white hover:border-violet-300 hover:bg-violet-50/50' }}">
                            
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-black text-slate-800 uppercase text-[12px] group-hover:text-violet-700 tracking-tight">{{ $room->room_name }}</h4>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase">Capacity: {{ $room->capacity }} pax</p>
                                </div>
                                
                                <span class="px-2 py-1 rounded-lg text-[8px] font-black uppercase {{ $room->type == 'Lab' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                    {{ $room->type }}
                                </span>
                            </div>

                            @php
                                $load = $room->utilization;
                                $colorClass = $load > 85 ? 'bg-red-500' : ($load > 50 ? 'bg-yellow-500' : 'bg-green-500');
                                $textClass = $load > 85 ? 'text-red-600' : ($load > 50 ? 'text-yellow-600' : 'text-green-600');
                            @endphp

                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black text-slate-400 uppercase">Load Status</span>
                                    <span class="text-[10px] font-black {{ $textClass }}">{{ $load }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden p-[2px]">
                                    <div class="{{ $colorClass }} h-full rounded-full transition-all duration-1000 shadow-sm" 
                                         style="width: {{ $load }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </main>
</div>