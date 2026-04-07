<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900" 
     x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Room Management</h2>
                <p class="text-sm text-slate-400 font-medium italic">Institutional Space Allocation</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <button @click="bulkOpen = true" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black hover:bg-slate-200 transition-all flex items-center border border-slate-200">
                    <span class="mr-2 text-lg">📥</span> Bulk Import
                </button>

                <button wire:click="openModal" class="group relative px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 overflow-hidden transition-all active:scale-95">
                    <span class="relative z-10">+ Add New Room</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                </button>
            </div>
        </header>

        <div class="p-12 overflow-y-auto space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                <div class="relative flex-1 max-w-lg">
                    <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">🔍</span>
                    <input type="text" wire:model.live="search" placeholder="Search by room name, building, or type..." 
                           class="w-full pl-14 pr-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500 transition-all text-sm">
                </div>
                
                <div class="flex items-center space-x-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest px-2">Filter:</label>
                    <select wire:model.live="filterType" class="bg-slate-50 border-none rounded-xl font-bold text-xs uppercase p-3 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                    </select>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="p-4 bg-green-50 text-green-700 rounded-2xl font-bold border border-green-100 flex items-center shadow-sm">
                    <span class="mr-3">✅</span> {{ session('message') }}
                </div>
            @endif

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-slate-400 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-10 py-5">Room Name</th>
                            <th class="px-10 py-5">Building / Classification</th>
                            <th class="px-10 py-5">Capacity</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rooms as $room)
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="px-10 py-6">
                                <span class="font-black text-slate-800 text-lg uppercase tracking-tight">{{ $room->room_name }}</span>
                            </td>
                            <td class="px-10 py-6">
                                <p class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">{{ $room->building }}</p>
                                <span class="px-3 py-1 {{ $room->type == 'Lab' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} rounded-lg text-[10px] uppercase font-black tracking-tighter">
                                    {{ $room->type }}
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex items-center space-x-2">
                                    <span class="text-slate-700 font-bold text-lg">{{ $room->capacity }}</span>
                                    <span class="text-slate-400 text-[10px] font-black uppercase">Seats</span>
                                </div>
                            </td>
                            <td class="px-10 py-6 text-right space-x-2">
                                <button wire:click="editRoom({{ $room->id }})" class="p-2 bg-slate-100 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all font-bold text-xs uppercase px-4">Edit</button>
                                <button onclick="confirm('Permanently remove this room?') || event.stopImmediatePropagation()" wire:click="deleteRoom({{ $room->id }})" class="p-2 bg-slate-100 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all font-bold text-xs uppercase px-4">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-10 py-32 text-center text-slate-400 font-black uppercase tracking-widest text-xs">
                                No records found for "{{ $search }}"
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $rooms->links() }}
            </div>
        </div>
    </main>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-12 shadow-2xl border border-slate-200" @click.away="open = false">
            <h3 class="text-3xl font-black text-slate-800 tracking-tighter mb-6">
                {{ $isEditMode ? 'Edit Room' : 'Add New Room' }}
            </h3>
            
            <form wire:submit.prevent="{{ $isEditMode ? 'updateRoom' : 'saveRoom' }}" class="space-y-5">
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Room Identifier</label>
                    <input type="text" wire:model="room_name" placeholder="e.g. Room 302" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                    @error('room_name') <span class="text-red-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Type</label>
                        <select wire:model="type" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                            <option value="Lecture">Lecture</option>
                            <option value="Lab">Lab</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Capacity</label>
                        <input type="number" wire:model="capacity" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex space-x-4 pt-4">
                    <button type="button" @click="open = false" class="flex-1 font-black text-slate-400 uppercase tracking-widest text-xs">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all uppercase text-xs">
                        {{ $isEditMode ? 'Update Details' : 'Confirm & Add' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="bulkOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulkOpen = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">Bulk Room Import</h3>
            <p class="text-xs text-slate-400 mb-6 font-medium">Upload a CSV file to batch-add rooms.</p>
            
            <form wire:submit.prevent="importRooms" class="space-y-6">
                <div class="border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer relative">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    <span class="text-4xl mb-3">📁</span>
                    <span class="text-sm font-bold text-slate-600">{{ $importFile ? $importFile->getClientOriginalName() : 'Click to select file' }}</span>
                    <span class="text-[10px] text-slate-400 mt-1 uppercase font-black tracking-widest">CSV supported</span>
                    @error('importFile') <span class="text-red-500 text-[10px] font-bold mt-2 uppercase text-center">{{ $message }}</span> @enderror
                </div>

                <div class="flex space-x-4">
                    <button type="button" @click="bulkOpen = false" class="flex-1 font-black text-slate-400 uppercase tracking-widest text-xs">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all uppercase text-xs">
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>