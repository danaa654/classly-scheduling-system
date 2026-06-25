<div class="manage-subjects-readable min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500"
     x-data="{
        open: @entangle('showModal'),
        bulkOpen: @entangle('bulkOpen')
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
                            class="flex items-center px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 
                                rounded-xl font-black text-sm hover:bg-slate-200 dark:hover:bg-slate-700 
                                transition shadow-sm border border-slate-200 dark:border-slate-700">
                        <span class="mr-2 text-base">📥</span> Bulk Import
                    </button>

                    <button wire:click="openModal"
                            class="flex items-center px-4 py-2 bg-blue-700 dark:bg-indigo-700 text-white 
                                rounded-xl font-black shadow-md shadow-blue-600/40 dark:shadow-none 
                                text-sm hover:scale-105 active:scale-95 transition-all">
                        <span class="mr-1.5 text-base">+</span> Add Subject
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
                                placeholder="Search by code, subject, or EDP..."
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
                            <table class="w-full text-left min-w-[920px]">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 tracking-wide border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="pl-6 pr-3 py-3 w-10">
                                            <input type="checkbox" wire:model.live="selectAll" @disabled($catalogMode !== 'active')
                                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                        </th>
                                        <th class="px-4 py-3">EDP Code</th>
                                        <th class="px-5 py-3">Subject</th>
                                        <th class="px-4 py-3 text-center">Section</th>
                                        <th class="px-4 py-3 text-center">Year</th>
                                        <th class="px-4 py-3 text-center">Duration</th>
                                        <th class="px-5 py-3 text-center">Type</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                    @forelse($subjects as $subject)
                                    <tr class="align-middle hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors {{ in_array($subject->id, $selectedSubjects) ? 'bg-blue-50/50 dark:bg-indigo-900/20' : '' }}">

                                        {{-- Checkbox --}}
                                        <td class="pl-6 pr-3 py-4 align-middle">
                                            <input type="checkbox" wire:model.live="selectedSubjects" value="{{ $subject->id }}"
                                                @disabled($catalogMode !== 'active')
                                                class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                        </td>

                                        {{-- EDP Code --}}
                                        <td class="px-4 py-4 align-middle">
                                            <span class="text-sm font-extrabold text-blue-700 dark:text-blue-400 uppercase tracking-wide">{{ $subject->edp_code }}</span>
                                        </td>

                                        {{-- Subject --}}
                                        <td class="px-5 py-4 align-middle max-w-[240px]">
                                            <p class="text-base font-extrabold text-slate-900 dark:text-slate-100 uppercase truncate">{{ $subject->subject_code }}</p>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 font-medium mt-0.5 truncate">{{ $subject->description }}</p>
                                        </td>

                                        {{-- Section --}}
                                        <td class="px-4 py-4 align-middle text-center">
                                            <span class="inline-flex min-w-9 items-center justify-center px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 text-sm font-bold text-slate-800 dark:border-slate-600 dark:bg-slate-700/80 dark:text-slate-100">
                                                {{ $subject->section }}
                                            </span>
                                        </td>

                                        {{-- Year --}}
                                        <td class="px-4 py-4 align-middle text-center text-sm font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                            Y{{ $subject->year_level }}
                                        </td>

                                        {{-- Duration --}}
                                        <td class="px-4 py-4 align-middle text-center text-sm font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                            {{ $subject->duration_hours }}h
                                        </td>

                                        {{-- Type --}}
                                        <td class="px-5 py-4 align-middle text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <span class="inline-flex min-w-[6.5rem] items-center justify-center px-3 py-1 border rounded-md text-xs font-extrabold uppercase tracking-wide {{ strtolower($subject->type) === 'major' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800' }}">
                                                    {{ $subject->type }}
                                                </span>
                                                <span class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400 tracking-wide">{{ $subject->units }} Units</span>
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-5 py-4 align-middle text-right whitespace-nowrap">
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

            {{-- ROOM TYPE OVERRIDE — single contextual checkbox --}}
            {{-- Major subject  → default is lab  → checkbox = "Use Lecture Room instead"   --}}
            {{-- Minor subject  → default is lecture → checkbox = "Use Lab Room instead"    --}}
            {{-- preferred_room_type + requires_lab are kept in sync; auto-sched reads both --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2.5">Room Type Override</p>

                @if(strtolower($type ?? 'major') === 'major')
                    {{-- ── MAJOR subject: default = lab → offer "Use Lecture Room" ── --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox"
                            wire:model.live="room_override"
                            id="room_override_checkbox"
                            class="mt-0.5 w-4 h-4 shrink-0 accent-emerald-500 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-[11px] font-black uppercase tracking-wide
                                {{ $room_override ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' }}">
                                Use Lecture Room
                            </p>
                            <p class="text-[9px] text-slate-400 leading-tight mt-0.5">
                                Override: this Major subject will be scheduled in a <strong>lecture room</strong> instead of the default lab.
                            </p>
                        </div>
                    </label>

                    {{-- Status badge --}}
                    <div class="mt-2.5 flex items-center gap-2">
                        @if($room_override)
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 dark:bg-emerald-900/40 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Lecture Room — Major→Lab routing bypassed
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 dark:bg-amber-900/40 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                Default: Lab Room (Major subject)
                            </span>
                        @endif
                        <p class="text-[8px] text-slate-400">Auto-scheduler respects this setting.</p>
                    </div>

                @else
                    {{-- ── MINOR subject: default = lecture → offer "Use Lab Room (dept lab)" ── --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox"
                            wire:model.live="room_override"
                            id="room_override_checkbox"
                            class="mt-0.5 w-4 h-4 shrink-0 accent-amber-500 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-[11px] font-black uppercase tracking-wide
                                {{ $room_override ? 'text-amber-700 dark:text-amber-400' : 'text-slate-600 dark:text-slate-300' }}">
                                Use Lab Room
                                @if(!empty($major))
                                    <span class="font-normal normal-case text-[9px] text-slate-400 ml-1">({{ strtoupper($major) }} lab)</span>
                                @endif
                            </p>
                            <p class="text-[9px] text-slate-400 leading-tight mt-0.5">
                                Override: this Minor subject will use the <strong>{{ strtoupper($major ?: 'department') }} lab room</strong> instead of a lecture room.
                            </p>
                        </div>
                    </label>

                    {{-- Status badge --}}
                    <div class="mt-2.5 flex items-center gap-2">
                        @if($room_override)
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 dark:bg-amber-900/40 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                Lab Room — Minor→Lecture routing bypassed ({{ strtoupper($major ?: 'dept') }} lab)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-700 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Default: Lecture Room (Minor subject)
                            </span>
                        @endif
                        <p class="text-[8px] text-slate-400">Auto-scheduler respects this setting.</p>
                    </div>
                @endif
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