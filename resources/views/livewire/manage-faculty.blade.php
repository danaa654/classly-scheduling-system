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

        {{-- Container logic: Admin/Registrar can see this section --}}
        @if($this->isAdminOrRegistrar() && $pendingRequests->count() > 0)
            <div class="mb-8 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md rounded-[2rem] border border-white dark:border-slate-800 shadow-xl shadow-blue-100/50 dark:shadow-none p-6 transition-colors mx-12 mt-6">
                
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
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ $request->displayDepartment() }} / {{ $request->scopeLabel() }}</p>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex gap-1">
                                    <button wire:click="approveFaculty({{ $request->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:target="approveFaculty"
                                            class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 transition-all disabled:opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button wire:click="declineFaculty({{ $request->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:target="declineFaculty"
                                            class="p-2 bg-red-50 dark:bg-red-900/20 text-red-400 rounded-xl hover:bg-red-500 hover:text-white transition-all disabled:opacity-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
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
                                @foreach(array_merge(['ALL'], $departments ?? ['CCS', 'CTE', 'COC', 'SHTM']) as $dept)
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
                    <div class="flex items-center gap-3">
    <select wire:model.live="filterType" class="bg-white dark:bg-slate-900 border-none rounded-2xl text-xs font-bold uppercase py-2.5 px-4 shadow-sm">
        <option value="">All Types</option>
        <option value="Full-time">Full-time</option>
        <option value="Part-time">Part-time</option>
    </select>

    <select wire:model.live="filterScope" class="bg-white dark:bg-slate-900 border-none rounded-2xl text-xs font-bold uppercase py-2.5 px-4 shadow-sm">
        <option value="">All Scopes</option>
        @foreach($scopeOptions ?? [] as $scopeValue => $scopeLabel)
            <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
        @endforeach
    </select>
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
                                    <th class="px-6 py-5">Employment & Workload</th>
                                    <th class="px-6 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($faculties as $faculty)
                                    <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all {{ in_array($faculty->id, $selectedFaculty) ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                                        
                                        {{-- Checkbox Logic --}}
                                        <td class="px-6 py-6 text-center">
                                            @php
                                                $canSelect = in_array(auth()->user()->role, ['admin', 'registrar']) || 
                                                            (auth()->user()->role === 'dean' && $faculty->status === 'rejected') ||
                                                            (auth()->user()->role === 'oic' && $faculty->status === 'rejected' && $faculty->department === auth()->user()->department);
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
                                            <span class="text-blue-600 dark:text-blue-400 font-black uppercase text-[10px] bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full whitespace-nowrap">{{ $faculty->displayDepartment() }}</span>
                                        </td>

                                        {{-- Employment Type & Workload Column --}}
                                        <td class="px-6 py-6">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                @php
                                                    $scopeClasses = match($faculty->faculty_scope) {
                                                        \App\Models\Faculty::SCOPE_GENED => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                                        \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
                                                        default => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
                                                    };
                                                @endphp
                                                {{-- Employment Type Badge --}}
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 {{ $faculty->employment_type === 'Full-time' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' }} rounded-full text-[9px] font-black uppercase tracking-tighter whitespace-nowrap">
                                                    @if($faculty->employment_type === 'Full-time')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                        </svg>
                                                        FT
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
                                                        </svg>
                                                        PT
                                                    @endif
                                                </span>

                                                {{-- Faculty Scope Badge --}}
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 {{ $scopeClasses }} rounded-full text-[9px] font-black uppercase tracking-tighter whitespace-nowrap">
                                                    {{ $faculty->scopeLabel() }}
                                                </span>

                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 {{ $faculty->canTeachMinorSubjects() ? 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300' }} rounded-full text-[9px] font-black uppercase tracking-tighter whitespace-nowrap">
                                                    {{ $faculty->canTeachMinorSubjects() ? 'Minor OK' : 'Major Only' }}
                                                </span>

                                                {{-- Max Units Badge --}}
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-full text-[9px] font-black uppercase tracking-tighter whitespace-nowrap">
                                                    📊 {{ $faculty->max_units ?? 21 }} Units
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-6 py-6 text-right space-x-4">
                                            @php
                                                $user = auth()->user();
                                                $isAdminOrRegistrar = in_array($user->role, ['admin', 'registrar']);
                                                $isAssocDean = $user->role === 'associate_dean';
                                                $isDeptHead = in_array($user->role, ['dean', 'oic']) && ($user->department === $faculty->department);
                                                $isRejected = $faculty->status === 'rejected';
                                            @endphp

                                            @if($isAdminOrRegistrar)
                                                <button @click="open = true" wire:click="editFaculty({{ $faculty->id }})" 
                                                        class="text-blue-600 font-black text-xs uppercase hover:underline">
                                                    Edit
                                                </button>
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" 
                                                        wire:confirm="Permanently delete {{ $faculty->full_name }}?" 
                                                        wire:loading.attr="disabled"
                                                        class="text-slate-300 font-black text-xs uppercase hover:text-red-600 transition-colors">
                                                    Delete
                                                </button>

                                            @elseif(($isAssocDean || $isDeptHead) && $isRejected)
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" 
                                                        wire:confirm="Remove this rejected application for {{ $faculty->full_name }}?" 
                                                        wire:loading.attr="disabled"
                                                        class="text-red-500 font-black text-xs uppercase hover:underline">
                                                    Delete
                                                </button>

                                            @else
                                                <div class="flex items-center justify-end gap-1">
                                                    <span class="text-[9px] text-slate-400 font-black uppercase italic tracking-tighter">Locked</span>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-10 py-20 text-center text-slate-400 dark:text-slate-600 font-black uppercase text-xs italic tracking-widest">No matching records.</td></tr>
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
                                            {{ in_array($log->action, ['deleted', 'rejected']) ? 'bg-rose-500' : '' }}
                                            {{ $log->action == 'approved' ? 'bg-blue-500' : '' }}
                                            {{ $log->action == 'updated' ? 'bg-orange-500' : '' }}">
                                        </div>

                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[10px] font-black uppercase tracking-widest 
                                                    {{ $log->action == 'created' ? 'text-emerald-500' : '' }}
                                                    {{ in_array($log->action, ['deleted', 'rejected']) ? 'text-rose-500' : '' }}
                                                    {{ $log->action == 'approved' ? 'text-blue-500' : '' }}
                                                    {{ $log->action == 'updated' ? 'text-orange-500' : '' }}">
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
                                                    {{ $log->user->role ?? 'System' }}
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
    <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-[2.5rem] p-10 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" @click.away="open = false">
        <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter mb-6">
            {{ $isEditMode ? '✏️ Update Record' : '➕ New Registration' }}
        </h3>

        <div class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                {{-- Employee ID --}}
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">Employee ID</label>
                    <input type="text" 
                        wire:model="employee_id" 
                        placeholder="e.g. 2026-1001" 
                        {{ $isEditMode ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:opacity-50 @error('employee_id') ring-2 ring-red-500 @enderror">
                    @error('employee_id') 
                        <span class="text-red-500 text-[9px] font-bold ml-2 italic">⚠️ {{ $message }}</span> 
                    @enderror
                </div>

                {{-- Department --}}
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">Department</label>
                    
                    @php
                        // Only lock for Dean and OIC
                        $isDeanOrOIC = in_array(auth()->user()->role, ['dean', 'oic']);
                    @endphp

                    @if($faculty_scope === \App\Models\Faculty::SCOPE_GENED)
                        <div class="w-full px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl font-bold text-blue-700 dark:text-blue-300">
                            Institution-wide
                        </div>
                        <p class="mt-2 text-[9px] font-bold text-blue-500 dark:text-blue-300 italic">General Education faculty may teach institution-wide subjects.</p>
                    @elseif($isDeanOrOIC && auth()->user()->department)
                        <div class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-2xl font-black text-blue-600 dark:text-blue-400 flex items-center justify-between">
                            <span>{{ auth()->user()->department }}</span>
                            <span class="text-[8px] bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded text-blue-700 dark:text-blue-300">LOCKED</span>
                        </div>
                        <input type="hidden" wire:model="department" value="{{ auth()->user()->department }}">
                    @else
                        <div class="relative">
                            <select wire:model="department" 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('department') ring-2 ring-red-500 @enderror">
                                <option value="">Select Department</option>
                                @foreach($departments ?? ['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                    
                    @error('department') 
                        <span class="text-red-500 text-[9px] font-bold ml-2">⚠️ {{ $message }}</span> 
                    @enderror
                </div>
            </div>

            {{-- Full Name --}}
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">Full Name</label>
                <input type="text" 
                    wire:model.blur="full_name" 
                    placeholder="Firstname Lastname"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('full_name') ring-2 ring-red-500 @enderror">
                
                @error('full_name') 
                    <span class="text-red-500 text-[9px] font-bold ml-2 italic">⚠️ {{ $message }}</span> 
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">Email Address</label>
                <input type="email" 
                    wire:model.blur="email" 
                    placeholder="example@email.com"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('email') ring-2 ring-red-500 @enderror">
                
                @error('email') 
                    <span class="text-red-500 text-[9px] font-bold ml-2 italic">⚠️ {{ $message }}</span> 
                @enderror
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mt-4">
                <p class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase mb-4">📅 Scheduling Information</p>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Employment Type --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">⏱️ Employment Type</label>
                        <div class="relative">
                            <select wire:model="employment_type" 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('employment_type') ring-2 ring-red-500 @enderror">
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        @error('employment_type')
                            <span class="text-red-500 text-[9px] font-bold ml-2">⚠️ {{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Faculty Scope --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">Faculty Scope</label>
                        <div class="relative">
                            <select wire:model.live="faculty_scope"
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('faculty_scope') ring-2 ring-red-500 @enderror">
                                @foreach($scopeOptions ?? [] as $scopeValue => $scopeLabel)
                                    <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        @error('faculty_scope')
                            <span class="text-red-500 text-[9px] font-bold ml-2">⚠️ {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Can Teach Minor --}}
                <label class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 dark:bg-slate-800/50 px-4 py-3">
                    <span>
                        <span class="block text-[10px] font-black text-slate-500 dark:text-slate-300 uppercase">Can Teach Minor / GenEd</span>
                        <span class="block text-[9px] font-bold text-slate-400 italic">Required for minor and general education assignments.</span>
                    </span>
                    <input type="checkbox" wire:model.live="can_teach_minor" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @if($faculty_scope === \App\Models\Faculty::SCOPE_GENED) disabled @endif>
                </label>
                @error('can_teach_minor')
                    <span class="text-red-500 text-[9px] font-bold ml-2">{{ $message }}</span>
                @enderror

                {{-- Max Units --}}
                <div class="mt-4">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block mb-2">📊 Maximum Teaching Units</label>
                    <input type="number" 
                        wire:model="max_units" 
                        placeholder="21"
                        min="1" max="30"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('max_units') ring-2 ring-red-500 @enderror">
                    <p class="text-[9px] text-slate-400 mt-2 italic">ℹ️ Part-time: max 18 units | Full-time: max 30 units</p>
                    @error('max_units')
                        <span class="text-red-500 text-[9px] font-bold ml-2">⚠️ {{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Submit Button --}}
            <button wire:click="{{ $isEditMode ? 'updateFaculty' : 'saveFaculty' }}" 
                    wire:loading.attr="disabled"
                    class="w-full py-4 mt-6 bg-blue-600 dark:bg-blue-700 text-white rounded-2xl font-black uppercase text-xs shadow-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                <span wire:loading.remove>{{ $isEditMode ? '💾 Save Changes' : '✓ Confirm Registration' }}</span>
                <span wire:loading wire:target="saveFaculty,updateFaculty">⏳ Processing...</span>
            </button>
            
            <button @click="open = false" 
                    type="button"
                    class="w-full py-3 text-slate-400 dark:text-slate-500 font-black uppercase text-[10px] hover:text-red-500 transition-colors">
                ✕ Close
            </button>
        </div>
    </div>
</div>

{{-- MODAL: Bulk Import --}}
<div x-show="bulk" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
    <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-[2.5rem] p-10 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" @click.away="bulk = false">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">📥 Faculty Batch Import</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-bold italic mt-2">
                    Upload a CSV file with faculty data including scheduling information
                </p>
            </div>
            <button @click="bulk = false" class="text-slate-400 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-400 text-2xl font-black">✕</button>
        </div>

        {{-- CSV Format Guide --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-900/40">
                <p class="text-[10px] font-black text-blue-900 dark:text-blue-200 uppercase mb-3">📋 Required CSV Columns:</p>
                <ul class="space-y-2 text-[10px] font-mono font-bold text-blue-900 dark:text-blue-300">
                    <li>✓ <strong>employee_id</strong> - Unique identifier</li>
                    <li>✓ <strong>full_name</strong> - First and Last name</li>
                    <li>✓ <strong>email</strong> - Valid email address</li>
                    <li>✓ <strong>department</strong> - optional for gened faculty</li>
                    <li>✓ <strong>employment_type</strong> - "Full-time" or "Part-time"</li>
                    <li>✓ <strong>faculty_scope</strong> - "gened", "departmental", or "cross_department"</li>
                    <li>✓ <strong>can_teach_minor</strong> - yes/no, true/false, or 1/0</li>
                    <li>✓ <strong>max_units</strong> - 1-30 (auto-filled if blank)</li>
                </ul>
            </div>

            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-900/40">
                <p class="text-[10px] font-black text-green-900 dark:text-green-200 uppercase mb-3">✓ Example CSV Row:</p>
                <div class="space-y-1 text-[9px] font-mono text-green-900 dark:text-green-300 bg-white dark:bg-slate-800 p-3 rounded">
                    <p><span class="text-slate-500">employee_id:</span> 2026-1001</p>
                    <p><span class="text-slate-500">full_name:</span> Juan Dela Cruz</p>
                    <p><span class="text-slate-500">email:</span> juan@pap.edu.ph</p>
                    <p><span class="text-slate-500">department:</span> GENED</p>
                    <p><span class="text-slate-500">employment_type:</span> Full-time</p>
                    <p><span class="text-slate-500">faculty_scope:</span> gened</p>
                    <p><span class="text-slate-500">can_teach_minor:</span> YES</p>
                    <p><span class="text-slate-500">max_units:</span> 30</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- File Error Warning --}}
            @error('importFile')
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 rounded-2xl flex items-start gap-3 text-red-600 dark:text-red-400">
                <span class="text-lg">⚠️</span>
                <div>
                    <p class="text-xs font-black uppercase">Error</p>
                    <p class="text-[11px] font-semibold">{{ $message }}</p>
                </div>
            </div>
            @enderror

            {{-- File Upload Area --}}
            <div class="relative border-2 border-dashed {{ $importFile ? 'border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-900/20' : 'border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500' }} rounded-3xl p-12 text-center transition-all">
                <input type="file" wire:model.live="importFile" accept=".csv,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                <div class="space-y-3">
                    <div class="text-4xl">{{ $importFile ? '✓' : '📂' }}</div>
                    <div class="text-slate-600 dark:text-slate-400 font-bold text-sm">
                        <span wire:loading.remove wire:target="importFile">
                            {{ $importFile ? '📄 ' . $importFile->getClientOriginalName() : 'Click or drag CSV file here' }}
                        </span>
                        <span wire:loading wire:target="importFile" class="text-blue-600 dark:text-blue-400 animate-pulse">⏳ Scanning file...</span>
                    </div>
                </div>
            </div>

            {{-- Preview Table --}}
            @if(count($importPreview) > 0)
                <div>
                    <p class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase mb-3">📊 Preview ({{ count($importPreview) }} records found)</p>
                    <div class="max-h-96 overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-900/40">
                        <table class="w-full text-left text-[9px]">
                            <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-3 py-2 whitespace-nowrap">ID</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Name</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Email</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Dept</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Employment</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Scope</th>
                                    <th class="px-3 py-2 whitespace-nowrap">Minor</th>
                                    <th class="px-3 py-2 whitespace-nowrap text-center">Units</th>
                                    <th class="px-3 py-2 whitespace-nowrap text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                @foreach($importPreview as $preview)
                                    <tr class="hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors {{ $preview['error'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                        <td class="px-3 py-2 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $preview['employee_id'] }}</td>
                                        <td class="px-3 py-2 font-semibold text-slate-800 dark:text-slate-200">{{ $preview['full_name'] }}</td>
                                        <td class="px-3 py-2 text-slate-600 dark:text-slate-400 truncate text-[8px]">{{ $preview['email'] }}</td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded font-bold text-[8px]">{{ $preview['department'] }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($preview['employment_type'] === 'Part-time')
                                                <span class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400 font-black text-[8px]">⏰ PT</span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-black text-[8px]">⏱️ FT</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @php
                                                $previewScopeClasses = match($preview['faculty_scope']) {
                                                    \App\Models\Faculty::SCOPE_GENED => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                                                    \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
                                                    default => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 {{ $previewScopeClasses }} rounded font-black text-[8px]">{{ strtoupper(str_replace('_', '-', $preview['faculty_scope'])) }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex px-2 py-0.5 {{ $preview['can_teach_minor'] ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} rounded font-black text-[8px] uppercase">
                                                {{ $preview['can_teach_minor'] ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 font-bold text-slate-700 dark:text-slate-300 text-center">{{ $preview['max_units'] }}</td>
                                        <td class="px-3 py-2 text-right">
                                            @if(($preview['status'] ?? '') === 'invalid')
                                                <span class="inline-flex px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded font-black text-[8px] uppercase" title="{{ implode(' ', $preview['errors'] ?? []) }}">Invalid</span>
                                            @elseif(($preview['status'] ?? '') === 'duplicate')
                                                <span class="inline-flex px-2 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded font-black text-[8px] uppercase">Duplicate</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded font-black text-[8px] uppercase">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button wire:click="processImport" 
                            wire:loading.attr="disabled"
                            class="w-full py-4 mt-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-2xl font-black uppercase text-xs shadow-lg hover:shadow-xl transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                        <span wire:loading.remove>✓ Finalize Import ({{ count($importPreview) }} records)</span>
                        <span wire:loading wire:target="processImport">⏳ Importing...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

    {{-- MODAL: Bulk Delete Confirm --}}
    <div x-show="confirmDelete" 
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm" 
        x-cloak 
        x-transition
        @keydown.window.escape="confirmDelete = false">
        <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-800"
            @click.away="confirmDelete = false">
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 animate-bounce">
                ⚠️
            </div>
            <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter uppercase">Purge Records?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 mt-3 italic font-medium">
                You are about to delete <span class="text-red-600 dark:text-red-400 font-black">{{ count($selectedFaculty) }}</span> entries permanently.
            </p>
            <div class="flex flex-col space-y-3">
                <button 
                    wire:click="deleteSelected" 
                    wire:loading.attr="disabled"
                    @keydown.window.enter="confirmDelete = false; $wire.deleteSelected()"
                    class="w-full py-4 bg-red-600 dark:bg-red-700 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-red-500/20 hover:bg-red-700 dark:hover:bg-red-600 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading.remove>🗑️ Confirm Delete (Enter)</span>
                    <span wire:loading wire:target="deleteSelected">⏳...</span>
                </button>
                <button 
                    @click="confirmDelete = false" 
                    class="w-full py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    ✕ Cancel (Esc)
                </button>
            </div>
        </div>
    </div>

</div>
