<div class="flex h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 overflow-hidden"
     x-data="{ roomsOpen: true, subjectsOpen: true }">

    <main class="flex-1 flex flex-col min-w-0 relative h-full">

        {{-- HEADER --}}
        <header class="h-16 bg-white/50 dark:bg-slate-900/50 border-b-2 border-slate-300 dark:border-slate-700 backdrop-blur-md flex items-center justify-between px-4 z-20 shrink-0 shadow-md">
            <div class="flex flex-col justify-center">
                <h2 class="text-xl font-black text-slate-900 dark:text-slate-50 uppercase tracking-tight">
                    Master <span class="text-blue-600 dark:text-blue-400">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[8px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest">Active:</span>
                    @if($selectedRoomName)
                        <span class="text-[8px] bg-emerald-100/80 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 px-2.5 py-0.5 rounded-lg font-black uppercase flex items-center gap-1 backdrop-blur-sm border-2 border-emerald-300 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 bg-emerald-600 dark:bg-emerald-400 rounded-full animate-pulse"></span>
                            🏢 {{ $selectedRoomName }}
                        </span>
                        @if($selectedRoomType)
                            <span class="text-[8px] {{ strtoupper($selectedRoomType) === 'LECTURE' ? 'bg-blue-100/80 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 border-blue-300 dark:border-blue-800' : 'bg-purple-100/80 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-300 dark:border-purple-800' }} px-2.5 py-0.5 rounded-lg font-black uppercase backdrop-blur-sm border-2">
                                {{ strtoupper($selectedRoomType) === 'LECTURE' ? '🎓 LEC' : '🔬 LAB' }}
                            </span>
                        @endif
                    @else
                        <span class="text-[8px] bg-slate-100/80 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 rounded-lg font-black uppercase backdrop-blur-sm border-2 border-slate-300 dark:border-slate-700">
                            📍 Select Room
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="flex bg-slate-100/70 dark:bg-slate-800/60 p-0.5 rounded-lg border-2 border-slate-300 dark:border-slate-700 backdrop-blur-sm">
                    <button 
                        @click="subjectsOpen = !subjectsOpen"
                        :class="subjectsOpen ? 'bg-white/80 dark:bg-slate-700/80 text-blue-600 dark:text-blue-400 shadow-md' : 'text-slate-500 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-transparent"
                        :style="subjectsOpen ? 'border-color: rgb(59, 130, 246)' : ''">
                        <span :class="subjectsOpen ? 'bg-blue-600' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full transition-colors"></span>
                        📚 Subjects
                    </button>
                    <button 
                        @click="roomsOpen = !roomsOpen"
                        :class="roomsOpen ? 'bg-white/80 dark:bg-slate-700/80 text-purple-600 dark:text-purple-400 shadow-md' : 'text-slate-500 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-transparent"
                        :style="roomsOpen ? 'border-color: rgb(147, 51, 234)' : ''">
                        <span :class="roomsOpen ? 'bg-purple-600' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full transition-colors"></span>
                        🏢 Rooms
                    </button>
                </div>
            </div>
        </header>

        {{-- MAIN CONTENT AREA --}}
        <div class="flex-1 flex overflow-hidden gap-0">

            {{-- GRID AREA --}}
            <main class="flex-1 overflow-hidden p-2">
                @include('livewire.schedule-grid')
            </main>

            {{-- SIDEBARS CONTAINER --}}
            <div 
                class="flex overflow-hidden bg-white/40 dark:bg-slate-900/30 border-l-2 border-slate-300 dark:border-slate-700 backdrop-blur-md shadow-2xl transition-all"
                x-show="roomsOpen || subjectsOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                @refreshGrid.window="$wire.$refresh()">
                @include('livewire.master-grid-sidebar')
            </div>
        </div>
    </main>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    
    @media (prefers-color-scheme: dark) {
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748B; }
    }
</style>




