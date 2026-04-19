<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900" 
     x-data="{ 
        open: @entangle('showModal'), 
        bulkOpen: @entangle('bulkOpen'),
        confirmDelete: @entangle('confirmingDeletion') 
     }">
    
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header Section --}}
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Room Management</h2>
                <p class="text-sm text-slate-400 font-medium italic underline decoration-blue-500/20">Institutional Space Allocation</p>
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
            {{-- Search & Filter Bar --}}
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

            {{-- Rooms Table --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/50 text-slate-400 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-6 py-5 w-10 text-center">
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
                            <td class="px-6 py-6 text-center">
                                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                                <input type="checkbox" wire:model.live="selectedRooms" value="{{ $room->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-lg uppercase tracking-tight">{{ $room->room_name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Main Campus Building</span>
                                </div>
                            </td>
                            <td class="px-10 py-6 text-center">
                                <span class="px-4 py-1.5 {{ strtoupper($room->type) === 'LAB' ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-100 text-blue-700 border-blue-200' }} border rounded-xl text-[10px] uppercase font-black tracking-tighter">
                                    {{ $room->type }}
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex items-baseline space-x-1">
                                    <span class="text-slate-800 font-black text-lg tracking-tight">{{ $room->capacity }}</span>
                                    <span class="text-slate-400 text-[8px] font-bold uppercase tracking-widest">Max Seats</span>
                                </div>
                            </td>
                            
                            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                            <td class="px-10 py-6 text-right space-x-2 whitespace-nowrap">
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
                                    <span class="text-5xl mb-4 text-slate-300">🏫</span>
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

    {{-- --- ADD/EDIT MODAL --- --}}
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

    {{-- --- BULK IMPORT MODAL --- --}}
    <div x-show="bulkOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-xl rounded-[3rem] p-10 shadow-2xl border border-slate-200" @click.away="bulkOpen = false">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2 uppercase">Batch Room Import</h3>
            <p class="text-xs text-slate-400 mb-6 font-medium italic underline decoration-blue-500/30">CSV Required Header: <span class="text-slate-600 font-bold italic">room_name, capacity, type</span></p>
            
            <div class="space-y-6">
                <div class="group border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center justify-center bg-slate-50 hover:bg-white hover:border-blue-400 transition-all cursor-pointer relative shadow-inner"
                     wire:loading.class="opacity-50 pointer-events-none" wire:target="importFile">
                    
                    <input type="file" wire:model.live="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    
                    <div wire:loading wire:target="importFile" class="text-center">
                        <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                        <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Analyzing CSV Structure...</span>
                    </div>

                    <div wire:loading.remove wire:target="importFile" class="text-center">
                        <span class="text-4xl mb-3 transition-transform group-hover:scale-110 block">📊</span>
                        <span class="text-sm font-bold text-slate-600">
                            {{ $importFile ? $importFile->getClientOriginalName() : 'Drop room CSV here or click to browse' }}
                        </span>
                    </div>
                </div>
                
                @if(count($importPreview) > 0)
                    <div class="mt-6 border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="max-h-60 overflow-y-auto custom-scrollbar bg-white">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm">
                                    <tr class="text-[9px] uppercase font-black text-slate-400">
                                        <th class="px-4 py-3">Room Name</th>
                                        <th class="px-4 py-3">Type</th>
                                        <th class="px-4 py-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach($importPreview as $preview)
                                        <tr class="text-[11px] font-bold">
                                            <td class="px-4 py-3 text-slate-700">{{ $preview['room_name'] }}</td>
                                            <td class="px-4 py-3 text-slate-400 italic font-medium uppercase">{{ $preview['type'] }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="px-2 py-0.5 rounded-full text-[8px] uppercase font-black
                                                    {{ $preview['status'] === 'DUPLICATE' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                                                    {{ $preview['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button wire:click="processImport" wire:loading.attr="disabled" class="w-full py-4 bg-blue-600 text-white rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-blue-700 shadow-xl shadow-blue-100 transition-all">
                        <span wire:loading.remove wire:target="processImport">Confirm & Import Valid Rooms</span>
                        <span wire:loading wire:target="processImport">Saving to Database...</span>
                    </button>
                @endif

                <div class="flex justify-center">
                    <button type="button" @click="bulkOpen = false; $wire.reset(['importFile', 'importPreview'])" class="font-black text-slate-400 uppercase tracking-widest text-xs hover:text-slate-600 transition-colors">Close Importer</button>
                </div>
            </div>
        </div>
    </div>

    {{-- --- BULK DELETE CONFIRMATION --- --}}
    <div x-show="confirmDelete" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-red-100" @click.away="confirmDelete = false">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner">⚠️</div>
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter mb-2">Security Check</h3>
            <p class="text-sm text-slate-500 mb-8 font-medium italic">You are about to delete <span class="text-red-600 font-black">{{ count($selectedRooms) }}</span> room record(s). This action is permanent.</p>
            
            <div class="flex flex-col space-y-3">
                <button wire:click="deleteSelected" 
                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-black shadow-lg shadow-red-200 hover:bg-red-700 transition-all uppercase text-xs">
                    Yes, Delete Permanently
                </button>
                
                <button @click="confirmDelete = false" 
                        class="w-full py-4 bg-slate-100 text-slate-400 rounded-2xl font-black hover:bg-slate-200 transition-all uppercase text-xs">
                    No, Keep Records
                </button>
            </div>
        </div>
    </div>

    {{-- --- SWEETALERT SCRIPT --- --}}
    <script>
        document.addEventListener('livewire:init', () => {
            // Success/Error/Warning Listener
            Livewire.on('swal', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                
                // If the backend sent a simple success toast (best for fast actions)
                if (data.icon === 'success') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: 'success',
                        title: data.title,
                        text: data.text || ''
                    });
                } else {
                    // Regular Modal for Errors/Warnings
                    Swal.fire({
                        title: data.title || 'Notification',
                        text: data.text || '',
                        icon: data.icon || 'info',
                        confirmButtonColor: '#3B82F6',
                        confirmButtonText: 'Understood'
                    });
                }
            });

            // Handle network/timeout errors
            Livewire.on('livewire:error', (el, component, response) => {
                Swal.fire({
                    title: 'Connection Lost',
                    text: 'Unable to reach the server. Please check your connection.',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
                return false; 
            });
        });
    </script>
</div>