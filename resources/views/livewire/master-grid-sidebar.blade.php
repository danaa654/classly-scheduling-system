<div class="h-full flex bg-gray-50 dark:bg-slate-900 text-[11px]">
    {{-- SUBJECTS SIDEBAR --}}
    <aside x-show="subjectsOpen" class="w-64 flex flex-col border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 sticky top-0">
            <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-blue-600 rounded-full"></span> Subjects
            </h3>

            <div class="space-y-1">
                <select wire:model.live="selectedDept" class="w-full text-[10px] px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 uppercase font-semibold">
                    <option value="">All Departments</option>
                    <option value="CCS">CCS</option>
                    <option value="SHTM">SHTM</option>
                    <option value="CTE">CTE</option>
                    <option value="COC">COC</option>
                </select>

                <div class="flex gap-1">
                    <select wire:model.live="selectedYear" class="w-16 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold text-[10px] uppercase">
                        <option value="">YR</option>
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                        <option value="3">3rd</option>
                        <option value="4">4th</option>
                    </select>

                    <select wire:model.live="selectedMajor" class="flex-1 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-semibold text-[10px] uppercase">
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

                <input type="text" wire:model.live.debounce.300ms="searchSubject"
                       placeholder="Search subject or code..."
                       class="w-full px-3 py-2 rounded-lg text-[10px] bg-slate-100 dark:bg-slate-800 placeholder:text-slate-500 font-semibold uppercase focus:ring focus:ring-blue-200 outline-none">
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
            @forelse($subjects as $subject)
                <div wire:key="subject-{{ $subject->id }}"
                     wire:click="$set('activeSubjectId', {{ $subject->id }})"
                     class="p-2 my-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-400 cursor-pointer transition-all text-[10px]">
                    <div class="flex justify-between items-center">
                        <span class="font-bold">{{ $subject->subject_code }}</span>
                        <span class="text-blue-500 text-[9px]">#{{ $subject->edp_code }}</span>
                    </div>
                    <p class="font-semibold text-[10px] mt-1 leading-tight">{{ $subject->description }}</p>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[9px] opacity-70">{{ $subject->units }} units</span>
                        @php $remain = $subject->units - ($subject->scheduled_hours ?? 0); @endphp
                        <span class="text-[8px] bg-blue-100 text-blue-700 rounded px-1 py-0.5 font-bold">{{ $remain }}h</span>
                    </div>
                </div>
            @empty
                <p class="text-center py-6 text-slate-400 text-[10px] uppercase">No subjects</p>
            @endforelse
        </div>
    </aside>

    {{-- ROOMS SIDEBAR --}}
    <aside x-show="roomsOpen" class="w-64 flex flex-col border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 sticky top-0">
            <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-purple-600 rounded-full"></span> Rooms
            </h3>
        </div>

        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
            @foreach($rooms as $room)
                <div wire:click="selectRoom({{ $room->id }})"
                     class="p-2 my-1 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 hover:border-purple-400 cursor-pointer transition-all text-[10px]">
                    <div class="flex justify-between">
                        <span class="font-bold">{{ $room->room_name }}</span>
                        <span class="text-[8px] bg-blue-100 text-blue-700 px-2 rounded">{{ $room->type }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[8px] text-slate-500 uppercase">Cap: {{ $room->capacity }}</span>
                        <span class="text-[8px]">{{ $room->utilization }}%</span>
                    </div>
                    <div class="h-1 bg-gray-100 dark:bg-slate-700 mt-1 rounded overflow-hidden">
                        <div class="h-full bg-blue-500" style="width: {{ $room->utilization }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </aside>
</div>
