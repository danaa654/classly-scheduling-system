<div class="flex h-screen bg-[#F8FAFC] overflow-hidden">
    
    @include('sidebar')

    <main class="flex-1 flex flex-col min-w-0 relative" 
          x-data="{ roomsOpen: true, subjectsOpen: true }">
        
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-10 shrink-0 shadow-sm">
            <div class="flex flex-col">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Master <span class="text-blue-600">Grid</span></h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Active Filter:</span>
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-black uppercase">
                        {{ $selectedRoomName ?? ($selectedDept ? "$selectedDept $selectedYear-$selectedSection" : 'No Filter Selected') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="flex bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                    <select wire:model.live="selectedDept" class="bg-transparent border-none text-[10px] font-black uppercase focus:ring-0 cursor-pointer text-slate-600">
                        <option value="">Department</option>
                        <option value="CCS">CCS</option>
                        <option value="SHTM">SHTM</option>
                    </select>
                    <div class="w-[1px] h-4 bg-slate-300 self-center mx-1"></div>
                    <select wire:model.live="selectedYear" class="bg-transparent border-none text-[10px] font-black uppercase focus:ring-0 cursor-pointer text-slate-600">
                        <option value="">Year</option>
                        @for ($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}">{{ $i }}{{ $i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th')) }}</option>
                        @endfor
                    </select>
                    <div class="w-[1px] h-4 bg-slate-300 self-center mx-1"></div>
                    <select wire:model.live="selectedSection" class="bg-transparent border-none text-[10px] font-black uppercase focus:ring-0 cursor-pointer text-slate-600">
                        <option value="">Section</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                    </select>
                </div>

                <div class="flex bg-slate-100 rounded-xl p-1 border border-slate-200">
                    <button @click="subjectsOpen = !subjectsOpen" :class="subjectsOpen ? 'bg-white shadow-sm text-blue-600' : 'text-slate-400'" class="px-3 py-2 text-[9px] font-black uppercase rounded-lg transition-all">Subjects 📚</button>
                    <button @click="roomsOpen = !roomsOpen" :class="roomsOpen ? 'bg-white shadow-sm text-blue-600' : 'text-slate-400'" class="px-3 py-2 text-[9px] font-black uppercase rounded-lg transition-all">Rooms 🏢</button>
                </div>

                <button class="px-6 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-blue-200">
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
                                <td class="p-5 text-[10px] font-black text-slate-500 bg-slate-50/50">{{ $slot }}</td>
                                @foreach($days as $day)
                                    <td class="p-2 h-36 relative transition-all hover:bg-blue-50/30"
                                        ondragover="event.preventDefault()"
                                        @drop="
                                            const subId = event.dataTransfer.getData('subjectId');
                                            if(subId) {
                                                $wire.assignSubject(subId, '{{ $day }}', '{{ $slot }}');
                                            }
                                        ">
                                        
                                        @php 
                                            $booked = $schedules->where('day', $day)->where('time_slot', $slot)->first();
                                        @endphp

                                        @if($booked)
                                            <div class="h-full w-full bg-blue-600 text-white p-3 rounded-2xl shadow-lg relative group overflow-hidden">
                                                <div class="flex justify-between items-start">
                                                    <p class="text-[10px] font-black uppercase leading-tight">{{ $booked->subject->code }}</p>
                                                    <span class="text-[7px] font-black bg-white/20 px-1 rounded">{{ $booked->room->name ?? 'N/A' }}</span>
                                                </div>
                                                <p class="text-[8px] font-bold opacity-80 mt-1 line-clamp-2">{{ $booked->subject->name }}</p>
                                                
                                                <div class="absolute bottom-2 left-3">
                                                     <p class="text-[7px] font-black uppercase tracking-tighter opacity-60">
                                                        {{ $booked->subject->department }} | {{ $booked->subject->year_level }}-{{ $booked->subject->section }}
                                                     </p>
                                                </div>

                                                <button wire:click="removeAssignment({{ $booked->id }})" 
                                                        class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center font-bold">✕</button>
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

            <aside x-show="subjectsOpen" x-transition class="w-72 bg-white border-l border-slate-200 flex flex-col shrink-0">
                <div class="p-6 border-b border-slate-100 bg-blue-50/30">
                    <h3 class="text-[10px] font-black uppercase text-blue-600 tracking-widest">Subject List</h3>
                </div>
                    <div class="space-y-4">
                        @foreach($subjects as $subject)
                            <div draggable="true" 
                                ondragstart="event.dataTransfer.setData('subjectId', {{ $subject->id }})"
                                class="cursor-grab active:cursor-grabbing p-4 border border-slate-100 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ $subject->department }}
                                    </span>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-bold uppercase">
                                        {{ $subject->units }} Units
                                    </span>
                                </div>

                                <h5 class="font-black text-slate-800 uppercase leading-none">{{ $subject->code }}</h5>
                                
                                <p class="text-[11px] text-slate-500 font-medium mt-1 line-clamp-1">
                                    {{ $subject->description }}
                                </p>
                            </div>
                        @endforeach
                    </div>
            </aside>

            <aside x-show="roomsOpen" x-transition class="w-72 bg-white border-l border-slate-200 flex flex-col shrink-0">
                <div class="p-6 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Room Load</h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        @foreach($rooms as $room)
                            <div wire:click="selectRoom({{ $room->id }})" 
                                class="cursor-pointer p-4 mb-3 border rounded-2xl {{ $selectedRoomId == $room->id ? 'border-blue-500 bg-blue-50' : 'border-slate-100' }}">
                                
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-slate-800 uppercase">{{ $room->room_name }}</h4>
                                        <p class="text-[10px] text-slate-400 font-bold">CAP: {{ $room->capacity }}</p>
                                    </div>
                                    
                                    <span class="px-2 py-1 rounded text-[10px] font-black uppercase {{ $room->type == 'Lab' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                        {{ $room->type }}
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-[10px] font-bold text-slate-400">LOAD</span>
                                        <span class="text-[10px] font-bold text-blue-600">{{ $room->utilization }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-1.5 rounded-full">
                                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $room->utilization }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                </div>
            </aside>
        </div>
    </main>
</div>