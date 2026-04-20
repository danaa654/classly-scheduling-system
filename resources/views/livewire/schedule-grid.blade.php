<div class="flex-1 flex flex-col min-w-0 overflow-hidden transition-colors duration-300">
    <div class="flex-1 p-4 overflow-auto bg-slate-50 dark:bg-slate-950 custom-scrollbar">
        
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none overflow-hidden min-w-fit transition-colors">
            
            <table class="w-full border-collapse table-fixed min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-900 dark:bg-slate-800 text-white">
                        <th class="p-4 border-r border-slate-800 dark:border-slate-700 text-[10px] font-black uppercase tracking-[0.2em] w-28">
                            Time
                        </th>
                        @foreach($days as $day)
                            <th class="p-4 border-r border-slate-800 dark:border-slate-700 text-[10px] font-black uppercase tracking-[0.2em] text-center last:border-r-0">
                                {{ $day }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($gridSlots as $slot)
                    <tr class="divide-x divide-slate-100 dark:divide-slate-800">
                        {{-- Time Label Column --}}
                        <td class="p-3 text-[10px] font-black text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/30 italic text-center leading-tight">
                            {{ $slot['display'] }}
                        </td>
                        
                        @foreach($days as $day)
                            @php 
                                $booked = $schedules->where('day', $day)
                                    ->where('start_time', $slot['start'])
                                    ->where('end_time', $slot['end'])
                                    ->first();
                            @endphp

                            <td class="p-2 h-28 relative transition-all group"
                                x-data="{ draggingOver: false }"
                                @dragover.prevent="draggingOver = true"
                                @dragleave.prevent="draggingOver = false"
                                @drop.prevent="
                                    draggingOver = false;
                                    const subId = event.dataTransfer.getData('subject_id');
                                    if(subId) { 
                                        $wire.assignSubject(subId, '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}'); 
                                    }
                                "
                                :class="draggingOver ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-inset ring-indigo-400 dark:ring-indigo-500' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/20'">
                                
                                @if($booked)
                                    {{-- Scheduled Card --}}
                                    <div class="h-full w-full bg-blue-600 dark:bg-indigo-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-200 dark:shadow-indigo-900/30 relative group/card overflow-hidden transition-all hover:scale-[1.02] active:scale-95">
                                        {{-- Visual Flair --}}
                                        <div class="absolute -right-2 -top-2 w-12 h-12 bg-white/10 rounded-full blur-xl"></div>
                                        
                                        <div class="flex flex-col h-full justify-between relative z-10">
                                            <div>
                                                <div class="flex justify-between items-start gap-1">
                                                    <p class="text-[10px] font-black uppercase leading-none truncate tracking-tighter">
                                                        {{ $booked->subject->subject_code }}
                                                    </p>
                                                    <span class="text-[7px] font-black bg-white/20 px-1.5 py-0.5 rounded uppercase shrink-0 backdrop-blur-sm border border-white/10">
                                                        {{ $selectedRoomName ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <p class="text-[8px] font-medium opacity-90 line-clamp-2 uppercase leading-tight mt-1.5 tracking-tight">
                                                    {{ $booked->subject->description }}
                                                </p>
                                            </div>
                                            
                                            <div class="flex justify-between items-center mt-auto pt-2 border-t border-white/10">
                                                <p class="text-[7px] font-black uppercase tracking-widest opacity-70">
                                                    {{ $booked->subject->edp_code }}
                                                </p>
                                                <span class="text-[7px] font-bold bg-black/20 px-1 rounded">SEC {{ $booked->section ?? 'A' }}</span>
                                            </div>
                                        </div>

                                        {{-- Delete Button --}}
                                        <button wire:click="removeAssignment({{ $booked->id }})" 
                                                wire:confirm="Remove this schedule?"
                                                class="absolute top-1.5 right-1.5 bg-red-500/90 hover:bg-red-600 text-white w-5 h-5 rounded-lg shadow-lg opacity-0 group-hover/card:opacity-100 transition-all flex items-center justify-center text-[10px] backdrop-blur-sm">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                @else
                                    {{-- Drop Zone / Empty Slot --}}
                                    <button 
                                        wire:click="assignSubject('{{ $activeSubjectId }}', '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                        @disabled(!$activeSubjectId)
                                        class="h-full w-full border-2 border-dashed rounded-[1.25rem] flex items-center justify-center transition-all duration-300
                                        {{ $activeSubjectId 
                                            ? 'border-indigo-400 text-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10 animate-pulse cursor-pointer shadow-inner' 
                                            : 'border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-700 opacity-40 cursor-default group-hover:opacity-100 group-hover:border-slate-400' }}">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-2xl font-black">+</span>
                                            @if($activeSubjectId)
                                                <span class="text-[7px] font-black uppercase tracking-[0.2em]">Drop Here</span>
                                            @endif
                                        </div>
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>