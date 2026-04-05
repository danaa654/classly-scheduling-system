<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classly | Academy OS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[#020617] text-white selection:bg-blue-500/30 overflow-hidden">
    
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-6">
        
        <div class="mb-12 text-center">
            <h1 class="text-7xl font-black tracking-tighter text-blue-400 drop-shadow-2xl">
                Classly<span class="text-white">.</span>
            </h1>
            <p class="text-xs text-slate-400 mt-2 uppercase font-black tracking-[0.4em] opacity-80">
                Academy Operating System
            </p>
        </div>

        <div class="w-full max-w-xl bg-[#0f172a]/50 backdrop-blur-xl border border-white/5 rounded-[3rem] p-12 shadow-2xl">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-bold text-white mb-3">Welcome Back</h2>
                <p class="text-slate-400 text-sm leading-relaxed">
                    Access the automated scheduling core and institutional data management systems.
                </p>
            </div>

            <div class="space-y-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="group relative flex items-center justify-center w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-xl shadow-blue-900/20">
                            Enter Dashboard
                            <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="group relative flex items-center justify-center w-full py-5 bg-white text-slate-900 hover:bg-slate-100 rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-xl shadow-white/5">
                            Authorized Access Only
                        </a>
                    @endauth
                @endif
            </div>

            <div class="mt-12 pt-8 border-t border-white/5 text-center">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic">
                    "Life is not a race"
                </p>
            </div>
        </div>

        <footer class="absolute bottom-8 w-full text-center">
            <p class="text-[9px] text-slate-600 uppercase font-bold tracking-widest">
                Professional Academy of the Philippines &copy; {{ date('Y') }}
            </p>
        </footer>
    </div>
</body>
</html>