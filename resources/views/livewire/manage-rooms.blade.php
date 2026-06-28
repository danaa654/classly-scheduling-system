<div wire:poll.3s class="min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500">

    <div class="flex min-h-screen font-sans antialiased text-slate-900 dark:text-white"
         x-data="{ 
            open: @entangle('showModal'),
            bulkOpen: @entangle('bulkOpen'),
            confirmDelete: @entangle('confirmingDeletion')
         }">

        <main class="flex-1 flex flex-col overflow-hidden">
            {{-- Header Section --}}
             <header class="mx-auto mt-2 h-14 w-[97%] max-w-[1600px] bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-5 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">
                        Room Management
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-indigo-400/80 font-medium italic">
                        Institutional Space Allocation
                    </p>
                </div>  

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                    <div class="flex items-center space-x-3">
                        @if(count($selectedRooms) > 0)
                            <button
                                wire:click="$set('confirmingDeletion', true)"
                                class="px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                    rounded-xl text-sm font-black hover:bg-red-100 dark:hover:bg-red-900/30
                                    transition-all border border-red-200 dark:border-red-800 shadow-sm">
                                🗑️ Delete Selected ({{ count($selectedRooms) }})
                            </button>
                        @endif

                        <button
                            @click="bulkOpen = true"
                            class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200
                                rounded-2xl text-sm font-black border border-slate-200 dark:border-slate-700 shadow-sm
                                transition-all duration-300 ease-out
                                hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white dark:hover:bg-slate-700
                                hover:shadow-[0_10px_25px_rgba(15,23,42,0.12)] dark:hover:shadow-[0_10px_25px_rgba(0,0,0,0.35)]
                                active:scale-[0.98] active:translate-y-0 flex items-center">
                            <span class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-r from-slate-200/40 via-white/70 to-slate-200/40 dark:from-slate-700/20 dark:via-slate-600/20 dark:to-slate-700/20"></span>
                            <span class="absolute -inset-y-full left-[-40%] w-1/3 rotate-12 bg-white/40 dark:bg-white/10 blur-md group-hover:left-[120%] transition-all duration-700"></span>
                            <span class="relative z-10 mr-2 text-base transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-0.5">📥</span>
                            <span class="relative z-10">Bulk Import</span>
                        </button>

                        <button
                            wire:click="openModal"
                            class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-blue-600 text-white rounded-2xl text-sm
                                font-black shadow-md shadow-blue-200 dark:shadow-none
                                transition-all duration-300 ease-out active:scale-95
                                hover:-translate-y-0.5 hover:scale-[1.02]
                                hover:shadow-[0_12px_30px_rgba(37,99,235,0.35)]">
                            <span class="relative z-10">+ Add New Room</span>
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-blue-500 via-indigo-500 to-blue-700
                                    opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                            </div>
                            <div class="absolute -inset-y-full left-[-30%] w-1/3 rotate-12 bg-white/30 blur-md group-hover:left-[120%] transition-all duration-700"></div>
                        </button>
                    </div>
                @endif
            </header>

            {{-- Content Body --}}
            <div class="p-4 md:p-5 pb-3 overflow-y-auto space-y-4 custom-scrollbar">

                {{-- Search & Filter Bar --}}
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-3
                           bg-white dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-200
                           dark:border-slate-800 shadow-sm transition-colors">
                    <div class="relative flex-1 max-w-2xl">
                        <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 text-lg">🔍</span>
                        <input
                            type="text"
                            wire:model.live="search"
                            placeholder="Search by room name, floor or specialization..."
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none
                                   rounded-xl text-sm font-semibold focus:ring-2 focus:ring-blue-500
                                   transition-all text-slate-800 dark:text-slate-200 placeholder-slate-400">
                    </div>

                    <div class="flex items-center space-x-3">
                        <span class="text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest whitespace-nowrap">
                            Filter Type:
                        </span>
                        <select
                            wire:model.live="filterType"
                            class="bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl text-sm
                                   font-bold uppercase px-4 py-3 focus:ring-2 focus:ring-blue-500
                                   text-slate-800 dark:text-slate-200 min-w-[140px] cursor-pointer">
                            <option value="">All Types</option>
                            <option value="LECTURE">Lecture</option>
                            <option value="LAB">Lab</option>
                        </select>

                        @php
                            $userRole = auth()->user()->role ?? '';
                            $userDept = strtoupper(trim(auth()->user()->department ?? ''));
                            $isRoleScopedUser = in_array($userRole, ['dean', 'oic', 'associate_dean']);
                            $myRoomsLabel = match(true) {
                                in_array($userRole, ['dean', 'oic']) => $userDept . ' Specialized Rooms',
                                $userRole === 'associate_dean'       => 'Lecture Rooms',
                                default                              => 'My Rooms',
                            };
                        @endphp

                        @if($isRoleScopedUser)
                            <button
                                wire:click="toggleViewMode"
                                class="inline-flex items-center gap-2 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-wider border transition-all duration-200
                                    {{ $viewMode === 'my_rooms'
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200 dark:shadow-none'
                                        : 'bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                {{ $myRoomsLabel }}
                                @if($viewMode === 'my_rooms')
                                    <span class="ml-0.5 text-indigo-200 text-[10px]">✓</span>
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Rooms Table --}}
                <div
                    class="bg-white dark:bg-slate-900/50 rounded-3xl border border-slate-200
                           dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="bg-slate-50/60 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400
                                   uppercase font-black tracking-widest text-xs border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-4 w-10 text-center">
                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectAll"
                                        class="rounded border-slate-300 dark:border-slate-600 bg-transparent
                                               text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                @endif
                            </th>
                            <th class="px-6 py-4">Room Details</th>
                            <th class="px-5 py-4 text-center">Classification</th>
                            <th class="px-5 py-4">Capacity</th>
                            <th class="px-5 py-4">Floor & Spec</th>
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                <th class="px-5 py-4 text-center">Assignments</th>
                                <th class="px-5 py-4 text-right">Actions</th>
                            @elseif(in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']))
                                <th class="px-5 py-4 text-center">Assignments</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-base">
                        @forelse($rooms as $room)
                            <tr
                                class="hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-colors
                                       {{ in_array($room->id, $selectedRooms) ? 'bg-blue-50/60 dark:bg-blue-900/20' : '' }}">
                                <td class="px-5 py-3 text-center align-middle">
                                    @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedRooms"
                                            value="{{ $room->id }}"
                                            class="rounded border-slate-300 dark:border-slate-600 bg-transparent
                                                   text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                    @endif
                                </td>
                                <td class="px-6 py-3 align-middle">
                                    <div class="flex flex-col leading-tight space-y-1">
                                        <span
                                            class="font-black text-slate-800 dark:text-slate-100 text-base lg:text-lg uppercase tracking-tight">
                                            {{ $room->room_name }}
                                        </span>
                                        <span
                                            class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                                            Main Campus Building
                                        </span>

                                        @php
                                            // Merge subjects from two sources so the utilisation
                                            // bar is accurate regardless of how the room was assigned:
                                            //   1. $room->subjects  → preferred_room_id (ManageRooms modal)
                                            //   2. $scheduledSubjectsByRoom[room_id] → Schedule.room_id
                                            //      (set during retrieve or auto-schedule)
                                            $scheduledForRoom = $scheduledSubjectsByRoom[$room->id] ?? collect();
                                            $allRoomSubjects  = $room->subjects
                                                ->merge($scheduledForRoom)
                                                ->unique('id')
                                                ->values();

                                            // Room load = SUM(duration_hours). meetings_per_week
                                            // only splits the block across days, never multiplies it.
                                            $roomTotalHours = $allRoomSubjects->sum(
                                                fn ($s) => (float) $s->duration_hours
                                            );

                                            $roomUtilPct    = min(100, (int) round((\App\Services\RoomCapacityService::getWeeklyCapacity() > 0 ? ($roomTotalHours / \App\Services\RoomCapacityService::getWeeklyCapacity()) : 0) * 100));
                                            $roomOverCap    = $roomTotalHours > \App\Services\RoomCapacityService::getWeeklyCapacity();

                                            $roomDotClass   = match (true) {
                                                $roomOverCap            => 'bg-red-500 animate-pulse',
                                                $roomUtilPct >= 86      => 'bg-red-500',
                                                $roomUtilPct >= 61      => 'bg-amber-500',
                                                default                 => 'bg-green-500',
                                            };

                                            $roomBarClass   = match (true) {
                                                $roomUtilPct >= 86      => 'bg-red-500',
                                                $roomUtilPct >= 61      => 'bg-amber-500',
                                                default                 => 'bg-green-500',
                                            };

                                            $roomTextClass  = match (true) {
                                                $roomUtilPct >= 86      => 'text-red-600 dark:text-red-400',
                                                $roomUtilPct >= 61      => 'text-amber-600 dark:text-amber-400',
                                                default                 => 'text-green-600 dark:text-green-500',
                                            };
                                        @endphp

                                        <button
                                            wire:click="toggleRoomDetails({{ $room->id }})"
                                            class="mt-1.5 w-full text-left focus:outline-none cursor-pointer group/util"
                                            title="{{ in_array($room->id, $expandedRooms) ? 'Collapse subject list' : 'Show allocated subjects' }}">

                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-block w-2 h-2 rounded-full shrink-0 {{ $roomDotClass }}"></span>
                                                <span class="text-[10px] font-black uppercase tracking-widest {{ $roomTextClass }}">
                                                    {{ number_format($roomTotalHours, 1) }}&nbsp;/ {{ \App\Services\RoomCapacityService::getFormattedCapacity() }}
                                                    @if ($roomOverCap)
                                                        &middot; OVER CAPACITY
                                                    @else
                                                        ({{ $roomUtilPct }}%)
                                                    @endif
                                                </span>
                                                <svg class="ml-auto w-3 h-3 text-slate-400 dark:text-slate-500 transition-transform duration-200 {{ in_array($room->id, $expandedRooms) ? 'rotate-180' : '' }}"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>

                                            <div class="mt-1.5 w-full max-w-[180px] h-1.5 bg-slate-100 dark:bg-slate-700/60 rounded-full overflow-hidden group-hover/util:max-w-[220px] transition-all duration-300">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $roomBarClass }}"
                                                     style="width: {{ $roomUtilPct }}%">
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center align-middle">
                                    <span
                                        class="px-4 py-1.5 text-xs uppercase font-black tracking-tight border rounded-xl
                                               {{ strtoupper($room->type) === 'LAB'
                                                    ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800'
                                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800' }}">
                                        {{ strtoupper($room->type) === 'LECTURE' ? 'Lecture' : 'Lab' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 align-middle">
                                    <div class="flex flex-col space-y-0.5">
                                        <span
                                            class="text-slate-800 dark:text-slate-100 font-black text-xl tracking-tight">
                                            {{ $room->capacity }}
                                        </span>
                                        <span
                                            class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                            Max Seats
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 align-middle">
                                    <div class="flex flex-col space-y-1">
                                        <span
                                            class="text-sm font-bold text-slate-700 dark:text-slate-300 leading-tight">
                                            {{ $room->floor ?? 'N/A' }}
                                        </span>
                                        @if($room->specialization)
                                            <span class="text-sm text-slate-500 dark:text-slate-400 italic">
                                                {{ $room->specialization }}
                                            </span>
                                        @endif
                                        @if($room->department_owner || $room->is_specialized)
                                            <span
                                                class="text-xs font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mt-0.5">
                                                {{ $room->department_owner ?: 'Shared' }} {{ $room->is_specialized ? '/ Specialized' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                    <td class="px-5 py-3 text-center align-middle">
                                        <button
                                            wire:click="openAssignModal({{ $room->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class.add="opacity-60 cursor-not-allowed"
                                            wire:target="openAssignModal({{ $room->id }})"
                                            class="inline-flex items-center justify-center px-4 py-2.5 text-xs w-full max-w-[160px]
                                                   bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800
                                                   text-indigo-700 dark:text-indigo-400 rounded-xl
                                                   hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600
                                                   hover:border-indigo-600 dark:hover:border-indigo-600
                                                   transition-all font-black shadow-sm">
                                            <span
                                                wire:loading
                                                wire:target="openAssignModal({{ $room->id }})"
                                                class="mr-2">⟳</span>
                                            <span wire:loading.remove wire:target="openAssignModal({{ $room->id }})">
                                                Manage Subjects
                                            </span>
                                        </button>
                                    </td>

                                    <td class="px-5 py-3 text-right align-middle space-x-2 whitespace-nowrap">
                                        <button
                                            wire:click="editRoom({{ $room->id }})"
                                            class="px-4 py-2 text-xs bg-white dark:bg-slate-800
                                                   border border-slate-200 dark:border-slate-700
                                                   text-blue-600 dark:text-blue-400 rounded-xl
                                                   hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600
                                                   hover:border-blue-600 dark:hover:border-blue-600
                                                   transition-all font-black shadow-sm inline-block">
                                            Edit
                                        </button>
                                        <button
                                            onclick="confirm('Permanently remove this room?') || event.stopImmediatePropagation()"
                                            wire:click="deleteRoom({{ $room->id }})"
                                            class="px-4 py-2 text-xs bg-white dark:bg-slate-800
                                                   border border-slate-200 dark:border-slate-700
                                                   text-red-600 dark:text-red-400 rounded-xl
                                                   hover:bg-red-600 hover:text-white dark:hover:bg-red-600
                                                   hover:border-red-600 dark:hover:border-red-600
                                                   transition-all font-black shadow-sm inline-block">
                                            Delete
                                        </button>
                                    </td>
                                @elseif(in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']))
                                    <td class="px-5 py-3 text-center align-middle">
                                        <button
                                            wire:click="openAssignModal({{ $room->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class.add="opacity-60 cursor-not-allowed"
                                            wire:target="openAssignModal({{ $room->id }})"
                                            class="inline-flex items-center justify-center px-4 py-2.5 text-xs w-full max-w-[160px]
                                                   bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800
                                                   text-indigo-700 dark:text-indigo-400 rounded-xl
                                                   hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600
                                                   hover:border-indigo-600 dark:hover:border-indigo-600
                                                   transition-all font-black shadow-sm">
                                            <span
                                                wire:loading
                                                wire:target="openAssignModal({{ $room->id }})"
                                                class="mr-2">⟳</span>
                                            <span wire:loading.remove wire:target="openAssignModal({{ $room->id }})">
                                                Manage Subjects
                                            </span>
                                        </button>
                                    </td>
                                @endif
                            </tr>

                        @if(in_array($room->id, $expandedRooms))
                            <tr wire:key="room-expand-{{ $room->id }}" class="border-0">
                                <td colspan="{{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 7 : (in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']) ? 6 : 5) }}"
                                    class="px-6 pb-4 pt-0">

                                    <div class="room-expand-anim ml-4 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900/70 shadow-sm">

                                        <div class="flex items-center justify-between px-4 py-2.5 bg-blue-900 dark:bg-blue-950 border-b border-blue-800 dark:border-blue-900">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-100 dark:text-blue-200">
                                                📋 Allocated Subjects
                                            </span>
                                            @if($allRoomSubjects->isNotEmpty())
                                                <span class="text-[10px] font-bold text-blue-300 dark:text-blue-400 uppercase tracking-widest">
                                                    {{ $allRoomSubjects->count() }} subject{{ $allRoomSubjects->count() !== 1 ? 's' : '' }}
                                                    &nbsp;·&nbsp;
                                                    {{ number_format($roomTotalHours, 1) }}h / wk total
                                                </span>
                                            @endif
                                        </div>

                                        @if($allRoomSubjects->isEmpty())
                                            <div class="py-5 px-4 text-center">
                                                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    No subjects allocated to this room yet.
                                                </p>
                                            </div>
                                        @else
                                            @foreach($allRoomSubjects as $subject)
                                                @php
                                                    // Per-subject room usage = duration_hours only.
                                                    // meetings_per_week is for schedule splitting, not multiplication.
                                                    $subjectWklyHrs = round((float) $subject->duration_hours, 1);
                                                    // Show a badge if this subject came from a schedule record
                                                    // (room_id) rather than a direct preferred_room_id assignment.
                                                    $isScheduledRoom = is_null($subject->preferred_room_id)
                                                        || (int) $subject->preferred_room_id !== $room->id;
                                                @endphp
                                                <div wire:key="expand-subj-{{ $subject->id }}"
                                                     class="flex items-center gap-3 px-4 py-3
                                                            {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700/50' : '' }}
                                                            hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-colors">
                                                    <span class="font-black font-mono text-xs text-slate-500 dark:text-slate-400 uppercase tracking-widest w-28 shrink-0">
                                                        {{ $subject->edp_code }}
                                                    </span>

                                                    <span class="font-black text-xs text-slate-800 dark:text-slate-100 uppercase tracking-tight w-24 shrink-0">
                                                        {{ $subject->subject_code }}
                                                    </span>

                                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium flex-1 truncate">
                                                        {{ $subject->description }}
                                                    </span>

                                                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-widest border border-indigo-200 dark:border-indigo-800 shrink-0">
                                                        Sec&nbsp;{{ $subject->section ?? '—' }}
                                                    </span>

                                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shrink-0
                                                        {{ strtolower($subject->type) === 'minor'
                                                            ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                                                            : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' }}">
                                                        {{ $subject->type }}
                                                    </span>

                                                    {{-- Badge: shows how the room was assigned --}}
                                                    @if($isScheduledRoom)
                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800 shrink-0"
                                                              title="Room set during auto-schedule or retrieve; not yet saved as preferred room">
                                                            SCHEDULED
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 shrink-0"
                                                              title="Room preferred-assigned via Manage Subjects">
                                                            📌 PREFERRED
                                                        </span>
                                                    @endif

                                                    <span class="text-xs font-black text-slate-600 dark:text-slate-300 tabular-nums shrink-0 w-14 text-right">
                                                        {{ $subjectWklyHrs }}h<span class="text-slate-400 dark:text-slate-500 font-bold text-[10px]">/wk</span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif

                        @empty
                            <tr>
                                <td colspan="{{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 7 : (in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']) ? 6 : 5) }}" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-5xl mb-4 opacity-50">🏫</span>
                                        <p
                                            class="text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest text-sm">
                                            No space records found
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3 mb-3 flex justify-center">
                    {{ $rooms->links('livewire.custom-pagination') }}
                </div>
            </div>
        </main>

        {{-- --- ADD/EDIT MODAL --- --}}
        <div
            x-show="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 dark:bg-slate-950/80 backdrop-blur-md"
            x-cloak
            x-transition>
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl
                       p-8 md:p-10 shadow-2xl border border-slate-200 dark:border-slate-800 transition-colors
                       custom-scrollbar"
                @click.away="open = false">

                <div class="flex items-center justify-between mb-8">
                    <h3
                        class="text-2xl lg:text-3xl font-black text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $isEditMode ? 'Edit Room' : 'Add New Room' }}
                    </h3>
                    <button
                        @click="open = false"
                        class="text-slate-400 hover:text-red-500 text-2xl font-bold transition-colors">
                        ✕
                    </button>
                </div>

                <form
                    wire:submit.prevent="{{ $isEditMode ? 'updateRoom' : 'saveRoom' }}"
                    class="space-y-6">
                    <div>
                        <label
                            class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                            Room Identifier
                        </label>
                        <input
                            type="text"
                            wire:model="room_name"
                            placeholder="e.g. Room 302"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none dark:text-slate-200
                                   rounded-xl font-bold text-base focus:ring-2 focus:ring-blue-500
                                   shadow-inner dark:placeholder-slate-500">
                        @error('room_name')
                        <span
                            class="text-red-500 text-xs font-bold mt-1.5 ml-1 uppercase block">
                            {{ $message }}
                        </span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                                Room Type
                            </label>
                            <select
                                wire:model="type"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none
                                       dark:text-slate-200 rounded-xl font-bold focus:ring-2
                                       focus:ring-blue-500 shadow-inner uppercase text-sm cursor-pointer">
                                <option value="LECTURE">Lecture</option>
                                <option value="LAB">Lab</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                                Capacity
                            </label>
                            <input
                                type="number"
                                wire:model="capacity"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none
                                       dark:text-slate-200 rounded-xl font-bold focus:ring-2
                                       focus:ring-blue-500 shadow-inner text-base">
                            @error('capacity')
                            <span
                                class="text-red-500 text-xs font-bold mt-1.5 ml-1 uppercase block">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                                Floor
                            </label>
                            <input
                                type="text"
                                wire:model="floor"
                                placeholder="e.g. 1st Floor"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none
                                       dark:text-slate-200 rounded-xl font-bold focus:ring-2
                                       focus:ring-blue-500 shadow-inner dark:placeholder-slate-500 text-base">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                                Specialization
                            </label>
                            <input
                                type="text"
                                wire:model="specialization"
                                placeholder="e.g. FB, LD, QD"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none
                                       dark:text-slate-200 rounded-xl font-bold focus:ring-2
                                       focus:ring-blue-500 shadow-inner dark:placeholder-slate-500 text-base">
                        </div>
                    </div>

                    <div class="grid grid-cols-[3fr,2fr] gap-5">
                        <div>
                            <label
                                class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                                Department Owner
                            </label>
                            <select
                                wire:model="department_owner"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none
                                       dark:text-slate-200 rounded-xl font-bold focus:ring-2
                                       focus:ring-blue-500 shadow-inner uppercase text-sm cursor-pointer">
                                <option value="">Shared</option>
                                <option value="CCS">CCS</option>
                                <option value="CTE">CTE</option>
                                <option value="COC">COC</option>
                                <option value="SHTM">SHTM</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label
                                class="flex w-full items-center gap-3 rounded-xl bg-slate-50 px-4 py-3.5 text-xs
                                       font-black uppercase tracking-widest text-slate-600 shadow-inner
                                       dark:bg-slate-800 dark:text-slate-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="is_specialized"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500
                                           dark:border-slate-600 dark:bg-slate-900 h-5 w-5 cursor-pointer">
                                Specialized
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest mb-2 ml-1">
                            Allowed Departments
                        </label>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach(['CCS', 'CTE', 'COC', 'SHTM'] as $deptOption)
                                <label
                                    class="flex items-center justify-center gap-2 rounded-xl bg-slate-50 px-3 py-3 text-xs
                                           font-black uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer
                                           border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition">
                                    <input
                                        type="checkbox"
                                        value="{{ $deptOption }}"
                                        wire:model="allowed_departments"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500
                                               dark:border-slate-600 dark:bg-slate-900 h-4 w-4 cursor-pointer">
                                    {{ $deptOption }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 ml-1 text-xs font-bold text-slate-400 dark:text-slate-500">
                            Leave empty for shared rooms. Specialized rooms should list the departments allowed to use
                            them.
                        </p>
                    </div>

                    <div class="flex space-x-4 pt-6 mt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="open = false"
                            class="flex-1 py-4 font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest
                                   text-xs hover:text-slate-800 dark:hover:text-slate-200 transition-colors bg-slate-100 dark:bg-slate-800 rounded-2xl">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="flex-1 py-4 bg-blue-600 dark:bg-blue-700 text-white rounded-2xl font-black
                                   shadow-lg shadow-blue-200 dark:shadow-none hover:bg-blue-700 transition-all
                                   uppercase text-xs tracking-widest">
                            {{ $isEditMode ? 'Update Details' : 'Confirm & Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- --- BULK IMPORT MODAL --- --}}
        <div
            x-show="bulkOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
            x-cloak
            x-transition>
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl p-8 md:p-10 shadow-2xl border
                       border-slate-100 dark:border-slate-800 transition-colors"
                @click.away="bulkOpen = false">

                <div class="flex items-start justify-between mb-1">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100">
                            Batch Room Import
                        </h3>
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic mt-0.5">
                            Upload a CSV file to import multiple rooms at once.
                        </p>
                    </div>
                    <button
                        @click="bulkOpen = false"
                        class="mt-1 p-1 text-slate-300 hover:text-slate-500 dark:text-slate-600
                               dark:hover:text-slate-400 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-6 mb-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">
                        Required CSV Headers
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['room_name', 'room_type', 'specialization', 'capacity', 'floor'] as $csvHeader)
                            <span class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300
                                         rounded-lg text-xs font-mono font-black border border-slate-200 dark:border-slate-700
                                         tracking-tight">
                                {{ $csvHeader }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">
                            Upload File
                        </p>
                        <div
                            class="relative border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-8
                                   flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-800/40
                                   hover:border-blue-400 dark:hover:border-blue-600
                                   hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-all cursor-pointer"
                            wire:loading.class="opacity-50 pointer-events-none"
                            wire:target="importFile">

                            <input type="file" wire:model.live="importFile" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">

                            <div wire:loading wire:target="importFile" class="text-center">
                                <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                                <span class="text-xs font-black text-blue-500 uppercase tracking-widest">
                                    Analyzing CSV Structure...
                                </span>
                            </div>

                            <div wire:loading.remove wire:target="importFile" class="text-center">
                                @if($importFile)
                                    <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center
                                                justify-center text-2xl mx-auto mb-3 border border-blue-100 dark:border-blue-800">
                                        📊
                                    </div>
                                    <span class="text-sm font-black text-blue-600 dark:text-blue-400 block">
                                        {{ $importFile->getClientOriginalName() }}
                                    </span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-bold mt-1 block">
                                        File ready - see preview below
                                    </span>
                                @else
                                    <div class="w-14 h-14 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center
                                                justify-center text-2xl mx-auto mb-3 border border-slate-200 dark:border-slate-700">
                                        📂
                                    </div>
                                    <span class="text-sm font-bold text-slate-500 dark:text-slate-400 block">
                                        Drop your CSV here or click to browse
                                    </span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-medium mt-1 block">
                                        .csv files only
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(count($importPreview) > 0)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">
                                Preview &mdash; {{ count($importPreview) }} {{ Str::plural('Row', count($importPreview)) }} Detected
                            </p>
                            <div class="border border-slate-100 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm">
                                <div class="max-h-52 overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-slate-50 dark:bg-slate-800 sticky top-0 border-b border-slate-100 dark:border-slate-700">
                                            <tr class="text-[10px] uppercase font-black text-slate-400 dark:text-slate-500 tracking-widest">
                                                <th class="px-4 py-3">Room Name</th>
                                                <th class="px-4 py-3">Type</th>
                                                <th class="px-4 py-3">Floor</th>
                                                <th class="px-4 py-3 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800 bg-white dark:bg-slate-900/50">
                                        @foreach($importPreview as $preview)
                                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                                                <td class="px-4 py-3 font-black text-slate-800 dark:text-slate-200 text-sm">
                                                    {{ $preview['room_name'] }}
                                                </td>
                                                <td class="px-4 py-3 font-black text-slate-400 dark:text-slate-500 uppercase text-xs tracking-widest">
                                                    {{ $preview['type'] }}
                                                </td>
                                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300 text-sm">
                                                    {{ $preview['floor'] ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span
                                                        title="{{ $preview['errors'] ?? '' }}"
                                                        class="px-3 py-1 rounded-full text-[10px] uppercase font-black tracking-wider
                                                               {{ $preview['status'] === 'INVALID'
                                                                    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300'
                                                                    : ($preview['status'] === 'DUPLICATE'
                                                                        ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                                                        : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400') }}">
                                                        {{ $preview['status'] }}
                                                    </span>
                                                    @if(($preview['status'] ?? '') === 'INVALID')
                                                        <div class="mt-1 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                                            {{ $preview['errors'] ?? 'Invalid row' }}
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between mt-8">
                    <button
                        type="button"
                        @click="bulkOpen = false; $wire.reset(['importFile', 'importPreview'])"
                        class="font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-xs
                               hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        Cancel
                    </button>

                    <button
                        wire:click="processImport"
                        wire:loading.attr="disabled"
                        @disabled(count($importPreview) === 0)
                        class="px-8 py-3.5 bg-blue-600 text-white rounded-full font-black uppercase tracking-widest
                               text-xs shadow-lg shadow-blue-200 dark:shadow-none
                               hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500
                               transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none
                               {{ count($importPreview) === 0 ? 'opacity-40 cursor-not-allowed' : '' }}">
                        <span wire:loading.remove wire:target="processImport">
                            Confirm &amp; Import Valid Rooms
                        </span>
                        <span wire:loading wire:target="processImport" class="flex items-center gap-2">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Saving to Database...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- --- BULK DELETE CONFIRMATION --- --}}
        <div
            x-show="confirmDelete"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm"
            x-cloak
            x-transition>
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-3xl p-10 text-center shadow-2xl
                       border border-red-200 dark:border-red-900/50 transition-colors"
                @click.away="confirmDelete = false">
                <div
                    class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center
                           justify-center text-4xl mx-auto mb-5 shadow-inner">
                    ⚠️
                </div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight mb-2">
                    Security Check
                </h3>
                <p class="text-base text-slate-600 dark:text-slate-400 mb-8 font-medium">
                    You are about to delete
                    <span class="text-red-600 dark:text-red-400 font-black text-lg">{{ count($selectedRooms) }}</span>
                    room record(s). This action is permanent.
                </p>

                <div class="flex flex-col space-y-3">
                    <button
                        wire:click="deleteSelected"
                        class="w-full py-4 bg-red-600 dark:bg-red-700 text-white rounded-2xl font-black
                               shadow-lg shadow-red-200 dark:shadow-none hover:bg-red-700 dark:hover:bg-red-600
                               transition-all uppercase text-xs tracking-widest">
                        Yes, Delete Permanently
                    </button>

                    <button
                        @click="confirmDelete = false"
                        class="w-full py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                               rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition-all
                               uppercase text-xs tracking-widest">
                        No, Keep Records
                    </button>
                </div>
            </div>
        </div>

        <style>
            .room-expand-anim {
                animation: roomExpandIn 0.18s ease-out both;
            }
            @keyframes roomExpandIn {
                from { opacity: 0; transform: translateY(-5px); }
                to   { opacity: 1; transform: translateY(0); }
            }
        </style>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('toast', (event) => {
                    const data = Array.isArray(event) ? event[0] : event;

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });

                    Toast.fire({
                        icon: data.type,
                        title: data.message,
                        text: data.detail || '',
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
                    });
                });

                Livewire.on('swal', (event) => {
                    const data = Array.isArray(event) ? event[0] : event;
                    Swal.fire({
                        title: data.title,
                        text: data.text,
                        icon: data.icon,
                        confirmButtonColor: '#3B82F6',
                    });
                });
            });
        </script>

        {{-- --- ASSIGN SUBJECTS MODAL --- --}}
        <div
            x-data="{
                open: @entangle('showAssignModal').live,
                search: '',
                filterDept: '',
                filterMajor: '',
                filterYear: '',
                filterSection: '',
                get majorOptions() {
                    const map = { 'CCS': ['IT','ACT'], 'CTE': ['ED'], 'COC': ['FB','LD','QD'], 'SHTM': ['HM','TM'] };
                    return this.filterDept ? (map[this.filterDept] || []) : [];
                },
                get hasActiveFilters() {
                    return this.filterDept !== '' || this.filterMajor !== '' || this.filterYear !== '' || this.filterSection !== '';
                },
                clearFilters() { this.filterDept = ''; this.filterMajor = ''; this.filterYear = ''; this.filterSection = ''; }
            }"
            x-effect="if (!open) { search = ''; filterDept = ''; filterMajor = ''; filterYear = ''; filterSection = ''; }"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center
                   bg-slate-900/70 dark:bg-slate-950/80 backdrop-blur-md">

            <div
                @click.outside="$wire.closeAssignModal()"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] flex flex-col
                       rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800
                       transition-colors duration-300">

                <div class="px-8 pt-8 pb-4 flex-shrink-0 space-y-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight">
                                Manage Subjects for
                                <span class="text-indigo-600 dark:text-indigo-400">
                                    {{ $assigningRoomData['room_name'] ?? '—' }}
                                </span>
                            </h3>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">
                                Preferred room binding · auto-scheduler will honour these preferences
                            </p>
                        </div>
                        <button
                            wire:click="closeAssignModal"
                            class="ml-4 text-slate-400 hover:text-red-500 transition-colors
                                   text-2xl font-bold leading-none mt-1"
                            aria-label="Close">
                            ✕
                        </button>
                    </div>

                    @if(!empty($assigningRoomData))
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tight border
                                       {{ ($assigningRoomData['type'] ?? '') === 'LAB'
                                            ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700'
                                            : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700' }}">
                                🏫 {{ ($assigningRoomData['type'] ?? '') === 'LAB' ? 'Laboratory' : 'Lecture Room' }}
                            </span>

                            <span
                                class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tight
                                       bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400
                                       border border-indigo-200 dark:border-indigo-800">
                                🎯 Smart filter: {{ $assigningRoomData['filter_label'] ?? 'All Subjects' }}
                            </span>

                            <span
                                class="px-3 py-1.5 rounded-xl text-xs font-bold
                                       bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                {{ count($modalSubjects) }} eligible
                            </span>
                        </div>
                    @endif

                    <div class="pt-2 space-y-3">

                        {{-- ── Capacity header row ───────────────────────── --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                Weekly Room Load
                            </span>
                            <span class="text-sm font-black tabular-nums
                                       {{ $selectedWeeklyHours > $maxWeeklyHours ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ number_format($selectedWeeklyHours, 1) }}h
                                <span class="text-slate-400 dark:text-slate-600 font-medium">
                                    / {{ $maxWeeklyHours }}h max
                                </span>
                            </span>
                        </div>

                        {{-- ── Progress bar ───────────────────────────────── --}}
                        @php
                            $maxCap   = $maxWeeklyHours;
                            // "Saved" portion = hidden load + pre-selected visible load
                            $savedPct   = $maxCap > 0 ? min(100, ($currentRoomHiddenLoad + $currentRoomVisibleLoad) / $maxCap * 100) : 0;
                            // "Projected" portion = full selected total
                            $projPct    = $maxCap > 0 ? min(100, $selectedWeeklyHours / $maxCap * 100) : 0;
                            $isOverCap  = $selectedWeeklyHours > $maxCap;
                            $savedBar   = $isOverCap ? 'bg-red-400' : 'bg-indigo-400 dark:bg-indigo-500';
                            $addedBar   = $isOverCap ? 'bg-red-600' : 'bg-indigo-600 dark:bg-indigo-400';
                        @endphp
                        <div class="relative h-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            {{-- Existing saved load (darker) --}}
                            <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 {{ $savedBar }}"
                                 style="width: {{ $savedPct }}%"></div>
                            {{-- Projected total incl. pending selection (lighter overlay) --}}
                            <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 opacity-60 {{ $addedBar }}"
                                 style="width: {{ $projPct }}%"></div>
                        </div>

                        {{-- ── Three-stat breakdown pills ─────────────────── --}}
                        @php
                            $curLoad    = $currentRoomHiddenLoad + $currentRoomVisibleLoad;
                            $remaining  = max(0, $maxCap - $curLoad);
                            // Net new hours being added (pending = selected − already-saved)
                            $pendingHrs = max(0, $selectedWeeklyHours - $curLoad);
                        @endphp
                        <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold">
                            <div class="bg-slate-100 dark:bg-slate-800 rounded-xl py-2 px-1">
                                <div class="text-slate-500 dark:text-slate-400 uppercase tracking-wide text-[9px] mb-0.5">Current</div>
                                <div class="text-slate-700 dark:text-slate-200 tabular-nums">{{ number_format($curLoad, 1) }}h</div>
                            </div>
                            <div class="{{ $pendingHrs > $remaining ? 'bg-red-50 dark:bg-red-900/20' : 'bg-indigo-50 dark:bg-indigo-900/20' }} rounded-xl py-2 px-1">
                                <div class="{{ $pendingHrs > $remaining ? 'text-red-500' : 'text-indigo-500 dark:text-indigo-400' }} uppercase tracking-wide text-[9px] mb-0.5">Adding</div>
                                <div class="{{ $pendingHrs > $remaining ? 'text-red-600 dark:text-red-400' : 'text-indigo-700 dark:text-indigo-300' }} tabular-nums">
                                    {{ $pendingHrs > 0 ? '+' : '' }}{{ number_format($pendingHrs, 1) }}h
                                </div>
                            </div>
                            <div class="{{ $remaining <= 0 ? 'bg-red-50 dark:bg-red-900/20' : ($remaining < $maxCap * 0.2 ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-emerald-50 dark:bg-emerald-900/20') }} rounded-xl py-2 px-1">
                                <div class="{{ $remaining <= 0 ? 'text-red-500' : ($remaining < $maxCap * 0.2 ? 'text-amber-500' : 'text-emerald-500') }} uppercase tracking-wide text-[9px] mb-0.5">Remaining</div>
                                <div class="{{ $remaining <= 0 ? 'text-red-600 dark:text-red-400' : ($remaining < $maxCap * 0.2 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-300') }} tabular-nums">
                                    {{ number_format($remaining, 1) }}h
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Hard capacity error (blocks save) ───────────────── --}}
                    @if($capacityError)
                        <div class="flex items-start gap-3 px-5 py-4 bg-red-50 dark:bg-red-900/20
                                   border border-red-200 dark:border-red-700 rounded-xl">
                            <span class="text-red-500 dark:text-red-400 text-xl flex-shrink-0">🚫</span>
                            <div>
                                <p class="text-sm font-black text-red-800 dark:text-red-200 leading-snug">
                                    Room Capacity Exceeded
                                </p>
                                <p class="text-xs text-red-700 dark:text-red-300 mt-1 leading-relaxed">
                                    {{ $capacityError }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- ── Soft advisory warning ────────────────────────────── --}}
                    @if($capacityWarning && !$capacityError)
                        <div class="flex items-start gap-3 px-5 py-4 bg-amber-50 dark:bg-amber-900/20
                                   border border-amber-200 dark:border-amber-700 rounded-xl">
                            <span class="text-amber-500 dark:text-amber-400 text-xl flex-shrink-0">⚠️</span>
                            <p class="text-sm font-bold text-amber-800 dark:text-amber-200 leading-snug">
                                {{ $capacityWarning }}
                            </p>
                        </div>
                    @endif

                    <div class="relative mt-2">
                        <span
                            class="absolute inset-y-0 left-5 flex items-center text-slate-400 pointer-events-none text-lg">
                            🔍
                        </span>
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Search by subject code, description or department..."
                            class="w-full pl-12 pr-5 py-3.5 text-sm font-bold bg-slate-50 dark:bg-slate-800/50
                                   border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-800 dark:text-slate-200
                                   placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2
                                   focus:ring-indigo-500 transition shadow-sm">
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 self-center mr-1">
                            Filter:
                        </span>

                        <select
                            x-model="filterDept"
                            @change="filterMajor = ''"
                            :class="filterDept !== ''
                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'"
                            class="text-xs font-black uppercase tracking-wide px-3 py-1.5 rounded-xl border cursor-pointer focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="">All Depts</option>
                            <option value="CCS">CCS</option>
                            <option value="CTE">CTE</option>
                            <option value="COC">COC</option>
                            <option value="SHTM">SHTM</option>
                        </select>

                        <select
                            x-model="filterMajor"
                            :disabled="filterDept === ''"
                            :class="filterMajor !== ''
                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'"
                            class="text-xs font-black uppercase tracking-wide px-3 py-1.5 rounded-xl border cursor-pointer focus:ring-2 focus:ring-indigo-500 transition-all disabled:opacity-40 disabled:cursor-default">
                            <option value="">All Majors</option>
                            <template x-for="maj in majorOptions" :key="maj">
                                <option :value="maj" x-text="maj"></option>
                            </template>
                        </select>

                        <select
                            x-model="filterYear"
                            :class="filterYear !== ''
                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'"
                            class="text-xs font-black uppercase tracking-wide px-3 py-1.5 rounded-xl border cursor-pointer focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="">All Years</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>

                        <select
                            x-model="filterSection"
                            :class="filterSection !== ''
                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'"
                            class="text-xs font-black uppercase tracking-wide px-3 py-1.5 rounded-xl border cursor-pointer focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="">All Sections</option>
                            <option value="A">Sec A</option>
                            <option value="B">Sec B</option>
                        </select>

                        <button
                            x-show="hasActiveFilters"
                            @click="clearFilters()"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-end="opacity-0"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wide
                                   bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                   border border-red-200 dark:border-red-800
                                   hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
                            ✕ Clear
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-8 py-2 min-h-0 custom-scrollbar text-base">
                    @if(empty($modalSubjects))
                        <div class="py-24 text-center">
                            <span class="text-5xl opacity-40 block mb-4">📭</span>
                            <p
                                class="text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest text-sm mb-2">
                                No matching subjects for this room
                            </p>
                            <p
                                class="text-slate-400 dark:text-slate-500 text-xs font-medium max-w-md mx-auto leading-relaxed">
                                The smart filter found no eligible subjects in the active term for this room type &
                                specialization.
                            </p>
                        </div>
                    @else
                        <div wire:loading wire:target="openAssignModal" class="py-20 text-center">
                            <div
                                class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-4">
                            </div>
                            <p
                                class="text-xs font-black text-indigo-500 uppercase tracking-widest">
                                Loading subjects...
                            </p>
                        </div>

                        <div wire:loading.remove wire:target="openAssignModal">
                            <div
                                class="sticky top-0 z-10 grid items-center gap-4 px-4 py-3 mb-2
                                       border-b border-slate-200 dark:border-slate-700
                                       bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm
                                       text-xs font-black uppercase tracking-widest
                                       text-slate-500 dark:text-slate-400 rounded-t-xl mt-2"
                                style="grid-template-columns: 2rem 1fr 9rem 4rem 6rem">
                                <div>
                                    <input
                                        type="checkbox"
                                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-600
                                               text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800 cursor-pointer"
                                        x-on:change="
                                            const visible = $el.closest('[x-data]')
                                                .querySelectorAll('[data-subject-row]:not([style*='display: none']) input[type=checkbox]');
                                            visible.forEach(cb => {
                                                if (cb.checked !== $el.checked) cb.click();
                                            });
                                        "
                                        title="Toggle all visible subjects">
                                </div>
                                <div>Subject Details</div>
                                <div class="text-right">Dept · Yr/Sec</div>
                                <div class="text-right">Units</div>
                                <div class="text-right">Wkly Hrs</div>
                            </div>

                            @foreach($modalSubjects as $subject)
                                @php
                                    $claimedByOtherRoom = !empty($subject['preferred_room_id'])
                                        && (int) $subject['preferred_room_id'] !== (int) $assigningRoomId;
                                @endphp
                                <div
                                    wire:key="assign-subject-{{ $subject['id'] }}"
                                    data-subject-row
                                    x-show="
                                        (() => {
                                            const sDept = {{ Js::from(strtoupper((string)($subject['department'] ?? ''))) }};
                                            const sMaj  = {{ Js::from(strtoupper((string)($subject['major'] ?? ''))) }};
                                            const sYear = {{ Js::from((string)($subject['year_level'] ?? '')) }};
                                            const sSec  = {{ Js::from(strtoupper(trim((string)($subject['section'] ?? '')))) }};
                                            const families = {'CCS':['CCS','IT','ACT'],'CTE':['CTE','ED'],'COC':['COC','FB','LD','QD'],'SHTM':['SHTM','HM','TM']};

                                            const matchSearch = search === '' ||
                                                {{ Js::from(strtolower((string)($subject['subject_code'] ?? ''))) }}.includes(search.toLowerCase()) ||
                                                {{ Js::from(strtolower((string)($subject['description'] ?? ''))) }}.includes(search.toLowerCase()) ||
                                                {{ Js::from(strtolower((string)($subject['department'] ?? ''))) }}.includes(search.toLowerCase()) ||
                                                {{ Js::from(strtolower((string)($subject['major'] ?? ''))) }}.includes(search.toLowerCase());
                                            if (!matchSearch) return false;

                                            if (filterDept !== '') {
                                                const fam = families[filterDept] || [];
                                                if (!fam.includes(sDept) && !fam.includes(sMaj)) return false;
                                            }
                                            if (filterMajor !== ''   && sMaj  !== filterMajor)   return false;
                                            if (filterYear  !== ''   && sYear !== filterYear)     return false;
                                            if (filterSection !== '' && sSec  !== filterSection)  return false;

                                            return true;
                                        })()
                                    "
                                    @class([
                                        'grid items-center gap-4 px-4 py-3.5 mb-1 rounded-2xl cursor-pointer select-none transition-all group',
                                        'border border-transparent hover:bg-indigo-50/80 dark:hover:bg-indigo-900/20 hover:border-indigo-200 dark:hover:border-indigo-800' => !$claimedByOtherRoom,
                                        'border border-amber-200/60 dark:border-amber-700/30 bg-amber-50/30 dark:bg-amber-950/10 hover:bg-amber-50/70 dark:hover:bg-amber-950/25' => $claimedByOtherRoom,
                                    ])
                                    style="grid-template-columns: 2rem 1fr 9rem 4rem 6rem"
                                    wire:click="toggleSubjectId('{{ $subject['id'] }}')">
                                    <div class="flex items-center" wire:click.stop>
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedSubjectIds"
                                            value="{{ $subject['id'] }}"
                                            class="w-4 h-4 rounded border-slate-300 dark:border-slate-600
                                                   text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800 cursor-pointer">
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span
                                                @class([
                                                    'font-black text-sm md:text-base uppercase tracking-tight',
                                                    'text-slate-800 dark:text-slate-100' => !$claimedByOtherRoom,
                                                    'text-slate-500 dark:text-slate-400' => $claimedByOtherRoom,
                                                ])>
                                                {{ $subject['subject_code'] }}
                                            </span>
                                            @if($subject['requires_lab'])
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                                                           bg-purple-100 dark:bg-purple-900/40
                                                           text-purple-700 dark:text-purple-300">
                                                    LAB
                                                </span>
                                            @endif
                                            @php
                                                $roomType       = strtoupper($assigningRoomData['type'] ?? '');
                                                $isLabRoom      = in_array($roomType, ['LAB', 'LABORATORY'], true);
                                                $subjectIsMinor = strtolower($subject['subject_type'] ?? 'major') === 'minor';
                                                $subjectIsMajor = !$subjectIsMinor;
                                                $prt            = strtoupper($subject['preferred_room_type'] ?? '');
                                                // Override: Minor in a LAB room, or Major in a LECTURE room
                                                $isOverride = ($isLabRoom  && $prt === 'LAB'     && $subjectIsMinor)
                                                           || (!$isLabRoom && $prt === 'LECTURE'  && $subjectIsMajor);
                                            @endphp
                                            @if($isOverride)
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                                                           bg-teal-100 dark:bg-teal-900/40
                                                           text-teal-700 dark:text-teal-300"
                                                    title="This subject was manually set to prefer a {{ $isLabRoom ? 'Lab' : 'Lecture' }} room via the room override setting.">
                                                    🔀 Override
                                                </span>
                                            @endif
                                            @if(in_array((string)$subject['id'], $selectedSubjectIds))
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                                                           bg-indigo-100 dark:bg-indigo-900/40
                                                           text-indigo-700 dark:text-indigo-300">
                                                    ✓ selected
                                                </span>
                                            @endif
                                            @if($claimedByOtherRoom)
                                                <span
                                                    class="bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400
                                                           text-[10px] font-bold uppercase px-2 py-0.5 rounded tracking-wide whitespace-nowrap">
                                                    ⚠️ Prefers Room: {{ $subject['preferred_room_name'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div
                                            class="text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                                            {{ $subject['description'] }}
                                        </div>
                                        <div
                                            class="text-[10px] text-slate-400 dark:text-slate-500 font-bold mt-1 font-mono uppercase tracking-widest">
                                            EDP: {{ $subject['edp_code'] }}
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div
                                            class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-tight mb-1">
                                            {{ $subject['department'] }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                            Yr {{ $subject['year_level'] }} · {{ $subject['section'] }}
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <span
                                            class="font-black text-slate-700 dark:text-slate-200 text-sm md:text-base tabular-nums">
                                            {{ $subject['units'] }}
                                        </span>
                                        <span
                                            class="block text-[10px] text-slate-500 dark:text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                            units
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <span
                                            class="font-black text-indigo-600 dark:text-indigo-400 text-sm md:text-base tabular-nums">
                                            {{ $subject['weekly_hours'] }}h
                                        </span>
                                        <span
                                            class="block text-[10px] text-slate-500 dark:text-slate-500 font-bold uppercase tracking-widest mt-0.5">
                                            / wk
                                        </span>
                                    </div>
                                </div>
                            @endforeach

                            <p
                                x-show="
                                    (search !== '' || hasActiveFilters) &&
                                    (() => {
                                        const families = {'CCS':['CCS','IT','ACT'],'CTE':['CTE','ED'],'COC':['COC','FB','LD','QD'],'SHTM':['SHTM','HM','TM']};
                                        return {{ Js::from(collect($modalSubjects)->map(fn($s) => [
                                            'code' => strtolower($s['subject_code'] ?? ''),
                                            'desc' => strtolower($s['description'] ?? ''),
                                            'dept' => strtoupper($s['department'] ?? ''),
                                            'maj'  => strtoupper($s['major'] ?? ''),
                                            'year' => (string)($s['year_level'] ?? ''),
                                            'sec'  => strtoupper(trim($s['section'] ?? '')),
                                        ])->toJson()) }}.every(s => {
                                            const matchSearch = search === '' ||
                                                s.code.includes(search.toLowerCase()) ||
                                                s.desc.includes(search.toLowerCase()) ||
                                                s.dept.toLowerCase().includes(search.toLowerCase()) ||
                                                s.maj.toLowerCase().includes(search.toLowerCase());
                                            if (!matchSearch) return true;
                                            if (filterDept !== '') {
                                                const fam = families[filterDept] || [];
                                                if (!fam.includes(s.dept) && !fam.includes(s.maj)) return true;
                                            }
                                            if (filterMajor !== ''   && s.maj  !== filterMajor)   return true;
                                            if (filterYear  !== ''   && s.year !== filterYear)     return true;
                                            if (filterSection !== '' && s.sec  !== filterSection)  return true;
                                            return false;
                                        });
                                    })()
                                "
                                class="py-16 text-center text-slate-500 dark:text-slate-400 font-black text-sm uppercase tracking-widest">
                                <span x-show="search !== ''">No subjects match "<span class="text-indigo-600 dark:text-indigo-400" x-text="search"></span>"</span>
                                <span x-show="search === '' && hasActiveFilters">No subjects match the current filters</span>
                            </p>
                        </div>
                    @endif
                </div>

                <div
                    class="px-8 py-5 flex-shrink-0 border-t border-slate-200 dark:border-slate-800
                           flex items-center justify-between gap-4 bg-slate-50 dark:bg-slate-900 rounded-b-3xl">
                    <div
                        class="text-sm font-bold text-slate-500 dark:text-slate-400 leading-relaxed bg-white dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        @php $selectedCount = count($selectedSubjectIds); @endphp
                        <span class="font-black text-slate-800 dark:text-slate-200 text-base">
                            {{ $selectedCount }}
                        </span>
                        subject{{ $selectedCount !== 1 ? 's' : '' }} selected
                        @if($selectedWeeklyHours > 0)
                            <span class="mx-2 opacity-50">|</span>
                            <span
                                class="font-black tabular-nums text-base
                                       {{ $selectedWeeklyHours > $maxWeeklyHours ? 'text-red-500' : 'text-indigo-600 dark:text-indigo-400' }}">
                                {{ number_format($selectedWeeklyHours, 1) }}h / wk
                            </span>
                        @endif
                        @if($capacityError)
                            <span class="mx-2 opacity-50">|</span>
                            <span class="text-red-600 dark:text-red-400 font-black text-xs uppercase tracking-widest bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-md">🚫 Exceeds capacity</span>
                        @elseif($capacityWarning)
                            <span class="mx-2 opacity-50">|</span>
                            <span class="text-amber-600 dark:text-amber-400 font-black text-xs uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded-md">⚠️ Over capacity</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="closeAssignModal"
                            class="px-6 py-3 font-black text-slate-500 dark:text-slate-400
                                   hover:text-slate-800 dark:hover:text-slate-200 uppercase tracking-widest
                                   text-xs transition-colors rounded-xl hover:bg-slate-200 dark:hover:bg-slate-800">
                            Cancel
                        </button>

                        {{-- Save button: disabled when over capacity OR Livewire is loading --}}
                        <button
                            type="button"
                            wire:click="saveRoomAssignments"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-not-allowed transform-none"
                            wire:target="saveRoomAssignments"
                            @if($selectedWeeklyHours > $maxWeeklyHours) disabled @endif
                            class="flex items-center gap-2 px-8 py-3.5 text-white
                                   rounded-xl font-black shadow-lg transition-all uppercase
                                   text-xs tracking-widest disabled:opacity-50 disabled:cursor-not-allowed
                                   {{ $selectedWeeklyHours > $maxWeeklyHours
                                       ? 'bg-red-500 dark:bg-red-700 shadow-red-200 dark:shadow-none cursor-not-allowed'
                                       : 'bg-indigo-600 dark:bg-indigo-700 shadow-indigo-200 dark:shadow-none hover:bg-indigo-700 dark:hover:bg-indigo-600' }}">
                            <span wire:loading wire:target="saveRoomAssignments" class="mr-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                            <span wire:loading wire:target="saveRoomAssignments">Saving...</span>

                            <span wire:loading.remove wire:target="saveRoomAssignments" class="flex items-center">
                                @if($selectedWeeklyHours > $maxWeeklyHours)
                                    🚫 Capacity Exceeded
                                @else
                                    💾 Save Assignments
                                    @if($selectedCount > 0)
                                        <span class="ml-3 px-2.5 py-0.5 bg-white/20 text-white rounded-full text-xs font-black">
                                            {{ $selectedCount }}
                                        </span>
                                    @endif
                                @endif
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>