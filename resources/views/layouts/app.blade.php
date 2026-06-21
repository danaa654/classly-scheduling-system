<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('logo.png') }}">

    <title>{{ config('app.name', 'Classly') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* ─── Scrollbars ─────────────────────────────────────────────── */
        .custom-scrollbar::-webkit-scrollbar { width: 3px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #334155; }

        [x-cloak] { display: none !important; }

        /* ─── CSS variables ──────────────────────────────────────────── */
        :root {
            --sidebar-collapsed-w: 64px;
            --sidebar-expanded-w: 240px;
            --sidebar-bg: #0b1120;
            --sidebar-border: rgba(255,255,255,0.05);
            --sidebar-duration: 280ms;
            --sidebar-ease: cubic-bezier(0.4, 0, 0.2, 1);
            --blue-glow: rgba(59, 130, 246, 0.35);
        }

        /* ─── Layout shell ───────────────────────────────────────────── */
        #layout-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ─── Sidebar ────────────────────────────────────────────────── */
        #app-sidebar {
            position: relative;
            display: flex;
            flex-direction: column;
            width: var(--sidebar-collapsed-w);
            min-width: var(--sidebar-collapsed-w);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            transition: width var(--sidebar-duration) var(--sidebar-ease),
                        min-width var(--sidebar-duration) var(--sidebar-ease);
            will-change: width;
            z-index: 50;
            overflow: hidden;
        }

        /* Expanded: hover or pinned */
        #app-sidebar.is-expanded,
        #app-sidebar:hover {
            width: var(--sidebar-expanded-w);
            min-width: var(--sidebar-expanded-w);
        }

        /* ─── Label reveal ───────────────────────────────────────────── */
        .nav-label {
            opacity: 0;
            white-space: nowrap;
            overflow: hidden;
            max-width: 0;
            transition: opacity 200ms ease 60ms,
                        max-width var(--sidebar-duration) var(--sidebar-ease);
            pointer-events: none;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        #app-sidebar.is-expanded .nav-label,
        #app-sidebar:hover .nav-label {
            opacity: 1;
            max-width: 180px;
            pointer-events: auto;
        }

        /* ─── Section heading ────────────────────────────────────────── */
        .nav-section-heading {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: opacity 200ms ease 60ms,
                        max-height var(--sidebar-duration) var(--sidebar-ease);
        }
        #app-sidebar.is-expanded .nav-section-heading,
        #app-sidebar:hover .nav-section-heading {
            opacity: 1;
            max-height: 32px;
        }

        /* ─── User info strip ────────────────────────────────────────── */
        .sidebar-user-text {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 200ms ease 60ms,
                        max-width var(--sidebar-duration) var(--sidebar-ease);
        }
        #app-sidebar.is-expanded .sidebar-user-text,
        #app-sidebar:hover .sidebar-user-text {
            opacity: 1;
            max-width: 160px;
        }

        /* ─── Chevron (submenu arrow) ────────────────────────────────── */
        .nav-chevron {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            transition: opacity 200ms ease 60ms,
                        max-width var(--sidebar-duration) var(--sidebar-ease),
                        transform 200ms ease;
        }
        #app-sidebar.is-expanded .nav-chevron,
        #app-sidebar:hover .nav-chevron {
            opacity: 1;
            max-width: 28px;
        }
        .nav-chevron.is-open {
            transform: rotate(180deg);
        }

        /* ─── Pin button ─────────────────────────────────────────────── */
        .pin-btn {
            opacity: 0;
            pointer-events: none;
            transition: opacity 200ms ease 60ms;
        }
        #app-sidebar.is-expanded .pin-btn,
        #app-sidebar:hover .pin-btn {
            opacity: 1;
            pointer-events: auto;
        }

        /* ─── Submenu ────────────────────────────────────────────────── */
        .nav-submenu {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 260ms var(--sidebar-ease),
                        opacity 200ms ease;
        }
        .nav-submenu.is-open {
            max-height: 180px;
            opacity: 1;
        }

        /* ─── Nav item icon centering ────────────────────────────────── */
        .nav-item-icon {
            flex-shrink: 0;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ─── Active glow ────────────────────────────────────────────── */
        .nav-link-active {
            background: rgba(59, 130, 246, 0.15) !important;
            border: 1px solid rgba(59, 130, 246, 0.25) !important;
            color: #93c5fd !important;
            box-shadow: 0 0 0 0 transparent;
        }

        /* ─── Tooltip (collapsed only) ───────────────────────────────── */
        .nav-tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed-w) + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: #1e293b;
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 5px 10px;
            border-radius: 8px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            border: 1px solid rgba(255,255,255,0.07);
            transition: opacity 120ms ease;
            z-index: 200;
        }
        .nav-link-wrap:hover .nav-tooltip {
            opacity: 1;
        }
        #app-sidebar.is-expanded .nav-tooltip,
        #app-sidebar:hover .nav-tooltip {
            display: none;
        }

        /* ─── Logo wordmark ──────────────────────────────────────────── */
        .logo-wordmark {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 200ms ease 60ms,
                        max-width var(--sidebar-duration) var(--sidebar-ease);
        }
        #app-sidebar.is-expanded .logo-wordmark,
        #app-sidebar:hover .logo-wordmark {
            opacity: 1;
            max-width: 160px;
        }

        /* ─── Mobile overlay ─────────────────────────────────────────── */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 40;
            backdrop-filter: blur(2px);
        }

        @media (max-width: 768px) {
            #app-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform var(--sidebar-duration) var(--sidebar-ease),
                            width var(--sidebar-duration) var(--sidebar-ease);
                width: var(--sidebar-expanded-w) !important;
                min-width: var(--sidebar-expanded-w) !important;
            }
            #app-sidebar.mobile-open {
                transform: translateX(0);
            }
            #app-sidebar.mobile-open .nav-label,
            #app-sidebar.mobile-open .nav-section-heading,
            #app-sidebar.mobile-open .sidebar-user-text,
            #app-sidebar.mobile-open .nav-chevron,
            #app-sidebar.mobile-open .pin-btn,
            #app-sidebar.mobile-open .logo-wordmark {
                opacity: 1;
                max-width: 200px;
                max-height: 40px;
                pointer-events: auto;
            }
            #sidebar-overlay.active { display: block; }
            #layout-content {
                margin-left: 0 !important;
            }
        }

        /* ─── Main content ───────────────────────────────────────────── */
        #layout-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
        }

        /* ─── Glow dot (active indicator) ───────────────────────────── */
        .active-pip {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 18px;
            border-radius: 0 3px 3px 0;
            background: #3b82f6;
            box-shadow: 0 0 8px rgba(59,130,246,0.7);
        }

        /* ─── Subtle noise texture overlay on sidebar ────────────────── */
        #app-sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            opacity: 0.4;
            z-index: 0;
        }
        #app-sidebar > * { position: relative; z-index: 1; }

        /* ─── Theme Toggle Pill ──────────────────────────────────── */
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
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.style.zIndex = "10000";

                        // `toast` here is the .swal2-popup, not the .swal2-container.
                        // The popup's z-index only matters inside the container's own
                        // stacking context, so it never lifts the toast above page
                        // content. The container is the element that actually competes
                        // with the app's modals (some go up to z-[10050]), so that's
                        // the one that needs the override.
                        const container = Swal.getContainer();
                        if (container) {
                            container.style.setProperty('z-index', '999999', 'important');
                        }

                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                Toast.fire({
                    icon: data.type,
                    title: data.message,
                    text: data.detail || '',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
                });
            });
        });
    </script>
</head>
<body
    x-data="{
        darkMode: localStorage.getItem('theme') === 'dark',
        sidebarPinned: localStorage.getItem('sidebar-pinned') === 'true',
        mobileOpen: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        },
        togglePin() {
            this.sidebarPinned = !this.sidebarPinned;
            localStorage.setItem('sidebar-pinned', this.sidebarPinned);
            const sidebar = document.getElementById('app-sidebar');
            if (this.sidebarPinned) {
                sidebar.classList.add('is-expanded');
            } else {
                sidebar.classList.remove('is-expanded');
            }
        },
        openMobile() {
            this.mobileOpen = true;
            document.getElementById('app-sidebar').classList.add('mobile-open');
            document.getElementById('sidebar-overlay').classList.add('active');
        },
        closeMobile() {
            this.mobileOpen = false;
            document.getElementById('app-sidebar').classList.remove('mobile-open');
            document.getElementById('sidebar-overlay').classList.remove('active');
        }
    }"
    x-init="
        if (sidebarPinned) {
            document.getElementById('app-sidebar').classList.add('is-expanded');
        }
        document.documentElement.classList.toggle('dark', darkMode);
        $watch('darkMode', val => document.documentElement.classList.toggle('dark', val));
    "
    :class="{ 'dark': darkMode }"
    class="font-sans antialiased text-slate-900 dark:text-white bg-slate-100 dark:bg-[#020617] transition-colors duration-300"
>

{{-- Mobile overlay --}}
<div id="sidebar-overlay" @click="closeMobile()"></div>

<div id="layout-shell">

    {{-- ═══════════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════════ --}}
    <aside id="app-sidebar">

        {{-- ── Logo ── --}}
        <div class="flex items-center h-16 px-4 border-b shrink-0" style="border-color: var(--sidebar-border);">
            <div class="flex items-center gap-3 w-full overflow-hidden">
                {{-- Logo Image --}}
                <img src="{{ asset('logo.png') }}" alt="Classly Logo" class="w-8 h-8 shrink-0">
                {{-- Wordmark --}}
                <div class="logo-wordmark flex flex-col leading-none">
                    <span class="text-white font-black text-[15px] tracking-tight uppercase">Classly<span class="text-blue-500">.</span></span>
                    <span class="text-slate-600 font-bold text-[7px] uppercase tracking-[0.2em] mt-0.5">Your Friendly Class Scheduler</span>
                </div>
            </div>
        </div>

        {{-- ── Navigation ── --}}
        <nav class="flex-1 overflow-y-auto custom-scrollbar px-2 py-3 space-y-0.5">

            {{-- Section: General --}}
            <div class="nav-section-heading px-2 pb-1 pt-2">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.18em]">General</p>
            </div>

            {{-- Dashboard --}}
            <div class="nav-link-wrap relative">
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->routeIs('dashboard')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->routeIs('dashboard'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <span class="nav-tooltip">Dashboard</span>
            </div>

            {{-- Manage Users (admin only) --}}
            @if(auth()->user()->role === 'admin')
            <div class="nav-link-wrap relative">
                <a href="/manage-users" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->is('manage-users*')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->is('manage-users*'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">👥</span>
                    <span class="nav-label">Manage Users</span>
                </a>
                <span class="nav-tooltip">Manage Users</span>
            </div>
            @endif

            {{-- Manage Rooms --}}
            <div class="nav-link-wrap relative">
                <a href="/manage-rooms" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->is('manage-rooms*')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->is('manage-rooms*'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">🏫</span>
                    <span class="nav-label">Manage Rooms</span>
                </a>
                <span class="nav-tooltip">Rooms</span>
            </div>

            {{-- Faculty --}}
            <div class="nav-link-wrap relative">
                <a href="/faculty" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->is('faculty')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->is('faculty'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">👨‍🏫</span>
                    <span class="nav-label">Faculty List</span>
                </a>
                <span class="nav-tooltip">Faculty</span>
            </div>

            {{-- Subjects --}}
            <div class="nav-link-wrap relative">
                <a href="{{ route('subjects') }}" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->routeIs('subjects*')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->routeIs('subjects*'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">📚</span>
                    <span class="nav-label">Subjects</span>
                </a>
                <span class="nav-tooltip">Subjects</span>
            </div>

            {{-- Divider --}}
            <div class="my-2 border-t" style="border-color: var(--sidebar-border);"></div>

            {{-- Section: Academic Tools --}}
            <div class="nav-section-heading px-2 pb-1 pt-1">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.18em]">Academic Tools</p>
            </div>

            {{-- Master Grid (with submenu) --}}
            <div x-data="{ submenuOpen: {{ request()->is('master-grid*', 'block-schedule*', 'faculty-load*') ? 'true' : 'false' }} }">
                <div class="nav-link-wrap relative">
                    <div class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group cursor-pointer
                        {{ request()->is('master-grid*', 'block-schedule*', 'faculty-load*')
                            ? 'nav-link-active'
                            : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}"
                        @click="
                            const sidebar = document.getElementById('app-sidebar');
                            const isVisible = sidebar.classList.contains('is-expanded') || sidebar.matches(':hover');
                            if (isVisible) {
                                submenuOpen = !submenuOpen;
                            } else {
                                window.location.href = '{{ route('master-grid') }}';
                            }
                        ">
                        @if(request()->is('master-grid*', 'block-schedule*', 'faculty-load*'))
                            <span class="active-pip"></span>
                        @endif
                        <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">📅</span>
                        <span class="nav-label flex-1">Master Grid</span>
                        <span class="nav-chevron ml-auto" :class="{ 'is-open': submenuOpen }">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </div>
                    <span class="nav-tooltip">Master Grid</span>
                </div>

                {{-- Submenu --}}
                <div class="nav-submenu pl-2 mt-0.5 space-y-0.5" :class="{ 'is-open': submenuOpen }">
                    <div class="ml-6 pl-3 border-l space-y-0.5" style="border-color: rgba(59,130,246,0.2);">
                        <a href="{{ route('master-grid') }}" wire:navigate
                            class="flex items-center gap-2 px-2 py-2 rounded-lg text-[12px] font-semibold transition-all duration-150
                            {{ request()->routeIs('master-grid') ? 'text-blue-300 bg-blue-500/10' : 'text-slate-500 hover:text-slate-200 hover:bg-white/5' }}">
                            <span class="text-sm">🏠</span>
                            <span class="nav-label" style="font-size:12px;">Room View</span>
                        </a>
                        <a href="{{ route('faculty-loading') }}" wire:navigate
                            class="flex items-center gap-2 px-2 py-2 rounded-lg text-[12px] font-semibold transition-all duration-150
                            {{ request()->routeIs('faculty-loading') ? 'text-blue-300 bg-blue-500/10' : 'text-slate-500 hover:text-slate-200 hover:bg-white/5' }}">
                            <span class="text-sm">✒️</span>
                            <span class="nav-label" style="font-size:12px;">Faculty Info</span>
                        </a>
                        <a href="{{ route('block-schedule') }}" wire:navigate
                            class="flex items-center gap-2 px-2 py-2 rounded-lg text-[12px] font-semibold transition-all duration-150
                            {{ request()->routeIs('block-schedule') ? 'text-blue-300 bg-blue-500/10' : 'text-slate-500 hover:text-slate-200 hover:bg-white/5' }}">
                            <span class="text-sm">📚</span>
                            <span class="nav-label" style="font-size:12px;">Block Schedule</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Settings (admin/registrar only) --}}
            @if(in_array(auth()->user()->role, ['admin', 'registrar']))
            <div class="nav-link-wrap relative">
                <a href="{{ route('settings') }}" wire:navigate
                    class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                    {{ request()->routeIs('settings')
                        ? 'nav-link-active'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                    @if(request()->routeIs('settings'))
                        <span class="active-pip"></span>
                    @endif
                    <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">⚙️</span>
                    <span class="nav-label">Settings</span>
                </a>
                <span class="nav-tooltip">Settings</span>
            </div>
            @endif

            {{-- Security Logs Link - Restricted Strictly to Admin --}}
                @if(auth()->user()->role === 'admin')
                <div class="nav-link-wrap relative">
                    <a href="/admin/security-logs" wire:navigate
                        class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                        {{ request()->is('admin/security-logs')
                            ? 'nav-link-active'
                            : 'text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent' }}">
                        
                        @if(request()->is('admin/security-logs'))
                            <span class="active-pip"></span>
                        @endif
                        
                        <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">🛡️</span>
                        <span class="nav-label">Security Logs</span>
                    </a>
                    <span class="nav-tooltip">Security Logs</span>
                </div>
                @endif

        </nav>

        {{-- ── Bottom: User + Logout ── --}}
        <div class="shrink-0 border-t p-2 space-y-1" style="border-color: var(--sidebar-border);">
            {{-- Account link --}}
            <div class="nav-link-wrap relative">
                <a href="/manage-account" wire:navigate
                    class="flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                           text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent">
                    <div class="relative shrink-0">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-[10px]">
                            {{ auth()->user()->initials() }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 border border-[var(--sidebar-bg)]"></span>
                    </div>
                    <div class="sidebar-user-text flex flex-col leading-none overflow-hidden">
                        <span class="text-[11px] font-bold text-slate-300 group-hover:text-blue-300 transition-colors truncate">Manage Account</span>
                        <span class="text-[9px] font-semibold text-slate-600 uppercase tracking-widest mt-0.5">{{ auth()->user()->role }}</span>
                    </div>
                </a>
                <span class="nav-tooltip">Account</span>
            </div>

            {{-- Logout --}}
            <div class="nav-link-wrap relative">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group
                               text-slate-600 hover:text-rose-400 hover:bg-rose-500/5 border border-transparent">
                        <span class="nav-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </span>
                        <span class="nav-label text-rose-400/70 group-hover:text-rose-400">Sign Out</span>
                    </button>
                </form>
                <span class="nav-tooltip">Sign Out</span>
            </div>
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════ --}}
    <div id="layout-content">

        {{-- ── Top Header ── --}}
        <header class="h-16 flex items-center justify-between px-6 md:px-8 shrink-0 z-30 border-b"
                style="background: #0b1120; border-color: var(--sidebar-border);">

            {{-- Mobile hamburger --}}
            <button @click="openMobile()"
                    class="md:hidden p-2 text-slate-400 hover:text-white transition-colors mr-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- School name --}}
            <div class="flex flex-col leading-tight">
                <h3 class="text-[13px] font-black text-slate-100 tracking-tight uppercase hidden sm:block">
                    Professional Academy of the Philippines
                </h3>
                <p class="text-[9px] text-blue-500 font-black uppercase tracking-[0.28em] leading-tight mt-0.5 hidden sm:block">
                    ClassLy - Class Scheduling Management System
                </p>
                <h3 class="text-[13px] font-black text-slate-100 tracking-tight uppercase sm:hidden">PAP</h3>
            </div>

            {{-- Right controls --}}
            <div class="flex items-center gap-3">

                {{-- Theme toggle pill --}}
                <button
                    @click="toggleTheme()"
                    class="theme-pill"
                    :aria-label="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">

                    {{-- Background images --}}
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

                {{-- Notifications --}}
                @livewire('notification-center')

                {{-- User pill --}}
                <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                    <div class="hidden md:flex flex-col items-end leading-tight">
                        <span class="text-[12px] font-black text-white tracking-tight">{{ auth()->user()->name }}</span>
                        <span class="text-[9px] text-blue-400 font-bold uppercase tracking-widest mt-0.5">{{ auth()->user()->role }}</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-[10px] font-black border border-white/10 transition-transform hover:scale-105"
                         style="background: linear-gradient(135deg, #2563eb, #4f46e5);">
                        {{ auth()->user()->initials() }}
                    </div>
                </div>
            </div>
        </header>

        {{-- ── Page content ── --}}
        <main class="flex-1 min-w-0 overflow-y-auto overflow-x-hidden custom-scrollbar bg-slate-100 dark:bg-[#020617] p-6 md:p-8">
            {{ $slot }}
        </main>
    </div>

</div>{{-- #layout-shell --}}

@livewireScripts

{{-- Notification container --}}
<div
    x-data="{
        notifications: [],
        add(e) {
            const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
            this.notifications.push({ id: Date.now(), type: data.type || 'info', title: data.title || 'Notification', message: data.message });
            setTimeout(() => { this.notifications.shift(); }, 5000);
        }
    }"
    @notify.window="add($event)"
    class="fixed bottom-6 right-6 flex flex-col gap-2 w-full max-w-sm pointer-events-none"
    style="z-index: 999999 !important;"
>
</div>

</body>
</html>