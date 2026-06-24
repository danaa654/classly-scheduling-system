{{-- resources/views/livewire/dean-dashboard.blade.php --}}

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

    /* ── Status card glow pulses ────────────────────── */
    @keyframes glowPulseRed    { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}   50%{box-shadow:0 0 16px 4px rgba(239,68,68,0.3);}   }
    @keyframes glowPulseGreen  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}  50%{box-shadow:0 0 16px 4px rgba(16,185,129,0.3);}  }
    @keyframes glowPulseYellow { 0%,100%{box-shadow:0 0 0 0 rgba(250,204,21,0);}  50%{box-shadow:0 0 16px 4px rgba(250,204,21,0.3);}  }
    @keyframes glowPulseBlue   { 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,0);}  50%{box-shadow:0 0 16px 4px rgba(59,130,246,0.3);}  }
    @keyframes glowPulseOrange { 0%,100%{box-shadow:0 0 0 0 rgba(251,146,60,0);}  50%{box-shadow:0 0 16px 4px rgba(251,146,60,0.3);}  }
    @keyframes stagePing { 75%,100% { transform:scale(1.6); opacity:0; } }
    @keyframes flowPulse { 0%,100%{opacity:.45;} 50%{opacity:1;} }
    .glow-green  { animation: glowPulseGreen  3s ease-in-out infinite; }
    .glow-red    { animation: glowPulseRed    3s ease-in-out infinite; }
    .glow-yellow { animation: glowPulseYellow 3s ease-in-out infinite; }
    .glow-blue   { animation: glowPulseBlue   3s ease-in-out infinite; }
    .glow-orange { animation: glowPulseOrange 3s ease-in-out infinite; }
    .stage-ping { animation: stagePing 1.5s cubic-bezier(0,0,0.2,1) infinite; }
    .flow-connector { animation: flowPulse 2.5s ease-in-out infinite; }

    /* ── Hover lift ─────────────────────────────────── */
    .card-lift { transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .card-lift:hover { transform: translateY(-2px); }

    /* ── Fade-in stagger ────────────────────────────── */
    @keyframes fadeSlideIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
    .fade-row { animation: fadeSlideIn 0.35s ease both; }

    /* ── Conflict ping ──────────────────────────────── */
    @keyframes pulse-slow { 0%,100%{opacity:1;} 50%{opacity:0.7;} }
    .animate-pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }

    /* ── Progress shimmer ───────────────────────────── */
    @keyframes shimmer { 0%{background-position:-200% center;} 100%{background-position:200% center;} }
    .progress-bar-shine { background-size:200% auto; animation: shimmer 2.5s linear infinite; }

    .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
</style>

<div
    wire:poll.10s="refreshSystemReadiness"
    x-data="{
        clock: '{{ date('g:i:s A') }}',
        init() {
            setInterval(() => {
                const now = new Date();
                let h = now.getHours();
                const ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                const m = String(now.getMinutes()).padStart(2,'0');
                const s = String(now.getSeconds()).padStart(2,'0');
                this.clock = h + ':' + m + ':' + s + ' ' + ampm;
            }, 1000);
        }
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
@php
    $pendingCount   = count($approvalQueue);
    $conflictCount  = count($escalatedConflicts);
    $periodLabel    = ($currentPeriod['semester_name'] ?? null)
        ?: \App\Models\Setting::semesterLabel($currentPeriod['semester'] ?? '1st') . ' ' . ($currentPeriod['school_year'] ?? '');
@endphp

@if($pendingCount > 0 || $conflictCount > 0)
<div class="flex flex-wrap gap-2.5 px-5 pt-4">
    @if($conflictCount > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-rose-50 dark:bg-rose-500/[0.06]
                border border-rose-200 dark:border-rose-500/20
                shadow-[0_0_10px_rgba(239,68,68,0.1)] animate-pulse-slow">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_6px_#f43f5e]"></span>
        <span class="text-[12px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-[0.12em]">
            {{ $conflictCount }} Escalated Conflict{{ $conflictCount !== 1 ? 's' : '' }}
        </span>
    </div>
    @endif
    @if($pendingCount > 0)
    <div class="flex items-center gap-2 px-3.5 py-2 rounded-lg
                bg-amber-50 dark:bg-amber-500/[0.06]
                border border-amber-200 dark:border-amber-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
        <span class="text-[12px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-[0.12em]">
            {{ $pendingCount }} Pending Approval{{ $pendingCount !== 1 ? 's' : '' }}
        </span>
    </div>
    @endif
</div>
@endif


{{-- ═══════════════════════════════════════════════════
     HEADER — Identity + Live Clock + KPI Vitals
═══════════════════════════════════════════════════════ --}}
<div class="px-5 pt-4">
    @if(! $systemReady)
    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm dark:border-amber-500/25 dark:bg-amber-500/[0.06]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">New Semester Setup</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">The registrar is configuring {{ $periodLabel }}.</h2>
                <p class="mt-1 max-w-3xl text-[13px] leading-6 text-slate-600 dark:text-slate-300">
                    You can get a head start with faculty loading and preferred room assignments. MasterGrid room view will open as soon as the setup is finalized.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('faculty-loading') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-white px-3.5 py-2 text-[12px] font-bold text-violet-700 shadow-sm transition hover:bg-violet-50 dark:border-violet-500/25 dark:bg-white/[0.04] dark:text-violet-300">
                    Faculty Loading
                </a>
                <a href="{{ route('manage.rooms') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-[12px] font-bold text-blue-700 shadow-sm transition hover:bg-blue-50 dark:border-blue-500/25 dark:bg-white/[0.04] dark:text-blue-300">
                    Manage Rooms
                </a>
                <button type="button" title="Available once system configuration is finalized" class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-[12px] font-bold text-slate-400 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-500">
                    MasterGrid Locked
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 px-5 py-4 shadow-sm dark:border-emerald-500/25 dark:bg-emerald-500/[0.06]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[13px] text-slate-600 dark:text-slate-300">
                <span class="font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Semester Ready</span>
                <span class="ml-2">{{ $periodLabel }} configuration is finalized.</span>
            </p>
            <a href="{{ route('master-grid') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-3.5 py-2 text-[12px] font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-500/25 dark:bg-white/[0.04] dark:text-emerald-300">
                Open MasterGrid
            </a>
        </div>
    </div>
    @endif
</div>

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
                    {{ $department }} — Academic Oversight
                </span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                CLASSLY <span class="text-blue-500 dark:text-blue-400">{{ Auth::user()->role === 'oic' ? 'OIC' : 'Dean' }}</span>
            </h1>
            <p class="text-[12px] text-slate-500 mt-1">
                {{ date('l, F d, Y') }}
                &nbsp;·&nbsp;
                <span class="text-slate-600 dark:text-slate-400 font-medium">{{ Auth::user()->name }}</span>
            </p>
        </div>
        <div class="hidden md:flex flex-col items-end gap-1">
            <span x-text="clock" class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white tracking-widest"></span>
            <span class="text-[10px] text-slate-500 uppercase tracking-widest">Server Time (PHT)</span>
            @php $covRate = $academicOverview['completion_rate'] ?? 0; @endphp
            <span class="text-[11px] font-semibold text-emerald-500 mt-0.5">{{ $covRate }}% Coverage</span>
        </div>
    </div>

    {{-- KPI Vitals --}}
    @php
        $vitals = [
            [
                'label'  => 'Faculty',
                'value'  => $academicOverview['total_faculty'],
                'color'  => 'violet',
                'icon'   => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            ],
            [
                'label'  => 'Subjects',
                'value'  => $academicOverview['total_subjects'],
                'color'  => 'blue',
                'icon'   => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
            ],
            [
                'label'  => 'Scheduled',
                'value'  => $academicOverview['scheduled_subjects'],
                'color'  => 'emerald',
                'icon'   => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            ],
            [
                'label'  => 'Approvals',
                'value'  => count($approvalQueue),
                'color'  => count($approvalQueue) > 0 ? 'amber' : 'emerald',
                'icon'   => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            ],
        ];
        $vColors = [
            'blue'    => 'border-blue-200   dark:border-blue-500/30   text-blue-600   dark:text-blue-400   bg-blue-50   dark:bg-blue-500/5',
            'violet'  => 'border-violet-200 dark:border-violet-500/30 text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/5',
            'emerald' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/5',
            'amber'   => 'border-amber-200  dark:border-amber-500/30  text-amber-600  dark:text-amber-400  bg-amber-50  dark:bg-amber-500/5',
        ];
    @endphp

    <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
        @foreach($vitals as $v)
        <div class="card-lift border {{ $vColors[$v['color']] }} rounded-2xl p-3.5 flex flex-col gap-2 cursor-default">
            <span class="{{ $vColors[$v['color']] }} w-7 h-7 rounded-lg flex items-center justify-center">{!! $v['icon'] !!}</span>
            <span class="text-[28px] font-bold text-slate-900 dark:text-white tabular-nums leading-none">{{ number_format((int)$v['value']) }}</span>
            <span class="text-[11px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
--}}
@php
    $wf = $workflowCounts ?? [];
    $deptScheduling = $schedulingStats ?? [];
    $completionRate = $deptScheduling['completion_pct'] ?? 0;
    $periodText = ($currentPeriod['semester_name'] ?? null)
        ?: \App\Models\Setting::semesterLabel($currentPeriod['semester'] ?? '1st') . ' ' . ($currentPeriod['school_year'] ?? '');

    $workflowStages = [
        ['step'=>'01','title'=>'System Config','sub'=>'Admin prepares term setup','count'=>$deptScheduling['total_subjects'] ?? 0,'active'=>$systemReady,'done'=>$systemReady,'palette'=>['ring'=>'ring-slate-400/60 dark:ring-slate-500/50','bg'=>'bg-slate-500/10 dark:bg-slate-500/10','label'=>'text-slate-600 dark:text-slate-300','badge'=>'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300','glow'=>'shadow-[0_0_24px_rgba(100,116,139,0.25)]','conn'=>'from-slate-400 to-teal-400']],
        ['step'=>'02','title'=>'Preparation','sub'=>'Dean / OIC pre-assigns faculty','count'=>$wf['faculty_assigned'] ?? 0,'active'=>($wf['faculty_assigned'] ?? 0) > 0,'done'=>false,'palette'=>['ring'=>'ring-teal-400/60 dark:ring-teal-500/50','bg'=>'bg-teal-500/10 dark:bg-teal-500/10','label'=>'text-teal-600 dark:text-teal-400','badge'=>'bg-teal-100 dark:bg-teal-500/20 text-teal-700 dark:text-teal-300','glow'=>'shadow-[0_0_24px_rgba(20,184,166,0.35)]','conn'=>'from-teal-400 to-blue-400']],
        ['step'=>'03','title'=>'Auto-generate','sub'=>'Admin / Registrar can run anytime','count'=>($wf['draft'] ?? 0) + ($wf['partial'] ?? 0),'active'=>(($wf['draft'] ?? 0) + ($wf['partial'] ?? 0)) > 0,'done'=>false,'palette'=>['ring'=>'ring-blue-400/60 dark:ring-blue-500/50','bg'=>'bg-blue-500/10 dark:bg-blue-500/10','label'=>'text-blue-600 dark:text-blue-400','badge'=>'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300','glow'=>'shadow-[0_0_24px_rgba(59,130,246,0.35)]','conn'=>'from-blue-400 to-amber-400']],
        ['step'=>'04','title'=>'Dept Review','sub'=>'Review generated schedules','count'=>$wf['partial'] ?? 0,'active'=>($wf['partial'] ?? 0) > 0,'done'=>false,'palette'=>['ring'=>'ring-amber-400/60 dark:ring-amber-500/50','bg'=>'bg-amber-500/10 dark:bg-amber-500/10','label'=>'text-amber-600 dark:text-amber-400','badge'=>'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300','glow'=>'shadow-[0_0_24px_rgba(245,158,11,0.35)]','conn'=>'from-amber-400 to-emerald-400']],
        ['step'=>'05','title'=>'Finalize','sub'=>'Admin / Registrar locks schedule','count'=>$wf['finalized'] ?? 0,'active'=>($wf['finalized'] ?? 0) > 0,'done'=>true,'palette'=>['ring'=>'ring-emerald-400/60 dark:ring-emerald-500/50','bg'=>'bg-emerald-500/10 dark:bg-emerald-500/10','label'=>'text-emerald-600 dark:text-emerald-400','badge'=>'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300','glow'=>'shadow-[0_0_24px_rgba(16,185,129,0.35)]','conn'=>'']],
    ];
@endphp

<div class="px-5 pt-4">
    <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-4 shadow-sm dark:shadow-none">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Scheduling Workflow</h3>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">Completion: <span class="text-emerald-500 font-bold">{{ $completionRate }}%</span></span>
                <span class="text-[12px] text-slate-500">{{ $periodText }}</span>
            </div>
        </div>

        <div class="flex items-center gap-0 overflow-x-auto pb-1">
            @foreach($workflowStages as $stage)
            <div class="min-w-[150px] flex-1 flex flex-col items-center relative {{ $stage['active'] ? $stage['palette']['glow'].' rounded-xl' : '' }} transition-all duration-500">
                <div class="relative mb-2">
                    @if($stage['active'])
                    <span class="stage-ping absolute inset-0 rounded-full ring-1 {{ $stage['palette']['ring'] }}"></span>
                    @endif
                    <div class="relative w-9 h-9 rounded-full flex items-center justify-center ring-1 {{ $stage['active'] ? $stage['palette']['ring'] : 'ring-slate-200 dark:ring-white/10' }} {{ $stage['active'] ? $stage['palette']['bg'] : 'bg-slate-100 dark:bg-white/[0.03]' }}">
                        @if($stage['done'] && $stage['count'] > 0)
                        <svg class="w-4 h-4 {{ $stage['palette']['label'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <span class="text-xs font-bold {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }}">{{ $stage['step'] }}</span>
                        @endif
                    </div>
                </div>
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $stage['active'] ? $stage['palette']['label'] : 'text-slate-400 dark:text-slate-600' }} mb-1 text-center">{{ $stage['title'] }}</p>
                <p class="text-[10px] text-slate-400 text-center leading-snug px-2 mb-2">{{ $stage['sub'] }}</p>
                @if($stage['count'] > 0)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $stage['palette']['badge'] }}">{{ number_format($stage['count']) }}</span>
                @else
                <span class="text-[10px] text-slate-400 dark:text-slate-600">-</span>
                @endif
            </div>
            @if(!$loop->last)
            <div class="w-10 lg:w-12 flex items-center justify-center flex-shrink-0 mb-8">
                <div class="w-full h-0.5 {{ $stage['active'] ? 'flow-connector' : '' }} bg-gradient-to-r {{ $stage['palette']['conn'] ?: 'from-slate-200 to-slate-200 dark:from-white/10 dark:to-white/10' }} {{ !$stage['active'] ? 'opacity-20' : '' }} rounded-full"></div>
            </div>
            @endif
            @endforeach
        </div>

        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-[12px] font-medium leading-snug text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
            Step 02 is optional and improves generation quality. Auto-generation is still available to Admin and Registrar even if preparation is skipped.
        </div>

        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/[0.05]">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[12px] text-slate-500 uppercase tracking-widest">Overall Progress</span>
                <span class="text-[13px] font-bold text-emerald-500">{{ $deptScheduling['finalized_subjects'] ?? 0 }} / {{ $deptScheduling['total_subjects'] ?? 0 }} finalized</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                <div class="h-full rounded-full progress-bar-shine" style="width:{{ max(0, min(100, $completionRate)) }}%; background:linear-gradient(90deg,#34d399,#60a5fa,#34d399); background-size:200% auto;"></div>
            </div>
        </div>
    </div>
</div>

{{-- MAIN 2-COLUMN GRID
═══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-12 gap-4 p-5 pt-4 pb-6">

    {{-- ─── LEFT COL: Escalated + Approval (top), Subject Distribution + Activity (bottom) ── --}}
    <div class="col-span-12 lg:col-span-7 flex flex-col gap-4">

        {{-- ── Top Row: Escalated Conflicts | Approval Queue ── --}}
        <div class="grid grid-cols-2 gap-4">

        {{-- Escalated Conflicts --}}
        <div class="bg-white dark:bg-white/[0.03]
                    border rounded-2xl p-5 shadow-sm dark:shadow-none
                    transition-all duration-500 flex flex-col
                    {{ $conflictCount > 0
                        ? 'border-rose-300 dark:border-rose-500/40 shadow-[0_0_30px_rgba(239,68,68,0.08)]'
                        : 'border-emerald-200 dark:border-emerald-500/25 shadow-[0_0_20px_rgba(16,185,129,0.06)]' }}">

            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    @if($conflictCount > 0)
                    <span class="w-1 h-4 bg-rose-400 rounded-full shadow-[0_0_8px_#f87171] animate-pulse"></span>
                    @else
                    <span class="w-1 h-4 bg-emerald-400 rounded-full shadow-[0_0_8px_#34d399]"></span>
                    @endif
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Escalated Conflicts</h3>
                </div>
                <span class="text-[12px] font-bold px-2.5 py-0.5 rounded-full
                    {{ $conflictCount > 0
                        ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20'
                        : 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' }}">
                    {{ $conflictCount }} {{ $conflictCount === 1 ? 'conflict' : 'conflicts' }}
                </span>
            </div>

            @if($conflictCount === 0)
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
            <div class="panel-scroll overflow-y-auto max-h-[240px] pr-1 space-y-3">
                @foreach($escalatedConflicts as $conflict)
                <div class="flex items-center gap-4 p-4 rounded-xl
                            bg-rose-50 dark:bg-rose-500/[0.05]
                            border border-rose-200 dark:border-rose-500/20
                            hover:border-rose-300 dark:hover:border-rose-500/40
                            transition-colors fade-row">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-500/10
                                border border-rose-200 dark:border-rose-500/20
                                flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-rose-700 dark:text-rose-300">{{ $conflict['room'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $conflict['day'] }} &nbsp;·&nbsp; {{ $conflict['time'] }}</p>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold
                                 bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400
                                 border border-rose-200 dark:border-rose-500/20">
                        CONFLICT
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>{{-- end Escalated Conflicts --}}

            {{-- Approval Queue --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border rounded-2xl p-4 shadow-sm dark:shadow-none flex flex-col
                        {{ $pendingCount > 0
                            ? 'border-amber-200 dark:border-amber-500/30'
                            : 'border-slate-200 dark:border-white/10' }}">

                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full
                                     {{ $pendingCount > 0 ? 'bg-amber-400 shadow-[0_0_8px_#fbbf24] animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                        <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval Queue</h3>
                    </div>
                    @if($pendingCount > 0)
                    <span class="text-[12px] font-bold px-2 py-0.5 rounded-full tabular-nums
                                 bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                                 border border-amber-200 dark:border-amber-500/20">
                        {{ $pendingCount }}
                    </span>
                    @endif
                </div>

                @if($pendingCount === 0)
                <div class="flex flex-col items-center justify-center py-6 text-center flex-1">
                    <div class="w-10 h-10 rounded-full
                                bg-emerald-50 dark:bg-emerald-500/[0.07]
                                border border-emerald-200 dark:border-emerald-500/20
                                flex items-center justify-center mb-2.5">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-[13px] font-semibold text-emerald-600 dark:text-emerald-400">All Caught Up</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">No pending approvals.</p>
                </div>
                @else
                <div class="panel-scroll overflow-y-auto max-h-[280px] pr-1 space-y-2.5">
                    @foreach($approvalQueue as $item)
                    <div class="flex flex-col gap-2.5 p-3 rounded-xl
                                bg-amber-50/60 dark:bg-amber-500/[0.04]
                                border border-amber-100 dark:border-amber-500/15
                                hover:border-amber-300 dark:hover:border-amber-500/30
                                transition-colors group fade-row">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-500/10
                                        border border-amber-200 dark:border-amber-500/20
                                        flex items-center justify-center flex-shrink-0">
                                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                    {{ strtoupper(substr($item['type'], 0, 2)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300 leading-snug line-clamp-2">{{ $item['description'] }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $item['submitted_by'] }} · {{ $item['time'] }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <button wire:click="approveItem({{ $item['id'] }}, '{{ $item['module'] }}')"
                                    class="flex-1 py-1 rounded-lg text-[11px] font-bold uppercase
                                           bg-emerald-100 dark:bg-emerald-500/10
                                           border border-emerald-200 dark:border-emerald-500/20
                                           text-emerald-700 dark:text-emerald-400
                                           hover:bg-emerald-200 dark:hover:bg-emerald-500/20 transition-colors">
                                Approve
                            </button>
                            <button wire:click="rejectItem({{ $item['id'] }}, '{{ $item['module'] }}')"
                                    class="flex-1 py-1 rounded-lg text-[11px] font-bold uppercase
                                           bg-rose-100 dark:bg-rose-500/10
                                           border border-rose-200 dark:border-rose-500/20
                                           text-rose-700 dark:text-rose-400
                                           hover:bg-rose-200 dark:hover:bg-rose-500/20 transition-colors">
                                Reject
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>{{-- end Approval Queue --}}

        </div>{{-- end top row grid --}}

        {{-- ── Bottom Row: Subject Distribution | Activity ── --}}
        <div class="grid grid-cols-2 gap-4 items-start">

            {{-- Request Tracking / Activity --}}
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

                <div class="space-y-2.5">
                    @forelse($requestTracking as $req)
                    @php
                        $dotColor = match($req['status']) {
                            'approved' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20',
                            'rejected' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20',
                            default    => 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20',
                        };
                    @endphp
                    <div class="flex gap-2 group fade-row">
                        <div class="flex flex-col items-center gap-1 flex-shrink-0">
                            <div class="w-6 h-6 rounded-lg {{ $dotColor }} border flex items-center justify-center text-[10px] font-bold">
                                {{ strtoupper(substr($req['module'], 0, 2)) }}
                            </div>
                            <div class="flex-1 w-px bg-slate-100 dark:bg-white/[0.04]"></div>
                        </div>
                        <div class="pb-3 min-w-0 flex-1">
                            <p class="text-[12px] text-slate-700 dark:text-slate-300 leading-snug line-clamp-2">{{ $req['description'] }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $req['user'] }} · {{ $req['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                        <p class="text-[13px]">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>{{-- end Activity --}}

            {{-- Subject Distribution --}}
            <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                        <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Subject Distribution</h3>
                    </div>
                    <span class="text-[12px] text-slate-400">
                        {{ $academicOverview['total_subjects'] }} subjects
                    </span>
                </div>

                @php
                    $majorCount = $academicOverview['major_subjects'] ?? 0;
                    $minorCount = $academicOverview['minor_subjects'] ?? 0;
                    $dTotal     = $majorCount + $minorCount;
                    $dMajPct    = $dTotal > 0 ? round(($majorCount / $dTotal) * 100) : 0;
                    $dMinPct    = $dTotal > 0 ? round(($minorCount / $dTotal) * 100) : 0;
                    $schedPct   = $academicOverview['completion_rate'] ?? 0;
                    $unschedPct = max(0, 100 - $schedPct);
                @endphp

                {{-- Donut --}}
                <div class="flex justify-center mb-4">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="#f43f5e" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $dMajPct }}, 100"/>
                            <circle stroke-width="3" stroke="#fb923c" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $dMinPct }}, 100"
                                stroke-dashoffset="{{ -$dMajPct }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($dTotal) }}</span>
                            <span class="text-[8px] text-slate-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>

                {{-- Major / Minor counters --}}
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($majorCount) }}</div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-wider">Major</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-xl font-bold text-orange-500 dark:text-orange-400 tabular-nums">{{ number_format($minorCount) }}</div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-wider">Minor</div>
                    </div>
                </div>

                {{-- Per-year coverage bars --}}
                <div class="panel-scroll overflow-y-auto max-h-[140px] pr-1 space-y-3">
                    @foreach($curriculumCoverage as $yr)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[12px] font-semibold text-slate-600 dark:text-slate-400">Year {{ $yr['year'] }}</span>
                            <span class="text-[11px] text-slate-400 tabular-nums">{{ $yr['scheduled'] }}/{{ $yr['total'] }} · {{ $yr['pct'] }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700
                                        {{ $yr['pct'] >= 100 ? 'bg-emerald-500' : ($yr['pct'] >= 50 ? 'bg-blue-500' : 'bg-amber-500') }}"
                                 style="width:{{ $yr['pct'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>{{-- end Subject Distribution --}}

        </div>{{-- end bottom row grid --}}

    </div>{{-- end LEFT COL --}}


    {{-- ─── RIGHT COL: Faculty Loading (full height) ── --}}
    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

        {{-- Faculty Loading --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col flex-1">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Faculty Load</h3>
                </div>
                <span class="text-[12px] text-slate-400">{{ $academicOverview['total_faculty'] }} total</span>
            </div>

            {{-- Faculty Assignment mini-stats --}}
            @php
                $assigned   = $academicOverview['assigned_faculty'];
                $unassigned = $academicOverview['unassigned_faculty'];
                $total      = $academicOverview['total_faculty'];
                $assignPct  = $total > 0 ? round(($assigned / $total) * 100) : 0;
            @endphp
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-2.5 text-center border border-slate-200 dark:border-white/[0.05]">
                    <div class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">{{ $total }}</div>
                    <div class="text-[9px] text-slate-500 uppercase tracking-wider">Total</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-500/[0.05] rounded-xl p-2.5 text-center border border-emerald-200 dark:border-emerald-500/15">
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $assigned }}</div>
                    <div class="text-[9px] text-slate-500 uppercase tracking-wider">Assigned</div>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/[0.05] rounded-xl p-2.5 text-center border border-amber-200 dark:border-amber-500/15">
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400 tabular-nums">{{ $unassigned }}</div>
                    <div class="text-[9px] text-slate-500 uppercase tracking-wider">Idle</div>
                </div>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden mb-4">
                <div class="h-full rounded-full progress-bar-shine"
                     style="width:{{ $assignPct }}%; background:linear-gradient(90deg,#34d399,#60a5fa,#34d399); background-size:200% auto;"></div>
            </div>

            {{-- Faculty list --}}
            <div class="panel-scroll overflow-y-auto max-h-[200px] pr-1 space-y-3">
                @forelse($facultySummary as $f)
                @php
                    $pct      = $f['max_units'] > 0 ? min(100, round(($f['load'] / $f['max_units']) * 100)) : 0;
                    $barColor = match($f['status']) {
                        'overloaded' => '#ef4444',
                        'unassigned' => '#94a3b8',
                        default      => '#3b82f6',
                    };
                    $labelClass = match($f['status']) {
                        'overloaded' => 'text-rose-600 dark:text-rose-400',
                        'unassigned' => 'text-slate-400',
                        default      => 'text-blue-600 dark:text-blue-400',
                    };
                @endphp
                <div class="fade-row">
                    <div class="flex justify-between items-start mb-1.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-tight">{{ $f['name'] }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ ucfirst($f['type'] ?? 'Faculty') }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                            <span class="text-[12px] font-bold tabular-nums {{ $labelClass }}">{{ $f['load'] }}/{{ $f['max_units'] }}</span>
                            @if($f['status'] === 'overloaded')
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 font-bold">OVR</span>
                            @elseif($f['status'] === 'unassigned')
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.05] text-slate-500 border border-slate-200 dark:border-white/10 font-bold">FREE</span>
                            @endif
                        </div>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-white/[0.05] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ $pct }}%; background-color:{{ $barColor }};"></div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                    <p class="text-[13px]">No faculty data</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Faculty Request Status --}}
        <div class="bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-indigo-400 rounded-full shadow-[0_0_8px_#818cf8]"></span>
                    <h3 class="text-[13px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Faculty Requests</h3>
                </div>
                <span class="text-[12px] text-slate-400">{{ count($facultyRequestHistory) }} recent</span>
            </div>

            <div class="panel-scroll overflow-y-auto max-h-[220px] pr-1 space-y-2.5">
                @forelse($facultyRequestHistory as $req)
                @php
                    $isApproved = $req['status'] === 'approved';
                    $isRejected = $req['status'] === 'rejected';
                    $cardBg     = $isApproved
                        ? 'bg-emerald-50/60 dark:bg-emerald-500/[0.04] border-emerald-100 dark:border-emerald-500/15'
                        : ($isRejected
                            ? 'bg-rose-50/60 dark:bg-rose-500/[0.04] border-rose-100 dark:border-rose-500/15'
                            : 'bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/[0.06]');
                    $badgeClass = $isApproved
                        ? 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20'
                        : ($isRejected
                            ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20'
                            : 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20');
                    $badgeLabel = $isApproved ? 'Approved' : ($isRejected ? 'Rejected' : 'Pending');
                    $iconBg     = $isApproved
                        ? 'bg-emerald-100 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                        : ($isRejected
                            ? 'bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400'
                            : 'bg-amber-100 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-600 dark:text-amber-400');
                @endphp
                <div class="flex items-start gap-3 p-3 rounded-xl border {{ $cardBg }} transition-colors fade-row">

                    {{-- Status icon --}}
                    <div class="w-8 h-8 rounded-lg border flex items-center justify-center flex-shrink-0 {{ $iconBg }}">
                        @if($isApproved)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        @elseif($isRejected)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        @else
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                        </svg>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-1.5 mb-0.5">
                            <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-tight">{{ $req['name'] }}</p>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border flex-shrink-0 {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ ucfirst($req['employment_type']) }}
                            @if($req['acted_by'])
                                &nbsp;·&nbsp; by {{ $req['acted_by'] }}
                            @endif
                        </p>
                        @if($isRejected && $req['rejection_reason'])
                        <p class="text-[11px] text-rose-500 dark:text-rose-400 mt-1 line-clamp-2 italic">
                            "{{ $req['rejection_reason'] }}"
                        </p>
                        @endif
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $req['time'] }}</p>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-8 text-center text-slate-400">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.06] flex items-center justify-center mb-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="text-[13px]">No faculty requests yet</p>
                    <p class="text-[11px] mt-0.5">Submitted requests will appear here.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>{{-- end RIGHT COL --}}

</div>{{-- end main grid --}}

</div>{{-- end root --}}
