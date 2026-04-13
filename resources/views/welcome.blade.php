<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
      }" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classly | Academy OS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="antialiased transition-all duration-700 overflow-hidden" 
      :class="darkMode ? 'bg-[#020617] text-white' : 'text-slate-900'"
      :style="!darkMode ? 'background: linear-gradient(233deg, rgba(238, 174, 202, 1) 0%, rgba(185, 182, 220, 1) 17%, rgba(168, 184, 226, 1) 46%, rgba(157, 186, 230, 1) 78%, rgba(148, 187, 233, 1) 90%);' : ''">
    
    <div x-show="darkMode" x-transition.opacity.duration.500ms class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="absolute top-8 right-12 z-50 cursor-pointer transition-all duration-300 hover:scale-110 active:rotate-12" 
         @click="toggleTheme()">
        <div class="relative group">
            <div x-show="darkMode" class="absolute inset-0 bg-yellow-400/20 blur-xl rounded-full"></div>
            
            <svg x-show="darkMode" class="w-10 h-10 text-yellow-400 drop-shadow-[0_0_15px_rgba(250,204,21,0.8)]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>

            <svg x-show="!darkMode" class="w-10 h-10 text-slate-700 drop-shadow-md" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.674M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
        </div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-6">
        
        <div class="mb-12 text-center animate-float">
            <h1 class="text-8xl font-black tracking-tighter drop-shadow-2xl transition-colors"
                :class="darkMode ? 'text-blue-400' : 'text-white drop-shadow-lg'">
                Classly<span :class="darkMode ? 'text-white' : 'text-blue-700'">.</span>
            </h1>
            <p class="text-[10px] mt-4 uppercase font-black tracking-[0.6em] transition-opacity"
               :class="darkMode ? 'text-slate-400 opacity-60' : 'text-slate-800 opacity-80'">
                Academy Operating System
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl w-full mb-12">
            <div class="group p-8 rounded-[2.5rem] border transition-all duration-500 hover:-translate-y-4 shadow-2xl"
                 :class="darkMode ? 'bg-[#0f172a]/50 border-white/5 hover:bg-white/10' : 'bg-white/70 backdrop-blur-md border-white/40 hover:bg-white/90'">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center mb-6 text-blue-500 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter mb-2">Conflict Detection</h3>
                <p class="text-sm leading-relaxed opacity-70">Advanced AI core that prevents classroom and faculty scheduling overlaps instantly.</p>
            </div>

            <div class="group p-8 rounded-[2.5rem] border transition-all duration-500 hover:-translate-y-4 shadow-2xl"
                 :class="darkMode ? 'bg-[#0f172a]/50 border-white/5 hover:bg-white/10' : 'bg-white/70 backdrop-blur-md border-white/40 hover:bg-white/90'">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center mb-6 text-purple-500 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter mb-2">Institutional Sync</h3>
                <p class="text-sm leading-relaxed opacity-70">Unified data management across CCS, CTE, COC, and SHTM departments.</p>
            </div>

            <div class="group p-8 rounded-[2.5rem] border transition-all duration-500 hover:-translate-y-4 shadow-2xl"
                 :class="darkMode ? 'bg-[#0f172a]/50 border-white/5 hover:bg-white/10' : 'bg-white/70 backdrop-blur-md border-white/40 hover:bg-white/90'">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-6 text-emerald-500 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter mb-2">Secure Gateways</h3>
                <p class="text-sm leading-relaxed opacity-70">Role-specific access for administrators and deans to ensure data integrity.</p>
            </div>
        </div>

        <div class="w-full max-w-xl text-center">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" 
                       class="group relative flex items-center justify-center w-full py-6 bg-blue-600 hover:bg-blue-500 text-white rounded-[2rem] font-black uppercase text-xs tracking-[0.2em] transition-all hover:scale-105 shadow-2xl shadow-blue-500/20">
                        Enter Command Center
                        <span class="ml-3 group-hover:translate-x-2 transition-transform">→</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" 
                       class="group relative flex items-center justify-center w-full py-6 font-black uppercase text-xs tracking-[0.2em] rounded-[2rem] transition-all hover:scale-105 shadow-2xl"
                       :class="darkMode ? 'bg-white text-slate-900 hover:bg-slate-100' : 'bg-slate-900 text-white hover:bg-black'">
                        Request Authorized Access
                    </a>
                @endauth
            @endif

            <p class="mt-12 text-[10px] font-black uppercase tracking-[0.5em] italic opacity-40 transition-colors"
               :class="darkMode ? 'text-white' : 'text-slate-900'">
                "Life is not a race"
            </p>
        </div>

        <footer class="absolute bottom-8 w-full text-center">
            <p class="text-[9px] uppercase font-bold tracking-[0.3em] opacity-30 transition-colors"
               :class="darkMode ? 'text-slate-400' : 'text-slate-700'">
                Professional Academy of the Philippines &copy; {{ date('Y') }}
            </p>
        </footer>
    </div>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</body>
</html>