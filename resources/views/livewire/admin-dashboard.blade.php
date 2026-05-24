<style>
    .dash-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
    .dash-scroll::-webkit-scrollbar-track { background: transparent; }
    .dash-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.2); border-radius: 99px; }
    .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.4); }
    .dark .dash-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    .dark .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>

<div
    x-data="{
        tick: 0,
        clock: '{{ date('H:i:s') }}',
        init() {
            setInterval(() => {
                this.clock = new Date().toLocaleTimeString('en-GB');
                this.tick++;
            }, 1000);
        }
    }"
    class="min-h-screen w-full font-mono antialiased overflow-x-hidden transition-colors duration-500
           bg-slate-100 dark:bg-[#0a0f1e]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image: radial-gradient(ellipse at 20% 20%, rgba(59,130,246,0.06) 0%, transparent 60%), radial-gradient(ellipse at 80% 80%, rgba(139,92,246,0.05) 0%, transparent 60%);'
        : 'background-image: radial-gradient(ellipse at 20% 20%, rgba(59,130,246,0.08) 0%, transparent 60%), radial-gradient(ellipse at 80% 80%, rgba(139,92,246,0.06) 0%, transparent 60%);'"
>

    {{-- ═══ HEADER ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5 pb-0">

        {{-- Logo / Role --}}
        <div class="col-span-12 lg:col-span-5 flex items-center justify-between
                    bg-white dark:bg-white/[0.03]
                    border border-slate-200 dark:border-white/10
                    rounded-2xl px-6 py-4 backdrop-blur-sm shadow-sm dark:shadow-none">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse"></span>
                    <span class="text-[10px] tracking-[0.3em] text-slate-500 dark:text-slate-500 uppercase">System Administrator — Root Access</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    CLASSLY <span class="text-blue-500 dark:text-blue-400">Control</span>
                </h1>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ date('l, F d, Y') }} &nbsp;·&nbsp; Node: PAP-CEBU-V3</p>
            </div>
            <div class="hidden md:flex flex-col items-end gap-1">
                <span x-text="clock" class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white tracking-widest"></span>
                <span class="text-[9px] text-slate-500 uppercase tracking-widest">Server Time</span>
            </div>
        </div>

        {{-- Vitals Row --}}
        <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
            @php
                $vitals = [
                    ['label' => 'Users',     'value' => $stats['total_users'],     'color' => 'blue',    'icon' => '👤'],
                    ['label' => 'Faculty',   'value' => $stats['total_faculty'],   'color' => 'violet',  'icon' => '🎓'],
                    ['label' => 'Rooms',     'value' => $stats['total_rooms'],     'color' => 'cyan',    'icon' => '🏫'],
                    ['label' => 'Schedules', 'value' => $stats['total_schedules'], 'color' => 'emerald', 'icon' => '📅'],
                ];
                $vCard = [
                    'blue'    => 'border-blue-400/40  dark:border-blue-500/30  text-blue-600  dark:text-blue-400  bg-blue-50   dark:bg-transparent',
                    'violet'  => 'border-violet-400/40 dark:border-violet-500/30 text-violet-600 dark:text-violet-400 bg-violet-50  dark:bg-transparent',
                    'cyan'    => 'border-cyan-400/40  dark:border-cyan-500/30  text-cyan-600  dark:text-cyan-400  bg-cyan-50   dark:bg-transparent',
                    'emerald' => 'border-emerald-400/40 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-transparent',
                ];
            @endphp
            @foreach($vitals as $v)
            <div class="border {{ $vCard[$v['color']] }} rounded-2xl p-4 flex flex-col gap-1.5 hover:shadow-md transition-all cursor-default">
                <span class="text-xl">{{ $v['icon'] }}</span>
                <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($v['value']) }}</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-500">{{ $v['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══ MAIN GRID ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5">

        {{-- LEFT: System Status + Analytics --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

            {{-- System Status --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors duration-500">
                <div class="flex items-center gap-2 mb-5">
                    <span class="w-1 h-4 bg-emerald-400 rounded-full shadow-[0_0_8px_#34d399]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">System Status</h3>
                </div>
                @php
                    $statusItems = [
                        ['label' => 'AI Scheduler',     'status' => $systemStatus['scheduler'],       'detail' => $systemStatus['conflict_count'] . ' conflicts'],
                        ['label' => 'Database',          'status' => $systemStatus['db_tables'],       'detail' => 'Connected'],
                        ['label' => 'Room Index',        'status' => $systemStatus['rooms_available'], 'detail' => $stats['total_rooms'] . ' rooms'],
                        ['label' => 'Faculty Approved',  'status' => 'healthy',                        'detail' => $systemStatus['faculty_assigned'] . ' active'],
                        ['label' => 'Pending Faculty',   'status' => $stats['pending_faculty'] > 0 ? 'warning' : 'healthy', 'detail' => $stats['pending_faculty'] . ' pending'],
                        ['label' => 'Semester',          'status' => 'healthy',                        'detail' => '1st 2026–2027'],
                    ];
                    $sDot = [
                        'healthy'  => 'bg-emerald-400 shadow-[0_0_6px_#34d399]',
                        'warning'  => 'bg-yellow-400 shadow-[0_0_6px_#facc15]',
                        'critical' => 'bg-rose-500 shadow-[0_0_6px_#f43f5e]',
                    ];
                @endphp
                <div class="space-y-3">
                    @foreach($statusItems as $item)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $sDot[$item['status']] ?? $sDot['healthy'] }}"></span>
                            <span class="text-[11px] text-slate-600 dark:text-slate-400">{{ $item['label'] }}</span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-400 tabular-nums">{{ $item['detail'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Scheduling Analytics --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Schedule Analytics</h3>
                </div>
                <div class="flex items-center justify-center mb-5">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.06]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="url(#admin_arc)" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $schedulingAnalytics['completion_rate'] }}, 100"/>
                            <defs>
                                <linearGradient id="admin_arc" x1="0" y1="0" x2="1" y2="0">
                                    <stop stop-color="#3b82f6"/><stop offset="1" stop-color="#8b5cf6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $schedulingAnalytics['completion_rate'] }}<span class="text-xs opacity-40">%</span></span>
                            <span class="text-[8px] text-slate-500 uppercase tracking-widest">Done</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $analyticsItems = [
                            ['label' => 'Finalized',   'value' => $schedulingAnalytics['finalized'],          'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Draft',       'value' => $schedulingAnalytics['draft'],               'color' => 'text-yellow-600 dark:text-yellow-400'],
                            ['label' => 'Partial',     'value' => $schedulingAnalytics['partial'],             'color' => 'text-blue-600 dark:text-blue-400'],
                            ['label' => 'Unscheduled', 'value' => $schedulingAnalytics['subjects_unscheduled'],'color' => 'text-rose-600 dark:text-rose-400'],
                        ];
                    @endphp
                    @foreach($analyticsItems as $a)
                    <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-2.5 border border-slate-200 dark:border-white/[0.05]">
                        <p class="text-[9px] text-slate-500 uppercase mb-1">{{ $a['label'] }}</p>
                        <p class="text-xl font-bold {{ $a['color'] }} tabular-nums">{{ number_format($a['value']) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Subject Distribution --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-amber-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Subject Distribution</h3>
                </div>
                @php
                    $totalSub = ($schedulingAnalytics['total_scheduled'] ?? 0);
                    $finPct   = $totalSub > 0 ? round(($schedulingAnalytics['finalized'] / $totalSub) * 100) : 0;
                    $draftPct = $totalSub > 0 ? round(($schedulingAnalytics['draft'] / $totalSub) * 100) : 0;
                @endphp
                <div class="flex justify-center mb-4">
                    <div class="relative w-24 h-24">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="#f43f5e" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $finPct }}, 100"/>
                            <circle stroke-width="3" stroke="#fb923c" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $draftPct }}, 100"
                                stroke-dashoffset="{{ -$finPct }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($totalSub) }}</span>
                            <span class="text-[8px] text-slate-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($schedulingAnalytics['finalized'] ?? 0) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Finalized</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400 tabular-nums">{{ number_format($schedulingAnalytics['draft'] ?? 0) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Draft</div>
                    </div>
                </div>
                <div class="space-y-2 dash-scroll overflow-y-auto max-h-28">
                    @php
                        $subRows = [
                            ['label' => 'Finalized', 'val' => $schedulingAnalytics['finalized'] ?? 0,  'pct' => $finPct,  'color' => 'bg-rose-500'],
                            ['label' => 'Draft',     'val' => $schedulingAnalytics['draft'] ?? 0,      'pct' => $draftPct,'color' => 'bg-orange-400'],
                            ['label' => 'Partial',   'val' => $schedulingAnalytics['partial'] ?? 0,    'pct' => $totalSub > 0 ? round(($schedulingAnalytics['partial'] / $totalSub) * 100) : 0, 'color' => 'bg-yellow-400'],
                        ];
                    @endphp
                    @foreach($subRows as $sr)
                    <div>
                        <div class="flex justify-between mb-0.5">
                            <span class="text-[10px] font-medium text-slate-600 dark:text-slate-400">{{ $sr['label'] }}</span>
                            <span class="text-[10px] text-slate-500 tabular-nums">{{ $sr['val'] }}</span>
                        </div>
                        <div class="h-1 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full {{ $sr['color'] }}" style="width: {{ $sr['pct'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- CENTER: Pending Verifications + Conflict Alerts --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

            {{-- Pending Faculty Verifications --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-violet-400 rounded-full shadow-[0_0_8px_#a78bfa]"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Pending Verifications</h3>
                    </div>
                    @if(count($pendingVerifications) > 0)
                    <span class="px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-500/10 border border-violet-300 dark:border-violet-500/20 text-violet-600 dark:text-violet-400 text-[9px] font-bold uppercase">
                        {{ count($pendingVerifications) }} pending
                    </span>
                    @endif
                </div>
                <div class="space-y-2.5 flex-1 dash-scroll overflow-y-auto max-h-52">
                    @forelse($pendingVerifications as $pending)
                    <div class="flex items-center gap-3 p-3 rounded-xl
                                bg-slate-50 dark:bg-white/[0.02]
                                border border-slate-200 dark:border-white/[0.05]
                                hover:border-violet-300 dark:hover:border-violet-500/30 transition-colors group">
                        <div class="w-9 h-9 shrink-0 rounded-lg bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 text-[11px] font-bold border border-violet-200 dark:border-violet-500/20">
                            {{ strtoupper(substr($pending['name'], 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $pending['name'] }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $pending['department'] }} · {{ $pending['time'] }}</p>
                        </div>
                        <div class="flex gap-1.5 shrink-0">
                            <button wire:click="approveFaculty({{ $pending['id'] }})"
                                class="px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[9px] font-bold uppercase hover:bg-emerald-200 dark:hover:bg-emerald-500/20 transition-colors">
                                Verify
                            </button>
                            <button wire:click="rejectFaculty({{ $pending['id'] }})"
                                class="px-3 py-1.5 rounded-lg bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[9px] font-bold uppercase hover:bg-rose-200 dark:hover:bg-rose-500/20 transition-colors">
                                Deny
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <span class="text-3xl mb-2">✅</span>
                        <p class="text-xs">No pending verifications</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Conflict Alerts --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-rose-200 dark:border-rose-500/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-rose-400 rounded-full shadow-[0_0_8px_#f87171] animate-pulse"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Critical Alerts</h3>
                    </div>
                    @if(count($conflictAlerts) > 0)
                    <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-[9px] font-bold uppercase">
                        {{ count($conflictAlerts) }} issues
                    </span>
                    @endif
                </div>
                <div class="space-y-2 dash-scroll overflow-y-auto max-h-52">
                    @forelse($conflictAlerts as $alert)
                    <div class="flex items-start gap-3 p-3 rounded-xl
                                bg-rose-50 dark:bg-rose-500/[0.04]
                                border border-rose-200 dark:border-rose-500/10
                                hover:border-rose-400 dark:hover:border-rose-500/30 transition-colors cursor-default">
                        <span class="text-rose-500 dark:text-rose-400 text-sm mt-0.5">⚠</span>
                        <div class="flex-1 min-w-0">
                            @if(isset($alert['room']))
                            <p class="text-xs font-bold text-slate-900 dark:text-white">Room Conflict: {{ $alert['room'] }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $alert['day'] }} · {{ $alert['time'] }}</p>
                            @else
                            <p class="text-xs font-bold text-slate-900 dark:text-white">Faculty Overload: {{ $alert['name'] }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $alert['dept'] }} · {{ $alert['load'] }} sessions</p>
                            @endif
                        </div>
                        <span class="px-2 py-0.5 rounded bg-rose-200 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 text-[8px] font-bold uppercase shrink-0">
                            {{ $alert['severity'] ?? 'alert' }}
                        </span>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <span class="text-2xl mb-2">🟢</span>
                        <p class="text-xs">No active conflicts detected</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Activity Feed --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 h-full flex flex-col shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-cyan-400 rounded-full shadow-[0_0_8px_#22d3ee]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Activity Feed</h3>
                </div>
                <div class="flex-1 space-y-3 dash-scroll overflow-y-auto pr-1">
                    @forelse($recentActivities as $activity)
                    @php
                        $severityColor = match($activity['severity'] ?? 'info') {
                            'critical' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10 border-rose-300 dark:border-rose-500/20',
                            'warning'  => 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10 border-yellow-300 dark:border-yellow-500/20',
                            default    => 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-500/10 border-cyan-300 dark:border-cyan-500/20',
                        };
                    @endphp
                    <div class="flex gap-2.5 group">
                        <div class="flex flex-col items-center gap-1 shrink-0">
                            <div class="w-6 h-6 rounded-lg {{ $severityColor }} border flex items-center justify-center text-[9px] font-bold shrink-0">
                                {{ strtoupper(substr($activity['module'], 0, 2)) }}
                            </div>
                            <div class="flex-1 w-px bg-slate-200 dark:bg-white/[0.05]"></div>
                        </div>
                        <div class="pb-3 min-w-0 flex-1">
                            <p class="text-[11px] text-slate-700 dark:text-slate-300 leading-snug truncate">{{ $activity['description'] }}</p>
                            <p class="text-[9px] text-slate-500 mt-0.5">{{ $activity['user'] }} · {{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 py-8">
                        <span class="text-2xl mb-2">📋</span>
                        <p class="text-xs">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ AUDIT TABLE ═══ --}}
    <div class="px-5 pb-5">
        <div class="bg-white dark:bg-white/[0.03]
                    border border-slate-200 dark:border-white/10
                    rounded-2xl p-5 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Global Audit Log</h3>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-500 uppercase tracking-widest">Auto-sync: Active</span>
                    <a href="{{ route('manage-users') }}"
                        class="px-4 py-1.5 rounded-lg bg-blue-100 dark:bg-blue-500/10 border border-blue-300 dark:border-blue-500/20 text-blue-700 dark:text-blue-400 text-[10px] font-bold uppercase hover:bg-blue-200 dark:hover:bg-blue-500/20 transition-colors">
                        Manage Users
                    </a>
                </div>
            </div>
            <div class="dash-scroll overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/[0.05] text-[10px] uppercase tracking-widest text-slate-500">
                            <th class="text-left py-2.5 pr-6 font-semibold">Action</th>
                            <th class="text-left py-2.5 pr-6 font-semibold">Module</th>
                            <th class="text-left py-2.5 pr-6 font-semibold">User</th>
                            <th class="text-left py-2.5 font-semibold">Description</th>
                            <th class="text-right py-2.5 font-semibold">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.03]">
                        @forelse($recentActivities as $a)
                        @php
                            $actionColor = match($a['severity'] ?? 'info') {
                                'critical' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10',
                                'warning'  => 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-500/10',
                                default    => 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-500/10',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="py-3 pr-6">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $actionColor }}">{{ $a['action'] }}</span>
                            </td>
                            <td class="py-3 pr-6 text-slate-600 dark:text-slate-400 font-bold text-[11px] uppercase">{{ $a['module'] }}</td>
                            <td class="py-3 pr-6 text-slate-600 dark:text-slate-400 text-[11px]">{{ $a['user'] }}</td>
                            <td class="py-3 text-slate-500 text-[11px] truncate max-w-xs">{{ $a['description'] }}</td>
                            <td class="py-3 text-right text-slate-500 text-[11px] tabular-nums">{{ $a['time'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400 text-xs">No activity logs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>