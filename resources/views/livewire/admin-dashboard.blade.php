<div class="h-screen w-full flex flex-col p-6 gap-6 antialiased font-sans bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500">
    <header class="grid grid-cols-12 gap-6 h-[12%] shrink-0">
        <div class="col-span-8 rounded-[2.5rem] border border-white/20 dark:border-slate-800 bg-white/70 dark:bg-slate-900/50 backdrop-blur-2xl px-10 flex items-center justify-between shadow-sm hover:shadow-[0_0_20px_rgba(59,130,246,0.2)] dark:hover:shadow-[0_0_25px_rgba(59,130,246,0.3)] hover:border-blue-400/50 transition-all duration-500 group/header">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-blue-500 dark:text-blue-400 font-bold mb-1 transition-all group-hover/header:tracking-[0.5em]">
                    System Root Access
                </p>
                <h1 class="text-2xl font-light text-slate-800 dark:text-slate-100">
                    Welcome, 
                    <span class="font-bold bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-400 bg-clip-text text-transparent">
                        Administrator
                    </span>
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <p class="text-[9px] font-black uppercase text-emerald-500 animate-pulse">System Online</p>
                    <p class="text-[10px] text-slate-400 font-bold">Node: PAP-CEBU-V3</p>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-slate-800/50 rounded-2xl text-2xl transition-transform duration-500 group-hover/header:rotate-12 group-hover/header:scale-110">🛡️</div>
            </div>
        </div>

        <div class="col-span-4 grid grid-cols-2 gap-4">
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 hover:border-blue-400/50 hover:shadow-[0_0_15px_rgba(59,130,246,0.2)] transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ date('F d, Y') }}</span>
                <span class="text-3xl font-black text-slate-800 dark:text-white mt-1">{{ date('H:i') }}</span>
            </div>
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800 text-center hover:border-purple-400/50 hover:shadow-[0_0_15px_rgba(168,85,247,0.2)] transition-all duration-500">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Server Status</span>
                <span class="text-sm font-bold text-emerald-500 mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Optimal
                </span>
            </div>
        </div>
    </header>

    <main class="grid grid-cols-12 gap-6 flex-1 min-h-0">
        
        <div class="col-span-4 flex flex-col gap-6">
            <div class="flex-1 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 backdrop-blur-xl shadow-sm flex items-center justify-center border border-white/20 dark:border-slate-800 relative group hover:border-blue-500/50 transition-all duration-700">
                <div class="relative w-48 h-48 transition-all duration-700 group-hover:scale-110">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle stroke-width="2.5" stroke="currentColor" class="text-slate-100 dark:text-slate-800" fill="none" r="16" cx="18" cy="18"/>
                        <circle stroke-width="2.5" stroke="url(#admin_grad)" stroke-dasharray="70, 100" stroke-linecap="round" fill="none" r="16" cx="18" cy="18"/>
                        <defs>
                            <linearGradient id="admin_grad" x1="0" y1="0" x2="1" y2="1">
                                <stop stop-color="#3b82f6" />
                                <stop offset="1" stop-color="#8b5cf6" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-slate-800 dark:text-white group-hover:text-blue-500 transition-colors">92<span class="text-xl font-light opacity-50">%</span></span>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">CPU Health</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 h-48">
                <a href="{{ route('manage-users') }}" class="rounded-[2.2rem] bg-blue-600 dark:bg-blue-600 p-6 flex flex-col justify-between shadow-lg shadow-blue-200 dark:shadow-none hover:scale-[1.02] hover:shadow-[0_0_20px_rgba(59,130,246,0.4)] transition-all duration-300 group">
                    <div>
                        <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Total Users</p>
                        <span class="text-4xl font-black text-white leading-none mt-2 block">{{ $totalUsersCount ?? 0 }}</span>
                    </div>
                    <p class="text-[9px] text-white/60 font-bold uppercase group-hover:text-white transition-colors">Manage Directory →</p>
                </a>
                <div class="grid grid-rows-2 gap-4">
                    <div class="rounded-[1.8rem] bg-slate-800 p-4 flex flex-col justify-center border border-white/5">
                        <p class="text-[8px] font-bold text-slate-400 uppercase">Faculty</p>
                        <span class="text-xl font-black text-white">142</span>
                    </div>
                    <div class="rounded-[1.8rem] bg-white dark:bg-slate-900 border border-white/20 dark:border-slate-800 p-4 flex flex-col justify-center shadow-sm">
                        <p class="text-[8px] font-bold text-slate-400 uppercase">Rooms</p>
                        <span class="text-xl font-black text-slate-800 dark:text-white">38</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-8 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 shadow-sm p-8 flex flex-col overflow-hidden hover:shadow-[0_0_30px_rgba(0,0,0,0.05)] transition-all duration-700">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-2.5 w-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_#3b82f6]"></div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Security & User Requests</h3>
                </div>
                <div class="flex gap-2">
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 dark:bg-blue-500/10 px-4 py-1.5 rounded-full border border-blue-100 dark:border-blue-500/20">4 Pending Actions</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin scrollbar-thumb-blue-500/20 scrollbar-track-transparent">
                <div class="flex items-center gap-5 p-5 rounded-3xl bg-white dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50 hover:border-blue-400 transition-all duration-300 group cursor-pointer">
                    <div class="h-12 w-12 shrink-0 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm transition-all group-hover:bg-blue-600 group-hover:text-white shadow-sm">USR</div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 transition-colors">New Faculty Registration: Prof. Ramos</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Department: College Departments • 5m ago</p>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                        <button class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-[10px] font-bold uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all active:scale-95">Verify</button>
                        <button class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wider hover:bg-rose-500 hover:text-white transition-all active:scale-95">Deny</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="h-[20%] rounded-[2.5rem] bg-white dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 p-8 shadow-sm flex flex-col hover:border-blue-500/30 transition-all duration-500">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-blue-500 rounded-full shadow-[0_0_8px_#3b82f6]"></div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-800 dark:text-slate-200">Global System Audit</h3>
            </div>
            <div class="flex gap-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest self-center mr-4">Auto-sync: Active</span>
                <button class="text-[10px] font-bold bg-slate-900 dark:bg-blue-600 text-white px-6 py-2.5 rounded-2xl hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all active:scale-95">
                    View All Logs
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="text-slate-400 uppercase text-[10px] tracking-tighter">
                    <tr class="border-b border-slate-50 dark:border-slate-800">
                        <th class="text-left py-2 font-medium">Task / Process</th>
                        <th class="text-left py-2 font-medium">Admin</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-right py-2 font-medium">Pulse</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <tr class="group cursor-default">
                        <td class="py-4 text-slate-700 dark:text-slate-300 font-medium group-hover:text-blue-500 transition-colors">Database Backup and Migration</td>
                        <td class="py-4 text-slate-500 uppercase font-black text-[9px]">Root-Sys</td>
                        <td><span class="px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[9px] font-bold border border-emerald-100 dark:border-emerald-500/20">COMPLETE</span></td>
                        <td class="text-right font-mono text-slate-400 group-hover:text-blue-500 transition-all">0.12ms</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </footer>
</div>