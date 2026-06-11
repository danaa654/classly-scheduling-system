<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">

    <div class="flex min-h-screen font-sans antialiased text-slate-900 dark:text-white"
         x-data="{ 
            open: @entangle('showModal'),
            bulkOpen: @entangle('bulkOpen'),
            confirmDelete: @entangle('confirmingDeletion')
         }">

        <main class="flex-1 flex flex-col overflow-hidden">
            {{-- Header Section --}}
            <header
                class="h-20 bg-white dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800
                       flex items-center justify-between px-8 shadow-sm shrink-0 backdrop-blur-xl
                       rounded-b-3xl transition-colors">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">
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
                                class="px-5 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                       rounded-xl text-sm font-black hover:bg-red-100 dark:hover:bg-red-900/30
                                       transition-all border border-red-200 dark:border-red-800 shadow-sm">
                                🗑️ Delete Selected ({{ count($selectedRooms) }})
                            </button>
                        @endif

                        <button
                            @click="bulkOpen = true"
                            class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200
                                   rounded-xl text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700
                                   transition-all flex items-center border border-slate-200 dark:border-slate-700">
                            <span class="mr-2 text-lg">📥</span> Bulk Import
                        </button>

                        <button
                            wire:click="openModal"
                            class="group relative px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm
                                   font-black shadow-md shadow-blue-200 dark:shadow-none overflow-hidden
                                   transition-all active:scale-95">
                            <span class="relative z-10">+ Add New Room</span>
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600
                                       opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                            </div>
                        </button>
                    </div>
                @endif
            </header>

            {{-- Content Body --}}
            <div class="p-6 pb-4 overflow-y-auto space-y-5 custom-scrollbar">

                {{-- Search & Filter Bar --}}
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-4
                           bg-white dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200
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
                        <span class="text-xs font-black uppercase text-slate-500 dark:text-slate-400 tracking-widest">
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
                                <td class="px-5 py-4 text-center align-middle">
                                    @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedRooms"
                                            value="{{ $room->id }}"
                                            class="rounded border-slate-300 dark:border-slate-600 bg-transparent
                                                   text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div class="flex flex-col leading-tight space-y-1">
                                        <span
                                            class="font-black text-slate-800 dark:text-slate-100 text-base lg:text-lg uppercase tracking-tight">
                                            {{ $room->room_name }}
                                        </span>
                                        <span
                                            class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                                            Main Campus Building
                                        </span>

                                        {{-- ══════════════════════════════════════════════════════════
                                             WEEKLY UTILISATION INDICATOR
                                             Max institutional cap: 60 hrs / week
                                             Source: subjects preferred-assigned via preferred_room_id,
                                                     scoped to the active academic term.
                                             ══════════════════════════════════════════════════════════ --}}
                                        @php
                                            // Sum (duration_hours × meetings_per_week) across all
                                            // active-term subjects preferred-assigned to this room.
                                            $roomTotalHours = $room->subjects->sum(
                                                fn ($s) => (float) $s->duration_hours
                                                         * max(1, (int) ($s->meetings_per_week ?? 1))
                                            );

                                            // Cap display at 100 %; over-capacity is surfaced via
                                            // the "OVER CAPACITY" label and the pulsing dot instead.
                                            $roomUtilPct    = min(100, (int) round(($roomTotalHours / 60) * 100));
                                            $roomOverCap    = $roomTotalHours > 60;

                                            // Indicator dot: green / amber / red / red+pulse
                                            $roomDotClass   = match (true) {
                                                $roomOverCap            => 'bg-red-500 animate-pulse',
                                                $roomUtilPct >= 86      => 'bg-red-500',
                                                $roomUtilPct >= 61      => 'bg-amber-500',
                                                default                 => 'bg-green-500',
                                            };

                                            // Progress bar fill colour (no pulse on bar — keeps it calm)
                                            $roomBarClass   = match (true) {
                                                $roomUtilPct >= 86      => 'bg-red-500',
                                                $roomUtilPct >= 61      => 'bg-amber-500',
                                                default                 => 'bg-green-500',
                                            };

                                            // Label text colour
                                            $roomTextClass  = match (true) {
                                                $roomUtilPct >= 86      => 'text-red-600 dark:text-red-400',
                                                $roomUtilPct >= 61      => 'text-amber-600 dark:text-amber-400',
                                                default                 => 'text-green-600 dark:text-green-500',
                                            };
                                        @endphp

                                        {{-- ─── CLICKABLE UTILISATION TRIGGER ────────────────────── --}}
                                        <button
                                            wire:click="toggleRoomDetails({{ $room->id }})"
                                            class="mt-2 w-full text-left focus:outline-none cursor-pointer group/util"
                                            title="{{ in_array($room->id, $expandedRooms) ? 'Collapse subject list' : 'Show allocated subjects' }}">

                                            {{-- Indicator dot + badge text + chevron --}}
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-block w-2 h-2 rounded-full shrink-0 {{ $roomDotClass }}"></span>
                                                <span class="text-[10px] font-black uppercase tracking-widest {{ $roomTextClass }}">
                                                    {{ number_format($roomTotalHours, 1) }}&nbsp;/ 60 hrs
                                                    @if ($roomOverCap)
                                                        &middot; OVER CAPACITY
                                                    @else
                                                        ({{ $roomUtilPct }}%)
                                                    @endif
                                                </span>
                                                {{-- Rotating chevron shows expand / collapse state --}}
                                                <svg class="ml-auto w-3 h-3 text-slate-400 dark:text-slate-500 transition-transform duration-200 {{ in_array($room->id, $expandedRooms) ? 'rotate-180' : '' }}"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>

                                            {{-- Micro progress bar — subtly widens on hover --}}
                                            <div class="mt-1.5 w-full max-w-[180px] h-1.5 bg-slate-100 dark:bg-slate-700/60 rounded-full overflow-hidden group-hover/util:max-w-[220px] transition-all duration-300">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $roomBarClass }}"
                                                     style="width: {{ $roomUtilPct }}%">
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center align-middle">
                                    <span
                                        class="px-4 py-1.5 text-xs uppercase font-black tracking-tight border rounded-xl
                                               {{ strtoupper($room->type) === 'LAB'
                                                    ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800'
                                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800' }}">
                                        {{ strtoupper($room->type) === 'LECTURE' ? 'Lecture' : 'Lab' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-middle">
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
                                <td class="px-5 py-4 align-middle">
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
                                                class="text-xs font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mt-1">
                                                {{ $room->department_owner ?: 'Shared' }} {{ $room->is_specialized ? '/ Specialized' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                    {{-- Separated Assignments Column --}}
                                    <td class="px-5 py-4 text-center align-middle">
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

                                    {{-- Standard Actions Column --}}
                                    <td class="px-5 py-4 text-right align-middle space-x-2 whitespace-nowrap">
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
                                    {{-- View-only Assignments Column for Department Officials --}}
                                    <td class="px-5 py-4 text-center align-middle">
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

                        {{-- ── INLINE SUBJECT ACCORDION ──────────────────────────────── --}}
                        @if(in_array($room->id, $expandedRooms))
                            <tr wire:key="room-expand-{{ $room->id }}" class="border-0">
                                <td colspan="{{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 7 : (in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']) ? 6 : 5) }}"
                                    class="px-6 pb-5 pt-0">

                                    <div class="room-expand-anim ml-4 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900/70 shadow-sm">

                                        {{-- Strip header --}}
                                        <div class="flex items-center justify-between px-4 py-2.5 bg-blue-900 dark:bg-blue-950 border-b border-blue-800 dark:border-blue-900">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-100 dark:text-blue-200">
                                                📋 Allocated Subjects
                                            </span>
                                            @if($room->subjects->isNotEmpty())
                                                <span class="text-[10px] font-bold text-blue-300 dark:text-blue-400 uppercase tracking-widest">
                                                    {{ $room->subjects->count() }} subject{{ $room->subjects->count() !== 1 ? 's' : '' }}
                                                    &nbsp;·&nbsp;
                                                    {{ number_format($roomTotalHours, 1) }}h / wk total
                                                </span>
                                            @endif
                                        </div>

                                        @if($room->subjects->isEmpty())
                                            {{-- Empty state --}}
                                            <div class="py-6 px-4 text-center">
                                                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    No subjects allocated to this room yet.
                                                </p>
                                            </div>
                                        @else
                                            {{-- Subject rows --}}
                                            @foreach($room->subjects as $subject)
                                                @php
                                                    $subjectWklyHrs = round(
                                                        (float) $subject->duration_hours * max(1, (int) ($subject->meetings_per_week ?? 1)),
                                                        1
                                                    );
                                                @endphp
                                                <div wire:key="expand-subj-{{ $subject->id }}"
                                                     class="flex items-center gap-3 px-4 py-3
                                                            {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700/50' : '' }}
                                                            hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-colors">

                                                    {{-- EDP code --}}
                                                    <span class="font-black font-mono text-xs text-slate-500 dark:text-slate-400 uppercase tracking-widest w-28 shrink-0">
                                                        {{ $subject->edp_code }}
                                                    </span>

                                                    {{-- Subject code --}}
                                                    <span class="font-black text-xs text-slate-800 dark:text-slate-100 uppercase tracking-tight w-24 shrink-0">
                                                        {{ $subject->subject_code }}
                                                    </span>

                                                    {{-- Description --}}
                                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium flex-1 truncate">
                                                        {{ $subject->description }}
                                                    </span>

                                                    {{-- Section badge --}}
                                                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-widest border border-indigo-200 dark:border-indigo-800 shrink-0">
                                                        Sec&nbsp;{{ $subject->section ?? '—' }}
                                                    </span>

                                                    {{-- Major / Minor badge --}}
                                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shrink-0
                                                        {{ strtolower($subject->type) === 'minor'
                                                            ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                                                            : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' }}">
                                                        {{ $subject->type }}
                                                    </span>

                                                    {{-- Weekly hours --}}
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
                                <td colspan="{{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 7 : (in_array(auth()->user()->role, ['dean', 'oic', 'associate_dean']) ? 6 : 5) }}" class="px-8 py-24 text-center">
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
                <div class="mt-5 mb-6 flex justify-center">
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
                    {{-- Room Identifier --}}
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
                        {{-- Room Type --}}
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
                        {{-- Capacity --}}
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

                    {{-- Floor and Specialization --}}
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

                    {{-- Actions --}}
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-md"
            x-cloak
            x-transition>
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-3xl p-8 md:p-10 shadow-2xl border
                       border-slate-200 dark:border-slate-800 transition-colors"
                @click.away="bulkOpen = false">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight uppercase">
                        Batch Room Import
                    </h3>
                    <button @click="bulkOpen = false" class="text-slate-400 hover:text-red-500 text-2xl font-bold transition-colors">✕</button>
                </div>
                
                <p
                    class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium italic">
                    CSV Required Headers:
                    <span class="text-slate-700 dark:text-slate-300 font-bold italic block mt-1 bg-slate-50 dark:bg-slate-800 p-2 rounded max-w-max">
                        room_name, room_type, specialization, capacity, floor
                    </span>
                </p>

                <div class="space-y-6">
                    <div
                        class="group border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-2xl p-8
                               flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-800/30
                               hover:bg-white dark:hover:bg-slate-800/50 hover:border-blue-500 transition-all
                               cursor-pointer relative shadow-inner"
                        wire:loading.class="opacity-50 pointer-events-none"
                        wire:target="importFile">

                        <input type="file" wire:model.live="importFile" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">

                        <div wire:loading wire:target="importFile" class="text-center">
                            <div
                                class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3">
                            </div>
                            <span
                                class="text-xs font-black text-blue-600 uppercase tracking-widest">
                                Analyzing CSV Structure...
                            </span>
                        </div>

                        <div wire:loading.remove wire:target="importFile" class="text-center">
                            <span class="text-5xl mb-4 transition-transform group-hover:scale-110 block">📊</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                {{ $importFile ? $importFile->getClientOriginalName() : 'Drop room CSV here or click to browse' }}
                            </span>
                        </div>
                    </div>

                    @if(count($importPreview) > 0)
                        <div class="mt-4 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm">
                            <div class="max-h-64 overflow-y-auto custom-scrollbar bg-white dark:bg-slate-800/50">
                                <table class="w-full text-left border-collapse">
                                    <thead
                                        class="bg-slate-100 dark:bg-slate-800 sticky top-0 backdrop-blur-sm z-10 border-b border-slate-200 dark:border-slate-700">
                                    <tr
                                        class="text-xs uppercase font-black text-slate-500 dark:text-slate-400 tracking-wider">
                                        <th class="px-4 py-3">Room Name</th>
                                        <th class="px-4 py-3">Type</th>
                                        <th class="px-4 py-3">Floor</th>
                                        <th class="px-4 py-3 text-right">Status</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm">
                                    @foreach($importPreview as $preview)
                                        <tr class="font-bold dark:hover:bg-slate-700/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-800 dark:text-slate-200">
                                                {{ $preview['room_name'] }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-slate-500 dark:text-slate-400 italic font-medium uppercase text-xs">
                                                {{ $preview['type'] }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                {{ $preview['floor'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span
                                                    title="{{ $preview['errors'] ?? '' }}"
                                                    class="px-3 py-1 rounded-full text-[10px] uppercase font-black tracking-wider
                                                           {{ $preview['status'] === 'INVALID'
                                                                ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                                                : ($preview['status'] === 'DUPLICATE'
                                                                    ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                                                    : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400') }}">
                                                    {{ $preview['status'] }}
                                                </span>
                                                @if(($preview['status'] ?? '') === 'INVALID')
                                                    <div
                                                        class="mt-1.5 text-[10px] font-bold text-amber-700 dark:text-amber-300">
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

                        <button
                            wire:click="processImport"
                            wire:loading.attr="disabled"
                            class="w-full mt-4 py-4 bg-blue-600 dark:bg-blue-700 text-white rounded-2xl font-black
                                   uppercase tracking-widest hover:bg-blue-800 dark:hover:bg-blue-600
                                   shadow-lg shadow-blue-200 dark:shadow-none transition-all text-xs">
                            <span wire:loading.remove wire:target="processImport">
                                Confirm & Import Valid Rooms
                            </span>
                            <span wire:loading wire:target="processImport">
                                Saving to Database...
                            </span>
                        </button>
                    @endif

                    <div class="flex justify-center pt-2">
                        <button
                            type="button"
                            @click="bulkOpen = false; $wire.reset(['importFile', 'importPreview'])"
                            class="font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest
                                   text-xs hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
                            Close Importer
                        </button>
                    </div>
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

        {{-- ── ACCORDION ENTRY ANIMATION ─────────────────────────────────── --}}
        <style>
            .room-expand-anim {
                animation: roomExpandIn 0.18s ease-out both;
            }
            @keyframes roomExpandIn {
                from { opacity: 0; transform: translateY(-5px); }
                to   { opacity: 1; transform: translateY(0);    }
            }
        </style>

        {{-- --- TOAST NOTIFICATION SCRIPT --- --}}
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

                {{-- HEADER --}}
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

                    {{-- Capacity meter --}}
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                Weekly Room Load
                            </span>
                            <span
                                class="text-sm font-black tabular-nums
                                       {{ $selectedWeeklyHours > $maxWeeklyHours ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ number_format($selectedWeeklyHours, 1) }}h
                                <span class="text-slate-400 dark:text-slate-600">
                                    / {{ $maxWeeklyHours }}h
                                </span>
                            </span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            @php
                                $pct = $maxWeeklyHours > 0 ? min(100, ($selectedWeeklyHours / $maxWeeklyHours) * 100) : 0;
                                $barColor = $selectedWeeklyHours > $maxWeeklyHours
                                    ? 'bg-red-500'
                                    : ($selectedWeeklyHours > $maxWeeklyHours * 0.8 ? 'bg-amber-400' : 'bg-indigo-500');
                            @endphp
                            <div
                                class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                                style="width: {{ $pct }}%">
                            </div>
                        </div>
                    </div>

                    @if($capacityWarning)
                        <div
                            class="flex items-start gap-3 px-5 py-4 bg-amber-50 dark:bg-amber-900/20
                                   border border-amber-200 dark:border-amber-700 rounded-xl mt-2">
                            <span
                                class="text-amber-500 dark:text-amber-400 text-xl flex-shrink-0">⚠️</span>
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

                    {{-- ── Subject Filters ─────────────────────────────────── --}}
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 self-center mr-1">
                            Filter:
                        </span>

                        {{-- Department --}}
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

                        {{-- Major — options are driven by selected department --}}
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

                        {{-- Year Level --}}
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

                        {{-- Section --}}
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

                        {{-- Clear all active filters --}}
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

                {{-- SCROLLABLE SUBJECT LIST --}}
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
                                Loading subjects…
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
                                                .querySelectorAll('[data-subject-row]:not([style*=''display: none'']) input[type=checkbox]');
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
                                    // True when this subject already prefers a DIFFERENT room —
                                    // used to mute the row and show the amber warning badge.
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
                                        // Base — always present
                                        'grid items-center gap-4 px-4 py-3.5 mb-1 rounded-2xl cursor-pointer select-none transition-all group',
                                        // Default (unclaimed) row
                                        'border border-transparent hover:bg-indigo-50/80 dark:hover:bg-indigo-900/20 hover:border-indigo-200 dark:hover:border-indigo-800' => !$claimedByOtherRoom,
                                        // Claimed-by-other-room row — amber tint so it reads as "caution"
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
                                            @if(in_array((string)$subject['id'], $selectedSubjectIds))
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                                                           bg-indigo-100 dark:bg-indigo-900/40
                                                           text-indigo-700 dark:text-indigo-300">
                                                    ✓ selected
                                                </span>
                                            @endif
                                            @if($claimedByOtherRoom)
                                                {{-- Guardrail badge: subject already preferred-bound to another room.
                                                     Row stays clickable so admins can consciously reassign it. --}}
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

                {{-- FOOTER --}}
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
                        @if($capacityWarning)
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

                        <button
                            type="button"
                            wire:click="saveRoomAssignments"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-not-allowed transform-none"
                            wire:target="saveRoomAssignments"
                            class="flex items-center gap-2 px-8 py-3.5 bg-indigo-600 dark:bg-indigo-700 text-white
                                   rounded-xl font-black shadow-lg shadow-indigo-200 dark:shadow-none
                                   hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all uppercase
                                   text-xs tracking-widest disabled:opacity-70 disabled:cursor-not-allowed">
                            <span wire:loading wire:target="saveRoomAssignments" class="mr-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                            <span wire:loading wire:target="saveRoomAssignments">Saving…</span>

                            <span wire:loading.remove wire:target="saveRoomAssignments" class="flex items-center">
                                💾 Save Assignments
                                @if($selectedCount > 0)
                                    <span class="ml-3 px-2.5 py-0.5 bg-white/20 text-white rounded-full text-xs font-black">
                                        {{ $selectedCount }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>