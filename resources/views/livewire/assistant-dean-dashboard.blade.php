<div class="h-screen w-full flex flex-col p-6 gap-6 antialiased font-sans bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">
    <header class="grid grid-cols-12 gap-6 h-[12%] shrink-0">
        <div class="col-span-8 rounded-[2.5rem] border border-white/20 dark:border-slate-800 bg-white/70 dark:bg-slate-900/50 backdrop-blur-2xl px-10 flex items-center justify-between shadow-sm hover:shadow-[0_0_20px_rgba(244,63,94,0.2)] dark:hover:shadow-[0_0_25px_rgba(244,63,94,0.3)] hover:border-rose-400/50 transition-all duration-500 group/header">
            <div class="group/header">
                <div class="flex items-center gap-3 mb-1">
                    <p class="text-[10px] uppercase tracking-[0.4em] text-rose-500 dark:text-rose-400 font-bold transition-all group-hover/header:tracking-[0.5em]">
                        Academic Oversight
                    </p>
                    {{-- Global Access Badge --}}
                    <span class="px-2 py-0.5 bg-rose-500/10 border border-rose-500/20 text-rose-500 text-[8px] font-black rounded-md uppercase tracking-widest">
                        Academy-Wide Access
                    </span>
                </div>
                <h1 class="text-2xl font-light text-slate-800 dark:text-slate-100">
                    Welcome, 
                    <span class="font-bold bg-gradient-to-r from-rose-600 via-orange-500 to-amber-400 bg-clip-text text-transparent">
                        Associate Dean
                    </span>
                </h1>
            </div>
            <div class="p-4 bg-rose-50 dark:bg-slate-800/50 rounded-2xl text-2xl transition-transform duration-500 group-hover/header:-rotate-12 group-hover/header:scale-110">🎓</div>
        </div>

        <div class="col-span-4 grid grid-cols-2 gap-4">
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 hover:border-rose-400/50 transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ date('F d, Y') }}</span>
                <span class="text-3xl font-black text-slate-800 dark:text-white mt-1">{{ date('H:i') }}</span>
            </div>
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 text-center hover:border-amber-400/50 transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Academic Year</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200 mt-1">2026 - 2027</span>
            </div>
        </div>
    </header>

    <main class="grid grid-cols-12 gap-6 flex-1 min-h-0">
        
        <div class="col-span-4 flex flex-col gap-6">
            <div class="flex-1 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 backdrop-blur-xl shadow-sm flex items-center justify-center border border-white/20 dark:border-slate-800 relative group hover:border-rose-500/50 transition-all duration-700">
                <div class="relative w-48 h-48 transition-all duration-700 group-hover:rotate-12">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle stroke-width="2.5" stroke="currentColor" class="text-slate-100 dark:text-slate-800" fill="none" r="16" cx="18" cy="18"/>
                        <circle stroke-width="2.5" stroke="url(#dean_grad)" stroke-dasharray="78, 100" stroke-linecap="round" fill="none" r="16" cx="18" cy="18"/>
                        <defs>
                            <linearGradient id="dean_grad" x1="0" y1="0" x2="1" y2="1">
                                <stop stop-color="#f43f5e" />
                                <stop offset="1" stop-color="#fb923c" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-slate-800 dark:text-white group-hover:text-rose-500 transition-colors">78<span class="text-xl font-light opacity-50">%</span></span>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Schedules Set</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 h-48">
                <div class="rounded-[2.2rem] bg-rose-600 dark:bg-rose-500 p-6 flex flex-col justify-between shadow-lg shadow-rose-200 dark:shadow-none hover:scale-[1.02] transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-bold text-rose-100 uppercase tracking-widest">Minor Subjects</p>
                        <span class="text-4xl font-black text-white leading-none mt-2 block">{{ $minorSubjectsCount ?? 24 }}</span>
                    </div>
                    <div class="h-1 w-12 bg-white/30 rounded-full"></div>
                </div>

                <div class="grid grid-rows-2 gap-4">
                    <div class="rounded-[1.8rem] bg-slate-800 p-4 flex flex-col justify-center border border-white/5">
                        <p class="text-[8px] font-bold text-slate-400 uppercase">Major Subjects</p>
                        <span class="text-xl font-black text-white">42</span>
                    </div>
                    <div class="rounded-[1.8rem] bg-white dark:bg-slate-900 border border-white/20 dark:border-slate-800 p-4 flex flex-col justify-center shadow-sm">
                        <p class="text-[8px] font-bold text-slate-400 uppercase">Faculty Assigned</p>
                        <span class="text-xl font-black text-slate-800 dark:text-white">142</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-8 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 shadow-sm p-8 flex flex-col overflow-hidden hover:shadow-[0_0_30px_rgba(0,0,0,0.05)] transition-all duration-700">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-2.5 w-2.5 rounded-full bg-orange-500 animate-pulse shadow-[0_0_10px_#f97316]"></div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Loading & Curriculum Approval</h3>
                </div>
                <div class="flex gap-2">
                    <span class="text-[10px] font-bold text-orange-600 bg-orange-50 dark:bg-orange-500/10 px-4 py-1.5 rounded-full border border-orange-100 dark:border-orange-500/20">3 Pending Review</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-rose-500/20 scrollbar-track-transparent">
                <div class="flex items-center gap-5 p-5 rounded-3xl bg-white dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50 hover:border-rose-400 transition-all duration-300 group cursor-pointer">
                    <div class="h-12 w-12 shrink-0 rounded-2xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-400 font-bold text-sm group-hover:bg-rose-600 group-hover:text-white transition-all">GEN</div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-rose-600 transition-colors">NSTP-1 Minor Subject Allocation</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Assigned to: Prof. Dela Cruz • Gym-B</p>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                        <button class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-[10px] font-bold uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all active:scale-95">Verify</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="h-[20%] rounded-[2.5rem] bg-white dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 p-8 shadow-sm flex flex-col hover:border-rose-500/30 transition-all duration-500">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-rose-500 rounded-full shadow-[0_0_8px_#f43f5e]"></div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-800 dark:text-slate-200">Recent Curriculum Logs</h3>
            </div>
            <button class="text-[10px] font-bold bg-slate-900 dark:bg-rose-600 text-white px-6 py-2.5 rounded-2xl hover:shadow-[0_0_15px_rgba(244,63,94,0.4)] transition-all">
                Generate Load Report
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="text-slate-400 uppercase text-[10px] tracking-tighter">
                    <tr class="border-b border-slate-50 dark:border-slate-800">
                        <th class="text-left py-2 font-medium">Subject Code</th>
                        <th class="text-left py-2 font-medium">Type</th>
                        <th class="text-left py-2 font-medium">Assigned Faculty</th>
                        <th class="text-right py-2 font-medium">Schedule Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <tr class="group cursor-default">
                        <td class="py-4 text-slate-700 dark:text-slate-300 font-bold">GEC-102</td>
                        <td class="py-4"><span class="px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-[8px] font-black uppercase">Minor</span></td>
                        <td class="py-4 text-slate-500">Prof. Jane Alfeche</td>
                        <td class="text-right font-mono text-emerald-500 font-bold group-hover:animate-pulse">CONFIRMED</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </footer>
</div>