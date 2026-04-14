<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Classly') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="font-sans antialiased text-slate-900 bg-[#f8fafc]">

    <div x-data="{ sidebarOpen: true }" class="relative flex h-screen overflow-hidden">
        
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            class="fixed top-5 z-50 p-1.5 bg-[#0f172a] text-blue-400 rounded-full border border-slate-700 shadow-xl transition-all duration-300 hover:scale-110 active:scale-95 hover:bg-slate-800 focus:outline-none"
            :class="sidebarOpen ? 'left-[240px]' : 'left-[52px]'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-300" :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <aside 
            class="bg-[#0f172a] text-white flex flex-col h-screen shrink-0 border-r border-slate-800 z-40 transition-all duration-300 ease-in-out"
            :class="sidebarOpen ? 'w-64' : 'w-16'"
        >
            <div class="px-5 h-16 flex items-center overflow-hidden shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0 shadow-lg">
                        <span class="text-base font-black text-white">C</span>
                    </div>
                    <div x-show="sidebarOpen" x-transition.opacity class="flex flex-col whitespace-nowrap">
                        <h1 class="text-base font-bold tracking-tighter text-white leading-none">Classly<span class="text-blue-500">.</span></h1>
                        <p class="text-[7px] text-slate-500 font-bold uppercase tracking-widest">Academy OS</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-2.5 space-y-1 overflow-y-auto pt-2 custom-scrollbar">
                
                <div x-show="sidebarOpen" class="px-2.5 pb-1">
                    <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">General</p>
                </div>

                <a href="{{ route('dashboard') }}" wire:navigate
                    class="flex items-center p-2 rounded-xl transition-all group {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">📊</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Dashboard</span>
                </a>

                @if(auth()->user()->role === 'admin')
                <a href="/manage-users" wire:navigate class="flex items-center p-2 rounded-xl transition-all group {{ request()->is('manage-users*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">👥</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Manage Users</span>
                </a>
                @endif

                <a href="/manage-rooms" wire:navigate class="flex items-center p-2 rounded-xl transition-all group {{ request()->is('manage-rooms*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">🏫</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Manage Rooms</span>
                </a>

                <a href="/faculty" wire:navigate class="relative flex items-center p-2 rounded-xl transition-all group {{ request()->is('faculty*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">👨‍🏫</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Faculty List</span>
                    
                    @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                        @php $pendingCount = \App\Models\Faculty::where('status', 'pending')->count(); @endphp
                        @if($pendingCount > 0)
                            <span class="absolute flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white border-2 border-[#0f172a] shadow-sm animate-pulse"
                                :class="sidebarOpen ? 'right-2' : 'top-0 right-1'">
                                {{ $pendingCount }}
                            </span>
                        @endif
                    @endif
                </a>

                <a href="{{ route('subjects') }}" wire:navigate class="flex items-center p-2 rounded-xl transition-all group {{ request()->routeIs('subjects*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">📚</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Subjects</span>
                </a>

                <div x-show="sidebarOpen" class="px-2.5 pb-1 pt-3">
                    <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">Scheduling</p>
                </div>

                <a href="{{ route('master-grid') }}" wire:navigate class="flex items-center p-2 rounded-xl transition-all group {{ request()->is('master-grid*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">📅</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Master Grid</span>
                </a>

                <a href="/notifications" wire:navigate 
                    class="relative flex items-center p-2 rounded-xl transition-all group {{ request()->is('notifications*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                    <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">🔔</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Notifications</span>

                    @php $notifCount = auth()->user()->unreadNotifications->count(); @endphp
                    @if($notifCount > 0)
                        <span class="absolute flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white border-2 border-[#0f172a] shadow-sm animate-pulse"
                            :class="sidebarOpen ? 'right-2' : 'top-0 right-1'">
                            {{ $notifCount }}
                        </span>
                    @endif
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                    <div x-show="sidebarOpen" class="px-2.5 pb-1 pt-3">
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">System</p>
                    </div>
                    <a href="/settings" wire:navigate class="flex items-center p-2 rounded-xl transition-all group {{ request()->is('settings*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span class="text-lg flex shrink-0 justify-center" :class="sidebarOpen ? 'mr-2.5' : 'w-full'">⚙️</span>
                        <span x-show="sidebarOpen" x-transition.opacity class="font-semibold text-[11px] whitespace-nowrap">Settings</span>
                    </a>
                @endif

                @php $unreadNotifs = auth()->user()->unreadNotifications; @endphp
                @if($unreadNotifs->count() > 0)
                <div x-show="sidebarOpen" class="mt-6 mx-2 p-3 bg-blue-500/5 rounded-2xl border border-blue-500/10 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[7px] font-black text-blue-500 uppercase tracking-[0.2em]">Recent Alerts</p>
                        <span class="flex h-1.5 w-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></span>
                    </div>
                    <div class="space-y-2 max-h-32 overflow-y-auto pr-1 custom-scrollbar">
                        @foreach($unreadNotifs->take(3) as $notification)
                            <div class="flex flex-col gap-1 border-l-2 border-blue-600 pl-2 py-0.5">
                                <p class="text-[9px] font-bold text-slate-200 leading-tight">
                                    {{ Str::limit($notification->data['message'] ?? 'New Request', 40) }}
                                </p>
                                <span class="text-[7px] text-slate-500 font-medium lowercase italic">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </nav>

            <div class="p-2.5 border-t border-slate-800 bg-[#020617]/50 shrink-0">
                <div class="flex items-center mb-2.5" :class="sidebarOpen ? 'px-1.5' : 'justify-center'">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-[9px] font-black shadow-lg border-2 border-slate-800 uppercase text-white">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div x-show="sidebarOpen" x-transition.opacity class="ml-2 overflow-hidden">
                        <p class="text-[10px] font-bold text-white truncate leading-tight">{{ auth()->user()->name }}</p>
                        <span class="text-[7px] px-1 py-0.5 bg-blue-500/10 text-blue-400 rounded uppercase font-bold tracking-tighter">{{ auth()->user()->role }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center p-1.5 text-slate-400 hover:text-white hover:bg-red-500/20 rounded-lg transition-all border border-white/5 group">
                        <span class="text-sm flex shrink-0" :class="sidebarOpen ? 'mr-2' : ''">🔒</span>
                        <span x-show="sidebarOpen" x-transition.opacity class="text-[8px] font-black uppercase tracking-widest">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto bg-[#F8FAFC] custom-scrollbar relative">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts
</body>
</html>