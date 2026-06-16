@php
    $summary = $facultySummary ?? [
        'totalUnits'         => 0,
        'maxUnits'           => 21,
        'remainingUnits'     => 21,
        'overloadUnits'      => 0,
        'utilizationPercent' => 0,
        'majorCount'         => 0,
        'minorCount'         => 0,
        'majorUnits'         => 0,
        'minorUnits'         => 0,
        'majorPercent'       => 0,
        'minorPercent'       => 0,
        'averageMajorUnits'  => 0,
        'averageMinorUnits'  => 0,
        'scheduleCount'      => 0,
    ];
    $deptSummary = $departmentSummary ?? [
        'totalSubjects'    => 0,
        'assignedSubjects' => 0,
        'subjectsLeft'     => 0,
        'facultyProcessed' => 0,
        'activeDepartment' => 'All',
    ];
@endphp
<div
    x-data="{
        assignmentOpen: @entangle('scheduleModalOpen').live
    }"
    class="faculty-loading-shell h-[calc(100vh-7rem)] min-h-[36rem] overflow-hidden bg-slate-100 text-slate-900 dark:bg-[#06111f] dark:text-slate-100 md:h-[calc(100vh-8rem)]"
>
    <div class="flex h-full min-h-0 flex-col overflow-hidden lg:flex-row">
        {{-- ============================================================ --}}
        {{-- SIDEBAR — Faculty Roster --}}
        {{-- ============================================================ --}}
        <aside class="no-print flex max-h-[46vh] w-full shrink-0 flex-col border-b border-slate-200 bg-white shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-[#071526]/95 dark:shadow-black/30 lg:h-full lg:max-h-none lg:w-[23rem] lg:border-b-0 lg:border-r">
            {{-- Header --}}
            <div class="shrink-0 border-b border-slate-200 p-4 dark:border-white/10">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300">Faculty Roster</p>
                        <h2 class="mt-1 text-lg font-black tracking-tight text-slate-900 dark:text-white">Assignment Control</h2>
                    </div>
                    <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-sky-700 dark:border-sky-400/40 dark:bg-sky-400/10 dark:text-sky-200">
                        {{ $faculties->count() }} Faculty
                    </span>
                </div>
                <div class="space-y-3">
                    <div>
                        <label for="faculty-search" class="sr-only">Search faculty</label>
                        <input
                            id="faculty-search"
                            type="search"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Search faculty name or employee ID"
                            class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20 dark:border-white/10 dark:bg-white/8 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-sky-300 dark:focus:bg-white/12"
                        >
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @if(count($facultyDepartments) > 1)
                            <select wire:model.live="departmentFilter" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Departments</option>
                                @foreach($facultyDepartments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        @endif
                        <select wire:model.live="selectedScope" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300 {{ count($facultyDepartments) > 1 ? '' : 'col-span-2' }}">
                            <option value="all">All Scopes</option>
                            <option value="departmental">Departmental</option>
                            <option value="gened">Gen-Ed</option>
                            <option value="cross_department">Cross-Departmental</option>
                        </select>
                    </div>
                </div>
            </div>
            {{-- Faculty List --}}
            <div class="custom-scrollbar flex-1 space-y-2 overflow-y-auto p-3">
                @forelse($faculties as $faculty)
                    @php
                        $units            = (int) ($faculty->assigned_units ?? 0);
                        $max              = max(1, (int) ($faculty->max_units ?? 21));
                        $percent          = min(100, round(($units / $max) * 100));
                        $ringColor        = $percent >= 100 ? '#f43f5e' : ($percent >= 85 ? '#f59e0b' : '#38bdf8');
                        $circumference    = 2 * 3.14159 * 18;
                        $strokeDashOffset = $circumference - ($percent / 100) * $circumference;
                        $isSelected       = (int) $selectedFacultyId === (int) $faculty->id;
                        $scopeClass       = match($faculty->faculty_scope) {
                            \App\Models\Faculty::SCOPE_GENED            => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-300/30 dark:bg-sky-400/10 dark:text-sky-200',
                            \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-300/30 dark:bg-violet-400/10 dark:text-violet-200',
                            default                                      => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-200',
                        };
                    @endphp
                    <div
                        wire:key="faculty-card-{{ $faculty->id }}"
                        x-data="{ open: false }"
                        class="group relative rounded-xl border transition duration-200 {{ $isSelected
                            ? 'border-sky-300 bg-sky-50 shadow-sm dark:border-sky-300/70 dark:bg-sky-400/12 dark:shadow-lg dark:shadow-sky-950/40'
                            : 'border-slate-200 bg-white shadow-sm hover:border-sky-200 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.055] dark:hover:border-sky-300/50 dark:hover:bg-white/[0.08]' }}"
                    >
                        <div class="flex items-center gap-3 p-3">
                            <button
                                type="button"
                                wire:click="selectFaculty({{ $faculty->id }})"
                                class="flex min-w-0 flex-1 items-center gap-3 text-left"
                            >
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-blue-700 text-sm font-black text-white shadow-md">
                                    {{ strtoupper(substr($faculty->full_name, 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold tracking-tight text-slate-800 dark:text-slate-100">
                                        {{ $faculty->full_name }}
                                    </span>
                                    <span class="mt-0.5 block truncate text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ $faculty->employee_id }} / {{ $faculty->displayDepartment() }} / {{ $faculty->employment_type ?? 'Faculty' }}
                                    </span>
                                    <span class="mt-1 inline-flex rounded-md border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $scopeClass }}">
                                        {{ $faculty->scopeLabel() }} / {{ $faculty->canTeachMinorSubjects() ? 'Minor OK' : 'Major Only' }}
                                    </span>
                                </span>
                            </button>
                            <div class="flex shrink-0 items-center gap-2">
                                {{-- Unit ring --}}
                                <div class="relative h-12 w-12">
                                    <svg class="h-full w-full -rotate-90" viewBox="0 0 42 42" aria-hidden="true">
                                        <circle cx="21" cy="21" r="18" stroke="currentColor" stroke-width="3" fill="none" class="text-slate-200 dark:text-white/10"></circle>
                                        <circle
                                            cx="21"
                                            cy="21"
                                            r="18"
                                            stroke="{{ $ringColor }}"
                                            stroke-width="3"
                                            fill="none"
                                            stroke-dasharray="{{ $circumference }}"
                                            stroke-dashoffset="{{ $strokeDashOffset }}"
                                            stroke-linecap="round"
                                            class="transition-all duration-500"
                                        ></circle>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                                        <span class="text-[11px] font-black text-slate-800 dark:text-white">{{ $units }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500">/{{ $max }}</span>
                                    </div>
                                </div>
                                {{-- Actions dropdown --}}
                                <button
                                    type="button"
                                    x-on:click.stop="open = !open"
                                    class="flex h-9 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:border-sky-300/60 dark:hover:bg-sky-400/10 dark:hover:text-white"
                                    aria-label="Open faculty actions"
                                >
                                    <span class="flex flex-col gap-0.5">
                                        <span class="h-1 w-1 rounded-full bg-current"></span>
                                        <span class="h-1 w-1 rounded-full bg-current"></span>
                                        <span class="h-1 w-1 rounded-full bg-current"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div
                            x-cloak
                            x-show="open"
                            x-transition.origin.top.right
                            x-on:click.outside="open = false"
                            class="absolute right-3 top-14 z-40 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-sky-300/20 dark:bg-[#081a2d]/95 dark:shadow-2xl dark:shadow-black/40"
                        >
                            <button type="button" wire:click="openAssignmentPanel(null, {{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-400/15 dark:hover:text-white">
                                Assign Subject / Schedule
                            </button>
                            <button type="button" wire:click="showFacultyLoad({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-400/15 dark:hover:text-white">
                                View Faculty Load
                            </button>
                            <button type="button" wire:click="showFacultySchedule({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-400/15 dark:hover:text-white">
                                View Schedule
                            </button>
                            <button type="button" wire:click="showFacultyConflicts({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-400/15 dark:hover:text-white">
                                View Conflicts
                            </button>
                            <button type="button" x-on:click.stop="$wire.preparePrintLoad({{ $faculty->id }}).then(() => window.print()); open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-sky-50 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-sky-400/15 dark:hover:text-white">
                                Print Load Summary
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 text-center dark:border-white/10">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No faculty found</p>
                    </div>
                @endforelse
            </div>
        </aside>
        {{-- ============================================================ --}}
        {{-- MAIN PANEL --}}
        {{-- ============================================================ --}}
        <main class="print-area custom-scrollbar flex min-w-0 flex-1 flex-col overflow-y-auto bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.08),transparent_34%),linear-gradient(135deg,#f8fafc_0%,#eef6ff_48%,#e2e8f0_100%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.18),transparent_34%),linear-gradient(135deg,#081526_0%,#0b1220_48%,#050914_100%)]">
            @if($currentFaculty)
                {{-- Compact department overview mini-cards --}}
                <div class="summary-mini-bar no-print shrink-0 border-b border-slate-200 bg-white/70 px-4 py-2 backdrop-blur-xl dark:border-white/[0.07] dark:bg-white/[0.025] sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-[9px] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-500">Dept Overview</span>
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm shadow-sm dark:border-white/5 dark:bg-white/[0.04]">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total</span>
                            <span class="font-black text-slate-900 dark:text-white">{{ $deptSummary['totalSubjects'] }}</span>
                        </div>
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm shadow-sm dark:border-emerald-300/15 dark:bg-emerald-400/[0.07]">
                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-300/70">Assigned</span>
                            <span class="font-black text-emerald-700 dark:text-emerald-200">{{ $deptSummary['assignedSubjects'] }}</span>
                        </div>
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm shadow-sm dark:border-amber-300/15 dark:bg-amber-400/[0.07]">
                            <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-300/70">Left</span>
                            <span class="font-black text-amber-700 dark:text-amber-200">{{ $deptSummary['subjectsLeft'] }}</span>
                        </div>
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm shadow-sm dark:border-sky-300/15 dark:bg-sky-400/[0.07]">
                            <span class="text-[9px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-300/70">Processed</span>
                            <span class="font-black text-sky-700 dark:text-sky-200">{{ $deptSummary['facultyProcessed'] }}</span>
                        </div>
                        <span class="ml-auto text-[10px] font-medium text-slate-500 dark:text-slate-400">
                            Dept: {{ $deptSummary['activeDepartment'] }}
                        </span>
                    </div>
                </div>
                {{-- Faculty Header + Load Cards --}}
                <section class="border-b border-slate-200 bg-white/60 p-4 backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.045] sm:p-6">

                    {{-- ← Back navigation / breadcrumb --}}
                    <div class="no-print mb-4 flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$set('selectedFacultyId', null)"
                            class="group inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500 shadow-sm transition duration-150 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 active:scale-95 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400 dark:hover:border-sky-400/40 dark:hover:bg-sky-400/[0.08] dark:hover:text-sky-300"
                        >
                            {{-- Left chevron — slides left on hover --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Overview
                        </button>
                        {{-- Breadcrumb separator --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        {{-- Current faculty name (truncated) --}}
                        <span class="truncate text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">
                            {{ $currentFaculty->full_name }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-sky-700 dark:border-sky-300/40 dark:bg-sky-400/10 dark:text-sky-200">Active Faculty</span>
                                @php
                                    $currentScopeClass = match($currentFaculty->faculty_scope) {
                                        \App\Models\Faculty::SCOPE_GENED            => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-300/30 dark:bg-sky-400/10 dark:text-sky-200',
                                        \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-300/30 dark:bg-violet-400/10 dark:text-violet-200',
                                        default                                      => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-200',
                                    };
                                @endphp
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] {{ $currentScopeClass }}">{{ $currentFaculty->scopeLabel() }}</span>
                                <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-700 dark:border-sky-300/30 dark:bg-sky-400/10 dark:text-sky-200">{{ $currentFaculty->canTeachMinorSubjects() ? 'Minor OK' : 'Major Only' }}</span>
                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-200">{{ $currentFaculty->employment_type ?? 'Faculty' }}</span>
                            </div>
                            <h1 class="truncate text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                                {{ $currentFaculty->full_name }}
                            </h1>
                            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                                {{ $currentFaculty->employee_id }} / {{ $currentFaculty->displayDepartment() }}
                            </p>
                        </div>
                        <div class="no-print flex flex-wrap items-center gap-2">
                            {{-- Main Action Button - Enlarged --}}
                            <button type="button" wire:click="openAssignmentPanel(null, {{ $currentFaculty->id }})" 
                                class="flex-1 rounded-lg bg-sky-500 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-md transition hover:bg-sky-600 active:scale-95 dark:bg-sky-400 dark:text-slate-950 dark:shadow-lg dark:shadow-sky-950/30 dark:hover:bg-sky-300 min-w-[200px]">
                                Assign Subject / Schedule
                            </button>
                            
                        </div>
                    </div>
                    {{-- Specialized Faculty Load Cards --}}
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @if(!$currentFaculty->isGenEd())
                        <div class="load-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-amber-300/20 dark:bg-slate-900/40 dark:shadow-xl dark:shadow-amber-950/10 {{ !$currentFaculty->canTeachMinorSubjects() ? 'md:col-span-2' : '' }}">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-amber-600 dark:text-amber-300">Major Load</p>
                                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Departmental subject assignments</p>
                                </div>
                                <span class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-amber-700 dark:border-amber-300/25 dark:bg-amber-400/12 dark:text-amber-200">
                                    {{ $summary['majorCount'] }} subject(s)
                                </span>
                            </div>
                            <div class="flex items-end gap-2">
                                <span class="text-5xl font-black leading-none text-slate-900 dark:text-white">{{ $summary['majorUnits'] }}</span>
                                <span class="pb-1 text-sm font-medium text-slate-400 dark:text-amber-200/60">/ {{ $summary['maxUnits'] }} units</span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400 transition-all duration-700"
                                    style="width: {{ $summary['majorPercent'] }}%"
                                ></div>
                            </div>
                            <p class="mt-2 text-[12px] font-medium text-slate-500 dark:text-amber-200/60">{{ $summary['majorPercent'] }}% of max load</p>
                            @if($summary['majorCount'] > 0)
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Avg Units</p>
                                        <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['averageMajorUnits'] }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Offerings</p>
                                        <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['majorCount'] }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif
                        @if($currentFaculty->canTeachMinorSubjects())
                        <div class="load-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:bg-slate-900/40 dark:backdrop-blur-md {{ $currentFaculty->isGenEd() ? 'md:col-span-2 dark:border-sky-300/25 dark:shadow-sky-950/10' : 'dark:border-violet-300/20 dark:shadow-violet-950/10' }}">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    @if($currentFaculty->isGenEd())
                                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300">GenEd / Minor Load</p>
                                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Institution-wide subject assignments</p>
                                    @else
                                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-indigo-600 dark:text-violet-300">Minor Load</p>
                                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Cross-department minor subject assignments</p>
                                    @endif
                                </div>
                                <span class="rounded-xl border px-3 py-1.5 text-xs font-black uppercase tracking-wider {{ $currentFaculty->isGenEd()
                                    ? 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-300/25 dark:bg-sky-400/12 dark:text-sky-200'
                                    : 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-violet-300/25 dark:bg-violet-400/12 dark:text-violet-200' }}">
                                    {{ $summary['minorCount'] }} subject(s)
                                </span>
                            </div>
                            <div class="flex items-end gap-2">
                                <span class="text-5xl font-black leading-none text-slate-900 dark:text-white">{{ $summary['minorUnits'] }}</span>
                                <span class="pb-1 text-sm font-medium text-slate-400 {{ $currentFaculty->isGenEd() ? 'dark:text-sky-200/60' : 'dark:text-violet-200/60' }}">/ {{ $summary['maxUnits'] }} units</span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                <div
                                    class="h-full rounded-full {{ $currentFaculty->isGenEd() ? 'bg-gradient-to-r from-sky-500 to-blue-400' : 'bg-gradient-to-r from-indigo-500 to-violet-400' }} transition-all duration-700"
                                    style="width: {{ $summary['minorPercent'] }}%"
                                ></div>
                            </div>
                            <p class="mt-2 text-[12px] font-medium text-slate-500 {{ $currentFaculty->isGenEd() ? 'dark:text-sky-200/60' : 'dark:text-violet-200/60' }}">{{ $summary['minorPercent'] }}% of max load</p>
                            @if($summary['minorCount'] > 0)
                                <div class="mt-4 grid grid-cols-2 gap-2 {{ $currentFaculty->isGenEd() ? 'md:grid-cols-4' : '' }}">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Avg Units</p>
                                        <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['averageMinorUnits'] }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Offerings</p>
                                        <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['minorCount'] }}</p>
                                    </div>
                                    @if($currentFaculty->isGenEd())
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Total Units</p>
                                            <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['totalUnits'] }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/5 dark:bg-white/[0.04]">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-500">Utilization</p>
                                            <p class="mt-0.5 text-lg font-black text-slate-900 dark:text-white">{{ $summary['utilizationPercent'] }}%</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    {{-- Total load progress bar --}}
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Total Load Utilization</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-black text-slate-900 dark:text-white">{{ $summary['totalUnits'] }}</span>
                                <span class="text-sm font-medium text-slate-400">/ {{ $summary['maxUnits'] }} units</span>
                                <span class="ml-2 text-xs font-bold {{ $summary['utilizationPercent'] >= 100 ? 'text-red-600 dark:text-red-300' : ($summary['utilizationPercent'] >= 85 ? 'text-amber-600 dark:text-amber-300' : 'text-sky-600 dark:text-sky-300') }}">
                                    {{ $summary['utilizationPercent'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div
                                class="h-full rounded-full transition-all duration-700 {{ $summary['utilizationPercent'] >= 100 ? 'bg-gradient-to-r from-red-500 to-rose-400' : ($summary['utilizationPercent'] >= 85 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-sky-500 to-blue-400') }}"
                                style="width: {{ min(100, $summary['utilizationPercent']) }}%"
                            ></div>
                        </div>
                    </div>
                    @if($summary['overloadUnits'] > 0)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-400/40 dark:bg-red-500/12">
                            <p class="text-sm font-black uppercase tracking-wider text-red-700 dark:text-red-100">Overload Warning</p>
                            <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-100/80">
                                This faculty load is {{ $summary['overloadUnits'] }} unit(s) over the configured maximum of {{ $summary['maxUnits'] }} units.
                            </p>
                        </div>
                    @endif
                </section>
                {{-- Tab navigation --}}
                <section class="no-print shrink-0 border-b border-slate-200 bg-white/80 px-4 pt-4 backdrop-blur-xl dark:border-white/10 dark:bg-[#071526]/80 sm:px-6">
                    <div class="flex gap-5 overflow-x-auto">
                        <button type="button" wire:click="toggleTab('subjects')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'subjects' ? 'border-sky-500 text-sky-700 dark:border-sky-300 dark:text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200' }}">
                            Assigned Subjects ({{ $groupedAssignedSubjects->count() }})
                        </button>
                        <button type="button" wire:click="toggleTab('schedule')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'schedule' ? 'border-sky-500 text-sky-700 dark:border-sky-300 dark:text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200' }}">
                            Schedule Overview
                        </button>
                        <button type="button" wire:click="toggleTab('conflicts')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'conflicts' ? 'border-sky-500 text-sky-700 dark:border-sky-300 dark:text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200' }}">
                            Conflicts ({{ $facultyConflicts->count() }})
                        </button>
                        <button type="button" wire:click="toggleTab('summary')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'summary' ? 'border-sky-500 text-sky-700 dark:border-sky-300 dark:text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200' }}">
                            Load Summary
                        </button>
                    </div>
                </section>
                {{-- Tab content --}}
                <section class="flex-1 p-4 sm:p-6">
                    @if($activeTab === 'subjects')
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.045] dark:shadow-2xl dark:shadow-black/20 backdrop-blur-xl">
                            @if($groupedAssignedSubjects->count() > 0)
                                <div class="custom-scrollbar overflow-x-auto">
                                    <table class="min-w-[960px] w-full text-left text-xs">
                                        <thead class="bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                            <tr>
                                                <th class="px-4 py-3">Code</th>
                                                <th class="px-4 py-3">Subject</th>
                                                <th class="px-4 py-3">Group</th>
                                                <th class="px-4 py-3">Room</th>
                                                <th class="px-4 py-3">Schedule</th>
                                                <th class="px-4 py-3 text-center">Units</th>
                                                <th class="px-4 py-3 text-center">Type</th>
                                                <th class="px-4 py-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-white/10">
                                            @foreach($groupedAssignedSubjects as $assignedSubject)
                                                @php
                                                    $isUnscheduled = str_contains($assignedSubject['schedule'], 'Unscheduled Placeholder');
                                                @endphp
                                                <tr
                                                    wire:key="assigned-row-{{ $assignedSubject['first_schedule_id'] }}"
                                                    class="transition {{ $isUnscheduled ? 'bg-blue-50 hover:bg-blue-100/60 dark:bg-blue-400/5 dark:hover:bg-blue-400/10' : 'hover:bg-slate-50 dark:hover:bg-sky-400/5' }}"
                                                >
                                                    <td class="px-4 py-3">
                                                        <p class="font-semibold text-slate-700 dark:text-slate-300">{{ $assignedSubject['subject_code'] }}</p>
                                                        <p class="mt-1 text-[10px] font-semibold uppercase text-amber-600 dark:text-amber-200/80">{{ $assignedSubject['edp_code'] }}</p>
                                                    </td>
                                                    <td class="max-w-sm px-4 py-3">
                                                        <p class="line-clamp-2 font-semibold text-slate-800 dark:text-white">{{ $assignedSubject['description'] }}</p>
                                                    </td>
                                                    <td class="px-4 py-3 font-medium text-slate-600 dark:text-slate-300">
                                                        {{ $assignedSubject['group'] }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if($isUnscheduled || $assignedSubject['room'] === 'No room')
                                                            @if(!empty($assignedSubject['preferred_room_name']))
                                                                <span class="inline-block text-xs font-semibold text-violet-700 dark:text-violet-300">
                                                                    📌 {{ $assignedSubject['preferred_room_name'] }}
                                                                </span>
                                                                <p class="mt-0.5 text-[9px] font-medium text-violet-500 dark:text-violet-400">Pre-assigned</p>
                                                            @else
                                                                <span class="inline-block rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-semibold uppercase text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-200">
                                                                    TBA
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="inline-block text-xs font-semibold text-emerald-700 dark:text-emerald-200">
                                                                🏛️ {{ $assignedSubject['room'] }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if($isUnscheduled)
                                                            <div class="flex items-center gap-2">
                                                                <div class="h-2 w-2 animate-pulse rounded-full bg-amber-400"></div>
                                                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] font-black uppercase text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-200">
                                                                    ⏳ No Schedule
                                                                </span>
                                                            </div>
                                                            <p class="mt-1 text-[9px] font-medium text-amber-600 dark:text-amber-200/70">Awaiting auto-generation</p>
                                                        @else
                                                            <span class="font-medium leading-6 text-slate-600 dark:text-slate-300">{!! $assignedSubject['schedule'] !!}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-black text-slate-900 dark:text-white">
                                                        {{ $assignedSubject['units'] }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="rounded-full border px-2 py-1 text-[10px] font-black uppercase
                                                            {{ $assignedSubject['type'] === 'Major'
                                                                ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-100'
                                                                : 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-violet-300/30 dark:bg-violet-400/10 dark:text-violet-100' }}">
                                                            {{ $assignedSubject['type'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        @if($isUnscheduled)
                                                            <button
                                                                type="button"
                                                                wire:click="openAssignmentPanel({{ $assignedSubject['first_schedule_id'] }})"
                                                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-blue-700 transition hover:bg-blue-100 dark:border-blue-300/20 dark:bg-blue-400/10 dark:text-blue-100 dark:hover:bg-blue-400/20"
                                                            >
                                                                Configure
                                                            </button>
                                                        @else
                                                            <button
                                                                type="button"
                                                                wire:click="removeSubject({{ $assignedSubject['first_schedule_id'] }})"
                                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-red-700 transition hover:bg-red-100 dark:border-red-300/20 dark:bg-red-400/10 dark:text-red-100 dark:hover:bg-red-400/20"
                                                            >
                                                                Remove
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="flex h-64 flex-col items-center justify-center text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No assigned subjects</p>
                                    <button type="button" wire:click="openAssignmentPanel(null, {{ $currentFaculty->id }})" class="mt-4 rounded-lg bg-sky-500 px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-sky-600 dark:bg-sky-400 dark:text-slate-950 dark:hover:bg-sky-300">
                                        Open Assignment Panel
                                    </button>
                                </div>
                            @endif
                            {{-- PRINT BUTTON BELOW TABLE --}}
                            <div class="no-print border-t border-slate-200 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/[0.025] flex justify-end">
                                <button type="button" x-on:click="window.print()" 
                                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-100 active:scale-95 dark:border-white/20 dark:bg-white/10 dark:text-white dark:hover:bg-white/15 shadow-sm">
                                    Print Schedule
                                </button>
                            </div>
                        </div>
                    @endif
                    @if($activeTab === 'schedule')
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.045] dark:shadow-2xl dark:shadow-black/20">
                            <div class="border-b border-slate-200 px-6 py-4 dark:border-white/10">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Schedule Overview</h3>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-black uppercase text-slate-600 dark:border-white/10 dark:bg-white/8 dark:text-slate-300">
                                            {{ $scheduleGroups->count() }} day(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($scheduleGroups->count() > 0)
                                <div class="grid gap-4 p-6 xl:grid-cols-2">
                                    @forelse($scheduleGroups as $day => $daySchedules)
                                        <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-300/15 dark:bg-sky-400/8">
                                            <div class="mb-4 flex items-center justify-between">
                                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">{{ $day }}</h3>
                                                <span class="rounded-full border border-slate-200 bg-white px-2 py-1 text-[10px] font-black uppercase text-slate-600 dark:border-white/10 dark:bg-white/8 dark:text-slate-300">
                                                    {{ $daySchedules->count() }} class(es)
                                                </span>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach($daySchedules as $schedule)
                                                    @php
                                                        $hasValidSchedule = filled($schedule->day)
                                                            && filled($schedule->start_time)
                                                            && filled($schedule->end_time);
                                                    @endphp
                                                    <div class="rounded-lg border {{ $hasValidSchedule ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-300/20 dark:bg-emerald-400/8' : 'border-amber-200 bg-amber-50/70 dark:border-amber-300/20 dark:bg-amber-400/8' }} p-3">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="truncate text-sm font-black uppercase {{ $hasValidSchedule ? 'text-emerald-700 dark:text-emerald-100' : 'text-amber-700 dark:text-amber-100' }}">
                                                                    {{ $schedule->subject?->subject_code ?? 'N/A' }}
                                                                </p>
                                                                <p class="mt-1 line-clamp-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                                                    {{ $schedule->subject?->description ?? 'Untitled subject' }}
                                                                </p>
                                                            </div>
                                                            <span class="shrink-0 rounded-md bg-white px-2 py-1 text-[10px] font-black text-slate-700 shadow-sm dark:bg-white/10 dark:text-white">
                                                                {{ $schedule->subject?->units ?? 0 }}u
                                                            </span>
                                                        </div>
                                                        @if($hasValidSchedule)
                                                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}
                                                                <span class="text-slate-400 dark:text-slate-600">-</span>
                                                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                                                @if($schedule->room)
                                                                    <span class="text-slate-400 dark:text-slate-500">/</span>
                                                                    🏛️ {{ $schedule->room?->room_name ?? 'Unknown' }}
                                                                @else
                                                                    <span class="ml-2 italic text-amber-600 dark:text-amber-500">(TBA)</span>
                                                                @endif
                                                            </p>
                                                        @else
                                                            <div class="mt-2 flex items-center gap-2">
                                                                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></div>
                                                                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                                                    ⏳ Awaiting Auto-Scheduler
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 p-10 text-center xl:col-span-2 dark:border-white/10 dark:bg-white/[0.035]">
                                            <div class="flex flex-col items-center opacity-60">
                                                <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="mb-3 h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                                    No schedules generated yet
                                                </p>
                                                <p class="mt-2 text-[10px] font-medium text-slate-500">
                                                    Assignments are awaiting auto-generation
                                                </p>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <div class="flex h-64 flex-col items-center justify-center p-10 text-center">
                                    <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="mb-3 h-12 w-12 text-slate-400 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No schedule to display</p>
                                    <p class="mt-2 text-[10px] font-medium text-slate-500">Select a faculty member to view their assigned schedule</p>
                                </div>
                            @endif
                        </div>
                    @endif
                    @if($activeTab === 'conflicts')
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.045] dark:shadow-xl dark:shadow-black/20">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Conflict Review</h3>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase text-slate-600 dark:border-white/10 dark:bg-white/8 dark:text-slate-300">{{ $facultyConflicts->count() }} issue(s)</span>
                            </div>
                            @forelse($facultyConflicts as $conflict)
                                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-300/30 dark:bg-amber-400/10">
                                    <p class="text-sm font-black uppercase tracking-wider text-amber-700 dark:text-amber-100">{{ $conflict['title'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-amber-700/80 dark:text-amber-100/80">{{ $conflict['message'] }}</p>
                                </div>
                            @empty
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-300/20 dark:bg-emerald-400/10">
                                    <p class="text-sm font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-100">No conflicts detected</p>
                                    <p class="mt-1 text-sm font-medium text-emerald-700/80 dark:text-emerald-100/75">The current faculty load has no overlapping assigned schedules or availability warnings.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif
                    @if($activeTab === 'summary')
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.045] dark:shadow-xl dark:shadow-black/20">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Load Summary</h3>
                                <dl class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/8">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Units</dt>
                                        <dd class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $summary['totalUnits'] }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/8">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Max Units</dt>
                                        <dd class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $summary['maxUnits'] }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/8">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Schedules</dt>
                                        <dd class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $summary['scheduleCount'] }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/8">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Usage</dt>
                                        <dd class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $summary['utilizationPercent'] }}%</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.045] dark:shadow-xl dark:shadow-black/20">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Subject Type Mix</h3>
                                <div class="mt-5 space-y-3">
                                    @if(!$currentFaculty->isGenEd())
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-300/20 dark:bg-amber-400/10">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-black uppercase text-amber-700 dark:text-amber-100">Major</p>
                                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $summary['majorUnits'] }} units</p>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-amber-100 dark:bg-white/10">
                                            <div class="h-full rounded-full bg-amber-400 transition-all duration-500" style="width: {{ $summary['majorPercent'] }}%"></div>
                                        </div>
                                        <p class="mt-1.5 text-xs font-medium text-amber-700/75 dark:text-amber-100/75">{{ $summary['majorCount'] }} subject offering(s) &bull; {{ $summary['averageMajorUnits'] }} avg units</p>
                                    </div>
                                    @endif
                                    <div class="rounded-lg border {{ $currentFaculty->isGenEd() ? 'border-sky-200 bg-sky-50 dark:border-sky-300/20 dark:bg-sky-400/10' : 'border-indigo-200 bg-indigo-50 dark:border-violet-300/20 dark:bg-violet-400/10' }} p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-black uppercase {{ $currentFaculty->isGenEd() ? 'text-sky-700 dark:text-sky-100' : 'text-indigo-700 dark:text-violet-100' }}">
                                                {{ $currentFaculty->isGenEd() ? 'GenEd / Minor' : 'Minor' }}
                                            </p>
                                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $summary['minorUnits'] }} units</p>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                            <div class="h-full rounded-full {{ $currentFaculty->isGenEd() ? 'bg-sky-400' : 'bg-violet-400' }} transition-all duration-500" style="width: {{ $summary['minorPercent'] }}%"></div>
                                        </div>
                                        <p class="mt-1.5 text-xs font-medium {{ $currentFaculty->isGenEd() ? 'text-sky-700/75 dark:text-sky-100/75' : 'text-indigo-700/75 dark:text-violet-100/75' }}">{{ $summary['minorCount'] }} subject offering(s) &bull; {{ $summary['averageMinorUnits'] }} avg units</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            @else
                {{-- ======================================================== --}}
                {{-- STATE A — NO FACULTY SELECTED --}}
                {{-- ======================================================== --}}
                <div class="flex h-full min-h-0 flex-col gap-6 overflow-y-auto p-6 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.26em] text-sky-600 dark:text-sky-300">Faculty Loading & Scheduling</p>
                            <h1 class="mt-1 text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white sm:text-3xl">Department Overview</h1>
                            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                                Select a faculty member to begin assignment, or review global subject distributions.
                                @if($deptSummary['activeDepartment'] !== 'All')
                                    Showing data for <span class="font-black text-sky-700 dark:text-sky-300">{{ $deptSummary['activeDepartment'] }}</span>.
                                @endif
                            </p>
                        </div>
                        
                    </div>
                    {{-- Overview cards --}}
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:border-sky-200 hover:bg-slate-50 dark:border-white/10 dark:bg-slate-900/40 dark:shadow-2xl dark:shadow-black/30 dark:hover:border-sky-300/30 dark:hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-sky-400/8 blur-2xl transition duration-500 group-hover:bg-sky-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300/80">Total Subjects</p>
                                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-2 dark:border-sky-300/20 dark:bg-sky-400/10">
                                        <svg class="h-4 w-4 text-sky-600 dark:text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-slate-900 dark:text-white">{{ $deptSummary['totalSubjects'] ?? ($totalSubjects ?? 0) }}</p>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Total class offerings in the active scope</p>
                                <div class="mt-4 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                    <div class="h-full w-full rounded-full bg-gradient-to-r from-sky-500/60 to-sky-300/40"></div>
                                </div>
                            </div>
                        </div>
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:border-emerald-200 hover:bg-slate-50 dark:border-emerald-300/15 dark:bg-slate-900/40 dark:shadow-2xl dark:shadow-black/30 dark:hover:border-emerald-300/35 dark:hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-400/8 blur-2xl transition duration-500 group-hover:bg-emerald-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-300/80">Scheduled</p>
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-2 dark:border-emerald-300/20 dark:bg-emerald-400/10">
                                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-slate-900 dark:text-white">{{ $deptSummary['assignedSubjects'] ?? ($scheduledCount ?? 0) }}</p>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Classes fully assigned & scheduled</p>
                                @php $totalSubj = $deptSummary['totalSubjects'] ?? ($totalSubjects ?? 0); @endphp
                                @if($totalSubj > 0)
                                    @php $assignedPct = round((($deptSummary['assignedSubjects'] ?? ($scheduledCount ?? 0)) / $totalSubj) * 100); @endphp
                                    <div class="mt-4 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-300 transition-all duration-700" style="width: {{ $assignedPct }}%"></div>
                                    </div>
                                    <p class="mt-1.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-300/60">{{ $assignedPct }}% of total</p>
                                @else
                                    <div class="mt-4 h-1 rounded-full bg-slate-100 dark:bg-white/10"></div>
                                @endif
                            </div>
                        </div>
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:border-red-200 hover:bg-slate-50 dark:border-red-300/15 dark:bg-slate-900/40 dark:shadow-2xl dark:shadow-black/30 dark:hover:border-red-300/35 dark:hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-red-400/8 blur-2xl transition duration-500 group-hover:bg-red-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-red-600 dark:text-red-300/80">Unscheduled Left</p>
                                    <div class="rounded-lg border border-red-200 bg-red-50 p-2 dark:border-red-300/20 dark:bg-red-400/10">
                                        <svg class="h-4 w-4 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                        </svg>
                                    </div>
                                </div>
                                @php $unscheduled = $deptSummary['subjectsLeft'] ?? ($unscheduledCount ?? 0); @endphp
                                <p class="text-6xl font-black leading-none {{ $unscheduled > 0 ? 'text-red-700 dark:text-red-200' : 'text-slate-900 dark:text-white' }}">{{ $unscheduled }}</p>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Remaining unassigned class blocks</p>
                                @if($totalSubj > 0)
                                    @php $leftPct = round(($unscheduled / $totalSubj) * 100); @endphp
                                    <div class="mt-4 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-red-500 to-rose-400 transition-all duration-700" style="width: {{ $leftPct }}%"></div>
                                    </div>
                                    <p class="mt-1.5 text-[11px] font-medium text-red-600 dark:text-red-300/60">{{ $leftPct }}% unassigned</p>
                                @else
                                    <div class="mt-4 h-1 rounded-full bg-slate-100 dark:bg-white/10"></div>
                                @endif
                            </div>
                        </div>
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-slate-50 dark:border-blue-300/15 dark:bg-slate-900/40 dark:shadow-2xl dark:shadow-black/30 dark:hover:border-blue-300/35 dark:hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-blue-400/8 blur-2xl transition duration-500 group-hover:bg-blue-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600 dark:text-blue-300/80">Pre-Assigned</p>
                                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-2 dark:border-blue-300/20 dark:bg-blue-400/10">
                                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-slate-900 dark:text-white">{{ $deptSummary['facultyProcessed'] ?? ($preAssignedCount ?? 0) }}</p>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">Assigned to instructor but awaiting auto-scheduler</p>
                                <div class="mt-4 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                    <div class="h-full w-full rounded-full bg-gradient-to-r from-blue-500/60 to-blue-300/40"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Global Subject Master List --}}
                    <div class="mt-4 flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.035] dark:shadow-2xl dark:shadow-black/20">
                        <div class="grid gap-4 border-b border-slate-200 bg-slate-50/70 p-5 sm:grid-cols-4 dark:border-white/10 dark:bg-white/[0.025]">
                            <div>
                                <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Department</label>
                                <select wire:model.live="filterDepartment" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                    <option value="">All Departments</option>
                                    @foreach($departments ?? ['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Major</label>
                                <select wire:model.live="filterMajor" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                    <option value="">All Majors</option>
                                    @foreach($majors ?? [] as $major)
                                        <option value="{{ $major }}">{{ $major }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Year Level</label>
                                <select wire:model.live="filterYearLevel" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                    <option value="">All Years</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Section</label>
                                <select wire:model.live="filterSection" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                    <option value="">All Sections</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>
                        <div class="custom-scrollbar overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <thead class="bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                    <tr>
                                        <th class="min-w-[120px] px-6 py-4">Subject Code</th>
                                        <th class="min-w-[200px] px-6 py-4">Description</th>
                                        <th class="min-w-[80px] px-6 py-4">Section</th>
                                        <th class="min-w-[150px] px-6 py-4">Faculty</th>
                                        <th class="min-w-[150px] px-6 py-4">Schedule</th>
                                        <th class="min-w-[120px] px-6 py-4">Room</th>
                                        <th class="min-w-[100px] px-6 py-4">Status</th>
                                        <th class="min-w-[120px] px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-white/10">
                                    @if(isset($subjects))
                                        @forelse($subjects as $subject)
                                            @php
                                                $schedule = $subject->schedules()
                                                    ->where(function($q) {
                                                        $q->whereNotNull('day')
                                                          ->whereNotNull('start_time')
                                                          ->whereNotNull('end_time')
                                                          ->whereNotNull('room_id');
                                                    })
                                                    ->first();
                                                $preAssignment = $subject->schedules()
                                                    ->whereNotNull('faculty_id')
                                                    ->where(function($q) {
                                                        $q->whereNull('day')
                                                          ->orWhereNull('start_time')
                                                          ->orWhereNull('end_time')
                                                          ->orWhereNull('room_id');
                                                    })
                                                    ->first();
                                                $isFullyScheduled = filled($schedule);
                                                $isPreAssigned    = filled($preAssignment) && !$isFullyScheduled;
                                                $rowBgClass = 'hover:bg-slate-50';
                                                if($isPreAssigned) {
                                                    $rowBgClass = 'bg-blue-50 hover:bg-blue-100/60 dark:bg-blue-400/5 dark:hover:bg-blue-400/10';
                                                } elseif($isFullyScheduled) {
                                                    $rowBgClass = 'hover:bg-emerald-50 dark:hover:bg-emerald-400/5';
                                                }
                                            @endphp
                                            <tr class="transition {{ $rowBgClass }}">
                                                <td class="px-6 py-4">
                                                    <p class="font-semibold text-slate-700 dark:text-slate-300">{{ $subject->subject_code }}</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="max-w-sm line-clamp-2 font-semibold text-slate-800 dark:text-white">{{ $subject->description }}</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-black uppercase text-slate-600 dark:border-white/10 dark:bg-white/8 dark:text-slate-300">
                                                        {{ $subject->section ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if($preAssignment && $preAssignment->faculty_id)
                                                        @php $faculty = $preAssignment->faculty; @endphp
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex h-7 w-7 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-[10px] font-black text-blue-700 shadow-sm dark:border-blue-400/30 dark:bg-blue-500/20 dark:text-blue-300">
                                                                {{ substr($faculty?->full_name ?? 'F', 0, 1) }}
                                                            </div>
                                                            <div>
                                                                <div class="max-w-[130px] truncate text-xs font-bold text-slate-800 dark:text-white">
                                                                    {{ $faculty?->full_name ?? 'Assigned' }}
                                                                </div>
                                                                <div class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-300">Pre-Assigned</div>
                                                            </div>
                                                        </div>
                                                    @elseif($schedule && $schedule->faculty_id)
                                                        @php $faculty = $schedule->faculty; @endphp
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex h-7 w-7 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-[10px] font-black text-emerald-700 shadow-sm dark:border-emerald-400/30 dark:bg-emerald-500/20 dark:text-emerald-300">
                                                                {{ substr($faculty?->full_name ?? 'F', 0, 1) }}
                                                            </div>
                                                            <div>
                                                                <div class="max-w-[130px] truncate text-xs font-bold text-slate-800 dark:text-white">
                                                                    {{ $faculty?->full_name ?? 'Unassigned' }}
                                                                </div>
                                                                <div class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">Assigned</div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex h-7 w-7 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-[10px] font-black text-slate-500 shadow-sm dark:border-slate-400/30 dark:bg-slate-500/20 dark:text-slate-400">?</div>
                                                            <span class="text-xs font-medium italic text-slate-500 dark:text-slate-400">Unassigned</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if($isPreAssigned)
                                                        <div class="flex items-center gap-2">
                                                            <div class="h-2 w-2 animate-pulse rounded-full bg-amber-400"></div>
                                                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] font-black uppercase text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-200">
                                                                ⏳ No Schedule
                                                            </span>
                                                        </div>
                                                        <div class="mt-1 text-[9px] font-medium text-amber-600 dark:text-amber-200/70">Awaiting auto-generation</div>
                                                    @elseif($isFullyScheduled)
                                                        <div class="flex flex-col gap-1">
                                                            <div class="text-xs font-bold text-slate-800 dark:text-white">{{ $schedule->day ?? 'N/A' }}</div>
                                                            <div class="text-[11px] font-medium text-slate-600 dark:text-slate-300">
                                                                {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') : '--:-- --' }}
                                                                <span class="text-slate-400 dark:text-slate-500">to</span>
                                                                {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') : '--:-- --' }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="rounded-full border border-slate-300 bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase text-slate-500 dark:border-slate-400/30 dark:bg-slate-500/10 dark:text-slate-400">
                                                            Not Scheduled
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if($schedule && $schedule->room_id)
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-semibold text-slate-700 dark:text-white">
                                                                🏛️ {{ $schedule->room?->room_name ?? 'Unknown' }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-xs font-medium italic text-slate-500">
                                                            {{ $isPreAssigned ? 'Pending' : 'N/A' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if($isPreAssigned)
                                                        <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-blue-700 dark:border-blue-300/30 dark:bg-blue-400/10 dark:text-blue-200">
                                                            👤 Pre-Assigned
                                                        </span>
                                                    @elseif($isFullyScheduled)
                                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-200">
                                                            ✓ Scheduled
                                                        </span>
                                                    @else
                                                        <span class="rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-red-700 dark:border-red-300/30 dark:bg-red-400/10 dark:text-red-200">
                                                            ✕ Unscheduled
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="space-x-2 px-6 py-4 text-right">
                                                    @if(!$isFullyScheduled)
                                                        <button wire:click="editSubject({{ $subject->id }})"
                                                                class="text-[10px] font-black uppercase tracking-wider text-sky-600 transition hover:text-sky-800 hover:underline dark:text-sky-300 dark:hover:text-sky-100">
                                                            {{ $isPreAssigned ? 'Assign Faculty' : 'Edit' }}
                                                        </button>
                                                    @endif
                                                    @if($isFullyScheduled)
                                                        <button wire:click="viewSchedule({{ $subject->id }})"
                                                                class="text-[10px] font-black uppercase tracking-wider text-emerald-600 transition hover:text-emerald-800 hover:underline dark:text-emerald-300 dark:hover:text-emerald-100">
                                                            View
                                                        </button>
                                                        <button wire:click="editSchedule({{ $subject->id }})"
                                                                class="text-[10px] font-black uppercase tracking-wider text-amber-600 transition hover:text-amber-800 hover:underline dark:text-amber-300 dark:hover:text-amber-100">
                                                            Edit
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="px-10 py-16 text-center">
                                                    <div class="flex flex-col items-center justify-center opacity-60">
                                                        <div class="mb-3 rounded-full bg-slate-100 p-3 dark:bg-white/5">
                                                            <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </div>
                                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">
                                                            No subjects found matching your filters.
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        @if(isset($subjects) && $subjects->count() > 0)
                            <div class="border-t border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                {{ $subjects->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>
                    {{-- Data Legend --}}
                    <div class="shrink-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.03]">
                        <h3 class="mb-3 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">📖 Schedule Status Legend</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="flex items-start gap-2">
                                <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[9px] font-black uppercase text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-200">✓ Scheduled</span>
                                <p class="mt-1 text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">Assigned to a faculty member with an explicit time slot and room.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="shrink-0 rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-[9px] font-black uppercase text-blue-700 dark:border-blue-300/30 dark:bg-blue-400/10 dark:text-blue-200">👤 Pre-Assigned</span>
                                <p class="mt-1 text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">Has an instructor, but waits on auto-scheduler for day, time, and room.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="shrink-0 rounded-full border border-red-200 bg-red-50 px-2 py-1 text-[9px] font-black uppercase text-red-700 dark:border-red-300/30 dark:bg-red-400/10 dark:text-red-200">✕ Unscheduled</span>
                                <p class="mt-1 text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">A floating block. No faculty assignment or schedule created yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </main>
    </div>
    {{-- ============================================================ --}}
    {{-- ASSIGNMENT SLIDE-OVER PANEL --}}
    {{-- ============================================================ --}}
    <div
        x-cloak
        x-show="assignmentOpen"
        class="fixed inset-0 z-[80] overflow-hidden"
        aria-modal="true"
        role="dialog"
    >
        <div
            x-show="assignmentOpen"
            x-transition.opacity
            x-on:click="$wire.closeAssignmentPanel()"
            class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
        ></div>
        <aside
            x-show="assignmentOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-60"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-60"
            class="absolute right-0 top-0 flex h-full w-full max-w-3xl flex-col border-l border-slate-200 bg-white shadow-2xl backdrop-blur-2xl dark:border-sky-300/20 dark:bg-[#071526]/95 dark:shadow-black/50"
        >
            @if($currentFaculty)
                <div class="shrink-0 border-b border-slate-200 p-5 dark:border-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-sky-600 dark:text-sky-300">Assignment Panel</p>
                            <h2 class="mt-1 truncate text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">
                                {{ $currentFaculty->full_name }}
                            </h2>
                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ $currentFaculty->displayDepartment() }} &bull; {{ $summary['totalUnits'] }}/{{ $summary['maxUnits'] }} units used
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeAssignmentPanel"
                            class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                        >
                            Close
                        </button>
                    </div>
                </div>
                <div class="shrink-0 border-b border-slate-200 p-4 dark:border-white/10">
                    <div class="grid gap-3">
                        <input
                            type="search"
                            wire:model.live.debounce.250ms="subjectSearch"
                            placeholder="Search code, title, EDP, department, major, or section"
                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-400/20 dark:border-sky-300/20 dark:bg-white/8 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-sky-300 dark:focus:bg-white/12"
                        >
                        <div class="grid gap-2 sm:grid-cols-5">
                            <select wire:model.live="subjectDepartmentFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Dept</option>
                                @foreach($scheduleDepartments ?? [] as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="subjectMajorFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Majors</option>
                                @foreach($majors ?? [] as $major)
                                    <option value="{{ $major }}">{{ $major }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="subjectYearLevelFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Years</option>
                                @foreach($yearLevels ?? [] as $level)
                                    <option value="{{ $level }}">Year {{ $level }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="subjectSectionFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Sections</option>
                                @foreach($sections ?? [] as $section)
                                    <option value="{{ $section }}">{{ $section }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="subjectTypeFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 dark:border-white/10 dark:bg-[#0b1b30] dark:text-slate-200 dark:focus:border-sky-300">
                                <option value="all">All Types</option>
                                @foreach($subjectTypes ?? [] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/10 dark:bg-white/[0.055]">
                            <span>
                                <span class="block text-xs font-black uppercase tracking-wider text-slate-800 dark:text-white">Show unassigned only</span>
                                <span class="block text-[11px] font-medium text-slate-500 dark:text-slate-400">Turn off to review assigned schedules and shared subject distribution.</span>
                            </span>
                            <input type="checkbox" wire:model.live="showUnassignedOnly" class="h-5 w-5 rounded border-slate-300 bg-white text-sky-500 focus:ring-sky-300 dark:border-white/20 dark:bg-[#0b1b30] dark:text-sky-400">
                        </label>
                    </div>
                </div>
                <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto p-4">
                    @forelse($groupedAvailableSubjects as $group)
                        @php
                            $assignedToCurrent = $group['faculty_id'] && (int) $group['faculty_id'] === (int) $currentFaculty->id;
                            $assignedElsewhere = $group['faculty_id'] && ! $assignedToCurrent;
                            $isFinalized       = $group['is_finalized'];
                        @endphp
                        <article wire:key="assignable-group-{{ $group['subject_id'] }}-{{ $group['section'] }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.055] dark:hover:border-sky-300/40 dark:hover:bg-white/[0.075]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-100">{{ $group['edp_code'] }}</span>
                                        <span class="rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-700 dark:border-sky-300/30 dark:bg-sky-400/10 dark:text-sky-100">{{ $group['subject_code'] }}</span>
                                        <span class="rounded-md border px-2 py-1 text-[10px] font-black uppercase tracking-wider {{ $group['type'] === 'Major'
                                            ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-100'
                                            : 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-violet-300/30 dark:bg-violet-400/10 dark:text-violet-100' }}">{{ $group['type'] }}</span>
                                        @if($assignedToCurrent)
                                            <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-300/30 dark:bg-emerald-400/10 dark:text-emerald-100">Assigned here</span>
                                        @elseif($assignedElsewhere)
                                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600 dark:border-slate-300/20 dark:bg-slate-400/10 dark:text-slate-300">Assigned</span>
                                        @else
                                            <span class="rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-700 dark:border-sky-300/20 dark:bg-sky-400/10 dark:text-sky-100">Unassigned</span>
                                        @endif
                                        @if(count($group['schedule_ids']) > 1)
                                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600 dark:border-white/10 dark:bg-white/8 dark:text-slate-300">{{ count($group['schedule_ids']) }} days</span>
                                        @endif
                                    </div>
                                    <h3 class="mt-3 line-clamp-2 text-base font-black text-slate-900 dark:text-white">{{ $group['description'] }}</h3>
                                    <div class="mt-3 grid gap-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 sm:grid-cols-2">
                                        <p>Department: <span class="text-slate-700 dark:text-slate-200">{{ $group['department'] }}</span></p>
                                        <p>Major: <span class="text-slate-700 dark:text-slate-200">{{ $group['major'] }}</span></p>
                                        <p>Year / Section: <span class="text-slate-700 dark:text-slate-200">Y{{ $group['year'] }} / {{ $group['section'] }}</span></p>
                                        <p>Room:
                                            @if(!empty($group['preferred_room_name']))
                                                <span class="inline-flex items-center gap-1 font-semibold text-violet-700 dark:text-violet-300">
                                                    📌 {{ $group['preferred_room_name'] }}
                                                    <span class="rounded-full border border-violet-200 bg-violet-50 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-600 dark:border-violet-300/30 dark:bg-violet-400/10 dark:text-violet-300">Pre-assigned</span>
                                                </span>
                                            @elseif($group['room'] !== 'No room')
                                                <span class="text-slate-700 dark:text-slate-200">🏛️ {{ $group['room'] }}</span>
                                            @else
                                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-700 dark:border-amber-300/30 dark:bg-amber-400/10 dark:text-amber-200">TBA</span>
                                            @endif
                                        </p>
                                        <p>Day: <span class="text-slate-700 dark:text-slate-200">{{ $group['days'] }}</span></p>
                                        <p>Time: <span class="text-slate-700 dark:text-slate-200">{{ $group['time'] }}</span></p>
                                        <p>Units: <span class="text-slate-700 dark:text-slate-200">{{ $group['units'] }}</span></p>
                                        <p>Status: <span class="text-slate-700 dark:text-slate-200">{{ $group['faculty_name'] ?? 'Unassigned' }}</span></p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2 sm:w-40">
                                    @if($assignedToCurrent)
                                        <button
                                            type="button"
                                            wire:click="removeSubjectGroup({{ json_encode($group['schedule_ids']) }})"
                                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-black uppercase tracking-widest text-red-700 transition hover:bg-red-100 dark:border-red-300/20 dark:bg-red-400/10 dark:text-red-100 dark:hover:bg-red-400/20"
                                        >
                                            Remove
                                        </button>
                                    @elseif($assignedElsewhere)
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-500">
                                            Assigned
                                        </button>
                                    @elseif($isFinalized)
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-500">
                                            Finalized
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            @if($group['is_unscheduled'] && !$group['first_schedule_id'])
                                                wire:click="initializeRawSubjectAssignment({{ $group['subject_id'] }}, '{{ $group['section'] }}')"
                                            @else
                                                wire:click="assignSubject({{ $group['first_schedule_id'] }})"
                                            @endif
                                            wire:loading.attr="disabled"
                                            wire:target="assignSubjectGroup"
                                            class="rounded-lg bg-sky-500 px-3 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-sky-600 active:scale-95 disabled:cursor-wait disabled:opacity-70 dark:bg-sky-400 dark:text-slate-950 dark:shadow-lg dark:shadow-sky-950/30 dark:hover:bg-sky-300"
                                        >
                                            Assign
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 text-center dark:border-white/10">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No schedules match the current filters</p>
                            <p class="mt-2 text-sm font-medium text-slate-500">Try clearing search terms or disabling the unassigned-only filter.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </aside>
    </div>
    {{-- ============================================================ --}}
    {{-- CONFLICT / OVERRIDE MODAL --}}
    {{-- ============================================================ --}}
    @if($conflictModalOpen)
        <div class="fixed inset-0 z-[100] flex items-stretch justify-end bg-slate-950/75 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="custom-scrollbar h-full w-full max-w-xl overflow-y-auto border-l border-amber-200 bg-white p-5 shadow-2xl dark:border-amber-300/30 dark:bg-[#081a2d] dark:shadow-black/50">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-amber-600 dark:text-amber-200">Assignment Review</p>
                <h2 class="mt-2 text-2xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Faculty Conflict Detected</h2>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                    This schedule cannot be assigned until the conflict is resolved. Time, duplicate subject, load limit, and eligibility conflicts are blocked.
                </p>
                <div class="mt-4 space-y-3">
                    @foreach($pendingAssignmentWarnings as $warning)
                        @php $details = $warning['details'] ?? []; @endphp
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-300/25 dark:bg-amber-400/10">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black uppercase tracking-wider text-amber-700 dark:text-amber-100">{{ $warning['title'] ?? 'Assignment Warning' }}</p>
                                    <p class="mt-1 text-sm font-medium text-amber-700/80 dark:text-amber-100/80">{{ $warning['message'] ?? 'Review this assignment before continuing.' }}</p>
                                </div>
                                <span class="rounded-md border border-amber-200 bg-white px-2 py-1 text-[9px] font-black uppercase tracking-wider text-amber-700 dark:border-amber-200/30 dark:bg-amber-200/10 dark:text-amber-100">
                                    {{ str_replace('_', ' ', $warning['type'] ?? 'conflict') }}
                                </span>
                            </div>
                            @if(!empty($details))
                                <div class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 sm:grid-cols-2 dark:border-white/10 dark:bg-slate-950/25 dark:text-slate-400">
                                    @if(!empty($details['faculty_name']))
                                        <p>Faculty: <span class="text-slate-800 dark:text-slate-100">{{ $details['faculty_name'] }}</span></p>
                                    @endif
                                    @if(!empty($details['conflicting_subject']))
                                        <p>Subject: <span class="text-slate-800 dark:text-slate-100">{{ $details['conflicting_subject'] }}</span></p>
                                    @endif
                                    @if(!empty($details['day']))
                                        <p>Day: <span class="text-slate-800 dark:text-slate-100">{{ $details['day'] }}</span></p>
                                    @endif
                                    @if(!empty($details['time']))
                                        <p>Time: <span class="text-slate-800 dark:text-slate-100">{{ $details['time'] }}</span></p>
                                    @endif
                                    @if(!empty($details['room']))
                                        <p>Room: <span class="text-slate-800 dark:text-slate-100">{{ $details['room'] }}</span></p>
                                    @endif
                                    @if(!empty($details['reason']))
                                        <p class="sm:col-span-2">Reason: <span class="normal-case tracking-normal text-slate-800 dark:text-slate-100">{{ $details['reason'] }}</span></p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if(!empty($assignmentRecommendations['faculty']) || !empty($assignmentRecommendations['slots']) || !empty($assignmentRecommendations['rooms']))
                    <div class="mt-5 grid gap-3">
                        @if(!empty($assignmentRecommendations['faculty']))
                            <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-300/25 dark:bg-sky-400/10">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-100">Suggested Faculty</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['faculty'] as $suggestion)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 dark:border-white/10 dark:bg-white/8">
                                            <span class="text-[10px] font-black uppercase text-slate-800 dark:text-slate-100">
                                                {{ $suggestion['name'] }} / {{ $suggestion['scope'] }} / {{ $suggestion['remaining_units'] }}u open
                                            </span>
                                            <button type="button" wire:click="useFacultySuggestion({{ (int) $suggestion['id'] }})" class="rounded-md bg-sky-500 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-white transition hover:bg-sky-600 dark:bg-sky-300 dark:text-slate-950 dark:hover:bg-sky-200">
                                                Use Suggestion
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($assignmentRecommendations['slots']))
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-300/25 dark:bg-emerald-400/10">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-100">Suggested Slots</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['slots'] as $suggestion)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 dark:border-white/10 dark:bg-white/8">
                                            <span class="text-[10px] font-black uppercase text-slate-800 dark:text-slate-100">{{ $suggestion['label'] }}</span>
                                            <button type="button" wire:click="useSlotSuggestion(@js($suggestion['day']), @js($suggestion['start_time']), @js($suggestion['end_time']))" class="rounded-md bg-emerald-500 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-white transition hover:bg-emerald-600 dark:bg-emerald-300 dark:text-slate-950 dark:hover:bg-emerald-200">
                                                Use Suggestion
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($assignmentRecommendations['rooms']))
                            <div class="rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-300/25 dark:bg-violet-400/10">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-700 dark:text-violet-100">Suggested Rooms</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['rooms'] as $room)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 dark:border-white/10 dark:bg-white/8">
                                            <span class="text-[10px] font-black uppercase text-slate-800 dark:text-slate-100">{{ $room['name'] }} / {{ $room['type'] }}</span>
                                            <button type="button" wire:click="useRoomSuggestion({{ (int) $room['id'] }})" class="rounded-md bg-violet-500 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-white transition hover:bg-violet-600 dark:bg-violet-300 dark:text-slate-950 dark:hover:bg-violet-200">
                                                Use Suggestion
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="mt-5 flex gap-3">
                    <button type="button" wire:click="cancelAssignmentOverride" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/8 dark:text-white dark:hover:bg-white/12">
                        Cancel
                    </button>
                    @if($canOverrideWarnings)
                        <button
                            type="button"
                            @if(!empty($pendingRawSubjectData))
                                wire:click="approveRawSubjectAssignmentOverride"
                            @else
                                wire:click="confirmAssignmentOverride"
                            @endif
                            class="rounded-lg bg-amber-500 px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-amber-600 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300"
                        >
                            Override and Assign
                        </button>
                    @else
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            Resolve conflict to assign
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    {{-- ============================================================ --}}
    {{-- TOAST NOTIFICATIONS --}}
    {{-- ============================================================ --}}
    {{-- ============================================================ --}}
    {{-- STYLES --}}
    {{-- ============================================================ --}}
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(56,189,248,0.28); border-radius: 999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(56,189,248,0.46); }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .dept-card {
            animation: cardSlideUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .dept-card:nth-child(1) { animation-delay: 0.04s; }
        .dept-card:nth-child(2) { animation-delay: 0.10s; }
        .dept-card:nth-child(3) { animation-delay: 0.16s; }
        .dept-card:nth-child(4) { animation-delay: 0.22s; }
        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .summary-mini-bar {
            animation: miniBarSlide 0.3s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes miniBarSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .load-card {
            animation: cardSlideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .load-card:nth-child(1) { animation-delay: 0.06s; }
        .load-card:nth-child(2) { animation-delay: 0.14s; }
        @media print {
            .no-print, aside, [role='dialog'] { display: none !important; }
            .print-area {
                display: block !important;
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                background: white !important;
                color: #0f172a !important;
            }
            /* Print stylesheet - Show only schedule table */
            body {
                background: white !important;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 0;
                margin: 0;
            }
            .faculty-loading-shell {
                display: flex;
                flex-direction: column;
                height: auto !important;
                background: white !important;
                color: #0f172a !important;
                padding: 40px;
            }
            /* Hide everything except header and subjects table */
            .faculty-loading-shell > div > section:not(:has(table)) {
                display: none !important;
            }
            .faculty-loading-shell > div > section:nth-of-type(1) {
                display: block !important;
                page-break-inside: avoid;
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
            }
            /* Hide tabs and other UI elements */
            [role='tablist'], .no-print, [x-on\:click] { display: none !important; }
            /* Format table for print */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 30px;
                page-break-inside: avoid;
            }
            thead {
                background: #f3f4f6 !important;
                color: #1f2937 !important;
                font-weight: 600 !important;
                border-top: 2px solid #1f2937 !important;
                border-bottom: 2px solid #1f2937 !important;
            }
            th {
                padding: 12px 8px !important;
                text-align: left !important;
                font-size: 11px !important;
                font-weight: 700 !important;
            }
            td {
                padding: 10px 8px !important;
                border-bottom: 1px solid #d1d5db !important;
                font-size: 10px !important;
            }
            tbody tr {
                page-break-inside: avoid;
            }
            /* Hide action buttons in print */
            td:last-child { display: none !important; }
            th:last-child { display: none !important; }
            /* Print header styling */
            h1, h2 {
                color: #1f2937 !important;
                margin: 0 0 5px 0 !important;
                font-size: 28px !important;
                font-weight: 700 !important;
            }
            .rounded-full, .shadow-sm, .shadow-md, [class*='shadow'] {
                box-shadow: none !important;
            }
            /* Keep only essential columns */
            .custom-scrollbar {
                overflow: visible !important;
            }
            /* Print-friendly colors */
            .bg-blue-50, .bg-amber-50, .bg-indigo-50 {
                background: white !important;
                color: #000 !important;
            }
            .text-sky-700, .text-amber-700, .text-indigo-700, .text-emerald-700 {
                color: #1f2937 !important;
            }
            /* Prevent page breaks in middle of content */
            .rounded-xl, .border {
                page-break-inside: avoid;
                border-color: #d1d5db !important;
            }
        }
        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    </style>
</div>