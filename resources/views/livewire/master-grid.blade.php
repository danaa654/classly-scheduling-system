<div class="flex h-screen bg-[#F8FAFC] overflow-hidden" 
     x-data="{ roomsOpen: true, subjectsOpen: true }">
    
    @include('sidebar')

    <main class="flex-1 flex flex-col min-w-0 relative h-full">
        
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-20 shrink-0 shadow-sm">
            <div class="flex flex-col ml-[5%]">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">
                    Master <span class="text-blue-600">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Active Filter:</span>
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-black uppercase">
                        {{ $selectedRoomName ?? ($selectedDept ? $selectedDept . " " . ($selectedYear ?? '') . "-" . ($selectedMajor ?? '') : 'All Institutional Facilities') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 mr-4">
                    <button @click="subjectsOpen = !subjectsOpen" 
                            :class="subjectsOpen ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="subjectsOpen ? 'bg-blue-600' : 'bg-slate-300'" class="w-2 h-2 rounded-full"></span>
                        SUBJECTS
                    </button>
                    <button @click="roomsOpen = !roomsOpen" 
                            :class="roomsOpen ? 'bg-white text-purple-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all flex items-center gap-2">
                        <span :class="roomsOpen ? 'bg-purple-600' : 'bg-slate-300'" class="w-2 h-2 rounded-full"></span>
                        ROOMS
                    </button>
                </div>

                <button class="px-6 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-blue-200 active:scale-95">
                    ✨ Auto-Generate
                </button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden bg-slate-50">
            
            <main class="flex-1 overflow-auto relative custom-scrollbar">
                @include('livewire.schedule-grid')
            </main>

            <div class="flex h-full border-l border-slate-200 bg-white" 
                 x-show="roomsOpen || subjectsOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 class="shadow-2xl">
                @include('livewire.master-grid-sidebar')
            </div>
        </div>
    </main>
</div>

<style>
    /* Custom Scrollbar for the Corporate Aesthetic */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94A3B8;
    }
</style>