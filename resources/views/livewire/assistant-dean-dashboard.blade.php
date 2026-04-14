<div class="h-screen overflow-hidden flex transition-all duration-700 font-sans selection:bg-indigo-500/20" 
     x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        notificationsOpen: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
     }" 
     :class="darkMode ? 'bg-[#020617] text-white' : 'text-slate-900'"
     :style="!darkMode ? 'background: linear-gradient(233deg, #e0e7ff 0%, #c7d2fe 17%, #a5b4fc 46%, #818cf8 78%, #6366f1 90%);' : ''">
    
   

    <main class="flex-1 flex flex-col relative z-10 p-6 space-y-6">
        <header class="flex items-center justify-between">
            <div class="transition-transform duration-500 hover:translate-x-1">
                <h2 class="text-3xl font-black uppercase tracking-tighter" :class="darkMode ? 'text-white' : 'text-slate-900'">
                    Associate <span class="text-indigo-500">Dean</span>
                </h2>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] opacity-40">Minor Subjects Coordination | PAP CRAMS v3.0</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div @click="toggleTheme()" class="cursor-pointer p-3 rounded-2xl transition-all group relative overflow-hidden" :class="darkMode ? 'bg-white/5 hover:bg-white/10' : 'bg-black/5 hover:bg-black/10'">
                    <div class="relative z-10">
                        <svg x-show="darkMode" class="w-6 h-6 text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.6)]" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <svg x-show="!darkMode" class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    </div>
                </div>
                <div class="flex items-center gap-3 pl-4 border-l border-white/20">
                    <div class="text-right">
                        <p class="text-xs font-black uppercase">{{ auth()->user()->name }}</p>
                        <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">Assistant Dean</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-black text-white shadow-lg shadow-indigo-500/30">
                        {{ auth()->user()->initials() }}
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 grid grid-cols-12 grid-rows-6 gap-6 min-h-0 pb-4">
            
            <div class="col-span-7 row-span-2 rounded-[2.5rem] border p-8 transition-all duration-500 relative overflow-hidden group hover:scale-[1.01] shadow-2xl"
                 :class="darkMode ? 'bg-[#0f172a]/80 backdrop-blur-2xl border-white/10' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 via-transparent to-pink-600/20 opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="relative z-10 flex items-center justify-between h-full">
                    <div>
                        <h1 class="text-4xl font-black tracking-tighter mb-2" :class="darkMode ? 'text-white' : 'text-slate-950'">Inter-Dept Sync.</h1>
                        <p class="text-xs opacity-60 leading-relaxed max-w-sm">Managing Minor Curriculum schedules for CCS, CTE, COC, and SHTM. Ensure zero-overlap with major departmental subjects.</p>
                    </div>
                    <div class="text-9xl grayscale opacity-10 group-hover:opacity-100 group-hover:grayscale-0 group-hover:-rotate-12 transition-all duration-700 select-none">🌐</div>
                </div>
            </div>

            <div class="col-span-5 row-span-2 rounded-[2.5rem] border p-8 flex flex-col justify-between transition-all duration-500 relative overflow-hidden group hover:scale-[1.05] shadow-xl"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5 hover:border-indigo-500/50' : 'bg-white/70 border-white/30 backdrop-blur-sm'">
                <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/10 via-transparent to-transparent"></div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-40 group-hover:text-indigo-400 transition-all">Managed Minor Subjects</p>
                    <p class="text-6xl font-black tabular-nums tracking-tighter mt-2 group-hover:text-indigo-500 transition-colors">{{ $minorSubjectsCount }}</p>
                </div>
                <div class="absolute -right-2 -bottom-2 text-8xl opacity-5 group-hover:opacity-30 transition-all duration-700">📚</div>
                <span class="text-[9px] font-bold uppercase tracking-widest text-indigo-500">Global Curriculum View →</span>
            </div>

            <div class="col-span-4 row-span-4 rounded-[2.5rem] border p-8 flex flex-col transition-all duration-500 relative overflow-hidden group shadow-2xl"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <h3 class="font-black uppercase tracking-widest text-[10px] mb-6 opacity-40 group-hover:text-indigo-400">Minor Load Distribution</h3>
                
                <div class="flex-1 grid grid-cols-6 gap-2 relative z-10">
                    <div class="col-span-1"></div>
                    @foreach(['M', 'T', 'W', 'T', 'F'] as $day)
                        <div class="text-[9px] font-black text-center opacity-30">{{ $day }}</div>
                    @endforeach

                    @php $times = ['08:00', '10:00', '13:00', '15:00', '17:00']; @endphp
                    @foreach($times as $time)
                        <div class="text-[9px] font-black opacity-30 flex items-center">{{ $time }}</div>
                        @for($i=0; $i<5; $i++)
                            <div class="rounded-lg bg-indigo-500/{{ rand(10, 80) }} border border-white/5 transition-all hover:scale-110 cursor-help"></div>
                        @endfor
                    @endforeach
                </div>
                <p class="text-[9px] font-bold mt-4 opacity-30 italic text-center text-indigo-500">Tracking Gen-Ed Room Pressure</p>
            </div>

            <div class="col-span-5 row-span-3 rounded-[2.5rem] border p-8 flex flex-col relative overflow-hidden group shadow-xl transition-all duration-500"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <h3 class="font-black uppercase tracking-widest text-[10px] mb-4 opacity-40">Departmental Schedule Sync</h3>
                <div class="space-y-4 overflow-y-auto pr-2 custom-scrollbar relative z-10">
                    @foreach($departments as $dept)
                    <div class="p-4 rounded-3xl border transition-all hover:bg-indigo-500/5 flex items-center justify-between" 
                         :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white/80 border-slate-100 shadow-sm'">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-[10px]">{{ $dept }}</div>
                            <div>
                                <p class="text-xs font-bold leading-tight">Minor Scheduling</p>
                                <p class="text-[9px] font-black opacity-40 uppercase">Conflicts: 0</p>
                            </div>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_emerald]"></span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="col-span-3 row-span-2 rounded-[2.5rem] border p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group shadow-xl transition-all duration-500 hover:scale-[1.05]"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-40 text-indigo-500">Sync Deadline</p>
                <p class="text-6xl font-black tracking-tighter">{{ $daysInMonth - $todayDay }}</p>
                <p class="text-xs font-bold opacity-60 mt-1 uppercase">Days Left</p>
            </div>

            <div class="col-span-3 row-span-2 grid grid-cols-1 gap-3">
                <a href="{{ route('master-grid') }}" class="rounded-[2rem] border flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] transition-all duration-300 hover:scale-[1.03] hover:bg-indigo-600 hover:text-white group"
                   :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white border-slate-200 shadow-lg'">
                   <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg> Master Grid Sync
                </a>
                <a href="#" class="rounded-[2rem] border flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] transition-all duration-300 hover:scale-[1.03] hover:bg-slate-900 hover:text-white"
                   :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white border-slate-200 shadow-lg'">
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2-8H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2z"/></svg> Export Report
                </a>
            </div>

            <div class="col-span-5 row-span-1 rounded-[2rem] border px-8 flex items-center justify-between relative overflow-hidden group shadow-xl transition-all"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <div class="flex items-center gap-4 relative z-10">
                    <div class="flex -space-x-3">
                        @for($i=0; $i<3; $i++)
                            <div class="w-8 h-8 rounded-full border-2 border-slate-800 bg-indigo-500 flex items-center justify-center text-[10px] font-bold text-white">F</div>
                        @endfor
                    </div>
                    <p class="text-[10px] font-black uppercase opacity-60">Minor Faculty Online: <span class="text-indigo-500">{{ $minorFacultyCount ?? 0 }}</span></p>
                </div>
                <div class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></div>
            </div>

        </div>
    </main>
</div>