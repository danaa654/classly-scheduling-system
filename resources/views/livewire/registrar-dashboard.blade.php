<div class="h-screen w-full flex flex-col antialiased font-sans overflow-hidden transition-colors duration-700 bg-slate-50 dark:bg-[#020617]">
    
    <div class="grid grid-cols-12 gap-5 h-[18%] shrink-0 p-6 pb-2">
        <div class="col-span-6 rounded-[2rem] relative overflow-hidden border border-white dark:border-white/5 bg-gradient-to-br from-white via-blue-50/30 to-white dark:from-slate-900 dark:to-slate-950 shadow-sm backdrop-blur-md flex items-center justify-between p-8">
            <div class="space-y-1">
                <p class="text-[10px] uppercase tracking-[0.2em] text-blue-500 font-bold">Registry Console</p>
                <h1 class="text-3xl font-light text-slate-900 dark:text-white">
                    Welcome, <span class="font-bold">Registrar</span>
                </h1>
            </div>
            <div class="text-6xl opacity-10 dark:text-blue-500">🏛️</div>
        </div>

        <div class="col-span-3 rounded-[2rem] border border-white dark:border-white/5 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl flex flex-col items-center justify-center shadow-sm">
            <div class="text-center space-y-0.5 z-10">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-orange-500">DATE</span>
                <div class="flex items-center justify-center gap-2">
                    <span class="text-4xl font-black text-slate-800 dark:text-white">{{ date('d') }}</span>
                    <span class="text-xs font-medium text-slate-400 uppercase leading-none">{{ date('M') }}<br>{{ date('Y') }}</span>
                </div>
            </div>
        </div>

        <div class="col-span-3 rounded-[2rem] border border-white dark:border-white/5 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl flex flex-col items-center justify-center shadow-sm">
            <p class="text-[9px] font-bold uppercase text-slate-400 tracking-widest">Semester SY:</p>
            <h4 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">2026-2027</h4>
            <span class="text-[10px] px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-500/20 text-blue-600 font-bold mt-1">Semester 1</span>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-5 h-[42%] shrink-0 px-6 py-2">
        <div class="col-span-3 flex flex-col gap-3">
            <div class="flex-1 rounded-3xl p-5 bg-white dark:bg-slate-900/40 border border-white/80 dark:border-white/5 shadow-sm flex flex-col justify-center">
                <p class="text-[9px] font-bold uppercase text-slate-400 tracking-widest mb-1">Total Faculty</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-800 dark:text-white">142</span>
                    <span class="text-xs text-blue-500 font-bold uppercase">Active</span>
                </div>
            </div>
            <div class="flex-1 rounded-3xl p-5 bg-white dark:bg-slate-900/40 border border-white/80 dark:border-white/5 shadow-sm flex flex-col justify-center">
                <p class="text-[9px] font-bold uppercase text-slate-400 tracking-widest mb-1">Managed Rooms</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-800 dark:text-white">{{ $totalRooms ?? 24 }}</span>
                    <span class="text-xs text-blue-500 font-bold uppercase">Units</span>
                </div>
            </div>
            <div class="flex-1 rounded-3xl p-5 bg-white dark:bg-slate-900/40 border border-white/80 dark:border-white/5 shadow-sm flex flex-col justify-center">
                <p class="text-[9px] font-bold uppercase text-slate-400 tracking-widest mb-1">Total Subjects</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-800 dark:text-white">200</span>
                    <span class="text-xs text-blue-500 font-bold uppercase">Courses</span>
                </div>
            </div>
        </div>

        <div class="col-span-4 rounded-[2.5rem] border border-white dark:border-white/5 bg-white dark:bg-slate-900 shadow-sm flex flex-col items-center justify-center p-6">
            <div class="relative flex items-center justify-center">
                <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 36 36">
                    <circle class="text-slate-100 dark:text-slate-800" stroke-width="3" stroke="currentColor" fill="none" r="16" cx="18" cy="18"/>
                    <circle class="text-blue-600 dark:text-blue-400 transition-all duration-1000" stroke-width="3" stroke-dasharray="85, 100" stroke-linecap="round" stroke="currentColor" fill="none" r="16" cx="18" cy="18"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center pt-2">
                    <span class="text-5xl font-black text-slate-900 dark:text-white leading-none">85%</span>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Scheduled</p>
                </div>
            </div>
        </div>

        <div class="col-span-5 rounded-[2.5rem] border border-white dark:border-white/5 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-6 shadow-sm overflow-hidden flex flex-col">
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-4 flex justify-between">
                Pending Requests
                <span class="text-blue-500">2 Active</span>
            </h3>
            <div class="space-y-3 overflow-y-auto custom-scrollbar flex-1 pr-1">
                @foreach(range(1, 2) as $i)
                <div class="flex items-center gap-4 p-4 rounded-2xl bg-white/60 dark:bg-slate-800/50 border border-white dark:border-white/5 shadow-sm">
                    <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs font-bold border-2 border-white dark:border-slate-700">IT</div>
                    <div class="flex-1">
                        <p class="text-xs font-bold dark:text-white">Lab Room Change: IT-101</p>
                        <p class="text-[9px] text-slate-400 uppercase">Dean of CCS</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all flex items-center justify-center text-xs">✓</button>
                        <button class="h-8 w-8 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center text-xs">✕</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex-1 px-6 pt-2 pb-6 overflow-hidden">
        <div class="h-full w-full rounded-[2.5rem] border border-white dark:border-white/5 bg-white/60 dark:bg-slate-900/40 backdrop-blur-xl p-6 shadow-sm flex flex-col overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Live Course Schedule</h3>
                <div class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-500/10">Today's Load</div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar pr-1">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead class="sticky top-0 z-10 text-[8px] uppercase font-bold text-slate-400 tracking-widest">
                        <tr>
                            <th class="px-4 py-3 bg-slate-100 dark:bg-slate-800">Subject</th>
                            <th class="px-4 py-3 bg-slate-100 dark:bg-slate-800">Section</th>
                            <th class="px-4 py-3 bg-slate-100 dark:bg-slate-800">Time</th>
                            <th class="px-4 py-3 text-right bg-slate-100 dark:bg-slate-800">Room</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-600 dark:text-slate-300">
                        @foreach(range(1, 4) as $i)
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">CS 101</td>
                            <td class="px-4 py-3">Section A</td>
                            <td class="px-4 py-3">08:00 AM</td>
                            <td class="px-4 py-3 text-right font-mono text-blue-600 dark:text-blue-400">R302</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Compact Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { 
        background: rgba(0,0,0,0.05); 
        border-radius: 10px; 
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }
</style>