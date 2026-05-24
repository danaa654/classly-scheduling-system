{{--
  ╔══════════════════════════════════════════════════════════════════╗
  ║  REGISTRAR DASHBOARD  —  Aligned to Admin Dashboard Design System ║
  ╚══════════════════════════════════════════════════════════════════╝
--}}

<style>
    /* ── Scrollbars ─────────────────────────────────── */
    .dash-scroll { scroll-behavior: smooth; }
    .dash-scroll::-webkit-scrollbar { width: 3px; height: 3px; }
    .dash-scroll::-webkit-scrollbar-track { background: transparent; }
    .dash-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.15); border-radius: 99px; transition: background 0.2s; }
    .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.45); }
    .dark .dash-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.07); }
    .dark .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.18); }

    /* ── Panel scroll wrappers ──────────────────────── */
    .panel-scroll { overflow-y: auto; scroll-behavior: smooth; }
    .panel-scroll::-webkit-scrollbar { width: 3px; }
    .panel-scroll::-webkit-scrollbar-track { background: transparent; }
    .panel-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.12); border-radius: 99px; transition: background 0.2s; }
    .panel-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.38); }
    .dark .panel-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); }
    .dark .panel-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.16); }

    /* ── Workflow pipeline connectors ───────────────── */
    @keyframes flowPulse {
        0%,100% { opacity: 0.3; transform: scaleX(1); }
        50%      { opacity: 1;   transform: scaleX(1.02); }
    }
    @keyframes stagePing {
        0%   { transform: scale(1);   opacity: 1; }
        75%  { transform: scale(1.8); opacity: 0; }
        100% { transform: scale(1.8); opacity: 0; }
    }
    .flow-connector { animation: flowPulse 2.5s ease-in-out infinite; }
    .stage-ping     { animation: stagePing 1.5s cubic-bezier(0,0,0.2,1) infinite; }

    /* ── Status card glow pulses ────────────────────── */
    @keyframes glowPulseBlue   { 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,0);}  50%{box-shadow:0 0 16px 4px rgba(59,130,246,0.3);}  }
    @keyframes glowPulseYellow { 0%,100%{box-shadow:0 0 0 0 rgba(234,179,8,0);}   50%{box-shadow:0 0 16px 4px rgba(234,179,8,0.3);}   }
    @keyframes glowPulseOrange { 0%,100%{box-shadow:0 0 0 0 rgba(249,115,22,0);}  50%{box-shadow:0 0 16px 4px rgba(249,115,22,0.35);} }
    @keyframes glowPulseGreen  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}  50%{box-shadow:0 0 16px 4px rgba(16,185,129,0.3);}  }
    @keyframes glowPulseRed    { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}   50%{box-shadow:0 0 16px 4px rgba(239,68,68,0.3);}   }
    .glow-blue   { animation: glowPulseBlue   3s ease-in-out infinite; }
    .glow-yellow { animation: glowPulseYellow 3s ease-in-out infinite; }
    .glow-orange { animation: glowPulseOrange 3s ease-in-out infinite; }
    .glow-green  { animation: glowPulseGreen  3s ease-in-out infinite; }
    .glow-red    { animation: glowPulseRed    3s ease-in-out infinite; }

    /* ── Progress bar shimmer ───────────────────────── */
    @keyframes shimmer {
        0%   { background-position: -200% center; }
        100% { background-position:  200% center; }
    }
    .progress-bar-shine {
        background-size: 200% auto;
        animation: shimmer 2.5s linear infinite;
    }

    /* ── Hover lift ─────────────────────────────────── */
    .card-lift { transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .card-lift:hover { transform: translateY(-2px); }

    /* ── Fade-in stagger ────────────────────────────── */
    @keyframes fadeSlideIn {
        from { opacity:0; transform:translateY(6px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-row { animation: fadeSlideIn 0.35s ease both; }

    /* ── Conflict ping ──────────────────────────────── */
    @keyframes pulse-slow { 0%,100%{opacity:1;} 50%{opacity:0.7;} }
    .animate-pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div
    x-data="{
        clock: '{{ date('H:i:s') }}',
        init() { setInterval(() => { this.clock = new Date().toLocaleTimeString('en-GB'); }, 1000); }
    }"
    class="min-h-screen w-full font-mono antialiased overflow-x-hidden transition-colors duration-500
           bg-slate-100 dark:bg-[#080d1a]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image:radial-gradient(ellipse at 15% 15%,rgba(59,130,246,0.07) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(139,92,246,0.05) 0%,transparent 55%);'
        : 'background-image:radial-gradient(ellipse at 15% 15%,rgba(59,130,246,0.06) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(139,92,246,0.04) 0%,transparent 55%);'"
>

{{-- ═══════════════════════════════════════════════════
     SMART ALERTS STRIP  (preserved from registrar)
═══════════════════════════════════════════════════════ --}}
@php
    $overloaded = collect($facultyLoad)->where('status', 'overloaded')->count();
    $pending    = ($schedulingStats['draft_schedules'] ?? 0) + ($schedulingStats['partial_schedules'] ?? 0);
@endphp

@if(count($conflicts) > 0 || count($unscheduledSubjects) > 0 || $overloaded > 0 || $pending > 0)
<div class="flex flex-wrap gap-2.5 px-5 pt-4">

    @if(count($conflicts) > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-rose-50 dark:bg-rose-500/[0.06]
                border border-rose-200 dark:border-rose-500/20
                shadow-[0_0_10px_rgba(239,68,68,0.1)] animate-pulse-slow">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e]"></span>
        <span class="text-[12px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-[0.12em]">
            {{ count($conflicts) }} Room Conflict{{ count($conflicts) !== 1 ? 's' : '' }} Detected
        </span>
    </div>
    @endif

    @if(count($unscheduledSubjects) > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-amber-50 dark:bg-amber-500/[0.06]
                border border-amber-200 dark:border-amber-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
        <span class="text-[12px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-[0.12em]">
            {{ count($unscheduledSubjects) }} Subjects Pending Scheduling
        </span>
    </div>
    @endif

    @if($overloaded > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-orange-50 dark:bg-orange-500/[0.06]
                border border-orange-200 dark:border-orange-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
        <span class="text-[12px] font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-[0.12em]">
            {{ $overloaded }} Faculty Overloaded
        </span>
    </div>
    @endif

    @if($pending > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-sky-50 dark:bg-sky-500/[0.06]
                border border-sky-200 dark:border-sky-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
        <span class="text-[12px] font-semibold text-sky-600 dark:text-sky-400 uppercase tracking-[0.12em]">
            {{ $pending }} Schedules Awaiting Review
        </span>
    </div>
    @endif

</div>
@endif


{{-- ═══════════════════════════════════════════════════
     HEADER STRIP  –  Identity + Live Clock + KPI Vitals
═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 p-5 pb-0">

    {{-- Identity / Role / Clock --}}
    <div class="col-span-12 lg:col-span-5
                bg-white dark:bg-white/[0.03]
                border border-slate-200 dark:border-white/10
                rounded-2xl px-6 py-4 backdrop-blur-sm shadow-sm dark:shadow-none
                flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 rounded-full bg-blue-400 shadow-[0_0_8px_#60a5fa] animate-pulse"></span>
                <span class="text-[11px] tracking-[0.25em] text-slate-500 dark:text-slate-500 uppercase">
                    Institutional Management — Registrar
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                CLASSLY <span class="text-blue-500 dark:text-blue-400">Scheduling</span>
            </h1>
            <p class="text-[12px] text-slate-500 mt-1">
                {{ date('l, F d, Y') }}
                &nbsp;·&nbsp;
                <span class="text-emerald-500 dark:text-emerald-400">{{ $systemStatus['current_semester'] ?? '—' }}</span>
            </p>
        </div>
        <div class="hidden md:flex flex-col items-end gap-1">
            <span x-text="clock" class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white tracking-widest"></span>
            <span class="text-[10px] text-slate-500 uppercase tracking-widest">Server Time (PHT)</span>
            @php $rate = $schedulingStats['completion_pct'] ?? 0; @endphp
            <span class="text-[11px] font-bold text-emerald-500 mt-0.5">{{ $rate }}% Complete</span>
        </div>
    </div>

    {{-- KPI Vitals Row --}}
    @php
        $vitals = [
            [
                'label' => 'Scheduling Progress',
                'value' => ($schedulingStats['completion_pct'] ?? 0) . '%',
                'color' => 'blue',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
            ],
            [
                'label' => 'Active Conflicts',
                'value' => count($conflicts),
                'color' => count($conflicts) > 0 ? 'rose' : 'emerald',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            ],
            [
                'label' => 'Unscheduled',
                'value' => $schedulingStats['unscheduled_subjects'] ?? 0,
                'color' => 'amber',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            ],
            [
                'label' => 'Finalized',
                'value' => $schedulingStats['finalized_schedules'] ?? 0,
                'color' => 'emerald',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
            ],
        ];
        $vColors = [
            'blue'    => 'border-blue-200   dark:border-blue-500/30   text-blue-600   dark:text-blue-400   bg-blue-50   dark:bg-blue-500/5',
            'rose'    => 'border-rose-200   dark:border-rose-500/30   text-rose-600   dark:text-rose-400   bg-rose-50   dark:bg-rose-500/5',
            'emerald' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/5',
            'amber'   => 'border-amber-200  dark:border-amber-500/30  text-amber-600  dark:text-amber-400  bg-amber-50  dark:bg-amber-500/5',
        ];
    @endphp

    <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
        @foreach($vitals as $v)
        <div class="card-lift border {{ $vColors[$v['color']] }} rounded-2xl p-4 flex flex-col gap-2.5 cursor-default">
            <span class="{{ $vColors[$v['color']] }} w-9 h-9 rounded-lg flex items-center justify-center">{!! $v['icon'] !!}</span>
            <span class="text-[38px] font-bold text-slate-900 dark:text-white tabular-nums leading-none">{{ $v['value'] }}</span>
            <span class="text-[13px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     WORKFLOW STATUS KPI CARDS  (6 cards)
═══════════════════════════════════════════════════════ --}}
@php
    $registrarCount = ($schedulingStats['draft_schedules'] ?? 0) + ($schedulingStats['partial_schedules'] ?? 0);
    $reviewCount    = $schedulingStats['partial_schedules'] ?? 0;
    $approvalCount  = $schedulingStats['draft_schedules']   ?? 0;
    $finalCount     = $schedulingStats['finalized_schedules'] ?? 0;
    $unscheduledCount = count($unscheduledSubjects);
    $conflictCount    = count($conflicts);

    $wfCards = [
        [
            'label'  => 'Draft',
            'count'  => $schedulingStats['draft_schedules'] ?? 0,
            'desc'   => 'Initial schedule entries',
            'glow'   => '',
            'dot'    => 'bg-slate-400',
            'border' => 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Generated',
            'count'  => $registrarCount,
            'desc'   => 'Auto-generated by Registrar',
            'glow'   => $registrarCount > 0 ? 'glow-blue' : '',
            'dot'    => 'bg-blue-400 shadow-[0_0_8px_#60a5fa]',
            'border' => $registrarCount > 0 ? 'border-blue-300 dark:border-blue-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Under Review',
            'count'  => $reviewCount,
            'desc'   => 'Faculty assigned & dept review',
            'glow'   => $reviewCount > 0 ? 'glow-yellow' : '',
            'dot'    => 'bg-yellow-400 shadow-[0_0_8px_#facc15]',
            'border' => $reviewCount > 0 ? 'border-yellow-300 dark:border-yellow-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Finalized',
            'count'  => $finalCount,
            'desc'   => 'Admin approved & finalized',
            'glow'   => $finalCount > 0 ? 'glow-green' : '',
            'dot'    => 'bg-emerald-400 shadow-[0_0_8px_#34d399]',
            'border' => $finalCount > 0 ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Unscheduled',
            'count'  => $unscheduledCount,
            'desc'   => 'Subjects without any schedule',
            'glow'   => $unscheduledCount > 0 ? 'glow-orange' : '',
            'dot'    => 'bg-orange-400 shadow-[0_0_8px_#fb923c]',
            'border' => $unscheduledCount > 0 ? 'border-orange-300 dark:border-orange-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Conflicts',
            'count'  => $conflictCount,
            'desc'   => 'Active scheduling conflicts',
            'glow'   => $conflictCount > 0 ? 'glow-red' : '',
            'dot'    => 'bg-rose-500 shadow-[0_0_8px_#f43f5e]',
            'border' => $conflictCount > 0 ? 'border-rose-300 dark:border-rose-500/40' : 'border-slate-200 dark:border-white/10',
        ],
    ];
@endphp

<div class="grid grid-cols-6 gap-3 px-5 pt-4">
    @foreach($wfCards as $card)
    <div class="card-lift {{ $card['glow'] }}
                bg-white dark:bg-white/[0.03]
                border {{ $card['border'] }}
                rounded-2xl p-5 flex flex-col gap-3 cursor-default relative overflow-hidden">

        @if($card['count'] > 0 && $card['dot'] !== 'bg-slate-400')
        <span class="absolute top-3 right-3 w-2 h-2">
            <span class="stage-ping absolute inset-0 rounded-full {{ $card['dot'] }} opacity-75"></span>
            <span class="relative block w-2 h-2 rounded-full {{ $card['dot'] }}"></span>
        </span>
        @endif

        <span class="w-2.5 h-2.5 rounded-full {{ $card['dot'] }} flex-shrink-0"></span>
        <span class="text-[40px] font-bold tabular-nums text-slate-900 dark:text-white leading-none">
            {{ number_format($card['count']) }}
        </span>
        <div>
            <p class="text-[14px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ $card['label'] }}</p>
            <p class="text-[12px] text-slate-400 mt-1 leading-snug">{{ $card['desc'] }}</p>
        </div>
    </div>
    @endforeach
</div>


{{-- ═══════════════════════════════════════════════════
     SCHEDULING WORKFLOW PIPELINE  (Matches Admin)
═══════════════════════════════════════════════════════ --}}
@php
    $stages = [
        [
            'step'   => '01',
            'title'  => 'Registrar',
            'sub'    => 'Auto-generating schedules',
            'count'  => $registrarCount,
            'active' => $registrarCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-blue-400/60 dark:ring-blue-500/50',
                'bg'    => 'bg-blue-500/10 dark:bg-blue-500/10',
                'label' => 'text-blue-600 dark:text-blue-400',
                'badge' => 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300',
                'glow'  => 'shadow-[0_0_24px_rgba(59,130,246,0.35)]',
                'conn'  => 'from-blue-400 to-yellow-400',
            ],
        ],
        [
            'step'   => '02',
            'title'  => 'Dept Review',
            'sub'    => 'Dean / OIC validation',
            'count'  => $reviewCount,
            'active' => $reviewCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-yellow-400/60 dark:ring-yellow-500/50',
                'bg'    => 'bg-yellow-500/10 dark:bg-yellow-500/10',
                'label' => 'text-yellow-600 dark:text-yellow-400',
                'badge' => 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-300',
                'glow'  => 'shadow-[0_0_24px_rgba(234,179,8,0.35)]',
                'conn'  => 'from-yellow-400 to-orange-400',
            ],
        ],
        [
            'step'   => '03',
            'title'  => 'Admin Approval',
            'sub'    => 'Final approval & conflict check',
            'count'  => $approvalCount,
            'active' => $approvalCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-orange-400/60 dark:ring-orange-500/50',
                'bg'    => 'bg-orange-500/10 dark:bg-orange-500/10',
                'label' => 'text-orange-600 dark:text-orange-400',
                'badge' => 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300',
                'glow'  => 'shadow-[0_0_24px_rgba(249,115,22,0.35)]',
                'conn'  => 'from-orange-400 to-emerald-400',
            ],
        ],
        [
            'step'   => '04',
            'title'  => 'Finalized',
            'sub'    => 'Locked & published schedules',
            'count'  => $finalCount,
            'active' => $finalCount > 0,
            'done'   => true,
            'palette' => [
                'ring'  => 'ring-emerald-400/60 dark:ring-emerald-500/50',
                'bg'    => 'bg-emerald-500/10 dark:bg-emerald-500/10',
                'label' => 'text-emerald-600 dark:text-emerald-400',
                'badge' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300',
                'glow'  => 'shadow-[0_0_24px_rgba(16,185,129,0.35)]',
                'conn'  => '',
            ],
        ],
    ];
@endphp

<div class="px-5 pt-4">
    <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">

        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Scheduling Workflow</h3>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">
                    Completion: <span class="text-emerald-500 font-bold">{{ $rate }}%</span>
                </span>
                <span class="text-[12px] text-slate-500">
                    {{ $systemStatus['current_semester'] ?? '—' }}
                </span>
            </div>
        </div>

        {{-- Pipeline --}}
        <div class="flex items-center gap-0">
            @foreach($stages as $i => $stage)

            {{-- Stage Node --}}
            <div class="flex-1 flex flex-col items-center relative
                        {{ $stage['active'] ? $stage['palette']['glow'] : '' }}
                        {{ $stage['active'] ? 'rounded-xl' : '' }}
                        transition-all duration-500">

                {{-- Circle + Ping --}}
                <div class="relative mb-3">
                    @if($stage['active'])
                    <span class="stage-ping absolute inset-0 rounded-full ring-1 {{ $stage['palette']['ring'] }}"></span>
                    @endif
                    <div class="relative w-12 h-12 rounded-full flex items-center justify-center
                                ring-1 {{ $stage['active'] ? $stage['palette']['ring'] : 'ring-slate-200 dark:ring-white/10' }}
                                {{ $stage['active'] ? $stage['palette']['bg'] : 'bg-slate-100 dark:bg-white/[0.03]' }}">
                        @if($stage['done'] && $stage['count'] > 0)
                        <svg class="w-5 h-5 {{ $stage['palette']['label'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        @else
                        <span class="text-sm font-bold {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }}">
                            {{ $stage['step'] }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Label + count --}}
                <p class="text-[13px] font-bold uppercase tracking-wider {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }} mb-1.5 text-center">
                    {{ $stage['title'] }}
                </p>
                <p class="text-[11px] text-slate-400 text-center leading-snug px-2 mb-2.5">{{ $stage['sub'] }}</p>

                @if($stage['count'] > 0)
                <span class="px-2.5 py-0.5 rounded-full text-[12px] font-bold {{ $stage['palette']['badge'] }}">
                    {{ number_format($stage['count']) }}
                </span>
                @else
                <span class="text-[11px] text-slate-400 dark:text-slate-600">—</span>
                @endif
            </div>

            {{-- Connector Arrow --}}
            @if(!$loop->last)
            <div class="w-12 flex items-center justify-center flex-shrink-0 mb-8">
                <div class="w-full h-0.5 {{ $stage['active'] ? 'flow-connector' : '' }} bg-gradient-to-r {{ $stage['palette']['conn'] ?: 'from-slate-200 to-slate-200 dark:from-white/10 dark:to-white/10' }}
                            {{ !$stage['active'] ? 'opacity-20' : '' }} rounded-full"></div>
            </div>
            @endif

            @endforeach
        </div>

        {{-- Overall progress bar --}}
        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/[0.05]">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">Overall Progress</span>
                <span class="text-[13px] font-bold text-emerald-500">
                    {{ $schedulingStats['finalized_schedules'] ?? 0 }} / {{ $schedulingStats['total_subjects'] ?? 0 }} finalized
                </span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                @php $barWidth = max(0, min(100, $rate)); @endphp
                <div class="h-full rounded-full progress-bar-shine"
                     style="width:{{ $barWidth }}%; background:linear-gradient(90deg,#34d399,#60a5fa,#34d399); background-size:200% auto;">
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     MAIN 3-COLUMN GRID
═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 p-5 pt-4">

    {{-- ─── LEFT COL: Schedule Breakdown + Room Utilization + Dept Scheduling ── --}}
    <div class="col-span-12 lg:col-span-3 flex flex-col gap-4">

        {{-- Schedule Breakdown --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Schedule Breakdown</h3>
            </div>
            @php
                $breakdown = [
                    ['label' => 'Scheduled',   'value' => $schedulingStats['scheduled_subjects']  ?? 0, 'color' => '#3b82f6', 'text' => 'text-blue-600 dark:text-blue-400'],
                    ['label' => 'Finalized',   'value' => $schedulingStats['finalized_schedules'] ?? 0, 'color' => '#10b981', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                    ['label' => 'Draft',       'value' => $schedulingStats['draft_schedules']     ?? 0, 'color' => '#94a3b8', 'text' => 'text-slate-600 dark:text-slate-400'],
                    ['label' => 'Partial',     'value' => $schedulingStats['partial_schedules']   ?? 0, 'color' => '#f59e0b', 'text' => 'text-amber-600 dark:text-amber-400'],
                    ['label' => 'Unscheduled', 'value' => $schedulingStats['unscheduled_subjects']?? 0, 'color' => '#ef4444', 'text' => 'text-rose-600 dark:text-rose-400'],
                ];
                $total = max(1, $schedulingStats['total_subjects'] ?? 1);
            @endphp
            <div class="space-y-4">
                @foreach($breakdown as $item)
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[13px] text-slate-600 dark:text-slate-400">{{ $item['label'] }}</span>
                        <span class="text-[13px] font-bold {{ $item['text'] }} tabular-nums">{{ $item['value'] }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ min(100, round(($item['value']/$total)*100)) }}%; background-color:{{ $item['color'] }};"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Room Utilization --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Room Utilization</h3>
            </div>
            <div class="space-y-3.5">
                @forelse($roomUtilization as $room)
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div>
                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ $room['name'] }}</span>
                            <span class="ml-1.5 text-[11px] text-slate-400">{{ $room['type'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[12px] tabular-nums text-slate-500">{{ $room['schedules'] }}</span>
                            @if($room['pct'] >= 80)
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            @elseif($room['pct'] >= 50)
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            @else
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            @endif
                        </div>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700
                                    {{ $room['pct'] >= 80 ? 'bg-rose-500' : ($room['pct'] >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width:{{ $room['pct'] }}%"></div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                    <p class="text-[13px]">No rooms available</p>
                </div>
                @endforelse
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/[0.05]">
                <span class="text-[12px] text-slate-500">
                    Total rooms: <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $schedulingStats['rooms_total'] ?? 0 }}</span>
                </span>
            </div>
        </div>

        {{-- Department Scheduling Progress --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Dept. Scheduling</h3>
            </div>
            @php
                $depts = [
                    ['code' => 'CCS',  'label' => 'CCS / IT',   'barColor' => '#eab308', 'text' => 'text-yellow-600 dark:text-yellow-400'],
                    ['code' => 'CTE',  'label' => 'CTE / ED',   'barColor' => '#3b82f6', 'text' => 'text-blue-600 dark:text-blue-400'],
                    ['code' => 'COC',  'label' => 'COC',        'barColor' => '#8b5cf6', 'text' => 'text-violet-600 dark:text-violet-400'],
                    ['code' => 'SHTM', 'label' => 'SHTM / HM',  'barColor' => '#f97316', 'text' => 'text-orange-600 dark:text-orange-400'],
                ];
            @endphp
            <div class="space-y-4">
                @foreach($depts as $dept)
                @php
                    $deptAliases   = \App\Models\Department::aliasesFor($dept['code']);
                    $deptTotal     = \App\Models\Subject::activeTerm()->whereIn('department', $deptAliases)->count();
                    $deptScheduled = \App\Models\Subject::activeTerm()->whereIn('department', $deptAliases)->whereHas('schedules', fn($q) => $q->activeTerm())->count();
                    $deptPct       = $deptTotal > 0 ? round(($deptScheduled / $deptTotal) * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ $dept['label'] }}</span>
                        <span class="text-[13px] font-bold {{ $dept['text'] }} tabular-nums">{{ $deptPct }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ $deptPct }}%; background-color:{{ $dept['barColor'] }};"></div>
                    </div>
                    <div class="flex items-center gap-2 mt-1.5 text-[11px] text-slate-400">
                        <span>{{ $deptScheduled }}/{{ $deptTotal }} subjects</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- end LEFT COL --}}


    {{-- ─── CENTER COL: Conflict Management + Unscheduled ── --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

        {{-- Conflict Management --}}
        <div class="bg-white dark:bg-white/[0.03]
                    border rounded-2xl p-5 shadow-sm dark:shadow-none
                    transition-all duration-500 flex flex-col
                    {{ count($conflicts) > 0
                        ? 'border-rose-300 dark:border-rose-500/40 shadow-[0_0_30px_rgba(239,68,68,0.08)]'
                        : 'border-emerald-200 dark:border-emerald-500/25 shadow-[0_0_20px_rgba(16,185,129,0.06)]' }}">

            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    @if(count($conflicts) > 0)
                    <span class="w-1 h-4 bg-rose-400 rounded-full shadow-[0_0_8px_#f87171] animate-pulse"></span>
                    @else
                    <span class="w-1 h-4 bg-emerald-400 rounded-full shadow-[0_0_8px_#34d399]"></span>
                    @endif
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Conflict Management</h3>
                </div>
                <span class="text-[12px] font-bold px-2.5 py-0.5 rounded-full
                    {{ count($conflicts) > 0
                        ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20'
                        : 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' }}">
                    {{ count($conflicts) }} {{ count($conflicts) === 1 ? 'conflict' : 'conflicts' }}
                </span>
            </div>

            @if(count($conflicts) === 0)
            {{-- No conflict success state --}}
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <div class="w-14 h-14 rounded-full
                            bg-emerald-50 dark:bg-emerald-500/[0.08]
                            border border-emerald-200 dark:border-emerald-500/25
                            flex items-center justify-center mb-4
                            shadow-[0_0_20px_rgba(16,185,129,0.15)] animate-pulse">
                    <svg class="w-7 h-7 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-[15px] font-semibold text-emerald-600 dark:text-emerald-400 mb-1">No Conflicts Detected</p>
                <p class="text-[13px] text-slate-400">All schedule assignments are clean.</p>
            </div>

            @else
            {{-- Conflict list --}}
            <div class="panel-scroll overflow-y-auto max-h-[360px] pr-1 space-y-3">
                @foreach($conflicts as $conflict)
                <div class="flex items-center gap-4 p-4 rounded-xl
                            bg-rose-50 dark:bg-rose-500/[0.05]
                            border border-rose-200 dark:border-rose-500/20
                            hover:border-rose-300 dark:hover:border-rose-500/40
                            transition-colors group fade-row">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="text-[13px] font-semibold text-rose-700 dark:text-rose-300">{{ $conflict['room'] }}</span>
                            <span class="w-1 h-1 rounded-full bg-rose-400"></span>
                            <span class="text-[12px] text-slate-500">{{ $conflict['day'] }}</span>
                            <span class="w-1 h-1 rounded-full bg-rose-400"></span>
                            <span class="text-[12px] text-slate-500">{{ $conflict['time'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">{{ $conflict['subject_a'] }}</span>
                            <svg class="w-3 h-3 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8m-8 5h8"/></svg>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">{{ $conflict['subject_b'] }}</span>
                        </div>
                    </div>
                    <button
                        wire:click="resolveConflict({{ $conflict['schedule_id'] }})"
                        wire:confirm="Remove this conflicting schedule?"
                        class="flex-shrink-0 px-3 py-1.5 rounded-lg text-[12px] font-bold uppercase
                               bg-rose-100 dark:bg-rose-500/10
                               border border-rose-200 dark:border-rose-500/20
                               text-rose-700 dark:text-rose-400
                               hover:bg-rose-200 dark:hover:bg-rose-500/20
                               transition-colors opacity-0 group-hover:opacity-100">
                        Resolve
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Unscheduled Subjects --}}
        <div class="bg-white dark:bg-white/[0.03] border rounded-2xl p-5 shadow-sm dark:shadow-none transition-all duration-500 flex flex-col
                    {{ count($unscheduledSubjects) > 0
                        ? 'border-amber-200 dark:border-amber-500/30 glow-orange'
                        : 'border-slate-200 dark:border-white/10' }}">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-amber-400 rounded-full shadow-[0_0_8px_#fbbf24]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Unscheduled Subjects</h3>
                </div>
                <span class="text-[12px] font-bold px-2.5 py-0.5 rounded-full
                              bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                              border border-amber-200 dark:border-amber-500/20 tabular-nums">
                    {{ count($unscheduledSubjects) }}
                </span>
            </div>

            @if(count($unscheduledSubjects) === 0)
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <p class="text-[14px] font-semibold text-emerald-600 dark:text-emerald-400 mb-1">All subjects scheduled!</p>
                <p class="text-[12px] text-slate-400">Nothing pending assignment.</p>
            </div>
            @else
            <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-2.5">
                @foreach($unscheduledSubjects as $subject)
                @php
                    $deptColorMap = [
                        'IT'  => 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20',
                        'CCS' => 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20',
                        'ACT' => 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20',
                        'CTE' => 'text-blue-700   dark:text-blue-400   bg-blue-100   dark:bg-blue-500/10   border-blue-200   dark:border-blue-500/20',
                        'ED'  => 'text-blue-700   dark:text-blue-400   bg-blue-100   dark:bg-blue-500/10   border-blue-200   dark:border-blue-500/20',
                        'COC' => 'text-violet-700 dark:text-violet-400 bg-violet-100 dark:bg-violet-500/10 border-violet-200 dark:border-violet-500/20',
                        'FB'  => 'text-violet-700 dark:text-violet-400 bg-violet-100 dark:bg-violet-500/10 border-violet-200 dark:border-violet-500/20',
                        'SHTM'=> 'text-orange-700 dark:text-orange-400 bg-orange-100 dark:bg-orange-500/10 border-orange-200 dark:border-orange-500/20',
                        'HM'  => 'text-orange-700 dark:text-orange-400 bg-orange-100 dark:bg-orange-500/10 border-orange-200 dark:border-orange-500/20',
                    ];
                    $deptClass = $deptColorMap[$subject['department'] ?? ''] ?? 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20';
                @endphp
                <div class="flex items-center gap-3 p-3.5 rounded-xl
                            bg-slate-50 dark:bg-white/[0.02]
                            border border-slate-200 dark:border-white/[0.05]
                            hover:border-amber-200 dark:hover:border-amber-500/20
                            transition-colors group fade-row">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <span class="text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ $subject['subject_code'] }}</span>
                            @if(!empty($subject['section']))
                            <span class="text-[11px] text-slate-400">· Sec {{ $subject['section'] }}</span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border {{ $deptClass }}">{{ $subject['department'] ?? '—' }}</span>
                            <span class="text-[11px] text-slate-400">Yr {{ $subject['year_level'] }}</span>
                        </div>
                        <p class="text-[12px] text-slate-500 truncate">{{ $subject['description'] }}</p>
                    </div>
                    <a href="{{ url('/schedules?subject_id=' . $subject['id']) }}"
                       class="flex-shrink-0 px-2.5 py-1.5 rounded-lg text-[11px] font-bold uppercase
                              bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20
                              text-blue-700 dark:text-blue-400
                              hover:bg-blue-200 dark:hover:bg-blue-500/20
                              transition-colors opacity-0 group-hover:opacity-100">
                        Assign
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>{{-- end CENTER COL --}}


    {{-- ─── RIGHT COL: Faculty Load + Approval Queue + Recent Activity ── --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

        {{-- Faculty Load --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Faculty Load</h3>
                </div>
                <span class="text-[12px] text-slate-400">{{ $schedulingStats['faculty_total'] ?? 0 }} approved</span>
            </div>

            <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-3.5">
                @forelse($facultyLoad as $faculty)
                @php
                    $loadPct    = min(100, ($faculty['max_units'] > 0) ? round(($faculty['load'] / $faculty['max_units']) * 100) : 0);
                    $barColor   = match($faculty['status']) {
                        'overloaded' => '#ef4444',
                        'unassigned' => '#94a3b8',
                        default      => '#3b82f6',
                    };
                    $labelClass = match($faculty['status']) {
                        'overloaded' => 'text-rose-600 dark:text-rose-400',
                        'unassigned' => 'text-slate-400',
                        default      => 'text-blue-600 dark:text-blue-400',
                    };
                @endphp
                <div>
                    <div class="flex justify-between items-start mb-1.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-tight">{{ $faculty['name'] }}</p>
                            <p class="text-[11px] text-slate-400">{{ $faculty['department'] ?? 'Gen Ed' }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                            <span class="text-[12px] font-bold tabular-nums {{ $labelClass }}">{{ $faculty['load'] }}/{{ $faculty['max_units'] }}</span>
                            @if($faculty['status'] === 'overloaded')
                            <span class="text-[10px] px-1.5 py-0.5 rounded
                                         bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400
                                         border border-rose-200 dark:border-rose-500/20 font-bold">OVR</span>
                            @elseif($faculty['status'] === 'unassigned')
                            <span class="text-[10px] px-1.5 py-0.5 rounded
                                         bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-500
                                         border border-slate-200 dark:border-white/10 font-bold">FREE</span>
                            @endif
                        </div>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ $loadPct }}%; background-color:{{ $barColor }};"></div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                    <p class="text-[13px]">No faculty data</p>
                </div>
                @endforelse
            </div>

            {{-- Load summary --}}
            @php
                $overloadedCount = collect($facultyLoad)->where('status','overloaded')->count();
                $unassignedCount = collect($facultyLoad)->where('status','unassigned')->count();
                $normalCount     = collect($facultyLoad)->where('status','normal')->count();
            @endphp
            <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-white/[0.05] grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-[18px] font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ $overloadedCount }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Overloaded</p>
                </div>
                <div class="border-x border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[18px] font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ $normalCount }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Normal</p>
                </div>
                <div>
                    <p class="text-[18px] font-bold text-slate-500 dark:text-slate-500 tabular-nums">{{ $unassignedCount }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Free</p>
                </div>
            </div>
        </div>

        {{-- Approval Queue (Pending Schedules) --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col flex-1">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval Queue</h3>
                    @if(isset($approvalQueue) && count($approvalQueue) > 0)
                    <span class="px-1.5 py-0.5 rounded-full
                                 bg-orange-100 dark:bg-orange-500/10
                                 text-orange-700 dark:text-orange-400
                                 border border-orange-200 dark:border-orange-500/20
                                 text-[11px] font-bold">{{ count($approvalQueue) }}</span>
                    @endif
                </div>
                <span class="text-[11px] text-slate-400 uppercase tracking-widest">Pending Finalization</span>
            </div>

            <div class="panel-scroll overflow-y-auto max-h-[320px] pr-1">
                @forelse($approvalQueue ?? [] as $i => $q)
                <div class="fade-row border-b border-slate-100 dark:border-white/[0.04] last:border-0 py-3.5"
                     style="animation-delay:{{ $i * 40 }}ms">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">
                                    {{ $q['department'] }}-{{ $q['major'] }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded
                                             bg-slate-100 dark:bg-white/[0.05]
                                             text-slate-500 dark:text-slate-400 text-[11px]">
                                    Y{{ $q['year_level'] }}-{{ $q['section'] }}
                                </span>
                            </div>
                            <p class="text-[14px] font-semibold text-slate-800 dark:text-slate-200 truncate mb-1">{{ $q['subject'] }}</p>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                <span>{{ $q['room'] }}</span>
                                <span>·</span>
                                <span>{{ $q['day'] }}</span>
                                <span>{{ $q['time'] }}</span>
                            </div>
                        </div>
                        <button wire:click="finalizeSchedule({{ $q['id'] }})"
                                wire:confirm="Finalize this schedule? This action cannot be undone."
                                class="px-3 py-1.5 rounded-lg
                                       bg-emerald-100 dark:bg-emerald-500/10
                                       border border-emerald-200 dark:border-emerald-500/20
                                       text-emerald-700 dark:text-emerald-400
                                       text-[11px] font-bold uppercase
                                       hover:bg-emerald-200 dark:hover:bg-emerald-500/20
                                       transition-colors flex-shrink-0">
                            Finalize
                        </button>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-32 text-slate-400">
                    <svg class="w-9 h-9 mb-2.5 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[13px]">No schedules pending</p>
                    <p class="text-[11px] mt-1 text-slate-400">All caught up!</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>{{-- end RIGHT COL --}}

</div>{{-- end 3-column grid --}}


{{-- ═══════════════════════════════════════════════════
     RECENT ACTIVITY FEED  +  COMPLETION SUMMARY
═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 px-5 pb-5">

    {{-- Recent Activity --}}
    <div class="col-span-12 lg:col-span-9
                bg-white dark:bg-white/[0.03]
                border border-slate-200 dark:border-white/10
                rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col">

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Recent Activity</h3>
            </div>
            <span class="flex items-center gap-1.5 text-[12px] text-emerald-500">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live
            </span>
        </div>

        @php
            $sColor = [
                'critical' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20',
                'warning'  => 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20',
                'success'  => 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20',
                'info'     => 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20',
            ];
        @endphp

        <div class="panel-scroll overflow-y-auto max-h-[320px] pr-1 space-y-3">
            @forelse($recentActivities as $activity)
            @php $sc = $sColor[$activity['severity'] ?? 'info']; @endphp
            <div class="flex gap-2.5 group fade-row">
                <div class="flex flex-col items-center gap-1 flex-shrink-0">
                    <div class="w-7 h-7 rounded-lg {{ $sc }} border flex items-center justify-center text-[11px] font-bold">
                        {{ strtoupper(substr($activity['module'], 0, 2)) }}
                    </div>
                    <div class="flex-1 w-px bg-slate-100 dark:bg-white/[0.04]"></div>
                </div>
                <div class="pb-3.5 min-w-0 flex-1">
                    <p class="text-[13px] text-slate-700 dark:text-slate-300 leading-snug line-clamp-2">{{ $activity['description'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">{{ $activity['user'] }} · {{ $activity['time'] }}</p>
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                <p class="text-[13px]">No recent activity</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Completion Summary --}}
    <div class="col-span-12 lg:col-span-3
                bg-white dark:bg-white/[0.03]
                border border-slate-200 dark:border-white/10
                rounded-2xl p-5 shadow-sm dark:shadow-none">

        <div class="flex items-center gap-2 mb-5">
            <span class="w-1 h-4 bg-emerald-400 rounded-full shadow-[0_0_8px_#34d399]"></span>
            <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Summary</h3>
        </div>

        @php
            $summary = [
                ['label' => 'Scheduled',    'val' => $schedulingStats['scheduled_subjects']  ?? 0, 'text' => 'text-blue-600 dark:text-blue-400',    'border' => 'border-blue-100 dark:border-blue-500/10'],
                ['label' => 'Finalized',    'val' => $schedulingStats['finalized_schedules'] ?? 0, 'text' => 'text-emerald-600 dark:text-emerald-400','border' => 'border-emerald-100 dark:border-emerald-500/10'],
                ['label' => 'Unscheduled',  'val' => $schedulingStats['unscheduled_subjects']?? 0, 'text' => 'text-amber-600 dark:text-amber-400',   'border' => 'border-amber-100 dark:border-amber-500/10'],
                ['label' => 'Under Review', 'val' => $schedulingStats['partial_schedules']   ?? 0, 'text' => 'text-yellow-600 dark:text-yellow-400', 'border' => 'border-yellow-100 dark:border-yellow-500/10'],
            ];
        @endphp

        <div class="grid grid-cols-2 gap-3">
            @foreach($summary as $item)
            <div class="rounded-xl bg-slate-50 dark:bg-white/[0.02] border {{ $item['border'] }} p-3 text-center">
                <p class="text-[28px] font-bold {{ $item['text'] }} tabular-nums leading-tight">{{ number_format($item['val']) }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5 uppercase tracking-wider">{{ $item['label'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Circular completion indicator --}}
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.05]">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">Completion</span>
                <span class="text-[13px] font-bold text-emerald-500">{{ $rate }}%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                <div class="h-full rounded-full progress-bar-shine"
                     style="width:{{ $rate }}%; background:linear-gradient(90deg,#34d399,#60a5fa,#34d399); background-size:200% auto;">
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2 text-center">
                {{ $schedulingStats['finalized_schedules'] ?? 0 }} / {{ $schedulingStats['total_subjects'] ?? 0 }} finalized
            </p>
        </div>
    </div>

</div>{{-- end activity + summary row --}}

</div>{{-- end root --}}