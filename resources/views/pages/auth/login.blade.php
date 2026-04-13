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
    <title>Login | Classly</title>
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
<body class="antialiased transition-all duration-700 overflow-hidden flex items-center justify-center min-h-screen"
      :class="darkMode ? 'bg-[#020617] text-white' : 'text-slate-900'"
      :style="!darkMode ? 'background: linear-gradient(233deg, rgba(238, 174, 202, 1) 0%, rgba(185, 182, 220, 1) 17%, rgba(168, 184, 226, 1) 46%, rgba(157, 186, 230, 1) 78%, rgba(148, 187, 233, 1) 90%);' : ''">
    
    <div x-show="darkMode" class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full animate-pulse"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[50%] h-[50%] bg-indigo-900/10 blur-[120px] rounded-full animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="absolute top-8 right-12 z-50 cursor-pointer transition-all duration-300 hover:scale-110" 
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

    <div class="relative z-10 w-full max-w-md px-6 animate-float">
        <div class="text-center mb-10">
            <h1 class="text-6xl font-black tracking-tighter transition-colors"
                :class="darkMode ? 'text-white' : 'text-white drop-shadow-md'">
                Classly<span :class="darkMode ? 'text-blue-500' : 'text-blue-700'">.</span>
            </h1>
            <p class="text-[10px] uppercase font-black tracking-[0.4em] mt-2 opacity-60 transition-colors"
               :class="darkMode ? 'text-slate-400' : 'text-slate-800'">
                Authentication Gateway
            </p>
        </div>

        <div class="border transition-all duration-500 rounded-[3.5rem] p-10 shadow-2xl"
             :class="darkMode ? 'bg-[#0f172a]/80 backdrop-blur-2xl border-white/10' : 'bg-white/80 backdrop-blur-xl border-white/40'"
             x-data="{ showPass: false }">
            
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase ml-4 tracking-widest opacity-60">Identify</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-5 flex items-center text-blue-500 text-xs font-bold">@</span>
                        <input type="email" name="email" required autofocus 
                            class="w-full pl-12 pr-6 py-5 rounded-2xl font-bold text-sm transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            :class="darkMode ? 'bg-slate-900/50 border-white/5 text-white placeholder:text-slate-600' : 'bg-white border-slate-200 text-slate-900 placeholder:text-slate-400'"
                            placeholder="registrar@pap.edu.ph">
                    </div>
                    @error('email') <span class="text-red-500 text-[10px] font-bold ml-4">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase ml-4 tracking-widest opacity-60">Access Key</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-5 flex items-center text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        
                        <input :type="showPass ? 'text' : 'password'" name="password" required 
                            class="w-full pl-12 pr-14 py-5 rounded-2xl font-bold text-sm transition-all focus:ring-2 focus:ring-blue-500 outline-none"
                            :class="darkMode ? 'bg-slate-900/50 border-white/5 text-white' : 'bg-white border-slate-200 text-slate-900'"
                            placeholder="••••••••">

                        <button type="button" @click="showPass = !showPass" 
                                class="absolute inset-y-0 right-5 flex items-center text-slate-500 hover:text-blue-500 transition-colors">
                            <template x-if="!showPass">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </template>
                            <template x-if="showPass">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </template>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between px-2">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-[10px] font-black uppercase tracking-widest opacity-40 group-hover:opacity-100 transition-opacity" :class="darkMode ? 'text-white' : 'text-slate-700'">Keep Session</span>
                    </label>
                </div>

                <button type="submit" 
                    class="w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                    Verify & Enter
                </button>
            </form>
        </div>

        <div class="mt-8 text-center">
            <p class="text-[9px] font-black uppercase tracking-[0.4em] opacity-30 transition-colors"
               :class="darkMode ? 'text-slate-500' : 'text-slate-700'">
                Professional Academy of the Philippines &copy; {{ date('Y') }}
            </p>
        </div>
    </div>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 5s ease-in-out infinite; }
    </style>
</body>
</html>