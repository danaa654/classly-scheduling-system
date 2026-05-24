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
            50%       { transform: translateY(-16px) rotate(0.5deg); }
        }
        .animate-float { animation: float 5s ease-in-out infinite; }

        @keyframes logo-glow-dark {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(59,130,246,0.5))
                        drop-shadow(0 0 40px rgba(59,130,246,0.2))
                        drop-shadow(0 0 8px rgba(239,68,68,0.3));
            }
            50% {
                filter: drop-shadow(0 0 35px rgba(59,130,246,0.85))
                        drop-shadow(0 0 70px rgba(59,130,246,0.35))
                        drop-shadow(0 0 20px rgba(239,68,68,0.5));
            }
        }
        @keyframes logo-glow-light {
            0%, 100% {
                filter: drop-shadow(0 8px 20px rgba(30,64,175,0.3))
                        drop-shadow(0 0 30px rgba(59,130,246,0.15));
            }
            50% {
                filter: drop-shadow(0 8px 35px rgba(30,64,175,0.55))
                        drop-shadow(0 0 50px rgba(59,130,246,0.3));
            }
        }
        .logo-glow-dark  { animation: logo-glow-dark  4s ease-in-out infinite; }
        .logo-glow-light { animation: logo-glow-light 4s ease-in-out infinite; }

        .logo-img {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .logo-img:hover {
            transform: scale(1.07) rotate(-2deg);
        }

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

        /* Shimmer sweep */
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
            background: linear-gradient(
                108deg,
                transparent 25%,
                rgba(255,255,255,0.7) 50%,
                transparent 75%
            );
            background-size: 400% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s linear infinite;
            pointer-events: none;
        }

        /* Text glow breathing */
        @keyframes classly-glow-dark {
            0%, 100% { filter: drop-shadow(0 0 15px rgba(59,130,246,0.4)) drop-shadow(0 0 5px rgba(239,68,68,0.2)); }
            50%       { filter: drop-shadow(0 0 35px rgba(59,130,246,0.7)) drop-shadow(0 0 15px rgba(239,68,68,0.4)); }
        }
        @keyframes classly-glow-light {
            0%, 100% { filter: drop-shadow(0 2px 10px rgba(30,64,175,0.2)); }
            50%       { filter: drop-shadow(0 2px 25px rgba(30,64,175,0.45)); }
        }
        .classly-glow-dark  { animation: classly-glow-dark  3.5s ease-in-out infinite; }
        .classly-glow-light { animation: classly-glow-light 3.5s ease-in-out infinite; }

        /* ════════════════════════════════════════
           TAGLINE FADE IN
        ════════════════════════════════════════ */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.9s ease-out forwards; }
        .fade-up-delay { animation: fade-up 0.9s 0.2s ease-out both; }
        .fade-up-delay2 { animation: fade-up 0.9s 0.4s ease-out both; }

        /* ════════════════════════════════════════
           BACKGROUND & BLOBS
        ════════════════════════════════════════ */
        @keyframes blob-drift {
            0%, 100% { transform: translate(0,0) scale(1); }
            33%       { transform: translate(30px,-22px) scale(1.05); }
            66%       { transform: translate(-18px,16px) scale(0.96); }
        }
        .blob          { animation: blob-drift 12s ease-in-out infinite; }
        .blob-d2       { animation: blob-drift 15s ease-in-out infinite; animation-delay: -5s; }
        .blob-d3       { animation: blob-drift 18s ease-in-out infinite; animation-delay: -9s; }

        /* Animated gradient bg (dark) */
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

        /* Floating particles */
        @keyframes particle-float {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: 0.6; }
            90%  { opacity: 0.3; }
            100% { transform: translateY(-20px) scale(1); opacity: 0; }
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: particle-float linear infinite;
        }

        /* ════════════════════════════════════════
           FEATURE CARDS
        ════════════════════════════════════════ */
        .feature-card {
            transition: transform 0.35s cubic-bezier(0.34, 1.4, 0.64, 1),
                        box-shadow 0.35s ease,
                        background 0.35s ease,
                        border-color 0.35s ease;
        }
        .feature-card:hover {
            transform: translateY(-10px) scale(1.025);
        }
        .feature-card-dark:hover {
            box-shadow: 0 0 0 1px rgba(99,102,241,0.4),
                        0 30px 60px -15px rgba(0,0,0,0.6),
                        0 0 40px rgba(99,102,241,0.12);
            border-color: rgba(99,102,241,0.35) !important;
            background: rgba(255,255,255,0.08) !important;
        }
        .feature-card-light:hover {
            box-shadow: 0 0 0 1.5px rgba(59,130,246,0.3),
                        0 25px 50px -10px rgba(30,64,175,0.2),
                        0 0 30px rgba(59,130,246,0.08);
            border-color: rgba(59,130,246,0.3) !important;
            background: rgba(255,255,255,0.95) !important;
        }

        /* Icon glow pulse on hover */
        .icon-wrap { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .feature-card:hover .icon-wrap {
            transform: scale(1.15);
            box-shadow: 0 0 20px currentColor;
        }

        /* ════════════════════════════════════════
           CTA BUTTON
        ════════════════════════════════════════ */
        @keyframes btn-glow-pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(59,130,246,0.35), 0 10px 40px rgba(59,130,246,0.2); }
            50%       { box-shadow: 0 0 40px rgba(59,130,246,0.6), 0 10px 60px rgba(59,130,246,0.35); }
        }
        @keyframes btn-shine {
            0%   { left: -100%; }
            100% { left: 200%; }
        }
        .cta-btn {
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
        }
        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transform: skewX(-20deg);
            transition: none;
        }
        .cta-btn:hover::before {
            animation: btn-shine 0.6s ease forwards;
        }
        .cta-btn-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 60%, #1d4ed8 100%);
            animation: btn-glow-pulse 3s ease-in-out infinite;
        }
        .cta-btn-blue:hover {
            transform: scale(1.04);
            box-shadow: 0 0 55px rgba(59,130,246,0.75), 0 15px 50px rgba(59,130,246,0.4) !important;
        }
        .cta-btn-dark:hover {
            transform: scale(1.04);
            box-shadow: 0 0 40px rgba(15,23,42,0.5), 0 15px 40px rgba(0,0,0,0.3);
        }
        .cta-btn-light:hover {
            transform: scale(1.04);
            box-shadow: 0 0 40px rgba(30,64,175,0.25), 0 15px 40px rgba(30,64,175,0.15);
        }

        /* ════════════════════════════════════════
           FOOTER
        ════════════════════════════════════════ */
        .footer-text {
            transition: opacity 0.3s ease, letter-spacing 0.3s ease;
        }
        .footer-text:hover {
            opacity: 0.7 !important;
            letter-spacing: 0.3em;
        }

        /* ════════════════════════════════════════
           THEME TOGGLE
        ════════════════════════════════════════ */
        .theme-toggle {
            transition: transform 0.3s ease;
        }
        .theme-toggle:hover { transform: scale(1.15) rotate(15deg); }
        .theme-toggle:active { transform: scale(0.95) rotate(-10deg); }
    </style>
</head>

<body class="antialiased transition-all duration-700 overflow-hidden"
      :class="darkMode ? 'text-white' : 'text-slate-900'">

    <!-- ════════════════════════════════════════
         BACKGROUND LAYER
    ════════════════════════════════════════ -->

    <!-- Dark animated bg -->
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="bg-animated-dark fixed inset-0 z-0"></div>

    <!-- Light bg -->
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 z-0 transition-all duration-700"
         style="background: linear-gradient(233deg, rgba(238,174,202,1) 0%, rgba(185,182,220,1) 17%, rgba(168,184,226,1) 46%, rgba(157,186,230,1) 78%, rgba(148,187,233,1) 90%);
                background-size: 300% 300%; animation: grad-shift 18s ease infinite;"></div>

    <!-- Dark mode blobs -->
    <div x-show="darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob   absolute top-[-15%] left-[-8%]  w-[50%] h-[50%] bg-blue-600/12  blur-[160px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-15%] right-[-8%] w-[50%] h-[50%] bg-indigo-600/12 blur-[160px] rounded-full"></div>
        <div class="blob-d3 absolute top-[35%] left-[45%]  w-[30%] h-[30%] bg-red-700/8   blur-[120px] rounded-full"></div>
        <div class="blob   absolute top-[60%] left-[10%]  w-[25%] h-[25%] bg-violet-600/8 blur-[100px] rounded-full"></div>
    </div>

    <!-- Light mode blobs -->
    <div x-show="!darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob   absolute top-[-10%] right-[5%]  w-[40%] h-[40%] bg-blue-300/25   blur-[120px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-5%] left-[5%] w-[35%] h-[35%] bg-pink-300/20   blur-[100px] rounded-full"></div>
        <div class="blob-d3 absolute top-[40%] left-[40%] w-[25%] h-[25%] bg-purple-300/18  blur-[90px] rounded-full"></div>
    </div>

    <!-- Floating particles (dark only) -->
    <div x-show="darkMode" class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="particle w-1 h-1 bg-blue-400/50"   style="left:12%; animation-duration:9s;  animation-delay:0s;"></div>
        <div class="particle w-1.5 h-1.5 bg-indigo-400/40" style="left:27%; animation-duration:12s; animation-delay:-3s;"></div>
        <div class="particle w-1 h-1 bg-blue-300/45"   style="left:44%; animation-duration:8s;  animation-delay:-6s;"></div>
        <div class="particle w-2 h-2 bg-violet-400/30" style="left:63%; animation-duration:14s; animation-delay:-1s;"></div>
        <div class="particle w-1 h-1 bg-red-400/35"    style="left:79%; animation-duration:10s; animation-delay:-4s;"></div>
        <div class="particle w-1.5 h-1.5 bg-blue-500/40" style="left:91%; animation-duration:11s; animation-delay:-7s;"></div>
    </div>

    <!-- ════════════════════════════════════════
         THEME TOGGLE
    ════════════════════════════════════════ -->
    <button class="theme-toggle absolute top-6 right-8 z-50 cursor-pointer"
            @click="toggleTheme()" aria-label="Toggle theme">
        <div class="relative">
            <div x-show="darkMode"
                 class="absolute inset-0 bg-yellow-400/25 blur-xl rounded-full scale-150"></div>
            <svg x-show="darkMode"
                 class="w-9 h-9 text-yellow-400 drop-shadow-[0_0_12px_rgba(250,204,21,0.9)]"
                 fill="currentColor" viewBox="0 0 20 20">
                <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a2 2 0 11-4 0 1 1 0 112 0zM13 10a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg x-show="!darkMode"
                 class="w-9 h-9 text-slate-700 drop-shadow-md"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9.663 17h4.674M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
    </button>

    <!-- ════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════ -->
    <div class="relative z-10 h-screen flex flex-col items-center justify-center px-4 py-6 gap-5">

        <!-- ── HERO BRANDING ── -->
        <div class="flex flex-col items-center text-center select-none">

            <!-- Logo -->
            <div class="animate-float mb-4 fade-up"
                 :class="darkMode ? 'logo-glow-dark' : 'logo-glow-light'">
                <img src="{{ asset('logo.png') }}"
                     alt="Classly Logo"
                     class="logo-img w-36 h-36 md:w-44 md:h-44 object-contain rounded-3xl"
                     draggable="false">
            </div>

            <!-- CLASSLY wordmark -->
            <div class="animate-float fade-up-delay"
                 :class="darkMode ? 'classly-glow-dark' : 'classly-glow-light'">
                <h1 class="text-[4.5rem] md:text-[6.5rem] leading-none font-black tracking-[-0.02em]">
                    <span class="brand-shimmer brand-class" data-text="Class">Class</span><span
                          class="brand-shimmer brand-ly"   data-text="ly">ly</span>
                </h1>
            </div>

            <!-- Tagline -->
            <p class="mt-2 text-[11px] md:text-[12px] font-bold tracking-[0.45em] uppercase fade-up-delay2 transition-colors"
               :class="darkMode ? 'text-blue-200/50' : 'text-slate-700/60'">
                Your Friendly Class Scheduler
            </p>
        </div>

        <!-- ── FEATURE CARDS ── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-5xl w-full px-2 fade-up-delay2">

            <!-- Card 1 · Conflict Detection -->
            <div class="feature-card p-5 rounded-2xl border backdrop-blur-md shadow-xl"
                 :class="darkMode
                     ? 'bg-white/5 border-white/8 feature-card-dark'
                     : 'bg-white/65 border-white/50 feature-card-light'">
                <div class="icon-wrap w-11 h-11 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-sm mb-1.5">Conflict Detection</h3>
                <p class="text-xs leading-relaxed"
                   :class="darkMode ? 'text-white/50' : 'text-slate-600/80'">
                    Advanced AI core that prevents classroom and faculty scheduling overlaps instantly.
                </p>
            </div>

            <!-- Card 2 · Institutional Sync -->
            <div class="feature-card p-5 rounded-2xl border backdrop-blur-md shadow-xl"
                 :class="darkMode
                     ? 'bg-white/5 border-white/8 feature-card-dark'
                     : 'bg-white/65 border-white/50 feature-card-light'">
                <div class="icon-wrap w-11 h-11 rounded-xl bg-purple-500/15 flex items-center justify-center mb-4 text-purple-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-sm mb-1.5">Institutional Sync</h3>
                <p class="text-xs leading-relaxed"
                   :class="darkMode ? 'text-white/50' : 'text-slate-600/80'">
                    Unified data management across CCS, CTE, COC, and SHTM departments.
                </p>
            </div>

            <!-- Card 3 · Secure Gateways -->
            <div class="feature-card p-5 rounded-2xl border backdrop-blur-md shadow-xl"
                 :class="darkMode
                     ? 'bg-white/5 border-white/8 feature-card-dark'
                     : 'bg-white/65 border-white/50 feature-card-light'">
                <div class="icon-wrap w-11 h-11 rounded-xl bg-emerald-500/15 flex items-center justify-center mb-4 text-emerald-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-sm mb-1.5">Secure Gateways</h3>
                <p class="text-xs leading-relaxed"
                   :class="darkMode ? 'text-white/50' : 'text-slate-600/80'">
                    Role-specific access for administrators and deans to ensure data integrity.
                </p>
            </div>
        </div>

        <!-- ── CTA BUTTON ── -->
        <div class="w-full max-w-sm text-center fade-up-delay2">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="cta-btn cta-btn-blue group flex items-center justify-center w-full py-5 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.25em]">
                        Enter Command Center
                        <span class="ml-3 group-hover:translate-x-2 transition-transform duration-300">→</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="cta-btn group flex items-center justify-center w-full py-5 rounded-2xl font-black uppercase text-[11px] tracking-[0.25em] shadow-xl"
                       :class="darkMode
                           ? 'bg-white text-slate-900 cta-btn-dark hover:bg-slate-50'
                           : 'bg-slate-900 text-white cta-btn-light hover:bg-black'">
                        Request Authorized Access
                        <span class="ml-3 group-hover:translate-x-2 transition-transform duration-300">→</span>
                    </a>
                @endauth
            @endif

            <!-- Tagline quote -->
            <p class="mt-4 text-[10px] font-black uppercase tracking-[0.5em] italic transition-colors"
               :class="darkMode ? 'text-white/30' : 'text-slate-900/35'">
                "Life is not a race"
            </p>
        </div>

        <!-- ── FOOTER ── -->
        <div class="text-center space-y-0.5">
            <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
               :class="darkMode ? 'text-blue-300/25' : 'text-slate-600/30'">
                Professional Academy of the Philippines &copy; 2026
            </p>
            <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
               :class="darkMode ? 'text-blue-300/20' : 'text-slate-600/25'">
                Classly &mdash; Developed by DJS
            </p>
        </div>

    </div><!-- /main -->

</body>
</html>