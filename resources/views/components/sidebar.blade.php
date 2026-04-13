<div x-data="{ sidebarOpen: true }" class="relative flex h-screen overflow-hidden">
    
    <button 
        @click="sidebarOpen = !sidebarOpen" 
        class="fixed top-6 z-50 p-2 bg-[#0f172a] text-blue-400 rounded-full border border-slate-700 shadow-xl transition-all duration-300 hover:scale-110 active:scale-95 hover:bg-slate-800 focus:outline-none"
        :class="sidebarOpen ? 'left-[268px]' : 'left-[60px]'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300" :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    <aside 
        class="bg-[#0f172a] text-white shadow-2xl flex flex-col h-screen shrink-0 border-r border-slate-800 z-40 transition-all duration-300 ease-in-out"
        :class="sidebarOpen ? 'w-72' : 'w-20'"
    >
        <div class="p-6 h-24 flex flex-col justify-center overflow-hidden">
            <h1 class="text-2xl font-extrabold tracking-tighter text-blue-400 whitespace-nowrap">
                C<span x-show="sidebarOpen" x-transition:enter="transition delay-100" x-transition:enter-start="opacity-0">lassly</span><span class="text-white">.</span>
            </h1>
            <p x-show="sidebarOpen" x-transition.opacity class="text-[9px] text-slate-400 mt-1 uppercase font-black tracking-widest whitespace-nowrap">
                Academy OS
            </p>
        </div>

        <nav class="flex-1 px-3 space-y-2 overflow-y-auto custom-scrollbar overflow-x-hidden">
            
            <a href="{{ route('dashboard') }}" 
                class="flex items-center p-3 {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group"
                title="Dashboard">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">📊</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Dashboard</span>
            </a>

            @if(auth()->user()->role === 'admin')
            <a href="/manage-users" class="flex items-center p-3 {{ request()->is('manage-users*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group" title="Users">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">👥</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Manage Users</span>
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'dean', 'oic', 'assistant_dean']))
            <a href="/manage-rooms" class="flex items-center p-3 {{ request()->is('manage-rooms*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group" title="Rooms">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">🏫</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Manage Rooms</span>
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'assistant_dean']))
            <a href="/faculty" class="flex items-center p-3 {{ request()->is('faculty*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group" title="Faculty">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">👨‍🏫</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Faculty List</span>
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'dean', 'assistant_dean']))
            <a href="{{ route('subjects') }}" class="flex items-center p-3 {{ request()->routeIs('subjects*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group" title="Subjects">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">📚</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Subjects</span>
            </a>
            @endif

            <div class="pt-6 pb-2 px-3" x-show="sidebarOpen" x-transition.opacity>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] whitespace-nowrap">Scheduling Core</p>
            </div>

            <div class="space-y-1" :class="sidebarOpen ? 'ml-3 border-l border-slate-800/50' : ''">
                <a href="{{ route('master-grid') }}" class="flex items-center p-3 {{ request()->is('master-grid*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }} transition-all text-sm group" title="Master Grid">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3 ml-2' : 'w-full'">📅</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Master Grid</span>
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                <a href="#" class="flex items-center p-3 text-slate-400 hover:text-white transition-all text-sm group" title="Master Data">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3 ml-2' : 'w-full'">📂</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Master Data</span>
                </a>
                @endif
            </div>

            @if(in_array(auth()->user()->role, ['dean', 'oic']))
            <div class="pt-6 pb-2 px-3" x-show="sidebarOpen" x-transition.opacity>
                <p class="text-[10px] font-black text-blue-500/70 uppercase tracking-[0.2em] whitespace-nowrap">Departmental</p>
            </div>
            <a href="/faculty-load" class="flex items-center p-3 {{ request()->is('faculty-load*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold group" title="Loading">
                <span class="text-xl flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-3' : 'w-full'">📋</span>
                <span x-show="sidebarOpen" x-transition.opacity class="whitespace-nowrap">Faculty Loading</span>
            </a>
            @endif

        </nav>

        <div class="p-4 border-t border-slate-800 bg-[#020617]">
            <div class="flex items-center mb-4" :class="sidebarOpen ? 'px-2' : 'justify-center'">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-black shadow-lg border border-white/10 uppercase">
                    {{ auth()->user()->initials() }}
                </div>
                <div x-show="sidebarOpen" x-transition.opacity class="ml-3 overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span class="text-[9px] px-1.5 py-0.5 bg-slate-800 text-slate-400 rounded-md uppercase font-bold tracking-tighter">{{ auth()->user()->role }}</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center p-2.5 text-red-400 hover:text-white hover:bg-red-500 rounded-xl transition-all border border-red-500/20 group">
                    <span class="text-lg flex shrink-0" :class="sidebarOpen ? 'mr-2' : ''">🔒</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="text-[10px] font-black uppercase tracking-widest whitespace-nowrap">Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 overflow-auto bg-[#F8FAFC]">
        {{ $slot ?? '' }}
    </div>
</div>