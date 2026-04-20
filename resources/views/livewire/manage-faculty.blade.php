<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500" 
x-data="{ 
    open: @entangle('showModal'), 
    bulk: @entangle('bulkOpen'), 
    confirmDelete: @entangle('confirmingDeletion') 
}">
    <main class="flex-1 flex flex-col overflow-hidden">
        
        {{-- Session Feedback Notifications --}}
        <div class="fixed top-6 right-6 z-[100] space-y-3 w-80">
            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                    class="p-4 bg-white dark:bg-slate-900 border-l-4 border-green-500 rounded-2xl flex items-center shadow-xl shadow-green-100/50 dark:shadow-none animate-bounce-in">
                    <span class="text-green-600 mr-3 text-xl">✅</span>
                    <p class="text-slate-800 dark:text-slate-200 font-bold text-xs uppercase tracking-tight">{{ session('message') }}</p>
                </div>
            @endif

            @if (session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                    class="p-4 bg-white dark:bg-slate-900 border-l-4 border-red-500 rounded-2xl flex items-center shadow-xl shadow-red-100/50 dark:shadow-none">
                    <span class="text-red-600 mr-3 text-xl">⚠️</span>
                    <p class="text-slate-800 dark:text-slate-200 font-bold text-xs uppercase tracking-tight">{{ session('error') }}</p>
                </div>
            @endif
        </div>

        {{-- Header Section: Curved Edge Logic --}}
         <header class="h-24 bg-white dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-12 shadow-sm shrink-0 backdrop-blur-xl rounded-b-[3rem] transition-colors">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">Faculty Registry</h2>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium italic">Academic Personnel Management</p>
            </div>
            
            <div class="flex items-center space-x-3">
                @if(count($selectedFaculty) > 0)
                    <button @click="confirmDelete = true" class="px-6 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-2xl font-black text-xs uppercase italic hover:bg-red-100 transition-all border border-red-100 dark:border-red-900/30 animate-pulse">
                        🗑️ Delete ({{ count($selectedFaculty) }})
                    </button>
                @endif

                <button wire:click="exportCSV" wire:loading.attr="disabled" class="px-6 py-3 bg-green-50 dark:bg-green-900/10 text-green-600 dark:text-green-400 rounded-2xl font-black text-xs uppercase italic hover:bg-green-100 transition-all border border-green-100 dark:border-green-900/20">
                    <span wire:loading.remove wire:target="exportCSV">📊 Export CSV</span>
                    <span wire:loading wire:target="exportCSV">⏳...</span>
                </button>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                    <button @click="bulk = true" class="px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-xs uppercase italic hover:bg-slate-200 transition-all">
                        📥 Bulk Import
                    </button>
                @endif

                <button @click="open = true" 
                        wire:click="openModal" 
                        class="px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 dark:shadow-none hover:bg-blue-700 hover:scale-105 transition-all text-xs uppercase active:scale-95">
                    + {{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 'Add Faculty' : 'Request' }}
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-12 py-10">
            <div class="grid grid-cols-12 gap-8">
                
                {{-- Left Content --}}
                <div class="col-span-12 lg:col-span-9 space-y-8">
                    
                    {{-- Search and Filters --}}
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900/40 p-4 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-md">
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>             
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or ID..." 
                                class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>

                        <div class="flex items-center gap-2 bg-gray-100/50 dark:bg-slate-800/50 p-1.5 rounded-2xl">
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                @foreach(['ALL', 'CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                    <button wire:click="$set('filterDepartment', '{{ $dept == 'ALL' ? '' : $dept }}')" 
                                        class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filterDepartment == ($dept == 'ALL' ? '' : $dept)) ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-slate-500' }}">
                                        {{ $dept }}
                                    </button>
                                @endforeach
                            @else
                                <div class="px-6 py-1.5 bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm rounded-xl text-xs font-black uppercase">
                                    Dept: {{ auth()->user()->department }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Main Table --}}
                    <div class="bg-white dark:bg-slate-900/40 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden backdrop-blur-md">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 uppercase font-black text-[10px] tracking-widest">
                                <tr>
                                    <th class="px-6 py-5 w-10">  
                                        @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                                        @endif
                                    </th>
                                    <th class="px-6 py-5">Status</th>
                                    <th class="px-6 py-5">ID Number</th>
                                    <th class="px-6 py-5">Full Name</th>
                                    <th class="px-6 py-5">Department</th>
                                    <th class="px-6 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($faculties as $faculty)
                                    <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all {{ in_array($faculty->id, $selectedFaculty) ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                                        <td class="px-6 py-6 text-center">
                                            @if(in_array(auth()->user()->role, ['admin', 'registrar']) || (auth()->user()->role === 'dean' && $faculty->status === 'rejected'))
                                                <input type="checkbox" wire:model.live="selectedFaculty" value="{{ $faculty->id }}" class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                                            @endif
                                        </td>
                                        <td class="px-6 py-6">
                                            @if($faculty->status === 'approved')
                                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-md text-[9px] font-black uppercase tracking-tighter">Active</span>
                                            @else
                                                <span class="px-2 py-1 bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 rounded-md text-[9px] font-black uppercase tracking-tighter">{{ $faculty->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-6 font-black text-slate-400 dark:text-slate-600 italic tracking-tighter">{{ $faculty->employee_id }}</td>
                                        <td class="px-6 py-6 font-bold text-slate-800 dark:text-slate-200">{{ $faculty->full_name }}</td>
                                        <td class="px-6 py-6">
                                            <span class="text-blue-600 dark:text-blue-400 font-black uppercase text-[10px] bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full">{{ $faculty->department }}</span>
                                        </td>
                                        <td class="px-6 py-6 text-right space-x-4">
                                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                                <button @click="open = true" wire:click="editFaculty({{ $faculty->id }})" 
                                                        class="text-blue-600 dark:text-blue-400 font-black text-xs uppercase hover:underline">Edit</button>
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" wire:confirm="Permanently delete {{ $faculty->full_name }}?" 
                                                        class="text-slate-300 dark:text-slate-600 font-black text-xs uppercase hover:text-red-600 transition-colors">Delete</button>
                                            @else
                                                <span class="text-[9px] text-slate-400 dark:text-slate-600 font-black uppercase italic tracking-tighter">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-10 py-20 text-center text-slate-400 dark:text-slate-600 font-black uppercase text-xs italic tracking-widest">No matching records.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 mb-10"> 
                        {{ $faculties->links('livewire.custom-pagination') }} 
                    </div>
                </div>

                {{-- Sidebar: Activity Logs --}}
                <div class="col-span-12 lg:col-span-3">
                    <div class="bg-white dark:bg-slate-900/40 p-8 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm sticky top-8 backdrop-blur-md">
                        <h3 class="font-black text-slate-800 dark:text-slate-200 uppercase text-xs tracking-widest mb-6">Recent Activity</h3>
                        <div class="space-y-6">
                            @forelse($recentLogs as $log)
                                <div class="flex gap-3 items-start relative pb-5 border-l-2 border-slate-100 dark:border-slate-800 ml-3 pl-5">
                                    <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full border-4 border-white dark:border-slate-900 
                                        {{ $log->action == 'Bulk Delete' ? 'bg-red-500' : 'bg-blue-500' }}"></div>
                                    <div>
                                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-300 leading-tight uppercase tracking-tight">
                                            {{ $log->action }}: {{ $log->description }}
                                        </p>
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase">
                                            {{ $log->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="py-10 text-center text-[10px] font-black text-slate-300 dark:text-slate-700 uppercase tracking-widest">Logs Clear</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


    {{-- MODAL: Request/Edit --}}
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
    <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="open = false">
        <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-6">
            {{ $isEditMode ? 'Update Record' : 'New Registration' }}
        </h3>

        <div class="space-y-4">
            {{-- Employee ID: Auto-Uppercase and Next Number Hint --}}
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Employee ID</label>
                <input type="text" 
                       wire:model="employee_id" 
                       placeholder="e.g. EMP001" 
                       class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 uppercase @error('employee_id') ring-2 ring-red-500 @enderror">
                @error('employee_id') 
                    <span class="text-red-500 text-[10px] font-bold ml-2 italic">⚠️ Required or already taken.</span> 
                @enderror
            </div>
                        {{-- Full Name Input --}}
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Full Name</label>
                    <input type="text" 
                        wire:model.blur="full_name" 
                        placeholder="Firstname Lastname"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 @error('full_name') ring-2 ring-red-500 @enderror">
                    
                    @error('full_name') 
                        {{-- FIX: Using {{ $message }} allows the 'already being used' text to appear --}}
                        <span class="text-red-500 text-[10px] font-bold ml-2 italic">⚠️ {{ $message }}</span> 
                    @enderror
                </div>

                {{-- Email Input --}}
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Email Address</label>
                    <input type="email" 
                        wire:model.blur="email" 
                        placeholder="example@email.com"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 @error('email') ring-2 ring-red-500 @enderror">
                    
                    @error('email') 
                        {{-- FIX: This will now show the specific email duplicate error --}}
                        <span class="text-red-500 text-[10px] font-bold ml-2 italic">⚠️ {{ $message }}</span> 
                    @enderror
                </div>
            
            {{-- Department: Role-based Logic --}}
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Department</label>
                
                @if(auth()->user()->role === 'dean')
                    {{-- Deans are fixed to their department --}}
                    <div class="w-full px-6 py-4 bg-slate-100 border-none rounded-2xl font-black text-blue-600 flex items-center justify-between">
                        <span>{{ auth()->user()->department }}</span>
                        <span class="text-[9px] bg-blue-100 px-2 py-1 rounded-lg">FIXED</span>
                    </div>
                    {{-- Hidden input ensures the value is still sent to the backend --}}
                    <input type="hidden" wire:model="department">
                @else
                    {{-- Admin, Registrar, and Ass. Dean can select --}}
                    <select wire:model="department" 
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 appearance-none @error('department') ring-2 ring-red-500 @enderror">
                        <option value="">Select Department</option>
                        <option value="CCS">CCS</option>
                        <option value="CTE">CTE</option>
                        <option value="COC">COC</option>
                        <option value="SHTM">SHTM</option>
                    </select>
                @endif
                
                @error('department') 
                    <span class="text-red-500 text-[10px] font-bold ml-2">⚠️ Department is required.</span> 
                @enderror
            </div>

            <button wire:click="{{ $isEditMode ? 'updateFaculty' : 'saveFaculty' }}" 
                    class="w-full py-5 mt-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl hover:bg-blue-700 transition-all active:scale-95">
                {{ $isEditMode ? 'Save Changes' : 'Confirm Registration' }}
            </button>
            
            <button @click="open = false" 
                    type="button"
                    class="w-full py-2 text-slate-400 font-black uppercase text-[10px] hover:text-red-500 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

    {{-- MODAL: Bulk Import --}}
    <div x-show="bulk" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulk = false">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Faculty Batch Import</h3>
                    <p class="text-sm text-slate-400 font-medium italic">Required Header: <span class="font-bold text-slate-600">employee_id, full_name, email, department</span></p>
                </div>
                <button @click="bulk = false" class="text-slate-300 hover:text-slate-600">✕</button>
            </div>

            <div class="space-y-6">
                {{-- File Error Warning --}}
                @error('importFile')
                <div class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center text-red-600 animate-shake">
                    <span class="mr-3 text-lg">⚠️</span>
                    <p class="text-xs font-black uppercase tracking-tight">Error: That file is not a valid faculty CSV.</p>
                </div>
                @enderror

                <div class="relative border-2 border-dashed {{ $importFile ? 'border-green-400 bg-green-50/30' : 'border-slate-200 hover:border-blue-400' }} rounded-3xl p-10 text-center transition-all">
                    <input type="file" wire:model.live="importFile" accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2">
                        <div class="text-3xl">📂</div>
                        <div class="text-slate-500 font-bold text-sm">
                            <span wire:loading.remove wire:target="importFile">
                                {{ $importFile ? 'File: ' . $importFile->getClientOriginalName() : 'Click or drag Faculty CSV here' }}
                            </span>
                            <span wire:loading wire:target="importFile" class="text-blue-600 animate-pulse">Scanning file structure...</span>
                        </div>
                    </div>
                </div>

                @if(count($importPreview) > 0)
                    <div class="max-h-64 overflow-y-auto border border-slate-100 rounded-2xl bg-slate-50/50">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white shadow-sm text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Full Name</th>
                                    <th class="px-4 py-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($importPreview as $preview)
                                    <tr class="text-xs">
                                        <td class="px-4 py-3 font-mono font-bold text-slate-500">{{ $preview['employee_id'] }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-800">{{ $preview['full_name'] }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if($preview['error'])
                                                <span class="px-2 py-1 bg-red-100 text-red-600 rounded-lg font-black text-[9px] uppercase">Exists</span>
                                            @else
                                                <span class="px-2 py-1 bg-green-100 text-green-600 rounded-lg font-black text-[9px] uppercase">Valid</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button wire:click="processImport" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl hover:bg-blue-700 transition-all">
                        Finalize Batch Import
                    </button>
                @endif
            </div>
        </div>
    </div>

     {{-- MODAL: Bulk Delete Confirm --}}

    <div x-show="confirmDelete" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6">⚠️</div>
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Purge Records?</h3>
            <p class="text-sm text-slate-500 mb-8 mt-2 italic font-medium">You are about to delete <span class="text-red-600 font-black">{{ count($selectedFaculty) }}</span> entries.</p>
            <div class="flex flex-col space-y-3">
                <button wire:click="deleteSelected" class="w-full py-4 bg-red-600 text-white rounded-2xl font-black uppercase text-xs shadow-lg hover:bg-red-700 transition-all">Yes, Delete All</button>
                <button @click="confirmDelete = false" class="w-full py-4 bg-slate-100 text-slate-400 rounded-2xl font-black uppercase text-xs">No, Keep Them</button>
            </div>
        </div>
    </div>
</div>