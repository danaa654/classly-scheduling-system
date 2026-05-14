{{-- resources/views/livewire/registrar-dashboard.blade.php --}}

<style>
    .dash-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
    .dash-scroll::-webkit-scrollbar-track { background: transparent; }
    .dash-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.2); border-radius: 99px; }
    .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.4); }
    .dark .dash-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    .dark .dash-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>

<div
    class="min-h-screen w-full font-mono antialiased overflow-x-hidden transition-colors duration-500
           bg-slate-100 dark:bg-[#080d1a]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image: radial-gradient(ellipse at 30% 10%, rgba(99,102,241,0.07) 0%, transparent 55%), radial-gradient(ellipse at 80% 90%, rgba(168,85,247,0.05) 0%, transparent 55%);'
        : 'background-image: radial-gradient(ellipse at 30% 10%, rgba(99,102,241,0.09) 0%, transparent 55%), radial-gradient(ellipse at 80% 90%, rgba(168,85,247,0.07) 0%, transparent 55%);'"
>

    {{-- ═══ HEADER ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5 pb-0">
        <div class="col-span-12 lg:col-span-5
                    bg-white dark:bg-white/[0.03]
                    border border-slate-200 dark:border-white/10
                    rounded-2xl px-6 py-4 flex items-center justify-between shadow-sm dark:shadow-none">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 shadow-[0_0_8px_#818cf8] animate-pulse"></span>
                    <span class="text-[10px] tracking-[0.3em] text-slate-500 uppercase">Institutional Management — Registrar</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Scheduling <span class="text-indigo-600 dark:text-indigo-400">Operations</span>
                </h1>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ date('l, F d, Y') }} &nbsp;·&nbsp; 1st Semester 2026–2027</p>
            </div>
            <div class="text-right hidden md:block">
                <div class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ date('H:i') }}</div>
                <div class="text-[9px] text-slate-500 uppercase tracking-widest">Local Time</div>
            </div>
        </div>

        {{-- Quick Stat Chips --}}
        <div class="col-span-12 lg:col-span-7 grid grid-cols-3 gap-3">
            @php
                $chips = [
                    ['label' => 'Completion',  'value' => $schedulingStats['completion_pct'] . '%', 'sub' => 'Subjects Scheduled', 'color' => 'indigo'],
                    ['label' => 'Conflicts',   'value' => count($conflicts),                         'sub' => 'Active Conflicts',   'color' => count($conflicts) > 0 ? 'rose' : 'emerald'],
                    ['label' => 'Unscheduled', 'value' => $schedulingStats['unscheduled_subjects'],  'sub' => 'Subjects Pending',   'color' => $schedulingStats['unscheduled_subjects'] > 0 ? 'yellow' : 'emerald'],
                ];
                $chipColors = [
                    'indigo'  => 'border-indigo-300 dark:border-indigo-500/30 text-indigo-600 dark:text-indigo-300 bg-indigo-50 dark:bg-transparent',
                    'rose'    => 'border-rose-300 dark:border-rose-500/30 text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-transparent',
                    'emerald' => 'border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-transparent',
                    'yellow'  => 'border-yellow-300 dark:border-yellow-500/30 text-yellow-600 dark:text-yellow-300 bg-yellow-50 dark:bg-transparent',
                ];
            @endphp
            @foreach($chips as $chip)
            <div class="border {{ $chipColors[$chip['color']] }} rounded-2xl p-4 flex flex-col gap-1.5 hover:shadow-md transition-all cursor-default">
                <span class="text-2xl font-bold tabular-nums">{{ $chip['value'] }}</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-500">{{ $chip['sub'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══ MAIN GRID ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5">

        {{-- LEFT: Schedule Breakdown + Room Utilization --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

            {{-- Schedule Progress --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-indigo-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Schedule Breakdown</h3>
                </div>
                <div class="flex justify-center mb-5">
                    <div class="relative w-32 h-32">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="url(#reg_arc)" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $schedulingStats['completion_pct'] }}, 100"/>
                            <defs>
                                <linearGradient id="reg_arc" x1="0" y1="0" x2="1" y2="0">
                                    <stop stop-color="#6366f1"/><stop offset="1" stop-color="#a855f7"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $schedulingStats['completion_pct'] }}<span class="text-sm opacity-40">%</span></span>
                            <span class="text-[8px] text-slate-500 uppercase">Done</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2.5">
                    @php
                        $rows = [
                            ['label' => 'Total Subjects',  'value' => $schedulingStats['total_subjects'],     'color' => 'text-slate-900 dark:text-white'],
                            ['label' => 'Scheduled',       'value' => $schedulingStats['scheduled_subjects'], 'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Finalized',       'value' => $schedulingStats['finalized_schedules'],'color' => 'text-indigo-600 dark:text-indigo-400'],
                            ['label' => 'Draft / Partial', 'value' => $schedulingStats['draft_schedules'] + $schedulingStats['partial_schedules'], 'color' => 'text-yellow-600 dark:text-yellow-400'],
                            ['label' => 'Unscheduled',     'value' => $schedulingStats['unscheduled_subjects'], 'color' => 'text-rose-600 dark:text-rose-400'],
                        ];
                    @endphp
                    @foreach($rows as $row)
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-white/[0.04]">
                        <span class="text-[11px] text-slate-600 dark:text-slate-500">{{ $row['label'] }}</span>
                        <span class="text-xs font-bold tabular-nums {{ $row['color'] }}">{{ number_format($row['value']) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Room Utilization --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-violet-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Room Utilization</h3>
                </div>
                <div class="space-y-3">
                    @forelse($roomUtilization as $room)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[11px] text-slate-700 dark:text-slate-400 font-medium truncate max-w-[120px]">{{ $room['name'] }}</span>
                            <span class="text-[11px] text-slate-500 tabular-nums">{{ $room['schedules'] }} sessions</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700"
                                style="width: {{ $room['pct'] }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">No room data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- CENTER: Conflicts + Unscheduled --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

            {{-- Conflict Management --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-rose-200 dark:border-rose-500/10
                        rounded-2xl p-5 flex flex-col flex-1 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-rose-400 rounded-full shadow-[0_0_8px_#f87171] animate-pulse"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Conflict Management</h3>
                    </div>
                    @if(count($conflicts) > 0)
                    <span class="px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[9px] font-bold">
                        {{ count($conflicts) }} conflicts
                    </span>
                    @else
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[9px] font-bold">
                        Clear
                    </span>
                    @endif
                </div>
                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-64">
                    @forelse($conflicts as $conflict)
                    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-500/[0.04] border border-rose-200 dark:border-rose-500/10 hover:border-rose-400 dark:hover:border-rose-500/30 transition-colors group">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ $conflict['subject_a'] }} <span class="text-rose-500 dark:text-rose-400">↔</span> {{ $conflict['subject_b'] }}
                                </p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $conflict['room'] }} · {{ $conflict['day'] }} · {{ $conflict['time'] }}</p>
                            </div>
                            <button wire:click="resolveConflict({{ $conflict['schedule_id'] }})"
                                class="shrink-0 px-3 py-1.5 rounded-lg bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[9px] font-bold uppercase hover:bg-rose-200 dark:hover:bg-rose-500/20 transition-colors">
                                Resolve
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <span class="text-3xl mb-2">🟢</span>
                        <p class="text-xs">No scheduling conflicts detected</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Unscheduled Subjects --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-yellow-200 dark:border-yellow-500/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-yellow-400 rounded-full shadow-[0_0_8px_#facc15]"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Unscheduled Subjects</h3>
                    </div>
                    <span class="text-[10px] text-yellow-600 dark:text-yellow-400 font-bold">{{ $schedulingStats['unscheduled_subjects'] }} pending</span>
                </div>
                <div class="space-y-1.5 dash-scroll overflow-y-auto max-h-48">
                    @forelse($unscheduledSubjects as $subj)
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                                bg-slate-50 dark:bg-white/[0.02]
                                hover:bg-slate-100 dark:hover:bg-white/[0.04] transition-colors
                                border border-slate-200 dark:border-white/[0.04] cursor-default">
                        <span class="w-1.5 h-1.5 rounded-full {{ $subj['type'] === 'Major' ? 'bg-indigo-500 dark:bg-indigo-400' : 'bg-violet-500 dark:bg-violet-400' }} shrink-0"></span>
                        <span class="text-[11px] font-bold text-slate-900 dark:text-white w-24 shrink-0">{{ $subj['subject_code'] }}</span>
                        <span class="text-[10px] text-slate-500 truncate">{{ $subj['description'] }}</span>
                        <span class="text-[10px] text-slate-500 shrink-0 ml-auto">Yr.{{ $subj['year_level'] }}</span>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                        <span class="text-2xl mb-1">✅</span>
                        <p class="text-xs">All subjects scheduled</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Faculty Load + Subject Distribution --}}
        <div class="col-span-12 lg:col-span-3 flex flex-col gap-4">

            {{-- Faculty Load --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 flex-1 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-cyan-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Faculty Load</h3>
                </div>
                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-52">
                    @forelse($facultyLoad as $f)
                    @php
                        $lc = match($f['status']) {
                            'overloaded' => 'text-rose-600 dark:text-rose-400',
                            'unassigned' => 'text-slate-500',
                            default      => 'text-emerald-600 dark:text-emerald-400',
                        };
                        $pct = min(100, $f['max_units'] > 0 ? round(($f['load'] / $f['max_units']) * 100) : 0);
                    @endphp
                    <div class="cursor-default">
                        <div class="flex justify-between mb-1">
                            <span class="text-[10px] text-slate-700 dark:text-slate-400 font-medium truncate max-w-[100px]">{{ $f['name'] }}</span>
                            <span class="text-[10px] font-bold {{ $lc }} tabular-nums">{{ $f['load'] }}/{{ $f['max_units'] }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $f['status'] === 'overloaded' ? 'bg-rose-500' : 'bg-gradient-to-r from-indigo-500 to-violet-500' }} transition-all duration-700"
                                style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">No faculty data</p>
                    @endforelse
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
                    $rTotalSub = $schedulingStats['total_subjects'] ?? 0;
                    $rSched    = $schedulingStats['scheduled_subjects'] ?? 0;
                    $rFin      = $schedulingStats['finalized_schedules'] ?? 0;
                    $rUnsched  = $schedulingStats['unscheduled_subjects'] ?? 0;
                    $rSchedPct = $rTotalSub > 0 ? round(($rSched / $rTotalSub) * 100) : 0;
                    $rFinPct   = $rTotalSub > 0 ? round(($rFin / $rTotalSub) * 100) : 0;
                @endphp
                <div class="flex justify-center mb-3">
                    <div class="relative w-24 h-24">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="#f43f5e" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $rFinPct }}, 100"/>
                            <circle stroke-width="3" stroke="#fb923c" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $rSchedPct }}, 100"
                                stroke-dashoffset="{{ -$rFinPct }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($rTotalSub) }}</span>
                            <span class="text-[8px] text-slate-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($rFin) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Finalized</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400 tabular-nums">{{ number_format($rUnsched) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Pending</div>
                    </div>
                </div>
                <div class="space-y-2">
                    @php
                        $distRows = [
                            ['label' => 'Scheduled',   'val' => $rSched,   'pct' => $rSchedPct, 'color' => 'bg-rose-500'],
                            ['label' => 'Finalized',   'val' => $rFin,     'pct' => $rFinPct,   'color' => 'bg-orange-400'],
                            ['label' => 'Unscheduled', 'val' => $rUnsched, 'pct' => $rTotalSub > 0 ? round(($rUnsched / $rTotalSub) * 100) : 0, 'color' => 'bg-yellow-400'],
                        ];
                    @endphp
                    @foreach($distRows as $dr)
                    <div>
                        <div class="flex justify-between mb-0.5">
                            <span class="text-[10px] font-medium text-slate-600 dark:text-slate-400">{{ $dr['label'] }}</span>
                            <span class="text-[10px] text-slate-500 tabular-nums">{{ $dr['val'] }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full {{ $dr['color'] }} transition-all duration-700" style="width: {{ $dr['pct'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Activity Feed --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1 h-4 bg-purple-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Recent Activity</h3>
                </div>
                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-48">
                    @forelse($recentActivities as $a)
                    <div class="border-l-2 border-slate-200 dark:border-white/10 pl-3">
                        <p class="text-[11px] text-slate-700 dark:text-slate-300 leading-snug">{{ $a['description'] }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $a['user'] }} · {{ $a['time'] }}</p>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">No recent activity</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>