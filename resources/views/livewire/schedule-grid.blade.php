<div class="overflow-auto custom-scrollbar h-full">
    <table class="w-full border-collapse text-[10px] font-semibold text-slate-700 dark:text-slate-300">
        <thead class="bg-slate-800 text-white">
            <tr>
                <th class="p-2 border border-slate-700">Time</th>
                @foreach($days as $day)
                    <th class="p-2 border border-slate-700 text-center">{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($gridSlots as $slot)
            <tr>
                <td class="p-2 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-center">
                    {{ $slot['display'] }}
                </td>
                @foreach($days as $day)
                @php 
                    $booked = $schedules->where('day', $day)
                        ->where('start_time', $slot['start'])
                        ->where('end_time', $slot['end'])
                        ->first();
                @endphp
                <td class="h-16 border border-slate-200 dark:border-slate-700 text-center align-middle transition-colors hover:bg-blue-50 dark:hover:bg-slate-800">
                    @if($booked)
                        <div class="p-2 text-white bg-blue-600 dark:bg-indigo-600 rounded-xl text-[9px] font-bold shadow-sm">
                            <div>{{ $booked->subject->subject_code }}</div>
                            <div class="text-[8px] opacity-80">{{ $booked->subject->description }}</div>
                            <div class="mt-1 text-[7px]">Sec {{ $booked->section }}</div>
                        </div>
                    @else
                        <button wire:click="assignSubject('{{ $activeSubjectId }}', '{{ $day }}', '{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                                class="text-blue-400 text-lg font-bold opacity-40 hover:opacity-100 transition-colors">+</button>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
</style>
