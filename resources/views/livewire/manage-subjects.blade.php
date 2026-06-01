<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"
     x-data="{
        open: @entangle('showModal'),
        bulkOpen: @entangle('bulkOpen'),
        prefOpen: false,
        prefSubjectId: null,
        prefFacultyId: @entangle('assignFacultyId'),
        prefRoomId: @entangle('assignRoomId'),
        prefLoading: false,
        prefFaculties: [],
        prefRooms: [],
        prefHint: { faculty_hint: '', room_hint: '', subject_info: {} },
        prefSearch: '',
        prefRoomSearch: '',
        prefRoomFilter: 'all',

        openPrefModal(subjectId, currentFacultyId, currentRoomId) {
            this.prefSubjectId = subjectId;
            this.prefFacultyId = currentFacultyId;
            this.prefRoomId = currentRoomId;
            this.prefSearch = '';
            this.prefRoomSearch = '';
            this.prefRoomFilter = 'all';
            this.prefLoading = true;
            this.prefOpen = true;

            Promise.all([
                $wire.getEligibleFacultiesForSubject(subjectId),
                $wire.getEligibleRoomsForSubject(subjectId),
                $wire.getSmartHintForSubject(subjectId),
            ]).then(([faculties, rooms, hint]) => {
                this.prefFaculties = faculties;
                this.prefRooms = rooms;
                this.prefHint = hint;
                this.prefLoading = false;
            });
        },

        get filteredFaculties() {
            if (!this.prefSearch) return this.prefFaculties;
            const q = this.prefSearch.toLowerCase();
            return this.prefFaculties.filter(f =>
                f.full_name.toLowerCase().includes(q) ||
                (f.department || '').toLowerCase().includes(q) ||
                (f.scope_label || '').toLowerCase().includes(q)
            );
        },

        get filteredRooms() {
            let rooms = this.prefRooms;
            if (this.prefRoomFilter !== 'all') {
                rooms = rooms.filter(r => r.tier === this.prefRoomFilter);
            }
            if (this.prefRoomSearch) {
                const q = this.prefRoomSearch.toLowerCase();
                rooms = rooms.filter(r =>
                    r.room_name.toLowerCase().includes(q) ||
                    (r.type || '').toLowerCase().includes(q) ||
                    (r.specialization || '').toLowerCase().includes(q)
                );
            }
            return rooms;
        },

        get selectedFacultyName() {
            if (!this.prefFacultyId) return null;
            const f = this.prefFaculties.find(f => f.id == this.prefFacultyId);
            return f ? f.full_name : null;
        },

        get selectedRoomName() {
            if (!this.prefRoomId) return null;
            const r = this.prefRooms.find(r => r.id == this.prefRoomId);
            return r ? r.room_name : null;
        },
     }">
    <main class="flex-1 flex flex-col h-screen overflow-hidden">

        {{-- ============================================================
             COMPACT TOP HEADER CARD
             ============================================================ --}}
        <header class="mx-auto mt-3 h-14 w-[97%] max-w-[1600px] bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-6 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">

            {{-- Left: Title + Badges --}}
            <div class="flex items-center gap-3">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">
                    Manage Subjects
                </h2>
                <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                    {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }} {{ $activePeriod['school_year'] }}
                </span>
                <span class="rounded-lg px-2 py-0.5 text-[9px] font-black uppercase tracking-widest {{ $catalogMode === 'archive' ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                    {{ $catalogMode === 'archive' ? 'Archive History' : 'Active Workspace' }}
                </span>
            </div>

            {{-- Right: Bulk actions + Buttons --}}
            <div class="flex items-center gap-2">
                @if($catalogMode === 'active' && count($selectedSubjects) > 0)
                    <button type="button" x-data @click.stop="$dispatch('open-bulk-duplicate-modal')"
                        wire:loading.attr="disabled" wire:key="bulk-duplicate-btn"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/25 text-indigo-700 dark:text-indigo-300 rounded-2xl font-extrabold text-[10px] uppercase tracking-widest hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition border border-indigo-200 dark:border-indigo-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                            <path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2H7a4 4 0 00-4 4v4H5V5z" />
                        </svg>
                        Duplicate ({{ count($selectedSubjects) }})
                    </button>
                    <button type="button" x-data @click.stop="$dispatch('open-delete-modal')" wire:key="bulk-delete-btn"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/25 text-red-700 dark:text-red-300 rounded-2xl font-extrabold text-[10px] uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-900/50 transition border border-red-200 dark:border-red-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Delete ({{ count($selectedSubjects) }})
                    </button>
                @endif
                @if($catalogMode === 'active')
                    <button @click.prevent="bulkOpen = true"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 rounded-2xl font-extrabold text-[10px] uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-sm border border-slate-200 dark:border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Bulk Import
                    </button>
                    <button wire:click="openModal"
                        class="flex items-center gap-1.5 px-4 py-1.5 bg-blue-700 dark:bg-indigo-700 text-white rounded-2xl font-extrabold shadow-md shadow-blue-600/40 text-[10px] uppercase hover:scale-105 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Subject
                    </button>
                @endif
            </div>
        </header>

        {{-- ============================================================
             MAIN SCROLLABLE AREA
             ============================================================ --}}
        <div class="flex-1 overflow-y-auto p-3 lg:p-4 custom-main-scrollbar">
            <div class="grid grid-cols-12 gap-3 items-start max-w-[1600px] mx-auto">

                @php
                    $userRole   = strtolower(auth()->user()->role);
                    $powerRoles = ['admin', 'registrar', 'associate_dean'];
                    $isPowerUser = in_array($userRole, $powerRoles);
                @endphp

                {{-- LEFT COLUMN --}}
                <div class="col-span-12 lg:col-span-9 space-y-3">

                    {{-- FILTER TOOLBAR --}}
                    <div class="bg-white dark:bg-slate-900 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex flex-wrap gap-2 items-end">
                            <div class="flex-1 min-w-[130px]">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Workspace</label>
                                <select wire:model.live="catalogMode" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="active">Current</option>
                                    <option value="archive">Archived</option>
                                </select>
                            </div>
                            @if($catalogMode === 'archive')
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Archived Batch</label>
                                    <select wire:model.live="selectedArchiveBatch" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                        <option value="">Select Archive</option>
                                        @foreach($archiveOptions as $archive)
                                            <option value="{{ $archive->archive_batch_id }}">
                                                {{ $archive->archive_batch_id }} – {{ $archive->semester_name ?: \App\Models\Setting::semesterDisplayName($archive->semester, $archive->school_year) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="flex-1 min-w-[110px] relative">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Department</label>
                                <select wire:model.live="selectedDept"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all {{ !$isPowerUser ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    {{ !$isPowerUser ? 'disabled' : '' }}>
                                    @if($isPowerUser)
                                        <option value="">All Dept</option>
                                        <option value="CCS">CCS</option>
                                        <option value="SHTM">SHTM</option>
                                        <option value="COC">COC</option>
                                        <option value="CTE">CTE</option>
                                    @else
                                        <option value="{{ auth()->user()->department }}">{{ auth()->user()->department }}</option>
                                    @endif
                                </select>
                                @if(!$isPowerUser)
                                    <span class="absolute -top-1.5 right-2 bg-white dark:bg-slate-900 px-1 text-[8px] font-black text-amber-500 uppercase italic">Locked</span>
                                @endif
                            </div>
                            <div class="min-w-[90px]">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Year</label>
                                <select wire:model.live="selectedYear" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="">All</option>
                                    <option value="1">1st</option>
                                    <option value="2">2nd</option>
                                    <option value="3">3rd</option>
                                    <option value="4">4th</option>
                                </select>
                            </div>
                            <div class="min-w-[90px]">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Section</label>
                                <select wire:model.live="selectedSection" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="">All</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec }}">{{ $sec }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-[110px]">
                                <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Subject Type</label>
                                <select wire:model.live="selectedMajor" wire:key="major-filter-{{ $selectedDept }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-semibold text-xs uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="">All Majors</option>
                                    @if($selectedDept === 'SHTM')
                                        <option value="HM">Hospitality (HM)</option>
                                        <option value="TM">Tourism (TM)</option>
                                    @elseif($selectedDept === 'CCS')
                                        <option value="IT">Information Technology (IT)</option>
                                        <option value="ACT">Assistive Computer Technology (ACT)</option>
                                    @elseif($selectedDept === 'COC')
                                        <option value="FB">Forensic Biology (FB)</option>
                                        <option value="LD">Lie Detection (LD)</option>
                                        <option value="QD">Questioned Documents (QD)</option>
                                    @elseif($selectedDept === 'CTE')
                                        <option value="ED">Education (ED)</option>
                                    @else
                                        <option value="" disabled>Select dept first</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black uppercase tracking-widest text-transparent mb-1">.</label>
                                <button wire:click="$set('search', ''); $set('selectedSection', ''); $set('selectedYear', ''); $set('selectedMajor', '')"
                                    class="flex items-center gap-1 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- SEARCH BAR --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="flex items-center px-4 focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-inset rounded-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" wire:model.live="search"
                                placeholder="Search by code, subject, faculty, room, or EDP..."
                                class="w-full bg-transparent border-none focus:ring-0 font-semibold text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 py-3">
                            @if($search)
                                <button wire:click="$set('search', '')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- SUBJECTS TABLE --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[900px]">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 tracking-wide border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="pl-4 pr-2 py-3 w-8">
                                            <input type="checkbox" wire:model.live="selectAll" @disabled($catalogMode !== 'active')
                                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                        </th>
                                        <th class="px-3 py-3">EDP Code</th>
                                        <th class="px-3 py-3">Subject</th>
                                        <th class="px-2 py-3">Section</th>
                                        <th class="px-2 py-3">Year</th>
                                        <th class="px-2 py-3">Duration</th>
                                        <th class="px-2 py-3">Type</th>
                                        <th class="px-3 py-3">Preferences</th>
                                        <th class="px-3 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                    @forelse($subjects as $subject)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors {{ in_array($subject->id, $selectedSubjects) ? 'bg-blue-50/50 dark:bg-indigo-900/20' : '' }} align-middle">

                                        {{-- Checkbox --}}
                                        <td class="pl-4 pr-2 py-3.5">
                                            <input type="checkbox" wire:model.live="selectedSubjects" value="{{ $subject->id }}"
                                                @disabled($catalogMode !== 'active')
                                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                        </td>

                                        {{-- EDP Code --}}
                                        <td class="px-3 py-3.5">
                                            <span class="font-extrabold text-blue-700 dark:text-indigo-400 text-xs uppercase tracking-wide">{{ $subject->edp_code }}</span>
                                        </td>

                                        {{-- Subject --}}
                                        <td class="px-3 py-3.5 max-w-[180px]">
                                            <p class="font-bold uppercase text-xs text-slate-900 dark:text-slate-200 truncate">{{ $subject->subject_code }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate mt-0.5">{{ $subject->description }}</p>
                                        </td>

                                        {{-- Section --}}
                                        <td class="px-2 py-3.5">
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700/80 rounded-md text-[11px] font-bold border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300">
                                                {{ $subject->section }}
                                            </span>
                                        </td>

                                        {{-- Year --}}
                                        <td class="px-2 py-3.5 text-xs font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                            Y{{ $subject->year_level }}
                                        </td>

                                        {{-- Duration --}}
                                        <td class="px-2 py-3.5 text-xs font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                            {{ $subject->duration_hours }}h
                                        </td>

                                        {{-- Type --}}
                                        <td class="px-2 py-3.5">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="inline-block px-2 py-0.5 border rounded-md text-[10px] font-black uppercase {{ strtolower($subject->type) === 'major' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800' }}">
                                                    {{ $subject->type }}
                                                </span>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $subject->units }}u</span>
                                            </div>
                                        </td>

                                        {{-- ============================================================
                                             COMBINED PREFERENCES COLUMN
                                             ============================================================ --}}
                                        <td class="px-3 py-3.5 min-w-[200px]">
                                            <div class="flex flex-col gap-1">

                                                {{-- Faculty preference pill --}}
                                                @if($subject->preferredFaculty)
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-[10px] font-bold text-blue-700 dark:text-blue-300 max-w-[170px]">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                                            </svg>
                                                            <span class="truncate">{{ $subject->preferredFaculty->full_name }}</span>
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Room preference pill --}}
                                                @if($subject->preferredRoom)
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 text-[10px] font-bold text-purple-700 dark:text-purple-300 max-w-[170px]">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                                            </svg>
                                                            <span class="truncate">{{ $subject->preferredRoom->room_name }}</span>
                                                        </span>
                                                    </div>
                                                @endif

                                                @if(!$subject->preferredFaculty && !$subject->preferredRoom)
                                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 italic">No preference set</span>
                                                @endif

                                                {{-- Edit Preferences button --}}
                                                @if($catalogMode === 'active')
                                                    <button
                                                        x-on:click="openPrefModal({{ $subject->id }}, {{ $subject->preferred_faculty_id ?? 'null' }}, {{ $subject->preferred_room_id ?? 'null' }})"
                                                        class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition w-fit mt-0.5 group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                        Assign Preferences
                                                    </button>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-3 py-3.5 text-right whitespace-nowrap">
                                            @if($catalogMode === 'active')
                                                <button wire:click="editSubject({{ $subject->id }})" title="Edit"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition border border-blue-200 dark:border-blue-800 mr-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmDeleteSubject({{ $subject->id }})" title="Delete"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition border border-red-200 dark:border-red-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="text-slate-400 font-extrabold text-[10px] uppercase">Archived</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                            <div class="flex flex-col items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                <p class="font-semibold uppercase text-xs tracking-wider">
                                                    {{ $catalogMode === 'archive' ? 'Select an archive batch or no archived subjects found' : 'No active subjects for the current semester' }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($subjects->hasPages())
                            <div class="py-3 px-4 border-t border-slate-100 dark:border-slate-800">
                                {{ $subjects->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RIGHT COLUMN: Recent Activity --}}
                <aside class="col-span-12 lg:col-span-3">
                    <div class="sticky top-4">
                        <div class="bg-white dark:bg-[#071024]/90 backdrop-blur-xl rounded-2xl border border-slate-200 dark:border-slate-800 shadow-lg overflow-hidden">
                            <div class="px-4 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-white">Recent Activity</h3>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Live department updates</p>
                                </div>
                                <div class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse shadow-md shadow-emerald-500/40"></div>
                            </div>
                            <div class="h-[calc(100vh-10rem)] max-h-[640px] overflow-y-auto px-3.5 py-4 space-y-3.5 custom-scrollbar relative">
                                <div class="absolute top-0 bottom-0 left-[26px] w-px bg-gradient-to-b from-transparent via-slate-200 dark:via-slate-700/70 to-transparent pointer-events-none"></div>
                                @forelse($activities as $activity)
                                @php
                                    $action = strtolower($activity->action ?? '');
                                    [$dotColor, $badgeColor, $actionLabel] = match(true) {
                                        str_contains($action, 'import')   => ['bg-emerald-500 shadow-emerald-500/40',   'text-emerald-700 bg-emerald-50 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-900/20 dark:border-emerald-800',   'Import'],
                                        str_contains($action, 'add') || str_contains($action, 'creat') => ['bg-blue-500 shadow-blue-500/40', 'text-blue-700 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-900/20 dark:border-blue-800', 'Add'],
                                        str_contains($action, 'edit') || str_contains($action, 'updat') => ['bg-amber-500 shadow-amber-500/40', 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-400 dark:bg-amber-900/20 dark:border-amber-800', 'Edit'],
                                        str_contains($action, 'duplic')   => ['bg-purple-500 shadow-purple-500/40',      'text-purple-700 bg-purple-50 border-purple-200 dark:text-purple-400 dark:bg-purple-900/20 dark:border-purple-800',       'Duplicate'],
                                        str_contains($action, 'delet') || str_contains($action, 'remov') => ['bg-red-500 shadow-red-500/40', 'text-red-700 bg-red-50 border-red-200 dark:text-red-400 dark:bg-red-900/20 dark:border-red-800', 'Delete'],
                                        default => ['bg-slate-400 shadow-slate-400/40', 'text-slate-600 bg-slate-50 border-slate-200 dark:text-slate-400 dark:bg-slate-800 dark:border-slate-700', 'Action'],
                                    };
                                    $role = strtoupper($activity->user->role ?? 'USER');
                                    $name = $activity->user->name ?? 'Unknown';
                                @endphp
                                <div class="relative flex gap-2.5 group">
                                    <div class="relative z-10 mt-1 shrink-0">
                                        <div class="h-4 w-4 rounded-full border-2 border-white dark:border-[#071024] {{ $dotColor }} shadow-sm"></div>
                                    </div>
                                    <div class="flex-1 rounded-xl p-3 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200 group-hover:shadow-sm">
                                        <div class="flex items-center gap-1.5 flex-wrap mb-1.5">
                                            <span class="inline-block px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">{{ $role }}</span>
                                            <span class="inline-block px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider border {{ $badgeColor }}">{{ $actionLabel }}</span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-snug truncate">{{ $name }}</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed line-clamp-2">{{ $activity->description }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 font-medium">{{ $activity->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                @empty
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">No Recent Activity</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </main>

    {{-- ================================================================
         SMART COMBINED PREFERENCES MODAL
         ================================================================ --}}
    <div
        x-show="prefOpen"
        x-cloak
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-slate-900 w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[92vh]"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            @click.away="prefOpen = false">

            {{-- ── MODAL HEADER ── --}}
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900 dark:to-slate-800/50 flex items-start justify-between shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="h-7 w-7 rounded-lg bg-blue-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-black uppercase tracking-wide text-slate-800 dark:text-white">Assign Preferences</h3>
                    </div>
                    <p class="text-xs text-slate-400 ml-9">Smart-filtered faculty and room options based on subject type and department</p>
                </div>
                <button @click="prefOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ── SUBJECT INFO BADGE ── --}}
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 shrink-0">
                <template x-if="prefHint.subject_info && prefHint.subject_info.subject_code">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Configuring:</span>
                        <span class="font-extrabold text-xs text-blue-700 dark:text-blue-300 uppercase" x-text="prefHint.subject_info.subject_code"></span>
                        <span class="text-[10px] text-slate-400" x-text="'— ' + (prefHint.subject_info.description || '')"></span>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black uppercase"
                            :class="prefHint.subject_info.type === 'Major' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                            x-text="prefHint.subject_info.type || 'Major'">
                        </span>
                        <span class="inline-block px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-[9px] font-bold text-slate-600 dark:text-slate-300"
                            x-text="(prefHint.subject_info.major || '') + ' / ' + (prefHint.subject_info.department || '')">
                        </span>
                        <template x-if="prefHint.subject_info.requires_lab">
                            <span class="inline-block px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/20 text-[9px] font-black text-orange-700 dark:text-orange-400 uppercase">⚗️ Requires Lab</span>
                        </template>
                    </div>
                </template>
            </div>

            {{-- ── LOADING STATE ── --}}
            <div x-show="prefLoading" class="flex items-center justify-center py-16 shrink-0">
                <div class="flex flex-col items-center gap-3">
                    <div class="relative h-10 w-10">
                        <div class="absolute inset-0 rounded-full border-4 border-blue-100 dark:border-blue-900/30"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide animate-pulse">Filtering eligible options…</p>
                </div>
            </div>

            {{-- ── MAIN SCROLLABLE CONTENT ── --}}
            <div x-show="!prefLoading" class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800 min-h-0">

                    {{-- ========== LEFT: FACULTY PANEL ========== --}}
                    <div class="flex flex-col">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-blue-50/50 dark:bg-blue-900/10">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="h-6 w-6 rounded-md bg-blue-600 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">Preferred Faculty</span>
                            </div>
                            {{-- Smart hint --}}
                            <p class="text-[10px] text-blue-700 dark:text-blue-300 font-semibold bg-blue-100 dark:bg-blue-900/30 rounded-lg px-2.5 py-1.5 leading-relaxed" x-text="prefHint.faculty_hint"></p>
                        </div>

                        {{-- Faculty search --}}
                        <div class="px-5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="relative">
                                <input type="text" x-model="prefSearch" placeholder="Search faculty…"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-7 pr-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Clear faculty --}}
                        <div class="px-5 py-2 border-b border-slate-100 dark:border-slate-800">
                            <button @click="prefFacultyId = null"
                                :class="!prefFacultyId ? 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black ring-2 ring-slate-400 dark:ring-slate-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition">
                                — No Preference (Clear)
                            </button>
                        </div>

                        {{-- Faculty list --}}
                        <div class="flex-1 overflow-y-auto px-5 py-2 space-y-1 max-h-72">
                            <template x-if="filteredFaculties.length === 0">
                                <div class="py-8 text-center">
                                    <p class="text-xs text-slate-400 font-semibold">No eligible faculty found</p>
                                </div>
                            </template>
                            <template x-for="faculty in filteredFaculties" :key="faculty.id">
                                <button @click="prefFacultyId = faculty.id"
                                    :class="prefFacultyId == faculty.id
                                        ? 'bg-blue-600 text-white border-blue-700 shadow-md shadow-blue-500/20'
                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/10'"
                                    class="w-full text-left px-3 py-2.5 rounded-xl border transition-all flex items-start gap-2.5">
                                    <div class="h-7 w-7 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                        :class="prefFacultyId == faculty.id ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-700'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="prefFacultyId == faculty.id ? 'text-white' : 'text-slate-500'" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold truncate leading-tight" x-text="faculty.full_name"></p>
                                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded"
                                                :class="prefFacultyId == faculty.id ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
                                                x-text="faculty.scope_label">
                                            </span>
                                            <span class="text-[9px] font-semibold opacity-75" x-text="faculty.employment_type"></span>
                                        </div>
                                    </div>
                                    <template x-if="prefFacultyId == faculty.id">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </template>
                                </button>
                            </template>
                        </div>

                        {{-- Faculty count --}}
                        <div class="px-5 py-2 border-t border-slate-100 dark:border-slate-800 shrink-0">
                            <p class="text-[10px] text-slate-400 font-semibold" x-text="filteredFaculties.length + ' eligible faculty shown'"></p>
                        </div>
                    </div>

                    {{-- ========== RIGHT: ROOM PANEL ========== --}}
                    <div class="flex flex-col">
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-purple-50/50 dark:bg-purple-900/10">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="h-6 w-6 rounded-md bg-purple-600 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">Preferred Room</span>
                            </div>
                            {{-- Smart hint --}}
                            <p class="text-[10px] text-purple-700 dark:text-purple-300 font-semibold bg-purple-100 dark:bg-purple-900/30 rounded-lg px-2.5 py-1.5 leading-relaxed" x-text="prefHint.room_hint"></p>
                        </div>

                        {{-- Room search + filter --}}
                        <div class="px-5 py-2.5 border-b border-slate-100 dark:border-slate-800 space-y-1.5">
                            <div class="relative">
                                <input type="text" x-model="prefRoomSearch" placeholder="Search rooms…"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-7 pr-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            {{-- Tier filter tabs --}}
                            <div class="flex gap-1">
                                <button @click="prefRoomFilter = 'all'"
                                    :class="prefRoomFilter === 'all' ? 'bg-slate-700 text-white dark:bg-slate-200 dark:text-slate-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                    class="flex-1 text-[9px] font-black uppercase tracking-wide py-1 rounded-md transition">All</button>
                                <button @click="prefRoomFilter = 'recommended'"
                                    :class="prefRoomFilter === 'recommended' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/10'"
                                    class="flex-1 text-[9px] font-black uppercase tracking-wide py-1 rounded-md transition">⭐ Best</button>
                                <button @click="prefRoomFilter = 'available'"
                                    :class="prefRoomFilter === 'available' ? 'bg-slate-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                    class="flex-1 text-[9px] font-black uppercase tracking-wide py-1 rounded-md transition">Other</button>
                            </div>
                        </div>

                        {{-- Clear room --}}
                        <div class="px-5 py-2 border-b border-slate-100 dark:border-slate-800">
                            <button @click="prefRoomId = null"
                                :class="!prefRoomId ? 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black ring-2 ring-slate-400 dark:ring-slate-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition">
                                — No Preference (Clear)
                            </button>
                        </div>

                        {{-- Room list --}}
                        <div class="flex-1 overflow-y-auto px-5 py-2 space-y-1 max-h-72">
                            <template x-if="filteredRooms.length === 0">
                                <div class="py-8 text-center">
                                    <p class="text-xs text-slate-400 font-semibold">No rooms match the filter</p>
                                </div>
                            </template>
                            <template x-for="room in filteredRooms" :key="room.id">
                                <button @click="prefRoomId = room.id"
                                    :class="prefRoomId == room.id
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-md shadow-purple-500/20'
                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/10'"
                                    class="w-full text-left px-3 py-2.5 rounded-xl border transition-all flex items-start gap-2.5">
                                    {{-- Room type icon --}}
                                    <div class="h-7 w-7 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                        :class="prefRoomId == room.id ? 'bg-white/20' : (room.tier === 'recommended' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700')">
                                        <template x-if="room.type && room.type.toLowerCase().includes('lab')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="prefRoomId == room.id ? 'text-white' : (room.tier === 'recommended' ? 'text-emerald-600' : 'text-slate-400')" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L7 4.414v3.758a1 1 0 01-.293.707l-4 4C.817 14.769 2.156 18 4.828 18h10.343c2.673 0 4.012-3.231 2.122-5.121l-4-4A1 1 0 0113 8.172V4.414l.707-.707A1 1 0 0013 2H7zm2 6.172V4h2v4.172a3 3 0 00.879 2.12l1.027 1.028a4 4 0 00-2.171.102l-.47.156a4 4 0 01-2.53 0l-.563-.187a1.993 1.993 0 00-.114-.035l1.063-1.063A3 3 0 009 8.172z" clip-rule="evenodd"/>
                                            </svg>
                                        </template>
                                        <template x-if="!room.type || !room.type.toLowerCase().includes('lab')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="prefRoomId == room.id ? 'text-white' : 'text-slate-400'" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold truncate leading-tight" x-text="room.room_name"></p>
                                            <template x-if="room.tier === 'recommended' && prefRoomId != room.id">
                                                <span class="shrink-0 text-[8px] font-black bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-1 py-0.5 rounded uppercase">Best</span>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="text-[9px] font-bold"
                                                :class="prefRoomId == room.id ? 'text-white/70' : 'text-slate-400'"
                                                x-text="room.type">
                                            </span>
                                            <template x-if="room.specialization">
                                                <span class="text-[9px] font-semibold"
                                                    :class="prefRoomId == room.id ? 'text-white/60' : 'text-slate-400'"
                                                    x-text="'· ' + room.specialization">
                                                </span>
                                            </template>
                                            <span class="text-[9px]"
                                                :class="prefRoomId == room.id ? 'text-white/50' : 'text-slate-400'"
                                                x-text="room.capacity ? '· Cap: ' + room.capacity : ''">
                                            </span>
                                        </div>
                                        <p class="text-[9px] mt-0.5 leading-tight"
                                            :class="prefRoomId == room.id ? 'text-white/60' : 'text-slate-400'"
                                            x-text="room.reason">
                                        </p>
                                    </div>
                                    <template x-if="prefRoomId == room.id">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </template>
                                </button>
                            </template>
                        </div>

                        {{-- Room count --}}
                        <div class="px-5 py-2 border-t border-slate-100 dark:border-slate-800 shrink-0">
                            <p class="text-[10px] text-slate-400 font-semibold" x-text="filteredRooms.length + ' rooms shown'"></p>
                        </div>
                    </div>
                </div>

                {{-- ── CURRENT SELECTION SUMMARY ── --}}
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Selection:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <template x-if="selectedFacultyName">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                    <span x-text="selectedFacultyName"></span>
                                </span>
                            </template>
                            <template x-if="!selectedFacultyName">
                                <span class="text-[10px] text-slate-400 italic">No faculty</span>
                            </template>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <template x-if="selectedRoomName">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-[10px] font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/></svg>
                                    <span x-text="selectedRoomName"></span>
                                </span>
                            </template>
                            <template x-if="!selectedRoomName">
                                <span class="text-[10px] text-slate-400 italic">No room</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── MODAL FOOTER ── --}}
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex gap-3 shrink-0">
                <button @click="prefOpen = false"
                    class="flex-1 py-2.5 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl border-2 border-slate-200 dark:border-slate-600 font-bold text-xs uppercase tracking-wide hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                    Cancel
                </button>
                <button
                    x-on:click="
                        $wire.set('assignFacultyId', prefFacultyId || null);
                        $wire.set('assignRoomId', prefRoomId || null);
                        $wire.savePreferredFacultyAndRoom(prefSubjectId);
                        prefOpen = false;
                    "
                    class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-wide shadow-md shadow-blue-500/20 hover:bg-blue-700 active:scale-95 transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Save Preferences
                </button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         PROTECTED DELETE MODAL (unchanged)
         ================================================================ --}}
    @if($showProtectedDeleteModal)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/75 p-6 backdrop-blur-xl">
            <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-red-500/50 bg-white shadow-2xl dark:bg-slate-950">
                <div class="bg-red-600 px-7 py-5 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.24em]">Finalized Subject Warning</p>
                    <h2 class="mt-1 text-xl font-black uppercase tracking-tight">{{ $protectedDeleteImpact['subject_code'] ?? 'Subject' }}</h2>
                </div>
                <div class="space-y-4 p-7">
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">This subject is already finalized and currently used in official schedules.</p>
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 dark:border-red-900/60 dark:bg-red-950/25 dark:text-red-200">
                        Schedule impact: {{ $protectedDeleteImpact['count'] ?? 0 }} finalized schedule row(s) will be removed with this subject.
                    </div>
                    @if(!empty($protectedDeleteImpact['schedules']))
                        <div class="space-y-2">
                            @foreach($protectedDeleteImpact['schedules'] as $schedule)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                                    {{ $schedule['day'] }} {{ $schedule['time'] }} — {{ $schedule['room'] }} — {{ $schedule['faculty'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($protectedDeleteSecondStep)
                        <div class="rounded-2xl border-2 border-red-500 bg-red-600/10 p-4 text-sm font-black uppercase tracking-widest text-red-700 dark:text-red-200">
                            Second confirmation required. This cannot be undone.
                        </div>
                    @endif
                </div>
                <div class="flex gap-3 border-t border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                    <button wire:click="cancelProtectedDelete" class="flex-1 rounded-xl bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">Cancel</button>
                    @if($protectedDeleteSecondStep)
                        <button wire:click="deleteProtectedSubject" class="flex-1 rounded-xl bg-red-700 px-4 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-700/20 hover:bg-red-600">Yes Delete Subject</button>
                    @else
                        <button wire:click="advanceProtectedDeleteConfirmation" class="flex-1 rounded-xl bg-red-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-600/20 hover:bg-red-500">Yes Delete Subject</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ================================================================
         BULK DELETE CONFIRMATION MODAL (unchanged)
         ================================================================ --}}
    <div x-data="{ showDeleteModal: false }" x-show="showDeleteModal"
        x-on:open-delete-modal.window="showDeleteModal = true"
        x-on:close-delete-modal.window="showDeleteModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-md" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-8 shadow-2xl text-center border border-slate-200 dark:border-slate-800" @click.away="showDeleteModal = false">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h2 class="text-xl font-extrabold text-slate-800 dark:text-white uppercase tracking-tight mb-2">Confirm Delete</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold mb-6">
                Are you sure you want to delete <span class="text-red-600 font-bold">{{ count($selectedSubjects) }}</span> selected subjects?
            </p>
            <div class="flex gap-4">
                <button @click="showDeleteModal = false" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-sm uppercase tracking-wide hover:scale-105 transition">Cancel</button>
                <button wire:click="deleteSelected" @click="showDeleteModal = false" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold text-sm uppercase tracking-wide shadow-lg shadow-red-500/30 hover:scale-105 transition">Confirm</button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         BULK DUPLICATE CONFIRMATION MODAL (unchanged)
         ================================================================ --}}
    <div x-data="{ showBulkDuplicateModal: false }" x-show="showBulkDuplicateModal"
        x-on:open-bulk-duplicate-modal.window="showBulkDuplicateModal = true"
        x-on:close-bulk-duplicate-modal.window="showBulkDuplicateModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-md" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-8 shadow-2xl text-center border border-slate-200 dark:border-slate-800" @click.away="showBulkDuplicateModal = false">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-5 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
            </div>
            <h2 class="text-xl font-extrabold text-slate-800 dark:text-white uppercase tracking-tight mb-2">Confirm Bulk Duplicate</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold mb-6">
                Duplicate <span class="text-blue-600 font-bold">{{ count($selectedSubjects) }}</span> selected subjects to the next section?
            </p>
            <div class="flex gap-4">
                <button @click="showBulkDuplicateModal = false" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-sm uppercase tracking-wide hover:scale-105 transition">Cancel</button>
                <button wire:click="bulkDuplicate" @click="showBulkDuplicateModal = false" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:scale-105 transition">Confirm</button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         BULK IMPORT MODAL (unchanged)
         ================================================================ --}}
    <div x-show="open || bulkOpen" class="fixed inset-0 z-50 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md" x-cloak x-transition></div>
    <div x-show="bulkOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-3xl p-7 shadow-2xl border dark:border-slate-800"
            @click.away="bulkOpen = false; $wire.set('previewData', [])">
            <h3 class="text-xl font-extrabold text-slate-800 dark:text-slate-100 mb-5 uppercase tracking-tight">Bulk Import Subjects</h3>
            @if(empty($previewData))
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-3xl p-12 flex flex-col items-center bg-slate-50 dark:bg-slate-800/50 relative hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                    <div class="text-center" wire:loading.remove wire:target="importFile">
                        <div class="h-14 w-14 bg-white dark:bg-slate-900 rounded-2xl shadow flex items-center justify-center mx-auto mb-4 border">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 uppercase tracking-wide">Click or drag CSV file to upload</p>
                    </div>
                    <div wire:loading wire:target="importFile" class="text-center">
                        <div class="relative h-14 w-14 mx-auto mb-4">
                            <div class="absolute inset-0 rounded-full border-4 border-blue-100"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
                        </div>
                        <p class="text-sm font-bold text-blue-600 uppercase animate-pulse">Scanning File...</p>
                    </div>
                </div>
            @else
                <div class="mb-5">
                    <div class="flex items-center gap-2 text-emerald-600 mb-4 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 rounded-xl w-fit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wide">Structure Verified</span>
                    </div>
                    <div class="max-h-80 overflow-y-auto rounded-2xl border border-slate-200 dark:border-slate-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0 z-10 text-xs uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">EDP Code</th>
                                    <th class="px-4 py-3">Subject</th>
                                    <th class="px-4 py-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($previewData as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-3 font-semibold text-blue-600">{{ $row['edp_code'] }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium break-words">{{ $row['subject'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($row['exists'])
                                            <span class="text-red-600 font-bold uppercase text-xs">Exists in Workspace</span>
                                        @else
                                            <span class="text-emerald-600 font-bold uppercase text-xs">New</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <button wire:click="importSubjects" class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold uppercase text-sm shadow-lg hover:bg-blue-700 active:scale-95 transition">
                    Finalize & Save Subjects
                </button>
                <button wire:click="$set('previewData', [])" class="w-full mt-2 text-xs font-bold text-slate-400 uppercase hover:text-red-500 transition">
                    Cancel & Re-upload
                </button>
            @endif
        </div>
    </div>

    {{-- ================================================================
         ADD/EDIT SUBJECT MODAL (unchanged)
         ================================================================ --}}
    <div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-transparent dark:border-slate-800 overflow-y-auto max-h-[90vh]" @click.away="open = false">

        @php
            $userRole   = strtolower(auth()->user()->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isPowerUser = in_array($userRole, $powerRoles);
        @endphp

        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 mb-4 uppercase tracking-tighter">
            {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
        </h3>

        <form wire:submit.prevent="saveSubject" class="space-y-3" autocomplete="off">
            {{-- STEP 1: DEPARTMENT --}}
            <div class="relative">
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">Step 1: Department</label>
                @if($isEditMode)
                    <input disabled value="{{ $department }}" class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
                    <span class="text-[7px] font-black text-amber-600 uppercase ml-1 italic">Locked in Edit Mode</span>
                @else
                    <select wire:model.live="department" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">— Select Department —</option>
                        @if($isPowerUser)
                            <option value="CCS">CCS</option>
                            <option value="CTE">CTE</option>
                            <option value="COC">COC</option>
                            <option value="SHTM">SHTM</option>
                        @else
                            <option value="{{ auth()->user()->department }}">{{ auth()->user()->department }}</option>
                        @endif
                    </select>
                @endif
                @error('department')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror
            </div>

            {{-- STEP 2: MAJOR --}}
            <div class="relative">
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">Step 2: Major</label>
                @if($isEditMode)
                    <input disabled value="{{ $major }}" class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
                    <span class="text-[7px] font-black text-amber-600 uppercase ml-1 italic">Locked in Edit Mode</span>
                @else
                    <select wire:model.live="major" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 {{ empty($department) ? 'opacity-50 cursor-not-allowed' : '' }}" {{ empty($department) ? 'disabled' : '' }}>
                        <option value="">— Select Major —</option>
                        @foreach($availableMajors as $majorCode => $majorName)
                            <option value="{{ $majorCode }}">{{ $majorName }}</option>
                        @endforeach
                    </select>
                @endif
                @error('major')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror
            </div>

            {{-- STEP 3: YEAR LEVEL --}}
            <div class="relative">
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">Step 3: Year Level</label>
                @if($isEditMode)
                    <input disabled value="Year {{ $year_level }}" class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
                    <span class="text-[7px] font-black text-amber-600 uppercase ml-1 italic">Locked in Edit Mode</span>
                @else
                    <select wire:model.live="year_level" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 {{ empty($major) ? 'opacity-50 cursor-not-allowed' : '' }}" {{ empty($major) ? 'disabled' : '' }}>
                        <option value="">— Select Year Level —</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                @endif
                @error('year_level')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror
            </div>

            {{-- AUTO EDP CODE --}}
            <div class="relative bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-200 dark:border-blue-800">
                <label class="text-[9px] font-black opacity-60 dark:text-slate-400 uppercase ml-1 mb-1 block">Auto-Generated EDP Code</label>
                <input type="text" wire:model="edp_code" readonly
                    class="w-full bg-white dark:bg-slate-800 rounded-lg p-2 font-bold text-xs uppercase text-blue-700 dark:text-blue-300 cursor-not-allowed border border-blue-300 dark:border-blue-600"
                    placeholder="Generates after selecting Major & Year">
                <span class="text-[7px] font-bold text-blue-600 dark:text-blue-400 uppercase ml-1 italic mt-1 block">Format: MAJOR-YYSEMLEVEL### (e.g., IT-2621001)</span>
            </div>

            {{-- SUBJECT CODE & SECTION --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Subject Code</label>
                    <input type="text" wire:model.live="subject_code" placeholder="e.g., UTS"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div class="relative">
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Section</label>
                    <input type="text" wire:model="section" placeholder="A, B, C..."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
            </div>
            @error('section')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror
            @error('subject_code')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror
            @error('edp_code')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror

            {{-- DESCRIPTION --}}
            <input type="text" wire:model="description" placeholder="DESCRIPTION"
                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
            @error('description')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror

            {{-- UNITS, TYPE, DURATION, MEETINGS --}}
            <div class="grid grid-cols-4 gap-2">
                <div>
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Units</label>
                    <input type="number" wire:model.live="units" min="3" max="5"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div>
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Type</label>
                    <select wire:model="type" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="Major">Major</option>
                        <option value="Minor">Minor</option>
                    </select>
                </div>
                <div>
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Duration</label>
                    <select wire:model="duration_hours" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="2">2 Hrs</option>
                        <option value="3">3 Hrs</option>
                        <option value="4">4 Hrs</option>
                        <option value="5">5 Hrs</option>
                    </select>
                </div>
                <div>
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block text-blue-500">Meetings</label>
                    <select wire:model="meetings_per_week" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="1">1x</option>
                        <option value="2">2x</option>
                        <option value="3">3x</option>
                    </select>
                </div>
            </div>
            @error('units')<span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>@enderror

            {{-- LAB / ROOM TYPE --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 rounded-xl bg-white px-3 py-3 text-[10px] font-black uppercase tracking-widest text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                        <input type="checkbox" wire:model.live="requires_lab" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900">
                        Requires Lab
                    </label>
                    <select wire:model.live="preferred_room_type" class="w-full bg-white dark:bg-slate-900 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Auto Room Type</option>
                        <option value="LECTURE">Lecture</option>
                        <option value="LAB">Lab</option>
                        <option value="ICT LAB">ICT Lab</option>
                        <option value="COMPUTER LAB">Computer Lab</option>
                        <option value="WORKSHOP LAB">Workshop Lab</option>
                        <option value="HRM KITCHEN">HRM Kitchen</option>
                        <option value="HOSPITALITY LAB">Hospitality Lab</option>
                    </select>
                </div>
                <p class="mt-2 text-[9px] font-bold text-slate-400">These fields guide AI room filtering without changing the scheduling workflow.</p>
            </div>

            {{-- SUBMIT --}}
            <button type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-not-allowed"
                class="w-full py-3 mt-4 bg-blue-600 dark:bg-indigo-600 text-white rounded-xl font-black uppercase text-[10px] shadow-lg hover:shadow-blue-500/20 active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="saveSubject">{{ $isEditMode ? 'Update' : 'Save' }} Subject</span>
                <span wire:loading wire:target="saveSubject" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </form>
        </div>
    </div>

<style>
.custom-main-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-main-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-main-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, rgba(99,102,241,0.5), rgba(59,130,246,0.5));
    border-radius: 999px; border: 2px solid transparent; background-clip: content-box;
}
.custom-main-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, rgba(129,140,248,0.8), rgba(96,165,250,0.8));
    background-clip: content-box;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 999px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(99,102,241,0.6); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.dark nav[role="navigation"] span[aria-current="page"] > span {
    background-color: #4f46e5 !important; border-color: #6366f1 !important; box-shadow: 0 0 15px rgba(79, 70, 229, 0.4) !important;
}
.dark nav[role="navigation"] button:hover { background-color: #1e293b !important; color: #818cf8 !important; }
</style>
</div>