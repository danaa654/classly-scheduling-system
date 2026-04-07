<div class="flex h-screen bg-[#F8FAFC]" x-data="{ notificationsOpen: false }">
    <x-sidebar class="bg-white/40 backdrop-blur-md border-r border-slate-200" />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white/70 backdrop-blur-lg border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0 z-30">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Admin <span class="text-blue-600">Command Center</span></h2>
                <p class="text-sm text-slate-400 font-medium italic">Global System Control • Classly OS</p>
            </div>
            
            <div class="flex items-center space-x-8">
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" class="relative p-2 text-slate-400 hover:text-blue-600 transition-colors focus:outline-none">
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v1m6 0H9"></path></svg>
                    </button>

                    <div x-show="notificationsOpen" @click.away="notificationsOpen = false" x-transition class="absolute right-0 mt-4 w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl p-4 z-50">
                        <h4 class="font-black text-xs uppercase text-slate-400 mb-3 tracking-widest">Recent Alerts</h4>
                        <div class="space-y-3">
                            <div class="p-3 bg-blue-50 rounded-xl text-xs border border-blue-100">
                                <p class="font-bold text-blue-800">New Faculty Added</p>
                                <p class="text-blue-600/70">Dr. Smith joined CCS department.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-4 border-l border-slate-200 pl-8">
                    <div class="text-right">
                        <p class="text-slate-900 font-black text-sm">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">System Root</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 flex items-center justify-center text-white font-black shadow-lg shadow-slate-900/20">
                        {{ auth()->user()->initials() }}
                    </div>
                </div>
            </div>
        </header>

        <div class="p-12 overflow-y-auto space-y-10 custom-scrollbar">
            
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-5xl font-black text-slate-900 tracking-tighter mb-3">Hi, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
                    <p class="text-lg text-slate-500 max-w-lg">Everything is running smoothly. All system modules are currently <span class="text-emerald-500 font-bold italic text-sm px-2 py-1 bg-emerald-50 rounded-lg">● Synchronized</span></p>
                </div>
                <div class="text-[10rem] opacity-10 absolute -right-4 -bottom-10 rotate-12 select-none">⚡</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-lg group">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Campus Rooms</p>
                    <p class="text-5xl font-black text-slate-800 tracking-tighter group-hover:text-blue-600 transition-colors">{{ $totalRooms }}</p>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-lg group">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Global Faculty</p>
                    <p class="text-5xl font-black text-slate-800 tracking-tighter group-hover:text-emerald-500 transition-colors">{{ $totalFaculty }}</p>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-lg group">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Active Users</p>
                    <p class="text-5xl font-black text-slate-800 tracking-tighter group-hover:text-purple-500 transition-colors">{{ $activeUsersCount }}</p>
                </div>
                <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl shadow-slate-900/30">
                    <p class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em] mb-2">System Load</p>
                    <p class="text-5xl font-black text-white tracking-tighter italic">STABLE</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-4 bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-black text-slate-800 text-sm uppercase tracking-widest">{{ $currentMonth }}</h3>
                        <div class="flex space-x-1">
                            <button class="p-1 hover:bg-slate-100 rounded-md">◀</button>
                            <button class="p-1 hover:bg-slate-100 rounded-md">▶</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-2 text-center mb-4">
                        @foreach(['S','M','T','W','T','F','S'] as $day)
                            <span class="text-[10px] font-black text-slate-300">{{ $day }}</span>
                        @endforeach
                        
                        @for($i = 1; $i <= $daysInMonth; $i++)
                            <div class="py-2 text-xs font-bold rounded-xl transition-all 
                                {{ $i == $todayDay ? 'bg-blue-600 text-white shadow-lg scale-110' : 'text-slate-500 hover:bg-slate-50' }}">
                                {{ $i }}
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="lg:col-span-8 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl">
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-8">Administrative Shortcuts</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <a href="{{ route('manage-users') }}" class="group p-6 bg-slate-50 border border-slate-100 rounded-3xl hover:bg-blue-600 transition-all duration-300">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-sm">🛡️</div>
                            <p class="font-black text-slate-800 group-hover:text-white uppercase text-xs tracking-widest">Manage Accounts</p>
                            <p class="text-[10px] text-slate-400 group-hover:text-blue-100 mt-1">Audit permissions & roles</p>
                        </a>
                        <a href="{{ route('manage-rooms') }}" class="group p-6 bg-slate-50 border border-slate-100 rounded-3xl hover:bg-emerald-600 transition-all duration-300">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-sm">🏫</div>
                            <p class="font-black text-slate-800 group-hover:text-white uppercase text-xs tracking-widest">Campus Setup</p>
                            <p class="text-[10px] text-slate-400 group-hover:text-emerald-100 mt-1">Manage building assets</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>