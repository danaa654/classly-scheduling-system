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
    @keyframes glowPulseRed    { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}   50%{box-shadow:0 0 16px 4px rgba(239,68,68,0.3);}   }
    @keyframes glowPulseGreen  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}  50%{box-shadow:0 0 16px 4px rgba(16,185,129,0.3);}  }
    @keyframes glowPulseBlue   { 0%,100%{box-shadow:0 0 0 0 rgba(96,165,250,0);}  50%{box-shadow:0 0 16px 4px rgba(96,165,250,0.3);}  }
    @keyframes glowPulseYellow { 0%,100%{box-shadow:0 0 0 0 rgba(250,204,21,0);}  50%{box-shadow:0 0 16px 4px rgba(250,204,21,0.3);}  }
    @keyframes glowPulseOrange { 0%,100%{box-shadow:0 0 0 0 rgba(251,146,60,0);}  50%{box-shadow:0 0 16px 4px rgba(251,146,60,0.3);}  }
    .glow-green  { animation: glowPulseGreen  3s ease-in-out infinite; }
    .glow-red    { animation: glowPulseRed    3s ease-in-out infinite; }
    .glow-blue   { animation: glowPulseBlue   3s ease-in-out infinite; }
    .glow-yellow { animation: glowPulseYellow 3s ease-in-out infinite; }
    .glow-orange { animation: glowPulseOrange 3s ease-in-out infinite; }

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

    /* ── Filter pill active state ───────────────────── */
    .filter-pill.active {
        background: rgba(59,130,246,0.12);
        border-color: rgba(59,130,246,0.4);
        color: #3b82f6;
    }
    .dark .filter-pill.active {
        background: rgba(96,165,250,0.1);
        border-color: rgba(96,165,250,0.35);
        color: #60a5fa;
    }
</style>

<div
    x-data="{
        clock: '{{ date('H:i:s') }}',
        facultyDeptFilter: 'all',
        facultyRoleFilter: 'all',
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
     SMART ALERTS STRIP
═══════════════════════════════════════════════════════ --}}
@if(! $systemReady)
<div class="px-5 pt-4 banner-slide">
    <div class="rounded-2xl border border-amber-300 bg-amber-50 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/[0.06]">
        <div class="p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-[15px] font-bold tracking-tight text-amber-800 dark:text-amber-300">New Semester - Setup Required</h2>
                    <p class="mt-1 text-[12px] leading-snug text-amber-700/80 dark:text-amber-400/70">
                        Registrar can configure this semester too. Complete the checklist, then mark the system ready for Dean-level roles.
                    </p>
                </div>

                <div class="flex flex-shrink-0 flex-col items-stretch gap-2 sm:flex-row lg:flex-col">
                    @if($setupComplete)
                        <button wire:click="openMarkReadyModal"
                                class="rounded-xl bg-emerald-500 px-4 py-2 text-[12px] font-bold uppercase tracking-wider text-white shadow-[0_4px_14px_rgba(16,185,129,0.4)] transition-colors hover:bg-emerald-600">
                            Mark as Ready
                        </button>
                    @else
                        <button disabled
                                class="cursor-not-allowed rounded-xl bg-slate-200 px-4 py-2 text-[12px] font-bold uppercase tracking-wider text-slate-400 opacity-60 dark:bg-white/10 dark:text-slate-500">
                            Mark as Ready
                        </button>
                    @endif

                    <a href="{{ route('settings', ['unlock' => 1]) }}"
                       wire:navigate
                       class="rounded-xl border border-amber-300 bg-amber-100 px-4 py-2 text-center text-[12px] font-bold uppercase tracking-wider text-amber-800 transition-colors hover:bg-amber-200 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20">
                        Open Settings
                    </a>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach($setupChecklist as $i => $item)
                    <div class="flex items-start gap-2.5 rounded-xl p-3 {{ $item['done'] ? 'border border-emerald-200 bg-emerald-50 dark:border-emerald-500/25 dark:bg-emerald-500/[0.06]' : 'border border-amber-200/60 bg-white/60 dark:border-amber-500/20 dark:bg-white/[0.03]' }}">
                        <div class="mt-0.5 flex-shrink-0">
                            @if($item['done'])
                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-400 text-white">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-amber-300 bg-amber-100 dark:border-amber-500/50 dark:bg-amber-500/10">
                                    <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400">{{ $i + 1 }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] font-bold leading-snug {{ $item['done'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-800 dark:text-amber-300' }}">{{ $item['label'] }}</p>
                            <p class="mt-0.5 text-[10px] leading-snug {{ $item['done'] ? 'text-emerald-600/70 dark:text-emerald-500/70' : 'text-amber-700/70 dark:text-amber-400/60' }}">{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@else
<div class="px-5 pt-4 banner-slide">
    <div class="flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 dark:border-emerald-500/25 dark:bg-emerald-500/[0.05]">
        <div class="flex items-center gap-3">
            <span class="h-2 w-2 flex-shrink-0 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></span>
            <div>
                <span class="text-[13px] font-bold text-emerald-700 dark:text-emerald-400">System is Live</span>
                <span class="ml-2 text-[12px] text-slate-500 dark:text-slate-400">{{ $systemStatus['current_semester'] ?? '-' }}</span>
            </div>
        </div>
        <span class="rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:border-emerald-500/25 dark:bg-white/[0.04] dark:text-emerald-400">
            Ready to Manage Schedule
        </span>
    </div>
</div>
@endif

@php
    $overloaded = collect($facultyLoad)->where('status', 'overloaded')->count();
@endphp

@if(count($conflicts) > 0 || $overloaded > 0)
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
            <span class="text-[11px] font-semibold text-emerald-500 mt-0.5">{{ $rate }}% Complete</span>
        </div>
    </div>

    {{-- KPI Vitals Row: Scheduling Progress, Total Faculty, Total Rooms, Total Schedules --}}
    @php
        $vitals = [
            [
                'label' => 'Scheduling Progress',
                'value' => ($schedulingStats['completion_pct'] ?? 0),
                'suffix' => '%',
                'color' => 'blue',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
            ],
            [
                'label' => 'Total Faculty',
                'value' => $schedulingStats['faculty_total'] ?? count($facultyLoad),
                'suffix' => '',
                'color' => 'violet',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            ],
            [
                'label' => 'Total Rooms',
                'value' => $schedulingStats['rooms_total'] ?? 0,
                'suffix' => '',
                'color' => 'cyan',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
            ],
            [
                'label' => 'Total Schedules',
                'value' => $schedulingStats['total_subjects'] ?? 0,
                'suffix' => '',
                'color' => 'emerald',
                'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            ],
        ];
        $vColors = [
            'blue'    => 'border-blue-200   dark:border-blue-500/30   text-blue-600   dark:text-blue-400   bg-blue-50   dark:bg-blue-500/5',
            'violet'  => 'border-violet-200 dark:border-violet-500/30 text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/5',
            'cyan'    => 'border-cyan-200   dark:border-cyan-500/30   text-cyan-600   dark:text-cyan-400   bg-cyan-50   dark:bg-cyan-500/5',
            'emerald' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/5',
        ];
    @endphp

    <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
        @foreach($vitals as $v)
        <div class="card-lift border {{ $vColors[$v['color']] }} rounded-2xl p-3.5 flex flex-col gap-2 cursor-default">
            <span class="{{ $vColors[$v['color']] }} w-7 h-7 rounded-lg flex items-center justify-center">{!! $v['icon'] !!}</span>
            <span class="text-[28px] font-bold text-slate-900 dark:text-white tabular-nums leading-none">{{ number_format((float)$v['value']) }}{{ $v['suffix'] ?? '' }}</span>
            <span class="text-[11px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     WORKFLOW STATUS COUNTS STRIP
═══════════════════════════════════════════════════════ --}}
@php
    // Derive workflow counts from schedulingStats if $workflowCounts is not passed
    $wf = $workflowCounts ?? [
        'draft'            => $schedulingStats['draft_schedules']     ?? 0,
        'partial'          => $schedulingStats['partial_schedules']   ?? 0,
        'faculty_assigned' => $schedulingStats['partial_schedules']   ?? 0,
        'finalized'        => $schedulingStats['finalized_schedules'] ?? 0,
        'conflict_count'   => count($conflicts ?? []),
    ];

    // ── Unscheduled: subjects from Manage Subjects with NO schedule row at all.
    // Computed as: total listed subjects − every subject that has any schedule status.
    // This mirrors exactly what the admin dashboard shows and is accurate regardless
    // of whether $schedulingAnalytics is populated by the registrar controller.
    $totalListedSubjects  = $schedulingStats['total_subjects'] ?? 0;
    $totalScheduledCount  = ($wf['draft'] ?? 0)
                          + ($wf['partial'] ?? 0)
                          + ($wf['faculty_assigned'] ?? 0)
                          + ($wf['finalized'] ?? 0);
    $unscheduledCount     = max(0, $totalListedSubjects - $totalScheduledCount);

    $wfCards = [
        [
            'label'    => 'Draft',
            'count'    => $wf['draft'] ?? 0,
            'desc'     => 'Initial schedule entries',
            'color'    => 'slate',
            'glow'     => '',
            'dot'      => 'bg-slate-400',
            'badge'    => 'bg-slate-100 dark:bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
            'border'   => 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Generated',
            'count'    => $wf['partial'] ?? 0,
            'desc'     => 'Auto-generated by Registrar',
            'color'    => 'blue',
            'glow'     => ($wf['partial'] ?? 0) > 0 ? 'glow-blue' : '',
            'dot'      => 'bg-blue-400 shadow-[0_0_8px_#60a5fa]',
            'badge'    => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
            'border'   => ($wf['partial'] ?? 0) > 0 ? 'border-blue-300 dark:border-blue-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Under Review',
            'count'    => $wf['faculty_assigned'] ?? 0,
            'desc'     => 'Faculty assigned & dept review',
            'color'    => 'yellow',
            'glow'     => ($wf['faculty_assigned'] ?? 0) > 0 ? 'glow-yellow' : '',
            'dot'      => 'bg-yellow-400 shadow-[0_0_8px_#facc15]',
            'badge'    => 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20',
            'border'   => ($wf['faculty_assigned'] ?? 0) > 0 ? 'border-yellow-300 dark:border-yellow-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Finalized',
            'count'    => $wf['finalized'] ?? 0,
            'desc'     => 'Subjects finalized & locked',
            'color'    => 'emerald',
            'glow'     => ($wf['finalized'] ?? 0) > 0 ? 'glow-green' : '',
            'dot'      => 'bg-emerald-400 shadow-[0_0_8px_#34d399]',
            'badge'    => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
            'border'   => ($wf['finalized'] ?? 0) > 0 ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Unscheduled',
            'count'    => $unscheduledCount,
            'desc'     => 'Subjects not yet assigned a schedule',
            'color'    => 'orange',
            'glow'     => $unscheduledCount > 0 ? 'glow-orange' : '',
            'dot'      => 'bg-orange-400 shadow-[0_0_8px_#fb923c]',
            'badge'    => 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20',
            'border'   => $unscheduledCount > 0 ? 'border-orange-300 dark:border-orange-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Conflicts',
            'count'    => $wf['conflict_count'] ?? 0,
            'desc'     => 'Active scheduling conflicts',
            'color'    => 'rose',
            'glow'     => ($wf['conflict_count'] ?? 0) > 0 ? 'glow-red' : '',
            'dot'      => 'bg-rose-500 shadow-[0_0_8px_#f43f5e]',
            'badge'    => 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
            'border'   => ($wf['conflict_count'] ?? 0) > 0 ? 'border-rose-300 dark:border-rose-500/40' : 'border-slate-200 dark:border-white/10',
        ],
    ];
@endphp

<div class="px-5 pt-4">
    <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl px-4 py-3 shadow-sm dark:shadow-none">
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($wfCards as $card)
            <div class="card-lift flex flex-col gap-1.5 p-3 rounded-xl border {{ $card['border'] }} {{ $card['glow'] }} transition-all duration-500 cursor-default">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $card['dot'] }}"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400 truncate">
                        {{ $card['label'] }}
                    </span>
                </div>
                <span class="text-[26px] font-bold tabular-nums text-slate-900 dark:text-white leading-none">
                    {{ number_format($card['count']) }}
                </span>
                <span class="text-[10px] text-slate-400 leading-snug">{{ $card['desc'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     SCHEDULING WORKFLOW PIPELINE
═══════════════════════════════════════════════════════ --}}
@php
    $configCount      = $schedulingStats['total_subjects'] ?? 0;
    $preparationCount = $wf['faculty_assigned'] ?? 0;
    $generatedCount   = ($schedulingStats['draft_schedules'] ?? 0) + ($schedulingStats['partial_schedules'] ?? 0);
    $reviewCount      = $schedulingStats['partial_schedules'] ?? 0;
    $finalCount       = $schedulingStats['finalized_schedules'] ?? 0;

    $stages = [
        [
            'step'   => '01',
            'title'  => 'System Config',
            'sub'    => 'Admin prepares term setup',
            'count'  => $configCount,
            'active' => $configCount > 0,
            'done'   => true,
            'palette' => [
                'ring'  => 'ring-slate-400/60 dark:ring-slate-500/50',
                'bg'    => 'bg-slate-500/10 dark:bg-slate-500/10',
                'label' => 'text-slate-600 dark:text-slate-300',
                'badge' => 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300',
                'glow'  => 'shadow-[0_0_24px_rgba(100,116,139,0.25)]',
                'conn'  => 'from-slate-400 to-teal-400',
            ],
        ],
        [
            'step'   => '02',
            'title'  => 'Preparation',
            'sub'    => 'Dean / OIC / Associate Dean pre-assigns faculty',
            'count'  => $preparationCount,
            'active' => $preparationCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-teal-400/60 dark:ring-teal-500/50',
                'bg'    => 'bg-teal-500/10 dark:bg-teal-500/10',
                'label' => 'text-teal-600 dark:text-teal-400',
                'badge' => 'bg-teal-100 dark:bg-teal-500/20 text-teal-700 dark:text-teal-300',
                'glow'  => 'shadow-[0_0_24px_rgba(20,184,166,0.35)]',
                'conn'  => 'from-teal-400 to-blue-400',
            ],
        ],
        [
            'step'   => '03',
            'title'  => 'Auto-generate',
            'sub'    => 'Admin / Registrar can run anytime',
            'count'  => $generatedCount,
            'active' => $generatedCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-blue-400/60 dark:ring-blue-500/50',
                'bg'    => 'bg-blue-500/10 dark:bg-blue-500/10',
                'label' => 'text-blue-600 dark:text-blue-400',
                'badge' => 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300',
                'glow'  => 'shadow-[0_0_24px_rgba(59,130,246,0.35)]',
                'conn'  => 'from-blue-400 to-amber-400',
            ],
        ],
        [
            'step'   => '04',
            'title'  => 'Dept Review',
            'sub'    => 'Remove allowed while partial',
            'count'  => $reviewCount,
            'active' => $reviewCount > 0,
            'done'   => false,
            'palette' => [
                'ring'  => 'ring-amber-400/60 dark:ring-amber-500/50',
                'bg'    => 'bg-amber-500/10 dark:bg-amber-500/10',
                'label' => 'text-amber-600 dark:text-amber-400',
                'badge' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
                'glow'  => 'shadow-[0_0_24px_rgba(245,158,11,0.35)]',
                'conn'  => 'from-amber-400 to-emerald-400',
            ],
        ],
        [
            'step'   => '05',
            'title'  => 'Finalize',
            'sub'    => 'Admin / Registrar locks schedule',
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
    <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-4 shadow-sm dark:shadow-none">

        <div class="flex items-center justify-between mb-4">
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
                <div class="relative mb-2">
                    @if($stage['active'])
                    <span class="stage-ping absolute inset-0 rounded-full ring-1 {{ $stage['palette']['ring'] }}"></span>
                    @endif
                    <div class="relative w-9 h-9 rounded-full flex items-center justify-center
                                ring-1 {{ $stage['active'] ? $stage['palette']['ring'] : 'ring-slate-200 dark:ring-white/10' }}
                                {{ $stage['active'] ? $stage['palette']['bg'] : 'bg-slate-100 dark:bg-white/[0.03]' }}">
                        @if($stage['done'] && $stage['count'] > 0)
                        <svg class="w-4 h-4 {{ $stage['palette']['label'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        @else
                        <span class="text-xs font-bold {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }}">
                            {{ $stage['step'] }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Label + count --}}
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }} mb-1 text-center">
                    {{ $stage['title'] }}
                </p>
                <p class="text-[10px] text-slate-400 text-center leading-snug px-2 mb-2">{{ $stage['sub'] }}</p>

                @if($stage['count'] > 0)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $stage['palette']['badge'] }}">
                    {{ number_format($stage['count']) }}
                </span>
                @else
                <span class="text-[10px] text-slate-400 dark:text-slate-600">—</span>
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

        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-[12px] font-medium leading-snug text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
            Step 02 is optional and improves generation quality. Auto-generation is still available to Admin and Registrar even if preparation is skipped.
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
     MAIN 2-COLUMN GRID: LEFT (Conflicts + Unscheduled) | RIGHT (Faculty Load + Room Utilization)
═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 p-5 pt-4 pb-6">

    {{-- ─── LEFT COL: Conflict Management + Unscheduled Subjects ── --}}
    <div class="col-span-12 lg:col-span-7 flex flex-col gap-4">

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
            <div class="flex flex-col items-center justify-center py-8 text-center">
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
            <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-3">
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

        {{-- ── Unscheduled Subjects + Recent Activity — side by side ── --}}
        <div class="grid grid-cols-2 gap-4">

            {{-- Unscheduled Subjects --}}
            <div class="bg-white dark:bg-white/[0.03] border rounded-2xl p-4 shadow-sm dark:shadow-none transition-all duration-500 flex flex-col
                        {{ count($unscheduledSubjects) > 0
                            ? 'border-amber-200 dark:border-amber-500/30'
                            : 'border-slate-200 dark:border-white/10' }}">

                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-amber-400 rounded-full shadow-[0_0_8px_#fbbf24]"></span>
                        <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Unscheduled</h3>
                    </div>
                    <span class="text-[12px] font-bold px-2 py-0.5 rounded-full
                                  bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                                  border border-amber-200 dark:border-amber-500/20 tabular-nums">
                        {{ count($unscheduledSubjects) }}
                    </span>
                </div>

                @if(count($unscheduledSubjects) === 0)
                <div class="flex flex-col items-center justify-center py-6 text-center flex-1">
                    <p class="text-[13px] font-semibold text-emerald-600 dark:text-emerald-400 mb-0.5">All scheduled!</p>
                    <p class="text-[11px] text-slate-400">Nothing pending.</p>
                </div>
                @else
                <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-2">
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
                    <div class="flex items-start gap-2 p-2.5 rounded-xl
                                bg-slate-50 dark:bg-white/[0.02]
                                border border-slate-200 dark:border-white/[0.05]
                                hover:border-amber-200 dark:hover:border-amber-500/20
                                transition-colors group fade-row">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                                <span class="text-[12px] font-bold text-slate-800 dark:text-slate-200">{{ $subject['subject_code'] }}</span>
                                @if(!empty($subject['section']))
                                <span class="text-[10px] text-slate-400">Sec {{ $subject['section'] }}</span>
                                @endif
                                <span class="px-1 py-0.5 rounded text-[10px] font-bold border {{ $deptClass }}">{{ $subject['department'] ?? '—' }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 truncate">{{ $subject['description'] }}</p>
                        </div>
                        <a href="{{ url('/schedules?subject_id=' . $subject['id']) }}"
                           class="flex-shrink-0 px-2 py-1 rounded-lg text-[10px] font-bold uppercase
                                  bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20
                                  text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-500/20
                                  transition-colors opacity-0 group-hover:opacity-100">
                            Assign
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-4 shadow-sm dark:shadow-none flex flex-col">

                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                        <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Activity</h3>
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

                <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-2.5">
                    @forelse($recentActivities as $activity)
                    @php $sc = $sColor[$activity['severity'] ?? 'info']; @endphp
                    <div class="flex gap-2 group fade-row">
                        <div class="flex flex-col items-center gap-1 flex-shrink-0">
                            <div class="w-6 h-6 rounded-lg {{ $sc }} border flex items-center justify-center text-[10px] font-bold">
                                {{ strtoupper(substr($activity['module'], 0, 2)) }}
                            </div>
                            <div class="flex-1 w-px bg-slate-100 dark:bg-white/[0.04]"></div>
                        </div>
                        <div class="pb-3 min-w-0 flex-1">
                            <p class="text-[12px] text-slate-700 dark:text-slate-300 leading-snug line-clamp-2">{{ $activity['description'] }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $activity['user'] }} · {{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                        <p class="text-[13px]">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end 2-col sub-grid --}}

        {{-- ── Pending Faculty Requests ── --}}
        <div class="bg-white dark:bg-white/[0.03]
                    border rounded-2xl p-4 shadow-sm dark:shadow-none flex flex-col
                    {{ count($pendingFaculty) > 0
                        ? 'border-amber-200 dark:border-amber-500/30 shadow-[0_0_20px_rgba(251,191,36,0.06)]'
                        : 'border-slate-200 dark:border-white/10' }}">

            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full shadow-[0_0_8px_#f59e0b]
                                 {{ count($pendingFaculty) > 0 ? 'bg-amber-400 animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                        Pending Faculty Requests
                    </h3>
                </div>
                <div class="flex items-center gap-2">
                    @if(count($pendingFaculty) > 0)
                    <span class="text-[12px] font-bold px-2 py-0.5 rounded-full tabular-nums
                                 bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                                 border border-amber-200 dark:border-amber-500/20">
                        {{ count($pendingFaculty) }}
                    </span>
                    @endif
                    <a href="{{ url('/manage-faculty?filter=pending') }}"
                       class="text-[11px] font-semibold uppercase tracking-wider
                              text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300
                              transition-colors">
                        View All →
                    </a>
                </div>
            </div>

            @if(count($pendingFaculty) === 0)
            <div class="flex flex-col items-center justify-center py-5 text-center flex-1">
                <div class="w-10 h-10 rounded-full
                            bg-emerald-50 dark:bg-emerald-500/[0.07]
                            border border-emerald-200 dark:border-emerald-500/20
                            flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-[13px] font-semibold text-emerald-600 dark:text-emerald-400">No Pending Requests</p>
                <p class="text-[11px] text-slate-400 mt-0.5">All faculty requests have been processed.</p>
            </div>

            @else
            <div class="panel-scroll overflow-y-auto max-h-[220px] pr-1 space-y-2">
                @foreach($pendingFaculty as $pf)
                @php
                    $scopeBadge = match($pf['scope']) {
                        'GENED'            => 'bg-cyan-100 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/20',
                        'CROSS-DEPARTMENT' => 'bg-violet-100 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-500/20',
                        default            => 'bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/10',
                    };
                @endphp
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                            bg-amber-50/60 dark:bg-amber-500/[0.04]
                            border border-amber-100 dark:border-amber-500/15
                            hover:border-amber-300 dark:hover:border-amber-500/30
                            transition-colors group fade-row">

                    {{-- Avatar --}}
                    <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-500/10
                                border border-amber-200 dark:border-amber-500/20
                                flex items-center justify-center flex-shrink-0">
                        <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                            {{ strtoupper(substr($pf['name'], 0, 1)) }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300 truncate">{{ $pf['name'] }}</p>
                            <span class="text-[10px] px-1.5 py-0.5 rounded border font-semibold {{ $scopeBadge }}">
                                {{ $pf['scope'] }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">
                            {{ $pf['department'] }}
                            &nbsp;·&nbsp;
                            <span class="text-slate-500 dark:text-slate-500">Req. by {{ $pf['requested_by'] }}</span>
                            &nbsp;·&nbsp;
                            {{ $pf['submitted_at'] }}
                        </p>
                    </div>

                    {{-- Quick action --}}
                    <a href="{{ url('/manage-faculty?highlight=' . $pf['id']) }}"
                       class="flex-shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase
                              bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20
                              text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-500/20
                              transition-colors opacity-0 group-hover:opacity-100">
                        Review
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>{{-- end LEFT COL --}}


    {{-- ─── RIGHT COL: Faculty Load + Room Utilization ── --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

        {{-- Faculty Load --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col"
             x-data="{
                deptFilter: 'all',
                roleFilter: 'all',
                deptOpen: false,
                roleOpen: false,
                deptLabels: { all: 'All', CCS: 'CCS / IT', CTE: 'CTE / ED', COC: 'COC', SHTM: 'SHTM / HM', 'Institution-wide': 'Inst. Wide' },
                roleLabels: { all: 'All', departmental: 'Departmental', 'cross-departmental': 'Cross-Dept.', 'institution-wide': 'Institution-Wide' }
             }"
             @click.outside="deptOpen = false; roleOpen = false">

            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Faculty Load</h3>
                </div>
                <span class="text-[12px] text-slate-400">{{ $schedulingStats['faculty_total'] ?? count($facultyLoad) }} total</span>
            </div>

            {{-- Filter Dropdowns: Department + Assignment Type side by side --}}
            <div class="flex gap-2 mb-3">

                {{-- Department Dropdown --}}
                <div class="relative flex-1" x-data>
                    <button
                        @click.stop="deptOpen = !deptOpen; roleOpen = false"
                        :class="deptFilter !== 'all'
                            ? 'border-blue-300 dark:border-blue-500/40 bg-blue-50 dark:bg-blue-500/[0.08] text-blue-700 dark:text-blue-400'
                            : 'border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.04] text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'"
                        class="w-full flex items-center justify-between gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition-colors">
                        <span class="flex items-center gap-1.5">
                            {{-- building icon --}}
                            <svg class="w-3 h-3 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span x-text="deptLabels[deptFilter] ?? 'Dept'"></span>
                        </span>
                        <svg class="w-3 h-3 flex-shrink-0 opacity-50 transition-transform duration-150" :class="deptOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="deptOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                        @click.stop
                        class="absolute z-30 left-0 top-full mt-1 w-full min-w-[130px]
                               bg-white dark:bg-[#131929]
                               border border-slate-200 dark:border-white/10
                               rounded-xl shadow-xl dark:shadow-black/40
                               overflow-hidden py-1">
                        @php
                            $deptOptions = [
                                'all'              => 'All',
                                'CCS'              => 'CCS / IT',
                                'CTE'              => 'CTE / ED',
                                'COC'              => 'COC',
                                'SHTM'             => 'SHTM / HM',
                                'Institution-wide' => 'Inst. Wide',
                            ];
                        @endphp
                        @foreach($deptOptions as $val => $label)
                        <button
                            @click="deptFilter = '{{ $val }}'; deptOpen = false"
                            :class="deptFilter === '{{ $val }}'
                                ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/[0.04]'"
                            class="w-full flex items-center justify-between px-3 py-1.5 text-[11px] font-semibold transition-colors">
                            <span>{{ $label }}</span>
                            <svg x-show="deptFilter === '{{ $val }}'" class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Assignment Type Dropdown --}}
                <div class="relative flex-1" x-data>
                    <button
                        @click.stop="roleOpen = !roleOpen; deptOpen = false"
                        :class="roleFilter !== 'all'
                            ? 'border-violet-300 dark:border-violet-500/40 bg-violet-50 dark:bg-violet-500/[0.08] text-violet-700 dark:text-violet-400'
                            : 'border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.04] text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20'"
                        class="w-full flex items-center justify-between gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition-colors">
                        <span class="flex items-center gap-1.5 min-w-0">
                            {{-- users icon --}}
                            <svg class="w-3 h-3 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="truncate" x-text="roleLabels[roleFilter] ?? 'Type'"></span>
                        </span>
                        <svg class="w-3 h-3 flex-shrink-0 opacity-50 transition-transform duration-150" :class="roleOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="roleOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                        @click.stop
                        class="absolute z-30 right-0 top-full mt-1 w-full min-w-[150px]
                               bg-white dark:bg-[#131929]
                               border border-slate-200 dark:border-white/10
                               rounded-xl shadow-xl dark:shadow-black/40
                               overflow-hidden py-1">
                        @php
                            $roleOptions = [
                                'all'                => 'All',
                                'departmental'       => 'Departmental',
                                'cross-departmental' => 'Cross-Dept.',
                                'institution-wide'   => 'Institution-Wide',
                            ];
                        @endphp
                        @foreach($roleOptions as $val => $label)
                        <button
                            @click="roleFilter = '{{ $val }}'; roleOpen = false"
                            :class="roleFilter === '{{ $val }}'
                                ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/[0.04]'"
                            class="w-full flex items-center justify-between px-3 py-1.5 text-[11px] font-semibold transition-colors">
                            <span>{{ $label }}</span>
                            <svg x-show="roleFilter === '{{ $val }}'" class="w-3 h-3 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        @endforeach
                    </div>
                </div>

            </div>{{-- end filter row --}}

            {{-- Faculty List (scrollable – first 5 visible, scroll for more) --}}
            <div class="panel-scroll overflow-y-auto max-h-[330px] pr-1 space-y-3">
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

                    // Determine faculty role for filtering
                    $facultyDept = $faculty['department'] ?? '';
                    $isInstitutionWide = in_array(strtolower($facultyDept), ['institution-wide', 'gen ed', 'general education']);
                    $isCrossDept = ($faculty['is_cross_dept'] ?? false);
                    $facultyRole = $isInstitutionWide ? 'institution-wide' : ($isCrossDept ? 'cross-departmental' : 'departmental');

                    // Normalize dept for filtering
                    $deptNorm = $isInstitutionWide ? 'Institution-wide' : strtoupper($facultyDept);
                    $deptMatch = in_array($deptNorm, ['CCS', 'IT', 'ACT']) ? 'CCS' :
                                 (in_array($deptNorm, ['CTE', 'ED']) ? 'CTE' :
                                 (in_array($deptNorm, ['SHTM', 'HM']) ? 'SHTM' :
                                 ($isInstitutionWide ? 'Institution-wide' : $deptNorm)));
                @endphp
                <div
                    x-show="(deptFilter === 'all' || deptFilter === '{{ $deptMatch }}') && (roleFilter === 'all' || roleFilter === '{{ $facultyRole }}')"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="flex justify-between items-start mb-1.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-tight">{{ $faculty['name'] }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <p class="text-[11px] text-slate-400">{{ $facultyDept ?: 'Gen Ed' }}</p>
                                <span class="text-[10px] px-1.5 py-0.5 rounded
                                             {{ $isInstitutionWide
                                                ? 'bg-cyan-100 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-500/20'
                                                : ($isCrossDept
                                                   ? 'bg-violet-100 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400 border border-violet-200 dark:border-violet-500/20'
                                                   : 'bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-500 border border-slate-200 dark:border-white/10') }}
                                             font-semibold">
                                    {{ $isInstitutionWide ? 'Inst. Wide' : ($isCrossDept ? 'Cross-Dept' : 'Dept') }}
                                </span>
                            </div>
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
        </div>

        {{-- Room Utilization --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Room Utilization</h3>
                </div>
                <span class="text-[12px] text-slate-400">
                    {{ $schedulingStats['rooms_total'] ?? 0 }} rooms
                </span>
            </div>
            <div class="panel-scroll overflow-y-auto max-h-[260px] pr-1 space-y-3.5">
                @forelse($roomUtilization as $room)
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div>
                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ $room['name'] }}</span>
                            <span class="ml-1.5 text-[11px] text-slate-400 uppercase">{{ $room['type'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[12px] tabular-nums text-slate-500">{{ $room['schedules'] }} scheduled</span>
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
        </div>

    </div>{{-- end RIGHT COL --}}

</div>{{-- end main 2-column grid --}}


@if($confirmingMarkReady)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-md rounded-2xl border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-500/20 dark:bg-[#0f172a]">
        <h3 class="text-[16px] font-bold text-slate-900 dark:text-white">Mark System as Ready?</h3>
        <p class="mt-3 text-[13px] leading-relaxed text-slate-600 dark:text-slate-400">
            This will notify Dean, OIC, and Assistant Dean roles that the semester configuration is ready.
        </p>

        <div class="mt-5 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/[0.03]">
            @foreach($setupChecklist as $item)
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-[12px] text-slate-600 dark:text-slate-400">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex gap-2">
            <button wire:click="cancelMarkReady"
                    class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-[13px] font-bold uppercase tracking-wider text-slate-600 transition-colors hover:bg-slate-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/[0.04]">
                Cancel
            </button>
            <button wire:click="confirmMarkReady"
                    class="flex-1 rounded-xl bg-emerald-500 px-4 py-2.5 text-[13px] font-bold uppercase tracking-wider text-white transition-colors hover:bg-emerald-600">
                Yes, Go Live
            </button>
        </div>
    </div>
</div>
@endif

@if($confirmingMarkNotReady)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-md rounded-2xl border border-rose-200 bg-white p-6 shadow-2xl dark:border-rose-500/20 dark:bg-[#0f172a]">
        <h3 class="text-[16px] font-bold text-slate-900 dark:text-white">Reopen Semester Setup?</h3>
        <p class="mt-3 text-[13px] leading-relaxed text-slate-600 dark:text-slate-400">
            This marks the system as not ready. Dean-level roles will see the waiting state until the system is marked ready again.
        </p>

        <div class="mt-6 flex gap-2">
            <button wire:click="cancelMarkNotReady"
                    class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-[13px] font-bold uppercase tracking-wider text-slate-600 transition-colors hover:bg-slate-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/[0.04]">
                Cancel
            </button>
            <button wire:click="confirmMarkNotReady"
                    class="flex-1 rounded-xl bg-rose-500 px-4 py-2.5 text-[13px] font-bold uppercase tracking-wider text-white transition-colors hover:bg-rose-600">
                Reopen Setup
            </button>
        </div>
    </div>
</div>
@endif



</div>{{-- end root --}}
