<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        showPass: false,
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
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('logo.png') }}">

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        /* ════════════════════════════════════════
           FONTS
        ════════════════════════════════════════ */
        @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800;900&display=swap');
        * { font-family: 'Sora', sans-serif; }

        /* ════════════════════════════════════════
           LOGO ANIMATIONS
        ════════════════════════════════════════ */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50%       { transform: translateY(-12px) rotate(0.4deg); }
        }
        .animate-float { animation: float 5s ease-in-out infinite; }

        @keyframes logo-glow-dark {
            0%, 100% {
                filter: drop-shadow(0 0 18px rgba(59,130,246,0.5))
                        drop-shadow(0 0 35px rgba(59,130,246,0.2))
                        drop-shadow(0 0 7px rgba(239,68,68,0.3));
            }
            50% {
                filter: drop-shadow(0 0 32px rgba(59,130,246,0.85))
                        drop-shadow(0 0 60px rgba(59,130,246,0.35))
                        drop-shadow(0 0 18px rgba(239,68,68,0.5));
            }
        }
        @keyframes logo-glow-light {
            0%, 100% {
                filter: drop-shadow(0 8px 18px rgba(30,64,175,0.3))
                        drop-shadow(0 0 28px rgba(59,130,246,0.15));
            }
            50% {
                filter: drop-shadow(0 8px 32px rgba(30,64,175,0.55))
                        drop-shadow(0 0 45px rgba(59,130,246,0.3));
            }
        }
        .logo-glow-dark  { animation: logo-glow-dark  4s ease-in-out infinite; }
        .logo-glow-light { animation: logo-glow-light 4s ease-in-out infinite; }

        .logo-img {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .logo-img:hover { transform: scale(1.07) rotate(-2deg); }

        /* ════════════════════════════════════════
           CLASSLY WORDMARK
        ════════════════════════════════════════ */
        .brand-class {
            background: linear-gradient(135deg, #bfdbfe 0%, #60a5fa 30%, #3b82f6 60%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-ly {
            background: linear-gradient(135deg, #fecaca 0%, #f87171 30%, #ef4444 60%, #b91c1c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes shimmer {
            0%   { background-position: -400% center; }
            100% { background-position:  400% center; }
        }
        .brand-shimmer {
            position: relative;
            display: inline-block;
        }
        .brand-shimmer::after {
            content: attr(data-text);
            position: absolute;
            inset: 0;
            background: linear-gradient(108deg, transparent 25%, rgba(255,255,255,0.7) 50%, transparent 75%);
            background-size: 400% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s linear infinite;
            pointer-events: none;
        }

        @keyframes classly-glow-dark {
            0%, 100% { filter: drop-shadow(0 0 12px rgba(59,130,246,0.4)) drop-shadow(0 0 4px rgba(239,68,68,0.2)); }
            50%       { filter: drop-shadow(0 0 28px rgba(59,130,246,0.7)) drop-shadow(0 0 12px rgba(239,68,68,0.4)); }
        }
        @keyframes classly-glow-light {
            0%, 100% { filter: drop-shadow(0 2px 8px rgba(30,64,175,0.2)); }
            50%       { filter: drop-shadow(0 2px 22px rgba(30,64,175,0.45)); }
        }
        .classly-glow-dark  { animation: classly-glow-dark  3.5s ease-in-out infinite; }
        .classly-glow-light { animation: classly-glow-light 3.5s ease-in-out infinite; }

        /* ════════════════════════════════════════
           FADE IN
        ════════════════════════════════════════ */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up        { animation: fade-up 0.8s ease-out forwards; }
        .fade-up-d1     { animation: fade-up 0.8s 0.15s ease-out both; }
        .fade-up-d2     { animation: fade-up 0.8s 0.30s ease-out both; }
        .fade-up-d3     { animation: fade-up 0.8s 0.45s ease-out both; }

        /* ════════════════════════════════════════
           BACKGROUND
        ════════════════════════════════════════ */
        @keyframes grad-shift {
            0%   { background-position: 0%   50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0%   50%; }
        }
        .bg-animated-dark {
            background: linear-gradient(-45deg, #020617, #0a1628, #020617, #060d1f);
            background-size: 400% 400%;
            animation: grad-shift 14s ease infinite;
        }

        @keyframes blob-drift {
            0%, 100% { transform: translate(0,0) scale(1); }
            33%       { transform: translate(28px,-20px) scale(1.04); }
            66%       { transform: translate(-16px,14px) scale(0.97); }
        }
        .blob    { animation: blob-drift 12s ease-in-out infinite; }
        .blob-d2 { animation: blob-drift 15s ease-in-out infinite; animation-delay: -5s; }
        .blob-d3 { animation: blob-drift 18s ease-in-out infinite; animation-delay: -9s; }

        /* Particles */
        @keyframes particle-float {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: 0.55; }
            90%  { opacity: 0.25; }
            100% { transform: translateY(-20px) scale(1); opacity: 0; }
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: particle-float linear infinite;
        }

        /* ════════════════════════════════════════
           GLASS CARD
        ════════════════════════════════════════ */
        .glass-card-dark {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
        .glass-card-light {
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(255,255,255,0.55);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* ════════════════════════════════════════
           INPUTS
        ════════════════════════════════════════ */
        .input-dark {
            background: rgba(15, 23, 42, 0.65);
            border: 1.5px solid rgba(255,255,255,0.07);
            color: #fff;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
        }
        .input-dark::placeholder { color: rgba(148,163,184,0.45); }
        .input-dark:focus {
            border-color: rgba(59,130,246,0.6);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15), 0 0 20px rgba(59,130,246,0.1);
            background: rgba(15, 23, 42, 0.85);
            outline: none;
        }

        .input-light {
            background: rgba(255,255,255,0.9);
            border: 1.5px solid rgba(203,213,225,0.7);
            color: #0f172a;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .input-light::placeholder { color: rgba(148,163,184,0.7); }
        .input-light:focus {
            border-color: rgba(59,130,246,0.55);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12), 0 0 16px rgba(59,130,246,0.08);
            outline: none;
        }

        /* ════════════════════════════════════════
           SUBMIT BUTTON
        ════════════════════════════════════════ */
        @keyframes btn-glow-pulse {
            0%, 100% { box-shadow: 0 0 18px rgba(59,130,246,0.35), 0 8px 35px rgba(59,130,246,0.2); }
            50%       { box-shadow: 0 0 35px rgba(59,130,246,0.6), 0 8px 50px rgba(59,130,246,0.35); }
        }
        @keyframes btn-shine {
            0%   { left: -100%; }
            100% { left: 200%; }
        }
        .submit-btn {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 60%, #1d4ed8 100%);
            animation: btn-glow-pulse 3s ease-in-out infinite;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
        }
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.28), transparent);
            transform: skewX(-20deg);
        }
        .submit-btn:hover::before {
            animation: btn-shine 0.6s ease forwards;
        }
        .submit-btn:hover {
            transform: scale(1.025);
            box-shadow: 0 0 50px rgba(59,130,246,0.7), 0 12px 45px rgba(59,130,246,0.4) !important;
        }
        .submit-btn:active { transform: scale(0.97); }

        /* ════════════════════════════════════════
           THEME TOGGLE
        ════════════════════════════════════════ */
        .theme-toggle { transition: transform 0.3s ease; }
        .theme-toggle:hover  { transform: scale(1.15) rotate(15deg); }
        .theme-toggle:active { transform: scale(0.95) rotate(-10deg); }

        /* ════════════════════════════════════════
           FOOTER
        ════════════════════════════════════════ */
        .footer-text {
            transition: opacity 0.3s ease, letter-spacing 0.3s ease;
        }
        .footer-text:hover {
            opacity: 0.65 !important;
            letter-spacing: 0.3em;
        }

        /* ════════════════════════════════════════
           LABEL DIVIDER
        ════════════════════════════════════════ */
        .field-label {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
    </style>
</head>

<body class="antialiased transition-all duration-700 overflow-hidden flex items-center justify-center min-h-screen"
      :class="darkMode ? 'text-white' : 'text-slate-900'">

    <!-- ════════════════════════════════════════
         BACKGROUND LAYER
    ════════════════════════════════════════ -->
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="bg-animated-dark fixed inset-0 z-0"></div>

    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 z-0"
         style="background: linear-gradient(233deg, rgba(238,174,202,1) 0%, rgba(185,182,220,1) 17%, rgba(168,184,226,1) 46%, rgba(157,186,230,1) 78%, rgba(148,187,233,1) 90%);
                background-size: 300% 300%; animation: grad-shift 18s ease infinite;"></div>

    <!-- Dark blobs -->
    <div x-show="darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob    absolute top-[-12%] left-[-8%]  w-[48%] h-[48%] bg-blue-600/12  blur-[150px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-12%] right-[-8%] w-[48%] h-[48%] bg-indigo-700/12 blur-[150px] rounded-full"></div>
        <div class="blob-d3 absolute top-[38%] left-[42%]  w-[28%] h-[28%] bg-red-700/7   blur-[110px] rounded-full"></div>
        <div class="blob    absolute top-[55%] left-[8%]   w-[22%] h-[22%] bg-violet-600/7 blur-[90px] rounded-full"></div>
    </div>

    <!-- Light blobs -->
    <div x-show="!darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob    absolute top-[-10%] right-[5%] w-[40%] h-[40%] bg-blue-300/25  blur-[120px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-5%] left-[5%] w-[35%] h-[35%] bg-pink-300/20 blur-[100px] rounded-full"></div>
        <div class="blob-d3 absolute top-[40%] left-[40%] w-[25%] h-[25%] bg-purple-300/18 blur-[85px] rounded-full"></div>
    </div>

    <!-- Particles (dark) -->
    <div x-show="darkMode" class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="particle w-1   h-1   bg-blue-400/50"   style="left:10%; animation-duration:9s;  animation-delay:0s;"></div>
        <div class="particle w-1.5 h-1.5 bg-indigo-400/40" style="left:26%; animation-duration:12s; animation-delay:-3s;"></div>
        <div class="particle w-1   h-1   bg-blue-300/45"   style="left:50%; animation-duration:8s;  animation-delay:-5s;"></div>
        <div class="particle w-2   h-2   bg-violet-400/30" style="left:68%; animation-duration:14s; animation-delay:-1s;"></div>
        <div class="particle w-1   h-1   bg-red-400/30"    style="left:82%; animation-duration:10s; animation-delay:-7s;"></div>
        <div class="particle w-1.5 h-1.5 bg-blue-500/35"  style="left:93%; animation-duration:11s; animation-delay:-4s;"></div>
    </div>

    <!-- ════════════════════════════════════════
         THEME TOGGLE
    ════════════════════════════════════════ -->
    <button class="theme-toggle absolute top-6 right-8 z-50 cursor-pointer" @click="toggleTheme()" aria-label="Toggle theme">
        <div class="relative">
            <div x-show="darkMode" class="absolute inset-0 bg-yellow-400/25 blur-xl rounded-full scale-150"></div>
            <svg x-show="darkMode" class="w-9 h-9 text-yellow-400 drop-shadow-[0_0_12px_rgba(250,204,21,0.9)]"
                 fill="currentColor" viewBox="0 0 20 20">
                <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg x-show="!darkMode" class="w-9 h-9 text-slate-700 drop-shadow-md"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.674M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
    </button>

    <!-- ════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════ -->
    <div class="relative z-10 w-full max-w-sm px-4 flex flex-col items-center gap-6">

        <!-- ── BRANDING ── -->
        <div class="flex flex-col items-center text-center select-none">

            <!-- Logo -->
            <div class="animate-float mb-3 fade-up"
                 :class="darkMode ? 'logo-glow-dark' : 'logo-glow-light'">
                <img src="{{ asset('logo.png') }}"
                     alt="Classly Logo"
                     class="logo-img w-20 h-20 md:w-24 md:h-24 object-contain rounded-2xl"
                     draggable="false">
            </div>

            <!-- CLASSLY wordmark -->
            <div class="animate-float fade-up-d1"
                 :class="darkMode ? 'classly-glow-dark' : 'classly-glow-light'">
                <h1 class="text-[3.2rem] md:text-[3.8rem] leading-none font-black tracking-[-0.02em]">
                    <span class="brand-shimmer brand-class" data-text="Class">Class</span><span
                          class="brand-shimmer brand-ly"   data-text="ly">ly</span>
                </h1>
            </div>

            <!-- Sub-tagline -->
            <p class="mt-1.5 text-[10px] font-bold tracking-[0.42em] uppercase fade-up-d2 transition-colors"
               :class="darkMode ? 'text-blue-200/45' : 'text-slate-700/55'">
                Authentication Gateway
            </p>
        </div>

        <!-- ── FORM CARD ── -->
        <div class="w-full rounded-[2.2rem] shadow-2xl fade-up-d2 transition-all duration-500"
             :class="darkMode ? 'glass-card-dark' : 'glass-card-light'">

            <form method="POST" action="{{ route('login') }}" class="px-8 py-8 space-y-5">
                @csrf

                <!-- Email -->
                <div class="space-y-1.5 fade-up-d2">
                    <label class="field-label pl-1 transition-colors"
                           :class="darkMode ? 'text-slate-400/70' : 'text-slate-500/80'">
                        Identify
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 flex items-center text-blue-500 font-black text-sm select-none pointer-events-none">@</span>
                        <input type="email" name="email" required autofocus
                               class="w-full pl-10 pr-5 py-4 rounded-xl text-sm font-semibold"
                               :class="darkMode ? 'input-dark' : 'input-light'"
                               placeholder="registrar@pap.edu.ph"
                               value="{{ old('email') }}">
                    </div>
                    @error('email')
                        <span class="block text-red-400 text-[10px] font-bold pl-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="space-y-1.5 fade-up-d2">
                    <label class="field-label pl-1 transition-colors"
                           :class="darkMode ? 'text-slate-400/70' : 'text-slate-500/80'">
                        Access Key
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 flex items-center text-blue-500 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input :type="showPass ? 'text' : 'password'"
                               name="password" required
                               class="w-full pl-10 pr-12 py-4 rounded-xl text-sm font-semibold"
                               :class="darkMode ? 'input-dark' : 'input-light'"
                               placeholder="••••••••">
                        <button type="button"
                                @click="showPass = !showPass"
                                class="absolute inset-y-0 right-4 flex items-center transition-colors"
                                :class="darkMode ? 'text-slate-500 hover:text-blue-400' : 'text-slate-400 hover:text-blue-600'">
                            <template x-if="!showPass">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </template>
                            <template x-if="showPass">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </template>
                        </button>
                    </div>
                    @error('password')
                        <span class="block text-red-400 text-[10px] font-bold pl-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember + Forgot -->
                <div class="flex items-center justify-between px-1 fade-up-d3">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember"
                               class="w-3.5 h-3.5 rounded border-slate-600 bg-transparent text-blue-600 focus:ring-blue-500 focus:ring-1 cursor-pointer">
                        <span class="field-label opacity-40 group-hover:opacity-80 transition-opacity cursor-pointer"
                              :class="darkMode ? 'text-white' : 'text-slate-700'">
                            Keep Session
                        </span>
                    </label>
                    <a href="{{ route('password.request') }}"
                       class="field-label text-blue-500 hover:text-blue-400 transition-colors hover:underline underline-offset-2">
                        Forgot Password?
                    </a>
                </div>

                <!-- Submit -->
                <div class="fade-up-d3 pt-1">
                    <button type="submit"
                            class="submit-btn w-full py-4 text-white rounded-xl font-black uppercase text-[11px] tracking-[0.25em]">
                        Verify &amp; Enter
                    </button>
                </div>
            </form>
        </div>

        <!-- ── FOOTER ── -->
        <div class="text-center space-y-0.5 fade-up-d3 pb-2">
            <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
               :class="darkMode ? 'text-blue-300/22 opacity-[0.22]' : 'text-slate-600/28 opacity-[0.28]'">
                Professional Academy of the Philippines &copy; {{ date('Y') }}
            </p>
            <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
               :class="darkMode ? 'text-blue-300/18 opacity-[0.18]' : 'text-slate-600/22 opacity-[0.22]'">
                Classly &mdash; Developed by DJS
            </p>
        </div>

    </div><!-- /main -->

</body>
</html>