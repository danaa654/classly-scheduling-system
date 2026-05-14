{{-- resources/views/livewire/assistant-dean-dashboard.blade.php --}}

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
           bg-slate-100 dark:bg-[#0b0710]
           text-slate-800 dark:text-slate-100"
    :style="darkMode
        ? 'background-image: radial-gradient(ellipse at 25% 15%, rgba(244,63,94,0.06) 0%, transparent 55%), radial-gradient(ellipse at 75% 85%, rgba(251,146,60,0.05) 0%, transparent 55%);'
        : 'background-image: radial-gradient(ellipse at 25% 15%, rgba(244,63,94,0.08) 0%, transparent 55%), radial-gradient(ellipse at 75% 85%, rgba(251,146,60,0.06) 0%, transparent 55%);'"
>

    {{-- ═══ HEADER ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5 pb-0">
        <div class="col-span-12 lg:col-span-5
                    bg-white dark:bg-white/[0.03]
                    border border-slate-200 dark:border-white/10
                    rounded-2xl px-6 py-4 flex items-center justify-between shadow-sm dark:shadow-none">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-rose-400 shadow-[0_0_8px_#f87171] animate-pulse"></span>
                    <span class="text-[10px] tracking-[0.3em] text-slate-500 uppercase">Academic Oversight — Associate Dean</span>
                    <span class="px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-[8px] font-bold uppercase">Academy-Wide</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Associate Dean <span class="text-rose-500 dark:text-rose-400">Portal</span>
                </h1>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ date('l, F d, Y') }} · Academic Year 2026–2027</p>
            </div>
            <div class="text-right hidden md:block">
                <div class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ date('H:i') }}</div>
                <div class="text-[9px] text-slate-500 uppercase tracking-widest">Server Time</div>
            </div>
        </div>

        {{-- Global Stats --}}
        <div class="col-span-12 lg:col-span-7 grid grid-cols-4 gap-3">
            @php
                $gStats = [
                    ['label' => 'Faculty',    'value' => $globalStats['total_faculty'],       'color' => 'text-rose-600 dark:text-rose-300',   'bg' => 'bg-rose-50 dark:bg-transparent border-rose-200 dark:border-white/10'],
                    ['label' => 'Major Subj', 'value' => $globalStats['major_subjects'],      'color' => 'text-orange-600 dark:text-orange-300','bg' => 'bg-orange-50 dark:bg-transparent border-orange-200 dark:border-white/10'],
                    ['label' => 'Minor Subj', 'value' => $globalStats['minor_subjects'],      'color' => 'text-amber-600 dark:text-amber-300',  'bg' => 'bg-amber-50 dark:bg-transparent border-amber-200 dark:border-white/10'],
                    ['label' => 'Completion', 'value' => $globalStats['completion_pct'] . '%','color' => 'text-emerald-600 dark:text-emerald-300','bg' => 'bg-emerald-50 dark:bg-transparent border-emerald-200 dark:border-white/10'],
                ];
            @endphp
            @foreach($gStats as $s)
            <div class="border {{ $s['bg'] }} {{ $s['color'] }} rounded-2xl p-4 flex flex-col gap-1.5 hover:shadow-md transition-all cursor-default">
                <span class="text-2xl font-bold tabular-nums">{{ $s['value'] }}</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-500">{{ $s['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══ MAIN GRID ═══ --}}
    <div class="grid grid-cols-12 gap-4 p-5">

        {{-- LEFT: Faculty Coordination + AI Recommendations --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

            {{-- Faculty Coordination --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-rose-400 rounded-full shadow-[0_0_8px_#f87171]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Faculty Coordination</h3>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-4">
                    @php
                        $fc = [
                            ['label' => 'Overloaded', 'value' => $facultyCoordination['overloaded'],  'color' => 'text-rose-600 dark:text-rose-400',    'border' => 'border-rose-200 dark:border-rose-500/20',    'bg' => 'bg-rose-50 dark:bg-transparent'],
                            ['label' => 'Normal',     'value' => $facultyCoordination['normal'],      'color' => 'text-emerald-600 dark:text-emerald-400','border' => 'border-emerald-200 dark:border-emerald-500/20','bg' => 'bg-emerald-50 dark:bg-transparent'],
                            ['label' => 'Idle',       'value' => $facultyCoordination['underloaded'], 'color' => 'text-slate-600 dark:text-slate-400',   'border' => 'border-slate-200 dark:border-white/10',      'bg' => 'bg-slate-50 dark:bg-transparent'],
                        ];
                    @endphp
                    @foreach($fc as $c)
                    <div class="rounded-xl p-2.5 border {{ $c['border'] }} {{ $c['bg'] }} text-center">
                        <div class="text-xl font-bold {{ $c['color'] }} tabular-nums">{{ $c['value'] }}</div>
                        <div class="text-[9px] uppercase tracking-wide text-slate-500 mt-0.5">{{ $c['label'] }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Dept breakdown bars --}}
                <div class="space-y-2.5 mb-4">
                    @foreach($facultyCoordination['dept_breakdown'] ?? [] as $dept => $count)
                    @php $maxDept = max(array_values($facultyCoordination['dept_breakdown'] ?? [1])); @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[10px] text-slate-600 dark:text-slate-400 font-semibold">{{ $dept }}</span>
                            <span class="text-[10px] text-slate-500 tabular-nums">{{ $count }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 dark:bg-white/[0.06] rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-rose-500 to-orange-400 transition-all duration-700"
                                style="width: {{ $maxDept > 0 ? round(($count / $maxDept) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Top Loaded Faculty --}}
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 mb-2.5">Top Loading</p>
                    <div class="space-y-2.5">
                        @foreach($facultyCoordination['top_loaded'] ?? [] as $f)
                        @php
                            $statusColor = match($f['status']) {
                                'overloaded' => 'text-rose-600 dark:text-rose-400',
                                'unassigned' => 'text-slate-500',
                                default      => 'text-emerald-600 dark:text-emerald-400',
                            };
                        @endphp
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-white/[0.04]">
                            <div>
                                <p class="text-[11px] text-slate-800 dark:text-slate-300 font-semibold truncate max-w-[120px]">{{ $f['name'] }}</p>
                                <p class="text-[9px] text-slate-500">{{ $f['department'] }}</p>
                            </div>
                            <span class="text-[11px] font-bold {{ $statusColor }} tabular-nums">{{ $f['load'] }}/{{ $f['max'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- AI Recommendations --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-amber-400 rounded-full shadow-[0_0_8px_#fbbf24]"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">AI Recommendations</h3>
                </div>
                <div class="space-y-2.5">
                    @foreach($aiRecommendations as $rec)
                    @php
                        $recColors = [
                            'critical' => 'bg-rose-50 dark:bg-rose-500/[0.04] border-rose-300 dark:border-rose-500/15 text-rose-700 dark:text-rose-300',
                            'warning'  => 'bg-yellow-50 dark:bg-yellow-500/[0.04] border-yellow-300 dark:border-yellow-500/15 text-yellow-700 dark:text-yellow-300',
                            'info'     => 'bg-blue-50 dark:bg-blue-500/[0.04] border-blue-300 dark:border-blue-500/15 text-blue-700 dark:text-blue-300',
                            'success'  => 'bg-emerald-50 dark:bg-emerald-500/[0.04] border-emerald-300 dark:border-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                        ];
                        $rc = $recColors[$rec['type']] ?? $recColors['info'];
                    @endphp
                    <div class="p-3 rounded-xl border {{ $rc }}">
                        <div class="flex items-start gap-2">
                            <span class="text-sm shrink-0 mt-0.5">{{ $rec['icon'] }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold leading-snug">{{ $rec['title'] }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">{{ $rec['detail'] }}</p>
                                @if($rec['action'])
                                <button class="mt-1.5 text-[9px] uppercase tracking-widest font-bold opacity-60 hover:opacity-100 transition-opacity">{{ $rec['action'] }} →</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- CENTER: Schedule Review + Curriculum Validation --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">

            {{-- Schedule Review Queue --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col flex-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-orange-400 rounded-full shadow-[0_0_8px_#fb923c]"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Schedule Review Queue</h3>
                    </div>
                    @if(count($scheduleReview) > 0)
                    <span class="px-2.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-500/10 border border-orange-300 dark:border-orange-500/20 text-orange-700 dark:text-orange-400 text-[9px] font-bold">
                        {{ count($scheduleReview) }} flagged
                    </span>
                    @endif
                </div>
                <div class="dash-scroll overflow-y-auto flex-1 max-h-64">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 bg-white dark:bg-[#0b0710]">
                            <tr class="border-b border-slate-200 dark:border-white/[0.06] text-[9px] uppercase tracking-widest text-slate-500">
                                <th class="text-left py-2.5 pr-3 font-semibold">Subject</th>
                                <th class="text-left py-2.5 pr-3 font-semibold">Room</th>
                                <th class="text-left py-2.5 pr-3 font-semibold">Faculty</th>
                                <th class="text-left py-2.5 pr-3 font-semibold">Day</th>
                                <th class="text-left py-2.5 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.03]">
                            @forelse($scheduleReview as $s)
                            @php
                                $statusBadge = match($s['status']) {
                                    'draft'   => 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-300 dark:border-yellow-500/20',
                                    'partial' => 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-300 dark:border-orange-500/20',
                                    default   => 'bg-slate-100 dark:bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-500/20',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors cursor-default">
                                <td class="py-2.5 pr-3">
                                    <div class="text-[11px] font-bold text-slate-900 dark:text-white">{{ $s['subject'] }}</div>
                                    <div class="text-[9px] text-slate-500">{{ $s['department'] }}</div>
                                </td>
                                <td class="py-2.5 pr-3 text-[10px] text-slate-600 dark:text-slate-400">{{ $s['room'] }}</td>
                                <td class="py-2.5 pr-3">
                                    <div class="text-[10px] {{ $s['faculty'] === 'Unassigned' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400' }}">
                                        {{ $s['faculty'] }}
                                    </div>
                                </td>
                                <td class="py-2.5 pr-3 text-[10px] text-slate-600 dark:text-slate-400">{{ $s['day'] }}</td>
                                <td class="py-2.5">
                                    <span class="px-1.5 py-0.5 rounded border text-[8px] font-bold uppercase {{ $statusBadge }}">{{ $s['status'] }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center">
                                    <div class="flex flex-col items-center gap-1 text-slate-400">
                                        <span class="text-2xl">✅</span>
                                        <p class="text-xs">No schedules pending review</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Curriculum Validation --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-rose-200 dark:border-rose-500/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 bg-rose-400 rounded-full {{ count($curriculumValidation) > 0 ? 'animate-pulse' : '' }}"></span>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Curriculum Validation</h3>
                    </div>
                    <span class="text-[10px] {{ count($curriculumValidation) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} font-bold">
                        {{ count($curriculumValidation) > 0 ? count($curriculumValidation) . ' issues' : 'Validated' }}
                    </span>
                </div>
                <div class="space-y-2 dash-scroll overflow-y-auto max-h-48">
                    @forelse($curriculumValidation as $issue)
                    <div class="flex items-start gap-3 p-2.5 rounded-lg
                                bg-rose-50 dark:bg-rose-500/[0.03]
                                border border-rose-200 dark:border-rose-500/[0.08]
                                hover:border-rose-400 dark:hover:border-rose-500/20 transition-colors">
                        <span class="text-rose-500 dark:text-rose-400 text-sm mt-0.5 shrink-0">○</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-900 dark:text-white truncate">{{ $issue['subject_code'] ?? '—' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $issue['issue'] }} · {{ $issue['department'] ?? '—' }} · Yr.{{ $issue['year_level'] ?? '?' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[8px] font-bold uppercase shrink-0">
                            {{ $issue['type'] ?? '—' }}
                        </span>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                        <span class="text-3xl mb-2">🎯</span>
                        <p class="text-xs">Curriculum fully validated</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Subject Distribution --}}
        <div class="col-span-12 lg:col-span-3 flex flex-col gap-4">

            {{-- Subject Distribution --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 flex-1 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-4 bg-amber-400 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Subject Distribution</h3>
                </div>

                @php
                    $totalSub = ($subjectDistribution['total_major'] ?? 0) + ($subjectDistribution['total_minor'] ?? 0);
                    $majorPct = $totalSub > 0 ? round(($subjectDistribution['total_major'] / $totalSub) * 100) : 0;
                    $minorPct = $totalSub > 0 ? round(($subjectDistribution['total_minor'] / $totalSub) * 100) : 0;
                @endphp
                <div class="flex justify-center mb-4">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle stroke-width="3" stroke="currentColor" class="text-slate-200 dark:text-white/[0.05]" fill="none" r="15" cx="18" cy="18"/>
                            <circle stroke-width="3" stroke="#f43f5e" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $majorPct }}, 100"/>
                            <circle stroke-width="3" stroke="#fb923c" stroke-linecap="round" fill="none" r="15" cx="18" cy="18"
                                stroke-dasharray="{{ $minorPct }}, 100"
                                stroke-dashoffset="{{ -$majorPct }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($totalSub) }}</span>
                            <span class="text-[8px] text-slate-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-200 dark:border-rose-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($subjectDistribution['total_major'] ?? 0) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Major</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/[0.05] border border-orange-200 dark:border-orange-500/15 rounded-xl p-2.5 text-center">
                        <div class="text-xl font-bold text-orange-600 dark:text-orange-400 tabular-nums">{{ number_format($subjectDistribution['total_minor'] ?? 0) }}</div>
                        <div class="text-[8px] text-slate-500 uppercase">Minor</div>
                    </div>
                </div>

                <div class="space-y-2.5 dash-scroll overflow-y-auto max-h-48">
                    @foreach($subjectDistribution['by_department'] ?? [] as $dept => $data)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300">{{ $dept }}</span>
                            <span class="text-[10px] text-slate-500 tabular-nums">{{ $data['total'] }}</span>
                        </div>
                        <div class="flex gap-0.5 h-1.5 rounded-full overflow-hidden">
                            @php
                                $deptTotal = $data['total'] > 0 ? $data['total'] : 1;
                                $mPct = round(($data['major'] / $deptTotal) * 100);
                                $minPct = 100 - $mPct;
                            @endphp
                            <div class="h-full bg-rose-500 transition-all" style="width: {{ $mPct }}%"></div>
                            <div class="h-full bg-orange-400 transition-all" style="width: {{ $minPct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Departments Overview --}}
            <div class="bg-white dark:bg-white/[0.03]
                        border border-slate-200 dark:border-white/10
                        rounded-2xl p-5 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1 h-4 bg-slate-400 dark:bg-slate-500 rounded-full"></span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Departments</h3>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($globalStats['departments'] ?? [] as $dept)
                    <div class="bg-slate-50 dark:bg-white/[0.03]
                                border border-slate-200 dark:border-white/[0.06]
                                rounded-xl p-2.5 text-center
                                hover:border-rose-400 dark:hover:border-rose-500/20 transition-colors cursor-default">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $dept }}</p>
                        <p class="text-[8px] text-slate-500 uppercase mt-0.5">Active</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>