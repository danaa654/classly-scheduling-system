<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900" 
     x-data="{ 
        open: @entangle('showModal'), 
        bulkOpen: @entangle('bulkOpen'),
        confirmDelete: @entangle('confirmingDeletion') 
     }">
    
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Room Management</h2>
                <p class="text-sm text-slate-400 font-medium italic">Institutional Space Allocation</p>
            </div>
            
            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
            <div class="flex items-center space-x-3">
                @if(count($selectedRooms) > 0)
                    <button wire:click="$set('confirmingDeletion', true)" class="px-6 py-3 bg-red-50 text-red-600 rounded-2xl font-black hover:bg-red-100 transition-all border border-red-200 shadow-sm animate-pulse">
                        🗑️ Delete Selected ({{ count($selectedRooms) }})
                    </button>
                @endif

                <button @click="bulkOpen = true" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black hover:bg-slate-200 transition-all flex items-center border border-slate-200">
                    <span class="mr-2 text-lg">📥</span> Bulk Import
                </button>

                <button wire:click="openModal" class="group relative px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 overflow-hidden transition-all active:scale-95">
                    <span class="relative z-10">+ Add New Room</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                </button>
            </div>
            @endif
        </header>

        <div class="p-12 overflow-y-auto space-y-6 custom-scrollbar">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                <div class="relative flex-1 max-w-lg">
                    <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">🔍</span>
                    <input type="text" wire:model.live="search" placeholder="Search by room name or type..." 
                           class="w-full pl-14 pr-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500 transition-all text-sm">
                </div>
                
                <div class="flex items-center space-x-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest px-2">Filter Type:</label>
                    <select wire:model.live="filterType" class="bg-slate-50 border-none rounded-xl font-bold text-xs uppercase p-3 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="LECTURE">Lecture</option>
                        <option value="LAB">Lab</option>
                    </select>
                </div>
            </div>

            @if (session()->has('message'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                     class="p-4 bg-green-50 text-green-700 rounded-2xl font-bold border border-green-100 flex items-center shadow-sm transition-all">
                    <span class="mr-3">✅</span> {{ session('message') }}
                </div>
            @endif

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/50 text-slate-400 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-6 py-5 w-10">
                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </th>
                            
                            <th class="px-10 py-5">Room Details</th>
                            <th class="px-10 py-5 text-center">Classification</th>
                            <th class="px-10 py-5">Capacity Index</th>
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                            <th class="px-10 py-5 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rooms as $room)
                        <tr class="hover:bg-blue-50/30 transition-colors group {{ in_array($room->id, $selectedRooms) ? 'bg-blue-50/50' : '' }}">
                            <td class="px-6 py-6">
                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                <input type="checkbox" wire:model.live="selectedRooms" value="{{ $room->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-lg uppercase tracking-tight">{{ $room->room_name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $room->building ?? 'Main Campus' }}</span>
                                </div>
                            </td>
                            <td class="px-10 py-6 text-center">
                                <span class="px-4 py-1.5 {{ strtoupper($room->type) === 'LAB' ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-100 text-blue-700 border-blue-200' }} border rounded-xl text-[10px] uppercase font-black tracking-tighter">
                                    {{ $room->type }}
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex flex-col">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-slate-800 font-black text-lg tracking-tight">{{ $room->capacity }}</span>
                                        <span class="text-slate-400 text-[8px] font-bold uppercase tracking-widest">Max Seats</span>
                                    </div>
                                    </div>
                            </td>
                            
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                            <td class="px-10 py-6 text-right space-x-2">
                                <button wire:click="editRoom({{ $room->id }})" class="p-2 bg-white border border-slate-200 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all font-black text-[10px] uppercase px-4 shadow-sm">
                                    Edit
                                </button>
                                <button onclick="confirm('Permanently remove this room?') || event.stopImmediatePropagation()" 
                                        wire:click="deleteRoom({{ $room->id }})" 
                                        class="p-2 bg-white border border-slate-200 text-red-600 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all font-black text-[10px] uppercase px-4 shadow-sm">
                                    Delete
                                </button>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-10 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-5xl mb-4">🏫</span>
                                    <p class="text-slate-400 font-black uppercase tracking-widest text-xs">No space allocation records found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 mb-10">
                {{ $rooms->links('livewire.custom-pagination') }}
            </div>
        </div>
    </main>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-12 shadow-2xl border border-slate-200" @click.away="open = false">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-3xl font-black text-slate-800 tracking-tighter">
                    {{ $isEditMode ? 'Edit Room' : 'Add New Room' }}
                </h3>
                <button @click="open = false" class="text-slate-300 hover:text-slate-600 transition-colors">✕</button>
            </div>
            
            <form wire:submit.prevent="{{ $isEditMode ? 'updateRoom' : 'saveRoom' }}" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Room Identifier</label>
                    <input type="text" wire:model="room_name" placeholder="e.g. Room 302" 
                           class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500 shadow-inner">
                    @error('room_name') <span class="text-red-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Room Type</label>
                        <select wire:model="type" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500 shadow-inner">
                            <option value="LECTURE">Lecture</option>
                            <option value="LAB">Lab</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 ml-2">Capacity</label>
                        <input type="number" wire:model="capacity" 
                               class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl font-bold focus:ring-2 focus:ring-blue-500 shadow-inner">
                        @error('capacity') <span class="text-red-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex space-x-4 pt-6">
                    <button type="button" @click="open = false" class="flex-1 font-black text-slate-400 uppercase tracking-widest text-xs hover:text-slate-600 transition-colors">Cancel</button>
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
            <p class="text-xs text-slate-400 mb-6 font-medium italic underline decoration-blue-500/30">Batch space allocation via CSV.</p>
            
            <form wire:submit.prevent="importRooms" class="space-y-6">
                <div class="group border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50 hover:bg-white hover:border-blue-400 transition-all cursor-pointer relative shadow-inner">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    <span class="text-4xl mb-3 transition-transform group-hover:scale-110">📁</span>
                    <span class="text-sm font-bold text-slate-600 text-center">
                        {{ $importFile ? $importFile->getClientOriginalName() : 'Click or drag file here' }}
                    </span>
                    <span class="text-[9px] text-slate-400 mt-2 uppercase font-black tracking-widest">CSV format required</span>
                    @error('importFile') <span class="text-red-500 text-[10px] font-bold mt-2 uppercase text-center">{{ $message }}</span> @enderror
                </div>

                <div class="flex space-x-4 pt-2">
                    <button type="button" @click="bulkOpen = false" class="flex-1 font-black text-slate-400 uppercase tracking-widest text-xs">Close</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all uppercase text-xs">
                        Upload Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="confirmDelete" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-red-100" @click.away="confirmDelete = false">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner">⚠️</div>
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">Are you sure?</h3>
            <p class="text-sm text-slate-500 mb-8 font-medium italic">You are about to delete <span class="text-red-600 font-black">{{ count($selectedRooms) }}</span> room(s). This cannot be undone.</p>
            
            <div class="flex flex-col space-y-3">
                <button wire:click="deleteSelected" 
                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-black shadow-lg shadow-red-200 hover:bg-red-700 transition-all uppercase text-xs">
                    Yes, Delete Permanently
                </button>
                
                <button @click="confirmDelete = false" 
                        class="w-full py-4 bg-slate-100 text-slate-400 rounded-2xl font-black hover:bg-slate-200 transition-all uppercase text-xs">
                    No, Cancel
                </button>
            </div>
        </div>
    </div>
</div>