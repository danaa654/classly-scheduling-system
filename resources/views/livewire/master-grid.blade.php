<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"
     x-data="{ roomsOpen: true, subjectsOpen: true, activeSubject: null }">
    
    <main class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">
        
        {{-- Header --}}
        <header class="h-24 bg-white dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-12 shadow-sm shrink-0 backdrop-blur-xl rounded-b-[3rem] transition-colors">
            <div class="flex flex-col ml-[5%]">
                <h2 class="text-xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">
                    Master <span class="text-blue-600 dark:text-indigo-400">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest">Active Filter:</span>
                    <span class="text-[10px] bg-blue-100 dark:bg-indigo-950/50 text-blue-700 dark:text-indigo-300 px-2 py-0.5 rounded font-black uppercase">
                        {{ $selectedRoomName ?? 'All Institutional Facilities' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Toggle Buttons --}}
                <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 mr-4 transition-colors">
                    <button @click="subjectsOpen = !subjectsOpen" 
                            :class="subjectsOpen ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="subjectsOpen ? 'bg-blue-600 dark:bg-indigo-400' : 'bg-slate-300 dark:bg-slate-600'" class="w-2 h-2 rounded-full"></span>
                        SUBJECTS
                    </button>
                    <button @click="roomsOpen = !roomsOpen" 
                            :class="roomsOpen ? 'bg-white dark:bg-slate-700 text-purple-600 dark:text-purple-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="roomsOpen ? 'bg-purple-600 dark:bg-purple-400' : 'bg-slate-300 dark:bg-slate-600'" class="w-2 h-2 rounded-full"></span>
                        ROOMS
                    </button>
                </div>

                <button class="px-6 py-3 bg-blue-600 dark:bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-indigo-500 transition-all shadow-lg shadow-blue-200 dark:shadow-indigo-900/20 active:scale-95">
                    ✨ Auto-Generate
                </button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden bg-slate-50 dark:bg-slate-950">
            
            {{-- Main Grid Area --}}
            <main class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden transition-colors">
                    <table class="w-full border-collapse table-fixed">
                        <thead>
                            <tr class="bg-slate-900 dark:bg-slate-800 text-white">
                                <th class="w-32 p-4 text-[10px] font-black uppercase tracking-widest border-r border-slate-800 dark:border-slate-700">Time Slot</th>
                                @foreach($days as $day)
                                    <th class="p-4 text-[10px] font-black uppercase tracking-widest border-r border-slate-800 dark:border-slate-700 last:border-r-0">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($gridSlots as $slot)
                                <tr class="h-24">
                                    <td class="p-4 border-r border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 font-black text-[10px] text-slate-500 dark:text-slate-400 text-center leading-tight whitespace-nowrap">
                                        {{ $slot['display'] }}
                                    </td>

                                    @foreach($days as $day)
                                        <td class="p-2 border-r border-slate-100 dark:border-slate-800 relative group transition-colors hover:bg-blue-50/30 dark:hover:bg-indigo-900/10">
                                            @php
                                                $scheduled = $schedules->where('day', $day)
                                                                       ->where('start_time', $slot['start'])
                                                                       ->first();
                                            @endphp

                                            @if($scheduled)
                                                <div class="h-full w-full bg-blue-600 dark:bg-indigo-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-200 dark:shadow-indigo-900/30 flex flex-col justify-between group/card relative overflow-hidden transition-transform hover:scale-[1.02]">
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
                                                    wire:click="assignSubject(activeSubjectId, '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                                    class="w-full h-full rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:border-blue-400 dark:hover:border-indigo-400 hover:bg-white dark:hover:bg-slate-800">
                                                    <span class="text-blue-500 dark:text-indigo-400 font-black text-xl">+</span>
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

            {{-- Sidebar Container --}}
            <div class="flex h-full border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-colors" 
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
    /* Custom Scrollbar for Dark Mode */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }

    /* Fix for table sticking in dark mode */
    thead th { position: sticky; top: 0; z-index: 10; }
</style>