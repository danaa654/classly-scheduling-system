<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"  
     x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        {{-- Header --}}
        <header class="mx-auto mt-3 h-16 w-[97%] max-w-7xl bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-8 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Subject Catalog</h2>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                        {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }} {{ $activePeriod['school_year'] }}
                    </span>
                    <span class="rounded-lg px-2 py-0.5 text-[9px] font-black uppercase tracking-widest {{ $catalogMode === 'archive' ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                        {{ $catalogMode === 'archive' ? 'Archive History' : 'Active Workspace' }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                @if($catalogMode === 'active' && count($selectedSubjects) > 0)
                    <div class="flex items-center gap-2">
                        <button 
                            type="button"
                            x-data 
                            @click.stop="$dispatch('open-bulk-duplicate-modal')"
                            wire:loading.attr="disabled"
                            wire:key="bulk-duplicate-btn"
                            class="px-5 py-2 bg-indigo-50 dark:bg-indigo-900/25 text-indigo-700 dark:text-indigo-300 rounded-2xl font-extrabold text-xs uppercase tracking-widest hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-shadow border border-indigo-200 dark:border-indigo-700 shadow-md flex items-center gap-2"
                        >
                            <svg wire:loading wire:target="bulkDuplicate" class="animate-spin h-4 w-4 text-indigo-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <svg wire:loading.remove wire:target="bulkDuplicate" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                                <path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2H7a4 4 0 00-4 4v4H5V5z" />
                            </svg>

                            <span>Duplicate ({{ count($selectedSubjects) }})</span>
                        </button>

                        <button 
                            type="button"
                            x-data 
                            @click.stop="$dispatch('open-delete-modal')" 
                            wire:key="bulk-delete-btn"
                            class="px-5 py-2 bg-red-50 dark:bg-red-900/25 text-red-700 dark:text-red-300 rounded-2xl font-extrabold text-xs uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-900/50 transition-shadow border border-red-200 dark:border-red-700 shadow-md flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Delete ({{ count($selectedSubjects) }})</span>
                        </button>
                    </div>
                @endif
                

                @if($catalogMode === 'active')
                <button @click.prevent="bulkOpen = true" class="px-5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 rounded-2xl font-extrabold text-xs uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-sm">
                    📥 Bulk Import
                </button>
                <button wire:click="openModal" class="px-6 py-3 bg-blue-700 dark:bg-indigo-700 text-white rounded-3xl font-extrabold shadow-lg shadow-blue-600/70 text-xs uppercase hover:scale-105 active:scale-95 transition-all">
                    + Add Subject
                </button>
                @endif
            </div>
        </header>
        {{-- Main Scrollable Container --}}
        <div class="flex-1 overflow-y-auto p-4 lg:p-5 custom-main-scrollbar">
            <div class="grid grid-cols-12 gap-4 items-start max-w-7xl mx-auto">
                
                @php
                    $userRole = strtolower(auth()->user()->role);
                    $powerRoles = ['admin', 'registrar', 'associate_dean'];
                    $isPowerUser = in_array($userRole, $powerRoles);
                @endphp
                <div class="col-span-12 lg:col-span-9 space-y-4">
                    <div class="grid gap-3 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-300 dark:border-slate-700 shadow-md md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Catalog View</label>
                            <select
                                wire:model.live="catalogMode"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                <option value="active">Current Workspace</option>
                                <option value="archive">Archived Semesters</option>
                            </select>
                        </div>

                        @if($catalogMode === 'archive')
                            <div>
                                <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Archived Batch</label>
                                <select
                                    wire:model.live="selectedArchiveBatch"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="">Select Archived Semester</option>
                                    @foreach($archiveOptions as $archive)
                                        <option value="{{ $archive->archive_batch_id }}">
                                            {{ $archive->archive_batch_id }} - {{ $archive->semester_name ?: \App\Models\Setting::semesterDisplayName($archive->semester, $archive->school_year) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Filter Bar Section --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-300 dark:border-slate-700 shadow-md">
    
    {{-- 1. DEPARTMENT FILTER --}}
    <div class="relative">
        <select 
            wire:model.live="selectedDept" 
            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all {{ !$isPowerUser ? 'opacity-70 cursor-not-allowed' : '' }}"
            {{ !$isPowerUser ? 'disabled' : '' }}>
            
            @if($isPowerUser)
                <option value="">ALL DEPARTMENTS</option>
                <option value="CCS">CCS</option>
                <option value="SHTM">SHTM</option>
                <option value="COC">COC</option>
                <option value="CTE">CTE</option>
            @else
                <option value="{{ auth()->user()->department }}">{{ auth()->user()->department }}</option>
            @endif
        </select>

        @if(!$isPowerUser)
            <span class="absolute -top-2 left-4 bg-white dark:bg-slate-900 px-1.5 text-xs font-extrabold text-blue-600 uppercase italic">
                Locked
            </span>
        @endif
    </div>

    {{-- 2. YEAR LEVEL FILTER --}}
    <select 
        wire:model.live="selectedYear" 
        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
        <option value="">YEAR</option>
        <option value="1">1st Year</option>
        <option value="2">2nd Year</option>
        <option value="3">3rd Year</option>
        <option value="4">4th Year</option>
    </select>

    {{-- 3. SECTION FILTER --}}
    <select 
        wire:model.live="selectedSection" 
        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
        <option value="">ALL SECTIONS</option>
        @foreach($sections as $sec)
            <option value="{{ $sec }}">{{ $sec }}</option>
        @endforeach
    </select>

    {{-- 4. MAJOR FILTER - WITH wire:key FIX --}}
    <select 
        wire:model.live="selectedMajor" 
        wire:key="major-filter-{{ $selectedDept }}"
        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
        <option value="">MAJOR</option>
        
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
            <option value="">N/A</option>
        @endif
    </select>
</div>

{{-- SEARCH BAR - Add below filters section --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-300 dark:border-slate-700 shadow-md">
    <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center px-4 border border-transparent focus-within:ring-2 focus-within:ring-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input 
            type="text" 
            wire:model.live="search" 
            placeholder="Search Catalog by Code, EDP, or Description..." 
            class="w-full bg-transparent border-none focus:ring-0 font-semibold text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 py-4">
    </div>
</div>
                    {{-- Table Container --}}
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-300 dark:border-slate-700 shadow-md overflow-hidden transition-colors">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-extrabold uppercase text-slate-500 dark:text-slate-400 tracking-wide">
                                <tr>
                                    <th class="pl-5 pr-3 py-4 w-10">
                                        <input type="checkbox" wire:model.live="selectAll" @disabled($catalogMode !== 'active')
                                            class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue600 focus:ring-blue-500 transition-all">
                                    </th>
                                    <th class="px-4 py-4">EDP Code</th>
                                    <th class="px-6 py-4">Subject</th>
                                    <th class="px-4 py-4">Section</th>
                                    <th class="px-4 py-4">Year</th>
                                    <th class="px-4 py-4">Duration</th>
                                    <th class="px-4 py-4">Type</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                @forelse($subjects as $subject)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors {{ in_array($subject->id, $selectedSubjects) ? 'bg-blue-50/40 dark:bg-indigo-900/20' : '' }} align-middle">
                                    <td class="pl-5 pr-3 py-5">
                                        <input type="checkbox" wire:model.live="selectedSubjects" value="{{ $subject->id }}" @disabled($catalogMode !== 'active') class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                    </td>
                                    <td class="px-4 py-5 font-extrabold text-blue-700 dark:text-indigo-400 uppercase text-sm">{{ $subject->edp_code }}</td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold uppercase text-sm text-slate-900 dark:text-slate-200">{{ $subject->subject_code }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[220px]">{{ $subject->description }}</p>
                                    </td>
                                    <td class="px-4 py-5">
                                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300">
                                            {{ $subject->section }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-5 font-bold text-sm text-slate-700 dark:text-slate-400">Year {{ $subject->year_level }}</td>
                                    <td class="px-4 py-5 font-bold text-sm text-slate-700 dark:text-slate-400">{{ $subject->duration_hours }} hrs</td>
                                    <td class="px-4 py-5">
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="px-3 py-1 border rounded-lg text-xs font-black uppercase {{ strtolower($subject->type) === 'major' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-yellow-100 text-yellow-800 border-yellow-300' }}">
                                                {{ $subject->type }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">{{ $subject->units }} Units</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-right space-x-4 whitespace-nowrap">
                                       
                                        @if($catalogMode === 'active')
                                            <button wire:click="editSubject({{ $subject->id }})" class="text-blue-700 dark:text-indigo-400 font-extrabold text-xs uppercase hover:underline">Edit</button>
                                            <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Are you sure?" class="text-red-500 dark:text-red-600 font-extrabold text-xs uppercase hover:text-red-700 transition-colors">Delete</button>
                                        @else
                                            <span class="text-slate-400 font-extrabold text-xs uppercase">Archived</span>
                                        @endif
                                        
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        <p class="font-semibold uppercase text-sm">
                                            {{ $catalogMode === 'archive' ? 'Select an archive batch or no archived subjects found' : 'No active subjects for the current semester' }}
                                        </p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    
                        @if($subjects->hasPages())
                            <div class="py-4 px-6 border-t border-slate-200 dark:border-slate-700">
                                {{ $subjects->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>
                </div>
                {{-- RIGHT SIDE: Recent Activity --}}
<aside class="col-span-12 xl:col-span-4 2xl:col-span-3">
    
    <div class="sticky top-4">
        
        <div class="bg-white dark:bg-[#071024]/90 backdrop-blur-xl 
                    rounded-[28px] border border-slate-200 dark:border-slate-800
                    shadow-[0_10px_40px_rgba(0,0,0,0.25)]
                    overflow-hidden">

            {{-- HEADER --}}
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800
                        flex items-center justify-between">

                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider
                               text-slate-800 dark:text-white">
                        Recent Activity
                    </h3>

                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Live department actions & updates
                    </p>
                </div>

                <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></div>
            </div>

            {{-- ACTIVITY LIST --}}
            <div class="h-[650px] overflow-y-auto px-5 py-5 space-y-5
                        custom-scrollbar relative">

                {{-- TIMELINE LINE --}}
                <div class="absolute top-0 bottom-0 left-[29px] w-px
                            bg-gradient-to-b from-transparent
                            via-slate-700/70 to-transparent">
                </div>

                @forelse($activities as $activity)

                    @php
                        $rawDept = trim($activity->user->department ?? '');
                        $dept = !empty($rawDept) ? strtoupper($rawDept) : null;
                        $role = strtolower($activity->user->role ?? '');

                        $dotColor = match(true) {
                            $dept === 'CCS'  => 'bg-cyan-500',
                            $dept === 'CTE'  => 'bg-blue-500',
                            $dept === 'COC'  => 'bg-violet-500',
                            $dept === 'SHTM' => 'bg-orange-500',
                            $role === 'registrar' => 'bg-emerald-500',
                            $role === 'admin' => 'bg-rose-500',
                            $role === 'associate_dean' => 'bg-pink-500',
                            default => 'bg-slate-500',
                        };

                        $badgeColor = match(true) {
                            $dept === 'CCS'  => 'text-cyan-400 bg-cyan-500/10',
                            $dept === 'CTE'  => 'text-blue-400 bg-blue-500/10',
                            $dept === 'COC'  => 'text-violet-400 bg-violet-500/10',
                            $dept === 'SHTM' => 'text-orange-400 bg-orange-500/10',
                            $role === 'registrar' => 'text-emerald-400 bg-emerald-500/10',
                            $role === 'admin' => 'text-rose-400 bg-rose-500/10',
                            $role === 'associate_dean' => 'text-pink-400 bg-pink-500/10',
                            default => 'text-slate-400 bg-slate-500/10',
                        };
                    @endphp

                    <div class="relative flex gap-4 group">

                        {{-- TIMELINE DOT --}}
                        <div class="relative z-10 mt-1">
                            <div class="h-5 w-5 rounded-full border-4 border-[#071024] {{ $dotColor }}
                                        shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                            </div>
                        </div>

                        {{-- CARD --}}
                        <div class="flex-1 rounded-2xl p-4
                                    bg-slate-50 dark:bg-slate-900/60
                                    border border-slate-200 dark:border-slate-800
                                    hover:border-slate-700
                                    transition-all duration-300">

                            {{-- TOP --}}
                            <div class="flex items-start justify-between gap-3">

                                <div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full
                                                 text-[10px] font-black uppercase tracking-wider
                                                 {{ $badgeColor }}">
                                        {{ $activity->user->name }}
                                    </span>

                                    <h4 class="mt-3 text-sm font-bold
                                               text-slate-800 dark:text-slate-100 leading-snug">
                                        {{ $activity->action }} {{ $activity->module }}
                                    </h4>
                                </div>

                                <span class="text-[10px] whitespace-nowrap
                                             text-slate-400 mt-1">
                                    {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </div>

                            {{-- DESCRIPTION --}}
                            <p class="mt-2 text-[12px] leading-relaxed
                                      text-slate-500 dark:text-slate-400">
                                {{ $activity->description }}
                            </p>
                        </div>
                    </div>

                @empty

                    <div class="h-full flex items-center justify-center">
                        <div class="text-center">
                            <div class="h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800
                                        mx-auto mb-4 flex items-center justify-center">
                                
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-8 w-8 text-slate-400"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>

                            <p class="text-xs font-bold uppercase tracking-[0.2em]
                                      text-slate-500">
                                No Recent Activity
                            </p>
                        </div>
                    </div>

                @endforelse
            </div>
        </div>
    </div>
</aside>
            </div>
        </div>
    </main>

    {{-- Hidden Delete Confirmation Modal --}}
    <div 
        x-data="{ showDeleteModal: false }" 
        x-show="showDeleteModal" 
        x-on:open-delete-modal.window="showDeleteModal = true" 
        x-on:close-delete-modal.window="showDeleteModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-md" 
        x-cloak>
            <div 
            class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-8 shadow-2xl text-center border border-slate-200 dark:border-slate-800"
            @click.away="showDeleteModal = false">
            <!-- Icon -->
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-5 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h2 class="text-xl font-extrabold text-slate-800 dark:text-white uppercase tracking-tight mb-2">
                Confirm Delete
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold mb-6">
                Are you sure you want to delete 
                <span class="text-red-600 font-bold">
                    {{ count($selectedSubjects) }}
                </span> selected subjects?
            </p>
            <div class="flex gap-4">
                <button 
                    @click="showDeleteModal = false" 
                    class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-sm uppercase tracking-wide hover:scale-105 transition">
                    Cancel
                </button>
                <button 
                    wire:click="deleteSelected" 
                    @click="showDeleteModal = false" 
                    class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold text-sm uppercase tracking-wide shadow-lg shadow-red-500/30 hover:scale-105 transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>

        {{-- Bulk Duplicate Confirmation Modal --}}
    <div 
        x-data="{ showBulkDuplicateModal: false }" 
        x-show="showBulkDuplicateModal" 
        x-on:open-bulk-duplicate-modal.window="showBulkDuplicateModal = true" 
        x-on:close-bulk-duplicate-modal.window="showBulkDuplicateModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-md" 
        x-cloak>
            <div 
            class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl p-8 shadow-2xl text-center border border-slate-200 dark:border-slate-800"
            @click.away="showBulkDuplicateModal = false">
            <!-- Icon -->
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-5 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
            </div>
            <h2 class="text-xl font-extrabold text-slate-800 dark:text-white uppercase tracking-tight mb-2">
                Confirm Bulk Duplicate
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold mb-6">
                Duplicate 
                <span class="text-blue-600 font-bold">
                    {{ count($selectedSubjects) }}
                </span> selected subjects to the next section?
            </p>
            <div class="flex gap-4">
                <button 
                    @click="showBulkDuplicateModal = false" 
                    class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-sm uppercase tracking-wide hover:scale-105 transition">
                    Cancel
                </button>
                <button 
                    wire:click="bulkDuplicate" 
                    @click="showBulkDuplicateModal = false" 
                    class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:scale-105 transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>

   
    {{-- Dark Mode Backdrop --}}
    <div x-show="open || bulkOpen" class="fixed inset-0 z-50 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md" x-cloak x-transition></div>
        <div x-show="bulkOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-3xl p-7 shadow-2xl border dark:border-slate-800"
            @click.away="bulkOpen = false; $wire.set('previewData', [])">

            {{-- TITLE --}}
            <h3 class="text-xl font-extrabold text-slate-800 dark:text-slate-100 mb-5 uppercase tracking-tight">
                Bulk Import Subjects
            </h3>

            {{-- UPLOAD STATE --}}
            @if(empty($previewData))
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-3xl p-12 flex flex-col items-center bg-slate-50 dark:bg-slate-800/50 relative hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">

                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer z-10">

                    <div class="text-center" wire:loading.remove wire:target="importFile">
                        <div class="h-14 w-14 bg-white dark:bg-slate-900 rounded-2xl shadow flex items-center justify-center mx-auto mb-4 border">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 uppercase tracking-wide">
                            Click or drag CSV file to upload
                        </p>
                    </div>

                    <div wire:loading wire:target="importFile" class="text-center">
                        <div class="relative h-14 w-14 mx-auto mb-4">
                            <div class="absolute inset-0 rounded-full border-4 border-blue-100"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
                        </div>
                        <p class="text-sm font-bold text-blue-600 uppercase animate-pulse">
                            Scanning File...
                        </p>
                    </div>
                </div>

            @else
                {{-- PREVIEW STATE --}}
                <div class="mb-5">

                    {{-- VERIFIED BADGE --}}
                    <div class="flex items-center gap-2 text-emerald-600 mb-4 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 rounded-xl w-fit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wide">
                            Structure Verified
                        </span>
                    </div>

                    {{-- TABLE --}}
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
                                    <td class="px-4 py-3 font-semibold text-blue-600">
                                        {{ $row['edp_code'] }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium break-words">
                                        {{ $row['subject'] }}
                                    </td>
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

                {{-- ACTION BUTTONS --}}
                <button wire:click="importSubjects"
                    class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold uppercase text-sm shadow-lg hover:bg-blue-700 active:scale-95 transition">
                    Finalize & Save Subjects
                </button>

                <button wire:click="$set('previewData', [])"
                    class="w-full mt-2 text-xs font-bold text-slate-400 uppercase hover:text-red-500 transition">
                    Cancel & Re-upload
                </button>
            @endif
        </div>
    </div>
{{-- NEW/EDIT SUBJECT MODAL --}}
<div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-transparent dark:border-slate-800 overflow-y-auto max-h-[90vh]" @click.away="open = false">
    
    @php
        $userRole = strtolower(auth()->user()->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);
    @endphp

    <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 mb-4 uppercase tracking-tighter">
        {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
    </h3>

    <form wire:submit.prevent="saveSubject" class="space-y-3" autocomplete="off">

        {{-- STEP 1: DEPARTMENT --}}
        <div class="relative">
            <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">
                Step 1: Department
            </label>
            
            @if($isEditMode)
                <input
                    disabled
                    value="{{ $department }}"
                    class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase
                        text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
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
            @error('department')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror
        </div>

        {{-- STEP 2: MAJOR --}}
        <div class="relative">
            <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">
                Step 2: Major
            </label>
            
            @if($isEditMode)
                <input
                    disabled
                    value="{{ $major }}"
                    class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase
                        text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
                <span class="text-[7px] font-black text-amber-600 uppercase ml-1 italic">Locked in Edit Mode</span>
            @else
                <select wire:model.live="major" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 {{ empty($department) ? 'opacity-50 cursor-not-allowed' : '' }}" {{ empty($department) ? 'disabled' : '' }}>
                    <option value="">— Select Major —</option>
                    @foreach($availableMajors as $majorCode => $majorName)
                        <option value="{{ $majorCode }}">{{ $majorName }}</option>
                    @endforeach
                </select>
            @endif
            @error('major')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror
        </div>

        {{-- STEP 3: YEAR LEVEL --}}
        <div class="relative">
            <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-1 block">
                Step 3: Year Level
            </label>
            
            @if($isEditMode)
                <input
                    disabled
                    value="Year {{ $year_level }}"
                    class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase
                        text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">
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
            @error('year_level')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror
        </div>

        {{-- AUTOMATIC EDP CODE --}}
        <div class="relative bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-200 dark:border-blue-800">
            <label class="text-[9px] font-black opacity-60 dark:text-slate-400 uppercase ml-1 mb-1 block">
                Auto-Generated EDP Code
            </label>
            <input
                type="text"
                wire:model="edp_code"
                readonly
                class="w-full bg-white dark:bg-slate-800 rounded-lg p-2 font-bold text-xs uppercase
                    text-blue-700 dark:text-blue-300 cursor-not-allowed border border-blue-300 dark:border-blue-600"
                placeholder="Generates after selecting Major & Year">
            <span class="text-[7px] font-bold text-blue-600 dark:text-blue-400 uppercase ml-1 italic mt-1 block">
                Format: MAJOR-YYSEMLEVEL### (e.g., IT-2621001)
            </span>
        </div>

        {{-- SUBJECT CODE & SECTION (Manual inputs) --}}
        <div class="grid grid-cols-2 gap-2">
            <div class="relative">
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Subject Code</label>
                <input type="text" 
                    wire:model.live="subject_code" 
                    placeholder="e.g., UTS" 
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
            </div>

            <div class="relative">
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Section</label>
                <input type="text" 
                    wire:model="section" 
                    placeholder="A, B, C..." 
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
        </div>

        {{-- ERROR MESSAGES --}}
        @error('section')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror
        @error('subject_code')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror
        @error('edp_code')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror

        {{-- DESCRIPTION --}}
        <input type="text" 
            wire:model="description" 
            placeholder="DESCRIPTION" 
            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">

        @error('description')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror

        {{-- UNITS, TYPE, DURATION, MEETINGS --}}
        <div class="grid grid-cols-4 gap-2">
            <div>
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Units</label>
                <input type="number" 
                    wire:model.live="units" 
                    min="3" 
                    max="5"
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            
            <div>
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Type</label>
                <select wire:model="type" 
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="Major">Major</option>
                    <option value="Minor">Minor</option>
                </select>
            </div>

            <div>
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Duration</label>
                <select wire:model="duration_hours" 
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="2">2 Hrs</option>
                    <option value="3">3 Hrs</option>
                    <option value="4">4 Hrs</option>
                    <option value="5">5 Hrs</option>
                </select>
            </div>

            <div>
                <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block text-blue-500">Meetings</label>
                <select wire:model="meetings_per_week" 
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="1">1x</option>
                    <option value="2">2x</option>
                    <option value="3">3x</option>
                </select>
            </div>
        </div>

        @error('units')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror
        @error('type')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror
        @error('duration_hours')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror
        @error('meetings_per_week')
            <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
        @enderror

        {{-- ✅ SUBMIT BUTTON - WITH wire:loading.attr="disabled" TO PREVENT DOUBLE SUBMISSION --}}
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
            <p class="mt-2 text-[9px] font-bold text-slate-400">
                These fields guide AI room filtering without changing the scheduling workflow.
            </p>
        </div>

        <button 
            type="submit" 
            wire:loading.attr="disabled"
            wire:loading.class="opacity-60 cursor-not-allowed"
            class="w-full py-3 mt-4 bg-blue-600 dark:bg-indigo-600 text-white rounded-xl font-black uppercase text-[10px] shadow-lg hover:shadow-blue-500/20 active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove wire:target="saveSubject">
                {{ $isEditMode ? 'Update' : 'Save' }} Subject
            </span>
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
    .custom-main-scrollbar::-webkit-scrollbar {
    width: 10px;
}

.custom-main-scrollbar::-webkit-scrollbar-track {
    background: #020617;
}

.custom-main-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(
        to bottom,
        rgba(99,102,241,0.7),
        rgba(59,130,246,0.7)
    );
    border-radius: 999px;
    border: 2px solid #020617;
}

.custom-main-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(
        to bottom,
        rgba(129,140,248,0.9),
        rgba(96,165,250,0.9)
    );
}
    .dark nav[role="navigation"] span[aria-current="page"] > span {
        background-color: #4f46e5 !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 15px rgba(79, 70, 229, 0.4) !important;
    }
    .dark nav[role="navigation"] button:hover {
        background-color: #1e293b !important;
        color: #818cf8 !important;
    }
</style>
</div>
