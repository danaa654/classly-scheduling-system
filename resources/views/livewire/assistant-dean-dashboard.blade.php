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
    @keyframes glowPulseBlue   { 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,0);}   50%{box-shadow:0 0 16px 4px rgba(59,130,246,0.3);}   }
    @keyframes glowPulseYellow { 0%,100%{box-shadow:0 0 0 0 rgba(234,179,8,0);}    50%{box-shadow:0 0 16px 4px rgba(234,179,8,0.3);}    }
    @keyframes glowPulseOrange { 0%,100%{box-shadow:0 0 0 0 rgba(249,115,22,0);}   50%{box-shadow:0 0 16px 4px rgba(249,115,22,0.35);}  }
    @keyframes glowPulseGreen  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}   50%{box-shadow:0 0 16px 4px rgba(16,185,129,0.3);}   }
    @keyframes glowPulseRed    { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}    50%{box-shadow:0 0 16px 4px rgba(239,68,68,0.3);}    }
    @keyframes glowPulseRose   { 0%,100%{box-shadow:0 0 0 0 rgba(244,63,94,0);}    50%{box-shadow:0 0 16px 4px rgba(244,63,94,0.3);}    }
    .glow-blue   { animation: glowPulseBlue   3s ease-in-out infinite; }
    .glow-yellow { animation: glowPulseYellow 3s ease-in-out infinite; }
    .glow-orange { animation: glowPulseOrange 3s ease-in-out infinite; }
    .glow-green  { animation: glowPulseGreen  3s ease-in-out infinite; }
    .glow-red    { animation: glowPulseRed    3s ease-in-out infinite; }
    .glow-rose   { animation: glowPulseRose   3s ease-in-out infinite; }

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

    /* ── Queue row hover ─────────────────────────────── */
    .queue-row { transition: background 0.14s ease; }
    .queue-row:hover { background: rgba(249,115,22,0.03); }
    .dark .queue-row:hover { background: rgba(249,115,22,0.04); }

    /* ── line-clamp utility ───────────────────────────── */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

{{-- ════════════════════════════════════════════════════════════════════
     ROOT WRAPPER — Alpine live clock + dark/light radial tint
════════════════════════════════════════════════════════════════════════ --}}
<div
    x-data="{
        clock: '{{ date('h:i:s A') }}',
        init() { setInterval(() => { this.clock = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); }, 1000); }
    }"
    class="min-h-screen w-full font-mono antialiased overflow-x-hidden transition-colors duration-500
           bg-slate-100 dark:bg-[#080d1a]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image:radial-gradient(ellipse at 15% 15%,rgba(251,146,60,0.06) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(244,63,94,0.05) 0%,transparent 55%);'
        : 'background-image:radial-gradient(ellipse at 15% 15%,rgba(251,146,60,0.07) 0%,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(244,63,94,0.05) 0%,transparent 55%);'"
>

{{-- ════════════════════════════════════════════════════════════════════
     HEADER STRIP — Identity · Clock · KPI Vitals
════════════════════════════════════════════════════════════════════════ --}}
@php
    $period = \App\Models\Setting::getAcademicPeriod();
@endphp
<div class="grid grid-cols-12 gap-4 p-5 pb-0">

    {{-- Identity / Role / Clock ——————————————————————————————————————— --}}
    <div class="col-span-12 lg:col-span-5
                bg-white dark:bg-white/[0.03]
                border border-slate-200 dark:border-white/10
                rounded-2xl px-6 py-4 backdrop-blur-sm shadow-sm dark:shadow-none
                flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 rounded-full bg-orange-400 shadow-[0_0_8px_#fb923c] animate-pulse"></span>
                <span class="text-[11px] tracking-[0.25em] text-slate-500 dark:text-slate-500 uppercase">
                    Minor Scheduling &amp; Faculty Coordination
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Associate Dean <span class="text-orange-500 dark:text-orange-400">Portal</span>
            </h1>
            <p class="text-[12px] text-slate-500 mt-1">
                {{ date('l, F d, Y') }}
                &nbsp;·&nbsp;
                <span class="text-orange-500 dark:text-orange-400">
                    {{ \App\Models\Setting::semesterLabel($period['semester'] ?? '1st') }}
                    {{ $period['school_year'] ?? '2026-2027' }}
                </span>
            </p>
        </div>
        <div class="hidden md:flex flex-col items-end gap-1">
            <span x-text="clock" class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white tracking-widest"></span>
            <span class="text-[10px] text-slate-500 uppercase tracking-widest">Server Time (PHT)</span>
        </div>
    </div>

    {{-- KPI Vitals Row ————————————————————————————————————————————————— --}}
    @php
        $vitals = [
            ['label' => 'Faculty',   'value' => $globalStats['total_faculty'],       'color' => 'orange', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
            ['label' => 'Subjects',  'value' => $globalStats['total_subjects'],      'color' => 'amber',  'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'],
            ['label' => 'Minor Subs', 'value' => $globalStats['minor_subjects'],      'color' => 'yellow', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
            ['label' => 'Rooms',      'value' => $globalStats['total_rooms'],          'color' => 'emerald','icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'],
        ];
        $vColors = [
            'orange'  => 'border-orange-200 dark:border-orange-500/30 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-500/5',
            'amber'   => 'border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/5',
            'yellow'  => 'border-yellow-200 dark:border-yellow-500/30 text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-500/5',
            'emerald' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/5',
        ];
    @endphp
    <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
        @foreach($vitals as $v)
        <div class="card-lift border {{ $vColors[$v['color']] }} rounded-2xl p-3.5 flex flex-col gap-2 cursor-default">
            <span class="{{ $vColors[$v['color']] }} w-7 h-7 rounded-lg flex items-center justify-center">{!! $v['icon'] !!}</span>
            <span class="text-[28px] font-bold text-slate-900 dark:text-white tabular-nums leading-none">{{ $v['value'] }}</span>
            <span class="text-[11px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════
     WORKFLOW STATUS KPI CARDS — 6 cards matching Admin Dashboard styling
════════════════════════════════════════════════════════════════════════ --}}
@php
    // Derive workflow counts from available component data
    $unassignedCount  = collect($curriculumValidation)->where('issue', 'No faculty assigned')->count()
                      + collect($scheduleReview)->where('faculty', 'Unassigned')->count();
    $partialCount     = collect($scheduleReview)->where('status', 'partial')->count();
    $draftCount       = collect($scheduleReview)->where('status', 'draft')->count();
    $conflictCount    = collect($aiRecommendations)->firstWhere('type', 'critical')
                        ? (int) preg_replace('/\D/', '', collect($aiRecommendations)->firstWhere('type', 'critical')['detail'] ?? '0')
                        : 0;
    $approvedCount    = $globalStats['finalized_schedules'];
    $unscheduledCount = collect($curriculumValidation)->where('issue', 'No schedule assigned')->count();

    $wfCards = [
        [
            'label'  => 'Unassigned',
            'count'  => $unassignedCount,
            'desc'   => 'Minor subjects without faculty',
            'color'  => 'slate',
            'glow'   => $unassignedCount > 0 ? 'glow-orange' : '',
            'dot'    => $unassignedCount > 0 ? 'bg-orange-400 shadow-[0_0_8px_#fb923c]' : 'bg-slate-400',
            'badge'  => $unassignedCount > 0
                ? 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20'
                : 'bg-slate-100 dark:bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
            'border' => $unassignedCount > 0 ? 'border-orange-300 dark:border-orange-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Under Review',
            'count'  => $draftCount,
            'desc'   => 'Awaiting dean/OIC validation',
            'color'  => 'yellow',
            'glow'   => $draftCount > 0 ? 'glow-yellow' : '',
            'dot'    => 'bg-yellow-400 shadow-[0_0_8px_#facc15]',
            'badge'  => 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20',
            'border' => $draftCount > 0 ? 'border-yellow-300 dark:border-yellow-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Partial',
            'count'  => $partialCount,
            'desc'   => 'Auto-generated, needs faculty',
            'color'  => 'blue',
            'glow'   => $partialCount > 0 ? 'glow-blue' : '',
            'dot'    => 'bg-blue-400 shadow-[0_0_8px_#60a5fa]',
            'badge'  => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
            'border' => $partialCount > 0 ? 'border-blue-300 dark:border-blue-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Approved',
            'count'  => $approvedCount,
            'desc'   => 'Finalized minor schedules',
            'color'  => 'emerald',
            'glow'   => $approvedCount > 0 ? 'glow-green' : '',
            'dot'    => 'bg-emerald-400 shadow-[0_0_8px_#34d399]',
            'badge'  => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
            'border' => $approvedCount > 0 ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Conflicts',
            'count'  => $conflictCount,
            'desc'   => 'Active scheduling conflicts',
            'color'  => 'rose',
            'glow'   => $conflictCount > 0 ? 'glow-red' : '',
            'dot'    => 'bg-rose-500 shadow-[0_0_8px_#f43f5e]',
            'badge'  => 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
            'border' => $conflictCount > 0 ? 'border-rose-300 dark:border-rose-500/40' : 'border-slate-200 dark:border-white/10',
        ],
        [
            'label'  => 'Finalized',
            'count'  => $globalStats['total_schedules'],
            'desc'   => 'Locked & published schedules',
            'color'  => 'violet',
            'glow'   => $globalStats['total_schedules'] > 0 ? '' : '',
            'dot'    => 'bg-violet-400 shadow-[0_0_8px_#a78bfa]',
            'badge'  => 'bg-violet-100 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-500/20',
            'border' => 'border-slate-200 dark:border-white/10',
        ],
    ];
@endphp

<div class="px-5 pt-4">
    <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm dark:shadow-none overflow-hidden">
        <div class="flex items-stretch divide-x divide-slate-100 dark:divide-white/[0.06]">
            @foreach($wfCards as $card)
            <div class="flex-1 flex items-center gap-3 px-4 py-4 cursor-default relative
                        {{ ($card['count'] > 0 && in_array($card['color'], ['orange','rose'])) ? 'bg-orange-50/50 dark:bg-orange-500/[0.03]' : '' }}">
                @if($card['count'] > 0 && $card['color'] !== 'slate')
                <span class="absolute top-2 right-2 w-1.5 h-1.5">
                    <span class="stage-ping absolute inset-0 rounded-full {{ $card['dot'] }} opacity-60"></span>
                    <span class="relative block w-1.5 h-1.5 rounded-full {{ $card['dot'] }}"></span>
                </span>
                @endif
                <span class="w-2 h-2 rounded-full {{ $card['dot'] }} flex-shrink-0"></span>
                <div class="min-w-0">
                    <div class="text-[24px] font-bold tabular-nums text-slate-900 dark:text-white leading-none
                                {{ ($card['count'] > 0 && $card['color'] === 'orange') ? 'text-orange-600 dark:text-orange-400' : '' }}
                                {{ ($card['count'] > 0 && $card['color'] === 'rose') ? 'text-rose-600 dark:text-rose-400' : '' }}">
                        {{ number_format($card['count']) }}
                    </div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5 truncate">{{ $card['label'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════
     SCHEDULING WORKFLOW PIPELINE — 5-step Associate Dean flow
════════════════════════════════════════════════════════════════════════ --}}
@php
    $stages = [
        [
            'step'   => '01',
            'title'  => 'Registrar',
            'sub'    => 'Generates partial schedules',
            'count'  => $partialCount + $draftCount,
            'active' => ($partialCount + $draftCount) > 0,
            'done'   => false,
            'palette'=> [
                'ring'  => 'ring-blue-400/60 dark:ring-blue-500/50',
                'bg'    => 'bg-blue-500/10 dark:bg-blue-500/10',
                'label' => 'text-blue-600 dark:text-blue-400',
                'badge' => 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300',
                'glow'  => 'shadow-[0_0_24px_rgba(59,130,246,0.35)]',
                'conn'  => 'from-blue-400 to-orange-400',
            ],
        ],
        [
            'step'   => '02',
            'title'  => 'Assoc. Dean',
            'sub'    => 'Assigns faculty to minor subjects',
            'count'  => count($scheduleReview),
            'active' => count($scheduleReview) > 0,
            'done'   => false,
            'palette'=> [
                'ring'  => 'ring-orange-400/60 dark:ring-orange-500/50',
                'bg'    => 'bg-orange-500/10 dark:bg-orange-500/10',
                'label' => 'text-orange-600 dark:text-orange-400',
                'badge' => 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300',
                'glow'  => 'shadow-[0_0_24px_rgba(249,115,22,0.35)]',
                'conn'  => 'from-orange-400 to-yellow-400',
            ],
        ],
        [
            'step'   => '03',
            'title'  => 'Dean / OIC',
            'sub'    => 'Department review & validation',
            'count'  => $draftCount,
            'active' => $draftCount > 0,
            'done'   => false,
            'palette'=> [
                'ring'  => 'ring-yellow-400/60 dark:ring-yellow-500/50',
                'bg'    => 'bg-yellow-500/10 dark:bg-yellow-500/10',
                'label' => 'text-yellow-600 dark:text-yellow-400',
                'badge' => 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-300',
                'glow'  => 'shadow-[0_0_24px_rgba(234,179,8,0.35)]',
                'conn'  => 'from-yellow-400 to-violet-400',
            ],
        ],
        [
            'step'   => '04',
            'title'  => 'Admin',
            'sub'    => 'Final system sign-off',
            'count'  => $approvedCount,
            'active' => $approvedCount > 0,
            'done'   => false,
            'palette'=> [
                'ring'  => 'ring-violet-400/60 dark:ring-violet-500/50',
                'bg'    => 'bg-violet-500/10 dark:bg-violet-500/10',
                'label' => 'text-violet-600 dark:text-violet-400',
                'badge' => 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300',
                'glow'  => 'shadow-[0_0_24px_rgba(139,92,246,0.35)]',
                'conn'  => 'from-violet-400 to-emerald-400',
            ],
        ],
        [
            'step'   => '05',
            'title'  => 'Finalized',
            'sub'    => 'Schedule locked & published',
            'count'  => $globalStats['finalized_schedules'],
            'active' => $globalStats['finalized_schedules'] > 0,
            'done'   => true,
            'palette'=> [
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
                <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Scheduling Workflow</h3>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">
                    Completion: <span class="text-emerald-500 font-bold">{{ $globalStats['completion_pct'] }}%</span>
                </span>
                <span class="text-[12px] text-slate-500">
                    {{ \App\Models\Setting::semesterLabel($period['semester'] ?? '1st') }} {{ $period['school_year'] ?? '' }}
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
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }} mb-1 text-center">
                    {{ $stage['title'] }}
                </p>
                <p class="text-[10px] text-slate-400 text-center leading-snug px-1 mb-2">{{ $stage['sub'] }}</p>
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
            <div class="w-10 flex items-center justify-center flex-shrink-0 mb-8">
                <div class="w-full h-0.5 {{ $stage['active'] ? 'flow-connector' : '' }}
                            bg-gradient-to-r {{ $stage['palette']['conn'] ?: 'from-slate-200 to-slate-200 dark:from-white/10 dark:to-white/10' }}
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
                    {{ $globalStats['finalized_schedules'] }} / {{ $globalStats['total_schedules'] }} finalized
                </span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                @php $barW = max(0, min(100, $globalStats['completion_pct'])); @endphp
                <div class="h-full rounded-full progress-bar-shine"
                     style="width:{{ $barW }}%; background:linear-gradient(90deg,#fb923c,#f43f5e,#fb923c); background-size:200% auto;">
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════
     MAIN 3-COLUMN GRID
════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 p-5 pt-4">

    {{-- ─── LEFT COLUMN: Faculty Load Overview ──────────────────────── --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

        {{-- Faculty Coordination Panel ─────────────────────────────── --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">

            <div class="flex items-center gap-2 mb-5">
                <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Faculty Load Overview</h3>
                <span class="ml-auto text-[12px] font-bold text-slate-400 tabular-nums">{{ $facultyCoordination['total'] ?? 0 }} total</span>
            </div>

            {{-- Status trio ──────────────────────────────────────────── --}}
            <div class="grid grid-cols-3 gap-2.5 mb-5">
                @php
                    $fcTrio = [
                        ['label' => 'Overloaded', 'value' => $facultyCoordination['overloaded'],  'badge' => 'bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20',   'num' => 'text-rose-600 dark:text-rose-400'],
                        ['label' => 'Normal',     'value' => $facultyCoordination['normal'],      'badge' => 'bg-emerald-100 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20', 'num' => 'text-emerald-600 dark:text-emerald-400'],
                        ['label' => 'Idle',       'value' => $facultyCoordination['underloaded'], 'badge' => 'bg-slate-100 dark:bg-white/[0.04] border-slate-200 dark:border-white/10',   'num' => 'text-slate-500 dark:text-slate-400'],
                    ];
                @endphp
                @foreach($fcTrio as $fc)
                <div class="rounded-xl p-3 border {{ $fc['badge'] }} text-center">
                    <div class="text-[28px] font-bold {{ $fc['num'] }} tabular-nums leading-none mb-1">{{ $fc['value'] }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-500">{{ $fc['label'] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Dept breakdown bars ───────────────────────────────────── --}}
            <div class="mb-5">
                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500 mb-3">Dept. Loading</p>
                <div class="space-y-3.5">
                    @php $maxDept = max(array_values($facultyCoordination['dept_breakdown'] ?? [1])); @endphp
                    @foreach($facultyCoordination['dept_breakdown'] ?? [] as $dept => $count)
                    @php $dpct = $maxDept > 0 ? round(($count / $maxDept) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[12px] text-slate-700 dark:text-slate-300 font-semibold">{{ $dept }}</span>
                            <span class="text-[12px] text-slate-500 tabular-nums">{{ $count }}</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                            <div class="h-full rounded-full progress-bar-shine transition-all duration-700"
                                 style="width:{{ $dpct }}%; background:linear-gradient(90deg,#fb923c,#f59e0b,#fb923c); background-size:200% auto;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Loaded Faculty scrollable list ──────────────────── --}}
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500 mb-3 sticky top-0">Top Loading</p>
                <div class="panel-scroll overflow-y-auto max-h-48 space-y-0">
                    @forelse($facultyCoordination['top_loaded'] ?? [] as $idx => $f)
                    @php
                        $fBadge = match($f['status']) {
                            'overloaded' => 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
                            'unassigned' => 'bg-slate-100 dark:bg-white/[0.04] text-slate-500 border-slate-200 dark:border-white/10',
                            default      => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                        };
                        $fNum = match($f['status']) {
                            'overloaded' => 'text-rose-600 dark:text-rose-400',
                            'unassigned' => 'text-slate-400',
                            default      => 'text-emerald-600 dark:text-emerald-400',
                        };
                    @endphp
                    <div class="fade-row flex items-center justify-between py-2.5
                                border-b border-slate-100 dark:border-white/[0.04] last:border-0
                                hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors -mx-1 px-1 rounded-lg cursor-default"
                         style="animation-delay: {{ $idx * 40 }}ms">
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $f['name'] }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $f['department'] ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-[13px] font-bold {{ $fNum }} tabular-nums">{{ $f['load'] }}/{{ $f['max'] }}</span>
                            <span class="px-1.5 py-0.5 rounded border text-[10px] font-bold uppercase {{ $fBadge }}">{{ $f['status'] }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                        <p class="text-[13px]">No faculty data available</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- System Insights (AI Recommendations) ───────────────────── --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex-1">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-amber-400 rounded-full shadow-[0_0_8px_#fbbf24]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">System Insights</h3>
            </div>
            <div class="space-y-3">
                @foreach($aiRecommendations as $rec)
                @php
                    $rScheme = [
                        'critical' => 'bg-rose-50 dark:bg-rose-500/[0.04] border-rose-200 dark:border-rose-500/15 text-rose-700 dark:text-rose-300',
                        'warning'  => 'bg-yellow-50 dark:bg-yellow-500/[0.04] border-yellow-300 dark:border-yellow-500/15 text-yellow-700 dark:text-yellow-300',
                        'info'     => 'bg-blue-50 dark:bg-blue-500/[0.04] border-blue-300 dark:border-blue-500/15 text-blue-700 dark:text-blue-300',
                        'success'  => 'bg-emerald-50 dark:bg-emerald-500/[0.04] border-emerald-300 dark:border-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                    ];
                    $rc = $rScheme[$rec['type']] ?? $rScheme['info'];
                @endphp
                <div class="p-3 rounded-xl border {{ $rc }}">
                    <div class="flex items-start gap-2.5">
                        <span class="text-base flex-shrink-0 mt-0.5">{{ $rec['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-bold leading-snug">{{ $rec['title'] }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $rec['detail'] }}</p>
                            @if($rec['action'])
                            <button class="mt-1.5 text-[10px] uppercase tracking-widest font-bold opacity-60 hover:opacity-100 transition-opacity">
                                {{ $rec['action'] }} →
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ─── CENTER COLUMN: Minor Subject Review Queue ────────────────── --}}
    <div class="col-span-12 lg:col-span-5">
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none h-full flex flex-col">

            {{-- Panel header ─────────────────────────────────────────── --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Minor Subject Review Queue</h3>
                    @if(count($scheduleReview) > 0)
                    <span class="relative">
                        <span class="stage-ping absolute inset-0 rounded-full bg-orange-400 opacity-60"></span>
                        <span class="relative px-1.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 text-[11px] font-bold border border-orange-200 dark:border-orange-500/20">
                            {{ count($scheduleReview) }}
                        </span>
                    </span>
                    @endif
                </div>
                <span class="text-[11px] text-slate-400 uppercase tracking-widest">Draft &amp; Partial</span>
            </div>

            {{-- Sticky-header scrollable table ──────────────────────── --}}
            <div class="flex-1 panel-scroll overflow-y-auto max-h-[580px] pr-0.5">
                <table class="w-full text-[13px]">
                    <thead class="sticky top-0 z-10 bg-white dark:bg-[#080d1a]">
                        <tr class="border-b border-slate-100 dark:border-white/[0.05] text-[11px] uppercase tracking-widest text-slate-500">
                            <th class="text-left py-3 pr-3 font-semibold">Subject</th>
                            <th class="text-left py-3 pr-3 font-semibold">Faculty</th>
                            <th class="text-left py-3 pr-3 font-semibold">Room</th>
                            <th class="text-left py-3 pr-3 font-semibold">Schedule</th>
                            <th class="text-left py-3 pr-3 font-semibold">Status</th>
                            <th class="text-left py-3 font-semibold">Flag</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/[0.02]">
                        @forelse($scheduleReview as $i => $s)
                        @php
                            $sBadge = match($s['status']) {
                                'draft'   => 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20',
                                'partial' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
                                default   => 'bg-slate-100 dark:bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
                            };
                            $fFlag = $s['flag'] === 'No Faculty'
                                ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20'
                                : 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20';
                        @endphp
                        <tr class="queue-row fade-row hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors cursor-default"
                            style="animation-delay: {{ $i * 35 }}ms">
                            <td class="py-3.5 pr-3">
                                <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ $s['subject'] }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $s['department'] }}</p>
                            </td>
                            <td class="py-3.5 pr-3">
                                @if($s['faculty'] === 'Unassigned')
                                <span class="flex items-center gap-1.5 text-[12px] font-semibold text-rose-600 dark:text-rose-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse flex-shrink-0"></span>
                                    Unassigned
                                </span>
                                @else
                                <span class="text-[12px] text-slate-600 dark:text-slate-400 truncate max-w-[100px] block">{{ $s['faculty'] }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 pr-3 text-[12px] text-slate-500">{{ $s['room'] }}</td>
                            <td class="py-3.5 pr-3">
                                <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300">{{ $s['day'] }}</p>
                                <p class="text-[11px] text-slate-400">{{ $s['time'] }}</p>
                            </td>
                            <td class="py-3.5 pr-3">
                                <span class="px-2 py-0.5 rounded border text-[10px] font-bold uppercase {{ $sBadge }}">{{ $s['status'] }}</span>
                            </td>
                            <td class="py-3.5">
                                <span class="px-2 py-0.5 rounded border text-[10px] font-bold {{ $fFlag }}">{{ $s['flag'] }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-14 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10 opacity-25 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-[13px] font-medium">No schedules pending review</p>
                                    <p class="text-[11px]">All caught up — the queue is clear</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Curriculum issues footer strip ──────────────────────── --}}
            @if(count($curriculumValidation) > 0)
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e] animate-pulse"></span>
                        <h4 class="text-[12px] font-semibold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400">
                            Curriculum Issues
                        </h4>
                    </div>
                    <span class="px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 text-[11px] font-bold">
                        {{ count($curriculumValidation) }}
                    </span>
                </div>
                <div class="dash-scroll overflow-y-auto max-h-32 space-y-2">
                    @foreach(array_slice($curriculumValidation, 0, 5) as $issue)
                    <div class="flex items-center justify-between gap-3 py-1.5
                                border-b border-slate-50 dark:border-white/[0.03] last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $issue['subject_code'] ?? '—' }}</p>
                            <p class="text-[11px] text-slate-400">{{ $issue['issue'] }}</p>
                        </div>
                        <span class="flex-shrink-0 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded
                                     bg-slate-100 dark:bg-white/[0.05] text-slate-500 border border-slate-200 dark:border-white/10">
                            {{ $issue['type'] ?? '—' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ─── RIGHT COLUMN: Subject Overview + Dept Breakdown ────────────── --}}
    <div class="col-span-12 lg:col-span-3 flex flex-col gap-4">

        {{-- Subject Distribution (simplified) ──────────────────────────── --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-amber-400 rounded-full"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Subject Overview</h3>
            </div>

            @php
                $totalSub = ($subjectDistribution['total_major'] ?? 0) + ($subjectDistribution['total_minor'] ?? 0);
                $majPct   = $totalSub > 0 ? round(($subjectDistribution['total_major'] / $totalSub) * 100) : 0;
                $minPct   = $totalSub > 0 ? 100 - $majPct : 0;
            @endphp

            {{-- Major / Minor tiles ───────────────────────────────────── --}}
            <div class="grid grid-cols-2 gap-2.5 mb-4">
                <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-3 text-center">
                    <div class="text-[26px] font-bold text-rose-600 dark:text-rose-400 tabular-nums leading-none mb-1">
                        {{ number_format($subjectDistribution['total_major'] ?? 0) }}
                    </div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-widest">Major</div>
                    <div class="text-[11px] font-bold text-rose-500 mt-0.5">{{ $majPct }}%</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-3 text-center">
                    <div class="text-[26px] font-bold text-orange-600 dark:text-orange-400 tabular-nums leading-none mb-1">
                        {{ number_format($subjectDistribution['total_minor'] ?? 0) }}
                    </div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-widest">Minor</div>
                    <div class="text-[11px] font-bold text-orange-500 mt-0.5">{{ $minPct }}%</div>
                </div>
            </div>

            {{-- Stacked ratio bar ────────────────────────────────────── --}}
            <div class="flex h-2 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-rose-500 transition-all duration-700" style="width:{{ $majPct }}%"></div>
                <div class="h-full bg-orange-400 transition-all duration-700" style="width:{{ $minPct }}%"></div>
            </div>

            {{-- Completion strip ─────────────────────────────────────── --}}
            <div class="pt-3 border-t border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] text-slate-500 uppercase tracking-widest">Overall Completion</span>
                    <span class="text-[12px] font-bold text-emerald-500 tabular-nums">{{ $globalStats['completion_pct'] }}%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                    <div class="h-full rounded-full progress-bar-shine"
                         style="width:{{ $globalStats['completion_pct'] }}%; background:linear-gradient(90deg,#34d399,#60a5fa,#34d399); background-size:200% auto;"></div>
                </div>
                <div class="flex justify-between mt-1.5 text-[10px] text-slate-400">
                    <span>{{ $globalStats['finalized_schedules'] }} finalized</span>
                    <span>{{ $globalStats['total_schedules'] }} total</span>
                </div>
            </div>
        </div>

        {{-- Per-Dept Breakdown ──────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex-1">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">By Department</h3>
            </div>
            <div class="space-y-4">
                @foreach($subjectDistribution['by_department'] ?? [] as $dept => $data)
                @php
                    $dt   = max(1, $data['total']);
                    $dMaj = round(($data['major'] / $dt) * 100);
                    $dMin = 100 - $dMaj;
                @endphp
                <div>
                    <div class="flex justify-between mb-1.5">
                        <span class="text-[12px] font-semibold text-slate-700 dark:text-slate-300">{{ $dept }}</span>
                        <span class="text-[11px] text-slate-500 tabular-nums">
                            <span class="text-rose-500">{{ $data['major'] }}M</span> / <span class="text-orange-400">{{ $data['minor'] }}m</span>
                        </span>
                    </div>
                    <div class="flex h-1.5 rounded-full overflow-hidden">
                        <div class="h-full bg-rose-500 transition-all duration-700 rounded-l-full" style="width:{{ $dMaj }}%"></div>
                        <div class="h-full bg-orange-400 transition-all duration-700 rounded-r-full" style="width:{{ $dMin }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Legend ───────────────────────────────────────────────── --}}
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/[0.05] flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span class="text-[11px] text-slate-500">Major</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span>
                    <span class="text-[11px] text-slate-500">Minor</span>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /main grid --}}
</div>{{-- /root wrapper --}}