<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Classly</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[#020617] text-white overflow-hidden flex items-center justify-center min-h-screen">
    
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[50%] h-[50%] bg-indigo-900/20 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-6">
        <div class="text-center mb-10">
            <h1 class="text-5xl font-black tracking-tighter text-white">
                Classly<span class="text-blue-500">.</span>
            </h1>
            <p class="text-[10px] text-slate-500 uppercase font-black tracking-[0.3em] mt-2">
                Secure Authentication Gateway
            </p>
        </div>

        <div class="bg-[#0f172a]/80 backdrop-blur-2xl border border-white/10 rounded-[3rem] p-10 shadow-2xl">
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-4 tracking-widest">Identify</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-5 flex items-center text-slate-500 text-xs">@</span>
                        <input type="email" name="email" required autofocus 
                            class="w-full pl-12 pr-6 py-4 bg-slate-900/50 border border-white/5 rounded-2xl font-bold text-sm text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder:text-slate-600"
                            placeholder="registrar@pap.edu.ph">
                    </div>
                    @error('email') <span class="text-red-500 text-[10px] font-bold ml-4">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-4 tracking-widest">Access Key</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-5 flex items-center text-slate-500 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input type="password" name="password" required 
                            class="w-full pl-12 pr-6 py-4 bg-slate-900/50 border border-white/5 rounded-2xl font-bold text-sm text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder:text-slate-600"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between px-2">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                        <span class="ml-2 text-[10px] font-bold text-slate-500 uppercase tracking-tight group-hover:text-slate-300 transition-colors">Remember Session</span>
                    </label>
                </div>

                <button type="submit" 
                    class="w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-xl shadow-blue-900/40 active:scale-[0.98]">
                    Verify & Enter
                </button>
            </form>
        </div>

        <div class="mt-8 text-center">
            <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">
                System Administrator: Registrar Office
            </p>
        </div>
    </div>
</body>
</html>