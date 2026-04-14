<div class="flex h-screen bg-[#F8FAFC] overflow-hidden" 
     x-data="{ roomsOpen: true, subjectsOpen: true, activeSubject: null }">
    
    
    <main class="flex-1 flex flex-col min-w-0 relative h-full">
        
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-20 shrink-0 shadow-sm">
            <div class="flex flex-col ml-[5%]">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">
                    Master <span class="text-blue-600">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Active Filter:</span>
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-black uppercase">
                        {{ $selectedRoomName ?? 'All Institutional Facilities' }}
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

        <div class="flex-1 flex overflow-hidden bg-slate-50">
            
            <main class="flex-1 overflow-auto relative custom-scrollbar p-6">
                <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden">
                    <table class="w-full border-collapse table-fixed">
                        <thead>
                            <tr class="bg-slate-900 text-white">
                                <th class="w-32 p-4 text-[10px] font-black uppercase tracking-widest border-r border-slate-800">Time Slot</th>
                                @foreach($days as $day)
                                    <th class="p-4 text-[10px] font-black uppercase tracking-widest">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($gridSlots as $slot)
                                <tr class="h-24">
                                    <td class="p-4 border-r border-slate-100 bg-slate-50/50 font-black text-[10px] text-slate-500 text-center leading-tight whitespace-nowrap">
                                        {{ $slot['display'] }}
                                    </td>

                                    @foreach($days as $day)
                                        <td class="p-2 border-r border-slate-100 relative group transition-colors hover:bg-blue-50/30">
                                            @php
                                                // Check if there is a schedule for this Room, Day, and Time
                                                $scheduled = $schedules->where('day', $day)
                                                                       ->where('start_time', $slot['start'])
                                                                       ->first();
                                            @endphp

                                            @if($scheduled)
                                                <div class="h-full w-full bg-blue-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-200 flex flex-col justify-between group/card relative overflow-hidden">
                                                    <div class="absolute -right-2 -top-2 w-12 h-12 bg-white/10 rounded-full blur-xl"></div>
                                                    
                                                    <div>
                                                        <p class="text-[11px] font-black leading-none uppercase tracking-tighter">
                                                            {{ $scheduled->subject->subject_code }}
                                                        </p>
                                                        <p class="text-[9px] font-medium opacity-80 mt-1 truncate">
                                                            {{ $scheduled->subject->description }}
                                                        </p>
                                                    </div>

                                                    <div class="flex items-center justify-between mt-2">
                                                        <span class="text-[8px] font-black bg-white/20 px-2 py-0.5 rounded-md uppercase">Sec {{ $scheduled->section }}</span>
                                                        <button wire:click="removeAssignment({{ $scheduled->id }})" 
                                                                wire:confirm="Remove this schedule?"
                                                                class="opacity-0 group-hover/card:opacity-100 text-white hover:text-red-200 transition-all">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <button 
                                                    {{-- This logic assumes you have a variable or way to select a subject in the sidebar --}}
                                                    wire:click="assignSubject(activeSubjectId, '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                                    class="w-full h-full rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:border-blue-400 hover:bg-white">
                                                    <span class="text-blue-500 font-black text-xl">+</span>
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </main>

            <div class="flex h-full border-l border-slate-200 bg-white" 
                 x-show="roomsOpen || subjectsOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 class="shadow-2xl">
                @include('livewire.master-grid-sidebar')
            </div>
        </div>
    </main>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>