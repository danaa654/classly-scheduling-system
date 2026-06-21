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

        /* ════════════════════════════════════════
           MESH GRID (dark only)
        ════════════════════════════════════════ */
        .mesh-grid {
            position: fixed; inset: 0; pointer-events: none; z-index: 2;
            opacity: 0.022;
            background-image:
                linear-gradient(rgba(255,255,255,0.6) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.6) 1px, transparent 1px);
            background-size: 72px 72px;
        }

        /* ════════════════════════════════════════
           FIREFLIES
        ════════════════════════════════════════ */
        .fireflies { position:fixed; inset:0; pointer-events:none; z-index:3; overflow:visible; }
        .firefly {
            position:absolute; border-radius:999px; filter:blur(3px); opacity:0;
            animation-name: firefly-float, firefly-flicker;
            animation-timing-function: ease-in-out, linear;
            animation-iteration-count: infinite, infinite;
            will-change: transform, opacity;
        }
        @keyframes firefly-float {
            0%   { transform: translate(0,0) scale(0.6); }
            25%  { transform: translate(24px,-18px) scale(1.05); }
            50%  { transform: translate(-18px,-36px) scale(0.9); }
            75%  { transform: translate(12px,-12px) scale(1.08); }
            100% { transform: translate(0,0) scale(0.6); }
        }
        @keyframes firefly-flicker {
            0%   { opacity:0; }  6% { opacity:1; }  22% { opacity:0.35; }
            40%  { opacity:0.9; } 60% { opacity:0.55; } 80% { opacity:0.95; } 100% { opacity:0; }
        }
        .firefly-light {
            background: radial-gradient(circle at 30% 30%, rgba(255,255,230,1) 0%, rgba(255,220,120,0.9) 25%, rgba(255,180,60,0.6) 50%, rgba(255,180,60,0) 70%);
            box-shadow: 0 0 20px rgba(255,210,120,0.85), 0 0 40px rgba(255,150,50,0.18);
        }
        .firefly-dark {
            background: radial-gradient(circle at 30% 30%, rgba(180,230,255,1) 0%, rgba(110,200,255,0.9) 30%, rgba(60,150,255,0.6) 55%, rgba(50,120,255,0) 75%);
            box-shadow: 0 0 26px rgba(110,200,255,0.95), 0 0 48px rgba(40,120,255,0.18);
            filter: blur(2px);
        }

        /* ════════════════════════════════════════
           LIGHT MODE BUBBLES
        ════════════════════════════════════════ */
        .bubble { position:absolute; border-radius:50%; pointer-events:none; }
        .bubble-large-pink {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.04) 0%, rgba(253,232,244,0.08) 50%,
                rgba(251,182,206,0.18) 80%, rgba(244,114,182,0.22) 100%);
            border: 1.5px solid rgba(244,114,182,0.20);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.28), inset 0 2px 16px rgba(255,255,255,0.14),
                        0 12px 60px rgba(244,114,182,0.10), 0 0 0 0.5px rgba(251,113,133,0.08);
        }
        .bubble-large-blue {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.04) 0%, rgba(219,234,254,0.08) 50%,
                rgba(147,197,253,0.18) 80%, rgba(96,165,250,0.22) 100%);
            border: 1.5px solid rgba(96,165,250,0.22);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.28), inset 0 2px 16px rgba(255,255,255,0.14),
                        0 12px 60px rgba(96,165,250,0.12), 0 0 0 0.5px rgba(96,165,250,0.08);
        }
        .bubble-sm-pink {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%, rgba(253,164,175,0.10) 55%,
                rgba(251,113,133,0.22) 85%, rgba(244,63,94,0.26) 100%);
            border: 1.5px solid rgba(251,113,133,0.28);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.35), inset 0 1px 10px rgba(255,255,255,0.20),
                        0 6px 28px rgba(251,113,133,0.14);
        }
        .bubble-sm-blue {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%, rgba(147,197,253,0.10) 55%,
                rgba(96,165,250,0.22) 85%, rgba(59,130,246,0.26) 100%);
            border: 1.5px solid rgba(96,165,250,0.28);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.35), inset 0 1px 10px rgba(255,255,255,0.20),
                        0 6px 28px rgba(96,165,250,0.14);
        }
        .bubble-sm-red {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%, rgba(252,165,165,0.10) 55%,
                rgba(239,68,68,0.20) 85%, rgba(220,38,38,0.24) 100%);
            border: 1.5px solid rgba(239,68,68,0.26);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.35), inset 0 1px 10px rgba(255,255,255,0.20),
                        0 6px 26px rgba(239,68,68,0.12);
        }
        .bubble-sm-indigo {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%, rgba(196,181,253,0.10) 55%,
                rgba(139,92,246,0.20) 85%, rgba(109,40,217,0.24) 100%);
            border: 1.5px solid rgba(139,92,246,0.26);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.35), inset 0 1px 10px rgba(255,255,255,0.20),
                        0 6px 26px rgba(139,92,246,0.12);
        }

        /* Bubble drift animations */
        @keyframes bubble-drift-a { 0%,100% { transform:translate(0,0) scale(1); } 33% { transform:translate(10px,-14px) scale(1.03); } 66% { transform:translate(-8px,8px) scale(0.97); } }
        @keyframes bubble-drift-b { 0%,100% { transform:translate(0,0) scale(1); } 40% { transform:translate(-12px,-10px) scale(1.04); } 70% { transform:translate(8px,12px) scale(0.98); } }
        @keyframes bubble-drift-c { 0%,100% { transform:translate(0,0) scale(1); } 30% { transform:translate(14px,10px) scale(1.05); } 65% { transform:translate(-10px,-8px) scale(0.96); } }
        .light-circle-pulse  { animation: bubble-drift-a 9s  ease-in-out infinite; }
        .light-circle-pulse2 { animation: bubble-drift-b 12s ease-in-out infinite; animation-delay:-4s; }
        .bubble-drift-c      { animation: bubble-drift-c 15s ease-in-out infinite; animation-delay:-7s; }
        .bubble-drift-d      { animation: bubble-drift-a 11s ease-in-out infinite; animation-delay:-3s; }

        /* Light dot grid */
        .light-dot-grid {
            position:absolute; pointer-events:none;
            background-image: radial-gradient(circle, rgba(100,116,139,0.22) 1.2px, transparent 1.2px);
            background-size: 22px 22px;
        }
        /* Plus marks */
        .light-plus { position:absolute; pointer-events:none; font-weight:900; line-height:1; user-select:none; font-family:'Sora',sans-serif; }

        /* ════════════════════════════════════════
           DARK MODE GLOW BUBBLES
        ════════════════════════════════════════ */
        .dark-bubble { position:absolute; border-radius:50%; pointer-events:none; }
        .dark-bubble-large-blue {
            background: radial-gradient(circle at 40% 35%, rgba(96,165,250,0.04) 0%, rgba(59,130,246,0.06) 45%, rgba(29,78,216,0.08) 75%, rgba(29,78,216,0.04) 100%);
            border: 1px solid rgba(96,165,250,0.10);
            box-shadow: inset 0 0 60px rgba(59,130,246,0.06), 0 0 80px rgba(59,130,246,0.12), 0 0 140px rgba(59,130,246,0.07);
        }
        .dark-bubble-large-red {
            background: radial-gradient(circle at 40% 35%, rgba(239,68,68,0.03) 0%, rgba(220,38,38,0.06) 45%, rgba(185,28,28,0.07) 75%, rgba(185,28,28,0.03) 100%);
            border: 1px solid rgba(239,68,68,0.09);
            box-shadow: inset 0 0 60px rgba(239,68,68,0.05), 0 0 80px rgba(239,68,68,0.10), 0 0 140px rgba(239,68,68,0.06);
        }
        .dark-bubble-sm-blue {
            background: radial-gradient(circle at 40% 35%, rgba(147,197,253,0.05) 0%, rgba(96,165,250,0.08) 55%, rgba(59,130,246,0.06) 85%, transparent 100%);
            border: 1px solid rgba(96,165,250,0.12);
            box-shadow: 0 0 30px rgba(59,130,246,0.14), 0 0 60px rgba(59,130,246,0.07);
        }
        .dark-bubble-sm-indigo {
            background: radial-gradient(circle at 40% 35%, rgba(196,181,253,0.05) 0%, rgba(139,92,246,0.08) 55%, rgba(109,40,217,0.06) 85%, transparent 100%);
            border: 1px solid rgba(139,92,246,0.12);
            box-shadow: 0 0 30px rgba(139,92,246,0.14), 0 0 60px rgba(139,92,246,0.07);
        }
        .dark-bubble-sm-cyan {
            background: radial-gradient(circle at 40% 35%, rgba(103,232,249,0.04) 0%, rgba(34,211,238,0.07) 55%, rgba(6,182,212,0.05) 85%, transparent 100%);
            border: 1px solid rgba(34,211,238,0.10);
            box-shadow: 0 0 26px rgba(34,211,238,0.13), 0 0 50px rgba(34,211,238,0.06);
        }
        .dark-bubble-sm-red {
            background: radial-gradient(circle at 40% 35%, rgba(252,165,165,0.04) 0%, rgba(239,68,68,0.07) 55%, rgba(220,38,38,0.05) 85%, transparent 100%);
            border: 1px solid rgba(239,68,68,0.10);
            box-shadow: 0 0 24px rgba(239,68,68,0.12), 0 0 48px rgba(239,68,68,0.06);
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
      :class="darkMode ? 'bg-[#020617] text-white' : 'bg-[#f0f5ff] text-slate-900'">

    <!-- ════════════════════════════════════════
         LAYER 0 : PURE BASE COLOUR
    ════════════════════════════════════════ -->
    <div class="fixed inset-0 transition-all duration-700" style="z-index:0;"
         :style="darkMode
            ? 'background: radial-gradient(circle at 0% 100%, rgba(239,68,68,.12), transparent 35%), radial-gradient(circle at 100% 100%, rgba(59,130,246,.15), transparent 35%), linear-gradient(135deg,#020617 0%,#081225 50%,#0f172a 100%);'
            : 'background: linear-gradient(145deg, #f0f5ff 0%, #fdf0f3 35%, #eef4ff 65%, #fdf5f8 100%);'">
    </div>

    <!-- ════════════════════════════════════════
         LAYER 1 : ANIMATED BACKGROUND
    ════════════════════════════════════════ -->
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 bg-animated-dark" style="z-index:1; opacity:0.95;"></div>

    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0" style="z-index:1; opacity:1;
                background: linear-gradient(145deg, #f0f5ff 0%, #fdf0f3 35%, #eef4ff 65%, #fdf5f8 100%);"></div>

    <!-- ════════════════════════════════════════
         LAYER 2 : GRADIENT MESH BLOBS
    ════════════════════════════════════════ -->

    <!-- Dark mesh blobs -->
    <div x-show="darkMode" x-transition.opacity.duration.600ms
         class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:2;">
        <div class="blob absolute rounded-full"
             style="top:-12%; left:-8%; width:55%; height:55%;
                    background: radial-gradient(circle, rgba(29,78,216,0.40) 0%, rgba(29,78,216,0) 68%);
                    filter: blur(90px);"></div>
        <div class="blob-d2 absolute rounded-full"
             style="bottom:-10%; right:-6%; width:48%; height:48%;
                    background: radial-gradient(circle, rgba(185,28,28,0.28) 0%, rgba(185,28,28,0) 68%);
                    filter: blur(90px);"></div>
        <div class="blob-d3 absolute rounded-full"
             style="top:32%; left:38%; width:38%; height:38%;
                    background: radial-gradient(circle, rgba(79,46,209,0.16) 0%, rgba(79,46,209,0) 68%);
                    filter: blur(110px);"></div>
        <div class="absolute bottom-0 left-0 right-0 pointer-events-none"
             style="height:35%; background: linear-gradient(to top, rgba(5,12,28,0.90), transparent);"></div>
    </div>

    <!-- Dark mode glow bubbles -->
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:3;">
        <div class="dark-bubble dark-bubble-large-blue light-circle-pulse2"
             style="width:640px; height:640px; right:-200px; top:10%;"></div>
        <div class="dark-bubble dark-bubble-large-red light-circle-pulse"
             style="width:700px; height:700px; bottom:-260px; left:-200px;"></div>
        <div class="dark-bubble dark-bubble-sm-blue light-circle-pulse"
             style="width:130px; height:130px; top:7%; left:5%;"></div>
        <div class="dark-bubble dark-bubble-sm-indigo light-circle-pulse2"
             style="width:150px; height:150px; top:5%; right:12%;"></div>
        <div class="dark-bubble dark-bubble-sm-cyan bubble-drift-c"
             style="width:88px; height:88px; top:42%; left:8%;"></div>
        <div class="dark-bubble dark-bubble-sm-red bubble-drift-d"
             style="width:64px; height:64px; top:10%; left:44%;"></div>
        <div class="dark-bubble dark-bubble-sm-blue light-circle-pulse"
             style="width:76px; height:76px; bottom:22%; right:26%; animation-delay:-5s;"></div>
        <div class="dark-bubble dark-bubble-sm-indigo bubble-drift-c"
             style="width:46px; height:46px; top:55%; right:18%; animation-delay:-2s;"></div>
        <div class="dark-bubble dark-bubble-sm-cyan bubble-drift-d"
             style="width:38px; height:38px; bottom:18%; left:38%; animation-delay:-8s;"></div>
        <div class="dark-bubble dark-bubble-sm-red light-circle-pulse2"
             style="width:50px; height:50px; top:33%; right:7%; animation-delay:-1s;"></div>
    </div>

    <!-- Light mode bubbles + accents -->
    <div x-show="!darkMode" x-transition.opacity.duration.600ms
         class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:2;">
        <div class="bubble bubble-large-pink"
             style="width:700px; height:700px; bottom:-260px; left:-200px;"></div>
        <div class="bubble bubble-large-blue"
             style="width:640px; height:640px; right:-200px; top:10%;"></div>
        <div class="bubble bubble-sm-pink light-circle-pulse"
             style="width:130px; height:130px; top:7%; left:5%;"></div>
        <div class="bubble bubble-sm-blue light-circle-pulse2"
             style="width:150px; height:150px; top:5%; right:12%;"></div>
        <div class="bubble bubble-sm-indigo bubble-drift-c"
             style="width:88px; height:88px; top:42%; left:8%;"></div>
        <div class="bubble bubble-sm-red bubble-drift-d"
             style="width:64px; height:64px; top:10%; left:44%;"></div>
        <div class="bubble bubble-sm-blue light-circle-pulse"
             style="width:76px; height:76px; bottom:22%; right:26%; animation-delay:-5s;"></div>
        <div class="bubble bubble-sm-pink bubble-drift-c"
             style="width:46px; height:46px; top:55%; right:18%; animation-delay:-2s;"></div>
        <div class="bubble bubble-sm-indigo bubble-drift-d"
             style="width:38px; height:38px; bottom:18%; left:38%; animation-delay:-8s;"></div>
        <div class="bubble bubble-sm-red light-circle-pulse2"
             style="width:50px; height:50px; top:33%; right:7%; animation-delay:-1s;"></div>
    </div>

    <!-- Light mode dot grids -->
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:2;">
        <div class="light-dot-grid" style="width:250px; height:230px; left:8%; bottom:14%;"></div>
        <div class="light-dot-grid" style="width:220px; height:200px; right:14%; top:24%;"></div>
    </div>

    <!-- Light mode plus marks -->
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:3;">
        <div class="light-plus" style="left:20%; top:27%; font-size:22px; color:rgba(239,68,68,0.38);">+</div>
        <div class="light-plus" style="left:7%; top:58%; font-size:15px; color:rgba(251,113,133,0.32);">+</div>
        <div class="light-plus" style="left:27%; bottom:26%; font-size:13px; color:rgba(96,165,250,0.30);">+</div>
        <div class="light-plus" style="right:31%; top:13%; font-size:18px; color:rgba(96,165,250,0.38);">+</div>
        <div class="light-plus" style="right:10%; top:65%; font-size:20px; color:rgba(96,165,250,0.34);">+</div>
        <div class="light-plus" style="right:23%; bottom:21%; font-size:16px; color:rgba(248,113,113,0.32);">+</div>
        <div class="light-plus" style="left:55%; top:7%; font-size:14px; color:rgba(147,197,253,0.40);">+</div>
        <div class="light-plus" style="right:46%; top:5%; font-size:13px; color:rgba(239,68,68,0.30);">+</div>
    </div>

    <!-- Mesh grid (dark only) -->
    <div x-show="darkMode" class="mesh-grid"></div>

    <!-- Floating particles (dark) -->
    <div x-show="darkMode" class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:3;">
        <div class="particle w-1   h-1   bg-blue-400/50"   style="left:10%;animation-duration:9s;animation-delay:0s;"></div>
        <div class="particle w-1.5 h-1.5 bg-indigo-400/38" style="left:25%;animation-duration:13s;animation-delay:-3s;"></div>
        <div class="particle w-1   h-1   bg-blue-300/45"   style="left:42%;animation-duration:8s;animation-delay:-6s;"></div>
        <div class="particle w-2   h-2   bg-violet-400/28" style="left:61%;animation-duration:15s;animation-delay:-1s;"></div>
        <div class="particle w-1   h-1   bg-red-400/33"    style="left:77%;animation-duration:11s;animation-delay:-4s;"></div>
        <div class="particle w-1.5 h-1.5 bg-blue-500/38"  style="left:90%;animation-duration:10s;animation-delay:-7s;"></div>
    </div>

    <!-- Fireflies (dark) -->
    <div x-show="darkMode" class="fireflies" aria-hidden="true">
        <div class="firefly firefly-dark" style="left:8%;  top:18%; width:6px; height:6px; animation-duration:9s; animation-delay:-1s;"></div>
        <div class="firefly firefly-dark" style="left:22%; top:38%; width:8px; height:8px; animation-duration:13s; animation-delay:-3s;"></div>
        <div class="firefly firefly-dark" style="left:34%; top:12%; width:5px; height:5px; animation-duration:11s; animation-delay:-4s;"></div>
        <div class="firefly firefly-dark" style="left:48%; top:46%; width:7px; height:7px; animation-duration:10s; animation-delay:-2s;"></div>
        <div class="firefly firefly-dark" style="left:62%; top:28%; width:6px; height:6px; animation-duration:14s; animation-delay:-6s;"></div>
        <div class="firefly firefly-dark" style="left:76%; top:55%; width:9px; height:9px; animation-duration:12s; animation-delay:-5s;"></div>
        <div class="firefly firefly-dark" style="left:88%; top:22%; width:5px; height:5px; animation-duration:8s; animation-delay:-7s;"></div>
        <div class="firefly firefly-dark" style="left:52%; top:72%; width:6px; height:6px; animation-duration:16s; animation-delay:-9s;"></div>
    </div>

    <!-- Fireflies (light) -->
    <div x-show="!darkMode" class="fireflies" aria-hidden="true">
        <div class="firefly firefly-light" style="left:6%;  top:26%; width:7px; height:7px; animation-duration:10s; animation-delay:-2s;"></div>
        <div class="firefly firefly-light" style="left:18%; top:44%; width:6px; height:6px; animation-duration:13s; animation-delay:-4s;"></div>
        <div class="firefly firefly-light" style="left:30%; top:14%; width:5px; height:5px; animation-duration:9s; animation-delay:-1s;"></div>
        <div class="firefly firefly-light" style="left:46%; top:52%; width:8px; height:8px; animation-duration:12s; animation-delay:-3s;"></div>
        <div class="firefly firefly-light" style="left:60%; top:30%; width:6px; height:6px; animation-duration:14s; animation-delay:-6s;"></div>
        <div class="firefly firefly-light" style="left:74%; top:60%; width:9px; height:9px; animation-duration:11s; animation-delay:-5s;"></div>
        <div class="firefly firefly-light" style="left:86%; top:20%; width:5px; height:5px; animation-duration:8s; animation-delay:-7s;"></div>
        <div class="firefly firefly-light" style="left:50%; top:78%; width:6px; height:6px; animation-duration:16s; animation-delay:-9s;"></div>
    </div>

    <!-- ════════════════════════════════════════
         THEME PILL TOGGLE (matches welcome page)
    ════════════════════════════════════════ -->
    <style>
        .theme-pill {
            position: relative; width: 130px; height: 46px; border-radius: 100px;
            overflow: hidden; cursor: pointer; border: none; padding: 0; flex-shrink: 0; background: transparent;
        }
        .theme-pill:focus-visible { outline: 2px solid rgba(99,102,241,0.6); outline-offset: 2px; }
        .theme-pill-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:opacity 0.45s ease; }
        .theme-pill-ring { position:absolute; inset:0; border-radius:100px; pointer-events:none; z-index:2; transition:box-shadow 0.45s ease; }
        .theme-pill-knob { position:absolute; top:50%; transform:translateY(-50%); width:38px; height:38px; border-radius:50%; overflow:hidden; z-index:3; transition:left 0.5s cubic-bezier(0.68,-0.55,0.27,1.55); }
        .theme-pill-knob img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:opacity 0.35s ease; border-radius:50%; }
        .theme-pill-glow { position:absolute; inset:-3px; border-radius:50%; z-index:4; pointer-events:none; transition:box-shadow 0.45s ease; }
    </style>
    <div class="fixed top-4 right-6 z-50">
        <button @click="toggleTheme()" class="theme-pill" :aria-label="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
            <img src="{{ asset('images/toggle/bg%20dark.jpg') }}"  alt="" class="theme-pill-img" :style="darkMode ? 'opacity:1' : 'opacity:0'">
            <img src="{{ asset('images/toggle/bg%20light.jpg') }}" alt="" class="theme-pill-img" :style="darkMode ? 'opacity:0' : 'opacity:1'">
            <div class="theme-pill-ring"
                 :style="darkMode
                    ? 'box-shadow: inset 0 0 0 1.5px rgba(80,40,200,0.55), 0 0 18px rgba(80,40,200,0.30)'
                    : 'box-shadow: inset 0 0 0 1.5px rgba(100,190,255,0.55), 0 0 18px rgba(100,190,255,0.25)'">
            </div>
            <div class="theme-pill-knob" :style="darkMode ? 'left:4px' : 'left:88px'">
                <img src="{{ asset('images/toggle/circle%20dark.jpg') }}"  alt="Dark"  :style="darkMode ? 'opacity:1' : 'opacity:0'">
                <img src="{{ asset('images/toggle/circle%20light.jpg') }}" alt="Light" :style="darkMode ? 'opacity:0' : 'opacity:1'">
                <div class="theme-pill-glow"
                     :style="darkMode
                        ? 'box-shadow: 0 0 0 2px rgba(60,40,150,0.9), 0 0 16px rgba(80,40,200,0.5)'
                        : 'box-shadow: 0 0 0 2px rgba(180,220,255,0.9), 0 0 16px rgba(100,190,255,0.6)'">
                </div>
            </div>
        </button>
    </div>

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