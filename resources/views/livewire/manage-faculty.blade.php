<div class="flex h-screen bg-[#F8FAFC]" x-data="{ open: @entangle('showModal'), bulk: @entangle('bulkOpen') }">
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header Section --}}
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Faculty Registry</h2>
                <p class="text-sm text-slate-400 font-medium italic">Academic Personnel Management</p>
            </div>
            
            <div class="flex items-center space-x-3">
                {{-- Export Button --}}
                <button wire:click="exportCSV" wire:loading.attr="disabled" class="px-6 py-3 bg-green-50 text-green-600 rounded-2xl font-black text-xs uppercase italic hover:bg-green-100 transition-all border border-green-100 disabled:opacity-50">
                    <span wire:loading.remove wire:target="exportCSV">📊 Export CSV</span>
                    <span wire:loading wire:target="exportCSV">⏳ Exporting...</span>
                </button>

                {{-- Bulk Import Trigger --}}
                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                    <button @click="bulk = true" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs uppercase italic hover:bg-slate-200 transition-all">
                        📥 Bulk Import
                    </button>
                @endif

                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all text-xs uppercase">
                    + {{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 'Add Faculty' : 'Request New Faculty' }}
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-12 py-8">
            <div class="grid grid-cols-12 gap-8">
                
                {{-- Left Side --}}
                <div class="col-span-12 lg:col-span-9 space-y-8">
                    
                    {{-- 1. ADMIN/REGISTRAR VIEW: Approval Queue --}}
                    @if(in_array(auth()->user()->role, ['admin', 'registrar']) && $pendingRequests->count() > 0)
                        <div class="animate-pulse-subtle">
                            <div class="bg-amber-50 border-2 border-amber-200 rounded-[2rem] p-6 shadow-sm">
                                <h3 class="text-amber-800 font-black uppercase text-xs tracking-widest flex items-center mb-4 px-4">
                                    <span class="mr-2 text-lg">🔔</span> Incoming Requests ({{ $pendingRequests->count() }})
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($pendingRequests as $request)
                                        <div class="bg-white border border-amber-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                                            <div>
                                                <p class="text-slate-800 font-bold text-sm">{{ $request->full_name }}</p>
                                                <p class="text-[10px] text-slate-400 font-black uppercase tracking-tight">{{ $request->department }}</p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button wire:click="approveFaculty({{ $request->id }})" class="p-2 bg-green-100 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all">✓</button>
                                                <button wire:click="declineFaculty({{ $request->id }})" class="p-2 bg-red-100 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all">✕</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @php $userRole = auth()->user()->role; @endphp
                        @if(in_array($userRole, ['dean', 'oic', 'associate_dean']) && $pendingRequests->count() > 0)
                            <div class="mb-8 bg-blue-600 rounded-[2rem] p-6 shadow-xl shadow-blue-100 animate-pulse-subtle">
                                <h3 class="text-white font-black text-xs uppercase tracking-widest mb-4 flex items-center px-2">
                                    <span class="mr-2">⏳</span> Your Pending Registration Requests
                                </h3>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($pendingRequests as $request)
                                        <div class="bg-blue-500/30 border border-blue-400/30 px-4 py-2 rounded-2xl flex items-center group transition-all">
                                            <div class="w-2 h-2 bg-yellow-300 rounded-full animate-pulse mr-3"></div>
                                            <span class="text-white font-bold text-sm">{{ $request->full_name }}</span>
                                            <p class="text-[10px] text-blue-200 ml-2 group-hover:text-blue-100 transition-colors">/ {{ $request->department }}</p>
                                            {{-- Add a Cancel button if needed --}}
                                            <button wire:click="declineFaculty({{ $request->id }})" class="ml-4 text-[9px] bg-white/10 hover:bg-red-500 text-white px-2 py-1 rounded-lg font-black uppercase transition-colors">Cancel</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    {{-- Search and Filters --}}
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-[2rem] border border-slate-200 shadow-sm">
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>             
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or ID..." 
                                class="w-full pl-14 pr-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-slate-400">
                        </div>

                        <div class="flex items-center gap-2 bg-gray-100/50 p-1.5 rounded-2xl">
                            @if($this->isAdminOrRegistrar())
                                {{-- Show all filters for Admin/Registrar --}}
                                @foreach(['ALL', 'CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                    <button wire:click="$set('filterDepartment', '{{ $dept == 'ALL' ? '' : $dept }}')" 
                                        class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filterDepartment == ($dept == 'ALL' ? '' : $dept)) ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                                        {{ $dept }}
                                    </button>
                                @endforeach
                            @else
                                {{-- Only show the Dean's specific department --}}
                                <div class="px-6 py-1.5 bg-white text-blue-600 shadow-sm rounded-xl text-xs font-black uppercase tracking-wider">
                                    Department: {{ auth()->user()->department }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Main Table --}}
                    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-400 uppercase font-black text-[10px] tracking-widest">
                                <tr>
                                    <th class="px-10 py-5">Status</th>
                                    <th class="px-10 py-5">ID Number</th>
                                    <th class="px-10 py-5">Full Name</th>
                                    <th class="px-10 py-5">Department</th>
                                    <th class="px-10 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($faculties as $faculty)
                                    <tr class="hover:bg-blue-50/30 transition-all">
                                        <td class="px-10 py-6">
                                            @if($faculty->status === 'approved')
                                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-md text-[9px] font-black uppercase">Active</span>
                                            @else
                                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-md text-[9px] font-black uppercase">{{ $faculty->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-10 py-6 font-black text-slate-400 italic">{{ $faculty->employee_id }}</td>
                                        <td class="px-10 py-6 font-bold text-slate-800">{{ $faculty->full_name }}</td>
                                        <td class="px-10 py-6 text-blue-600 font-black uppercase text-[10px]">{{ $faculty->department }}</td>
                                        <td class="px-10 py-6 text-right space-x-2">
                                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                                <button wire:click="editFaculty({{ $faculty->id }})" class="text-blue-600 font-black text-xs uppercase hover:underline">Edit</button>
                                                <button wire:click="deleteFaculty({{ $faculty->id }})" wire:confirm="Permanently delete this record?" class="text-red-400 font-black text-xs uppercase hover:text-red-600">Delete</button>
                                            @else
                                                <span class="text-[9px] text-slate-300 font-bold uppercase italic">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-10 py-20 text-center text-slate-400 font-black uppercase text-xs italic">No records found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $faculties->links() }}</div>
                </div>

                {{-- Right Side: Activity Logs --}}
                <div class="col-span-12 lg:col-span-3">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm sticky top-8">
                        <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest mb-6">Recent Activity</h3>
                        <div class="space-y-6">
                            @foreach($recentLogs as $log)
                                <div class="relative pl-6 border-l-2 border-slate-100">
                                    <div class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-blue-500"></div>
                                    <p class="text-xs font-bold text-slate-700 leading-tight">{{ $log->description }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase font-black">{{ $log->user->name }} • {{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Request/Edit Modal --}}
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="open = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-6">
                {{ $isEditMode ? 'Update Record' : 'New Registration' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Employee ID</label>
                    <input type="text" wire:model="employee_id" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    @error('employee_id') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Full Name</label>
                    <input type="text" wire:model="full_name" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    @error('full_name') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Email Address</label>
                    <input type="email" wire:model="email" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 block">Department</label>
                    <select wire:model="department" {{ !in_array(auth()->user()->role, ['admin', 'registrar']) ? 'disabled' : '' }} class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Select Department</option>
                        <option value="CCS">CCS</option>
                        <option value="CTE">CTE</option>
                        <option value="COC">COC</option>
                        <option value="SHTM">SHTM</option>
                    </select>
                </div>

                <button wire:click="{{ $isEditMode ? 'updateFaculty' : 'saveFaculty' }}" class="w-full py-5 mt-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl hover:bg-blue-700 transition-all">
                    {{ $isEditMode ? 'Save Changes' : 'Confirm Registration' }}
                </button>
            </div>
        </div>
    </div>

    {{-- Bulk Import Modal --}}
    {{-- Bulk Import Modal --}}
<div x-show="bulk" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" 
     x-cloak 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    
    <div class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulk = false">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Batch Import</h3>
                <p class="text-sm text-slate-400 font-medium">Upload CSV: <span class="font-bold text-slate-600">ID, Name, Email, Dept</span></p>
            </div>
            <button @click="bulk = false" class="text-slate-300 hover:text-slate-600">✕</button>
        </div>

        <div class="space-y-6">
            {{-- Dropzone --}}
            <div class="relative border-2 border-dashed {{ $importFile ? 'border-green-400 bg-green-50/30' : 'border-slate-200 hover:border-blue-400' }} rounded-3xl p-10 text-center transition-all">
                <input type="file" wire:model="importFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                
                <div class="space-y-2">
                    <div class="text-3xl">📁</div>
                    <div class="text-slate-500 font-bold text-sm">
                        <span wire:loading.remove wire:target="importFile">
                            {{ $importFile ? 'File Selected: ' . $importFile->getClientOriginalName() : 'Click or drag CSV here' }}
                        </span>
                        <span wire:loading wire:target="importFile" class="text-blue-600 animate-pulse">Parsing data...</span>
                    </div>
                </div>
            </div>

            {{-- Preview Table --}}
            @if(count($importPreview) > 0)
                <div class="max-h-64 overflow-y-auto border border-slate-100 rounded-2xl bg-slate-50/50">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-white shadow-sm text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-4 py-3">ID Number</th>
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
                                            <span class="px-2 py-1 bg-green-100 text-green-600 rounded-lg font-black text-[9px] uppercase">Ready</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-3">
                    <button wire:click="processImport" 
                            wire:loading.attr="disabled"
                            class="flex-1 py-5 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="processImport">🚀 Finalize Import ({{ count($importPreview) }})</span>
                        <span wire:loading wire:target="processImport">⚙️ Processing...</span>
                    </button>
                </div>
            @endif
            
            <button @click="bulk = false" class="w-full py-2 text-slate-400 font-black uppercase text-[10px] tracking-widest hover:text-red-500 transition-colors">Cancel Operation</button>
        </div>
    </div>
</div>
</div>
