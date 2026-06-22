<div wire:poll.3s class="min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500"
     x-data="{
         openCollege: null,
         openMajor: null,
         colleges: $wire.entangle('collegeWorkspaceStats'),
         colors: {
             CCS:  { bg: 'from-yellow-400 to-amber-500',   border: 'border-yellow-400',   ring: 'ring-yellow-400',   text: 'text-yellow-700 dark:text-yellow-300',   icon: 'text-yellow-500',   tab: 'bg-yellow-400',   light: 'bg-yellow-50 dark:bg-yellow-900/20',   badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',   folder: '#f59e0b', shadow: 'shadow-yellow-200 dark:shadow-yellow-900/30' },
             CTE:  { bg: 'from-blue-500 to-indigo-600',    border: 'border-blue-500',     ring: 'ring-blue-500',     text: 'text-blue-700 dark:text-blue-300',       icon: 'text-blue-500',     tab: 'bg-blue-500',     light: 'bg-blue-50 dark:bg-blue-900/20',       badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',       folder: '#3b82f6', shadow: 'shadow-blue-200 dark:shadow-blue-900/30' },
             COC:  { bg: 'from-violet-500 to-purple-600',  border: 'border-violet-500',   ring: 'ring-violet-500',   text: 'text-violet-700 dark:text-violet-300',   icon: 'text-violet-500',   tab: 'bg-violet-500',   light: 'bg-violet-50 dark:bg-violet-900/20',   badge: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300', folder: '#8b5cf6', shadow: 'shadow-violet-200 dark:shadow-violet-900/30' },
             SHTM: { bg: 'from-orange-400 to-red-500',     border: 'border-orange-400',   ring: 'ring-orange-400',   text: 'text-orange-700 dark:text-orange-300',   icon: 'text-orange-500',   tab: 'bg-orange-400',   light: 'bg-orange-50 dark:bg-orange-900/20',   badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300', folder: '#f97316', shadow: 'shadow-orange-200 dark:shadow-orange-900/30' }
         },
         icons: { CCS: '💻', CTE: '📖', COC: '⚖️', SHTM: '🏨' },
         selectMajor(college, majorCode) {
             this.openCollege = college;
             this.openMajor = majorCode;
         },
         isScheduleVisible() { return this.openCollege !== null && this.openMajor !== null; },
         completionPercent(college) {
             return college.blocksTotal ? Math.round((college.blocksComplete / college.blocksTotal) * 100) : 0;
         },
         collegeSections(college) {
             return Object.values(college.majors).flatMap(major => major.sections || []);
         }
     }">

    {{-- ════════════════════════════════════════════════════════════════════
         FOLDER NAVIGATION (screen only — the printable schedule lives in its
         own #official-print-block further down, so this whole block is
         excluded from print to avoid a duplicated/garbled title block)
    ════════════════════════════════════════════════════════════════════ --}}
    <div class="pt-6 pb-2 px-5 print:hidden">
        <div class="max-w-[1500px] mx-auto">

            {{-- Page title --}}
            <div class="mb-6 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center shadow-md">
                    <span class="text-xl">📋</span>
                </div>
                <div>
                    <h1 class="text-[22px] font-black uppercase tracking-tight text-slate-800 dark:text-slate-100 leading-none">
                        Block Schedule
                    </h1>
                    <p class="text-[12px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                        Select a department folder to view schedules
                    </p>
                </div>

                {{-- Breadcrumb (visible when a major is selected) --}}
                <div x-show="isScheduleVisible()" x-transition.opacity
                     class="ml-auto flex items-center gap-2 text-[13px] font-bold">
                    <button @click="openCollege = null; openMajor = null"
                            class="flex items-center gap-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        All Colleges
                    </button>
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <template x-for="(college, code) in colleges" :key="code">
                        <template x-if="code === openCollege">
                            <button @click="openMajor = null"
                                    class="flex items-center gap-1 transition-colors"
                                    :class="colors[code].text">
                                <span x-text="icons[code]"></span>
                                <span x-text="code"></span>
                            </button>
                        </template>
                    </template>
                    <template x-if="openMajor">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-200" x-text="openMajor"></span>
                        </span>
                    </template>
                </div>
            </div>

            {{-- ── COLLEGE FOLDER GRID ── --}}
            <div x-show="openCollege === null" x-transition.opacity
                 x-data="{
                     clockTime: '',
                     updateClock() {
                         const now = new Date();
                         let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
                         const ampm = h >= 12 ? 'PM' : 'AM';
                         h = h % 12 || 12;
                         this.clockTime = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') + ' ' + ampm;
                     },
                     init() { this.updateClock(); setInterval(() => this.updateClock(), 1000); }
                 }">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <template x-for="(college, code) in colleges" :key="code">
                        <button @click="openCollege = code"
                                class="group relative rounded-2xl overflow-visible text-left cursor-pointer transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02] active:scale-[0.98]"
                                :class="colors[code].shadow + ' shadow-lg hover:shadow-xl'">

                            {{-- Folder tab (top-left protruding tab) --}}
                            <div class="absolute -top-3.5 left-5 h-4 w-24 rounded-t-lg z-10 flex items-center px-2"
                                 :style="`background-color: ${colors[code].folder}; opacity: 0.85;`">
                                <span class="text-white text-[9px] font-black uppercase tracking-widest truncate" x-text="code"></span>
                            </div>

                            {{-- Folder body --}}
                            <div class="relative rounded-2xl overflow-hidden border-2 pt-3 pb-5 px-5 bg-white dark:bg-slate-800 transition-colors"
                                 :class="colors[code].border">

                                {{-- Colored top stripe --}}
                                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r rounded-t-xl"
                                     :class="colors[code].bg"></div>

                                {{-- Icon + name --}}
                                <div class="mt-3 flex flex-col items-center text-center gap-2">
                                    {{-- Big folder SVG icon with department color --}}
                                    <div class="relative">
                                        <svg class="w-16 h-16 transition-transform duration-300 group-hover:scale-110 drop-shadow-md"
                                             viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            {{-- Folder back --}}
                                            <rect x="4" y="18" width="56" height="38" rx="5"
                                                  :fill="colors[code].folder" fill-opacity="0.25"/>
                                            {{-- Folder tab --}}
                                            <path d="M4 22 Q4 18 8 18 H26 L30 23 H4 Z"
                                                  :fill="colors[code].folder" fill-opacity="0.5"/>
                                            {{-- Folder front --}}
                                            <rect x="4" y="23" width="56" height="33" rx="4"
                                                  :fill="colors[code].folder" fill-opacity="0.85"/>
                                            {{-- Paper/document inside --}}
                                            <rect x="18" y="28" width="28" height="22" rx="2" fill="white" fill-opacity="0.9"/>
                                            <rect x="22" y="33" width="18" height="1.5" rx="1" fill="currentColor" class="text-slate-400" fill-opacity="0.5"/>
                                            <rect x="22" y="37" width="14" height="1.5" rx="1" fill="currentColor" fill-opacity="0.4"/>
                                            <rect x="22" y="41" width="16" height="1.5" rx="1" fill="currentColor" fill-opacity="0.3"/>
                                        </svg>
                                        <span class="absolute -bottom-1 -right-1 text-xl" x-text="icons[code]"></span>
                                    </div>

                                    {{-- College code badge --}}
                                    <span class="text-[20px] font-black tracking-tight"
                                          :class="colors[code].text" x-text="code"></span>
                                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 leading-tight px-2"
                                          x-text="college.label"></span>

                                    {{-- Major count badge --}}
                                    <span class="mt-1 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          :class="colors[code].badge"
                                          x-text="Object.keys(college.majors).length + ' major(s)'"></span>
                                </div>

                                {{-- Open arrow --}}
                                <div class="mt-3 flex justify-center">
                                    <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 group-hover:gap-2 transition-all">
                                        Open folder
                                        <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- ════════════════════════════════════════════════════
                     LIVE CLOCK RIBBON
                ════════════════════════════════════════════════════ --}}
                <div class="mt-7 mb-1 flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-white/70 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 shadow-sm backdrop-blur-md">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow shadow-emerald-300"></span>
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Live System Clock</span>
                        <span class="font-black text-[14px] text-slate-700 dark:text-slate-200 tabular-nums tracking-tight" x-text="clockTime"></span>
                    </div>
                    <div class="flex-1 h-px bg-gradient-to-r from-slate-200 to-transparent dark:from-slate-700"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-300 dark:text-slate-600">Institutional Snapshot</span>
                    <div class="flex-1 h-px bg-gradient-to-l from-slate-200 to-transparent dark:from-slate-700"></div>
                </div>

                {{-- ════════════════════════════════════════════════════
                     INSTITUTIONAL SNAPSHOT ROW — 4 Metric Blocks
                     wire:poll keeps these numbers live without a page reload
                ════════════════════════════════════════════════════ --}}
                <div wire:poll.15s="$refresh" class="bg-white/50 dark:bg-slate-900/40 backdrop-blur-md rounded-2xl border border-slate-200/60 dark:border-slate-700/50 p-4 shadow-sm grid grid-cols-2 lg:grid-cols-4 gap-4 mt-3">

                    {{-- Metric 1: Total Active Schedules --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/70 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center shadow-md shadow-indigo-200/40 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 leading-none mb-0.5">Total Active Schedules</p>
                            <p class="text-[15px] font-black text-slate-800 dark:text-slate-100 leading-tight truncate">{{ $dashboardStats['scheduledCount'] }} / {{ $dashboardStats['totalBlocks'] }} Blocks</p>
                            <p class="text-[10px] font-semibold text-indigo-500 dark:text-indigo-400 mt-0.5">Generated</p>
                        </div>
                    </div>

                    {{-- Metric 2: Conflicts Detected --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/70 dark:bg-slate-800/50 border border-red-100 dark:border-red-900/40 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center shadow-md shadow-red-200/40 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 leading-none mb-0.5">Conflicts Detected</p>
                            <p class="text-[15px] font-black text-red-600 dark:text-red-400 leading-tight truncate">{{ $dashboardStats['conflictsCount'] }} {{ Str::plural('Overlap', $dashboardStats['conflictsCount']) }}</p>
                            <p class="text-[10px] font-semibold text-amber-500 dark:text-amber-400 mt-0.5">{{ $dashboardStats['conflictsCount'] > 0 ? 'Pending Resolution' : 'All Clear' }}</p>
                        </div>
                    </div>

                    {{-- Metric 3: Rooms Occupied --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/70 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-md shadow-emerald-200/40 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 leading-none mb-0.5">Rooms Occupied</p>
                            <p class="text-[15px] font-black text-slate-800 dark:text-slate-100 leading-tight truncate">{{ $dashboardStats['roomsOccupied'] }} / {{ $dashboardStats['totalRooms'] }} Rooms</p>
                            <p class="text-[10px] font-semibold text-emerald-500 dark:text-emerald-400 mt-0.5">In Use This Term</p>
                        </div>
                    </div>

                    {{-- Metric 4: Last Sync --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/70 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-md shadow-violet-200/40 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 leading-none mb-0.5">Last Sync</p>
                            <p class="text-[15px] font-black text-slate-800 dark:text-slate-100 leading-tight tabular-nums">{{ $dashboardStats['lastSync']->format('h:i:s A') }}</p>
                            <p class="text-[10px] font-semibold text-violet-500 dark:text-violet-400 mt-0.5">Live Auto-Refresh (15s)</p>
                        </div>
                    </div>

                </div>

                {{-- ════════════════════════════════════════════════════
                     RECENT SCHEDULING ACTIVITY — full width, live data
                     wire:poll keeps this feed current without a manual refresh
                ════════════════════════════════════════════════════ --}}
                <div class="mt-5">

                    <div wire:poll.15s="$refresh" class="bg-white/50 dark:bg-slate-900/40 backdrop-blur-md rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-sm overflow-hidden">

                        {{-- Card Header --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-[13px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">Recent Scheduling Activity</h3>
                                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">System operation log</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-[9px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Live</span>
                        </div>

                        {{-- Activity List --}}
                        <div class="divide-y divide-slate-50 dark:divide-slate-800/80">

                            @forelse ($recentActivity as $entry)
                                <div class="flex items-start gap-3 px-5 py-3.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <div class="flex-shrink-0 mt-0.5 w-6 h-6 rounded-full bg-{{ $entry->badge_color }}-100 dark:bg-{{ $entry->badge_color }}-900/30 flex items-center justify-center text-[11px]">{{ $entry->icon }}</div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300 leading-snug">
                                            <span class="font-black text-indigo-500 dark:text-indigo-400 tabular-nums">[{{ $entry->time }}]</span>
                                            {{ $entry->title }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $entry->subtitle }}</p>
                                    </div>
                                    <span class="flex-shrink-0 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-{{ $entry->badge_color }}-50 dark:bg-{{ $entry->badge_color }}-900/20 text-{{ $entry->badge_color }}-600 dark:text-{{ $entry->badge_color }}-400 border border-{{ $entry->badge_color }}-200 dark:border-{{ $entry->badge_color }}-800">{{ $entry->badge }}</span>
                                </div>
                            @empty
                                <div class="px-5 py-8 text-center">
                                    <p class="text-[12px] font-semibold text-slate-400 dark:text-slate-500">No scheduling activity recorded yet for this term.</p>
                                </div>
                            @endforelse

                        </div>

                        {{-- Footer --}}
                        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Showing last {{ $recentActivity->count() }} operations</p>
                            <button class="text-[10px] font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors flex items-center gap-1">
                                View All Logs
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>{{-- end activity panel --}}


            </div>

            {{-- ── MAJOR SELECTION (open folder view) — 2-column workspace layout ── --}}
            <template x-for="(college, code) in colleges" :key="code">
                <div x-show="openCollege === code && openMajor === null" x-transition.opacity
                     class="flex flex-col lg:flex-row gap-6 p-6 min-h-[calc(100vh-8rem)] w-full text-slate-700 dark:text-slate-300">

                    {{-- ══════════════════════════════════════════════════════════
                         LEFT COLUMN — Department Context Panel (30%)
                    ══════════════════════════════════════════════════════════ --}}
                    <aside wire:poll.30s="refreshCollegeStats"
                           class="w-full lg:w-[30%] lg:sticky lg:top-6 lg:self-start bg-white/60 dark:bg-slate-900/40 backdrop-blur-md rounded-2xl border border-slate-200/60 dark:border-slate-700/50 p-5 shadow-sm flex flex-col space-y-6">

                        {{-- Back navigation --}}
                        <button @click="openCollege = null"
                                class="self-start flex items-center gap-1.5 text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            All Colleges
                        </button>

                        {{-- Workspace header --}}
                        <div class="flex items-start gap-3">
                            <div class="relative flex-shrink-0">
                                <svg class="w-12 h-12 drop-shadow-md" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="4" y="16" width="56" height="40" rx="5" :fill="colors[code].folder" fill-opacity="0.2"/>
                                    <path d="M4 20 Q4 16 8 16 H26 L30 21 H4 Z" :fill="colors[code].folder" fill-opacity="0.4"/>
                                    <path d="M4 20 H60 Q60 14 56 14 H30 L26 20 Z" :fill="colors[code].folder" fill-opacity="0.7"/>
                                    <rect x="4" y="20" width="56" height="36" rx="4" :fill="colors[code].folder" fill-opacity="0.8"/>
                                </svg>
                                <span class="absolute -bottom-1 -right-1 text-lg" x-text="icons[code]"></span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">College Workspace</span>
                                <h2 class="text-[20px] font-black uppercase tracking-tight leading-tight" :class="colors[code].text" x-text="code + ' WORKSPACE'"></h2>
                                <p class="text-[12px] font-bold text-slate-500 dark:text-slate-400 leading-snug mt-0.5" x-text="college.label"></p>
                            </div>
                        </div>

                        <div class="h-px bg-slate-200/70 dark:bg-slate-700/60"></div>

                        {{-- Administrative metadata --}}
                        <div class="space-y-4">

                            {{-- Dean --}}
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <svg class="w-4 h-4" :class="colors[code].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 leading-none mb-1">Dean</p>
                                    <p class="text-[12px] font-bold text-slate-700 dark:text-slate-200 leading-snug truncate" x-text="college.dean"></p>
                                </div>
                            </div>

                            {{-- Faculty Assigned --}}
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <svg class="w-4 h-4" :class="colors[code].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 leading-none mb-1">Faculty Assigned</p>
                                    <p class="text-[12px] font-bold text-slate-700 dark:text-slate-200 leading-snug" x-text="college.facultyCount + ' Active Instructors'"></p>
                                </div>
                            </div>

                            {{-- Total Blocks Scheduled --}}
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <svg class="w-4 h-4" :class="colors[code].icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 leading-none mb-1">Total Blocks Scheduled</p>
                                    <p class="text-[12px] font-bold text-slate-700 dark:text-slate-200 leading-snug" x-text="college.blocksComplete + ' out of ' + college.blocksTotal + ' Complete'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-slate-200/70 dark:bg-slate-700/60"></div>

                        {{-- Completion progress --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400" x-text="code + ' Completion Progress'"></span>
                                <span class="text-[13px] font-black" :class="colors[code].text" x-text="completionPercent(college) + '%'"></span>
                            </div>
                            <div class="bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r transition-all duration-700 ease-out"
                                     :class="colors[code].bg"
                                     :style="`width: ${completionPercent(college)}%`"></div>
                            </div>
                        </div>
                    </aside>

                    {{-- ══════════════════════════════════════════════════════════
                         RIGHT COLUMN — Major Navigation & Active Load Grid (70%)
                    ══════════════════════════════════════════════════════════ --}}
                    <div class="flex-1 flex flex-col space-y-6">

                        {{-- Top layer: Bond paper cards with major options --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="(major, majorCode) in college.majors" :key="majorCode">
                                <button @click="selectMajor(code, majorCode);
                                                $wire.set('selectedDepartment', majorCode)"
                                        class="group relative rounded-xl overflow-hidden border-2 bg-white dark:bg-slate-800 text-left cursor-pointer transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl active:scale-[0.98] p-0"
                                        :class="colors[code].border + ' ' + colors[code].shadow + ' shadow-md'">

                                    {{-- Bond-paper top margin line --}}
                                    <div class="h-1 w-full bg-gradient-to-r" :class="colors[code].bg"></div>

                                    {{-- Red margin line (bond paper aesthetic) --}}
                                    <div class="flex">
                                        <div class="w-1 self-stretch" :style="`background-color: ${colors[code].folder}; opacity: 0.5;`"></div>
                                        <div class="flex-1 px-5 py-5">
                                            {{-- Hole punch dots --}}
                                            <div class="flex gap-2 mb-4">
                                                <div class="w-2.5 h-2.5 rounded-full bg-slate-200 dark:bg-slate-600 border border-slate-300 dark:border-slate-500"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-slate-200 dark:bg-slate-600 border border-slate-300 dark:border-slate-500"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-slate-200 dark:bg-slate-600 border border-slate-300 dark:border-slate-500"></div>
                                            </div>

                                            {{-- Major code & label --}}
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-gradient-to-br" :class="colors[code].bg">
                                                    <span class="text-white text-[15px] font-black" x-text="majorCode.substring(0,2)"></span>
                                                </div>
                                                <div>
                                                    <span class="text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight" x-text="majorCode"></span>
                                                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 leading-snug mt-0.5" x-text="major.label"></p>
                                                </div>
                                            </div>

                                            {{-- Lined paper effect --}}
                                            <div class="mt-4 space-y-2">
                                                <div class="h-px bg-slate-100 dark:bg-slate-700"></div>
                                                <div class="h-px bg-slate-100 dark:bg-slate-700"></div>
                                                <div class="h-px bg-slate-100 dark:bg-slate-700"></div>
                                            </div>

                                            {{-- Open CTA --}}
                                            <div class="mt-4 flex items-center justify-between">
                                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">View Schedule</span>
                                                <span class="flex items-center justify-center w-7 h-7 rounded-full text-white bg-gradient-to-br group-hover:scale-110 transition-transform"
                                                      :class="colors[code].bg">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- Bottom layer: Active Section Monitor Grid --}}
                        <div class="flex-1 bg-white/50 dark:bg-slate-900/40 backdrop-blur-md rounded-2xl border border-slate-200/60 dark:border-slate-700/50 p-5 shadow-sm flex flex-col">

                            {{-- Card header --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center shadow-sm" :class="colors[code].bg">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6M9 16h6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-[13px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">Active Section Monitor</h3>
                                        <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider" x-text="code + ' Year-Level Blocks'"></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-[9px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Live</span>
                            </div>

                            {{-- Tracking table --}}
                            <div x-show="collegeSections(college).length > 0" class="rounded-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/80 dark:bg-slate-800/60">
                                            <th class="px-4 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Section</th>
                                            <th class="px-4 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Load Units</th>
                                            <th class="px-4 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                                        <template x-for="section in collegeSections(college)" :key="section.code">
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                                <td class="px-4 py-3">
                                                    <span class="text-[13px] font-black text-slate-800 dark:text-slate-100" x-text="section.code"></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[12px] font-bold text-slate-600 dark:text-slate-300 tabular-nums" x-text="section.units + ' / ' + section.maxUnits + ' Units Loaded'"></span>
                                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border"
                                                              :class="section.units >= section.maxUnits
                                                                        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
                                                                        : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800'"
                                                              x-text="section.units >= section.maxUnits ? 'Full' : 'Partial'"></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500" x-text="'Updated at ' + section.updated"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Empty state --}}
                            <template x-if="collegeSections(college).length === 0">
                                <div class="flex-1 flex items-center justify-center py-10">
                                    <p class="text-[12px] font-semibold text-slate-400 dark:text-slate-500">No active sections recorded yet for this college.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         SCHEDULE CONTENT (shown only when a major is selected)
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="isScheduleVisible()" x-transition.opacity class="px-5 pb-6">
        {{-- College color accent bar at top --}}
        <template x-for="(college, code) in colleges" :key="code">
            <div x-show="openCollege === code"
                 class="max-w-[1500px] mx-auto mb-3 h-1 rounded-full bg-gradient-to-r"
                 :class="colors[code].bg"></div>
        </template>
        <div class="max-w-[1500px] mx-auto space-y-4">

        {{-- ── Flash Message ─────────────────────────────────────────────── --}}
        @if($flashMessage)
            <div @class([
                    'flex items-start gap-3 px-5 py-4 rounded-xl text-sm font-semibold shadow-sm border',
                    'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300' => $flashType === 'success',
                    'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300'             => $flashType === 'error',
                    'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300' => $flashType === 'warning',
                    'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300'       => $flashType === 'info',
                ])
                 x-data
                 x-init="setTimeout(() => $el.remove(), 6000)">
                <span class="text-base mt-0.5 flex-shrink-0">
                    @if($flashType === 'success') ✅ @elseif($flashType === 'error') ⛔ @elseif($flashType === 'info') ℹ️ @else ⚠️ @endif
                </span>
                <span class="text-[14px]">{{ $flashMessage }}</span>
            </div>
        @endif

        {{-- ── Finalization Error List ────────────────────────────────────── --}}
        @if(!empty($finalizationErrors))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700
                        rounded-xl px-5 py-4 space-y-2">
                <p class="text-[14px] font-black text-red-700 dark:text-red-400 uppercase tracking-wide mb-2.5 flex items-center gap-2">
                    <span>⛔</span>
                    <span>Finalization Blocked — Resolve these issues:</span>
                </p>
                @foreach($finalizationErrors as $err)
                    <div class="flex items-start gap-2 text-[13px] text-red-600 dark:text-red-400">
                        <span class="flex-shrink-0 mt-0.5 font-bold">→</span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── Edit Mode Banner (UPDATED) ────────────────────────────────── --}}
        @if($workspaceEditMode)
            @php $liveConflictCount = count($workspaceRealTimeConflicts ?? []); @endphp
            <div class="sticky top-0 z-40 flex items-center justify-between gap-4 rounded-xl border border-blue-300 bg-blue-100/95 px-5 py-3 text-blue-900 shadow-lg shadow-blue-900/10 backdrop-blur-sm dark:border-blue-700 dark:bg-blue-950/85 dark:text-blue-200">
                <div class="flex items-center gap-3">
                    <span class="rounded-lg bg-blue-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-white">Edit Mode Active</span>
                    <span class="text-[13px] font-black">Conflicts are detected in real time while you edit.</span>
                    @if($liveConflictCount > 0)
                        <span class="rounded-lg bg-red-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-white animate-pulse">
                            ⚠ {{ $liveConflictCount }} conflict{{ $liveConflictCount === 1 ? '' : 's' }} detected
                        </span>
                    @else
                        <span class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-white">
                            ✓ No conflicts
                        </span>
                    @endif
                </div>
                @if($workspaceValidationActive)
                    <span class="text-[11px] font-black uppercase tracking-widest text-red-700 dark:text-red-300">Fix highlighted rows before saving</span>
                @endif
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             MAIN CONTAINER
             ════════════════════════════════════════════════════════════════ --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg overflow-hidden transition-colors duration-500"
             id="study-load-container">

            {{-- College color top bar --}}
            <template x-for="(college, code) in colleges" :key="code">
                <div x-show="openCollege === code"
                     class="h-1.5 w-full bg-gradient-to-r"
                     :class="colors[code].bg"></div>
            </template>

            {{-- ──────────────────────────────────────────────────────────────
                 HEADER (screen only — lives inside #study-load-container,
                 which is hidden in print; the printed copy uses its own
                 letterhead in #official-print-block instead)
                 ────────────────────────────────────────────────────────────── --}}
            <div class="border-b border-blue-600/20 px-8 py-6
                        bg-gradient-to-r from-blue-50/70 to-indigo-50/40
                        dark:from-blue-900/10 dark:to-indigo-900/5 print-header">
                <div class="flex flex-wrap items-center gap-5">

                    {{-- Icon (hidden in print via screen-only) --}}
                    <div class="flex-shrink-0 rounded-xl
                                bg-gradient-to-br from-blue-500 to-indigo-600
                                flex items-center justify-center shadow-md shadow-blue-200/50
                                dark:shadow-none screen-only w-12 h-12">
                        <span class="text-2xl leading-none">📚</span>
                    </div>

                    {{-- Title block --}}
                    <div>
                        <h1 class="text-[28px] font-black uppercase tracking-tighter
                                   text-slate-800 dark:text-slate-100 leading-none">
                            Blocked Schedule
                        </h1>
                        <h2 class="text-[17px] font-bold text-blue-600 dark:text-blue-400
                                   uppercase tracking-tight mt-1 flex items-center gap-2.5 flex-wrap">
                            {{ $departmentName }}

                            @if($isReadOnlyView)
                                <span class="inline-flex items-center gap-1.5 bg-amber-100 dark:bg-amber-900/30
                                             border border-amber-300 dark:border-amber-700
                                             text-amber-700 dark:text-amber-400
                                             px-2.5 py-1 rounded-full text-[11px] font-black
                                             uppercase tracking-wider normal-case">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Read-only
                                </span>
                            @endif
                        </h2>

                        @if($isReadOnlyView)
                            <p class="text-[12px] font-semibold text-amber-600 dark:text-amber-400 mt-1 print:hidden">
                                You're viewing {{ $readOnlyCollegeCode ?? 'another college' }}'s schedule.
                                Only {{ $readOnlyCollegeCode }} officials can assign faculty, edit, or finalize it.
                            </p>
                        @endif
                    </div>

                    {{-- Period badges --}}
                    <div class="flex flex-wrap items-center gap-2.5 ml-2">
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            S.Y. {{ $schoolYear }}
                        </span>
                        <span class="text-slate-300 dark:text-slate-600 text-lg">•</span>
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            {{ $semesterName }}
                        </span>
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            Year {{ $selectedYear }} · Section {{ $selectedSection }}
                        </span>
                    </div>

                    {{-- ════════════════════════════════════════════════════
                         RIGHT SIDE: Registrar Access Control (Admin only)
                         + Finalized badge — both pinned to the far right.
                         ════════════════════════════════════════════════════ --}}
                    <div class="ml-auto flex items-center gap-2.5 screen-only">

                        {{-- ADMIN-ONLY: Global Registrar Permission Control --}}
                        @if($canManagePermission)
                            <button
                                wire:click="openPermissionPanel"
                                title="Manage global Registrar finalization access"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg
                                       text-[11px] font-bold uppercase tracking-wider
                                       transition-all active:scale-95 border
                                       {{ $registrarWithPermission
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'
                                            : 'bg-white/70 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                            >
                                {{-- Shield icon (small) --}}
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>

                                <span class="leading-none">Registrar Access</span>

                                {{-- Compact toggle pill --}}
                                <span class="flex-shrink-0 w-8 h-4 rounded-full relative transition-colors
                                             {{ $registrarWithPermission ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                                    <span class="absolute top-0.5 w-3 h-3 rounded-full bg-white shadow-sm transition-all
                                                 {{ $registrarWithPermission ? 'left-4' : 'left-0.5' }}">
                                    </span>
                                </span>

                                {{-- Status dot label --}}
                                <span class="text-[10px] normal-case tracking-normal
                                             {{ $registrarWithPermission ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $registrarWithPermission ? 'ON' : 'OFF' }}
                                </span>
                            </button>
                        @endif

                        {{-- Finalized badge (shown when ALL rows are locked) --}}
                        @if($allFinalized)
                            <span class="flex items-center gap-1.5
                                         bg-green-100 dark:bg-green-900/30
                                         border border-green-300 dark:border-green-700
                                         text-green-700 dark:text-green-400
                                         px-3 py-1.5 rounded-lg text-[11px] font-black
                                         uppercase tracking-wider">
                                🔒 Finalized
                            </span>
                        @endif

                    </div>{{-- /right side --}}
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────────
                 STATS BAR  (screen only)
                 ────────────────────────────────────────────────────────────── --}}
            @if($totalRows > 0)
                <div class="print:hidden border-b border-slate-100 dark:border-slate-800
                            bg-slate-50/60 dark:bg-slate-800/30 px-8 py-3
                            flex flex-wrap items-center gap-x-8 gap-y-2 text-[13px]">

                    {{-- Total count --}}
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 flex-shrink-0"></span>
                        <span class="font-bold text-slate-600 dark:text-slate-400">
                            {{ $totalRows }} subject(s)
                        </span>
                    </div>

                    {{-- Assigned count --}}
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <span class="font-bold text-emerald-700 dark:text-emerald-400">
                            {{ $totalRows - $unassignedCount }} assigned
                        </span>
                    </div>

                    {{-- Unassigned --}}
                    @if($unassignedCount > 0)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold text-amber-600 dark:text-amber-400">
                                {{ $unassignedCount }} unassigned
                            </span>
                        </div>
                    @endif

                    {{-- Conflicts --}}
                    @if($conflictCount > 0)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                {{ $conflictCount }} conflict(s) detected
                            </span>
                        </div>
                    @endif

                    {{-- Ready / blocked indicator --}}
                    @if($unassignedCount === 0 && $conflictCount === 0 && !$allFinalized)
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20
                                         border border-emerald-200 dark:border-emerald-700
                                         text-emerald-700 dark:text-emerald-400
                                         px-3.5 py-1.5 rounded-lg text-[13px] font-bold">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Ready to Finalize
                            </span>
                        </div>
                    @elseif($unassignedCount > 0 || $conflictCount > 0)
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20
                                         border border-red-200 dark:border-red-700
                                         text-red-700 dark:text-red-400
                                         px-3.5 py-1.5 rounded-lg text-[13px] font-bold">
                                ⚠ Cannot finalize — resolve issues above
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ──────────────────────────────────────────────────────────────
                 FILTER + ACTION BAR  (screen only)
                 ────────────────────────────────────────────────────────────── --}}
            @if($canReviewRevision && $pendingRevisionRequests->isNotEmpty())
                <div class="print:hidden border-b border-amber-500/20 bg-amber-50/80 px-8 py-4 dark:bg-amber-950/20">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-[12px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">
                            ⏳ Pending Faculty Revision Requests
                        </p>
                        <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[10px] font-black uppercase text-amber-700 dark:text-amber-200">
                            {{ $pendingRevisionRequests->count() }} pending
                        </span>
                    </div>
                    <div class="grid gap-3 lg:grid-cols-2">
                        @foreach($pendingRevisionRequests as $request)
                            @php
                                $requesterRole = match($request->requester?->role) {
                                    'dean'           => 'Dean',
                                    'oic'            => 'OIC',
                                    'associate_dean' => 'Associate Dean',
                                    'admin'          => 'Admin',
                                    'registrar'      => 'Registrar',
                                    default          => ucfirst(str_replace('_', ' ', $request->requester?->role ?? '')),
                                };
                            @endphp
                            <div class="rounded-xl border border-amber-200 bg-white/90 p-4 text-[12px] shadow-sm dark:border-amber-900/60 dark:bg-slate-950/60">

                                {{-- Status badge + Subject ──────────────────────────────────────── --}}
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="min-w-0">
                                        <span class="inline-block rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wide bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 mb-1">
                                            ⏳ Pending
                                        </span>
                                        <p class="font-black uppercase text-slate-800 dark:text-slate-100 text-[13px]">
                                            {{ $request->subject?->subject_code }}
                                            @if($request->subject?->description)
                                                — {{ $request->subject->description }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0 mt-0.5">
                                        {{ $request->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                {{-- Requester ─────────────────────────────────────────────────── --}}
                                <div class="mb-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 px-3 py-2">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Submitted By</p>
                                    <p class="font-bold text-slate-700 dark:text-slate-200">
                                        {{ $request->requester?->name ?? 'Unknown' }}
                                        <span class="ml-1 rounded bg-blue-100 dark:bg-blue-900/40 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                            {{ $requesterRole }}
                                        </span>
                                    </p>
                                </div>

                                {{-- Faculty Change ─────────────────────────────────────────────── --}}
                                <div class="mb-2 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 w-24 shrink-0">Current</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-200 truncate">
                                            {{ $request->currentFaculty?->full_name ?? 'Unassigned' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 w-24 shrink-0">Requested</span>
                                        <span class="font-bold text-emerald-700 dark:text-emerald-400 truncate">
                                            {{ $request->requestedFaculty?->full_name ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Reason ───────────────────────────────────────────────────── --}}
                                @if($request->reason)
                                    <p class="mb-3 text-[11px] italic text-slate-500 dark:text-slate-400 line-clamp-2 border-l-2 border-amber-300 dark:border-amber-700 pl-2">
                                        "{{ $request->reason }}"
                                    </p>
                                @endif

                                {{-- Approve / Reject actions ─────────────────────────────────── --}}
                                <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <button
                                        type="button"
                                        wire:click="approveRevisionRequest({{ $request->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="approveRevisionRequest({{ $request->id }})"
                                        wire:confirm="Approve this faculty revision for {{ $request->subject?->subject_code }}? The schedule will be updated immediately."
                                        class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-emerald-500 transition-all active:scale-95 disabled:opacity-60">
                                        ✓ Approve
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="rejectRevisionRequest({{ $request->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="rejectRevisionRequest({{ $request->id }})"
                                        wire:confirm="Reject this faculty revision request? The requester will be notified."
                                        class="flex-1 rounded-lg bg-red-600 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-red-500 transition-all active:scale-95 disabled:opacity-60">
                                        ✕ Reject
                                    </button>
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="print:hidden border-b border-slate-200 dark:border-slate-800
                        bg-slate-50/90 dark:bg-slate-800/40 px-8 py-4">
                <div class="flex flex-wrap gap-5 items-end">

                    {{-- ── Left: Year / Section (Department handled by folder nav) ──── --}}
                    <div class="flex flex-col gap-1.5" style="display:none !important;">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Department / Major</label>
                        <select wire:model.live="selectedDepartment"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[240px]">
                            @foreach($availableDepts as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Year Level</label>
                        <select wire:model.live="selectedYear"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[140px]">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Section</label>
                        <select wire:model.live="selectedSection"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[140px]">
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>

                    {{-- ── Right: Action Buttons (UPDATED) ──────────────────────────── --}}
                    <div class="ml-auto flex flex-wrap items-center gap-3">

                        {{-- ── Edit Workspace Buttons ──────────────────────────
                             NEW: Cancel Editing + Done Editing (conditional)
                             ───────────────────────────────────────────────── --}}
                        @if($canEditWorkspace && !$allFinalized && $totalRows > 0)
                            @if($workspaceEditMode)
                                {{-- In Edit Mode: Show Cancel & Done buttons --}}
                                <button
                                    type="button"
                                    wire:click="cancelWorkspaceEdit"
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2.5 rounded-xl text-[13px] font-black uppercase tracking-wider text-white shadow-md transition-all active:scale-95 flex items-center gap-2.5
                                           bg-gradient-to-r from-slate-500 to-slate-600 hover:from-slate-400 hover:to-slate-500 shadow-slate-900/10">
                                    <span>Cancel Editing</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="finishWorkspaceEdit"
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2.5 rounded-xl text-[13px] font-black uppercase tracking-wider text-white shadow-md transition-all active:scale-95 flex items-center gap-2.5
                                           bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 shadow-amber-900/10">
                                    <span>Done Editing</span>
                                </button>
                            @else
                                {{-- Not in Edit Mode: Show Edit Workspace button --}}
                                <button
                                    type="button"
                                    wire:click="startWorkspaceEdit"
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2.5 rounded-xl text-[13px] font-black uppercase tracking-wider text-white shadow-md transition-all active:scale-95 flex items-center gap-2.5
                                           bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-700 hover:to-slate-600 shadow-slate-900/10">
                                    <span>Edit Workspace</span>
                                </button>
                            @endif
                        @endif

                        @if($canFinalize && !$allFinalized && $totalRows > 0)
                            <button
                                wire:click="finalizeSchedule"
                                wire:loading.attr="disabled"
                                wire:confirm="Finalize all {{ $totalRows }} schedule(s) for {{ $departmentName }} · Year {{ $selectedYear }} · Section {{ $selectedSection }}? Room and time slots will be locked, but faculty can still be safely reassigned."
                                @class([
                                    'relative px-5 py-2.5 rounded-xl text-[13px] font-black uppercase tracking-wider',
                                    'transition-all active:scale-95 flex items-center gap-2.5',
                                    'text-white shadow-md',
                                    'bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 shadow-emerald-200 dark:shadow-none cursor-pointer'
                                        => ($unassignedCount === 0 && $conflictCount === 0),
                                    'bg-gradient-to-r from-slate-400 to-slate-500 cursor-not-allowed opacity-50 shadow-none'
                                        => ($unassignedCount > 0 || $conflictCount > 0),
                                ])
                                @if($unassignedCount > 0 || $conflictCount > 0) disabled title="Resolve all issues before finalizing" @endif
                            >
                                <span class="text-base leading-none">🔒</span>
                                <span>Finalize Schedule</span>
                                @if($unassignedCount > 0 || $conflictCount > 0)
                                    <span class="text-[11px] opacity-70 font-medium normal-case tracking-normal">
                                        (resolve issues first)
                                    </span>
                                @endif
                            </button>
                        @endif

                        {{-- ── Print Button ──────────────────────────────── --}}
                        <button onclick="window.print()"
                                class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600
                                       hover:from-blue-700 hover:to-indigo-700
                                       text-white rounded-xl text-[13px] font-black uppercase tracking-wider
                                       shadow-md shadow-blue-200 dark:shadow-none
                                       transition-all active:scale-95 flex items-center gap-2.5">
                            <span class="text-base leading-none">🖨️</span>
                            <span>Print Official Load</span>
                        </button>

                    </div>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────────
                 SCHEDULE TABLE
                 ────────────────────────────────────────────────────────────── --}}
            <div class="overflow-x-auto" id="schedule-table-wrapper">

                @forelse($scheduleRows as $sched)
                    @if($loop->first)
                        <table class="w-full border-collapse" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 13%">  {{-- Time --}}
                                <col style="width: 9%">   {{-- EDP Code --}}
                                <col style="width: 9%">   {{-- Subject Code --}}
                                <col style="width: 21%">  {{-- Description --}}
                                <col style="width: 5%">   {{-- Units --}}
                                <col style="width: 8%">   {{-- Day --}}
                                <col style="width: 10%">  {{-- Room --}}
                                <col style="width: 17%">  {{-- Faculty --}}
                                <col style="width: 8%">   {{-- Status --}}
                            </colgroup>

                            <thead>
                                <tr class="bg-slate-900 dark:bg-slate-800 text-white
                                           text-[13px] uppercase font-black tracking-wider">
                                    <th class="px-4 py-3.5 text-center border-r border-slate-700">Time</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">EDP Code</th>
                                    <th class="px-3 py-3.5 text-left  border-r border-slate-700">Subject Code</th>
                                    <th class="px-3 py-3.5 text-left  border-r border-slate-700">Description</th>
                                    <th class="px-2 py-3.5 text-center border-r border-slate-700">Units</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Day(s)</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Room</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Faculty</th>
                                    <th class="px-3 py-3.5 text-center">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @endif

                    {{-- ── Data Row ─────────────────────────────────────────── --}}
                    <tr @class([
                            'transition-colors group',
                            'bg-red-50/80 dark:bg-red-950/20 hover:bg-red-50 dark:hover:bg-red-950/30 border-l-4 border-red-600'
                                => $sched->status === 'not_scheduled',
                            'bg-red-50/70 dark:bg-red-900/10 hover:bg-red-50 dark:hover:bg-red-900/20 border-l-4 border-red-500'
                                => $sched->has_conflict && $sched->status !== 'not_scheduled',
                            'bg-amber-50/50 dark:bg-amber-900/5 hover:bg-amber-50 dark:hover:bg-amber-900/10 border-l-4 border-amber-400'
                                => (! $sched->has_conflict && is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED && $sched->status !== 'not_scheduled'),
                            'bg-emerald-50/20 dark:bg-emerald-900/5 hover:bg-emerald-50/40 border-l-4 border-emerald-300'
                                => $sched->status === \App\Models\Schedule::STATUS_FINALIZED,
                            'hover:bg-blue-50/30 dark:hover:bg-blue-900/10'
                                => (! $sched->has_conflict && ! is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED && $sched->status !== 'not_scheduled'),
                            'bg-slate-50/50 dark:bg-slate-800/20'
                                => ($loop->even && ! $sched->has_conflict && ! is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED && $sched->status !== 'not_scheduled'),
                        ])>

                        {{-- ── Time ─────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center whitespace-nowrap">
                            @if($sched->status === 'not_scheduled')
                                <span class="inline-block rounded-md border border-red-300 bg-red-100 px-2.5 py-1.5 text-[12px] font-black uppercase tracking-tight text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                                    NOT SCHEDULED
                                </span>
                            @elseif($workspaceEditMode && isset($workspaceEdits[$sched->edit_key]))
                                @php
                                    $rtc       = $workspaceRealTimeConflicts[$sched->edit_key] ?? null;
                                    $hasRtc    = ! is_null($rtc);
                                    $timeRing  = $hasRtc ? 'border-red-500 ring-1 ring-red-400' : 'border-amber-300 dark:border-amber-700';
                                    $timeSlots = $workspaceEditOptions[$sched->edit_key]['timeSlots'] ?? [];
                                @endphp
                                <div class="flex flex-col gap-1">
                                    {{-- Start Time — master-grid slot select --}}
                                    <select wire:model.live="workspaceEdits.{{ $sched->edit_key }}.start_time"
                                            class="w-full rounded-md border {{ $timeRing }} bg-white px-2 py-1 text-[11px] font-black text-slate-800 dark:bg-slate-950 dark:text-slate-100 transition-colors">
                                        @foreach($timeSlots as $slot)
                                            <option value="{{ $slot['value'] }}">{{ $slot['label'] }}</option>
                                        @endforeach
                                    </select>
                                    {{-- End Time — master-grid slot select --}}
                                    <select wire:model.live="workspaceEdits.{{ $sched->edit_key }}.end_time"
                                            class="w-full rounded-md border {{ $timeRing }} bg-white px-2 py-1 text-[11px] font-black text-slate-800 dark:bg-slate-950 dark:text-slate-100 transition-colors">
                                        @foreach($timeSlots as $slot)
                                            <option value="{{ $slot['value'] }}">{{ $slot['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @if($hasRtc)
                                        <div class="mt-0.5 flex flex-wrap gap-0.5">
                                            @foreach($rtc['types'] ?? [] as $ctype)
                                                <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-white
                                                    {{ str_contains($ctype, 'ROOM') ? 'bg-red-500' : (str_contains($ctype, 'FACULTY') ? 'bg-orange-500' : 'bg-yellow-500') }}">
                                                    {{ $ctype }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                            <span class="bg-blue-50 dark:bg-blue-900/30
                                         text-blue-700 dark:text-blue-300
                                         border border-blue-100 dark:border-blue-800/40
                                         px-2.5 py-1.5 rounded-md
                                         text-[13px] font-black tracking-tight inline-block">
                                {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}
                                <span class="opacity-40">–</span>
                                {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                            </span>
                            @endif
                        </td>

                        {{-- ── EDP Code ─────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center
                                   font-mono text-[12px] font-semibold
                                   text-slate-400 dark:text-slate-500">
                            {{ $sched->subject?->edp_code ?? '—' }}
                        </td>

                        {{-- ── Subject Code ─────────────────────────────── --}}
                        <td class="px-3 py-3
                                   text-[15px] font-black uppercase tracking-tight
                                   text-slate-800 dark:text-slate-100">
                            {{ $sched->subject?->subject_code ?? '—' }}
                        </td>

                        {{-- ── Description ──────────────────────────────── --}}
                        <td class="px-3 py-3
                                   text-[13px] text-slate-600 dark:text-slate-400 leading-snug">
                            {{ $sched->subject?->description ?? '—' }}
                        </td>

                        {{-- ── Units ────────────────────────────────────── --}}
                        <td class="px-2 py-3 text-center">
                            <span class="bg-slate-100 dark:bg-slate-800
                                         text-slate-700 dark:text-slate-300
                                         border border-slate-200 dark:border-slate-700
                                         px-2 py-0.5 rounded text-[12px] font-black inline-block">
                                {{ $sched->subject?->units ?? 0 }}u
                            </span>
                        </td>

                        {{-- ── Day ──────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @if($workspaceEditMode && isset($workspaceEdits[$sched->edit_key]))
                                @php
                                    $rtcDay   = $workspaceRealTimeConflicts[$sched->edit_key] ?? null;
                                    $activeDayList = $workspaceEditOptions[$sched->edit_key]['days'] ?? [];
                                    // Parse currently selected days for checkbox state
                                    $selectedDays  = array_filter(array_map(
                                        'trim',
                                        explode(',', $workspaceEdits[$sched->edit_key]['day_string'] ?? '')
                                    ));
                                @endphp
                                {{-- Day checkboxes — only active scheduling days shown --}}
                                <div class="flex flex-col gap-0.5 text-left" x-data>
                                    @foreach($activeDayList as $activeDay)
                                        @php
                                            $dayAbbr  = strtoupper(substr($activeDay, 0, 3));
                                            $dayChecked = in_array($activeDay, $selectedDays, true);
                                        @endphp
                                        <label class="flex items-center gap-1.5 cursor-pointer select-none
                                                       {{ $dayChecked ? 'text-blue-700 dark:text-blue-300 font-black' : 'text-slate-500 dark:text-slate-400 font-semibold' }}
                                                       text-[11px] uppercase tracking-tight">
                                            <input type="checkbox"
                                                   value="{{ $activeDay }}"
                                                   {{ $dayChecked ? 'checked' : '' }}
                                                   class="accent-blue-600 w-3 h-3 flex-shrink-0"
                                                   x-on:change="
                                                       let key  = '{{ $sched->edit_key }}';
                                                       let cur  = ($wire.workspaceEdits[key] || {}).day_string || '';
                                                       let days = cur.split(',').map(d => d.trim()).filter(Boolean);
                                                       if ($event.target.checked) {
                                                           if (! days.includes('{{ $activeDay }}')) days.push('{{ $activeDay }}');
                                                       } else {
                                                           days = days.filter(d => d !== '{{ $activeDay }}');
                                                       }
                                                       $wire.set('workspaceEdits.' + key + '.day_string', days.join(', '));
                                                   ">
                                            {{ $dayAbbr }}
                                        </label>
                                    @endforeach
                                    @if($rtcDay)
                                        <span class="mt-0.5 inline-block rounded px-1 py-0.5 text-[9px] font-black uppercase text-white bg-yellow-500">CONFLICT</span>
                                    @endif
                                </div>
                            @else
                            <span class="text-[12px] font-black uppercase tracking-tight
                                         text-slate-600 dark:text-slate-400">
                                {{ $sched->day_display }}
                            </span>
                            @endif
                        </td>

                        {{-- ── Room ─────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @if($sched->status === 'not_scheduled')
                                <span class="inline-block rounded-md border border-red-300 bg-red-100 px-2.5 py-1 text-[12px] font-black uppercase tracking-tight text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                                    UNASSIGNED
                                </span>
                            @elseif($workspaceEditMode && isset($workspaceEdits[$sched->edit_key]))
                                @php
                                    $rtcRoom         = $workspaceRealTimeConflicts[$sched->edit_key] ?? null;
                                    $roomHasConflict = $rtcRoom && collect($rtcRoom['types'] ?? [])->contains(fn($t) => str_contains($t, 'ROOM'));
                                    $roomRing        = $roomHasConflict ? 'border-red-500 ring-1 ring-red-400' : 'border-amber-300 dark:border-amber-700';
                                    $filteredRooms   = $workspaceEditOptions[$sched->edit_key]['rooms'] ?? [];
                                @endphp
                                <div class="flex flex-col gap-0.5">
                                    <select wire:model.live="workspaceEdits.{{ $sched->edit_key }}.room_id"
                                            class="w-full rounded-md border {{ $roomRing }} bg-white px-2 py-1.5 text-[11px] font-black text-slate-800 dark:bg-slate-950 dark:text-slate-100 transition-colors">
                                        @if(empty($filteredRooms))
                                            <option value="">No compatible rooms</option>
                                        @else
                                            @php $hasOccupied = collect($filteredRooms)->contains('occupied', true); @endphp
                                            @if($hasOccupied)<optgroup label="── AVAILABLE ──">@endif
                                            @foreach($filteredRooms as $fr)
                                                @if($hasOccupied && $fr['occupied'])
                                                    </optgroup><optgroup label="── OCCUPIED ──">
                                                    @php $hasOccupied = false; @endphp
                                                @endif
                                                <option value="{{ $fr['id'] }}"
                                                        {{ (string)($workspaceEdits[$sched->edit_key]['room_id'] ?? '') === (string)$fr['id'] ? 'selected' : '' }}>
                                                    {{ $fr['label'] }}{{ $fr['occupied'] ? ' (Occupied)' : '' }}
                                                </option>
                                            @endforeach
                                            @if(! $hasOccupied)</optgroup>@endif
                                        @endif
                                    </select>
                                    @if($roomHasConflict)
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-white bg-red-500">
                                            ROOM CONFLICT
                                        </span>
                                    @endif
                                    @if(empty($filteredRooms))
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-white bg-slate-500">
                                            NO COMPATIBLE ROOM
                                        </span>
                                    @endif
                                </div>
                            @else
                            <span class="bg-blue-50 dark:bg-blue-900/30
                                         text-blue-600 dark:text-blue-400
                                         border border-blue-100 dark:border-blue-800/40
                                         px-2.5 py-1 rounded text-[12px] font-black uppercase tracking-tight inline-block">
                                {{ $sched->room?->room_name ?? 'No Room' }}
                            </span>
                            @endif
                        </td>

                        {{-- ── Faculty Cell ─────────────────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @if($workspaceEditMode && isset($workspaceEdits[$sched->edit_key]))
                                @php
                                    $rtcFac          = $workspaceRealTimeConflicts[$sched->edit_key] ?? null;
                                    $facHasConflict  = $rtcFac && collect($rtcFac['types'] ?? [])->contains(fn($t) => str_contains($t, 'FACULTY'));
                                    $facRing         = $facHasConflict ? 'border-red-500 ring-1 ring-red-400' : 'border-amber-300 dark:border-amber-700';
                                    $filteredFaculty = $workspaceEditOptions[$sched->edit_key]['faculty'] ?? [];
                                @endphp
                                <div class="flex flex-col gap-0.5">
                                    <select wire:model.live="workspaceEdits.{{ $sched->edit_key }}.faculty_id"
                                            class="w-full rounded-md border {{ $facRing }} bg-white px-2 py-1.5 text-[11px] font-black text-slate-800 dark:bg-slate-950 dark:text-slate-100 transition-colors">
                                        <option value="">UNASSIGNED</option>
                                        @if(! empty($filteredFaculty))
                                            @php $hasOccupied = collect($filteredFaculty)->contains('occupied', true); @endphp
                                            @if($hasOccupied)<optgroup label="── AVAILABLE ──">@endif
                                            @foreach($filteredFaculty as $ff)
                                                @if($hasOccupied && $ff['occupied'])
                                                    </optgroup><optgroup label="── OCCUPIED ──">
                                                    @php $hasOccupied = false; @endphp
                                                @endif
                                                <option value="{{ $ff['id'] }}"
                                                        {{ (string)($workspaceEdits[$sched->edit_key]['faculty_id'] ?? '') === (string)$ff['id'] ? 'selected' : '' }}>
                                                    {{ $ff['label'] }}{{ $ff['occupied'] ? ' (Occupied)' : '' }}
                                                </option>
                                            @endforeach
                                            @if(! $hasOccupied)</optgroup>@endif
                                        @endif
                                    </select>
                                    @if($facHasConflict)
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-white bg-orange-500">
                                            FACULTY CONFLICT
                                        </span>
                                    @endif
                                    @if(empty($filteredFaculty))
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-white bg-slate-500">
                                            NO ELIGIBLE FACULTY
                                        </span>
                                    @endif
                                </div>
                            @elseif($sched->status === \App\Models\Schedule::STATUS_FINALIZED && $canRequestRevision)
                                {{-- Always show faculty name first, then the revision action below ─────── --}}
                                <div class="flex flex-col items-center gap-1.5">

                                    {{-- Faculty name (always visible) ────────────────────────────────── --}}
                                    @if($sched->faculty)
                                        <div class="flex items-center gap-1.5 w-full">
                                            <div class="w-5 h-5 rounded-full bg-emerald-500 flex-shrink-0 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[12px] font-semibold text-slate-700 dark:text-slate-200 truncate leading-snug text-left">
                                                {{ $sched->faculty->full_name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-[11px] italic text-slate-400 dark:text-slate-500">Unassigned</span>
                                    @endif

                                    {{-- Revision action ─────────────────────────────────────────────── --}}
                                    @if(($sched->revision_request?->status ?? null) === \App\Models\ScheduleRevisionRequest::STATUS_PENDING)
                                        <span class="inline-block w-full text-center rounded-lg border border-amber-300 bg-amber-100 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                                            ⏳ Pending Revision
                                        </span>
                                    @elseif(($sched->revision_request?->status ?? null) === \App\Models\ScheduleRevisionRequest::STATUS_APPROVED)
                                        <span class="inline-block w-full text-center rounded-lg border border-emerald-300 bg-emerald-100 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            ✓ Revision Approved
                                        </span>
                                    @elseif(($sched->revision_request?->status ?? null) === \App\Models\ScheduleRevisionRequest::STATUS_REJECTED)
                                        <button type="button"
                                                wire:click="openRevisionModal({{ json_encode($sched->ids) }}, {{ $sched->subject?->id ?? 0 }})"
                                                class="w-full rounded-lg border border-red-300 bg-red-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-red-700 transition hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-300">
                                            ✕ Rejected · Re-request
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="openRevisionModal({{ json_encode($sched->ids) }}, {{ $sched->subject?->id ?? 0 }})"
                                                class="w-full rounded-lg border border-amber-400 bg-amber-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-700 transition hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                                            Request Revision
                                        </button>
                                    @endif

                                </div>
                            @elseif($canAssign && $sched->status !== 'not_scheduled')

                                <button
                                    wire:click="openFacultyModal(
                                        '{{ $sched->pairing_key }}',
                                        {{ json_encode($sched->ids) }},
                                        {{ $sched->subject?->id ?? 0 }}
                                    )"
                                    @class([
                                        'w-full text-left px-2.5 py-2 rounded-xl transition-all',
                                        'border-2 border-dashed',
                                        'group/btn hover:scale-[1.01] active:scale-95',
                                        'border-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30'
                                            => $sched->has_conflict,
                                        'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                            => (! $sched->has_conflict && ! is_null($sched->faculty)),
                                        'border-amber-300 dark:border-amber-700 bg-amber-50/60 dark:bg-amber-900/10 hover:border-amber-400 hover:bg-amber-50'
                                            => (! $sched->has_conflict && is_null($sched->faculty)),
                                    ])"
                                    title="{{ $sched->has_conflict
                                        ? $sched->conflict_reason
                                        : (is_null($sched->faculty) ? 'Click to assign faculty' : 'Click to change faculty') }}"
                                >
                                    @if($sched->has_conflict)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-red-500 text-sm flex-shrink-0">⚠</span>
                                            <span class="text-[12px] font-bold text-red-700 dark:text-red-400 truncate">
                                                {{ $sched->faculty?->full_name ?? 'Conflict!' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-red-500 dark:text-red-400 mt-0.5 truncate">
                                            {{ $sched->conflict_reason }}
                                        </div>

                                    @elseif($sched->faculty)
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-emerald-500 flex-shrink-0
                                                         flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-snug">
                                                {{ $sched->faculty->full_name }}
                                            </span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 ml-auto flex-shrink-0
                                                        opacity-0 group-hover/btn:opacity-100 transition-opacity"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </div>

                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full border-2 border-dashed border-amber-400
                                                         flex-shrink-0 flex items-center justify-center">
                                                <span class="text-[10px] text-amber-500 font-bold">+</span>
                                            </div>
                                            <span class="text-[12px] italic font-medium text-amber-600 dark:text-amber-500">
                                                Tap to assign
                                            </span>
                                        </div>
                                    @endif

                                </button>

                            @elseif($sched->faculty)
                                <div class="flex items-center justify-center gap-2 px-2 py-2">
                                    <span class="text-[13px] font-semibold text-slate-600 dark:text-slate-400">
                                        {{ $sched->faculty->full_name }}
                                    </span>
                                    <span class="text-slate-400 dark:text-slate-500 text-xs">🔒</span>
                                </div>

                            @else
                                <span @class([
                                    'text-[12px] italic',
                                    'font-black uppercase text-red-600 dark:text-red-300' => $sched->status === 'not_scheduled',
                                    'text-slate-400 dark:text-slate-500' => $sched->status !== 'not_scheduled',
                                ])>
                                    {{ $sched->status === 'not_scheduled' ? 'UNASSIGNED' : 'Unassigned' }}
                                </span>
                            @endif
                        </td>

                        {{-- ── Status Badge (UPDATED) ──────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @php
                                $statusMap = [
                                    \App\Models\Schedule::STATUS_PARTIAL          => [
                                        'label' => 'Partial',
                                        'cls'   => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700',
                                    ],
                                    \App\Models\Schedule::STATUS_FACULTY_ASSIGNED => [
                                        'label' => 'Assigned',
                                        'cls'   => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
                                    ],
                                    \App\Models\Schedule::STATUS_FINALIZED        => [
                                        'label' => 'Finalized',
                                        'cls'   => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700',
                                    ],
                                    'not_scheduled' => [
                                        'label' => 'Not Scheduled',
                                        'cls'   => 'bg-red-600 text-white border-red-500 dark:bg-red-500 dark:text-white dark:border-red-400',
                                    ],
                                ];
                                $s = $statusMap[$sched->status]
                                    ?? ['label' => $sched->status, 'cls' => 'bg-slate-100 text-slate-500 border-slate-200'];
                            @endphp

                            <span class="inline-block px-2.5 py-1 rounded-lg text-[12px] font-black
                                         uppercase tracking-wide border {{ $s['cls'] }}">
                                {{ $s['label'] }}
                            </span>

                            @if($sched->status === 'not_scheduled')
                                <div class="mt-1 text-[10px] font-black uppercase tracking-widest text-red-600 dark:text-red-300">
                                    Section {{ $sched->subject?->section ?? $selectedSection }}
                                </div>
                            @endif

                            @if($sched->has_conflict)
                                <div class="mt-1 text-[11px] font-bold text-red-600 dark:text-red-400 uppercase tracking-tight">
                                    Conflict
                                </div>
                                {{-- NEW: Display conflict type badges --}}
                                @if($sched->conflict_type)
                                    <div class="mt-1 text-[10px] font-black text-white uppercase tracking-tight inline-block px-2 py-1 rounded
                                                {{ str_contains($sched->conflict_type, 'ROOM') ? 'bg-red-500' : (str_contains($sched->conflict_type, 'FACULTY') ? 'bg-orange-500' : (str_contains($sched->conflict_type, 'TIME') ? 'bg-yellow-500' : 'bg-slate-500')) }}">
                                        {{ $sched->conflict_type }}
                                    </div>
                                @endif
                            @endif

                            @if($sched->revision_request && $canReviewRevision)
                                @php
                                    $revisionClasses = [
                                        \App\Models\ScheduleRevisionRequest::STATUS_PENDING  => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700',
                                        \App\Models\ScheduleRevisionRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700',
                                        \App\Models\ScheduleRevisionRequest::STATUS_REJECTED => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-200 dark:border-red-700',
                                    ];
                                    $revisionIcons = [
                                        \App\Models\ScheduleRevisionRequest::STATUS_PENDING  => '⏳',
                                        \App\Models\ScheduleRevisionRequest::STATUS_APPROVED => '✓',
                                        \App\Models\ScheduleRevisionRequest::STATUS_REJECTED => '✕',
                                    ];
                                @endphp
                                <div class="mt-1 inline-block rounded border px-2 py-0.5 text-[9px] font-black uppercase tracking-widest {{ $revisionClasses[$sched->revision_request->status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $revisionIcons[$sched->revision_request->status] ?? '' }}
                                    Revision {{ ucfirst($sched->revision_request->status) }}
                                </div>
                            @endif
                        </td>

                    </tr>

                    @if($loop->last)
                            </tbody>
                        </table>
                    @endif

                @empty
                    <div class="py-24 text-center">
                        <div class="inline-flex flex-col items-center gap-4">
                            <div class="text-6xl opacity-20 animate-pulse">📚</div>
                            <h3 class="text-[20px] font-black text-slate-800 dark:text-slate-100
                                       uppercase tracking-tighter">
                                No Schedules Found
                            </h3>
                            <p class="text-[14px] text-slate-400 dark:text-slate-500 max-w-xs">
                                No subjects scheduled for <strong>{{ $departmentName }}</strong>
                                · Year {{ $selectedYear }} · Section {{ $selectedSection }} yet.
                            </p>
                        </div>
                    </div>
                @endforelse

            </div>{{-- /table wrapper --}}

        </div>{{-- /main container --}}

        {{-- ════════════════════════════════════════════════════════════════
             OFFICIAL PRINT OUTPUT (print-only)
             ────────────────────────────────────────────────────────────────
             Built as its own static block instead of reusing the interactive
             workspace table above. The workspace table holds edit-mode
             dropdowns, checkboxes, and action buttons that don't translate
             cleanly to a printed page — that mismatch was the source of the
             garbled/leaky print output. This block is plain, self-contained,
             and renders ONLY in print (see #official-print-block in the
             <style> tag below), with exactly the columns requested:
             Time | Code | Subject Title | Units | Day(s) | Room — no
             Faculty, no EDP/Status noise, no buttons.
             ════════════════════════════════════════════════════════════════ --}}
        <div id="official-print-block" class="hidden">

            {{-- Letterhead --}}
            <div class="print-letterhead">
                <h1>Professional Academy of the Philippines</h1>
                <p>Naga City, Cebu, Philippines</p>
            </div>

            <div class="print-title-block">
                <h2>Official Block Schedule</h2>
                <p>S.Y. {{ $schoolYear }} &nbsp;|&nbsp; {{ strtoupper($semester) }} SEMESTER BLOCK SCHEDULE</p>
            </div>

            <div class="print-meta">
                <p><span>Department:</span> {{ strtoupper($printCollegeLabel ?? '') }} ({{ $printCollegeCode ?? '—' }})</p>
                <p><span>Program:</span> {{ strtoupper($departmentName) }}</p>
                <p><span>Year/Sec:</span> Year {{ $selectedYear }} &middot; Section {{ $selectedSection }}</p>
            </div>

            <table class="print-table">
                <colgroup>
                    <col style="width: 17%">{{-- Time --}}
                    <col style="width: 12%">{{-- Code --}}
                    <col style="width: 35%">{{-- Subject Title --}}
                    <col style="width: 7%">{{-- Units --}}
                    <col style="width: 12%">{{-- Day(s) --}}
                    <col style="width: 17%">{{-- Room --}}
                </colgroup>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Code</th>
                        <th>Subject Title</th>
                        <th>Units</th>
                        <th>Day(s)</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scheduleRows as $printRow)
                        <tr>
                            {{-- Time --}}
                            <td class="print-center">
                                @if($printRow->status === 'not_scheduled')
                                    NOT SCHEDULED
                                @else
                                    {{ \Carbon\Carbon::parse($printRow->start_time)->format('h:i A') }}&ndash;{{ \Carbon\Carbon::parse($printRow->end_time)->format('h:i A') }}
                                @endif
                            </td>

                            {{-- Code: EDP code when assigned, otherwise the subject code --}}
                            <td class="print-center">
                                {{ $printRow->subject?->edp_code ?: $printRow->subject?->subject_code ?: '—' }}
                            </td>

                            {{-- Subject Title --}}
                            <td class="print-subject">
                                {{ $printRow->subject?->description ?? '—' }}
                            </td>

                            {{-- Units --}}
                            <td class="print-center">{{ $printRow->subject?->units ?? 0 }}u</td>

                            {{-- Day(s) --}}
                            <td class="print-center">{{ $printRow->day_display }}</td>

                            {{-- Room --}}
                            <td class="print-center">
                                @if($printRow->status === 'not_scheduled')
                                    UNASSIGNED
                                @else
                                    {{ $printRow->room?->room_name ?? 'No Room' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="print-center">No subjects scheduled for this section yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="print-footer">
                <p>Generated By: Classly Scheduling System</p>
                <p>Date Printed: {{ now()->format('F j, Y') }}</p>
            </div>

        </div>{{-- /official print block --}}

    </div>{{-- /max-w --}}


    {{-- ════════════════════════════════════════════════════════════════
         ADMIN PERMISSION PANEL MODAL
         ════════════════════════════════════════════════════════════════
         Only rendered when $canManagePermission (Admin) AND $showPermissionPanel.
         Allows Admin to:
           - See the current state (enabled / disabled)
           - Grant to any active Registrar
           - Revoke from a Registrar who currently has it
           - View recent permission audit log
         ════════════════════════════════════════════════════════════════ --}}
    @if($canManagePermission && $showPermissionPanel)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
                 wire:click="closePermissionPanel"></div>

            {{-- Panel Card --}}
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl
                         border border-slate-200 dark:border-slate-700
                         w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                {{-- ── Panel Header ─────────────────────────────────────── --}}
                <div class="flex items-start justify-between px-7 py-5
                             border-b border-slate-200 dark:border-slate-700
                             bg-gradient-to-r from-indigo-50/60 to-blue-50/30
                             dark:from-indigo-900/10 dark:to-blue-900/5 flex-shrink-0">
                    <div>
                        <h3 class="text-[18px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight
                                   flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Registrar Finalization Access
                        </h3>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                            Grant <strong>global</strong> finalization access — covers ALL departments, year levels, and sections.
                        </p>
                    </div>
                    <button wire:click="closePermissionPanel"
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200
                                   transition-colors p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 min-h-0">

                    {{-- ── Current Status Banner ────────────────────────── --}}
                    <div class="mx-6 mt-5">
                        @if($registrarWithPermission)
                            {{-- ENABLED state --}}
                            <div class="flex items-center gap-4 px-5 py-4 rounded-xl
                                         bg-emerald-50 dark:bg-emerald-900/20
                                         border-2 border-emerald-300 dark:border-emerald-600">
                                {{-- Animated pulse indicator --}}
                                <div class="flex-shrink-0 relative">
                                    <span class="absolute -inset-1 rounded-full bg-emerald-400/30 animate-ping"></span>
                                    <span class="relative w-4 h-4 rounded-full bg-emerald-500 block"></span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[14px] font-black text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">
                                        Access Enabled
                                    </p>
                                    <p class="text-[13px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                        <strong>{{ $registrarWithPermission->name }}</strong> can finalize schedules across <em>all departments</em>.
                                    </p>
                                </div>
                                {{-- Revoke quick action --}}
                                <button
                                    wire:click="revokeRegistrarPermission({{ $registrarWithPermission->id }})"
                                    wire:confirm="Revoke finalization access from {{ $registrarWithPermission->name }}? They will no longer be able to finalize schedules."
                                    wire:loading.attr="disabled"
                                    class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                           bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                           border border-red-200 dark:border-red-700
                                           hover:bg-red-200 dark:hover:bg-red-900/50 transition-all active:scale-95">
                                    Revoke
                                </button>
                            </div>
                        @else
                            {{-- DISABLED state --}}
                            <div class="flex items-center gap-4 px-5 py-4 rounded-xl
                                         bg-slate-50 dark:bg-slate-800/60
                                         border-2 border-slate-200 dark:border-slate-700">
                                <span class="flex-shrink-0 w-4 h-4 rounded-full bg-slate-300 dark:bg-slate-600 block"></span>
                                <div class="flex-1">
                                    <p class="text-[14px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                                        Access Disabled
                                    </p>
                                    <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Only Administrators can currently finalize schedules.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ── Registrar List ────────────────────────────────── --}}
                    <div class="px-6 mt-5">
                        <p class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                   uppercase tracking-widest mb-3">
                            Active Registrar Accounts
                        </p>

                        @if($allRegistrars->isEmpty())
                            <div class="py-8 text-center text-slate-400 dark:text-slate-500 text-[13px]">
                                No active Registrar accounts found.
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($allRegistrars as $reg)
                                    @php $hasPermission = (bool) $reg->can_finalize_schedule; @endphp
                                    <div @class([
                                            'flex items-center gap-4 px-4 py-3.5 rounded-xl border-2 transition-all',
                                            'border-emerald-300 dark:border-emerald-600 bg-emerald-50/60 dark:bg-emerald-900/10'
                                                => $hasPermission,
                                            'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-slate-300 dark:hover:border-slate-600'
                                                => ! $hasPermission,
                                        ])>

                                        {{-- User avatar --}}
                                        <div @class([
                                                'w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-[14px] uppercase',
                                                'bg-emerald-500 text-white' => $hasPermission,
                                                'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' => ! $hasPermission,
                                            ])>
                                            {{ substr($reg->name, 0, 2) }}
                                        </div>

                                        {{-- User info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-[14px] font-bold text-slate-800 dark:text-slate-100 truncate">
                                                    {{ $reg->name }}
                                                </span>
                                                @if($hasPermission)
                                                    <span class="flex-shrink-0 text-[11px] bg-emerald-500 text-white
                                                                 px-2 py-0.5 rounded font-black uppercase tracking-wide">
                                                        ✓ Access Granted
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                {{ $reg->email }}
                                                @if($reg->department)
                                                    · {{ $reg->department }}
                                                @endif
                                            </p>
                                        </div>

                                        {{-- Grant / Revoke action --}}
                                        @if($hasPermission)
                                            <button
                                                wire:click="revokeRegistrarPermission({{ $reg->id }})"
                                                wire:confirm="Revoke finalization access from {{ $reg->name }}?"
                                                wire:loading.attr="disabled"
                                                class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                                       bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                                       border border-red-200 dark:border-red-700
                                                       hover:bg-red-100 dark:hover:bg-red-900/40 transition-all active:scale-95">
                                                Revoke
                                            </button>
                                        @else
                                            <button
                                                wire:click="grantRegistrarPermission({{ $reg->id }})"
                                                wire:confirm="Grant GLOBAL schedule finalization access to {{ $reg->name }}? They will be able to finalize ALL departments and sections. Any other Registrar's access will be revoked."
                                                wire:loading.attr="disabled"
                                                class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                                       bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                                                       border border-blue-200 dark:border-blue-700
                                                       hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-all active:scale-95">
                                                Grant
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ── Audit Log ─────────────────────────────────────── --}}
                    @if($recentPermissionLogs->isNotEmpty())
                        <div class="px-6 mt-6 pb-2">
                            <p class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                       uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Recent Activity
                            </p>
                            <div class="space-y-1.5">
                                @foreach($recentPermissionLogs as $log)
                                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-lg
                                                 bg-slate-50 dark:bg-slate-800/50
                                                 border border-slate-100 dark:border-slate-700/50">
                                        <span class="text-base flex-shrink-0 mt-0.5">{{ $log->action_icon }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[12px] text-slate-700 dark:text-slate-300 font-semibold truncate">
                                                {{ $log->description ?? $log->action_label }}
                                            </p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                {{ $log->created_at->diffForHumans() }}
                                                @if($log->performer)
                                                    · by {{ $log->performer->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>{{-- /scrollable body --}}

                {{-- ── Panel Footer ──────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-7 py-4 flex-shrink-0
                             border-t border-slate-200 dark:border-slate-700
                             bg-slate-50/80 dark:bg-slate-800/50">
                    <p class="text-[12px] text-slate-400 dark:text-slate-500">
                        Permission is <strong>global</strong> — covers all depts, sections &amp; years. All changes are logged.
                    </p>
                    <button wire:click="closePermissionPanel"
                            class="px-5 py-2.5 bg-slate-200 dark:bg-slate-700
                                   text-slate-700 dark:text-slate-300 font-bold rounded-xl
                                   text-[13px] hover:bg-slate-300 dark:hover:bg-slate-600
                                   transition-all active:scale-95">
                        Close
                    </button>
                </div>

            </div>{{-- /panel card --}}
        </div>{{-- /modal overlay --}}
    @endif


    {{-- ════════════════════════════════════════════════════════════════
         WORKSPACE CONFLICT MODAL
         ════════════════════════════════════════════════════════════════ --}}
    @if($showWorkspaceConflictModal)
        <div class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xl">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-red-500/30 bg-white shadow-2xl dark:bg-slate-950">
                <div class="border-b border-red-200 bg-red-50 px-6 py-4 dark:border-red-900/60 dark:bg-red-950/30">
                    <h3 class="text-lg font-black uppercase tracking-tight text-red-700 dark:text-red-200">Workspace Conflicts Found</h3>
                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-300">Your edits are temporary. Resolve these rows, then click Done Editing again.</p>
                </div>
                <div class="max-h-[60vh] space-y-3 overflow-y-auto p-6">
                    @foreach($workspaceConflictErrors as $error)
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 dark:border-red-900/60 dark:bg-red-950/25 dark:text-red-200">{{ $error }}</div>
                    @endforeach
                    @if(!empty($workspaceRecommendations))
                        <div class="pt-2">
                            <p class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Updated Recommendations</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($workspaceRecommendations as $suggestion)
                                    <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-3 text-xs dark:border-blue-900/60 dark:bg-blue-950/20">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-black text-blue-800 dark:text-blue-200">{{ $suggestion['label'] ?? 'Alternative' }}</p>
                                            <span class="rounded bg-blue-600 px-2 py-1 text-[9px] font-black uppercase text-white">{{ $suggestion['match_label'] ?? 'GOOD' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
                    <button type="button" wire:click="closeWorkspaceConflictModal" class="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600">Keep Editing</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════
         REVISION REQUEST MODAL
         ════════════════════════════════════════════════════════════════ --}}
    @if($showRevisionModal)
        <div class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xl"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-amber-500/30 bg-white shadow-2xl dark:bg-slate-950"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">

                {{-- Modal Header ────────────────────────────────────────────── --}}
                <div class="border-b border-amber-200 bg-amber-50 px-6 py-4 dark:border-amber-900/60 dark:bg-amber-950/30 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-amber-800 dark:text-amber-200">
                            Request Faculty Revision
                        </h3>
                        <p class="mt-1 text-sm font-semibold text-amber-700 dark:text-amber-300">
                            {{ $revisionSubject?->subject_code }} — {{ $revisionSubject?->description }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeRevisionModal"
                            class="text-amber-500 hover:text-amber-800 dark:hover:text-amber-200 p-1.5 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Subject + Faculty Summary Card ─────────────────────────── --}}
                @if($revisionSubject)
                <div class="mx-6 mt-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/50 px-4 py-3 text-[12px]">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Subject Code</p>
                            <p class="font-bold text-slate-800 dark:text-slate-100">{{ $revisionSubject->subject_code }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Section / Year</p>
                            <p class="font-bold text-slate-800 dark:text-slate-100">
                                {{ $revisionSubject->section }} · Year {{ $revisionSubject->year_level }}
                            </p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Description</p>
                            <p class="font-semibold text-slate-700 dark:text-slate-300">{{ $revisionSubject->description }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Current Faculty</p>
                            <p class="font-bold text-slate-700 dark:text-slate-200">
                                @php
                                    $currentFacForRevision = $revisionCurrentFacultyId
                                        ? \App\Models\Faculty::find($revisionCurrentFacultyId)
                                        : null;
                                @endphp
                                {{ $currentFacForRevision?->full_name ?? 'Unassigned' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-0.5">Semester / S.Y.</p>
                            <p class="font-bold text-slate-700 dark:text-slate-200">{{ $semester }} · {{ $schoolYear }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Form body ────────────────────────────────────────────────── --}}
                <div class="space-y-4 px-6 py-5">
                    @if($revisionError)
                        @php $isFacultyConflict = str_starts_with($revisionError, "⚠"); @endphp
                        @if($isFacultyConflict)
                            {{-- Faculty scheduling conflict — prominent amber warning --}}
                            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700/60 dark:bg-amber-950/30 px-4 py-3.5 flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5 w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                              d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-1">
                                        Faculty Scheduling Conflict
                                    </p>
                                    <p class="text-[13px] font-semibold text-amber-800 dark:text-amber-200 leading-snug">
                                        {{ ltrim($revisionError, "⚠ ") }}
                                    </p>
                                </div>
                            </div>
                        @else
                            {{-- Generic validation error --}}
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 dark:border-red-900/60 dark:bg-red-950/25 dark:text-red-200 flex items-start gap-2">
                                <span class="flex-shrink-0">⛔</span>
                                <span>{{ $revisionError }}</span>
                            </div>
                        @endif
                    @endif

                    <label class="block">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Requested Faculty
                        </span>
                        <select wire:model.live="revisionRequestedFacultyId"
                                class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none transition-all">
                            <option value="">— Choose replacement faculty —</option>
                            @foreach($revisionFacultyOptions as $faculty)
                                <option value="{{ $faculty->id }}">{{ $faculty->full_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Reason for Revision <span class="text-red-500">*</span>
                        </span>
                        <textarea wire:model.live="revisionReason"
                                  rows="3"
                                  class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none transition-all resize-none"
                                  placeholder="e.g., Faculty specialization alignment, workload balancing…"></textarea>
                    </label>
                </div>

                {{-- Footer ────────────────────────────────────────────────────── --}}
                <div class="flex justify-end gap-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900 px-6 py-4">
                    <button type="button"
                            wire:click="closeRevisionModal"
                            wire:loading.attr="disabled"
                            class="rounded-xl bg-slate-200 dark:bg-slate-700 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all active:scale-95 disabled:opacity-60">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="submitRevisionRequest"
                            wire:loading.attr="disabled"
                            wire:target="submitRevisionRequest"
                            class="rounded-xl bg-amber-600 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white hover:bg-amber-500 transition-all active:scale-95 disabled:opacity-60 flex items-center gap-2">
                        <span wire:loading.remove wire:target="submitRevisionRequest">Submit Request</span>
                        <span wire:loading wire:target="submitRevisionRequest" class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Submitting…
                        </span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════
         FACULTY ASSIGNMENT MODAL
         ════════════════════════════════════════════════════════════════ --}}
    @if($showFacultyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
                 wire:click="closeFacultyModal"></div>

            {{-- Modal Card --}}
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl
                         border border-slate-200 dark:border-slate-700
                         w-full max-w-2xl max-h-[90vh] flex flex-col
                         overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                {{-- Modal Header --}}
                <div class="flex items-start justify-between px-7 py-5
                             border-b border-slate-200 dark:border-slate-700
                             bg-gradient-to-r from-blue-50/60 to-indigo-50/30
                             dark:from-blue-900/10 dark:to-indigo-900/5 flex-shrink-0">
                    <div>
                        <h3 class="text-[18px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">
                            Assign Faculty
                        </h3>
                        @if($modalSubject)
                            <p class="text-[13px] text-blue-600 dark:text-blue-400 font-semibold mt-1">
                                {{ $modalSubject->subject_code }} — {{ $modalSubject->description }}
                                <span class="text-slate-400 dark:text-slate-500 font-normal ml-1.5">
                                    ({{ $modalSubject->units }}u · {{ strtoupper($modalSubject->type ?? 'Major') }})
                                </span>
                            </p>
                        @endif
                    </div>
                    <button wire:click="closeFacultyModal"
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200
                                   transition-colors p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Error Banner --}}
                @if($assignError)
                    <div class="flex-shrink-0 mx-6 mt-4 flex items-start gap-2.5
                                 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700
                                 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-[13px] font-medium">
                        <span class="flex-shrink-0 mt-0.5">⛔</span>
                        <span>{{ $assignError }}</span>
                    </div>
                @endif

                @if(!empty($facultyAssignmentSuggestions))
                    <div class="mx-6 mt-3 flex-shrink-0 rounded-xl border border-blue-200 bg-blue-50/80 p-3 dark:border-blue-900/60 dark:bg-blue-950/20">
                        <p class="text-[10px] font-black uppercase tracking-widest text-blue-700 dark:text-blue-300">Available Faculty Recommendations</p>
                        <div class="mt-2 space-y-2">
                            @foreach($facultyAssignmentSuggestions as $suggestion)
                                <button type="button"
                                        wire:click="assignFaculty({{ $suggestion['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="assignFaculty({{ $suggestion['id'] }})"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg border border-white/70 bg-white/85 px-3 py-2 text-left transition hover:border-blue-300 hover:bg-white disabled:opacity-60 dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-blue-700">
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-black text-slate-800 dark:text-slate-100">{{ $suggestion['name'] }}</span>
                                        <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $suggestion['department'] }} · {{ $suggestion['load'] }}/{{ $suggestion['max_units'] }}u</span>
                                    </span>
                                    <span class="shrink-0 rounded bg-blue-100 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ $suggestion['match_label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Search Input --}}
                <div class="px-6 pt-4 pb-2 flex-shrink-0">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="facultySearch"
                               type="text"
                               placeholder="Search by name or department…"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800
                                      border border-slate-200 dark:border-slate-700 rounded-xl
                                      text-[14px] text-slate-800 dark:text-slate-200
                                      placeholder-slate-400 dark:placeholder-slate-500
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      outline-none transition-all">
                    </div>
                </div>

                {{-- Faculty List --}}
                <div class="overflow-y-auto flex-1 px-6 pb-4 space-y-2 min-h-0 mt-1">

                    @forelse($modalFaculty as $faculty)
                        @php
                            $isCurrentlyAssigned = ($faculty->id === $currentFacultyId);
                            $currentUnits        = $this->getFacultyCurrentUnits($faculty->id);
                            $maxUnits            = $faculty->max_units ?? 21;
                            $newUnits            = (int) ($modalSubject?->units ?? 0);
                            $wouldOverload       = ($currentUnits + $newUnits) > $maxUnits;
                        @endphp

                        <button
                            wire:click="assignFaculty({{ $faculty->id }})"
                            wire:loading.attr="disabled"
                            @class([
                                'w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl transition-all text-left group',
                                'border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    => $isCurrentlyAssigned,
                                'border border-slate-200 dark:border-slate-700 hover:border-blue-400 hover:bg-blue-50/60 dark:hover:bg-blue-900/10 bg-white dark:bg-slate-800/50'
                                    => (! $isCurrentlyAssigned && ! $wouldOverload),
                                'border border-amber-200 dark:border-amber-700/50 bg-amber-50/60 dark:bg-amber-900/5 opacity-75'
                                    => ($wouldOverload && ! $isCurrentlyAssigned),
                            ])>

                            {{-- Avatar --}}
                            <div @class([
                                    'w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-[14px] uppercase',
                                    'bg-blue-500 text-white'                                                           => $isCurrentlyAssigned,
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'     => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'gened'),
                                    'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400'         => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'cross_department'),
                                    'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'                => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'departmental'),
                                ])>
                                {{ substr($faculty->full_name, 0, 2) }}
                            </div>

                            {{-- Name + Department --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[14px] font-bold text-slate-800 dark:text-slate-100 truncate">
                                        {{ $faculty->full_name }}
                                    </span>
                                    @if($isCurrentlyAssigned)
                                        <span class="flex-shrink-0 text-[11px] bg-blue-500 text-white
                                                     px-2 py-0.5 rounded font-black uppercase">
                                            Current
                                        </span>
                                    @endif
                                    @if($wouldOverload)
                                        <span class="flex-shrink-0 text-[11px] bg-amber-100 text-amber-700
                                                     dark:bg-amber-900/30 dark:text-amber-400
                                                     px-2 py-0.5 rounded font-black uppercase border border-amber-200 dark:border-amber-700">
                                            ⚠ Overload
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $faculty->displayDepartment() }}
                                    </span>
                                </div>
                            </div>

                            {{-- Units Load Bar --}}
                            <div class="flex-shrink-0 text-right min-w-[60px]">
                                <div class="text-[12px] font-bold
                                            @if($wouldOverload) text-amber-600 dark:text-amber-400 @else text-slate-600 dark:text-slate-400 @endif">
                                    {{ $currentUnits + $newUnits }}/{{ $maxUnits }}u
                                </div>
                                <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full mt-1.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all
                                                @if($wouldOverload)
                                                    bg-amber-400
                                                @elseif(($currentUnits + $newUnits) >= $maxUnits * 0.8)
                                                    bg-yellow-400
                                                @else
                                                    bg-emerald-500
                                                @endif"
                                         style="width: {{ min(100, round((($currentUnits + $newUnits) / max(1, $maxUnits)) * 100)) }}%">
                                    </div>
                                </div>
                            </div>

                        </button>

                    @empty
                        <div class="py-10 text-center">
                            <div class="text-4xl opacity-25 mb-3">👤</div>
                            <p class="text-[14px] text-slate-400 dark:text-slate-500">
                                @if(blank($facultySearch))
                                    No eligible faculty found for this subject type.
                                @else
                                    No faculty matching "{{ $facultySearch }}".
                                @endif
                            </p>
                        </div>
                    @endforelse

                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between px-7 py-4 flex-shrink-0
                             border-t border-slate-200 dark:border-slate-700
                             bg-slate-50/80 dark:bg-slate-800/50">
                    <div>
                        @if($currentFacultyId)
                            <button wire:click="removeFacultyAssignment"
                                    wire:confirm="Remove the faculty assignment from this schedule slot?"
                                    class="text-[13px] font-bold text-red-600 dark:text-red-400
                                           hover:text-red-800 dark:hover:text-red-300
                                           flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Remove Assignment
                            </button>
                        @endif
                    </div>

                    <button wire:click="closeFacultyModal"
                            class="px-5 py-2.5 bg-slate-200 dark:bg-slate-700
                                   text-slate-700 dark:text-slate-300 font-bold rounded-xl
                                   text-[13px] hover:bg-slate-300 dark:hover:bg-slate-600
                                   transition-all active:scale-95">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

        </div>{{-- /space-y-4 schedule content --}}
    </div>{{-- /schedule section px-5 --}}

</div>{{-- /root alpine x-data --}}
<style>
    @page {
        size: A4 portrait;
        margin: 14mm 16mm;
    }

    @media print {
        /* ── Hide every interactive / chrome element ──────────────────── */
        aside, nav, header, footer,
        .sidebar, .navbar,
        [class*="sidebar"], [class*="navbar"],
        [class*="nav-bar"], [class*="top-bar"], [class*="topbar"],
        .screen-only, button, [wire\:confirm] {
            display: none !important;
        }

        /* The entire interactive workspace card (color bar, gradient header,
           filters, action buttons, and the editable schedule table) is
           replaced on the printed page by #official-print-block below.
           Hiding it here — rather than trying to selectively hide its
           columns with :nth-child rules — is what fixes the old leaky,
           garbled print output (it was printing form controls and the
           Faculty column because the column-hiding hacks didn't keep up
           with the table's structure). */
        #study-load-container {
            display: none !important;
        }

        *, *::before, *::after {
            box-shadow: none !important;
            text-shadow: none !important;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            overflow: visible !important;
            font-family: 'Calibri', 'Arial', sans-serif;
        }

        [wire\:id], [data-livewire], [data-livewire] > div {
            all: unset !important;
            display: block !important;
        }

        .min-h-screen, div[class*="max-w-"], div[class*="space-y-"] {
            min-height: unset !important;
            max-width: 100% !important;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* ── Official Print Block ─────────────────────────────────────── */
        #official-print-block {
            display: block !important;
            color: #1e293b;
        }

        .print-letterhead {
            text-align: center;
            margin-bottom: 2mm;
        }
        .print-letterhead h1 {
            font-size: 15pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin: 0;
        }
        .print-letterhead p {
            font-size: 10pt;
            margin: 1mm 0 0;
        }

        .print-title-block {
            text-align: center;
            border-top: 2px solid #1e293b;
            border-bottom: 2px solid #1e293b;
            padding: 2mm 0;
            margin: 3mm 0 4mm;
        }
        .print-title-block h2 {
            font-size: 12pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0;
        }
        .print-title-block p {
            font-size: 9.5pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 1mm 0 0;
        }

        .print-meta {
            margin-bottom: 4mm;
            font-size: 9.5pt;
        }
        .print-meta p { margin: 0.5mm 0; }
        .print-meta span {
            display: inline-block;
            width: 26mm;
            font-weight: 900;
            text-transform: uppercase;
        }

        table.print-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-size: 9pt !important;
            page-break-inside: auto;
        }

        table.print-table thead { display: table-header-group; }

        table.print-table thead tr {
            background-color: #1e293b !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.print-table th {
            border: 1px solid #334155 !important;
            padding: 4px 6px !important;
            font-size: 8.5pt !important;
            font-weight: 900 !important;
            text-align: center !important;
            text-transform: uppercase;
            letter-spacing: 0.05em !important;
            color: white !important;
        }

        table.print-table td {
            border: 1px solid #cbd5e1 !important;
            padding: 4px 6px !important;
            font-size: 9pt !important;
            color: #1e293b !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        table.print-table td.print-center { text-align: center !important; }
        table.print-table td.print-subject { font-weight: 700; }

        table.print-table tbody tr:nth-child(even) td {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        table.print-table tbody tr:nth-child(odd) td { background-color: #ffffff !important; }

        table.print-table tr { page-break-inside: avoid; }

        .print-footer {
            margin-top: 5mm;
            padding-top: 2mm;
            border-top: 1px solid #cbd5e1;
            font-size: 8pt;
            color: #475569;
        }
        .print-footer p { margin: 0.5mm 0; }
    }
</style>