{{-- resources/views/livewire/dean-dashboard.blade.php --}}

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
           bg-slate-100 dark:bg-[#07101d]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image: radial-gradient(ellipse at 15% 30%, rgba(59,130,246,0.07) 0%, transparent 55%), radial-gradient(ellipse at 85% 70%, rgba(16,185,129,0.05) 0%, transparent 55%);'
        : 'background-image: radial-gradient(ellipse at 15% 30%, rgba(59,130,246,0.09) 0%, transparent 55%), radial-gradient(ellipse at 85% 70%, rgba(16,185,129,0.07) 0%, transparent 55%);'"
>

    {{-- ═══ HEADER ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5 pb-0">
        <div class="col-span-12 lg:col-span-5
                    bg-white dark:bg-white/[0.03]
                    border border-slate-200 dark:border-white/10
                    rounded-2xl px-6 py-4 flex items-center justify-between shadow-sm dark:shadow-none">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-blue-400 shadow-[0_0_8px_#60a5fa]"></span>
                    <span class="text-[10px] tracking-[0.3em] text-slate-500 uppercase">{{ $department }} Dean's Portal — Academic Oversight</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Dean <span class="text-blue-600 dark:text-blue-400">Dashboard</span>
                </h1>
                <p class="text-[11px] text-slate-500 mt-0.5">Logged in as <span class="text-slate-700 dark:text-slate-300 font-medium">{{ Auth::user()->name }}</span> · {{ date('F d, Y') }}</p>
            </div>
            <div class="px-4 py-2.5 rounded-xl bg-blue-100 dark:bg-blue-500/10 border border-blue-300 dark:border-blue-500/20 text-center hidden md:block">
                <div class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ date('H:i') }}</div>
                <div class="text-[9px] uppercase tracking-widest text-blue-600 dark:text-blue-400">Active</div>
            </div>
        </div>

        {{-- Top Stats --}}
        <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
            @php
                $topStats = [
                    ['label' => 'Faculty',   'value' => $academicOverview['total_faculty'],     'color' => 'text-blue-600 dark:text-blue-400',    'bg' => 'bg-blue-50 dark:bg-transparent border-blue-200 dark:border-white/10'],
                    ['label' => 'Subjects',  'value' => $academicOverview['total_subjects'],    'color' => 'text-slate-900 dark:text-white',       'bg' => 'bg-white dark:bg-transparent border-slate-200 dark:border-white/10'],
                    ['label' => 'Scheduled', 'value' => $academicOverview['scheduled_subjects'],'color' => 'text-emerald-600 dark:text-emerald-400','bg' => 'bg-emerald-50 dark:bg-transparent border-emerald-200 dark:border-white/10'],
                    ['label' => 'Approvals', 'value' => count($approvalQueue),                  'color' => count($approvalQueue) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-emerald-600 dark:text-emerald-400',
                     'bg' => count($approvalQueue) > 0 ? 'bg-yellow-50 dark:bg-transparent border-yellow-200 dark:border-white/10' : 'bg-emerald-50 dark:bg-transparent border-emerald-200 dark:border-white/10'],
                ];
            @endphp
            @foreach($topStats as $s)
            <div class="border {{ $s['bg'] }} rounded-2xl p-4 flex flex-col gap-1.5 hover:shadow-md transition-all cursor-default">
                <span class="text-2xl font-bold tabular-nums {{ $s['color'] }}">{{ number_format($s['value']) }}</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-500">{{ $s['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══ MAIN GRID ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5">

        {{-- LEFT: Curriculum + Conflicts + Faculty Mini --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

            {{-- Curriculum Coverage --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-blue-400 rounded-full shadow-[0_0_8px_#60a5fa]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Curriculum Coverage</h3>
                </div>
                <div class="flex justify-center mb-4">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="url(#dean_arc)" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $academicOverview['completion_rate'] }}, 100"/>
                            <defs>
                                <linearGradient id="dean_arc" x1="0" y1="0" x2="1" y2="0">
                                    <stop stop-color="#3b82f6"/><stop offset="1" stop-color="#10b981"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $academicOverview['completion_rate'] }}<span class="text-sm opacity-40">%</span></span>
                            <span class="text-[8px] text-slate-500 uppercase">Coverage</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    @foreach($curriculumCoverage as $yr)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[11px] text-slate-600 dark:text-slate-400 font-medium">Year {{ $yr['year'] }}</span>
                            <span class="text-[11px] text-slate-500 tabular-nums">{{ $yr['scheduled'] }}/{{ $yr['total'] }} · {{ $yr['pct'] }}%</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-emerald-400 transition-all duration-700"
                                style="width: {{ $yr['pct'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Escalated Conflicts --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-rose-200 dark:border-rose-500/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-rose-400 rounded-full {{ count($escalatedConflicts) > 0 ? 'animate-pulse shadow-[0_0_8px_#f87171]' : '' }}"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Escalated Conflicts</h3>
                    </div>
                    <span class="text-[10px] {{ count($escalatedConflicts) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} font-bold">
                        {{ count($escalatedConflicts) > 0 ? count($escalatedConflicts) . ' active' : 'Clear' }}
                    </span>
                </div>
                <div class="space-y-2 dash-scroll max-h-40 overflow-y-auto">
                    @forelse($escalatedConflicts as $c)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg
                                bg-rose-50 dark:bg-rose-500/[0.04]
                                border border-rose-200 dark:border-rose-500/10">
                        <span class="text-rose-500 dark:text-rose-400">⚠</span>
                        <div>
                            <p class="text-[11px] text-slate-900 dark:text-white font-bold">{{ $c['room'] }}</p>
                            <p class="text-[10px] text-slate-500">{{ $c['day'] }} · {{ $c['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center py-6 text-slate-400">
                        <span class="text-2xl mb-1">🟢</span>
                        <p class="text-xs">No escalated conflicts</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Faculty Assignment Mini --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1 h-4 bg-emerald-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Faculty Assignment</h3>
                </div>
                @php
                    $assigned   = $academicOverview['assigned_faculty'];
                    $unassigned = $academicOverview['unassigned_faculty'];
                    $total      = $academicOverview['total_faculty'];
                    $assignPct  = $total > 0 ? round(($assigned / $total) * 100) : 0;
                @endphp
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-2.5 text-center border border-slate-200 dark:border-white/[0.05]">
                        <div class="text-xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $total }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Total</div>
                    </div>
                    <div class="bg-emerald-50 dark:bg-white/[0.03] rounded-xl p-2.5 text-center border border-emerald-200 dark:border-emerald-500/10">
                        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $assigned }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Assigned</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-white/[0.03] rounded-xl p-2.5 text-center border border-yellow-200 dark:border-yellow-500/10">
                        <div class="text-xl font-bold text-yellow-600 dark:text-yellow-400 tabular-nums">{{ $unassigned }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Idle</div>
                    </div>
                </div>
                <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-emerald-400 rounded-full transition-all duration-700" style="width: {{ $assignPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- CENTER: Approval Queue + Request Tracking --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 flex flex-col flex-1 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-yellow-400 rounded-full shadow-[0_0_8px_#facc15]"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Approval Queue</h3>
                    </div>
                    @if(count($approvalQueue) > 0)
                    <span class="px-2.5 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-500/10 border border-yellow-300 dark:border-yellow-500/20 text-yellow-700 dark:text-yellow-400 text-[9px] font-bold">
                        {{ count($approvalQueue) }} pending
                    </span>
                    @endif
                </div>
                <div class="space-y-3 dash-scroll overflow-y-auto flex-1 max-h-72">
                    @forelse($approvalQueue as $item)
                    <div class="flex items-center gap-4 p-4 rounded-xl
                                bg-slate-50 dark:bg-white/[0.02]
                                border border-slate-200 dark:border-white/[0.06]
                                hover:border-blue-300 dark:hover:border-blue-500/30 transition-all group">
                        <div class="w-10 h-10 shrink-0 rounded-xl bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-300 text-[10px] font-bold">
                            {{ strtoupper(substr($item['type'], 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $item['description'] }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $item['type'] }} · by {{ $item['submitted_by'] }} · {{ $item['time'] }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button wire:click="approveItem({{ $item['id'] }}, '{{ $item['module'] }}')"
                                class="px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[9px] font-bold uppercase hover:bg-emerald-200 dark:hover:bg-emerald-500/20 transition-colors">
                                Approve
                            </button>
                            <button wire:click="rejectItem({{ $item['id'] }}, '{{ $item['module'] }}')"
                                class="px-3 py-1.5 rounded-lg bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[9px] font-bold uppercase hover:bg-rose-200 dark:hover:bg-rose-500/20 transition-colors">
                                Reject
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center h-48 text-slate-400">
                        <span class="text-4xl mb-3">📭</span>
                        <p class="text-sm">No pending approvals</p>
                        <p class="text-[10px] text-slate-500 mt-1">You're all caught up.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Request Tracking --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-slate-400 dark:bg-slate-500 rounded-full"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Request Tracking</h3>
                    </div>
                    <span class="text-[10px] text-slate-500">From all modules</span>
                </div>
                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-52">
                    @forelse($requestTracking as $req)
                    @php
                        $borderColor = match($req['status']) {
                            'approved' => 'border-l-emerald-500 bg-emerald-50 dark:bg-emerald-500/[0.03]',
                            'rejected' => 'border-l-rose-500 bg-rose-50 dark:bg-rose-500/[0.03]',
                            default    => 'border-l-slate-300 dark:border-l-slate-600 bg-slate-50 dark:bg-white/[0.02]',
                        };
                        $dot = match($req['status']) { 'approved' => '✓', 'rejected' => '✕', default => '·' };
                        $dotColor = match($req['status']) {
                            'approved' => 'bg-emerald-500 text-white',
                            'rejected' => 'bg-rose-500 text-white',
                            default    => 'bg-slate-300 dark:bg-slate-600 text-slate-700 dark:text-white',
                        };
                    @endphp
                    <div class="flex items-start gap-3 p-3 rounded-lg border-l-2 {{ $borderColor }}">
                        <div class="w-5 h-5 shrink-0 rounded-full {{ $dotColor }} flex items-center justify-center text-[8px] font-bold mt-0.5">{{ $dot }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] text-slate-700 dark:text-slate-300 truncate">{{ $req['description'] }}</p>
                            <p class="text-[10px] text-slate-500">{{ $req['user'] }} · {{ $req['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">No tracking data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Faculty Loading + Subject Distribution --}}
        <div class="col-span-12 lg:col-span-3 flex flex-col gap-4">

            {{-- Faculty Loading --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-sky-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Faculty Loading</h3>
                </div>
                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-52">
                    @forelse($facultySummary as $f)
                    @php
                        $sc = match($f['status']) {
                            'overloaded' => 'text-rose-600 dark:text-rose-400 border-rose-300 dark:border-rose-500/20',
                            'unassigned' => 'text-slate-500 border-slate-200 dark:border-white/10',
                            default      => 'text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                        };
                        $pct = $f['max_units'] > 0 ? min(100, round(($f['load'] / $f['max_units']) * 100)) : 0;
                    @endphp
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-white/[0.02] border border-slate-200 dark:border-white/[0.04] hover:border-slate-300 dark:hover:border-white/10 transition-colors cursor-default">
                        <div class="flex justify-between items-start mb-1.5">
                            <span class="text-[11px] text-slate-800 dark:text-slate-300 font-semibold truncate max-w-[110px]">{{ $f['name'] }}</span>
                            <span class="text-[9px] font-bold {{ $sc }} px-1.5 py-0.5 rounded border">{{ $f['load'] }}/{{ $f['max_units'] }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $f['status'] === 'overloaded' ? 'bg-rose-500' : 'bg-gradient-to-r from-sky-500 to-blue-400' }} transition-all duration-700"
                                style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-[9px] text-slate-500 mt-1">{{ ucfirst($f['type'] ?? 'Faculty') }}</p>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center h-40 text-slate-400">
                        <span class="text-2xl mb-2">👥</span>
                        <p class="text-xs">No faculty data</p>
                    </div>
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
                    $majorCount = $academicOverview['major_subjects'] ?? 0;
                    $minorCount = $academicOverview['minor_subjects'] ?? 0;
                    $dTotalSub  = $majorCount + $minorCount;
                    $dMajPct    = $dTotalSub > 0 ? round(($majorCount / $dTotalSub) * 100) : 0;
                    $dMinPct    = $dTotalSub > 0 ? round(($minorCount / $dTotalSub) * 100) : 0;
                @endphp
                <div class="flex justify-center mb-3">
                    <div class="relative w-24 h-24">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="#f43f5e" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $dMajPct }}, 100"/>
                            <circle stroke-width="3" stroke="#fb923c" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $dMinPct }}, 100"
                                stroke-dashoffset="{{ -$dMajPct }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($dTotalSub) }}</span>
                            <span class="text-[8px] text-slate-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($majorCount) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Major</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400 tabular-nums">{{ number_format($minorCount) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Minor</div>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach($curriculumCoverage as $yr)
                    <div>
                        <div class="flex justify-between mb-0.5">
                            <span class="text-[10px] font-medium text-slate-700 dark:text-slate-300">Year {{ $yr['year'] }}</span>
                            <span class="text-[10px] text-slate-500 tabular-nums">{{ $yr['total'] }}</span>
                        </div>
                        <div class="flex gap-0.5 h-1.5 rounded-full overflow-hidden">
                            @php
                                $yPct = $yr['total'] > 0 ? round(($yr['scheduled'] / $yr['total']) * 100) : 0;
                            @endphp
                            <div class="h-full bg-blue-500 transition-all duration-700" style="width: {{ $yPct }}%"></div>
                            <div class="h-full bg-slate-200 dark:bg-white/[0.06] flex-1"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>