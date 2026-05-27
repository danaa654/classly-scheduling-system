<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        showPass: false,
        panelOpen: false,
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
        * { font-family: 'Sora', sans-serif; box-sizing: border-box; }

        /* ════════════════════════════════════════
           LOGO ANIMATIONS
        ════════════════════════════════════════ */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50%       { transform: translateY(-10px) rotate(0.4deg); }
        }
        .animate-float { animation: float 5s ease-in-out infinite; }

        @keyframes logo-glow-dark {
            0%, 100% { filter: drop-shadow(0 0 18px rgba(59,130,246,0.5)) drop-shadow(0 0 35px rgba(59,130,246,0.2)); }
            50%       { filter: drop-shadow(0 0 32px rgba(59,130,246,0.85)) drop-shadow(0 0 60px rgba(59,130,246,0.35)); }
        }
        @keyframes logo-glow-light {
            0%, 100% { filter: drop-shadow(0 8px 18px rgba(30,64,175,0.3)) drop-shadow(0 0 28px rgba(59,130,246,0.15)); }
            50%       { filter: drop-shadow(0 8px 32px rgba(30,64,175,0.55)) drop-shadow(0 0 45px rgba(59,130,246,0.3)); }
        }
        .logo-glow-dark  { animation: logo-glow-dark  4s ease-in-out infinite; }
        .logo-glow-light { animation: logo-glow-light 4s ease-in-out infinite; }
        .logo-img { transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
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
        .brand-shimmer { position: relative; display: inline-block; }
        .brand-shimmer::after {
            content: attr(data-text);
            position: absolute; inset: 0;
            background: linear-gradient(108deg, transparent 25%, rgba(255,255,255,0.7) 50%, transparent 75%);
            background-size: 400% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s linear infinite;
            pointer-events: none;
        }
        @keyframes classly-glow-dark {
            0%, 100% { filter: drop-shadow(0 0 12px rgba(59,130,246,0.4)); }
            50%       { filter: drop-shadow(0 0 28px rgba(59,130,246,0.7)); }
        }
        .classly-glow-dark { animation: classly-glow-dark 3.5s ease-in-out infinite; }

        /* ════════════════════════════════════════
           FADE-IN STAGGER
        ════════════════════════════════════════ */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up    { animation: fade-up 0.8s ease-out forwards; }
        .fade-up-d1 { animation: fade-up 0.8s 0.15s ease-out both; }
        .fade-up-d2 { animation: fade-up 0.8s 0.30s ease-out both; }
        .fade-up-d3 { animation: fade-up 0.8s 0.45s ease-out both; }
        .fade-up-d4 { animation: fade-up 0.8s 0.60s ease-out both; }

        /* ════════════════════════════════════════
           PAGE BACKGROUNDS
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
            0%, 100% { transform: translate(0,0)    scale(1);    }
            33%       { transform: translate(28px,-20px) scale(1.04); }
            66%       { transform: translate(-16px,14px) scale(0.97); }
        }
        .blob    { animation: blob-drift 12s ease-in-out infinite; }
        .blob-d2 { animation: blob-drift 15s ease-in-out infinite; animation-delay:-5s; }
        .blob-d3 { animation: blob-drift 18s ease-in-out infinite; animation-delay:-9s; }

        /* ════════════════════════════════════════
           GLASS CARD
        ════════════════════════════════════════ */
        .glass-card-dark {
            background: rgba(15,23,42,0.55);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
        }
        .glass-card-light {
            background: rgba(255,255,255,0.76);
            border: 1px solid rgba(255,255,255,0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        /* ════════════════════════════════════════
           INPUTS
        ════════════════════════════════════════ */
        .input-dark {
            background: rgba(15,23,42,0.65);
            border: 1.5px solid rgba(255,255,255,0.07);
            color: #fff;
            transition: border-color .25s, box-shadow .25s, background .25s;
        }
        .input-dark::placeholder { color: rgba(148,163,184,0.45); }
        .input-dark:focus {
            border-color: rgba(59,130,246,0.6);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15), 0 0 20px rgba(59,130,246,0.1);
            background: rgba(15,23,42,0.85);
            outline: none;
        }
        .input-light {
            background: rgba(255,255,255,0.92);
            border: 1.5px solid rgba(203,213,225,0.7);
            color: #0f172a;
            transition: border-color .25s, box-shadow .25s;
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
            0%   { left:-100%; }
            100% { left: 200%; }
        }
        .submit-btn {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg,#3b82f6 0%,#2563eb 60%,#1d4ed8 100%);
            animation: btn-glow-pulse 3s ease-in-out infinite;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease;
        }
        .submit-btn::before {
            content:''; position:absolute; top:0; left:-100%;
            width:60%; height:100%;
            background: linear-gradient(90deg,transparent,rgba(255,255,255,0.28),transparent);
            transform: skewX(-20deg);
        }
        .submit-btn:hover::before { animation: btn-shine .6s ease forwards; }
        .submit-btn:hover {
            transform: scale(1.015);
            box-shadow: 0 0 50px rgba(59,130,246,0.7), 0 12px 45px rgba(59,130,246,0.4) !important;
        }
        .submit-btn:active { transform: scale(0.99); }

        /* ════════════════════════════════════════
           FIELD LABEL / FOOTER TEXT
        ════════════════════════════════════════ */
        .field-label {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .footer-text { transition: opacity .3s ease, letter-spacing .3s ease; }
        .footer-text:hover { opacity: 0.65 !important; letter-spacing: .3em; }

        /* ════════════════════════════════════════
           THEME TOGGLE
        ════════════════════════════════════════ */
        .theme-toggle { transition: transform .3s ease; }
        .theme-toggle:hover  { transform: scale(1.15) rotate(15deg); }
        .theme-toggle:active { transform: scale(0.95) rotate(-10deg); }

        /* ════════════════════════════════════════
           GEOMETRIC PATTERN / SHAPES (Left Panel)
        ════════════════════════════════════════ */
        .geo-pattern {
            background-image:
                linear-gradient(rgba(99,102,241,.09) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,.09) 1px, transparent 1px),
                radial-gradient(circle at 20% 30%, rgba(59,130,246,.14) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(99,102,241,.10) 0%, transparent 50%);
            background-size: 40px 40px, 40px 40px, 100% 100%, 100% 100%;
        }
        @keyframes geo-drift   {
            0%,100% { transform: translate(0,0) rotate(12deg); }
            50%      { transform: translate(8px,-14px) rotate(20deg); }
        }
        @keyframes geo-drift-b {
            0%,100% { transform: translate(0,0) rotate(-8deg); }
            50%      { transform: translate(-10px,10px) rotate(-18deg); }
        }
        @keyframes geo-pulse   {
            0%,100% { opacity:.18; transform:scale(1); }
            50%      { opacity:.32; transform:scale(1.06); }
        }
        .geo-shape-a { animation: geo-drift   9s ease-in-out infinite; }
        .geo-shape-b { animation: geo-drift-b 11s ease-in-out infinite; }
        .geo-shape-c { animation: geo-pulse    7s ease-in-out infinite; }

        /* Illustration mockup float */
        @keyframes illus-float {
            0%,100% { transform:translateY(0px); }
            50%      { transform:translateY(-8px); }
        }
        .illus-mock { animation: illus-float 6s ease-in-out infinite; }

        /* ════════════════════════════════════════
           PARTICLES
        ════════════════════════════════════════ */
        @keyframes particle-float {
            0%   { transform: translateY(100vh) scale(0); opacity:0; }
            10%  { opacity:.55; }
            90%  { opacity:.25; }
            100% { transform: translateY(-20px) scale(1); opacity:0; }
        }
        .particle {
            position:absolute; border-radius:50%;
            pointer-events:none;
            animation: particle-float linear infinite;
        }

        /* ════════════════════════════════════════
           AMBIENT GLOW BLOBS (behind card)
        ════════════════════════════════════════ */
        .glow-indigo {
            position:fixed; border-radius:9999px;
            pointer-events:none; filter:blur(80px);
        }

        /* ════════════════════════════════════════════════════════════
           ★ HOVER EXPAND — LEFT PANEL  (desktop only, md+)
           ─────────────────────────────────────────────────────────
           Default state  : panel collapses to a 7px indigo stripe.
           Hover state    : panel smoothly springs open to 45% width.
           Inner content  : slides in + fades after the panel opens.
        ════════════════════════════════════════════════════════════ */
        @media (min-width: 768px) {

            /* ── The card itself shifts to slightly wider on expand ── */
            .split-card {
                transition: box-shadow 0.5s ease;
            }
            .split-card:hover {
                box-shadow: 0 30px 80px rgba(59,130,246,0.18), 0 0 0 1px rgba(99,102,241,0.12);
            }

            /* ── Left panel: collapses to a thin indigo stripe ── */
            .left-panel {
                width: 7px;
                min-width: 7px;
                flex-shrink: 0;
                overflow: hidden;
                /* Spring-easing cubic-bezier for a satisfying snap */
                transition:
                    width     0.70s cubic-bezier(0.34, 1.20, 0.64, 1),
                    min-width 0.70s cubic-bezier(0.34, 1.20, 0.64, 1);
                cursor: e-resize;
            }

            /* ── On hover: spring-expand to full width ── */
            .split-card:hover .left-panel {
                width: 45%;
                min-width: 45%;
                cursor: default;
            }

            /* ── Stripe pulse: draws attention to the closed state ── */
            @keyframes stripe-breathe {
                0%,100% { box-shadow: inset 0 0 0px 0px rgba(99,102,241,0);    }
                50%      { box-shadow: inset 0 0 18px 2px rgba(99,102,241,0.25); }
            }
            .left-panel { animation: stripe-breathe 2.8s ease-in-out infinite; }
            .split-card:hover .left-panel { animation: none; }

            /* ── The expand-hint chevron on the stripe ── */
            .stripe-hint {
                position: absolute;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                display: flex; flex-direction: column;
                align-items: center; gap: 4px;
                transition: opacity 0.3s ease 0.1s;
                opacity: 1;
                pointer-events: none;
            }
            .split-card:hover .stripe-hint {
                opacity: 0;
                transition: opacity 0.15s ease;
            }
            @keyframes chevron-nudge {
                0%,100% { transform: translate(-50%,-50%) translateX(0); }
                50%      { transform: translate(-50%,-50%) translateX(3px); }
            }
            .stripe-hint { animation: chevron-nudge 1.4s ease-in-out infinite; }

            /* ── Panel inner content: hidden/slid left when closed ── */
            .left-panel-inner {
                min-width: 360px; /* prevents content squishing during transition */
                opacity: 0;
                transform: translateX(-22px);
                transition:
                    opacity   0.40s ease 0.40s,
                    transform 0.40s cubic-bezier(0.34, 1.20, 0.64, 1) 0.38s;
                pointer-events: none;
            }
            .split-card:hover .left-panel-inner {
                opacity: 1;
                transform: translateX(0);
                pointer-events: auto;
            }

            /* ── Right panel: subtle rightward nudge when panel opens ── */
            .right-panel {
                transition: padding-left 0.70s cubic-bezier(0.34, 1.20, 0.64, 1);
            }
            .split-card:hover .right-panel {
                /* no padding change needed — flex naturally handles the reflow */
            }
        }

        /* ── Mobile: hide left panel entirely, no stripe ── */
        @media (max-width: 767px) {
            .left-panel { display: none !important; }
        }

        /* ════════════════════════════════════════
           SPLIT CARD outer wrapper
           (max-width widens slightly on expand for breathing room)
        ════════════════════════════════════════ */
        .card-wrapper {
            width: 100%;
            max-width: 520px;
            transition: max-width 0.70s cubic-bezier(0.34, 1.20, 0.64, 1);
        }
        @media (min-width: 768px) {
            .card-wrapper:hover {
                max-width: 960px;
            }
        }

    </style>
</head>

<body class="antialiased min-h-screen transition-all duration-700 overflow-hidden flex items-center justify-center"
      :class="darkMode ? 'bg-[#020617] text-white' : 'bg-[#E6E6E6] text-slate-900'">

    <!-- ════════════════════════════════════════
         PAGE BACKGROUND
    ════════════════════════════════════════ -->
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="bg-animated-dark fixed inset-0 z-0"></div>

    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 z-0"
         style="background:linear-gradient(233deg,rgba(238,174,202,1) 0%,rgba(185,182,220,1) 17%,rgba(168,184,226,1) 46%,rgba(157,186,230,1) 78%,rgba(148,187,233,1) 90%);
                background-size:300% 300%; animation:grad-shift 18s ease infinite;">
    </div>

    <!-- Dark blobs -->
    <div x-show="darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob    absolute top-[-12%] left-[-8%]    w-[48%] h-[48%] bg-blue-600/12  blur-[150px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-12%] right-[-8%] w-[48%] h-[48%] bg-indigo-700/12 blur-[150px] rounded-full"></div>
        <div class="blob-d3 absolute top-[38%] left-[42%]    w-[28%] h-[28%] bg-blue-700/7   blur-[110px] rounded-full"></div>
        <div class="blob    absolute top-[55%] left-[8%]     w-[22%] h-[22%] bg-violet-600/7 blur-[90px]  rounded-full"></div>
    </div>

    <!-- Light blobs -->
    <div x-show="!darkMode" x-transition.opacity.duration.500ms
         class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob    absolute top-[-10%] right-[5%] w-[40%] h-[40%] bg-blue-300/25  blur-[120px] rounded-full"></div>
        <div class="blob-d2 absolute bottom-[-5%] left-[5%] w-[35%] h-[35%] bg-pink-300/20  blur-[100px] rounded-full"></div>
        <div class="blob-d3 absolute top-[40%] left-[40%]  w-[25%] h-[25%] bg-purple-300/18 blur-[85px]  rounded-full"></div>
    </div>

    <!-- Particles (dark) -->
    <div x-show="darkMode" class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="particle w-1   h-1   bg-blue-400/50"   style="left:10%;animation-duration:9s;animation-delay:0s;"></div>
        <div class="particle w-1.5 h-1.5 bg-indigo-400/40" style="left:26%;animation-duration:12s;animation-delay:-3s;"></div>
        <div class="particle w-1   h-1   bg-blue-300/45"   style="left:50%;animation-duration:8s;animation-delay:-5s;"></div>
        <div class="particle w-2   h-2   bg-violet-400/30" style="left:68%;animation-duration:14s;animation-delay:-1s;"></div>
        <div class="particle w-1   h-1   bg-blue-400/30"   style="left:82%;animation-duration:10s;animation-delay:-7s;"></div>
        <div class="particle w-1.5 h-1.5 bg-blue-500/35"  style="left:93%;animation-duration:11s;animation-delay:-4s;"></div>
    </div>

    <!-- ════════════════════════════════════════
         THEME TOGGLE
    ════════════════════════════════════════ -->
    <button class="theme-toggle absolute top-6 right-8 z-50 cursor-pointer"
            @click="toggleTheme()" aria-label="Toggle theme">
        <div class="relative">
            <div x-show="darkMode" class="absolute inset-0 bg-yellow-400/25 blur-xl rounded-full scale-150"></div>
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
         AMBIENT GLOW BLOBS (behind card)
    ════════════════════════════════════════ -->
    <div class="glow-indigo w-[480px] h-[360px] bg-indigo-500/18 top-1/2 left-1/2 -translate-x-3/4 -translate-y-1/2 z-[1]"></div>
    <div class="glow-indigo w-[360px] h-[300px] bg-blue-400/14 top-1/2 left-1/2 -translate-x-1/4 -translate-y-1/2 z-[1]"></div>

    <!-- ════════════════════════════════════════════════════
         MAIN WRAPPER
    ════════════════════════════════════════════════════ -->
    <div class="relative z-10 w-full px-4 py-8 md:py-0 flex items-center justify-center min-h-screen">

        <!--
            card-wrapper: controls outer max-width.
            Starts narrow (form-only), expands on hover via CSS.
        -->
        <div class="card-wrapper">

            <!-- ════════════════════════════════════════
                 SPLIT CARD
            ════════════════════════════════════════ -->
            <div class="split-card w-full rounded-[32px] shadow-2xl overflow-hidden
                        flex flex-col md:flex-row fade-up transition-all duration-500"
                 :class="darkMode ? 'glass-card-dark' : 'glass-card-light'">

                <!-- ────────────────────────────────────────
                     LEFT PANEL (collapses to stripe by default)
                ──────────────────────────────────────── -->
                <div class="left-panel relative flex-shrink-0
                            flex flex-col items-center justify-between overflow-hidden
                            bg-gradient-to-br from-indigo-700 via-blue-700 to-indigo-900 py-7 px-10"
                     style="min-height:440px;">

                    <!-- Geometric grid overlay -->
                    <div class="geo-pattern absolute inset-0 z-0 opacity-70"></div>

                    <!-- Floating shapes -->
                    <div class="geo-shape-a absolute top-10  right-12 w-20 h-20 border border-white/15 rounded-2xl z-0"></div>
                    <div class="geo-shape-b absolute bottom-24 left-8  w-14 h-14 border border-white/10 rounded-xl  rotate-12 z-0"></div>
                    <div class="geo-shape-c absolute top-1/2  left-6  w-9  h-9  border border-blue-300/20 rounded-lg z-0"></div>
                    <div class="geo-shape-a absolute bottom-10 right-8 w-10 h-10 bg-white/5  rounded-full z-0" style="animation-delay:-3s;"></div>
                    <div class="geo-shape-b absolute top-20 left-1/2  w-6  h-6  bg-indigo-300/10 rounded-full z-0" style="animation-delay:-6s;"></div>

                    <!-- ── Stripe hint: visible only when panel is collapsed ── -->
                    <div class="stripe-hint z-20">
                        <!-- Three right-pointing chevrons stacked -->
                        <svg class="w-3 h-3 text-white/60" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <svg class="w-3 h-3 text-white/35" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <svg class="w-3 h-3 text-white/18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>

                    <!-- ── All panel content (fades in after panel springs open) ── -->
                    <div class="left-panel-inner relative z-10 w-full h-full flex flex-col items-center justify-between">

                        <!-- Top: Branding -->
                        <div class="text-center select-none w-full">
                            <div class="animate-float mb-2 logo-glow-dark flex justify-center">
                                <img src="{{ asset('logo.png') }}"
                                     alt="Classly Logo"
                                     class="logo-img w-16 h-16 object-contain rounded-2xl"
                                     draggable="false">
                            </div>
                            <div class="classly-glow-dark">
                                <h1 class="text-5xl leading-none font-black tracking-[-0.02em]">
                                    <span class="brand-shimmer brand-class" data-text="Class">Class</span><span
                                          class="brand-shimmer brand-ly"   data-text="ly">ly</span>
                                </h1>
                            </div>
                            <!-- Divider -->
                            <div class="mt-3 mb-2 mx-auto w-12 h-px bg-gradient-to-r from-transparent via-blue-300/50 to-transparent"></div>
                            <!-- Headline -->
                            <h2 class="text-white font-extrabold text-2xl leading-tight tracking-tight">
                                Automated<br>Scheduling Hub
                            </h2>
                            <!-- Descriptor -->
                            <p class="mt-3 text-blue-200/65 text-[11px] font-semibold tracking-wide leading-relaxed max-w-[230px] mx-auto">
                                Streamline academic calendars, faculty loads, and room assignments — all in one intelligent platform.
                            </p>
                        </div>

                        <!-- Middle: Illustration mockup placeholder -->
                        <div class="w-full flex justify-center my-3">
                            <div class="illus-mock w-full max-w-[260px] rounded-2xl overflow-hidden"
                                 style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.10); backdrop-filter:blur(8px);">
                                <!-- Mock header bar -->
                                <div class="flex items-center gap-1.5 px-4 py-3 border-b border-white/8">
                                    <span class="w-2 h-2 rounded-full bg-red-400/60"></span>
                                    <span class="w-2 h-2 rounded-full bg-yellow-400/60"></span>
                                    <span class="w-2 h-2 rounded-full bg-green-400/60"></span>
                                    <span class="ml-2 text-[8px] font-bold tracking-widest uppercase text-blue-200/40">Schedule Board</span>
                                </div>
                                <!-- Mock rows -->
                                <div class="px-4 py-3 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-2.5 rounded-full bg-blue-400/30"></div>
                                        <div class="flex-1 h-2.5 rounded-full bg-white/10"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8  h-2.5 rounded-full bg-indigo-400/30"></div>
                                        <div class="flex-1 h-2.5 rounded-full bg-white/8"></div>
                                        <div class="w-5  h-2.5 rounded-full bg-blue-300/20"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-14 h-2.5 rounded-full bg-blue-500/25"></div>
                                        <div class="w-10 h-2.5 rounded-full bg-white/10"></div>
                                        <div class="flex-1 h-2.5 rounded-full bg-indigo-300/15"></div>
                                    </div>
                                    <div class="w-full h-px bg-white/6 my-1"></div>
                                    <div class="flex gap-2">
                                        <div class="h-10 w-[30%] rounded-lg bg-blue-500/20 border border-blue-300/15"></div>
                                        <div class="h-10 w-[35%] rounded-lg bg-indigo-500/15 border border-indigo-300/10"></div>
                                        <div class="h-10 flex-1  rounded-lg bg-white/6   border border-white/8"></div>
                                    </div>
                                </div>
                                <!-- Mock footer -->
                                <div class="px-4 py-2.5 border-t border-white/6 flex items-center justify-between">
                                    <div class="flex gap-1">
                                        <div class="w-5 h-1.5 rounded-full bg-blue-400/40"></div>
                                        <div class="w-3 h-1.5 rounded-full bg-white/15"></div>
                                        <div class="w-3 h-1.5 rounded-full bg-white/10"></div>
                                    </div>
                                    <div class="w-12 h-4 rounded-md bg-blue-500/35 border border-blue-300/20"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom: Footer blurb -->
                        <div class="text-center">
                            <p class="text-[9px] font-bold tracking-[0.30em] uppercase text-blue-200/30">
                                Professional Academy of the Philippines
                            </p>
                        </div>

                    </div>
                    <!-- /left-panel-inner -->

                </div>
                <!-- /LEFT PANEL -->

                <!-- ────────────────────────────────────────
                     RIGHT PANEL  (always visible, form lives here)
                ──────────────────────────────────────── -->
                <div class="right-panel flex-1 flex flex-col items-center justify-center px-8 sm:px-12 py-7">

                    <!-- Mobile-only branding -->
                    <div class="md:hidden flex flex-col items-center mb-8 select-none fade-up">
                        <div class="animate-float mb-2"
                             :class="darkMode ? 'logo-glow-dark' : 'logo-glow-light'">
                            <img src="{{ asset('logo.png') }}"
                                 alt="Classly Logo"
                                 class="logo-img w-16 h-16 object-contain rounded-2xl"
                                 draggable="false">
                        </div>
                        <div :class="darkMode ? 'classly-glow-dark' : ''">
                            <h1 class="text-[3rem] leading-none font-black tracking-[-0.02em]">
                                <span class="brand-shimmer brand-class" data-text="Class">Class</span><span
                                      class="brand-shimmer brand-ly"   data-text="ly">ly</span>
                            </h1>
                        </div>
                        <p class="mt-1 text-[9px] font-bold tracking-[0.38em] uppercase transition-colors"
                           :class="darkMode ? 'text-blue-200/45' : 'text-slate-600/55'">
                            Authentication Gateway
                        </p>
                    </div>

                    <!-- Form header -->
                    <div class="w-full max-w-sm fade-up-d1">
                        <h2 class="text-2xl font-extrabold tracking-tight"
                            :class="darkMode ? 'text-white' : 'text-slate-800'">
                            Welcome Back
                        </h2>
                        <p class="mt-1 text-[10px] font-bold tracking-[0.22em] uppercase transition-colors"
                           :class="darkMode ? 'text-blue-300/50' : 'text-slate-500/70'">
                            Sign in to your account to continue
                        </p>
                        <div class="mt-2 w-8 h-0.5 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                    </div>

                    <!-- ── FORM ── -->
                    <form method="POST" action="{{ route('login') }}"
                          class="w-full max-w-sm mt-4 space-y-4 fade-up-d2">
                        @csrf

                        <!-- Username / Email -->
                        <div class="space-y-1.5">
                            <label class="field-label pl-1 transition-colors"
                                   :class="darkMode ? 'text-slate-400/70' : 'text-slate-500/80'">
                                Username / Email
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-4 flex items-center text-blue-500 font-black text-sm select-none pointer-events-none">@</span>
                                <input type="text"
                                       name="email"
                                       required autofocus
                                       autocomplete="username"
                                       class="w-full pl-10 pr-5 py-3 rounded-2xl text-sm font-semibold"
                                       :class="darkMode ? 'input-dark' : 'input-light'"
                                       placeholder="username or email address"
                                       value="{{ old('email') }}">
                            </div>
                            @error('email')
                                <span class="block text-red-400 text-[10px] font-bold pl-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between pl-1 pr-0.5">
                                <label class="field-label transition-colors"
                                       :class="darkMode ? 'text-slate-400/70' : 'text-slate-500/80'">
                                    Password
                                </label>
                                <a href="{{ route('password.request') }}"
                                   class="field-label text-blue-500 hover:text-blue-400 transition-colors hover:underline underline-offset-2">
                                    Forgot?
                                </a>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-4 flex items-center text-blue-500 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </span>
                                <input :type="showPass ? 'text' : 'password'"
                                       name="password" required
                                       autocomplete="current-password"
                                       class="w-full pl-10 pr-12 py-3 rounded-2xl text-sm font-semibold"
                                       :class="darkMode ? 'input-dark' : 'input-light'"
                                       placeholder="••••••••">
                                <button type="button"
                                        @click="showPass = !showPass"
                                        class="absolute inset-y-0 right-4 flex items-center transition-colors"
                                        :class="darkMode ? 'text-slate-500 hover:text-blue-400' : 'text-slate-400 hover:text-blue-600'">
                                    <template x-if="!showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </template>
                                    <template x-if="showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                        <!-- Remember Me -->
                        <div class="flex items-center px-1 fade-up-d3">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input type="checkbox" name="remember"
                                       class="w-3.5 h-3.5 rounded border-slate-600 bg-transparent text-blue-600 focus:ring-blue-500 focus:ring-1 cursor-pointer">
                                <span class="field-label opacity-40 group-hover:opacity-75 transition-opacity cursor-pointer"
                                      :class="darkMode ? 'text-white' : 'text-slate-700'">
                                    Remember Me
                                </span>
                            </label>
                        </div>

                        <!-- Submit -->
                        <div class="fade-up-d3 pt-1">
                            <button type="submit"
                                    class="submit-btn w-full py-3 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.25em] active:scale-[0.99]">
                                Sign In Account
                            </button>
                        </div>

                    </form>

                    <!-- Footer -->
                    <div class="w-full max-w-sm text-center mt-5 space-y-1 fade-up-d4">
                        <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
                           :class="darkMode ? 'text-blue-300/22 opacity-[0.22]' : 'text-slate-600/28 opacity-[0.28]'">
                            Professional Academy of the Philippines &copy; {{ date('Y') }}
                        </p>
                        <p class="footer-text text-[9px] uppercase font-bold tracking-[0.28em] transition-colors"
                           :class="darkMode ? 'text-blue-300/18 opacity-[0.18]' : 'text-slate-600/22 opacity-[0.22]'">
                            Classly &mdash; Developed by DJS
                        </p>
                    </div>

                </div>
                <!-- /RIGHT PANEL -->

            </div>
            <!-- /SPLIT CARD -->

        </div>
        <!-- /card-wrapper -->

    </div>
    <!-- /MAIN WRAPPER -->

</body>
</html>