<div class="h-screen w-full flex flex-col p-6 gap-6 antialiased font-sans bg-[#E8EDF2] dark:bg-[#020617] transition-colors duration-500"
     x-data="{ 
        notificationsOpen: false,
        activeTab: 'all'
     }">
    
    <header class="grid grid-cols-12 gap-6 h-[12%] shrink-0">
        <div class="col-span-8 rounded-[2.5rem] border border-white/20 dark:border-slate-800 bg-white/70 dark:bg-slate-900/50 backdrop-blur-2xl px-10 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-blue-600 dark:text-blue-400 font-bold mb-1">
                    {{ $department }} Dean's Portal
                </p>
                <h1 class="text-2xl font-light text-slate-800 dark:text-slate-100">
                    Control Center <span class="font-bold opacity-30 mx-2">/</span> <span class="font-bold">Overview</span>
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-10 w-[1px] bg-slate-200 dark:bg-slate-800 mx-2"></div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Current User</p>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ Auth::user()->name }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-4 grid grid-cols-2 gap-4">
            <div class="rounded-[2.2rem] bg-white dark:bg-slate-900/50 flex flex-col items-center justify-center shadow-sm border border-white/20 dark:border-slate-800">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ date('F d') }}</span>
                <span class="text-2xl font-black text-slate-800 dark:text-white">{{ date('H:i') }}</span>
            </div>
            <div class="rounded-[2.2rem] bg-blue-600 flex flex-col items-center justify-center shadow-lg shadow-blue-200 dark:shadow-none text-center border border-blue-400">
                <span class="text-[9px] font-bold text-blue-100 uppercase tracking-widest">Status</span>
                <span class="text-xs font-bold text-white mt-1 uppercase tracking-widest">Active</span>
            </div>
        </div>
    </header>

    <main class="grid grid-cols-12 gap-6 flex-1 min-h-0">
        
        <div class="col-span-4 flex flex-col gap-6">
            <div class="flex-1 rounded-[2.5rem] bg-white/70 dark:bg-slate-900/50 backdrop-blur-xl shadow-sm p-8 border border-white/20 dark:border-slate-800 flex flex-col items-center justify-center text-center">
                <h3 class="font-black uppercase tracking-widest text-[10px] mb-8 text-slate-400 self-start">Institutional Conflict</h3>
                
                <div class="relative flex items-center justify-center">
                    <svg class="w-48 h-48 transform -rotate-90">
                        <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                        <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="502.4" stroke-dashoffset="120" class="text-blue-500 drop-shadow-[0_0_8px_rgba(59,130,246,0.5)]" />
                    </svg>
                    <div class="absolute flex flex-col">
                        <span class="text-4xl font-black text-slate-800 dark:text-white">76%</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Stability</span>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4 w-full">
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                        <p class="text-[9px] font-bold text-slate-400 uppercase">Conflicts</p>
                        <p class="text-sm font-black text-red-500">12 Fixed</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                        <p class="text-[9px] font-bold text-slate-400 uppercase">Pending</p>
                        <p class="text-sm font-black text-blue-500">04 Critical</p>
                    </div>
                </div>
            </div>

            <div class="h-48 rounded-[2.2rem] bg-white/70 dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 p-6 flex flex-col justify-between">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Peak Conflict Hours</p>
                <div class="flex items-end gap-1.5 h-16">
                    @foreach([40, 70, 90, 30, 50, 80, 20] as $h)
                        <div class="flex-1 bg-blue-500/20 rounded-t-md hover:bg-blue-500 transition-all cursor-pointer" style="height: {{ $h }}%"></div>
                    @endforeach
                </div>
                <div class="flex justify-between text-[8px] font-bold text-slate-400 uppercase">
                    <span>08:00</span>
                    <span>17:00</span>
                </div>
            </div>
        </div>

        <div class="col-span-8 flex flex-col gap-6">
            
            <div class="flex-1 rounded-[2.5rem] bg-white dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 shadow-sm p-8 flex flex-col overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span> Approval Queue
                    </h3>
                </div>

                <div class="space-y-3 overflow-y-auto custom-scrollbar pr-2">
                    <div class="flex items-center justify-between p-5 rounded-[2rem] bg-[#F8FAFC] dark:bg-slate-800/40 border border-slate-100 dark:border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-white dark:bg-slate-700 shadow-sm flex items-center justify-center text-xl">📄</div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase">Room Modification: IT-105</p>
                                <p class="text-[10px] text-slate-400 font-medium italic">Submitted by Registrar • 2 mins ago</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="px-6 py-2 rounded-xl bg-blue-600 text-white text-[10px] font-black uppercase hover:bg-blue-700 transition-colors">Approve</button>
                            <button class="px-6 py-2 rounded-xl bg-white dark:bg-slate-700 text-slate-400 dark:text-slate-300 text-[10px] font-black uppercase hover:text-red-500 transition-colors">Reject</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 rounded-[2.5rem] bg-white dark:bg-slate-900/50 border border-white/20 dark:border-slate-800 shadow-sm p-8 flex flex-col overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 flex items-center gap-2">
                         My Requests Tracking
                    </h3>
                    <span class="text-[9px] font-bold text-slate-400 italic">Tracking status from Registrar Office</span>
                </div>

                <div class="space-y-3 overflow-y-auto custom-scrollbar pr-2">
                    <div class="flex items-center gap-4 p-4 rounded-3xl border-l-4 border-emerald-500 bg-emerald-50/30 dark:bg-emerald-500/5 transition-all">
                        <div class="h-10 w-10 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs">✓</div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Request Approved</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">The schedule update for CCS Faculty has been confirmed by the Registrar.</p>
                        </div>
                        <span class="text-[9px] font-bold text-slate-400">1h ago</span>
                    </div>

                    <div class="flex items-center gap-4 p-4 rounded-3xl border-l-4 border-red-500 bg-red-50/30 dark:bg-red-500/5 transition-all">
                        <div class="h-10 w-10 rounded-full bg-red-500 flex items-center justify-center text-white text-xs">✕</div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Request Rejected</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">"Conflict detected with Room 202 during Lab hours." — Registrar Office</p>
                        </div>
                        <span class="text-[9px] font-bold text-slate-400">3h ago</span>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>