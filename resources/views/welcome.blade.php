<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        mouseX: 0,
        mouseY: 0,
        handleMouseMove(e) {
            this.mouseX = e.clientX;
            this.mouseY = e.clientY;
        }
      }"
      x-init="document.documentElement.classList.toggle('dark', darkMode)"
      @mousemove="handleMouseMove($event)"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classly | Academy OS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon"       type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon"              href="{{ asset('logo.png') }}">

    {{-- Prevent FOUC --}}
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        /* ══════════════════════════════════════════════════════
           FONTS
        ══════════════════════════════════════════════════════ */
        @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800;900&display=swap');
        * { font-family: 'Sora', sans-serif; }

        /* ══════════════════════════════════════════════════════
           RESET
        ══════════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }
        [x-cloak] { display: none !important; }

        /* ══════════════════════════════════════════════════════
           THEME PILL TOGGLE  —  exact copy from app.blade.php
        ══════════════════════════════════════════════════════ */
        .theme-pill {
            position: relative;
            width: 130px;
            height: 46px;
            border-radius: 100px;
            overflow: hidden;
            cursor: pointer;
            border: none;
            padding: 0;
            flex-shrink: 0;
            background: transparent;
        }
        .theme-pill:focus-visible {
            outline: 2px solid rgba(99, 102, 241, 0.6);
            outline-offset: 2px;
        }
        .theme-pill-bg {
            position: absolute;
            inset: 0;
            border-radius: 100px;
        }
        .theme-pill-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.45s ease;
        }
        .theme-pill-ring {
            position: absolute;
            inset: 0;
            border-radius: 100px;
            pointer-events: none;
            z-index: 2;
            transition: box-shadow 0.45s ease;
        }
        .theme-pill-knob {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            z-index: 3;
            transition: left 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .theme-pill-knob img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.35s ease;
            border-radius: 50%;
        }
        .theme-pill-glow {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            z-index: 4;
            pointer-events: none;
            transition: box-shadow 0.45s ease;
        }

        /* ══════════════════════════════════════════════════════
           TOP NAV BAR
        ══════════════════════════════════════════════════════ */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 200;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            transition: background 0.6s ease, border-color 0.6s ease;
        }
        .top-nav-dark {
            background: rgba(5, 10, 22, 0.80);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .top-nav-light {
            background: rgba(248, 250, 252, 0.88);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        /* Subtle top scan-line accent on the nav */
        .top-nav::after {
            content: '';
            position: absolute;
            top: 0;
            left: 5%;
            right: 5%;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59,130,246,0.5) 30%,
                rgba(239,68,68,0.3) 70%,
                transparent
            );
            pointer-events: none;
        }

        /* ══════════════════════════════════════════════════════
           BACKGROUND GRADIENTS (replaced building images)
        ══════════════════════════════════════════════════════ */
        .bg-gradient-light {
            background: linear-gradient(145deg,
                #f0f5ff 0%,
                #fdf0f3 35%,
                #eef4ff 65%,
                #fdf5f8 100%);
        }
        .bg-gradient-dark {
            /* deep indigo → teal with subtle depth */
            background: linear-gradient(160deg, #071020 0%, #08203a 30%, #0f2743 55%, #06384a 100%);
            background-size: 200% 200%;
            transform: scale(1.02);
            filter: saturate(0.9);
        }

        /* subtle slow shift */
        @keyframes bg-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .bg-animated {
            animation: bg-shift 24s ease-in-out infinite;
        }

        /* ══════════════════════════════════════════════════════
           FIRELIES / FLOATING GLOWING PARTICLES
        ══════════════════════════════════════════════════════ */
        .fireflies {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 3;
            overflow: visible;
        }

        .firefly {
            position: absolute;
            border-radius: 999px;
            filter: blur(3px);
            opacity: 0;
            transform: translate3d(0,0,0);
            animation-name: firefly-float, firefly-flicker;
            animation-timing-function: ease-in-out, linear;
            animation-iteration-count: infinite, infinite;
            will-change: transform, opacity;
        }

        /* float movement */
        @keyframes firefly-float {
            0%   { transform: translate(0, 0) scale(0.6); }
            25%  { transform: translate(24px, -18px) scale(1.05); }
            50%  { transform: translate(-18px, -36px) scale(0.9); }
            75%  { transform: translate(12px, -12px) scale(1.08); }
            100% { transform: translate(0, 0) scale(0.6); }
        }
        /* gentle flicker */
        @keyframes firefly-flicker {
            0%   { opacity: 0; }
            6%   { opacity: 1; }
            22%  { opacity: 0.35; }
            40%  { opacity: 0.9; }
            60%  { opacity: 0.55; }
            80%  { opacity: 0.95; }
            100% { opacity: 0; }
        }

        /* light-mode firefly look */
        .firefly-light {
            background: radial-gradient(circle at 30% 30%, rgba(255,255,230,1) 0%, rgba(255,220,120,0.9) 25%, rgba(255,180,60,0.6) 50%, rgba(255,180,60,0) 70%);
            box-shadow: 0 0 20px rgba(255,210,120,0.85), 0 0 40px rgba(255,150,50,0.18);
        }
        /* dark-mode firefly look */
        .firefly-dark {
            background: radial-gradient(circle at 30% 30%, rgba(180,230,255,1) 0%, rgba(110,200,255,0.9) 30%, rgba(60,150,255,0.6) 55%, rgba(50,120,255,0) 75%);
            box-shadow: 0 0 26px rgba(110,200,255,0.95), 0 0 48px rgba(40,120,255,0.18);
            filter: blur(2px);
        }

        /* ══════════════════════════════════════════════════════
           Animated gradient mesh blobs (keep existing)
        ══════════════════════════════════════════════════════ */
        @keyframes blob-drift {
            0%,  100% { transform: translate(0, 0)     scale(1);    }
            33%        { transform: translate(28px,-20px) scale(1.04); }
            66%        { transform: translate(-16px,14px) scale(0.97); }
        }
        .blob     { animation: blob-drift 14s ease-in-out infinite; }
        .blob-d2  { animation: blob-drift 19s ease-in-out infinite; animation-delay: -6s;  }
        .blob-d3  { animation: blob-drift 24s ease-in-out infinite; animation-delay: -12s; }

        /* ══════════════════════════════════════════════════════
           CURSOR SPOTLIGHT
        ══════════════════════════════════════════════════════ */
        .spotlight-mask {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 6;
            background: radial-gradient(
                circle 120px at var(--mouse-x, 50%) var(--mouse-y, 50%),
                transparent 0%,
                transparent 45%,
                rgba(0,0,0,0.30) 68%,
                rgba(0,0,0,0.38) 100%
            );
        }
        .spotlight-mask-light {
            background: radial-gradient(
                circle 120px at var(--mouse-x, 50%) var(--mouse-y, 50%),
                transparent 0%,
                transparent 45%,
                rgba(0,0,0,0.12) 68%,
                rgba(0,0,0,0.20) 100%
            );
        }
        .spotlight-glow {
            position: fixed;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 5;
            box-shadow:
                0 0 40px 0  rgba(59,130,246,0.22),
                0 0 24px 3px rgba(59,130,246,0.10),
                inset 0 0 40px 0 rgba(59,130,246,0.06);
        }
        .spotlight-glow-light {
            box-shadow:
                0 0 40px 0  rgba(30,64,175,0.14),
                0 0 24px 3px rgba(59,130,246,0.08),
                inset 0 0 40px 0 rgba(59,130,246,0.04);
        }

        /* ══════════════════════════════════════════════════════
           SUBTLE MESH GRID
        ══════════════════════════════════════════════════════ */
        .mesh-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 2;
            opacity: 0.022;
            background-image:
                linear-gradient(rgba(255,255,255,0.6) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.6) 1px, transparent 1px);
            background-size: 72px 72px;
        }

        /* ══════════════════════════════════════════════════════
           FLOATING PARTICLES  (dark only)
        ══════════════════════════════════════════════════════ */
        @keyframes particle-rise {
            0%   { transform: translateY(100vh) scale(0); opacity: 0;   }
            10%  { opacity: 0.55; }
            90%  { opacity: 0.25; }
            100% { transform: translateY(-30px)  scale(1); opacity: 0;  }
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: particle-rise linear infinite;
        }

        /* ══════════════════════════════════════════════════════
           LOGO FLOAT + GLOW
        ══════════════════════════════════════════════════════ */
        @keyframes float-logo {
            0%,  100% { transform: translateY(0px)   rotate(0deg)    scale(1);    }
            25%        { transform: translateY(-16px)  rotate( 0.8deg) scale(1.01); }
            50%        { transform: translateY(-22px)  rotate( 0.3deg) scale(1.02); }
            75%        { transform: translateY(-14px)  rotate(-0.5deg) scale(1.01); }
        }
        .animate-float { animation: float-logo 6s ease-in-out infinite; }

        @keyframes logo-glow-dark {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(59,130,246,0.55))
                        drop-shadow(0 0 40px rgba(59,130,246,0.20))
                        drop-shadow(0 0  8px rgba(239,68,68,0.30));
            }
            50% {
                filter: drop-shadow(0 0 36px rgba(59,130,246,0.90))
                        drop-shadow(0 0 70px rgba(59,130,246,0.35))
                        drop-shadow(0 0 22px rgba(239,68,68,0.55));
            }
        }
        @keyframes logo-glow-light {
            0%, 100% { filter: drop-shadow(0 8px 20px rgba(30,64,175,0.30)) drop-shadow(0 0 30px rgba(59,130,246,0.15)); }
            50%       { filter: drop-shadow(0 8px 36px rgba(30,64,175,0.55)) drop-shadow(0 0 50px rgba(59,130,246,0.30)); }
        }
        .logo-glow-dark  { animation: logo-glow-dark  4s ease-in-out infinite; }
        .logo-glow-light { animation: logo-glow-light 4s ease-in-out infinite; }

        .logo-img { transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .logo-img:hover { transform: scale(1.09) rotate(-3deg); }

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
        .brand-shimmer { display: inline-block; }
        @keyframes classly-glow-dark {
            0%, 100% { filter: drop-shadow(0 0 12px rgba(59,130,246,0.4)); }
            50%       { filter: drop-shadow(0 0 28px rgba(59,130,246,0.7)); }
        }
        .classly-glow-dark { animation: classly-glow-dark 3.5s ease-in-out infinite; }
        /* ══════════════════════════════════════════════════════
           SECTION DIVIDER GLOW
        ══════════════════════════════════════════════════════ */
        .glow-divider {
            width: 140px;
            height: 1px;
            margin: 0 auto;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(59,130,246,0.65) 35%,
                rgba(239,68,68,0.45) 65%,
                transparent 100%
            );
        }

        /* ══════════════════════════════════════════════════════
           FADE-UP ENTRANCES
        ══════════════════════════════════════════════════════ */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        .fade-up         { animation: fade-up 0.85s ease-out              forwards; }
        .fade-up-d1      { animation: fade-up 0.85s 0.15s ease-out both;            }
        .fade-up-d2      { animation: fade-up 0.85s 0.30s ease-out both;            }
        .fade-up-d3      { animation: fade-up 0.85s 0.50s ease-out both;            }
        .fade-up-d4      { animation: fade-up 0.85s 0.68s ease-out both;            }

        /* ══════════════════════════════════════════════════════
           FEATURE CARDS
        ══════════════════════════════════════════════════════ */
        @keyframes card-breathe {
            0%,  100% { transform: translateY(0px);  }
            50%        { transform: translateY(-8px); }
        }
        .feature-card {
            transition: transform 0.35s cubic-bezier(0.34,1.4,0.64,1),
                        box-shadow 0.35s ease,
                        background  0.35s ease,
                        border-color 0.35s ease;
            position: relative;
            overflow: hidden;
            animation: card-breathe 7s ease-in-out infinite;
            animation-delay: var(--delay, 0s);
        }
        .feature-card:hover {
            transform: translateY(-14px) scale(1.04) !important;
            animation: none;
        }

        /* Shimmer on hover */
        .feature-card::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.09) 50%, transparent 100%);
            transform: rotate(45deg);
            opacity: 0;
            transition: opacity 0.35s ease;
            pointer-events: none;
        }
        .feature-card:hover::before { opacity: 1; }

        .feature-card-dark {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(99,102,241,0.18) !important;
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
        }
        .feature-card-dark:hover {
            box-shadow:
                0 0 0 1px rgba(99,102,241,0.44),
                0 28px 56px -14px rgba(0,0,0,0.85),
                0 0 48px rgba(59,130,246,0.18);
            border-color: rgba(99,102,241,0.50) !important;
            background: rgba(255,255,255,0.09) !important;
        }
        .feature-card-light {
            background: rgba(255,255,255,0.80) !important;
            border: 1px solid rgba(59,130,246,0.20) !important;
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
        }
        .feature-card-light:hover {
            box-shadow:
                0 0 0 1.5px rgba(59,130,246,0.40),
                0 24px 50px -10px rgba(30,64,175,0.22),
                0 0 40px rgba(59,130,246,0.12);
            border-color: rgba(59,130,246,0.45) !important;
            background: rgba(255,255,255,0.96) !important;
        }
        .icon-wrap {
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
        }
        .feature-card:hover .icon-wrap { transform: scale(1.18) rotate(6deg); }

        /* ══════════════════════════════════════════════════════
           CTA BUTTON
        ══════════════════════════════════════════════════════ */
        @keyframes cta-glow-pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(59,130,246,0.35), 0 10px 40px rgba(59,130,246,0.20); }
            50%       { box-shadow: 0 0 40px rgba(59,130,246,0.62), 0 10px 60px rgba(59,130,246,0.36); }
        }
        @keyframes cta-shine {
            0%   { left: -100%; }
            100% { left:  200%; }
        }
        @keyframes cta-float {
            0%, 100% { transform: translateY(0);  }
            50%       { transform: translateY(-5px); }
        }
        .cta-btn {
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
            animation: cta-float 4s ease-in-out infinite;
        }
        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.28), transparent);
            transform: skewX(-20deg);
        }
        .cta-btn:hover::before { animation: cta-shine 0.6s ease forwards; }
        .cta-btn:hover         { animation: none; }

        .cta-btn-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 60%, #1d4ed8 100%);
            animation: cta-glow-pulse 3s ease-in-out infinite;
        }
        .cta-btn-blue:hover {
            transform: scale(1.04) translateY(-3px);
            box-shadow: 0 0 55px rgba(59,130,246,0.85), 0 15px 50px rgba(59,130,246,0.50) !important;
        }
        .cta-btn-dark {
            background: rgba(12, 18, 40, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(59,130,246,0.28);
        }
        .cta-btn-dark:hover {
            transform: scale(1.04) translateY(-3px);
            box-shadow: 0 0 44px rgba(59,130,246,0.30), 0 15px 44px rgba(0,0,0,0.44);
            border-color: rgba(59,130,246,0.55) !important;
        }
        .cta-btn-light {
            background: rgba(15,23,42,0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(59,130,246,0.20);
        }
        .cta-btn-light:hover {
            transform: scale(1.04) translateY(-3px);
            box-shadow: 0 0 44px rgba(30,64,175,0.32), 0 15px 44px rgba(30,64,175,0.20);
        }

        /* ══════════════════════════════════════════════════════
           PAP INSTITUTION BADGE (nav center)
        ══════════════════════════════════════════════════════ */
        .institution-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px 5px 10px;
            border-radius: 999px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all 0.35s ease;
        }
        .institution-badge:hover { letter-spacing: 0.20em; }
        .institution-badge-dark {
            background: rgba(59,130,246,0.10);
            border: 1px solid rgba(59,130,246,0.22);
            color: rgba(147,197,253,0.85);
        }
        .institution-badge-light {
            background: rgba(59,130,246,0.07);
            border: 1px solid rgba(59,130,246,0.18);
            color: rgba(30,64,175,0.85);
        }
        .accent-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 7px rgba(239,68,68,0.80);
            flex-shrink: 0;
        }

        /* ══════════════════════════════════════════════════════
           SY BADGE (near wordmark)
        ══════════════════════════════════════════════════════ */
        .sy-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            border: 1px solid rgba(239,68,68,0.30);
            background: rgba(239,68,68,0.08);
            color: rgba(252,165,165,0.80);
        }

        /* ══════════════════════════════════════════════════════
           FOOTER TEXT HOVER
        ══════════════════════════════════════════════════════ */
        .footer-text {
            transition: letter-spacing 0.35s ease, color 0.35s ease;
        }
        .footer-text:hover { letter-spacing: 0.40em; }

        /* ══════════════════════════════════════════════════════
           LIGHT MODE — DECORATIVE CIRCLES + DOT GRID + PLUS MARKS
        ══════════════════════════════════════════════════════ */
        .light-deco-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        /* ── Frosted-glass bubble base ── */
        .bubble {
            border-radius: 50%;
            position: absolute;
            pointer-events: none;
            /* key: near-transparent fill so background shows through */
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            overflow: hidden;
        }

        /* Specular shine arc — top-left crescent (::before) */
        .bubble::before {
            content: '';
            position: absolute;
            top: 6%;
            left: 8%;
            width: 46%;
            height: 32%;
            border-radius: 50%;
            background: radial-gradient(ellipse at 40% 40%,
                rgba(255,255,255,0.60) 0%,
                rgba(255,255,255,0.18) 55%,
                transparent 100%);
            filter: blur(3px);
            pointer-events: none;
            z-index: 1;
        }

        /* Bottom-right inner shadow arc (::after) */
        .bubble::after {
            content: '';
            position: absolute;
            bottom: 8%;
            right: 8%;
            width: 40%;
            height: 28%;
            border-radius: 50%;
            background: radial-gradient(ellipse,
                rgba(255,255,255,0.20) 0%,
                transparent 75%);
            filter: blur(5px);
            pointer-events: none;
            z-index: 1;
        }

        /* ── Large bubbles — mostly transparent, edge tint only ── */
        .bubble-large-pink {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.04) 0%,
                rgba(254,205,211,0.08) 50%,
                rgba(251,113,133,0.18) 80%,
                rgba(244,63,94,0.22) 100%);
            border: 1.5px solid rgba(251,113,133,0.22);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.28),
                inset 0 2px 16px rgba(255,255,255,0.14),
                0 12px 60px rgba(251,113,133,0.12),
                0 0 0 0.5px rgba(251,113,133,0.08);
        }
        .bubble-large-blue {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.04) 0%,
                rgba(219,234,254,0.08) 50%,
                rgba(147,197,253,0.18) 80%,
                rgba(96,165,250,0.22) 100%);
            border: 1.5px solid rgba(96,165,250,0.22);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.28),
                inset 0 2px 16px rgba(255,255,255,0.14),
                0 12px 60px rgba(96,165,250,0.12),
                0 0 0 0.5px rgba(96,165,250,0.08);
        }

        /* ── Small bubbles — glass with very subtle tint ── */
        .bubble-sm-pink {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%,
                rgba(253,164,175,0.10) 55%,
                rgba(251,113,133,0.22) 85%,
                rgba(244,63,94,0.26) 100%);
            border: 1.5px solid rgba(251,113,133,0.28);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.35),
                inset 0 1px 10px rgba(255,255,255,0.20),
                0 6px 28px rgba(251,113,133,0.14);
        }
        .bubble-sm-blue {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%,
                rgba(147,197,253,0.10) 55%,
                rgba(96,165,250,0.22) 85%,
                rgba(59,130,246,0.26) 100%);
            border: 1.5px solid rgba(96,165,250,0.28);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.35),
                inset 0 1px 10px rgba(255,255,255,0.20),
                0 6px 28px rgba(96,165,250,0.14);
        }
        .bubble-sm-red {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%,
                rgba(252,165,165,0.10) 55%,
                rgba(239,68,68,0.20) 85%,
                rgba(220,38,38,0.24) 100%);
            border: 1.5px solid rgba(239,68,68,0.26);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.35),
                inset 0 1px 10px rgba(255,255,255,0.20),
                0 6px 26px rgba(239,68,68,0.12);
        }
        .bubble-sm-indigo {
            background: radial-gradient(circle at 50% 50%,
                rgba(255,255,255,0.06) 0%,
                rgba(196,181,253,0.10) 55%,
                rgba(139,92,246,0.20) 85%,
                rgba(109,40,217,0.24) 100%);
            border: 1.5px solid rgba(139,92,246,0.26);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.35),
                inset 0 1px 10px rgba(255,255,255,0.20),
                0 6px 26px rgba(139,92,246,0.12);
        }

        /* Subtle floating pulse for the small accent circles */
        @keyframes light-circle-pulse {
            0%, 100% { transform: scale(1);    opacity: 1;    }
            50%       { transform: scale(1.06); opacity: 0.85; }
        }
        @keyframes bubble-drift-a {
            0%, 100% { transform: translate(0,0) scale(1); }
            33%       { transform: translate(10px,-14px) scale(1.03); }
            66%       { transform: translate(-8px,8px) scale(0.97); }
        }
        @keyframes bubble-drift-b {
            0%, 100% { transform: translate(0,0) scale(1); }
            40%       { transform: translate(-12px,-10px) scale(1.04); }
            70%       { transform: translate(8px,12px) scale(0.98); }
        }
        @keyframes bubble-drift-c {
            0%, 100% { transform: translate(0,0) scale(1); }
            30%       { transform: translate(14px,10px) scale(1.05); }
            65%       { transform: translate(-10px,-8px) scale(0.96); }
        }
        .light-circle-pulse  { animation: bubble-drift-a 9s  ease-in-out infinite; }
        .light-circle-pulse2 { animation: bubble-drift-b 12s ease-in-out infinite; animation-delay: -4s; }
        .bubble-drift-c      { animation: bubble-drift-c 15s ease-in-out infinite; animation-delay: -7s; }
        .bubble-drift-d      { animation: bubble-drift-a 11s ease-in-out infinite; animation-delay: -3s; }

        /* Repeating dot grid */
        .light-dot-grid {
            position: absolute;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(100,116,139,0.22) 1.2px, transparent 1.2px);
            background-size: 22px 22px;
        }

        /* Plus / cross marks */
        .light-plus {
            position: absolute;
            pointer-events: none;
            font-weight: 900;
            line-height: 1;
            user-select: none;
            font-family: 'Sora', sans-serif;
        }

        /* ══════════════════════════════════════════════════════
           DARK MODE GLOW BUBBLES
        ══════════════════════════════════════════════════════ */
        .dark-bubble {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .dark-bubble-large-blue {
            background: radial-gradient(circle at 40% 35%,
                rgba(96,165,250,0.04) 0%,
                rgba(59,130,246,0.06) 45%,
                rgba(29,78,216,0.08) 75%,
                rgba(29,78,216,0.04) 100%);
            border: 1px solid rgba(96,165,250,0.10);
            box-shadow:
                inset 0 0 60px rgba(59,130,246,0.06),
                0 0 80px rgba(59,130,246,0.12),
                0 0 140px rgba(59,130,246,0.07);
        }
        .dark-bubble-large-red {
            background: radial-gradient(circle at 40% 35%,
                rgba(239,68,68,0.03) 0%,
                rgba(220,38,38,0.06) 45%,
                rgba(185,28,28,0.07) 75%,
                rgba(185,28,28,0.03) 100%);
            border: 1px solid rgba(239,68,68,0.09);
            box-shadow:
                inset 0 0 60px rgba(239,68,68,0.05),
                0 0 80px rgba(239,68,68,0.10),
                0 0 140px rgba(239,68,68,0.06);
        }
        .dark-bubble-sm-blue {
            background: radial-gradient(circle at 40% 35%,
                rgba(147,197,253,0.05) 0%,
                rgba(96,165,250,0.08) 55%,
                rgba(59,130,246,0.06) 85%,
                transparent 100%);
            border: 1px solid rgba(96,165,250,0.12);
            box-shadow:
                0 0 30px rgba(59,130,246,0.14),
                0 0 60px rgba(59,130,246,0.07);
        }
        .dark-bubble-sm-indigo {
            background: radial-gradient(circle at 40% 35%,
                rgba(196,181,253,0.05) 0%,
                rgba(139,92,246,0.08) 55%,
                rgba(109,40,217,0.06) 85%,
                transparent 100%);
            border: 1px solid rgba(139,92,246,0.12);
            box-shadow:
                0 0 30px rgba(139,92,246,0.14),
                0 0 60px rgba(139,92,246,0.07);
        }
        .dark-bubble-sm-cyan {
            background: radial-gradient(circle at 40% 35%,
                rgba(103,232,249,0.04) 0%,
                rgba(34,211,238,0.07) 55%,
                rgba(6,182,212,0.05) 85%,
                transparent 100%);
            border: 1px solid rgba(34,211,238,0.10);
            box-shadow:
                0 0 26px rgba(34,211,238,0.13),
                0 0 50px rgba(34,211,238,0.06);
        }
        .dark-bubble-sm-red {
            background: radial-gradient(circle at 40% 35%,
                rgba(252,165,165,0.04) 0%,
                rgba(239,68,68,0.07) 55%,
                rgba(220,38,38,0.05) 85%,
                transparent 100%);
            border: 1px solid rgba(239,68,68,0.10);
            box-shadow:
                0 0 24px rgba(239,68,68,0.12),
                0 0 48px rgba(239,68,68,0.06);
        }

        /* Card coloured bottom accent strip */
        .card-accent-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 0 0 16px 16px;
        }
    </style>
</head>

{{-- ══════════════════════════════════════════════════════
     BODY
══════════════════════════════════════════════════════ --}}
<body class="antialiased overflow-hidden h-screen transition-colors duration-700"
      :class="darkMode ? 'text-white' : 'text-slate-900'">

    {{-- ─────────────────────────────────────────────────
         LAYER 0 : PURE BASE COLOUR
    ───────────────────────────────────────────────── --}}
    <div
    class="fixed inset-0 transition-all duration-700"
    style="z-index:0;"
    :style="darkMode
        ? `
            background:
            radial-gradient(circle at 0% 100%, rgba(239,68,68,.12), transparent 35%),
            radial-gradient(circle at 100% 100%, rgba(59,130,246,.15), transparent 35%),
            linear-gradient(135deg,#020617 0%,#081225 50%,#0f172a 100%);
        `
        : `
            background: linear-gradient(145deg, #f0f5ff 0%, #fdf0f3 35%, #eef4ff 65%, #fdf5f8 100%);
        `">
</div>

    {{-- ─────────────────────────────────────────────────
         LAYER 1 : GRADIENT BACKGROUND (replaces school photo)
    ───────────────────────────────────────────────── --}}
    {{-- Dark gradient --}}
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 bg-animated bg-gradient-dark"
         style="z-index: 1; filter: blur(6px) saturate(0.95); opacity: 0.95;">
    </div>

    {{-- Light gradient: pure white base, no tint --}}
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 bg-gradient-light"
         style="z-index: 1; opacity: 1;">
    </div>

    {{-- ─────────────────────────────────────────────────
         LAYER 2 : GRADIENT MESH BLOBS
    ───────────────────────────────────────────────── --}}

    {{-- Dark mesh: deep blue (top-left) + crimson (bottom-right) --}}
    <div x-show="darkMode" x-transition.opacity.duration.600ms
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 2;">

        {{-- Deep blue primary blob — top-left --}}
        <div class="blob absolute rounded-full"
             style="top:-12%; left:-8%; width:55%; height:55%;
                    background: radial-gradient(circle, rgba(29,78,216,0.40) 0%, rgba(29,78,216,0) 68%);
                    filter: blur(90px);"></div>

        {{-- Crimson accent blob — bottom-right --}}
        <div class="blob-d2 absolute rounded-full"
             style="bottom:-10%; right:-6%; width:48%; height:48%;
                    background: radial-gradient(circle, rgba(185,28,28,0.28) 0%, rgba(185,28,28,0) 68%);
                    filter: blur(90px);"></div>

        {{-- Indigo depth blob — center --}}
        <div class="blob-d3 absolute rounded-full"
             style="top:32%; left:38%; width:38%; height:38%;
                    background: radial-gradient(circle, rgba(79,46,209,0.16) 0%, rgba(79,46,209,0) 68%);
                    filter: blur(110px);"></div>

        {{-- Navy vignette at bottom --}}
        <div class="absolute bottom-0 left-0 right-0 pointer-events-none"
             style="height:35%;
                    background: linear-gradient(to top, rgba(5,12,28,0.90), transparent);">
        </div>
    </div>

    {{-- Dark mode: glowing transparent bubble circles (mirroring light mode layout) --}}
    <div x-show="darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 3;">

        {{-- ═══ HUGE glow blue bubble — right side center (mirrors light-mode blue) ═══ --}}
        <div class="dark-bubble dark-bubble-large-blue light-circle-pulse2"
             style="width:640px; height:640px; right:-200px; top:10%;"></div>

        {{-- ═══ HUGE glow red/rose bubble — bottom-left (mirrors light-mode pink) ═══ --}}
        <div class="dark-bubble dark-bubble-large-red light-circle-pulse"
             style="width:700px; height:700px; bottom:-260px; left:-200px;"></div>

        {{-- ═══ Medium blue glow bubble — upper-left ═══ --}}
        <div class="dark-bubble dark-bubble-sm-blue light-circle-pulse"
             style="width:130px; height:130px; top:7%; left:5%;"></div>

        {{-- ═══ Medium indigo glow bubble — upper-right ═══ --}}
        <div class="dark-bubble dark-bubble-sm-indigo light-circle-pulse2"
             style="width:150px; height:150px; top:5%; right:12%;"></div>

        {{-- ═══ Small cyan glow bubble — left mid ═══ --}}
        <div class="dark-bubble dark-bubble-sm-cyan bubble-drift-c"
             style="width:88px; height:88px; top:42%; left:8%;"></div>

        {{-- ═══ Small red glow bubble — upper center ═══ --}}
        <div class="dark-bubble dark-bubble-sm-red bubble-drift-d"
             style="width:64px; height:64px; top:10%; left:44%;"></div>

        {{-- ═══ Small blue glow bubble — lower-right ═══ --}}
        <div class="dark-bubble dark-bubble-sm-blue light-circle-pulse"
             style="width:76px; height:76px; bottom:22%; right:26%; animation-delay:-5s;"></div>

        {{-- ═══ Tiny indigo glow bubble — center-right ═══ --}}
        <div class="dark-bubble dark-bubble-sm-indigo bubble-drift-c"
             style="width:46px; height:46px; top:55%; right:18%; animation-delay:-2s;"></div>

        {{-- ═══ Tiny cyan glow bubble — lower center ═══ --}}
        <div class="dark-bubble dark-bubble-sm-cyan bubble-drift-d"
             style="width:38px; height:38px; bottom:18%; left:38%; animation-delay:-8s;"></div>

        {{-- ═══ Extra small red glow bubble — right mid ═══ --}}
        <div class="dark-bubble dark-bubble-sm-red light-circle-pulse2"
             style="width:50px; height:50px; top:33%; right:7%; animation-delay:-1s;"></div>
    </div>

    {{-- Light mode: large decorative bubble circles + small accents --}}
    <div x-show="!darkMode" x-transition.opacity.duration.600ms
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 2;">

        {{-- ═══ HUGE rose/pink bubble — bottom-left (partially off-screen) ═══ --}}
        <div class="bubble bubble-large-pink"
             style="width:700px; height:700px; bottom:-260px; left:-200px;"></div>

        {{-- ═══ HUGE sky-blue bubble — right side center (partially off-screen) ═══ --}}
        <div class="bubble bubble-large-blue"
             style="width:640px; height:640px; right:-200px; top:10%;"></div>

        {{-- ═══ Medium pink bubble — upper-left ═══ --}}
        <div class="bubble bubble-sm-pink light-circle-pulse"
             style="width:130px; height:130px; top:7%; left:5%;"></div>

        {{-- ═══ Medium blue bubble — upper-right ═══ --}}
        <div class="bubble bubble-sm-blue light-circle-pulse2"
             style="width:150px; height:150px; top:5%; right:12%;"></div>

        {{-- ═══ Small indigo bubble — left mid ═══ --}}
        <div class="bubble bubble-sm-indigo bubble-drift-c"
             style="width:88px; height:88px; top:42%; left:8%;"></div>

        {{-- ═══ Small red bubble — upper center ═══ --}}
        <div class="bubble bubble-sm-red bubble-drift-d"
             style="width:64px; height:64px; top:10%; left:44%;"></div>

        {{-- ═══ Small blue bubble — lower-right ═══ --}}
        <div class="bubble bubble-sm-blue light-circle-pulse"
             style="width:76px; height:76px; bottom:22%; right:26%; animation-delay:-5s;"></div>

        {{-- ═══ Tiny pink bubble — center-right ═══ --}}
        <div class="bubble bubble-sm-pink bubble-drift-c"
             style="width:46px; height:46px; top:55%; right:18%; animation-delay:-2s;"></div>

        {{-- ═══ Tiny indigo bubble — lower center ═══ --}}
        <div class="bubble bubble-sm-indigo bubble-drift-d"
             style="width:38px; height:38px; bottom:18%; left:38%; animation-delay:-8s;"></div>

        {{-- ═══ Extra small red bubble — right mid ═══ --}}
        <div class="bubble bubble-sm-red light-circle-pulse2"
             style="width:50px; height:50px; top:33%; right:7%; animation-delay:-1s;"></div>
    </div>

    {{-- ─────────────────────────────────────────────────
         FIRELIES (floating glowing particles) - separate for dark & light
    ───────────────────────────────────────────────── --}}
    <div x-show="darkMode" class="fireflies" aria-hidden="true">
        {{-- Several dark-mode fireflies with varied positions/durations --}}
        <div class="firefly firefly-dark" style="left:8%;  top:18%;  width:6px; height:6px; animation-duration:9s; animation-delay:-1s;"></div>
        <div class="firefly firefly-dark" style="left:22%; top:38%;  width:8px; height:8px; animation-duration:13s; animation-delay:-3s;"></div>
        <div class="firefly firefly-dark" style="left:34%; top:12%;  width:5px; height:5px; animation-duration:11s; animation-delay:-4s;"></div>
        <div class="firefly firefly-dark" style="left:48%; top:46%;  width:7px; height:7px; animation-duration:10s; animation-delay:-2s;"></div>
        <div class="firefly firefly-dark" style="left:62%; top:28%;  width:6px; height:6px; animation-duration:14s; animation-delay:-6s;"></div>
        <div class="firefly firefly-dark" style="left:76%; top:55%;  width:9px; height:9px; animation-duration:12s; animation-delay:-5s;"></div>
        <div class="firefly firefly-dark" style="left:88%; top:22%;  width:5px; height:5px; animation-duration:8s; animation-delay:-7s;"></div>
        <div class="firefly firefly-dark" style="left:52%; top:72%;  width:6px; height:6px; animation-duration:16s; animation-delay:-9s;"></div>
    </div>

    <div x-show="!darkMode" class="fireflies" aria-hidden="true">
        {{-- Several light-mode (golden) fireflies --}}
        <div class="firefly firefly-light" style="left:6%;  top:26%;  width:7px; height:7px; animation-duration:10s; animation-delay:-2s;"></div>
        <div class="firefly firefly-light" style="left:18%; top:44%;  width:6px; height:6px; animation-duration:13s; animation-delay:-4s;"></div>
        <div class="firefly firefly-light" style="left:30%; top:14%;  width:5px; height:5px; animation-duration:9s; animation-delay:-1s;"></div>
        <div class="firefly firefly-light" style="left:46%; top:52%;  width:8px; height:8px; animation-duration:12s; animation-delay:-3s;"></div>
        <div class="firefly firefly-light" style="left:60%; top:30%;  width:6px; height:6px; animation-duration:14s; animation-delay:-6s;"></div>
        <div class="firefly firefly-light" style="left:74%; top:60%;  width:9px; height:9px; animation-duration:11s; animation-delay:-5s;"></div>
        <div class="firefly firefly-light" style="left:86%; top:20%;  width:5px; height:5px; animation-duration:8s; animation-delay:-7s;"></div>
        <div class="firefly firefly-light" style="left:50%; top:78%;  width:6px; height:6px; animation-duration:16s; animation-delay:-9s;"></div>
    </div>

    {{-- ─────────────────────────────────────────────────
         LIGHT MODE : SUBTLE DOT GRID PATTERNS
    ───────────────────────────────────────────────── --}}
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 2;">
        {{-- Dot grid — lower-left zone (inside the large pink circle area) --}}
        <div class="light-dot-grid"
             style="width:250px; height:230px; left:8%; bottom:14%;"></div>
        {{-- Dot grid — upper-right zone (inside the large blue circle area) --}}
        <div class="light-dot-grid"
             style="width:220px; height:200px; right:14%; top:24%;"></div>
    </div>

    {{-- ─────────────────────────────────────────────────
         LIGHT MODE : DECORATIVE PLUS (+) MARKS
    ───────────────────────────────────────────────── --}}
    <div x-show="!darkMode" x-transition.opacity.duration.700ms
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 3;">
        {{-- Left side / near pink circle --}}
        <div class="light-plus" style="left:20%;  top:27%;  font-size:22px; color:rgba(239,68,68,0.38);">+</div>
        <div class="light-plus" style="left:7%;   top:58%;  font-size:15px; color:rgba(251,113,133,0.32);">+</div>
        <div class="light-plus" style="left:27%;  bottom:26%; font-size:13px; color:rgba(96,165,250,0.30);">+</div>
        {{-- Right side / near blue circle --}}
        <div class="light-plus" style="right:31%; top:13%;  font-size:18px; color:rgba(96,165,250,0.38);">+</div>
        <div class="light-plus" style="right:10%; top:65%;  font-size:20px; color:rgba(96,165,250,0.34);">+</div>
        <div class="light-plus" style="right:23%; bottom:21%; font-size:16px; color:rgba(248,113,113,0.32);">+</div>
        {{-- Center scatter --}}
        <div class="light-plus" style="left:55%;  top:7%;   font-size:14px; color:rgba(147,197,253,0.40);">+</div>
        <div class="light-plus" style="right:46%; top:5%;   font-size:13px; color:rgba(239,68,68,0.30);">+</div>
    </div>

    {{-- ─────────────────────────────────────────────────
         LAYER 3 : MESH GRID  (dark only)
    ───────────────────────────────────────────────── --}}
    <div x-show="darkMode" class="mesh-grid"></div>

    {{-- ─────────────────────────────────────────────────
         LAYER 3 : FLOATING PARTICLES  (dark only)  (kept as subtle extra effect)
    ───────────────────────────────────────────────── --}}
    <div x-show="darkMode"
         class="fixed inset-0 overflow-hidden pointer-events-none"
         style="z-index: 3;">
        <div class="particle w-1   h-1   bg-blue-400/50"   style="left:10%; animation-duration:9s;  animation-delay:0s;"></div>
        <div class="particle w-1.5 h-1.5 bg-indigo-400/38" style="left:25%; animation-duration:13s; animation-delay:-3s;"></div>
        <div class="particle w-1   h-1   bg-blue-300/45"   style="left:42%; animation-duration:8s;  animation-delay:-6s;"></div>
        <div class="particle w-2   h-2   bg-violet-400/28" style="left:61%; animation-duration:15s; animation-delay:-1s;"></div>
        <div class="particle w-1   h-1   bg-red-400/33"    style="left:77%; animation-duration:11s; animation-delay:-4s;"></div>
        <div class="particle w-1.5 h-1.5 bg-blue-500/38"  style="left:90%; animation-duration:10s; animation-delay:-7s;"></div>
    </div>

    {{-- ─────────────────────────────────────────────────
         CURSOR SPOTLIGHT (pointer-events: none, above content)
    ───────────────────────────────────────────────── --}}
    <div x-show="darkMode"
         class="spotlight-mask"
         :style="{ '--mouse-x': mouseX + 'px', '--mouse-y': mouseY + 'px' }">
    </div>
    <div x-show="darkMode"
         class="spotlight-glow"
         :style="{ left: (mouseX - 70) + 'px', top: (mouseY - 70) + 'px' }">
    </div>


    {{-- ══════════════════════════════════════════════════════
         TOP NAVIGATION BAR
    ══════════════════════════════════════════════════════ --}}
    <header class="top-nav fade-up"
            :class="darkMode ? 'top-nav-dark' : 'top-nav-light'">

        {{-- ── Left: Logo + wordmark ── --}}
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo.png') }}"
                 alt="Classly"
                 class="w-9 h-9 object-contain rounded-xl shadow-lg shrink-0"
                 :class="darkMode ? 'shadow-blue-950/60' : 'shadow-blue-200/60'">

            <div class="flex flex-col leading-none">
                <span class="font-black text-[15px] tracking-tight uppercase leading-none"
                      :class="darkMode ? 'text-white' : 'text-slate-900'">
                    Classly<span class="text-blue-500">.</span>
                </span>
                <span class="text-[7.5px] font-bold uppercase tracking-[0.20em] mt-0.5 hidden sm:block"
                      :class="darkMode ? 'text-blue-400/55' : 'text-slate-500/80'">
                    Class Scheduling System
                </span>
            </div>
        </div>

        {{-- ── Center: Institution badge ── --}}
        <div class="hidden md:flex items-center">
            <div class="institution-badge"
                 :class="darkMode ? 'institution-badge-dark' : 'institution-badge-light'">
                <span class="accent-dot"></span>
                Professional Academy of the Philippines
            </div>
        </div>

        {{-- ── Right: Theme pill toggle ── --}}
        <button
            @click="toggleTheme()"
            class="theme-pill"
            :aria-label="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">

            {{-- Background imagery --}}
            <div class="theme-pill-bg">
                <img class="theme-pill-img"
                     src="{{ asset('images/toggle/bg%20dark.jpg') }}" alt=""
                     :style="darkMode ? 'opacity:1' : 'opacity:0'">
                <img class="theme-pill-img"
                     src="{{ asset('images/toggle/bg%20light.jpg') }}" alt=""
                     :style="darkMode ? 'opacity:0' : 'opacity:1'">
            </div>

            {{-- Inset ring --}}
            <div class="theme-pill-ring"
                 :style="darkMode
                    ? 'box-shadow: inset 0 0 0 2.5px rgba(80,60,180,0.85), inset 0 0 0 5px rgba(40,20,100,0.5)'
                    : 'box-shadow: inset 0 0 0 2.5px rgba(100,180,255,0.85), inset 0 0 0 5px rgba(180,220,255,0.4)'">
            </div>

            {{-- Sliding knob --}}
            <div class="theme-pill-knob"
                 :style="darkMode ? 'left:4px' : 'left:88px'">
                <img src="{{ asset('images/toggle/circle%20dark.jpg') }}"  alt="Dark"
                     :style="darkMode ? 'opacity:1' : 'opacity:0'">
                <img src="{{ asset('images/toggle/circle%20light.jpg') }}" alt="Light"
                     :style="darkMode ? 'opacity:0' : 'opacity:1'">
                <div class="theme-pill-glow"
                     :style="darkMode
                        ? 'box-shadow: 0 0 0 2px rgba(60,40,150,0.9), 0 0 16px rgba(80,40,200,0.5)'
                        : 'box-shadow: 0 0 0 2px rgba(180,220,255,0.9), 0 0 16px rgba(100,190,255,0.6)'">
                </div>
            </div>
        </button>
    </header>


    {{-- ══════════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════════ --}}
    <div class="relative flex flex-col items-center justify-center px-4 gap-4 select-none"
         style="z-index: 10; height: calc(100vh - 66px); margin-top: 66px; overflow: hidden;">

        {{-- ── HERO BRANDING ── --}}
        <div class="flex flex-col items-center text-center">

            {{-- Logo --}}
            <div class="animate-float mb-2 fade-up"
                 :class="darkMode ? 'logo-glow-dark' : 'logo-glow-light'">
                <img src="{{ asset('logo.png') }}"
                     alt="Classly Logo"
                     class="logo-img w-28 h-28 md:w-36 md:h-36 object-contain rounded-3xl shadow-2xl"
                     draggable="false">
            </div>

            {{-- CLASSLY wordmark --}}
            <div class="animate-float fade-up-d1"
                 :class="darkMode ? 'classly-glow-dark' : 'classly-glow-light'">
                <h1 class="text-[3.8rem] md:text-[5.6rem] leading-none font-black tracking-[-0.02em]">
                    <span class="brand-shimmer brand-class" data-text="Class">Class</span><span
                          class="brand-shimmer brand-ly"   data-text="ly">ly</span>
                </h1>
            </div>

            {{-- Tagline + SY badge row --}}
            <div class="flex flex-col items-center gap-2 mt-2 fade-up-d2">
                <p class="text-[10.5px] md:text-[11.5px] font-bold tracking-[0.45em] uppercase transition-all duration-300 hover:tracking-[0.55em]"
                   :class="darkMode ? 'text-blue-200/50 hover:text-blue-300/80' : 'text-slate-700/55 hover:text-slate-800/80'">
                    Your Friendly Class Scheduler
                </p>
                <span class="sy-badge">
                    <span class="accent-dot" style="width:4px;height:4px;"></span>
                    SY 2025 – 2026
                </span>
            </div>

            {{-- Glow divider --}}
            <div class="glow-divider mt-3 fade-up-d2 opacity-75"></div>
        </div>


        {{-- ── FEATURE CARDS ── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 max-w-4xl w-full px-2 fade-up-d3">

            {{-- Card 1 · Conflict Detection --}}
            <div class="feature-card p-4 md:p-5 rounded-2xl border backdrop-blur-lg shadow-xl"
                 :class="darkMode ? 'feature-card-dark' : 'feature-card-light'"
                 style="--delay: 0s;">
                <div class="icon-wrap w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-blue-500/14 text-blue-500">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-[12px] mb-1.5"
                    :class="darkMode ? 'text-white/90' : 'text-slate-900'">
                    Conflict Detection
                </h3>
                <p class="text-[11px] leading-relaxed"
                   :class="darkMode ? 'text-white/45' : 'text-slate-600/80'">
                    Advanced AI core that prevents classroom and faculty scheduling overlaps instantly.
                </p>
                {{-- Coloured bottom accent — light mode only --}}
                <div x-show="!darkMode" class="card-accent-bottom"
                     style="background: linear-gradient(90deg, rgba(59,130,246,0.70), rgba(96,165,250,0.90));"></div>
            </div>

            {{-- Card 2 · Institutional Sync --}}
            <div class="feature-card p-4 md:p-5 rounded-2xl border backdrop-blur-lg shadow-xl"
                 :class="darkMode ? 'feature-card-dark' : 'feature-card-light'"
                 style="--delay: 0.15s;">
                <div class="icon-wrap w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-purple-500/14 text-purple-400">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-[12px] mb-1.5"
                    :class="darkMode ? 'text-white/90' : 'text-slate-900'">
                    Institutional Sync
                </h3>
                <p class="text-[11px] leading-relaxed"
                   :class="darkMode ? 'text-white/45' : 'text-slate-600/80'">
                    Unified data management across CCS, CTE, COC, and SHTM departments.
                </p>
                {{-- Coloured bottom accent — light mode only --}}
                <div x-show="!darkMode" class="card-accent-bottom"
                     style="background: linear-gradient(90deg, rgba(147,51,234,0.65), rgba(192,132,252,0.88));"></div>
            </div>

            {{-- Card 3 · Secure Gateways --}}
            <div class="feature-card p-4 md:p-5 rounded-2xl border backdrop-blur-lg shadow-xl"
                 :class="darkMode ? 'feature-card-dark' : 'feature-card-light'"
                 style="--delay: 0.30s;">
                <div class="icon-wrap w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-emerald-500/14 text-emerald-400">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-black uppercase tracking-tighter text-[12px] mb-1.5"
                    :class="darkMode ? 'text-white/90' : 'text-slate-900'">
                    Secure Gateways
                </h3>
                <p class="text-[11px] leading-relaxed"
                   :class="darkMode ? 'text-white/45' : 'text-slate-600/80'">
                    Role-specific access for administrators and deans to ensure data integrity.
                </p>
                {{-- Coloured bottom accent — light mode only --}}
                <div x-show="!darkMode" class="card-accent-bottom"
                     style="background: linear-gradient(90deg, rgba(16,185,129,0.65), rgba(52,211,153,0.88));"></div>
            </div>
        </div>


        {{-- ── CTA BUTTON ── --}}
        <div class="w-full max-w-xs text-center fade-up-d4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="cta-btn cta-btn-blue group flex items-center justify-center w-full py-[18px] text-white rounded-2xl font-black uppercase text-[10.5px] tracking-[0.25em] shadow-lg">
                        Enter Command Center
                        <span class="ml-3 group-hover:translate-x-2 transition-transform duration-300">→</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="cta-btn group flex items-center justify-center w-full py-[18px] rounded-2xl font-black uppercase text-[10.5px] tracking-[0.25em] shadow-xl text-white"
                       :class="darkMode ? 'cta-btn-dark hover:text-blue-200' : 'cta-btn-light hover:text-blue-200'">
                        Request Authorized Access
                        <span class="ml-3 group-hover:translate-x-2 transition-transform duration-300">→</span>
                    </a>
                @endauth
            @endif

            {{-- Quote --}}
            <p class="mt-3 text-[9.5px] font-black uppercase tracking-[0.50em] italic transition-all duration-300 hover:tracking-[0.60em]"
               :class="darkMode ? 'text-white/22 hover:text-blue-300/45' : 'text-slate-900/28 hover:text-slate-900/52'">
                "Life is not a race"
            </p>
        </div>


        {{-- ── FOOTER ── --}}
        <div class="text-center space-y-0.5 fade-up-d4">
            <p class="footer-text text-[8.5px] uppercase font-bold tracking-[0.28em]"
               :class="darkMode ? 'text-blue-300/22 hover:text-blue-300/46' : 'text-slate-600/28 hover:text-slate-700/48'">
                Professional Academy of the Philippines &copy; 2026
            </p>
            <p class="footer-text text-[8.5px] uppercase font-bold tracking-[0.28em]"
               :class="darkMode ? 'text-blue-300/16 hover:text-blue-300/40' : 'text-slate-600/22 hover:text-slate-700/42'">
                Classly &mdash; Developed by DJS
            </p>
        </div>

    </div>{{-- /main --}}

</body>
</html>