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
        [x-cloak] { display: none !important; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                // Livewire v3 sends data inside an array [0]
                const data = Array.isArray(event) ? event[0] : event;

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: data.type, 
                    title: data.message,
                    text: data.detail || '',
                    // Adding a bit of your glassmorphism style to the toast
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
                });
            });
        });
    </script>
</head>
<body 
    x-data="{ 
        sidebarOpen: true, 
        darkMode: localStorage.getItem('theme') === 'dark',
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    :class="{ 'dark': darkMode }"
    class="font-sans antialiased text-slate-900 dark:text-white bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"
>

    <div class="relative flex h-screen overflow-hidden">
        
        <aside 
            class="bg-[#0f172a] text-white flex flex-col h-screen shrink-0 border-r border-slate-800 z-40 transition-all duration-300 ease-in-out relative"
            :class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            <div class="px-6 h-20 flex items-center overflow-hidden shrink-0 border-b border-slate-800/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shrink-0 shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                        <span class="text-lg font-black text-white">C</span>
                    </div>
                    <div x-show="sidebarOpen" x-transition.opacity class="flex flex-col whitespace-nowrap">
                        <h1 class="text-lg font-black tracking-tighter text-white leading-none uppercase">Classly<span class="text-blue-500">.</span></h1>
                        <p class="text-[7px] text-slate-500 font-black uppercase tracking-[0.2em]">Academy OS</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 space-y-1.5 overflow-y-auto pt-6 custom-scrollbar">
                <div x-show="sidebarOpen" class="px-3 pb-2">
                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">General Navigation</p>
                </div>

                <a href="{{ route('dashboard') }}" wire:navigate
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->routeIs('dashboard')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">📊</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Dashboard</span>
                </a>

                @if(auth()->user()->role === 'admin')
                <a href="/manage-users" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->is('manage-users*') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->is('manage-users*')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">👥</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Manage Users</span>
                </a>
                @endif

                <a href="/manage-rooms" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->is('manage-rooms*') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->is('manage-rooms*')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">🏫</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Manage Rooms</span>
                </a>

                <a href="/faculty" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->is('faculty') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->is('faculty')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">👨‍🏫</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Faculty List</span>
                </a>

                <a href="{{ route('subjects') }}" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->routeIs('subjects*') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->routeIs('subjects*')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">📚</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Subjects</span>
                </a>

                <div x-show="sidebarOpen" class="px-3 pb-2 pt-6">
                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Academic Tools</p>
                </div>

                <a href="{{ route('master-grid') }}" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->is('master-grid*') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->is('master-grid*')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">📅</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Master Grid</span>
                </a>

                <a href="{{ route('faculty-loading') }}" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->routeIs('faculty-loading') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->routeIs('faculty-loading')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">✒️</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Faculty Info</span>
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                <a href="{{ route('settings') }}" wire:navigate 
                    class="relative flex items-center p-3 rounded-2xl transition-all duration-300 group {{ request()->routeIs('settings') ? 'bg-blue-600 text-white shadow-[0_0_20px_rgba(37,99,235,0.3)]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    @if(request()->routeIs('settings')) <span class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></span> @endif
                    <span class="text-xl flex shrink-0 justify-center transition-transform group-hover:scale-110" :class="sidebarOpen ? 'mr-3' : 'w-full'">⚙️</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="font-bold text-[14px] tracking-tight whitespace-nowrap">Settings</span>
                </a>
                 @endif
            </nav>

            <button 
                @click="sidebarOpen = !sidebarOpen" 
                class="absolute top-6 -right-3.5 z-50 p-1.5 bg-blue-600 text-white rounded-full border-2 border-[#0f172a] shadow-xl transition-all duration-300 hover:scale-110 active:scale-95"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-300" :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            
            <header class="h-20 flex items-center justify-between px-10 bg-[#0f172a] text-white shrink-0 z-30 shadow-2xl relative border-b border-slate-800/50">
                <div class="flex flex-col">
                    <h3 class="text-base font-black text-slate-100 tracking-tight uppercase">Professional Academy of the Philippines</h3>
                    <p class="text-[9px] text-blue-500 font-black uppercase tracking-[0.3em] leading-tight mt-0.5">Academic Personnel Management</p>
                </div>

                <div class="flex items-center gap-6">
                    <div @click="toggleTheme()" class="cursor-pointer p-2.5 rounded-2xl transition-all group bg-slate-800/40 border border-slate-700 hover:border-blue-500/50">
                        <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.6)]" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <svg x-show="!darkMode" x-cloak class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.674M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>

                    @livewire('notification-center')

                    <div class="flex items-center gap-4 pl-6 border-l border-slate-800">
                        <div class="text-right hidden md:block">
                            <p class="text-[12px] font-black text-white leading-none uppercase tracking-tighter">{{ auth()->user()->name }}</p>
                            <p class="text-[9px] text-blue-500 font-black uppercase mt-1 tracking-widest">{{ auth()->user()->role }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 flex items-center justify-center text-white text-[11px] font-black shadow-lg border border-white/10 transition-transform hover:scale-105">
                            {{ auth()->user()->initials() }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 text-slate-500 hover:text-rose-500 transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto custom-scrollbar bg-[#E6E6E6] dark:bg-[#020617] p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>

