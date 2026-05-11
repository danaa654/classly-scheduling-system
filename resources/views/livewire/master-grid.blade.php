<div class="flex h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 overflow-hidden"
     x-data="{
        roomsOpen: true,
        subjectsOpen: true,
        showConflictModal: false,
        conflictData: {
            type: '',
            title: '',
            message: '',
            details: {}
        }
     }"
     @room-selected.window="roomsOpen = false"
     @show-conflict-modal.window="
        console.log('🔴 CONFLICT MODAL EVENT RECEIVED:', \$event.detail);
        conflictData = \$event.detail.conflictData || \$event.detail[0] || \$event.detail;
        showConflictModal = true;
        console.log('✅ CONFLICT MODAL STATE UPDATED:', conflictData);
     ">

    <main class="flex-1 flex flex-col min-w-0 relative h-full">

        {{-- HEADER --}}
        <header class="h-16 bg-white/50 dark:bg-slate-900/50 border-b-2 border-slate-300 dark:border-slate-700 backdrop-blur-md flex items-center justify-between px-6 z-20 shrink-0 shadow-md">
            <div class="flex flex-col justify-center">
                <h2 class="text-xl font-black text-slate-900 dark:text-slate-50 uppercase tracking-tight">
                    Master <span class="text-blue-600 dark:text-blue-400">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[8px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest">{{ $schoolYear }} • {{ $semester }} SEM</span>
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

            <div class="flex items-center gap-3">
                <!-- AI GENERATION BUTTON -->
                <button 
                    class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-indigo-400 dark:border-indigo-600 bg-indigo-100/70 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-200/70 dark:hover:bg-indigo-900/50 shadow-md hover:shadow-lg"
                    title="Generate schedule using AI">
                    <span>✨</span>
                    AI Generate
                </button>

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

    {{-- ===== CONFLICT ALERT MODAL (FIXED & COMPLETE) ===== --}}
    <div 
        x-show="showConflictModal"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-md z-[9999] flex items-center justify-center p-4"
        @click.self="showConflictModal = false"
    >
        
        {{-- MODAL CARD --}}
        <div 
            x-show="showConflictModal"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="scale-95 opacity-0 -translate-y-4"
            x-transition:enter-end="scale-100 opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="scale-100 opacity-100 translate-y-0"
            x-transition:leave-end="scale-95 opacity-0 -translate-y-4"
            class="relative w-full max-w-2xl bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-2xl border-2 border-red-200 dark:border-red-900/50 overflow-hidden"
            @click.stop
        >
            
            {{-- TOP ACCENT BAR --}}
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-500 via-orange-500 to-red-600"></div>

            {{-- CONTENT --}}
            <div class="p-8 space-y-6 mt-2">
                
                {{-- HEADER SECTION --}}
                <div class="flex items-start gap-4">
                    {{-- ICON --}}
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-red-100/80 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800/50">
                            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    {{-- TITLE & SUBTITLE --}}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wider break-words" x-text="conflictData.title">
                            ⚠️ SCHEDULE CONFLICT
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-semibold uppercase tracking-widest">
                            Scheduling Issue Detected
                        </p>
                    </div>
                </div>

                {{-- MAIN MESSAGE --}}
                <div class="bg-red-50/50 dark:bg-red-950/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-lg">
                    <p class="text-lg font-semibold text-red-800 dark:text-red-300" x-text="conflictData.message">
                        This scheduling request conflicts with an existing schedule.
                    </p>
                </div>

                {{-- CONFLICT DETAILS GRID --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Conflict Type --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">🔴 Conflict Type</p>
                        <p class="text-base font-black text-slate-900 dark:text-white break-words">
                            <template x-if="conflictData.type === 'SECTION_CONFLICT'">
                                <span>🎓 STUDENT GROUP</span>
                            </template>
                            <template x-if="conflictData.type === 'FACULTY_CONFLICT'">
                                <span>👨‍🏫 FACULTY</span>
                            </template>
                            <template x-if="conflictData.type === 'ROOM_CONFLICT'">
                                <span>🏢 ROOM</span>
                            </template>
                            <template x-if="!['SECTION_CONFLICT', 'FACULTY_CONFLICT', 'ROOM_CONFLICT'].includes(conflictData.type)">
                                <span x-text="conflictData.type">UNKNOWN</span>
                            </template>
                        </p>
                    </div>

                    {{-- Reason --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">💡 Issue</p>
                        <p class="text-base font-black text-orange-600 dark:text-orange-400">Already Scheduled</p>
                    </div>

                    {{-- Requested Subject --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">📝 You Want</p>
                        <p class="text-base font-black text-blue-700 dark:text-blue-300 break-words" x-text="conflictData.details?.requested_subject || 'N/A'">
                            CS-101
                        </p>
                    </div>

                    {{-- Conflicting Subject --}}
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-xs font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-2">❌ Conflict With</p>
                        <p class="text-base font-black text-red-700 dark:text-red-300 break-words" x-text="conflictData.details?.conflicting_subject || 'N/A'">
                            CS-102
                        </p>
                    </div>

                    {{-- Conflicting Room --}}
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                        <p class="text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-2">🏢 In Room</p>
                        <p class="text-base font-black text-purple-700 dark:text-purple-300 break-words" x-text="conflictData.details?.conflicting_room || 'N/A'">
                            Room 201
                        </p>
                    </div>

                    {{-- Faculty/Group Info --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <template x-if="conflictData.type === 'FACULTY_CONFLICT'">
                            <div>
                                <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">👨‍🏫 Faculty</p>
                                <p class="text-base font-black text-slate-900 dark:text-white break-words" x-text="conflictData.details?.faculty_name || 'Unknown'">Dr. Smith</p>
                            </div>
                        </template>
                        <template x-if="conflictData.type === 'SECTION_CONFLICT'">
                            <div>
                                <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">👥 Student Group</p>
                                <p class="text-base font-black text-slate-900 dark:text-white break-words" x-text="conflictData.details?.group || 'Unknown'">CCS-IT-1-A</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- TIME COMPARISON --}}
                <div class="space-y-3">
                    <p class="text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">⏰ Time Details</p>
                    
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Requested Time --}}
                        <div class="bg-blue-50 dark:bg-blue-950/20 border-2 border-blue-200 dark:border-blue-800 p-4 rounded-lg">
                            <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-2">✅ Requested Time</p>
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 dark:text-white" x-text="conflictData.details?.requested_day || 'Monday'">Monday</p>
                                <p class="text-lg font-black text-blue-600 dark:text-blue-400">
                                    <span x-text="(conflictData.details?.requested_start || '9:00 AM')"></span>
                                    <span class="text-sm">-</span>
                                    <span x-text="(conflictData.details?.requested_end || '10:00 AM')"></span>
                                </p>
                            </div>
                        </div>

                        {{-- Conflicting Time --}}
                        <div class="bg-red-50 dark:bg-red-950/20 border-2 border-red-200 dark:border-red-800 p-4 rounded-lg">
                            <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wide mb-2">❌ Conflicting Time</p>
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 dark:text-white" x-text="conflictData.details?.conflicting_day || 'Monday'">Monday</p>
                                <p class="text-lg font-black text-red-600 dark:text-red-400">
                                    <span x-text="(conflictData.details?.conflicting_start || '9:00 AM')"></span>
                                    <span class="text-sm">-</span>
                                    <span x-text="(conflictData.details?.conflicting_end || '10:00 AM')"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUGGESTION --}}
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border-2 border-yellow-200 dark:border-yellow-800 p-4 rounded-lg">
                    <p class="text-sm font-bold text-yellow-800 dark:text-yellow-300 flex items-start gap-3">
                        <span class="text-2xl mt-0.5 flex-shrink-0">💡</span>
                        <span x-text="conflictData.details?.suggestion || 'Please choose a different time or resource to resolve this conflict.'">
                            Suggestion text here
                        </span>
                    </p>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-slate-900/30 dark:to-slate-800/30 border-t border-red-200 dark:border-red-900/30 p-6 flex gap-3 justify-between">
                <button 
                    @click="showConflictModal = false"
                    class="flex-1 px-6 py-3 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-black uppercase text-sm tracking-wider transition-all shadow-md hover:shadow-lg transform hover:scale-105 active:scale-95">
                    ← TRY DIFFERENT TIME
                </button>
                <button 
                    @click="showConflictModal = false"
                    class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 dark:from-red-700 dark:to-orange-700 dark:hover:from-red-600 dark:hover:to-orange-600 text-white font-black uppercase text-sm tracking-wider transition-all shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95">
                    ✓ UNDERSTOOD
                </button>
            </div>
        </div>
    </div>
    {{-- ===== END CONFLICT ALERT MODAL ===== --}}
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

    [x-cloak] {
        display: none !important;
    }
</style>
