<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"  
x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        {{-- Header --}}
        <header class="h-24 bg-white dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-12 shadow-sm shrink-0 backdrop-blur-xl rounded-b-[3rem] transition-colors z-20">
            <h2 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">Subject Catalog</h2>
            
            <div class="flex items-center space-x-3">
                {{-- Move Delete Selected Here --}}
                @if(count($selectedSubjects) > 0)
                    <button 
                        type="button"
                        x-data 
                        @click.stop="$dispatch('open-delete-modal')" 
                        wire:key="bulk-delete-btn"
                        class="px-6 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-900/40 transition-all border border-red-100 dark:border-red-800 shadow-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Delete ({{ count($selectedSubjects) }})</span>
                    </button>
                @endif

                <button @click.prevent="bulkOpen = true" class="px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-xs uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">📥 Bulk Import</button>
                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 dark:bg-indigo-600 text-white rounded-2xl font-black shadow-xl dark:shadow-indigo-900/20 text-xs uppercase hover:scale-105 active:scale-95 transition-all">+ Add Subject</button>
            </div>
        </header>

        {{-- Main Scrollable Container --}}
        <div class="flex-1 overflow-y-auto p-12">
            <div class="grid grid-cols-12 gap-8 items-start">
        
        @php
            $userRole = strtolower(auth()->user()->role);
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isPowerUser = in_array($userRole, $powerRoles);
        @endphp

        {{-- LEFT SIDE: Filters and Table (9 Columns) --}}
        <div class="col-span-12 lg:col-span-9 space-y-8">
            
            {{-- Filter Bar Section --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm">
                {{-- 1. DEPARTMENT FILTER --}}
                <div class="relative">
                    <select wire:model.live="selectedDept" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 {{ !$isPowerUser ? 'opacity-60 cursor-not-allowed' : '' }}"
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
                        <span class="absolute -top-2 left-4 bg-white dark:bg-slate-900 px-2 text-[9px] font-black text-blue-500 uppercase italic">Locked to {{ auth()->user()->department }}</span>
                    @endif
                </div>

                {{-- 2. YEAR FILTER --}}
                <select wire:model.live="selectedYear" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">YEAR</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>

                {{-- 3. MAJOR FILTER --}}
                <select wire:model.live="selectedMajor" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
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

                {{-- 4. SEARCH BOX --}}
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center px-4 border border-transparent focus-within:ring-2 focus-within:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live="search" placeholder="Search Catalog..." class="w-full bg-transparent border-none focus:ring-0 font-bold text-sm text-slate-700 dark:text-slate-200 placeholder-slate-400">
                </div>
            </div>

            {{-- Table Container --}}
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest">
                        <tr>
                            <th class="pl-10 pr-4 py-5 w-10">
                                <input type="checkbox" wire:model.live="selectAll" 
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                            </th>
                            <th class="px-6 py-5">EDP Code</th>
                            <th class="px-10 py-5">Subject</th>
                            <th class="px-10 py-5">Duration</th>
                            <th class="px-10 py-5">Type</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($subjects as $subject)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors {{ in_array($subject->id, $selectedSubjects) ? 'bg-blue-50/30 dark:bg-indigo-900/10' : '' }}">
                                    <td class="pl-10 pr-4 py-6">
                                        <input type="checkbox" wire:model.live="selectedSubjects" value="{{ $subject->id }}" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-blue-600 focus:ring-blue-500 transition-all">
                                    </td>
                                    <td class="px-6 py-6 font-black text-blue-600 dark:text-indigo-400 uppercase text-xs">{{ $subject->edp_code }}</td>
                                    <td class="px-10 py-6">
                                        <p class="font-bold uppercase text-xs text-slate-800 dark:text-slate-200">{{ $subject->subject_code }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[200px]">{{ $subject->description }}</p>
                                    </td>
                                    <td class="px-10 py-6 font-bold text-xs text-slate-600 dark:text-slate-400">{{ $subject->duration_hours }} hrs</td>
                                    <td class="px-10 py-6">
                                        <div class="flex flex-col items-start">
                                            <span class="px-3 py-1 border rounded-lg text-[10px] font-black uppercase {{ strtolower($subject->type) === 'major' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                                                {{ $subject->type }}
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-400 mt-1 ml-1 uppercase tracking-tighter">{{ $subject->units }} Units</span>
                                        </div>
                                    </td>
                                    <td class="px-10 py-6 text-right space-x-4 text-nowrap">
                                        <button wire:click="editSubject({{ $subject->id }})" class="text-blue-600 dark:text-indigo-400 font-black text-[10px] uppercase hover:underline">Edit</button>
                                        <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Are you sure?" class="text-red-300 dark:text-red-900 font-black text-[10px] uppercase hover:text-red-600 transition-colors">Delete</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                    </table>
                
                        @if($subjects->hasPages())
                            <div class="mt-8 mb-6 px-10">
                                {{ $subjects->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RIGHT SIDE: Recent Activity (3 Columns) --}}
                <aside class="col-span-12 lg:col-span-3 sticky top-0">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 mb-8 uppercase italic tracking-tighter">Recent Activity</h3>
                        
                        <div class="space-y-8 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 dark:before:via-slate-700 before:to-transparent">
                            @forelse($activities as $activity)
                                @php
                                    $rawDept = trim($activity->user->department ?? '');
                                    $dept = !empty($rawDept) ? strtoupper($rawDept) : null;
                                    $role = strtolower($activity->user->role ?? '');
                                    
                                    // Priority: Department Color first, then Role Color
                                    $colorClasses = match(true) {
                                        $dept === 'CCS'  => 'border-yellow-500 bg-yellow-500',
                                        $dept === 'CTE'  => 'border-blue-500 bg-blue-500',
                                        $dept === 'COC'  => 'border-violet-500 bg-violet-500',
                                        $dept === 'SHTM' => 'border-orange-500 bg-orange-500',
                                        $role === 'registrar' => 'border-emerald-500 bg-emerald-500',
                                        $role === 'admin'     => 'border-slate-800 bg-slate-800',
                                        $role === 'associate_dean' => 'border-pink-500 bg-pink-500',
                                        default => 'border-slate-400 bg-slate-400',
                                    };

                                    $textClasses = match(true) {
                                        $dept === 'CCS'  => 'text-yellow-600 dark:text-yellow-400',
                                        $dept === 'CTE'  => 'text-blue-600 dark:text-blue-400',
                                        $dept === 'COC'  => 'text-violet-600 dark:text-violet-400',
                                        $dept === 'SHTM' => 'text-orange-600 dark:text-orange-400',
                                        $role === 'registrar' => 'text-emerald-600 dark:text-emerald-400',
                                        $role === 'admin'     => 'text-slate-800 dark:text-slate-200',
                                        $role === 'associate_dean' => 'text-pink-600 dark:text-pink-400',
                                        default => 'text-slate-600 dark:text-slate-400',
                                    };
                                @endphp

                                <div class="relative flex items-start gap-4">
                                    {{-- Dot Indicator --}}
                                    <div class="relative flex items-center justify-center h-10 w-10 rounded-full bg-white dark:bg-slate-900 border-2 {{ explode(' ', $colorClasses)[0] }} shadow-sm z-10 shrink-0">
                                        <div class="h-2 w-2 rounded-full {{ explode(' ', $colorClasses)[1] }} animate-pulse"></div>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-[11px] font-black {{ $textClasses }} uppercase tracking-tight">
                                                {{ $activity->user->name }}
                                            </p>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-[12px] font-bold text-slate-700 dark:text-slate-200 leading-tight mt-1">
                                            {{ $activity->action }}ed {{ $activity->module }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic">
                                            {{-- Displays: Successfully imported 10 subjects into the CTE department. --}}
                                            {{ $activity->description }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 text-slate-400 uppercase font-black text-[10px] italic tracking-widest">
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
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
        x-cloak
    >
        <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl text-center border border-slate-100 dark:border-slate-800"
             @click.away="showDeleteModal = false">
            
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter mb-2">Confirm Delete</h2>
            <p class="text-[10px] text-slate-500 font-bold mb-6 uppercase">
                Are you sure? You are about to delete {{ count($selectedSubjects) }} selected subjects.
            </p>
            
            <div class="flex gap-3">
                <button @click="showDeleteModal = false" 
                    class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl font-black text-[10px] uppercase tracking-widest">
                    Cancel
                </button>
                <button wire:click="deleteSelected" @click="showDeleteModal = false" 
                    class="flex-1 py-3 bg-red-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-red-500/30">
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>

    {{-- Dark Mode Backdrop --}}
    <div x-show="open || bulkOpen" class="fixed inset-0 z-50 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md" x-cloak x-transition></div>

    {{-- Bulk Import Modal --}}
    <div x-show="bulkOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl border dark:border-slate-800" 
             @click.away="bulkOpen = false; $wire.set('previewData', [])">
            <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 mb-6 uppercase tracking-tighter">
                {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
            </h3>

        {{-- 1. UPLOAD BOX --}}
        @if(empty($previewData))
            <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl p-16 flex flex-col items-center bg-slate-50 dark:bg-slate-800/50 relative hover:bg-slate-100 transition-all">
                <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                
                {{-- Default State --}}
                <div class="text-center" wire:loading.remove wire:target="importFile">
                    <div class="h-16 w-16 bg-white dark:bg-slate-900 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4 border border-slate-100 dark:border-slate-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <p class="text-xs font-black text-slate-500 uppercase tracking-widest">Select Subject CSV</p>
                </div>

                {{-- SCANNING STATE (Shown while processing) --}}
                <div wire:loading wire:target="importFile" class="text-center">
                    <div class="relative h-16 w-16 mx-auto mb-6">
                        <div class="absolute inset-0 rounded-full border-4 border-blue-100 dark:border-slate-800"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-[11px] font-black text-blue-600 uppercase tracking-[0.2em] animate-pulse">Scanning File Structure...</p>
                    <p class="text-[9px] text-slate-400 mt-2 uppercase italic">Analyzing headers & mapping data</p>
                </div>
            </div>
        @else
            {{-- 2. PREVIEW STATE --}}
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-emerald-500 mb-4 bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-xl w-fit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[10px] font-black uppercase italic tracking-widest">Structure Verified</span>
                </div>

                <div class="max-h-60 overflow-y-auto rounded-2xl border border-slate-100 dark:border-slate-800">
                    <table class="w-full text-[10px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-800 sticky top-0 uppercase font-black text-slate-400">
                            <tr>
                                <th class="p-3">EDP</th>
                                <th class="p-3">Subject</th>
                                <th class="p-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            @foreach($previewData as $row)
                            <tr>
                                <td class="p-3 font-bold text-blue-600">{{ $row[0] }}</td>
                                <td class="p-3 text-slate-600 dark:text-slate-400 font-medium truncate max-w-[150px] uppercase">{{ $row[1] }}</td>
                                <td class="p-3 text-right"><span class="text-emerald-500 font-black italic">VALID</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <button wire:click="importSubjects" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl hover:bg-blue-700 active:scale-95 transition-all">
                Finalize & Save Subjects
            </button>
            <button wire:click="$set('previewData', [])" class="w-full mt-3 text-[10px] font-black text-slate-400 uppercase hover:text-red-500 transition-colors">
                Cancel & Re-upload
            </button>
        @endif
    </div>
</div>

    {{-- New/Edit Subject Modal --}}
    {{-- New/Edit Subject Modal --}}
<div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-transparent dark:border-slate-800" @click.away="open = false">
        
        @php
            $userRole = strtolower(auth()->user()->role ?? '');
            $powerRoles = ['admin', 'registrar', 'associate_dean'];
            $isRestricted = !in_array($userRole, $powerRoles);
        @endphp

        <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 mb-6 uppercase tracking-tighter">
            {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
        </h3>

        <form wire:submit.prevent="saveSubject" class="space-y-4" autocomplete="off">
            <div class="grid grid-cols-2 gap-4">
    <input type="text" 
           wire:model="edp_code" 
           placeholder="EDP CODE" 
           autocomplete="off" {{-- <--- Insert it here --}}
           class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
    
    <input type="text" 
           wire:model="subject_code" 
           placeholder="SUBJ CODE" 
           autocomplete="off" {{-- <--- Good to add here too --}}
           class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
</div>
            
            <input type="text" wire:model="description" placeholder="DESCRIPTION" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
            
            <div class="grid grid-cols-2 gap-4">
                <input type="number" wire:model="units" placeholder="UNITS" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm text-slate-700 dark:text-slate-200">
                <select wire:model="type" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200">
                    <option value="Major">Major</option>
                    <option value="Minor">Minor</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-black opacity-40 dark:text-slate-400 uppercase ml-2 mb-1 block">Duration</label>
                    <select wire:model="duration_hours" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200">
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours</option>
                        <option value="4">4 Hours</option>
                        <option value="5">5 Hours</option>
                    </select>
                </div>

                {{-- Updated Department Field with Logic --}}
                <div>
                    <label class="text-[10px] font-black opacity-40 dark:text-slate-400 uppercase ml-2 mb-1 block">Department</label>
                    <div class="relative">
                        <select wire:model="department" 
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 {{ $isRestricted ? 'opacity-60 cursor-not-allowed' : '' }}"
                            {{ $isRestricted ? 'disabled' : '' }}>
                            <option value="">Select Dept</option>
                            <option value="CCS">CCS</option>
                            <option value="CTE">CTE</option>
                            <option value="COC">COC</option>
                            <option value="SHTM">SHTM</option>
                        </select>
                        @error('department')
                    <div class="mt-2 ml-2 flex items-center gap-1 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-[9px] font-black text-red-500 uppercase tracking-tight">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
                        
                        @if($isRestricted)
                            <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    @if($isRestricted)
                        <span class="text-[8px] font-black text-blue-500 uppercase ml-2 italic">Locked to your department</span>
                    @endif
                </div>
            </div>

            <button type="submit" class="w-full py-4 mt-4 bg-blue-600 dark:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl hover:shadow-blue-500/20 active:scale-95 transition-all">
                {{ $isEditMode ? 'Update' : 'Save' }} Subject
            </button>
        </form>
    </div>
</div>

</div>

<style>
    /* Pagination Overrides for Dark Mode */
    .dark nav[role="navigation"] span[aria-current="page"] > span {
        background-color: #4f46e5 !important; /* indigo-600 */
        border-color: #6366f1 !important;
        box-shadow: 0 0 15px rgba(79, 70, 229, 0.4) !important;
    }
    .dark nav[role="navigation"] button:hover {
        background-color: #1e293b !important;
        color: #818cf8 !important;
    }
</style>