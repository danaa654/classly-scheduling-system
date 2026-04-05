<div class="flex h-screen bg-[#F8FAFC]" x-data="{ open: @entangle('showModal'), bulk: @entangle('bulkOpen') }">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Faculty Registry</h2>
                <p class="text-sm text-slate-400 font-medium italic">Academic Personnel Management</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <button @click="bulk = true" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs uppercase italic hover:bg-slate-200 transition-all">
                    📥 Bulk Import
                </button>
                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all text-xs uppercase">
                    + Add Faculty
                </button>
            </div>
        </header>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-[2rem] border border-slate-200 shadow-sm mb-6">
            
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by name or employee ID..." 
                    class="w-full pl-14 pr-6 py-4 bg-slate-50 border-none rounded-2xl font-bold text-sm focus:ring-2 focus:ring-blue-500 transition-all"
                >
            </div>

            <div class="flex p-1 bg-slate-100 rounded-2xl space-x-1">
                <button wire:click="$set('filterDepartment', '')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $filterDepartment == '' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                    All
                </button>
                @foreach(['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                    <button wire:click="$set('filterDepartment', '{{ $dept }}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $filterDepartment == $dept ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                        {{ $dept }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-12 overflow-y-auto space-y-6">
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-400 uppercase font-black text-[10px] tracking-widest">
                        <tr>
                            <th class="px-10 py-5">ID Number</th>
                            <th class="px-10 py-5">Full Name</th>
                            <th class="px-10 py-5">Department</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($faculties as $faculty)
                        <tr class="hover:bg-blue-50/30 transition-all">
                            <td class="px-10 py-6 font-black text-slate-400 italic">{{ $faculty->employee_id }}</td>
                            <td class="px-10 py-6 font-bold text-slate-800">{{ $faculty->full_name }}</td>
                            <td class="px-10 py-6">
                                <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-black uppercase">{{ $faculty->department }}</span>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <button wire:click="editFaculty({{ $faculty->id }})" class="text-blue-600 font-black text-xs uppercase hover:underline mr-4">Edit</button>
                                <button wire:click="deleteFaculty({{ $faculty->id }})" wire:confirm="Remove this faculty member?" class="text-red-400 font-black text-xs uppercase hover:text-red-600">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-10 py-20 text-center text-slate-400 font-black uppercase text-xs">No faculty found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $faculties->links() }}</div>
        </div>
    </main>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="open = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-6">{{ $isEditMode ? 'Edit Faculty' : 'New Faculty' }}</h3>
            <div class="space-y-4">
                <input type="text" wire:model="employee_id" placeholder="Employee ID" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                @error('employee_id') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                
                <input type="text" wire:model="full_name" placeholder="Full Name" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                @error('full_name') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                
                <input type="email" wire:model="email" placeholder="Email" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                
                <select wire:model="department" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                    <option value="CCS">CCS</option>
                    <option value="CTE">CTE</option>
                    <option value="COC">COC</option>
                    <option value="SHTM">SHTM</option>
                </select>

                <button wire:click="{{ $isEditMode ? 'updateFaculty' : 'saveFaculty' }}" class="w-full py-4 mt-2 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">
                    {{ $isEditMode ? 'Update' : 'Save' }} Faculty
                </button>
            </div>
        </div>
    </div>

    <div x-show="bulk" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulk = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">Bulk Import</h3>
            <p class="text-xs text-slate-400 mb-6 font-medium italic">CSV Format: ID, Name, Email, Dept</p>
            
            <form wire:submit.prevent="importFaculty" class="space-y-6">
                <div class="border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 cursor-pointer relative">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    <span class="text-3xl mb-2">📄</span>
                    <span class="text-xs font-bold text-slate-600">
                        {{ $importFile ? $importFile->getClientOriginalName() : 'Select CSV File' }}
                    </span>
                </div>
                
                @error('importFile') 
                    <p class="text-red-500 text-[10px] font-bold text-center">{{ $message }}</p> 
                @enderror
                
                <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl shadow-blue-100">
                    <span wire:loading.remove wire:target="importFaculty">Start Processing</span>
                    <span wire:loading wire:target="importFaculty">Processing...</span>
                </button>
            </form>
        </div>
    </div>
</div>