<div class="h-screen w-full flex flex-col p-6 gap-6 antialiased font-sans bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">
    <header class="grid grid-cols-12 gap-6 h-[12%] shrink-0">
        <div class="col-span-8 rounded-[2.5rem] border border-white/20 dark:border-slate-800 bg-white/70 dark:bg-slate-900/50 backdrop-blur-2xl px-10 flex items-center justify-between shadow-sm hover:shadow-[0_0_20px_rgba(99,102,241,0.2)] dark:hover:shadow-[0_0_25px_rgba(99,102,241,0.3)] hover:border-indigo-400/50 transition-all duration-500 group/header">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-indigo-500 dark:text-indigo-400 font-bold mb-1 transition-all group-hover/header:tracking-[0.5em]">
                    Institutional Management
                </p>
                <h1 class="text-2xl font-light text-slate-800 dark:text-slate-100">
                    Welcome, 
                    <span class="font-bold bg-gradient-to-r from-indigo-600 via-purple-500 to-rose-400 bg-clip-text text-transparent">
                        Registrar
                    </span>
                </h1>
            </div>
            <div class="p-4 bg-indigo-50 dark:bg-slate-800/50 rounded-2xl text-2xl transition-transform duration-500 group-hover/header:rotate-12 group-hover/header:scale-110">🗓️</div>
        </div>

        <div class="col-span-4 grid grid-cols-2 gap-4">
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 hover:border-indigo-400/50 hover:shadow-[0_0_15px_rgba(99,102,241,0.2)] transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">April 20, 2026</span>
                <span class="text-3xl font-black text-slate-800 dark:text-white mt-1">07:42</span>
            </div>
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 text-center hover:border-purple-400/50 hover:shadow-[0_0_15px_rgba(168,85,247,0.2)] transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Current Semester</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200 mt-1">1st 2026-27</span>
            </div>
        </div>
    </header>

    <main class="grid grid-cols-12 gap-6 flex-1 min-h-0">
        
        <div class="col-span-4 flex flex-col gap-6">
            <div class="flex-1 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 backdrop-blur-xl shadow-sm flex items-center justify-center border border-white/20 dark:border-slate-800 relative group hover:border-indigo-500/50 hover:shadow-[0_0_30px_rgba(99,102,241,0.15)] transition-all duration-700">
                <div class="relative w-48 h-48 transition-all duration-700 group-hover:scale-110 group-hover:drop-shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle stroke-width="2.5" stroke="currentColor" class="text-slate-100 dark:text-slate-800" fill="none" r="16" cx="18" cy="18"/>
                        <circle stroke-width="2.5" stroke="url(#paint0_linear)" stroke-dasharray="85, 100" stroke-linecap="round" fill="none" r="16" cx="18" cy="18"/>
                        <defs>
                            <linearGradient id="paint0_linear" x1="0" y1="0" x2="1" y2="1">
                                <stop stop-color="#6366f1" />
                                <stop offset="1" stop-color="#a855f7" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-slate-800 dark:text-white transition-all duration-500 group-hover:text-indigo-500">85<span class="text-xl font-light opacity-50">%</span></span>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Efficiency</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 h-32">
                <div class="rounded-[2.2rem] bg-indigo-600 dark:bg-indigo-500 p-6 flex flex-col justify-between shadow-lg shadow-indigo-200 dark:shadow-none hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(99,102,241,0.4)] transition-all duration-300 cursor-pointer">
                    <p class="text-[10px] font-bold text-indigo-100 uppercase">Faculty</p>
                    <span class="text-3xl font-black text-white">142</span>
                </div>
                <div class="rounded-[2.2rem] bg-slate-800 dark:bg-slate-800 p-6 flex flex-col justify-between shadow-lg hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(30,41,59,0.4)] transition-all duration-300 cursor-pointer border border-transparent hover:border-slate-600">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Rooms</p>
                    <span class="text-3xl font-black text-white">38</span>
                </div>
            </div>
        </div>

        <div class="col-span-8 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 shadow-sm p-8 flex flex-col overflow-hidden hover:shadow-[0_0_30px_rgba(0,0,0,0.05)] dark:hover:shadow-[0_0_40px_rgba(99,102,241,0.1)] transition-all duration-700">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-2.5 w-2.5 rounded-full bg-rose-500 animate-pulse shadow-[0_0_10px_#f43f5e]"></div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Conflict Management</h3>
                </div>
                <div class="flex gap-2">
                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-500/10 px-4 py-1.5 rounded-full border border-rose-100 dark:border-rose-500/20">2 Urgent Alerts</span>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-4 py-1.5 rounded-full border border-transparent hover:border-indigo-500/30 transition-all cursor-default">12 Total Requests</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-indigo-500/20 scrollbar-track-transparent">
                <div class="flex items-center gap-5 p-5 rounded-3xl bg-white dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50 hover:border-indigo-400 dark:hover:border-indigo-400 hover:shadow-[0_5px_15px_rgba(99,102,241,0.1)] dark:hover:shadow-[0_5px_20px_rgba(99,102,241,0.2)] transition-all duration-300 group cursor-pointer">
                    <div class="h-12 w-12 shrink-0 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm transition-all group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white group-hover:shadow-[0_0_15px_rgba(99,102,241,0.5)]">IT</div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Lab Overlap: CS-202 vs IT-101</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Requested by Dr. Aris Thorne • 2m ago</p>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                        <button class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-[10px] font-bold uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all active:scale-95">Approve</button>
                        <button class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wider hover:bg-rose-500 hover:text-white transition-all active:scale-95">Decline</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="h-[20%] rounded-[2.5rem] bg-white dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 p-8 shadow-sm flex flex-col hover:border-indigo-500/30 transition-all duration-500">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-indigo-500 rounded-full shadow-[0_0_8px_#6366f1]"></div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-800 dark:text-slate-200">Recent Activity Logs</h3>
            </div>
            <button class="text-[10px] font-bold bg-slate-900 dark:bg-indigo-600 text-white px-6 py-2.5 rounded-2xl hover:shadow-[0_0_15px_rgba(99,102,241,0.5)] transition-all active:scale-95 flex items-center gap-2">
                <span>Export PDF Report</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="text-slate-400 uppercase text-[10px] tracking-tighter">
                    <tr class="border-b border-slate-50 dark:border-slate-800">
                        <th class="text-left py-2 font-medium">Event Description</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-right py-2 font-medium">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <tr class="group cursor-default">
                        <td class="py-4 text-slate-700 dark:text-slate-300 font-medium group-hover:text-indigo-500 transition-colors">Section Conflict Resolved for Room 302</td>
                        <td><span class="px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[9px] font-bold border border-transparent group-hover:border-emerald-500/30 transition-all">SUCCESS</span></td>
                        <td class="text-right font-mono text-slate-400 group-hover:text-indigo-500 group-hover:shadow-[0_0_10px_rgba(99,102,241,0.1)] transition-all">22:04:12</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </footer>

</div>