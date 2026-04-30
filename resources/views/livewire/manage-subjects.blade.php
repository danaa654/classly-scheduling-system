<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"  
     x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        {{-- Header --}}
        <header class="mx-auto mt-3 h-16 w-[97%] max-w-7xl bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-8 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">Subject Catalog</h2>
            
            <div class="flex items-center space-x-3">
                @if(count($selectedSubjects) > 0)
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
                

                <button @click.prevent="bulkOpen = true" class="px-5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 rounded-2xl font-extrabold text-xs uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-sm">
                    📥 Bulk Import
                </button>
                <button wire:click="openModal" class="px-6 py-3 bg-blue-700 dark:bg-indigo-700 text-white rounded-3xl font-extrabold shadow-lg shadow-blue-600/70 text-xs uppercase hover:scale-105 active:scale-95 transition-all">
                    + Add Subject
                </button>
            </div>
        </header>
        {{-- Main Scrollable Container --}}
        <div class="flex-1 overflow-y-auto p-4 lg:p-5">
            <div class="grid grid-cols-12 gap-4 items-start max-w-7xl mx-auto">
                
                @php
                    $userRole = strtolower(auth()->user()->role);
                    $powerRoles = ['admin', 'registrar', 'associate_dean'];
                    $isPowerUser = in_array($userRole, $powerRoles);
                @endphp
                <div class="col-span-12 lg:col-span-9 space-y-4">
                    
                    {{-- Filter Bar Section --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-300 dark:border-slate-700 shadow-md">
                        {{-- 1. DEPARTMENT FILTER --}}
                        <div class="relative">
                            <select wire:model.live="selectedDept" 
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 {{ !$isPowerUser ? 'opacity-70 cursor-not-allowed' : '' }}"
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
                                <span class="absolute -top-2 left-4 bg-white dark:bg-slate-900 px-1.5 text-xs font-extrabold text-blue-600 uppercase italic">Locked</span>
                            @endif
                        </div>
                        {{-- 2. YEAR FILTER --}}
                        <select wire:model.live="selectedYear" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                            <option value="">YEAR</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                        <select wire:model.live="selectedSection" 
                                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-xs font-bold uppercase text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="">All Sections</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec }}">{{ $sec }}</option>
                            @endforeach
                        </select>

                        {{-- 3. MAJOR FILTER --}}
                        <select wire:model.live="selectedMajor" 
                                class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-semibold text-sm uppercase text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500"
                                wire:key="major-filter-{{ $selectedDept }}">
                            <option value="">MAJOR</option>
                            @if($selectedDept == 'SHTM')
                                <option value="HM">Hospitality (HM)</option>
                                <option value="TM">Tourism (TM)</option>
                            @elseif($selectedDept == 'CCS')
                                <option value="IT">Information Technology (IT)</option>
                                <option value="ACT">ACT (ACT)</option> 
                            @elseif($selectedDept == 'COC')
                                <option value="FB">Forensic Biology (FB)</option>
                                <option value="LD">Lie Detection (LD)</option>
                                <option value="QD">Questioned Documents (QD)</option>
                            @else
                                <option value="">N/A</option>
                            @endif
                        </select>
                        {{-- 4. SEARCH --}}
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center px-4 border border-transparent focus-within:ring-2 focus-within:ring-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" wire:model.live="search" placeholder="Search Catalog..." class="w-full bg-transparent border-none focus:ring-0 font-semibold text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 py-4">
                        </div>
                    </div>
                    {{-- Table Container --}}
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-300 dark:border-slate-700 shadow-md overflow-hidden transition-colors">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-extrabold uppercase text-slate-500 dark:text-slate-400 tracking-wide">
                                <tr>
                                    <th class="pl-5 pr-3 py-4 w-10">
                                        <input type="checkbox" wire:model.live="selectAll" 
                                            class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue600 focus:ring-blue-500 transition-all">
                                    </th>
                                    <th class="px-4 py-4">EDP Code</th>
                                    <th class="px-6 py-4">Subject</th>
                                    <th class="px-4 py-4">Section</th>
                                    <th class="px-4 py-4">Duration</th>
                                    <th class="px-4 py-4">Type</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                @foreach($subjects as $subject)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors {{ in_array($subject->id, $selectedSubjects) ? 'bg-blue-50/40 dark:bg-indigo-900/20' : '' }} align-middle">
                                    <td class="pl-5 pr-3 py-5">
                                        <input type="checkbox" wire:model.live="selectedSubjects" value="{{ $subject->id }}" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
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
                                       
                                        <button wire:click="editSubject({{ $subject->id }})" class="text-blue-700 dark:text-indigo-400 font-extrabold text-xs uppercase hover:underline">Edit</button>
                                        <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Are you sure?" class="text-red-500 dark:text-red-600 font-extrabold text-xs uppercase hover:text-red-700 transition-colors">Delete</button>
                                        
                                    </td>
                                </tr>
                                @endforeach
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
                <aside class="col-span-12 lg:col-span-3 sticky top-4">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-300 dark:border-slate-700 shadow-md h-[520px] flex flex-col">
                        
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-4 uppercase italic tracking-tight">
                            Recent Activity
                        </h3>

                        {{-- Scrollable Activity List --}}
                        <div class="flex-1 overflow-y-auto pr-1 space-y-4 relative
                            before:absolute before:inset-y-0 before:left-4 before:-translate-x-px 
                            before:w-0.5 before:bg-gradient-to-b before:from-transparent 
                            before:via-slate-300 dark:before:via-slate-600 before:to-transparent">

                            @forelse($activities as $activity)
                                @php
                                    $rawDept = trim($activity->user->department ?? '');
                                    $dept = !empty($rawDept) ? strtoupper($rawDept) : null;
                                    $role = strtolower($activity->user->role ?? '');

                                    $colorClasses = match(true) {
                                        $dept === 'CCS'  => 'border-yellow-600 bg-yellow-600',
                                        $dept === 'CTE'  => 'border-blue-600 bg-blue-600',
                                        $dept === 'COC'  => 'border-violet-600 bg-violet-600',
                                        $dept === 'SHTM' => 'border-orange-600 bg-orange-600',
                                        $role === 'registrar' => 'border-emerald-600 bg-emerald-600',
                                        $role === 'admin'     => 'border-slate-900 bg-slate-900',
                                        $role === 'associate_dean' => 'border-pink-600 bg-pink-600',
                                        default => 'border-slate-400 bg-slate-400',
                                    };

                                    $textClasses = match(true) {
                                        $dept === 'CCS'  => 'text-yellow-700 dark:text-yellow-400',
                                        $dept === 'CTE'  => 'text-blue-700 dark:text-blue-400',
                                        $dept === 'COC'  => 'text-violet-700 dark:text-violet-400',
                                        $dept === 'SHTM' => 'text-orange-700 dark:text-orange-400',
                                        $role === 'registrar' => 'text-emerald-700 dark:text-emerald-400',
                                        $role === 'admin'     => 'text-slate-900 dark:text-slate-200',
                                        $role === 'associate_dean' => 'text-pink-700 dark:text-pink-400',
                                        default => 'text-slate-700 dark:text-slate-400',
                                    };
                                @endphp

                                <div class="relative flex items-start gap-3">
                                    {{-- Dot --}}
                                    <div class="relative flex items-center justify-center h-7 w-7 rounded-full bg-white dark:bg-slate-900 border-2 {{ explode(' ', $colorClasses)[0] }} shadow-sm z-10 shrink-0">
                                        <div class="h-2 w-2 rounded-full {{ explode(' ', $colorClasses)[1] }}"></div>
                                    </div>
                                    {{-- Content --}}
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-[11px] font-bold {{ $textClasses }} uppercase tracking-tight">
                                                {{ $activity->user->name }}
                                            </p>
                                            <span class="text-[10px] text-slate-400 whitespace-nowrap ml-2">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        {{-- MAIN MESSAGE (fixed wrapping) --}}
                                        <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 leading-snug mt-0.5 break-words">
                                            {{ $activity->action }} {{ $activity->module }}
                                        </p>
                                        {{-- DESCRIPTION (no truncate anymore) --}}
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug mt-1 break-words">
                                            {{ $activity->description }}
                                        </p>
                                    </div>
                                </div>

                            @empty
                                <div class="text-center py-10 text-slate-500 uppercase font-extrabold text-xs italic tracking-widest">
                                    No activity recorded yet
                                </div>
                            @endforelse
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
                                            <span class="text-red-600 font-bold uppercase text-xs">Already Exists</span>
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

       {{-- New/Edit Subject Modal --}}
    <div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-transparent dark:border-slate-800" @click.away="open = false">
        
        @php
            $userRole = strtolower(auth()->user()->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isRestricted = !in_array($userRole, $powerRoles);
        @endphp

        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 mb-4 uppercase tracking-tighter">
            {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
        </h3>

        <form wire:submit.prevent="saveSubject" class="space-y-3" autocomplete="off">
    @if(!$isEditMode)

        <div 
    x-data="{
        open:false,
        submenu:null,
        department: @entangle('department'),
        major: @entangle('selectedMajorCode'),

        selectMajor(dep, majorCode){
            this.department = dep;
            this.major = majorCode;
            this.open = false;
            this.submenu = null;
        }
    }"
    class="relative w-full"
>

    <label class="text-[9px] font-black opacity-40 uppercase ml-1 mb-1 block">
        Department / Major
    </label>

    <!-- Trigger -->
    <button
        @click="open=!open"
        type="button"
        class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 flex justify-between items-center text-xs font-bold uppercase"
    >
        <span x-text="major ? department + ' - ' + major : 'Select Department & Major'"></span>

        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>


    <!-- Main Dropdown -->
    <div
        x-show="open"
        @click.outside="open=false"
        x-transition
        class="absolute mt-2 w-full bg-white dark:bg-slate-900 rounded-xl shadow-xl z-50 border"
    >

        <!-- CCS -->
        <div class="relative"
             @mouseenter="submenu='ccs'"
        >
            <button class="w-full px-4 py-3 flex justify-between hover:bg-slate-100 dark:hover:bg-slate-800 text-left">
                CCS
                <span>▶</span>
            </button>

            <!-- Submenu -->
            <div
                x-show="submenu==='ccs'"
                class="absolute top-0 left-full ml-1 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border"
            >
                <button
                    @click="selectMajor('CCS','IT')"
                    class="block w-full text-left px-4 py-3 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    Information Technology (IT)
                </button>

                <button
                    @click="selectMajor('CCS','ACT')"
                    class="block w-full text-left px-4 py-3 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    Accounting Technology (ACT)
                </button>
            </div>
        </div>


        <!-- CTE -->
        <div class="relative"
             @mouseenter="submenu='cte'"
        >
            <button class="w-full px-4 py-3 flex justify-between hover:bg-slate-100 dark:hover:bg-slate-800 text-left">
                CTE
                <span>▶</span>
            </button>

            <div
                x-show="submenu==='cte'"
                class="absolute top-0 left-full ml-1 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border"
            >
                <button
                    @click="selectMajor('CTE','ED')"
                    class="block w-full text-left px-4 py-3 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    Education (ED)
                </button>
            </div>
        </div>


        <!-- COC -->
        <div class="relative"
             @mouseenter="submenu='coc'"
        >
            <button class="w-full px-4 py-3 flex justify-between hover:bg-slate-100 dark:hover:bg-slate-800 text-left">
                COC
                <span>▶</span>
            </button>

            <div
                x-show="submenu==='coc'"
                class="absolute top-0 left-full ml-1 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border"
            >
                <button @click="selectMajor('COC','FB')" class="block w-full px-4 py-3 text-left hover:bg-slate-100">
                    Forensic Biology
                </button>

                <button @click="selectMajor('COC','LD')" class="block w-full px-4 py-3 text-left hover:bg-slate-100">
                    Lie Detection
                </button>

                <button @click="selectMajor('COC','QD')" class="block w-full px-4 py-3 text-left hover:bg-slate-100">
                    Questioned Documents
                </button>
            </div>
        </div>


        <!-- SHTM -->
        <div class="relative"
             @mouseenter="submenu='shtm'"
        >
            <button class="w-full px-4 py-3 flex justify-between hover:bg-slate-100 dark:hover:bg-slate-800 text-left">
                SHTM
                <span>▶</span>
            </button>

            <div
                x-show="submenu==='shtm'"
                class="absolute top-0 left-full ml-1 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border"
            >
                <button @click="selectMajor('SHTM','HM')" class="block w-full px-4 py-3 text-left hover:bg-slate-100">
                    Hospitality Management
                </button>

                <button @click="selectMajor('SHTM','TM')" class="block w-full px-4 py-3 text-left hover:bg-slate-100">
                    Tourism Management
                </button>
            </div>
        </div>

    </div>
</div>
    @else

    <div class="relative">
        <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">
            Department
        </label>

        <input
            disabled
            value="{{ $department }}"
            class="w-full bg-slate-50 dark:bg-slate-800 rounded-xl p-3 font-bold text-xs uppercase
                text-slate-700 dark:text-slate-200 opacity-60 cursor-not-allowed">

        <span class="text-[7px] font-black text-amber-600 uppercase ml-1 italic">
            Locked
        </span>
</div>

@endif


            {{-- Top Row: EDP CODE, SUBJECT CODE, SECTION --}}
            <div class="grid grid-cols-3 gap-2">
                {{-- EDP CODE: Auto-filled prefix, user enters number --}}
                <div class="relative">
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">EDP Code</label>
                    <input type="text" 
                        wire:model="edp_code" 
                        placeholder="e.g., CCS-IT-101-A" 
                        {{ $isEditMode ? 'readonly' : '' }}
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all {{ $isEditMode ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @if($isEditMode)
                        <span class="absolute -top-6 left-1 text-[7px] font-extrabold text-slate-500 uppercase italic">Locked</span>
                    @elseif(!empty($selectedMajorCode))
                        <span class="absolute -top-6 left-1 text-[7px] font-bold text-blue-600 uppercase italic">Auto-filled</span>
                    @endif
                </div>
                
                {{-- SUBJECT CODE: Auto-filled prefix, user enters number --}}
                <div class="relative">
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Subj Code</label>
                    <input type="text" 
                        wire:model.live="subject_code" 
                        placeholder="e.g., IT101" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all {{ ($edpMajorMismatch ?? false) ? 'ring-2 ring-red-500' : '' }}">
                    @if(($edpMajorMismatch ?? false) && !empty($subject_code))
                        <svg class="absolute right-2 top-7 w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    @elseif(!empty($selectedMajorCode))
                        <span class="absolute -top-6 left-1 text-[7px] font-bold text-blue-600 uppercase italic">Auto-filled</span>
                    @endif
                </div>

                {{-- SECTION --}}   
                <div class="relative">
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block">Section</label>
                    <input type="text" 
                        wire:model="section" 
                        placeholder="A, B, C..." 
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
            </div>

            {{-- ERROR MESSAGES --}}
            @if(($edpMajorMismatch ?? false) && !empty($subject_code))
                <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-2">
                    <svg class="w-4 h-4 text-red-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[8px] font-bold text-red-600 uppercase">
                        Subject code must start with {{ strtoupper(explode('-', $edp_code)[1] ?? 'CODE') }}
                    </span>
                </div>
            @endif

            @error('section')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror
            @error('edp_code')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror
            @error('subject_code')
                <span class="text-[8px] font-black text-red-500 uppercase tracking-tight italic">{{ $message }}</span>
            @enderror

            {{-- DESCRIPTION --}}
            <input type="text" 
                wire:model="description" 
                placeholder="DESCRIPTION" 
                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all">

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

                {{-- NEW: MEETINGS PER WEEK --}}
                <div>
                    <label class="text-[9px] font-black opacity-40 dark:text-slate-400 uppercase ml-1 mb-0.5 block text-blue-500">Meetings</label>
                    <select wire:model="meetings_per_week" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl p-3 font-bold text-xs uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 transition-all border-l-2 border-blue-500">
                        <option value="1">1x</option>
                        <option value="2">2x</option>
                        <option value="3">3x</option>
                    </select>
                </div>
            </div>

            {{-- SUBMIT BUTTON --}}
            <button type="submit" 
                class="w-full py-3 mt-2 bg-blue-600 dark:bg-indigo-600 text-white rounded-xl font-black uppercase text-[10px] shadow-lg hover:shadow-blue-500/20 active:scale-95 transition-all">
                {{ $isEditMode ? 'Update' : 'Save' }} Subject
            </button>
        </form>
    </div>
</div>
<style>
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
