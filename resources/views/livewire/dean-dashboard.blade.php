<div class="h-screen overflow-hidden flex transition-all duration-700 font-sans selection:bg-blue-500/20" 
     x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        notificationsOpen: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
     }" 
     :class="darkMode ? 'bg-[#020617] text-white' : 'text-slate-900'"
     :style="!darkMode ? 'background: linear-gradient(233deg, rgba(238, 174, 202, 1) 0%, rgba(185, 182, 220, 1) 17%, rgba(168, 184, 226, 1) 46%, rgba(157, 186, 230, 1) 78%, rgba(148, 187, 233, 1) 90%);' : ''">
    
    

    <main class="flex-1 flex flex-col relative z-10 p-6 space-y-6">
        <header class="flex items-center justify-between">
            <div class="transition-transform duration-500 hover:translate-x-1">
                 <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">
                    <span class="text-blue-600">{{ $department }}</span> Dean's Portal
                </h2>
                <p class="text-sm text-slate-400 font-medium italic">Departmental Academic Management</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" 
                            class="p-3 rounded-2xl transition-all relative group overflow-hidden"
                            :class="darkMode ? 'bg-white/5 hover:bg-white/10' : 'bg-black/5 hover:bg-black/10'">
                        <div class="absolute inset-0 bg-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <svg class="w-6 h-6 opacity-70 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-3 right-3 w-2 h-2 bg-red-500 rounded-full border-2 animate-pulse" :class="darkMode ? 'border-slate-900' : 'border-white'"></span>
                    </button>
                </div>

                <div @click="toggleTheme()" class="cursor-pointer p-3 rounded-2xl transition-all group relative overflow-hidden" :class="darkMode ? 'bg-white/5 hover:bg-white/10' : 'bg-black/5 hover:bg-black/10'">
                    <div class="relative z-10">
                        <svg x-show="darkMode" class="w-6 h-6 text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.6)] group-hover:rotate-45 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <svg x-show="!darkMode" class="w-6 h-6 text-slate-700 group-hover:-rotate-12 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.674M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 grid grid-cols-12 grid-rows-6 gap-6 min-h-0 pb-4">
            
            <div class="col-span-6 row-span-2 rounded-[2.5rem] border p-8 transition-all duration-500 relative overflow-hidden group hover:scale-[1.01] shadow-2xl"
                 :class="darkMode ? 'bg-[#0f172a]/80 backdrop-blur-2xl border-white/10 shadow-blue-500/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 via-transparent to-purple-600/20 opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="relative z-10 flex items-center justify-between h-full">
                    <div>
                        <h1 class="text-4xl font-black tracking-tighter mb-2" :class="darkMode ? 'text-white' : 'text-slate-950'">{{ Auth::user()->name }}</h1>
                        <p class="text-xs opacity-60 leading-relaxed max-w-sm">Department Head</p>
                    </div>
                    <div class="text-9xl grayscale opacity-10 group-hover:opacity-100 group-hover:grayscale-0 group-hover:rotate-12 transition-all duration-700 select-none">🏛️</div>
                </div>
            </div>

           <a href="{{ route('manage.rooms') }}" class="col-span-3 row-span-2 rounded-[2.5rem] border p-8 flex flex-col justify-between transition-all duration-500 relative overflow-hidden group hover:scale-[1.05] hover:-translate-y-1 shadow-xl"
               :class="darkMode ? 'bg-slate-900/40 border-white/5 hover:border-blue-500/50' : 'bg-white/70 border-white/30 backdrop-blur-sm'">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-500/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-40 group-hover:text-blue-400 group-hover:opacity-100 transition-all">Managed Rooms</p>
                    <p class="text-6xl font-black tabular-nums tracking-tighter mt-2 group-hover:text-blue-500 transition-colors">{{ $totalRooms }}</p>
                </div>
                <div class="absolute -right-2 -bottom-2 text-8xl grayscale opacity-5 group-hover:opacity-40 group-hover:grayscale-0 group-hover:-rotate-12 transition-all duration-700 select-none">🏫</div>
                <span class="text-[9px] font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all text-blue-500">View Registry →</span>
            </a>

            <div class="col-span-3 row-span-2 rounded-[2.5rem] border p-8 flex flex-col justify-between transition-all duration-500 relative overflow-hidden group hover:scale-[1.05] hover:-translate-y-1 shadow-xl"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5 hover:border-purple-500/50' : 'bg-white/70 border-white/30 backdrop-blur-sm'">
                <div class="absolute inset-0 bg-gradient-to-tr from-purple-500/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-40 group-hover:text-purple-400 group-hover:opacity-100 transition-all">Faculty Load</p>
                    <p class="text-6xl font-black tabular-nums tracking-tighter mt-2 group-hover:text-purple-500 transition-colors">142</p>
                </div>
                <div class="absolute -right-2 -bottom-2 text-8xl grayscale opacity-5 group-hover:opacity-40 group-hover:grayscale-0 group-hover:rotate-12 transition-all duration-700 select-none">👥</div>
                <span class="text-[9px] font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all text-purple-500">Staff Audit →</span>
            </div>

            <div class="col-span-4 row-span-4 rounded-[2.5rem] border p-8 flex flex-col transition-all duration-500 relative overflow-hidden group shadow-2xl"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-black uppercase tracking-widest text-[10px] mb-6 opacity-40 group-hover:text-orange-400">Institutional Conflict Heatmap</h3>
                
                <div class="flex-1 grid grid-cols-6 gap-2 relative z-10">
                    <div class="col-span-1"></div>
                    @foreach(['M', 'T', 'W', 'T', 'F'] as $day)
                        <div class="text-[9px] font-black text-center opacity-30">{{ $day }}</div>
                    @endforeach

                    @php $times = ['08:00', '10:00', '13:00', '15:00', '17:00']; @endphp
                    @foreach($times as $time)
                        <div class="text-[9px] font-black opacity-30 flex items-center">{{ $time }}</div>
                        @for($i=0; $i<5; $i++)
                            @php 
                                $intensities = ['bg-emerald-500/20', 'bg-emerald-500/40', 'bg-yellow-500/40', 'bg-red-500/60'];
                                $pick = $intensities[array_rand($intensities)];
                            @endphp
                            <div class="rounded-lg {{ $pick }} border border-white/5 transition-all hover:scale-110 hover:shadow-xl cursor-help"></div>
                        @endfor
                    @endforeach
                </div>
                <p class="text-[9px] font-bold mt-4 opacity-30 italic text-center">Density: Green (Free) to Red (Max Capacity)</p>
            </div>

            <div class="col-span-5 row-span-2 rounded-[2.5rem] border p-8 flex flex-col relative overflow-hidden group shadow-xl transition-all duration-500 hover:scale-[1.02]"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-black uppercase tracking-widest text-[10px] mb-4 opacity-40">Approval Queue</h3>
                <div class="space-y-3 overflow-y-auto pr-2 custom-scrollbar relative z-10">
                    <div class="p-4 rounded-3xl border transition-all hover:bg-white/5 flex items-center justify-between group/action" :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white/80 border-slate-100 shadow-sm'">
                        <div>
                            <p class="text-[10px] font-black text-blue-500 mb-1">DEAN OF CCS</p>
                            <p class="text-xs font-bold leading-tight">Lab Room Change: IT-101</p>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover/action:opacity-100 transition-all translate-x-4 group-hover/action:translate-x-0">
                            <button class="p-2 rounded-xl bg-emerald-500/20 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></button>
                            <button class="p-2 rounded-xl bg-red-500/20 text-red-500 hover:bg-red-500 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                    </div>
                </div>
                <div class="absolute right-4 bottom-4 text-7xl opacity-5 group-hover:opacity-20 group-hover:rotate-12 transition-all duration-700 pointer-events-none">⚖️</div>
            </div>

            <div class="col-span-3 row-span-2 rounded-[2.5rem] border p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group shadow-xl transition-all duration-500 hover:scale-[1.05]"
                 :class="darkMode ? 'bg-slate-900/40 border-white/5 hover:border-orange-500/40' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ date('F') }}</p>
                <p class="text-6xl font-black tracking-tighter">{{ date('d') }}</p>
                <p class="text-xs font-bold opacity-60 mt-1 uppercase">{{ date('l') }}</p>
                <div class="absolute -right-4 -bottom-4 text-7xl opacity-5 group-hover:opacity-20 group-hover:-rotate-12 transition-all duration-700">📅</div>
            </div>

            <div class="col-span-8 row-span-2 grid grid-cols-2 gap-6">
                <div class="rounded-[2.5rem] border p-8 shadow-xl relative overflow-hidden group transition-all duration-500 hover:scale-[1.02]"
                     :class="darkMode ? 'bg-slate-900/40 border-white/5' : 'bg-white/60 border-white/40 backdrop-blur-md'">
                    <h3 class="font-black uppercase tracking-widest text-[10px] mb-4 opacity-40">Live Room Status</h3>
                    <div class="space-y-3 relative z-10 max-h-32 overflow-y-auto custom-scrollbar">
                        <div class="flex items-center justify-between p-2 rounded-2xl hover:bg-white/5 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
                                <p class="text-[11px] font-bold">Room 101</p>
                            </div>
                            <span class="text-[10px] opacity-40">IT-101 (Occupied)</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-2xl hover:bg-white/5 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-slate-500 opacity-30"></div>
                                <p class="text-[11px] font-bold">Lab 2</p>
                            </div>
                            <span class="text-[10px] opacity-40 font-italic text-blue-400">Next: 1:00 PM</span>
                        </div>
                    </div>
                    <div class="absolute right-4 bottom-4 text-6xl opacity-5 group-hover:opacity-20 group-hover:scale-125 transition-all duration-700">📡</div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <a href="{{ route('manage.rooms') }}"class="rounded-3xl border flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] transition-all duration-300 hover:scale-[1.03] hover:bg-blue-600 hover:text-white group"
                       :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white border-slate-200 shadow-lg'">
                       <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg> Manage Rooms
                    </a>
                    <a href="{{ route('master-grid') }}" class="rounded-3xl border flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] transition-all duration-300 hover:scale-[1.03] hover:bg-emerald-600 hover:text-white group"
                       :class="darkMode ? 'bg-white/5 border-white/5' : 'bg-white border-slate-200 shadow-lg'">
                       <svg class="w-4 h-4 group-hover:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Master Grid
                    </a>
                </div>
            </div>

        </div>
    </main>
</div>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 3px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); border-radius: 10px; }
    .backdrop-blur-2xl { backdrop-filter: blur(40px); }
</style>