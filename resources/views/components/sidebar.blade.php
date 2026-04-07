<div x-data="{ sidebarOpen: true }" class="relative flex h-screen overflow-hidden">
    
    <button 
        @click="sidebarOpen = !sidebarOpen" 
        class="fixed top-6 z-50 p-2 bg-[#0f172a] text-blue-400 rounded-full border border-slate-700 shadow-xl transition-all duration-300 hover:scale-110 active:scale-95 hover:bg-slate-800"
        :class="sidebarOpen ? 'left-[275px]' : 'left-6'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300" :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    <aside 
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="w-72 bg-[#0f172a] text-white shadow-2xl flex flex-col h-screen shrink-0 border-r border-slate-800 z-40"
    >
        <div class="p-8">
            <h1 class="text-3xl font-extrabold tracking-tighter text-blue-400">Classly<span class="text-white">.</span></h1>
            <p class="text-[10px] text-slate-400 mt-1 uppercase font-black tracking-widest">Academy OS</p>
        </div>

        <nav class="flex-1 px-4 space-y-1 overflow-y-auto custom-scrollbar">
            
            <a href="{{ route('dashboard') }}" 
                class="flex items-center px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
                <span class="mr-3">📊</span> Dashboard
            </a>

            @if(auth()->user()->role === 'admin')
            <a href="/manage-users" class="flex items-center px-4 py-3 {{ request()->is('manage-users*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
                <span class="mr-3">👥</span> Manage Users
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'dean', 'oic']))
            <a href="/manage-rooms" class="flex items-center px-4 py-3 {{ request()->is('manage-rooms*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
                <span class="mr-3">🏫</span> Manage Rooms
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
            <a href="/faculty" class="flex items-center px-4 py-3 {{ request()->is('faculty*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
                <span class="mr-3">👨‍🏫</span> Faculty List
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'dean', 'oic']))
            <a href="{{ route('subjects') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('subjects*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
                <span class="mr-3">📚</span> Subjects
            </a>
            @endif

            <div class="pt-6 pb-2 px-4">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Scheduling Core</p>
            </div>

            <div class="space-y-1 ml-4 border-l border-slate-800/50">
                <a href="{{ route('master-grid') }}" class="flex items-center px-4 py-3 {{ request()->is('master-grid*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }} transition-all text-sm group">
                    <span class="mr-3 text-sm group-hover:rotate-12 transition-transform">📅</span> Master Grid
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                <a href="#" class="flex items-center px-4 py-3 text-slate-400 hover:text-white transition-all text-sm group">
                    <span class="mr-3 text-sm group-hover:translate-x-1 transition-transform">📂</span> Master Data
                </a>
                @endif
            </div>
            
            @if(in_array(auth()->user()->role, ['dean', 'oic']))
            <div class="pt-6 pb-2 px-4">
                <p class="text-[10px] font-black text-blue-500/70 uppercase tracking-[0.2em]">Departmental ({{ auth()->user()->department }})</p>
            </div>
            <a href="/faculty-load" class="flex items-center px-4 py-3 {{ request()->is('faculty-load*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold border border-transparent {{ request()->is('faculty-load*') ? 'border-blue-500/30' : '' }}">
                <span class="mr-3">📋</span> Faculty Loading
            </a>
            @endif

        </nav>

        <div class="p-6 border-t border-slate-800 bg-[#020617]">
            <div class="flex items-center mb-4 px-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-black mr-3 shadow-lg border border-white/10 uppercase">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span class="text-[9px] px-1.5 py-0.5 bg-slate-800 text-slate-400 rounded-md uppercase font-bold tracking-tighter">{{ auth()->user()->role }}</span>
                        @if(auth()->user()->department)
                            <span class="text-[9px] px-1.5 py-0.5 bg-blue-500/10 text-blue-400 rounded-md uppercase font-bold tracking-tighter">{{ auth()->user()->department }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center px-4 py-2.5 text-red-400 hover:text-white hover:bg-red-500 rounded-xl transition-all text-[11px] font-black uppercase tracking-widest border border-red-500/20 group">
                    <span class="mr-2 group-hover:scale-110 transition-transform">🔒</span> Log Out
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 overflow-auto bg-[#F8FAFC]">
        {{ $slot ?? '' }}
    </div>
</div>