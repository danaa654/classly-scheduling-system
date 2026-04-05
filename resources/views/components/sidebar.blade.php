<aside class="w-72 bg-[#0f172a] text-white shadow-2xl flex flex-col h-screen shrink-0">
    <div class="p-8">
        <h1 class="text-3xl font-extrabold tracking-tighter text-blue-400">Classly<span class="text-white">.</span></h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase font-black tracking-widest">Academy OS</p>
    </div>

    <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
        
        <a href="/dashboard" class="flex items-center px-4 py-3 {{ request()->is('dashboard*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800' }} rounded-xl transition-all font-bold">
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
        <a href="{{ route('subjects') }}" class="flex items-center space-x-4 px-6 py-4 rounded-2xl transition-all {{ request()->routeIs('subjects') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-50' }}">
            <span class="text-xl">📚</span>
            <span class="font-black uppercase tracking-widest text-[11px]">Subjects</span>
        </a>
        @endif

        <div class="pt-6 pb-2 px-4">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Scheduling Core</p>
        </div>

        <div class="space-y-1 ml-4 border-l border-slate-800">
            <a href="/scheduler" class="flex items-center px-4 py-3 {{ request()->is('scheduler*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }} transition-all text-sm">
                <span class="mr-3 text-sm">📅</span> Master Grid
            </a>

            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
            <a href="#" class="flex items-center px-4 py-3 text-slate-400 hover:text-white transition-all text-sm">
                <span class="mr-3 text-sm">📂</span> Master Data
            </a>
            @endif
        </div>
        
        @if(in_array(auth()->user()->role, ['dean', 'oic', 'dean_assistant']))
        <div class="pt-6 pb-2 px-4">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Departmental</p>
        </div>
        <a href="/faculty-load" class="flex items-center px-4 py-3 text-slate-400 hover:bg-slate-800 rounded-xl transition-all font-bold">
            <span class="mr-3">👨‍🏫</span> Faculty Loading
        </a>
        @endif

    </nav>

    <div class="p-6 border-t border-slate-800 bg-[#020617]">
        <div class="flex items-center mb-4 px-2">
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-[10px] font-bold mr-3">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="overflow-hidden text-ellipsis whitespace-nowrap">
                <p class="text-xs font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-slate-500 uppercase">{{ auth()->user()->role }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-all text-xs font-bold border border-red-900/30">
                Log Out
            </button>
        </form>
    </div>
</aside>