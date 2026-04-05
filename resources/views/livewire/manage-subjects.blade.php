<div class="flex h-screen bg-[#F8FAFC]" x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Subject Catalog</h2>
                <p class="text-sm text-slate-400 font-medium italic">Curriculum Management</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <button @click="bulkOpen = true" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black hover:bg-slate-200 transition-all border border-slate-200 text-xs uppercase">
                    📥 Bulk Import
                </button>
                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all text-xs uppercase">
                    + Add Subject
                </button>
            </div>
        </header>

        <div class="p-12 overflow-y-auto space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-[2rem] border border-slate-200 shadow-sm">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">🔍</span>
                    <input type="text" wire:model.live="search" placeholder="Search code or description..." class="w-full pl-12 pr-6 py-3 bg-slate-50 border-none rounded-xl font-bold text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex p-1 bg-slate-100 rounded-2xl space-x-1">
                    <button wire:click="$set('filterDepartment', '')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $filterDepartment == '' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">All</button>
                    @foreach(['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                        <button wire:click="$set('filterDepartment', '{{ $dept }}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $filterDepartment == $dept ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            {{ $dept }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-400 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-10 py-5">Code</th>
                            <th class="px-10 py-5">Description</th>
                            <th class="px-10 py-5">Units</th>
                            <th class="px-10 py-5">Department</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($subjects as $subject)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-10 py-6 font-black text-blue-600 uppercase">{{ $subject->subject_code }}</td>
                            <td class="px-10 py-6 text-slate-700 font-bold">{{ $subject->description }}</td>
                            <td class="px-10 py-6 text-slate-500 font-black">{{ $subject->units }}</td>
                            <td class="px-10 py-6">
                                <span class="px-3 py-1 bg-slate-100 rounded-lg text-[10px] font-black text-slate-500 uppercase">{{ $subject->department }}</span>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <button wire:click="editSubject({{ $subject->id }})" class="text-blue-600 font-black text-xs uppercase hover:underline mr-4">Edit</button>
                                <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Remove this subject permanently?" class="text-red-400 font-black text-xs uppercase hover:text-red-600">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-10 py-20 text-center text-slate-400 font-black uppercase text-xs tracking-widest">No subjects found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $subjects->links() }}</div>
        </div>
    </main>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="open = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-6">{{ $isEditMode ? 'Edit Subject' : 'New Subject' }}</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-2 ml-2">Subject Code</label>
                    <input type="text" wire:model="subject_code" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold uppercase focus:ring-2 focus:ring-blue-500">
                    @error('subject_code') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 mb-2 ml-2">Description</label>
                    <input type="text" wire:model="description" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                    @error('description') <span class="text-red-500 text-[10px] font-bold ml-2">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-2 ml-2">Units</label>
                        <input type="number" wire:model="units" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-2 ml-2">Department</label>
                        <select wire:model="department" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                            @foreach(['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button wire:click="{{ $isEditMode ? 'updateSubject' : 'saveSubject' }}" class="w-full py-4 mt-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl shadow-blue-100">
                    {{ $isEditMode ? 'Update Subject' : 'Save Subject' }}
                </button>
            </div>
        </div>
    </div>

    <div x-show="bulkOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulkOpen = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">Bulk Subject Import</h3>
            <p class="text-xs text-slate-400 mb-6 font-medium">Upload a CSV file (Code, Description, Units, Department).</p>
            
            <form wire:submit.prevent="importSubjects" class="space-y-6">
                <div class="border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 cursor-pointer relative">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    <span class="text-3xl mb-2">📁</span>
                    <span class="text-xs font-bold text-slate-600">{{ $importFile ? $importFile->getClientOriginalName() : 'Click to select CSV' }}</span>
                </div>
                <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase text-xs shadow-xl shadow-blue-100">Start Import</button>
            </form>
        </div>
    </div>
</div>