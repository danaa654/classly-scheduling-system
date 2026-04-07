<div class="flex h-screen bg-[#F8FAFC]" x-data="{ notificationsOpen: false }">
    <x-sidebar class="bg-white/40 backdrop-blur-md border-r border-slate-200" />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white/70 backdrop-blur-lg border-b border-slate-200 flex items-center justify-between px-12 shrink-0 z-30">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">
                    <span class="text-blue-600">{{ $department }}</span> Dean's Portal
                </h2>
                <p class="text-sm text-slate-400 font-medium italic">Departmental Academic Management</p>
            </div>
            
            <div class="flex items-center space-x-8">
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" class="relative p-2 text-slate-400 hover:text-blue-600 transition-colors">
                        <span class="absolute top-2 right-2 w-2 h-2 bg-orange-500 rounded-full border-2 border-white"></span>
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v1m6 0H9"></path></svg>
                    </button>
                </div>

                <div class="flex items-center space-x-4 border-l border-slate-200 pl-8">
                    <div class="text-right">
                        <p class="text-slate-900 font-black text-sm">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Department Head</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black shadow-lg shadow-blue-600/20">
                        {{ auth()->user()->initials() }}
                    </div>
                </div>
            </div>
        </header>

        <div class="p-12 overflow-y-auto space-y-10 custom-scrollbar">
            
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter mb-2">Welcome Back, Dean!</h1>
                    <p class="text-slate-500 max-w-lg font-medium">Currently overseeing <span class="font-bold text-slate-800">{{ $totalFaculty }} Instructors</span> and <span class="font-bold text-slate-800">{{ $totalSubjects }} Subjects</span> in the {{ $department }} Department.</p>
                </div>
                <div class="text-9xl opacity-5 absolute right-10 -bottom-5 rotate-12 select-none">🎓</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-lg relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-8xl text-slate-50 group-hover:scale-110 transition-transform">👨‍🏫</div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 relative z-10">Faculty Size</p>
                    <p class="text-6xl font-black text-blue-600 tracking-tighter relative z-10">{{ $totalFaculty }}</p>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-lg relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-8xl text-slate-50 group-hover:scale-110 transition-transform">📚</div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 relative z-10">Department Subjects</p>
                    <p class="text-6xl font-black text-indigo-600 tracking-tighter relative z-10">{{ $totalSubjects }}</p>
                </div>

                <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl shadow-slate-900/30 text-white">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em]">Academic Date</p>
                        <span class="text-[10px] font-bold text-blue-400 underline">{{ now()->format('M Y') }}</span>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] mb-2 opacity-40">
                        <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                        @for($i = 1; $i <= $daysInMonth; $i++)
                            <span class="py-1 {{ $i == $todayDay ? 'bg-blue-600 rounded-lg font-bold' : 'opacity-60' }}">{{ $i }}</span>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl flex flex-col justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-4">Faculty Loading</h3>
                        <p class="text-sm text-slate-500 mb-8">Assign instructors to specific subjects and finalize the departmental schedule for the upcoming term.</p>
                    </div>
                    <a href="{{ route('faculty-loading') }}" class="w-full py-5 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] transition-all shadow-lg shadow-blue-600/20">
                        Manage Faculty Workload
                    </a>
                </div>

                <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-6">Quick Links</h3>
                    <div class="space-y-4">
                        <a href="{{ route('master-grid') }}" class="flex items-center p-4 bg-slate-50 border border-slate-100 rounded-2xl hover:bg-slate-100 transition-colors group">
                            <span class="text-xl mr-4">📅</span>
                            <span class="font-bold text-slate-700 text-sm">View Master Schedule</span>
                        </a>
                        <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">System Health</p>
                            <p class="text-sm text-emerald-800 font-bold">All classroom conflicts resolved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>