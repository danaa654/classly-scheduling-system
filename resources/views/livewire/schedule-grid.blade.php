<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <div class="flex-1 p-2 overflow-auto bg-[#F8FAFC] custom-scrollbar">
        
        <div class="bg-white rounded-2xl border border-slate-300 shadow-lg overflow-hidden min-w-fit">
            
            <table class="w-full border-collapse table-fixed min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="p-2 border-r border-white/20 text-[9px] font-black uppercase w-24">Time</th>
                        @foreach($days as $day)
                            <th class="p-2 border-r border-white/20 text-[9px] font-black uppercase tracking-tight text-center">
                                {{ $day }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-slate-300">
                    {{-- Note: Changed to $gridSlots to match the new MasterGrid.php property --}}
                    @foreach($gridSlots as $slot)
                    <tr class="divide-x divide-slate-300">
                        {{-- Time Label Column --}}
                        <td class="p-2 text-[10px] font-black text-slate-950 bg-slate-100/80 italic text-center leading-tight">
                            {{ $slot['display'] }}
                        </td>
                        
                        @foreach($days as $day)
                            @php 
                                $booked = $schedules->where('day', $day)
                                    ->where('start_time', $slot['start'])
                                    ->where('end_time', $slot['end'])
                                    ->first();
                            @endphp

                            <td class="p-1 h-24 relative transition-all group"
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
                                :class="draggingOver ? 'bg-blue-100 ring-2 ring-inset ring-blue-400' : 'hover:bg-slate-50'">
                                
                                @if($booked)
                                    {{-- Blue Card for Booked Subjects --}}
                                    <div class="h-full w-full bg-blue-600 text-white p-2 rounded-xl shadow-sm relative group/card overflow-hidden animate-in fade-in zoom-in duration-200">
                                        <div class="flex flex-col h-full justify-between">
                                            <div>
                                                <div class="flex justify-between items-start gap-1">
                                                    <p class="text-[9px] font-black uppercase leading-none truncate">
                                                        {{ $booked->subject->subject_code }}
                                                    </p>
                                                    <span class="text-[7px] font-black bg-white/20 px-1 rounded uppercase shrink-0">
                                                        {{ $selectedRoomName ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <p class="text-[7px] font-medium opacity-95 line-clamp-2 uppercase leading-tight mt-1">
                                                    {{ $booked->subject->description }}
                                                </p>
                                            </div>
                                            <div class="flex justify-between items-end">
                                                <p class="text-[6px] font-bold uppercase tracking-tighter opacity-70">
                                                    {{ $booked->subject->edp_code }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Delete Button --}}
                                        <button wire:click="removeAssignment({{ $booked->id }})" 
                                                wire:confirm="Remove this schedule?"
                                                class="absolute top-1 right-1 bg-red-500 text-white w-4 h-4 rounded shadow-sm opacity-0 group-hover/card:opacity-100 transition-all flex items-center justify-center text-[10px] hover:bg-red-600">
                                            ✕
                                        </button>
                                    </div>
                                @else
                                    {{-- Empty Slot: Supports both Drag-and-Drop AND Sidebar Selection --}}
                                    <button 
                                        wire:click="assignSubject('{{ $activeSubjectId }}', '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                        @disabled(!$activeSubjectId)
                                        class="h-full w-full border border-dashed rounded-xl flex items-center justify-center transition-all
                                        {{ $activeSubjectId 
                                            ? 'border-blue-400 text-blue-400 bg-blue-50/50 animate-pulse cursor-pointer' 
                                            : 'border-slate-300 text-slate-300 opacity-50 cursor-default group-hover:opacity-100' }}">
                                        <span class="text-xl font-black">+</span>
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