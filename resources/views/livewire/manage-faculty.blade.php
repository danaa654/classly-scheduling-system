<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500" 
x-data="{ 
    open: @entangle('showModal'), 
    bulk: @entangle('bulkOpen'), 
    confirmDelete: @entangle('confirmingDeletion') 
}">
    <main class="flex-1 flex flex-col overflow-hidden">

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

            {{-- Container logic: Ass. Dean can see this section, but buttons are restricted below --}}
@if($this->isAdminOrRegistrar() && $pendingRequests->count() > 0)
    <div class="mb-8 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md rounded-[2rem] border border-white dark:border-slate-800 shadow-xl shadow-blue-100/50 dark:shadow-none p-6 transition-colors">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-sm font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                <span class="h-2 w-2 bg-amber-500 rounded-full animate-pulse"></span>
                Pending Approvals
            </h3>
            <span class="text-[10px] bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 font-bold px-3 py-1 rounded-full">
                {{ $pendingRequests->count() }} REQUESTS
            </span>
        </div>  

        {{-- Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pendingRequests as $request)
                <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-4 rounded-2xl hover:shadow-lg transition-all group">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-black text-slate-900 dark:text-white">{{ $request->full_name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ $request->department }}</p>
                        </div>

                        {{-- Role-Based Action Logic --}}
                        <div class="flex gap-1">
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                {{-- Approve Button --}}
                                <button wire:click="approveFaculty({{ $request->id }})" 
                                        wire:loading.attr="disabled"
                                        wire:target="approveFaculty({{ $request->id }})"
                                        class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 transition-all disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>

                                {{-- Decline Button --}}
                                <button wire:click="declineFaculty({{ $request->id }})" 
                                        wire:loading.attr="disabled"
                                        wire:target="declineFaculty({{ $request->id }})"
                                        class="p-2 bg-red-50 dark:bg-red-900/20 text-red-400 rounded-xl hover:bg-red-500 hover:text-white transition-all disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @else
                                {{-- Display for Ass. Dean who cannot take action --}}
                                <span class="text-[9px] font-black text-slate-300 dark:text-slate-600 uppercase italic mt-2">Awaiting Review</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

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
                            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'associate_dean']))
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
                                        
                                        {{-- 1. Checkbox Logic --}}
                                        <td class="px-6 py-6 text-center">
                                            @php
                                                // Check if user is Admin/Registrar OR a Dean dealing with a REJECTED faculty
                                                $canSelect = in_array(auth()->user()->role, ['admin', 'registrar']) || 
                                                            (auth()->user()->role === 'dean' && $faculty->status === 'rejected');
                                            @endphp

                                            @if($canSelect)
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

                                        {{-- 2. Actions Logic (Fixes the "Locked" issue) --}}
                                        <td class="px-6 py-6 text-right space-x-4">
                                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                                {{-- Admin/Registrar sees everything --}}
                                                <button @click="open = true" wire:click="editFaculty({{ $faculty->id }})" 
                                                        class="text-blue-600 dark:text-blue-400 font-black text-xs uppercase hover:underline">Edit</button>
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" wire:confirm="Permanently delete {{ $faculty->full_name }}?" 
                                                        class="text-slate-300 dark:text-slate-600 font-black text-xs uppercase hover:text-red-600 transition-colors">Delete</button>
                                            
                                            @elseif(auth()->user()->role === 'dean' && $faculty->status === 'rejected')
                                                {{-- Dean can ONLY see Delete for REJECTED entries --}}
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" wire:confirm="Remove this rejected application for {{ $faculty->full_name }}?" 
                                                        class="text-red-500 dark:text-red-400 font-black text-xs uppercase hover:underline">Delete</button>
                                            
                                            @else
                                                {{-- Everything else is Locked --}}
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
        
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-black text-slate-800 dark:text-slate-200 uppercase text-xs tracking-widest">Recent Activity</h3>
            <span class="animate-pulse flex h-2 w-2 rounded-full bg-blue-500"></span>
        </div>

        <div class="space-y-4 h-[600px] overflow-y-auto pr-3 custom-scrollbar">
            @forelse($recentLogs as $log)
                <div class="group relative overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                    
                    <div class="flex items-start gap-4">
                        <div class="absolute left-0 top-0 bottom-0 w-1 
        {{ $log->action == 'created' ? 'bg-emerald-500' : '' }}
        {{-- Both 'deleted' and 'rejected' will now be Red --}}
        {{ in_array($log->action, ['deleted', 'rejected']) ? 'bg-rose-500' : '' }}
        {{ $log->action == 'approved' ? 'bg-blue-500' : '' }}">
    </div>

    <div class="flex-1">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[10px] font-black uppercase tracking-widest 
                {{ $log->action == 'created' ? 'text-emerald-500' : '' }}
                {{-- Makes the word 'DELETED' or 'REJECTED' Red --}}
                {{ in_array($log->action, ['deleted', 'rejected']) ? 'text-rose-500' : '' }}
                {{ $log->action == 'approved' ? 'text-blue-500' : '' }}">
                {{ $log->action }}
            </span>

            <span class="text-[10px] text-slate-400 font-medium">
                {{ $log->created_at->diffForHumans() }}
            </span>
        </div>

        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">
            {{ $log->description }}
        </h4>

                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex items-center gap-1.5 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg">
                                    <div class="w-4 h-4 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-[8px] font-bold text-white uppercase">
                                        {{ substr($log->user->name ?? 'S', 0, 1) }}
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                        {{ $log->user->name ?? 'System' }}
                                    </span>
                                </div>
                                
                                <span class="text-[9px] px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-slate-400 uppercase font-bold tracking-tighter">
                                    {{ $log->user->role }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full text-center py-10 opacity-60">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-xs text-slate-400 italic font-medium uppercase tracking-widest">No recent registry activity.</p>
                </div>
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
                    
                    {{-- Check if the user is a dean OR has a department assigned (OIC logic) --}}
                    @if(auth()->user()->role === 'dean' || auth()->user()->department)
                        {{-- Locked UI for Deans/OICs --}}
                        <div class="w-full px-6 py-4 bg-slate-100 border-none rounded-2xl font-black text-blue-600 flex items-center justify-between">
                            <span>{{ auth()->user()->department }}</span>
                            <span class="text-[9px] bg-blue-100 px-2 py-1 rounded-lg">LOCKED</span>
                        </div>
                        {{-- Hidden input ensures Livewire stays synced --}}
                        <input type="hidden" wire:model="department">
                    @else
                        {{-- Full access for Admin/Registrar/Associate Dean --}}
                        <div class="relative">
                            <select wire:model="department" 
                                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 appearance-none @error('department') ring-2 ring-red-500 @enderror">
                                <option value="">Select Department</option>
                                <option value="CCS">CCS</option>
                                <option value="CTE">CTE</option>
                                <option value="COC">COC</option>
                                <option value="SHTM">SHTM</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                    
                    @error('department') 
                        <span class="text-red-500 text-[10px] font-bold ml-2">⚠️ {{ $message }}</span> 
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
            <div x-show="confirmDelete" 
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm" 
            x-cloak 
            x-transition
            @keydown.window.escape="confirmDelete = false"> <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-800"
                @click.away="confirmDelete = false"> <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 animate-bounce">
                    ⚠️
                </div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter uppercase">Purge Records?</h3>
                <p class="text-sm text-slate-500 mb-8 mt-2 italic font-medium">
                    You are about to delete <span class="text-red-600 font-black">{{ count($selectedFaculty) }}</span> entries.
                </p>
                <div class="flex flex-col space-y-3">
                    <button 
                        wire:click="deleteSelected" 
                        @keydown.window.enter="confirmDelete = false; $wire.deleteSelected()"
                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all active:scale-95">
                        Confirm Delete (Enter)
                    </button>
                    <button 
                        @click="confirmDelete = false" 
                        class="w-full py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        Cancel (Esc)
                    </button>
                </div>
            </div>
        </div>
</div>
