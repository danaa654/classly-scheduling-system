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
    .panel-scroll {
        overflow-y: auto;
        scroll-behavior: smooth;
    }
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
    @keyframes glowPulseBlue   { 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,0);} 50%{box-shadow:0 0 16px 4px rgba(59,130,246,0.3);} }
    @keyframes glowPulseYellow { 0%,100%{box-shadow:0 0 0 0 rgba(234,179,8,0);}  50%{box-shadow:0 0 16px 4px rgba(234,179,8,0.3);}  }
    @keyframes glowPulseOrange { 0%,100%{box-shadow:0 0 0 0 rgba(249,115,22,0);} 50%{box-shadow:0 0 16px 4px rgba(249,115,22,0.35);} }
    @keyframes glowPulseGreen  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0);} 50%{box-shadow:0 0 16px 4px rgba(16,185,129,0.3);} }
    @keyframes glowPulseRed    { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}  50%{box-shadow:0 0 16px 4px rgba(239,68,68,0.3);}  }
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

    /* ── Fade-in stagger for table rows ─────────────── */
    @keyframes fadeSlideIn {
        from { opacity:0; transform:translateY(6px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-row { animation: fadeSlideIn 0.35s ease both; }
</style>

<div
    x-data="{
        clock: '{{ date('h:i:s A') }}',
        init() { setInterval(() => { this.clock = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); }, 1000); }
    }"
    class="min-h-screen w-full font-mono antialiased overflow-x-hidden transition-colors duration-500
           bg-slate-100 dark:bg-[#080d1a]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image:radial-gradient(ellipse at 15% 15%,rgba(59,130,246,0.07) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(139,92,246,0.05) 0%,transparent 55%);'
        : 'background-image:radial-gradient(ellipse at 15% 15%,rgba(59,130,246,0.06) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(139,92,246,0.04) 0%,transparent 55%);'"
>

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
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse"></span>
                <span class="text-[11px] tracking-[0.25em] text-slate-500 dark:text-slate-500 uppercase">
                    System Administrator — Admin
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                CLASSLY <span class="text-blue-500 dark:text-blue-400">Control</span>
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
        </div>
    </div>

    {{-- KPI Vitals Row --}}
    @php
        $vitals = [
            ['label'=>'Users',     'value'=>$stats['total_users'],     'color'=>'blue',    'icon'=>'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
            ['label'=>'Faculty',   'value'=>$stats['total_faculty'],   'color'=>'violet',  'icon'=>'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>'],
            ['label'=>'Rooms',     'value'=>$stats['total_rooms'],     'color'=>'cyan',    'icon'=>'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'],
            ['label'=>'Total Schedules', 'value'=>$stats['total_subjects'] ?? $stats['total_schedules'], 'color'=>'emerald', 'icon'=>'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'],
        ];
        $vColors = [
            'blue'    => 'border-blue-200 dark:border-blue-500/30 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/5',
            'violet'  => 'border-violet-200 dark:border-violet-500/30 text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/5',
            'cyan'    => 'border-cyan-200 dark:border-cyan-500/30 text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/5',
            'emerald' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/5',
        ];
    @endphp

        <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
        @foreach($vitals as $v)
        <div class="card-lift border {{ $vColors[$v['color']] }} rounded-2xl p-3.5 flex flex-col gap-2 cursor-default">
            <span class="{{ $vColors[$v['color']] }} w-7 h-7 rounded-lg flex items-center justify-center">{!! $v['icon'] !!}</span>
            <span class="text-[28px] font-bold text-slate-900 dark:text-white tabular-nums leading-none">{{ number_format($v['value']) }}</span>
            <span class="text-[11px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     WORKFLOW STATUS KPI CARDS  (6 cards)
═══════════════════════════════════════════════════════ --}}
@php
    $wf = $workflowCounts;
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
            'desc'     => 'Admin approved & finalized',
            'color'    => 'emerald',
            'glow'     => ($wf['finalized'] ?? 0) > 0 ? 'glow-green' : '',
            'dot'      => 'bg-emerald-400 shadow-[0_0_8px_#34d399]',
            'badge'    => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
            'border'   => ($wf['finalized'] ?? 0) > 0 ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'    => 'Unscheduled',
            'count'    => $schedulingAnalytics['subjects_unscheduled'] ?? 0,
            'desc'     => 'Subjects without any schedule',
            'color'    => 'orange',
            'glow'     => ($schedulingAnalytics['subjects_unscheduled'] ?? 0) > 0 ? 'glow-orange' : '',
            'dot'      => 'bg-orange-400 shadow-[0_0_8px_#fb923c]',
            'badge'    => 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20',
            'border'   => ($schedulingAnalytics['subjects_unscheduled'] ?? 0) > 0 ? 'border-orange-300 dark:border-orange-500/40' : 'border-slate-200 dark:border-white/10',
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
     WORKFLOW PIPELINE  –  Animated Stage Visualizer
═══════════════════════════════════════════════════════ --}}
@php
    $stages = [
        [
            'step'    => '01',
            'title'   => 'Registrar',
            'sub'     => 'Auto-generates partial schedules',
            'count'   => $workflowCounts['partial'] ?? 0,
            'active'  => ($workflowCounts['partial'] ?? 0) > 0,
            'done'    => false,
            'palette' => [
                'ring'   => 'ring-blue-400/60 dark:ring-blue-500/50',
                'bg'     => 'bg-blue-500/10 dark:bg-blue-500/10',
                'label'  => 'text-blue-600 dark:text-blue-400',
                'badge'  => 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300',
                'glow'   => 'shadow-[0_0_24px_rgba(59,130,246,0.35)]',
                'conn'   => 'from-blue-400 to-yellow-400',
            ],
        ],
        [
            'step'    => '02',
            'title'   => 'Dept Review',
            'sub'     => 'Dean / OIC reviews assignments',
            'count'   => $workflowCounts['faculty_assigned'] ?? 0,
            'active'  => ($workflowCounts['faculty_assigned'] ?? 0) > 0,
            'done'    => false,
            'palette' => [
                'ring'   => 'ring-yellow-400/60 dark:ring-yellow-500/50',
                'bg'     => 'bg-yellow-500/10 dark:bg-yellow-500/10',
                'label'  => 'text-yellow-600 dark:text-yellow-400',
                'badge'  => 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-300',
                'glow'   => 'shadow-[0_0_24px_rgba(234,179,8,0.35)]',
                'conn'   => 'from-yellow-400 to-orange-400',
            ],
        ],
        [
            'step'    => '03',
            'title'   => 'Admin Approval',
            'sub'     => 'System Admin gives final sign-off',
            'count'   => $workflowCounts['faculty_assigned'] ?? 0,
            'active'  => ($workflowCounts['faculty_assigned'] ?? 0) > 0,
            'done'    => false,
            'palette' => [
                'ring'   => 'ring-orange-400/60 dark:ring-orange-500/50',
                'bg'     => 'bg-orange-500/10 dark:bg-orange-500/10',
                'label'  => 'text-orange-600 dark:text-orange-400',
                'badge'  => 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300',
                'glow'   => 'shadow-[0_0_24px_rgba(249,115,22,0.35)]',
                'conn'   => 'from-orange-400 to-emerald-400',
            ],
        ],
        [
            'step'    => '04',
            'title'   => 'Finalized',
            'sub'     => 'Schedules published & locked',
            'count'   => $workflowCounts['finalized'] ?? 0,
            'active'  => ($workflowCounts['finalized'] ?? 0) > 0,
            'done'    => true,
            'palette' => [
                'ring'   => 'ring-emerald-400/60 dark:ring-emerald-500/50',
                'bg'     => 'bg-emerald-500/10 dark:bg-emerald-500/10',
                'label'  => 'text-emerald-600 dark:text-emerald-400',
                'badge'  => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300',
                'glow'   => 'shadow-[0_0_24px_rgba(16,185,129,0.35)]',
                'conn'   => '',
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
                @php
                    $totalSubjects = $stats['total_subjects'] ?? $stats['total_schedules'];
                    $finalizedCount = $wf['finalized'] ?? $schedulingAnalytics['finalized'] ?? 0;
                    $rate = $totalSubjects > 0 ? round(($finalizedCount / $totalSubjects) * 100, 1) : 0;
                @endphp
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

        {{-- Overall progress bar --}}
        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/[0.05]">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">Overall Progress</span>
                <span class="text-[13px] font-bold text-emerald-500">{{ $wf['finalized'] ?? $schedulingAnalytics['finalized'] ?? 0 }} / {{ $stats['total_subjects'] ?? $stats['total_schedules'] }} finalized</span>
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

    {{-- ─── LEFT COL: Department Progress ──────────── --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

        {{-- Dept Progress Tracker --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-5">
                <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Department Progress</h3>
            </div>

            @php
                $deptColors = [
                    'yellow' => ['bar'=>'#eab308','text'=>'text-yellow-600 dark:text-yellow-400','bg'=>'bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20'],
                    'blue'   => ['bar'=>'#3b82f6','text'=>'text-blue-600 dark:text-blue-400',    'bg'=>'bg-blue-100   dark:bg-blue-500/10   border-blue-200   dark:border-blue-500/20'],
                    'violet' => ['bar'=>'#8b5cf6','text'=>'text-violet-600 dark:text-violet-400','bg'=>'bg-violet-100 dark:bg-violet-500/10 border-violet-200 dark:border-violet-500/20'],
                    'orange' => ['bar'=>'#f97316','text'=>'text-orange-600 dark:text-orange-400','bg'=>'bg-orange-100 dark:bg-orange-500/10 border-orange-200 dark:border-orange-500/20'],
                ];
            @endphp

            <div class="space-y-5">
                @forelse($departmentProgress as $dept)
                @php $dc = $deptColors[$dept['color']] ?? $deptColors['blue']; @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border {{ $dc['bg'] }} {{ $dc['text'] }}">
                                {{ $dept['code'] }}
                            </span>
                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ $dept['code'] }}</span>
                            <span class="text-[11px] text-slate-400">({{ $dept['majors'] }})</span>
                        </div>
                        <span class="text-[13px] font-bold {{ $dc['text'] }}">{{ $dept['rate'] }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ $dept['rate'] }}%; background-color: {{ $dc['bar'] }};"></div>
                    </div>
                    <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-500">
                        <span>{{ $dept['total'] }} subjects</span>
                        <span class="text-emerald-500">✓ {{ $dept['finalized'] }} finalized</span>
                        @if($dept['pending'] > 0)
                        <span class="text-orange-400">{{ $dept['pending'] }} pending</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                    <p class="text-[13px]">No department data found</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Pending Faculty Verifications --}}
        @if(count($pendingVerifications) > 0)
        <div class="bg-yellow-50 dark:bg-yellow-500/5 border border-yellow-200 dark:border-yellow-500/20 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-400 shadow-[0_0_6px_#facc15] animate-pulse"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-yellow-700 dark:text-yellow-400">
                    Faculty Verifications
                    <span class="ml-2 px-1.5 py-0.5 rounded bg-yellow-200 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-300 text-[11px] font-bold">
                        {{ count($pendingVerifications) }}
                    </span>
                </h3>
            </div>
            <div class="space-y-3.5">
                @foreach($pendingVerifications as $f)
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ $f['name'] }}</p>
                        <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $f['department'] ?? 'No dept' }} · {{ $f['time'] }}</p>
                    </div>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <button wire:click="approveFaculty({{ $f['id'] }})"
                                class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[11px] font-bold uppercase hover:bg-emerald-200 dark:hover:bg-emerald-500/20 transition-colors">
                            Approve
                        </button>
                        <button wire:click="rejectFaculty({{ $f['id'] }})"
                                class="px-2.5 py-1 rounded-lg bg-rose-100 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[11px] font-bold uppercase hover:bg-rose-200 dark:hover:bg-rose-500/20 transition-colors">
                            Reject
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Global Audit Log (moved here, into System Status's old slot) --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex-1 flex flex-col min-h-0">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Global Audit Log</h3>
                </div>
                <span class="flex items-center gap-1.5 text-[11px] text-emerald-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live
                </span>
            </div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-[11px] text-slate-400">{{ count($recentActivities) }} entries</span>
                <a href="{{ route('manage-users') }}"
                   class="text-[11px] font-bold uppercase text-blue-600 dark:text-blue-400 hover:underline">
                    Manage Users
                </a>
            </div>

            @php
                $auditColors = [
                    'critical' => 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10',
                    'warning'  => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-500/10',
                    'success'  => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10',
                    'info'     => 'text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/10',
                ];
            @endphp

            <div class="panel-scroll overflow-y-auto flex-1 min-h-0 pr-1">
                @forelse($recentActivities as $i => $a)
                @php $ac = $auditColors[$a['severity'] ?? 'info']; @endphp
                <div class="fade-row border-b border-slate-100 dark:border-white/[0.04] last:border-0 py-3"
                     style="animation-delay: {{ $i * 30 }}ms">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase flex-shrink-0 {{ $ac }}">{{ $a['action'] }}</span>
                            <span class="text-[10px] font-bold uppercase text-slate-500 truncate">{{ $a['module'] }}</span>
                        </div>
                        <span class="text-[11px] text-slate-400 tabular-nums whitespace-nowrap flex-shrink-0">{{ $a['created_at'] }}</span>
                    </div>
                    <p class="text-[12px] text-slate-600 dark:text-slate-400 leading-snug line-clamp-2">{{ $a['description'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">{{ $a['user'] }}@if(!empty($a['role'])) · {{ $a['role'] }} @endif</p>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                    <p class="text-[13px]">No activity logs found</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ─── CENTER COL: Approval Queue Table + System Status ────────── --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex-1 flex flex-col min-h-0">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval Queue</h3>
                    @if(count($approvalQueue) > 0)
                    <span class="px-1.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 text-[11px] font-bold border border-orange-200 dark:border-orange-500/20">
                        {{ count($approvalQueue) }}
                    </span>
                    @endif
                </div>
                <span class="text-[12px] text-slate-400 uppercase tracking-widest">Faculty-Assigned Schedules</span>
            </div>

            <div class="panel-scroll overflow-y-auto flex-1 min-h-0 pr-1">
                @forelse($approvalQueue as $i => $q)
                <div class="fade-row border-b border-slate-100 dark:border-white/[0.04] last:border-0 py-3.5"
                     style="animation-delay: {{ $i * 40 }}ms">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">
                                    {{ $q['department'] }}-{{ $q['major'] }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.05] text-slate-500 dark:text-slate-400 text-[11px]">
                                    Y{{ $q['year_level'] }}-{{ $q['section'] }}
                                </span>
                            </div>
                            <p class="text-[14px] font-semibold text-slate-800 dark:text-slate-200 truncate mb-1">
                                {{ $q['subject'] }}
                            </p>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400">
                                <span>{{ $q['room'] }}</span>
                                <span>·</span>
                                <span>{{ $q['day'] }}</span>
                                <span>{{ $q['time'] }}</span>
                                <span>·</span>
                                <span class="truncate max-w-[100px]">{{ $q['faculty'] }}</span>
                            </div>
                        </div>
                        <button wire:click="finalizeSchedule({{ $q['id'] }})"
                                wire:confirm="Finalize this schedule? This action cannot be undone."
                                class="px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[11px] font-bold uppercase hover:bg-emerald-200 dark:hover:bg-emerald-500/20 transition-colors flex-shrink-0">
                            Finalize
                        </button>
                    </div>
                </div>
                @empty
                <div class="flex items-center gap-3 py-5 px-2 text-slate-400">
                    <svg class="w-5 h-5 opacity-40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-[13px] text-slate-500">No schedules pending approval</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Queue is clear — all caught up.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- System Status (moved below Approval Queue) --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-emerald-400 rounded-full shadow-[0_0_8px_#34d399]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">System Status</h3>
            </div>
            @php
                $statusRows = [
                    ['label'=>'AI Scheduler',    'status'=>$systemStatus['scheduler'],       'detail'=>$systemStatus['conflict_count'].' conflicts'],
                    ['label'=>'Database',        'status'=>$systemStatus['db_tables'],       'detail'=>'Connected'],
                    ['label'=>'Pending Faculty', 'status'=>$stats['pending_faculty']>0?'warning':'healthy', 'detail'=>$stats['pending_faculty'].' queued'],
                    ['label'=>'Active Term',     'status'=>'healthy',                        'detail'=>$systemStatus['current_semester'] ?? '—'],
                ];
                $dot = [
                    'healthy'  => 'bg-emerald-400 shadow-[0_0_6px_#34d399]',
                    'warning'  => 'bg-yellow-400 shadow-[0_0_6px_#facc15]',
                    'critical' => 'bg-rose-500 shadow-[0_0_6px_#f43f5e]',
                ];
            @endphp
            <div class="space-y-3.5">
                @foreach($statusRows as $row)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dot[$row['status']] ?? $dot['healthy'] }}"></span>
                        <span class="text-[13px] text-slate-600 dark:text-slate-400">{{ $row['label'] }}</span>
                    </div>
                    <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">{{ $row['detail'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ─── RIGHT COL: Activity Feed + Alerts ──────── --}}
    <div class="col-span-12 lg:col-span-2 flex flex-col gap-4">

        {{-- Critical Alerts --}}
        @if(count($conflictAlerts) > 0)
        <div class="bg-rose-50 dark:bg-rose-500/[0.04] border border-rose-200 dark:border-rose-500/20 rounded-2xl p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e] animate-pulse"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400">Critical Alerts</h3>
            </div>
            <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-44 pr-1">
                @foreach($conflictAlerts as $alert)
                <div class="flex gap-2 items-start">
                    <span class="mt-0.5 flex-shrink-0">
                        @if(($alert['type'] ?? '') === 'overload')
                        <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        @else
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        @if(($alert['type'] ?? '') === 'overload')
                        <p class="text-[12px] font-bold text-yellow-700 dark:text-yellow-400 leading-snug">{{ $alert['name'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $alert['dept'] }} · {{ $alert['load'] }} sessions overloaded</p>
                        @else
                        <p class="text-[12px] font-bold text-rose-700 dark:text-rose-400 leading-snug">Room: {{ $alert['room'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $alert['day'] }} · {{ $alert['time'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Activity Feed --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex-1 flex flex-col">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Activity Feed</h3>
            </div>

            @php
                $sColor = [
                    'critical' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20',
                    'warning'  => 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20',
                    'success'  => 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20',
                    'info'     => 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20',
                ];
            @endphp

            <div class="panel-scroll overflow-y-auto max-h-[380px] pr-1 space-y-3">
                @forelse($recentActivities as $activity)
                @php $sc = $sColor[$activity['severity'] ?? 'info']; @endphp
                <div class="flex gap-2.5 group">
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
    </div>
</div>


</div>