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
        assignmentOpen: @entangle('scheduleModalOpen').live,
        toasts: [],
        addToast(toast) {
            const id = Date.now() + Math.random();
            const item = {
                id,
                type: toast.type || 'success',
                message: toast.message || '',
                timeout: null
            };
            this.toasts.push(item);
            item.timeout = setTimeout(() => this.removeToast(id), 4200);
        },
        removeToast(id) {
            const toast = this.toasts.find((item) => item.id === id);
            if (toast && toast.timeout) {
                clearTimeout(toast.timeout);
            }
            this.toasts = this.toasts.filter((item) => item.id !== id);
        },
        toastClasses(type) {
            return {
                success: 'border-emerald-400/60 bg-emerald-500 text-white shadow-emerald-950/30',
                warning: 'border-amber-300/70 bg-amber-500 text-slate-950 shadow-amber-950/20',
                error:   'border-red-400/70 bg-red-600 text-white shadow-red-950/30'
            }[type] || 'border-sky-400/60 bg-slate-900 text-white shadow-slate-950/30';
        },
        toastIcon(type) {
            return {
                success: 'OK',
                warning: '!',
                error:   '!'
            }[type] || 'i';
        }
    }"
    x-on:toast.window="addToast($event.detail)"
    class="faculty-loading-shell h-[calc(100vh-7rem)] min-h-[36rem] overflow-hidden bg-slate-100 text-slate-950 dark:bg-[#06111f] dark:text-slate-100 md:h-[calc(100vh-8rem)]">

    <div class="flex h-full min-h-0 flex-col overflow-hidden lg:flex-row">

        {{-- ============================================================ --}}
        {{-- SIDEBAR — Faculty Roster                                      --}}
        {{-- ============================================================ --}}
        <aside class="no-print flex max-h-[46vh] w-full shrink-0 flex-col border-b border-slate-200 bg-white/90 shadow-2xl shadow-slate-300/50 backdrop-blur-xl dark:border-white/10 dark:bg-[#071526]/95 dark:shadow-black/30 lg:h-full lg:max-h-none lg:w-[23rem] lg:border-b-0 lg:border-r">

            {{-- Header --}}
            <div class="shrink-0 border-b border-white/10 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-sky-300">Faculty Roster</p>
                        <h2 class="mt-1 text-lg font-black tracking-tight text-white">Assignment Control</h2>
                    </div>
                    <span class="rounded-full border border-sky-400/40 bg-sky-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-sky-200">
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
                            class="h-10 w-full rounded-lg border border-white/10 bg-white/8 px-3 text-sm font-semibold text-white placeholder:text-slate-500 outline-none transition focus:border-sky-300 focus:bg-white/12 focus:ring-2 focus:ring-sky-400/20">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        @if(count($facultyDepartments) > 1)
                            <select wire:model.live="departmentFilter" class="h-9 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Departments</option>
                                @foreach($facultyDepartments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        @endif

                        <select wire:model.live="selectedScope" class="h-9 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300 {{ count($facultyDepartments) > 1 ? '' : 'col-span-2' }}">
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
                            \App\Models\Faculty::SCOPE_GENED            => 'border-sky-300/30 bg-sky-400/10 text-sky-200',
                            \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'border-violet-300/30 bg-violet-400/10 text-violet-200',
                            default                                      => 'border-emerald-300/30 bg-emerald-400/10 text-emerald-200',
                        };
                    @endphp

                    <div
                        wire:key="faculty-card-{{ $faculty->id }}"
                        x-data="{ open: false }"
                        class="group relative rounded-xl border transition duration-200 {{ $isSelected ? 'border-sky-300/70 bg-sky-400/12 shadow-lg shadow-sky-950/40' : 'border-white/10 bg-white/[0.055] hover:border-sky-300/50 hover:bg-white/[0.08]' }}">

                        <div class="flex items-center gap-3 p-3">
                            <button
                                type="button"
                                wire:click="selectFaculty({{ $faculty->id }})"
                                class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sky-400 to-blue-700 text-sm font-black text-white shadow-lg shadow-sky-950/40">
                                    {{ strtoupper(substr($faculty->full_name, 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-black uppercase tracking-tight text-white">
                                        {{ $faculty->full_name }}
                                    </span>
                                    <span class="mt-0.5 block truncate text-[11px] font-semibold text-slate-400">
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
                                        <circle cx="21" cy="21" r="18" stroke="currentColor" stroke-width="3" fill="none" class="text-white/10"></circle>
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
                                            class="transition-all duration-500">
                                        </circle>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                                        <span class="text-[11px] font-black text-white">{{ $units }}</span>
                                        <span class="text-[8px] font-bold text-slate-500">/{{ $max }}</span>
                                    </div>
                                </div>

                                {{-- Actions dropdown --}}
                                <button
                                    type="button"
                                    x-on:click.stop="open = !open"
                                    class="flex h-9 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-slate-300 transition hover:border-sky-300/60 hover:bg-sky-400/10 hover:text-white"
                                    aria-label="Open faculty actions">
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
                            class="absolute right-3 top-14 z-40 w-56 overflow-hidden rounded-xl border border-sky-300/20 bg-[#081a2d]/95 p-1 shadow-2xl shadow-black/40 backdrop-blur-xl">
                            <button type="button" wire:click="openAssignmentPanel({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-200 transition hover:bg-sky-400/15 hover:text-white">
                                Assign Subject / Schedule
                            </button>
                            <button type="button" wire:click="showFacultyLoad({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-200 transition hover:bg-sky-400/15 hover:text-white">
                                View Faculty Load
                            </button>
                            <button type="button" wire:click="showFacultySchedule({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-200 transition hover:bg-sky-400/15 hover:text-white">
                                View Schedule
                            </button>
                            <button type="button" wire:click="showFacultyConflicts({{ $faculty->id }})" x-on:click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-200 transition hover:bg-sky-400/15 hover:text-white">
                                View Conflicts
                            </button>
                            <button type="button" x-on:click.stop="$wire.preparePrintLoad({{ $faculty->id }}).then(() => window.print()); open = false" class="w-full rounded-lg px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-slate-200 transition hover:bg-sky-400/15 hover:text-white">
                                Print Load Summary
                            </button>
                        </div>
                    </div>

                @empty
                    <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-white/10 text-center">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No faculty found</p>
                    </div>
                @endforelse
            </div>
        </aside>

        {{-- ============================================================ --}}
        {{-- MAIN PANEL                                                    --}}
        {{-- ============================================================ --}}
        <main class="print-area custom-scrollbar flex min-w-0 flex-1 flex-col overflow-y-auto bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.16),transparent_34%),linear-gradient(135deg,#f8fafc_0%,#eef6ff_48%,#e2e8f0_100%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.18),transparent_34%),linear-gradient(135deg,#081526_0%,#0b1220_48%,#050914_100%)]">

            @if($currentFaculty)

                {{-- ========================================================
                     STATE B — FACULTY SELECTED
                     ======================================================== --}}

                {{-- Compact department overview mini-cards (top row) --}}
                <div class="summary-mini-bar no-print shrink-0 border-b border-white/[0.07] bg-white/[0.025] px-4 py-2 backdrop-blur-xl sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-[9px] font-black uppercase tracking-[0.22em] text-slate-500">Dept Overview</span>

                        {{-- Mini card: Total Subjects --}}
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-white/5 bg-white/[0.04] px-3 py-1.5 text-sm">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total</span>
                            <span class="font-black text-white">{{ $deptSummary['totalSubjects'] }}</span>
                        </div>

                        {{-- Mini card: Assigned --}}
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-emerald-300/15 bg-emerald-400/[0.07] px-3 py-1.5 text-sm">
                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-300/70">Assigned</span>
                            <span class="font-black text-emerald-200">{{ $deptSummary['assignedSubjects'] }}</span>
                        </div>

                        {{-- Mini card: Subjects Left --}}
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-amber-300/15 bg-amber-400/[0.07] px-3 py-1.5 text-sm">
                            <span class="text-[9px] font-black uppercase tracking-wider text-amber-300/70">Left</span>
                            <span class="font-black text-amber-200">{{ $deptSummary['subjectsLeft'] }}</span>
                        </div>

                        {{-- Mini card: Faculty Processed --}}
                        <div class="mini-stat-card flex items-center gap-2 rounded-xl border border-sky-300/15 bg-sky-400/[0.07] px-3 py-1.5 text-sm">
                            <span class="text-[9px] font-black uppercase tracking-wider text-sky-300/70">Processed</span>
                            <span class="font-black text-sky-200">{{ $deptSummary['facultyProcessed'] }}</span>
                        </div>

                        <span class="ml-auto text-[9px] font-bold text-slate-600">
                            Dept: {{ $deptSummary['activeDepartment'] }}
                        </span>
                    </div>
                </div>

                {{-- Faculty Header + Specialized Load Cards --}}
                <section class="border-b border-white/10 bg-white/[0.045] p-4 backdrop-blur-xl sm:p-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-sky-300/40 bg-sky-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-sky-200">Active Faculty</span>
                                @php
                                    $currentScopeClass = match($currentFaculty->faculty_scope) {
                                        \App\Models\Faculty::SCOPE_GENED            => 'border-sky-300/30 bg-sky-400/10 text-sky-200',
                                        \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'border-violet-300/30 bg-violet-400/10 text-violet-200',
                                        default                                      => 'border-emerald-300/30 bg-emerald-400/10 text-emerald-200',
                                    };
                                @endphp
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] {{ $currentScopeClass }}">{{ $currentFaculty->scopeLabel() }}</span>
                                <span class="rounded-full border border-sky-300/30 bg-sky-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-200">{{ $currentFaculty->canTeachMinorSubjects() ? 'Minor OK' : 'Major Only' }}</span>
                                <span class="rounded-full border border-emerald-300/30 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-200">{{ $currentFaculty->employment_type ?? 'Faculty' }}</span>
                            </div>
                            <h1 class="truncate text-2xl font-black uppercase tracking-tight text-white sm:text-4xl">
                                {{ $currentFaculty->full_name }}
                            </h1>
                            <p class="mt-2 text-sm font-semibold text-slate-400">
                                {{ $currentFaculty->employee_id }} / {{ $currentFaculty->displayDepartment() }}
                            </p>
                        </div>

                        <div class="no-print flex flex-wrap gap-2">
                            <button type="button" wire:click="openAssignmentPanel({{ $currentFaculty->id }})" class="rounded-lg bg-sky-400 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-950 shadow-lg shadow-sky-950/30 transition hover:bg-sky-300 active:scale-95">
                                Assign Subject / Schedule
                            </button>
                            <button type="button" wire:click="submitFacultyLoading" class="rounded-lg border border-emerald-300/30 bg-emerald-400/12 px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-100 transition hover:bg-emerald-400/20 active:scale-95">
                                Submit Loading
                            </button>
                            <button type="button" x-on:click="window.print()" class="rounded-lg border border-white/10 bg-white/8 px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-white/12 active:scale-95">
                                Print
                            </button>
                        </div>
                    </div>

                    {{-- ── Specialized Faculty Load Cards (State B) ── --}}
                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        {{-- ── MAJOR LOAD CARD ── --}}
                        @if(!$currentFaculty->isGenEd())
                        <div class="load-card rounded-2xl border border-amber-300/20 bg-slate-900/40 p-6 shadow-xl shadow-amber-950/10 backdrop-blur-md">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-amber-300/80">Major Load</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-400">Departmental subject assignments</p>
                                </div>
                                <span class="rounded-xl border border-amber-300/25 bg-amber-400/12 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-amber-200">
                                    {{ $summary['majorCount'] }} subject(s)
                                </span>
                            </div>

                            <div class="flex items-end gap-2">
                                <span class="text-5xl font-black leading-none text-white">{{ $summary['majorUnits'] }}</span>
                                <span class="pb-1 text-lg font-bold text-amber-200/60">/ {{ $summary['maxUnits'] }} units</span>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400 transition-all duration-700"
                                    style="width: {{ $summary['majorPercent'] }}%">
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] font-bold text-amber-200/60">{{ $summary['majorPercent'] }}% of max load</p>

                            @if($summary['majorCount'] > 0)
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Avg Units</p>
                                        <p class="mt-0.5 text-lg font-black text-white">{{ $summary['averageMajorUnits'] }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Offerings</p>
                                        <p class="mt-0.5 text-lg font-black text-white">{{ $summary['majorCount'] }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif

                        {{-- ── MINOR / GENED LOAD CARD ── --}}
                        <div class="load-card rounded-2xl border {{ $currentFaculty->isGenEd() ? 'border-sky-300/25 bg-slate-900/40' : 'border-violet-300/20 bg-slate-900/40' }} p-6 shadow-xl {{ $currentFaculty->isGenEd() ? 'shadow-sky-950/10' : 'shadow-violet-950/10' }} backdrop-blur-md {{ $currentFaculty->isGenEd() ? 'md:col-span-2' : '' }}">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    @if($currentFaculty->isGenEd())
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-sky-300/80">GenEd / Minor Load</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-400">Institution-wide subject assignments</p>
                                    @else
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-violet-300/80">Minor Load</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-400">Cross-department minor subject assignments</p>
                                    @endif
                                </div>
                                <span class="rounded-xl border {{ $currentFaculty->isGenEd() ? 'border-sky-300/25 bg-sky-400/12 text-sky-200' : 'border-violet-300/25 bg-violet-400/12 text-violet-200' }} px-3 py-1.5 text-xs font-black uppercase tracking-wider">
                                    {{ $summary['minorCount'] }} subject(s)
                                </span>
                            </div>

                            <div class="flex items-end gap-2">
                                <span class="text-5xl font-black leading-none text-white">{{ $summary['minorUnits'] }}</span>
                                <span class="pb-1 text-lg font-bold {{ $currentFaculty->isGenEd() ? 'text-sky-200/60' : 'text-violet-200/60' }}">/ {{ $summary['maxUnits'] }} units</span>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full {{ $currentFaculty->isGenEd() ? 'bg-gradient-to-r from-sky-500 to-blue-400' : 'bg-gradient-to-r from-violet-500 to-purple-400' }} transition-all duration-700"
                                    style="width: {{ $summary['minorPercent'] }}%">
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] font-bold {{ $currentFaculty->isGenEd() ? 'text-sky-200/60' : 'text-violet-200/60' }}">{{ $summary['minorPercent'] }}% of max load</p>

                            @if($summary['minorCount'] > 0)
                                <div class="mt-4 grid grid-cols-2 gap-2 {{ $currentFaculty->isGenEd() ? 'md:grid-cols-4' : '' }}">
                                    <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Avg Units</p>
                                        <p class="mt-0.5 text-lg font-black text-white">{{ $summary['averageMinorUnits'] }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Offerings</p>
                                        <p class="mt-0.5 text-lg font-black text-white">{{ $summary['minorCount'] }}</p>
                                    </div>
                                    @if($currentFaculty->isGenEd())
                                        <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Total Units</p>
                                            <p class="mt-0.5 text-lg font-black text-white">{{ $summary['totalUnits'] }}</p>
                                        </div>
                                        <div class="rounded-lg border border-white/5 bg-white/[0.04] px-3 py-2">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Utilization</p>
                                            <p class="mt-0.5 text-lg font-black text-white">{{ $summary['utilizationPercent'] }}%</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                    </div>

                    {{-- Total load progress bar (always visible) --}}
                    <div class="mt-4 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Load Utilization</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-black text-white">{{ $summary['totalUnits'] }}</span>
                                <span class="text-sm font-bold text-slate-400">/ {{ $summary['maxUnits'] }} units</span>
                                <span class="ml-2 text-xs font-bold {{ $summary['utilizationPercent'] >= 100 ? 'text-red-300' : ($summary['utilizationPercent'] >= 85 ? 'text-amber-300' : 'text-sky-300') }}">
                                    {{ $summary['utilizationPercent'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/10">
                            <div
                                class="h-full rounded-full transition-all duration-700 {{ $summary['utilizationPercent'] >= 100 ? 'bg-gradient-to-r from-red-500 to-rose-400' : ($summary['utilizationPercent'] >= 85 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-sky-500 to-blue-400') }}"
                                style="width: {{ min(100, $summary['utilizationPercent']) }}%">
                            </div>
                        </div>
                    </div>

                    @if($summary['overloadUnits'] > 0)
                        <div class="mt-4 rounded-xl border border-red-400/40 bg-red-500/12 p-4">
                            <p class="text-sm font-black uppercase tracking-wider text-red-100">Overload Warning</p>
                            <p class="mt-1 text-sm font-semibold text-red-100/80">
                                This faculty load is {{ $summary['overloadUnits'] }} unit(s) over the configured maximum of {{ $summary['maxUnits'] }} units.
                            </p>
                        </div>
                    @endif
                </section>

                {{-- Tab navigation --}}
                <section class="no-print shrink-0 border-b border-white/10 bg-[#071526]/80 px-4 pt-4 backdrop-blur-xl sm:px-6">
                    <div class="flex gap-5 overflow-x-auto">
                        <button type="button" wire:click="toggleTab('subjects')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'subjects' ? 'border-sky-300 text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-200' }}">
                            Assigned Subjects ({{ $groupedAssignedSubjects->count() }})
                        </button>
                        <button type="button" wire:click="toggleTab('schedule')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'schedule' ? 'border-sky-300 text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-200' }}">
                            Schedule Overview
                        </button>
                        <button type="button" wire:click="toggleTab('conflicts')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'conflicts' ? 'border-sky-300 text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-200' }}">
                            Conflicts ({{ $facultyConflicts->count() }})
                        </button>
                        <button type="button" wire:click="toggleTab('summary')" class="whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-widest transition {{ $activeTab === 'summary' ? 'border-sky-300 text-sky-200' : 'border-transparent text-slate-500 hover:text-slate-200' }}">
                            Load Summary
                        </button>
                    </div>
                </section>

                {{-- Tab content --}}
                <section class="flex-1 p-4 sm:p-6">

                    {{-- ------------------------------------------------ --}}
                    {{-- TAB: Assigned Subjects                             --}}
                    {{-- ------------------------------------------------ --}}
                    @if($activeTab === 'subjects')
                        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.045] shadow-2xl shadow-black/20 backdrop-blur-xl">
                            @if($groupedAssignedSubjects->count() > 0)
                                <div class="custom-scrollbar overflow-x-auto">
                                    <table class="min-w-[960px] w-full text-left text-xs">
                                        <thead class="bg-white/[0.07] text-[10px] uppercase tracking-[0.18em] text-slate-400">
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
                                        <tbody class="divide-y divide-white/10">
                                            @foreach($groupedAssignedSubjects as $assignedSubject)
                                                <tr
                                                    wire:key="assigned-row-{{ $assignedSubject['first_schedule_id'] }}"
                                                    class="transition hover:bg-sky-400/5">

                                                    <td class="px-4 py-3">
                                                        <p class="font-black uppercase text-sky-200">{{ $assignedSubject['subject_code'] }}</p>
                                                        <p class="mt-1 text-[10px] font-bold uppercase text-amber-200/80">{{ $assignedSubject['edp_code'] }}</p>
                                                    </td>

                                                    <td class="max-w-sm px-4 py-3">
                                                        <p class="line-clamp-2 font-bold text-white">{{ $assignedSubject['description'] }}</p>
                                                    </td>

                                                    <td class="px-4 py-3 font-bold text-slate-300">
                                                        {{ $assignedSubject['group'] }}
                                                    </td>

                                                    <td class="px-4 py-3 font-bold text-slate-300">
                                                        {{ $assignedSubject['room'] }}
                                                    </td>

                                                    <td class="px-4 py-3 font-bold leading-6 text-slate-300">
                                                        {!! $assignedSubject['schedule'] !!}
                                                    </td>

                                                    <td class="px-4 py-3 text-center font-black text-white">
                                                        {{ $assignedSubject['units'] }}
                                                    </td>

                                                    <td class="px-4 py-3 text-center">
                                                        <span class="rounded-full border px-2 py-1 text-[10px] font-black uppercase
                                                            {{ $assignedSubject['type'] === 'Major'
                                                                ? 'border-amber-300/30 bg-amber-400/10 text-amber-100'
                                                                : 'border-violet-300/30 bg-violet-400/10 text-violet-100' }}">
                                                            {{ $assignedSubject['type'] }}
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3 text-right">
                                                        <button
                                                            type="button"
                                                            wire:click="removeSubject({{ $assignedSubject['first_schedule_id'] }})"
                                                            class="rounded-lg border border-red-300/20 bg-red-400/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-red-100 transition hover:bg-red-400/20">
                                                            Remove
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="flex h-64 flex-col items-center justify-center text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No assigned subjects</p>
                                    <button type="button" wire:click="openAssignmentPanel({{ $currentFaculty->id }})" class="mt-4 rounded-lg bg-sky-400 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-950 transition hover:bg-sky-300">
                                        Open Assignment Panel
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- ------------------------------------------------ --}}
                    {{-- TAB: Schedule Overview                             --}}
                    {{-- ------------------------------------------------ --}}
                    @if($activeTab === 'schedule')
                        <div class="grid gap-4 xl:grid-cols-2">
                            @forelse($scheduleGroups as $day => $daySchedules)
                                <div class="rounded-xl border border-white/10 bg-white/[0.045] p-4 shadow-xl shadow-black/20 backdrop-blur-xl">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-sm font-black uppercase tracking-[0.18em] text-white">{{ $day }}</h3>
                                        <span class="rounded-full border border-white/10 bg-white/8 px-2 py-1 text-[10px] font-black uppercase text-slate-300">{{ $daySchedules->count() }} class(es)</span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($daySchedules as $schedule)
                                            <div class="rounded-lg border border-sky-300/15 bg-sky-400/8 p-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-black uppercase text-sky-100">{{ $schedule->subject?->subject_code ?? 'N/A' }}</p>
                                                        <p class="mt-1 line-clamp-2 text-xs font-semibold text-slate-300">{{ $schedule->subject?->description ?? 'Untitled subject' }}</p>
                                                    </div>
                                                    <span class="shrink-0 rounded-md bg-white/10 px-2 py-1 text-[10px] font-black text-white">{{ $schedule->subject?->units ?? 0 }}u</span>
                                                </div>
                                                <p class="mt-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }} / {{ $schedule->room?->room_name ?? 'No room' }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-white/10 bg-white/[0.035] p-10 text-center xl:col-span-2">
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No schedule to display</p>
                                </div>
                            @endforelse
                        </div>
                    @endif

                    {{-- ------------------------------------------------ --}}
                    {{-- TAB: Conflicts                                     --}}
                    {{-- ------------------------------------------------ --}}
                    @if($activeTab === 'conflicts')
                        <div class="rounded-xl border border-white/10 bg-white/[0.045] p-4 shadow-xl shadow-black/20 backdrop-blur-xl">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-white">Conflict Review</h3>
                                <span class="rounded-full border border-white/10 bg-white/8 px-2 py-1 text-[10px] font-black uppercase text-slate-300">{{ $facultyConflicts->count() }} issue(s)</span>
                            </div>

                            @forelse($facultyConflicts as $conflict)
                                <div class="mb-3 rounded-lg border border-amber-300/30 bg-amber-400/10 p-4">
                                    <p class="text-sm font-black uppercase tracking-wider text-amber-100">{{ $conflict['title'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-amber-100/80">{{ $conflict['message'] }}</p>
                                </div>
                            @empty
                                <div class="rounded-lg border border-emerald-300/20 bg-emerald-400/10 p-5">
                                    <p class="text-sm font-black uppercase tracking-wider text-emerald-100">No conflicts detected</p>
                                    <p class="mt-1 text-sm font-semibold text-emerald-100/75">The current faculty load has no overlapping assigned schedules or availability warnings.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif

                    {{-- ------------------------------------------------ --}}
                    {{-- TAB: Load Summary                                  --}}
                    {{-- ------------------------------------------------ --}}
                    @if($activeTab === 'summary')
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-white">Load Summary</h3>
                                <dl class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-white/8 p-3">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Units</dt>
                                        <dd class="mt-1 text-2xl font-black text-white">{{ $summary['totalUnits'] }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-white/8 p-3">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Max Units</dt>
                                        <dd class="mt-1 text-2xl font-black text-white">{{ $summary['maxUnits'] }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-white/8 p-3">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Schedules</dt>
                                        <dd class="mt-1 text-2xl font-black text-white">{{ $summary['scheduleCount'] }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-white/8 p-3">
                                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Usage</dt>
                                        <dd class="mt-1 text-2xl font-black text-white">{{ $summary['utilizationPercent'] }}%</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                                <h3 class="text-sm font-black uppercase tracking-[0.18em] text-white">Subject Type Mix</h3>
                                <div class="mt-5 space-y-3">
                                    @if(!$currentFaculty->isGenEd())
                                    <div class="rounded-lg border border-amber-300/20 bg-amber-400/10 p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-black uppercase text-amber-100">Major</p>
                                            <p class="text-sm font-black text-white">{{ $summary['majorUnits'] }} units</p>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10">
                                            <div class="h-full rounded-full bg-amber-400 transition-all duration-500" style="width: {{ $summary['majorPercent'] }}%"></div>
                                        </div>
                                        <p class="mt-1.5 text-xs font-semibold text-amber-100/75">{{ $summary['majorCount'] }} subject offering(s) &bull; {{ $summary['averageMajorUnits'] }} avg units</p>
                                    </div>
                                    @endif

                                    <div class="rounded-lg border {{ $currentFaculty->isGenEd() ? 'border-sky-300/20 bg-sky-400/10' : 'border-violet-300/20 bg-violet-400/10' }} p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-black uppercase {{ $currentFaculty->isGenEd() ? 'text-sky-100' : 'text-violet-100' }}">
                                                {{ $currentFaculty->isGenEd() ? 'GenEd / Minor' : 'Minor' }}
                                            </p>
                                            <p class="text-sm font-black text-white">{{ $summary['minorUnits'] }} units</p>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10">
                                            <div class="h-full rounded-full {{ $currentFaculty->isGenEd() ? 'bg-sky-400' : 'bg-violet-400' }} transition-all duration-500" style="width: {{ $summary['minorPercent'] }}%"></div>
                                        </div>
                                        <p class="mt-1.5 text-xs font-semibold {{ $currentFaculty->isGenEd() ? 'text-sky-100/75' : 'text-violet-100/75' }}">{{ $summary['minorCount'] }} subject offering(s) &bull; {{ $summary['averageMinorUnits'] }} avg units</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </section>

            @else

                {{-- ========================================================
                     STATE A — NO FACULTY SELECTED
                     Four large department-level overview cards
                     ======================================================== --}}
                <div class="flex h-full min-h-0 flex-col gap-6 overflow-y-auto p-6 sm:p-8">

                    {{-- Department label / context --}}
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.26em] text-sky-300">Faculty Loading</p>
                            <h1 class="mt-1 text-2xl font-black uppercase tracking-tight text-white sm:text-3xl">Department Overview</h1>
                            <p class="mt-1 text-sm font-semibold text-slate-400">
                                Select a faculty member from the roster to begin assignment.
                                @if($deptSummary['activeDepartment'] !== 'All')
                                    Showing data for <span class="font-black text-sky-300">{{ $deptSummary['activeDepartment'] }}</span>.
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- 4 large glassmorphism overview cards --}}
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                        {{-- Card 1: Total Subjects --}}
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40 p-6 shadow-2xl shadow-black/30 backdrop-blur-md transition duration-300 hover:border-sky-300/30 hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-sky-400/8 blur-2xl transition duration-500 group-hover:bg-sky-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-sky-300/80">Total Subjects</p>
                                    <div class="rounded-lg border border-sky-300/20 bg-sky-400/10 p-2">
                                        <svg class="h-4 w-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-white">{{ $deptSummary['totalSubjects'] }}</p>
                                <p class="mt-3 text-xs font-semibold text-slate-400">Total class offerings in the active scope</p>
                                <div class="mt-4 h-1 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full w-full rounded-full bg-gradient-to-r from-sky-500/60 to-sky-300/40"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 2: Subjects Assigned --}}
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-emerald-300/15 bg-slate-900/40 p-6 shadow-2xl shadow-black/30 backdrop-blur-md transition duration-300 hover:border-emerald-300/35 hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-400/8 blur-2xl transition duration-500 group-hover:bg-emerald-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-300/80">Assigned</p>
                                    <div class="rounded-lg border border-emerald-300/20 bg-emerald-400/10 p-2">
                                        <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-white">{{ $deptSummary['assignedSubjects'] }}</p>
                                <p class="mt-3 text-xs font-semibold text-slate-400">Class blocks with an instructor attached</p>
                                @if($deptSummary['totalSubjects'] > 0)
                                    @php $assignedPct = round(($deptSummary['assignedSubjects'] / $deptSummary['totalSubjects']) * 100); @endphp
                                    <div class="mt-4 h-1 overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-300 transition-all duration-700" style="width: {{ $assignedPct }}%"></div>
                                    </div>
                                    <p class="mt-1.5 text-[10px] font-bold text-emerald-300/60">{{ $assignedPct }}% of total</p>
                                @else
                                    <div class="mt-4 h-1 rounded-full bg-white/10"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Card 3: Subjects Left --}}
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-amber-300/15 bg-slate-900/40 p-6 shadow-2xl shadow-black/30 backdrop-blur-md transition duration-300 hover:border-amber-300/35 hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-400/8 blur-2xl transition duration-500 group-hover:bg-amber-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-amber-300/80">Subjects Left</p>
                                    <div class="rounded-lg border border-amber-300/20 bg-amber-400/10 p-2">
                                        <svg class="h-4 w-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none {{ $deptSummary['subjectsLeft'] > 0 ? 'text-amber-200' : 'text-white' }}">{{ $deptSummary['subjectsLeft'] }}</p>
                                <p class="mt-3 text-xs font-semibold text-slate-400">Remaining unassigned class blocks</p>
                                @if($deptSummary['totalSubjects'] > 0)
                                    @php $leftPct = round(($deptSummary['subjectsLeft'] / $deptSummary['totalSubjects']) * 100); @endphp
                                    <div class="mt-4 h-1 overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-yellow-300 transition-all duration-700" style="width: {{ $leftPct }}%"></div>
                                    </div>
                                    <p class="mt-1.5 text-[10px] font-bold text-amber-300/60">{{ $leftPct }}% unassigned</p>
                                @else
                                    <div class="mt-4 h-1 rounded-full bg-white/10"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Card 4: Faculty Processed --}}
                        <div class="dept-card group relative overflow-hidden rounded-2xl border border-violet-300/15 bg-slate-900/40 p-6 shadow-2xl shadow-black/30 backdrop-blur-md transition duration-300 hover:border-violet-300/35 hover:bg-slate-900/55">
                            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-400/8 blur-2xl transition duration-500 group-hover:bg-violet-400/15"></div>
                            <div class="relative">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-violet-300/80">Faculty Processed</p>
                                    <div class="rounded-lg border border-violet-300/20 bg-violet-400/10 p-2">
                                        <svg class="h-4 w-4 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-6xl font-black leading-none text-white">{{ $deptSummary['facultyProcessed'] }}</p>
                                <p class="mt-3 text-xs font-semibold text-slate-400">Unique faculty with ≥1 subject assigned here</p>
                                <div class="mt-4 h-1 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full w-full rounded-full bg-gradient-to-r from-violet-500/60 to-violet-300/40"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Prompt to select a faculty --}}
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-center backdrop-blur-xl">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Ready to Assign</p>
                        <p class="mt-2 text-sm font-semibold text-slate-400">
                            Pick a faculty member from the roster on the left to view their detailed load and begin assigning subjects.
                        </p>
                    </div>

                </div>

            @endif
        </main>
    </div>

    {{-- ============================================================ --}}
    {{-- ASSIGNMENT SLIDE-OVER PANEL                                   --}}
    {{-- ============================================================ --}}
    <div
        x-cloak
        x-show="assignmentOpen"
        class="fixed inset-0 z-[80] overflow-hidden"
        aria-modal="true"
        role="dialog">

        {{-- Backdrop --}}
        <div
            x-show="assignmentOpen"
            x-transition.opacity
            x-on:click="$wire.closeAssignmentPanel()"
            class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm">
        </div>

        {{-- Panel --}}
        <aside
            x-show="assignmentOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-60"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-60"
            class="absolute right-0 top-0 flex h-full w-full max-w-3xl flex-col border-l border-sky-300/20 bg-[#071526]/95 shadow-2xl shadow-black/50 backdrop-blur-2xl">

            @if($currentFaculty)
                {{-- Panel header --}}
                <div class="shrink-0 border-b border-white/10 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-sky-300">Assignment Panel</p>
                            <h2 class="mt-1 truncate text-xl font-black uppercase tracking-tight text-white">
                                {{ $currentFaculty->full_name }}
                            </h2>
                            <p class="mt-1 text-xs font-semibold text-slate-400">
                                {{ $currentFaculty->displayDepartment() }} &bull; {{ $summary['totalUnits'] }}/{{ $summary['maxUnits'] }} units used
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeAssignmentPanel"
                            class="shrink-0 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-black uppercase tracking-wider text-slate-300 transition hover:bg-white/10 hover:text-white">
                            Close
                        </button>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="shrink-0 border-b border-white/10 p-4">
                    <div class="grid gap-3">
                        <input
                            type="search"
                            wire:model.live.debounce.250ms="subjectSearch"
                            placeholder="Search code, title, EDP, department, major, or section"
                            class="h-11 w-full rounded-lg border border-sky-300/20 bg-white/8 px-3 text-sm font-semibold text-white placeholder:text-slate-500 outline-none transition focus:border-sky-300 focus:bg-white/12 focus:ring-2 focus:ring-sky-400/20">

                        <div class="grid gap-2 sm:grid-cols-5">
                            <select wire:model.live="subjectDepartmentFilter" class="h-10 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Dept</option>
                                @foreach($scheduleDepartments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="subjectMajorFilter" class="h-10 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Majors</option>
                                @foreach($majors as $major)
                                    <option value="{{ $major }}">{{ $major }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="subjectYearLevelFilter" class="h-10 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Years</option>
                                @foreach($yearLevels as $level)
                                    <option value="{{ $level }}">Year {{ $level }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="subjectSectionFilter" class="h-10 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Sections</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section }}">{{ $section }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="subjectTypeFilter" class="h-10 rounded-lg border border-white/10 bg-[#0b1b30] px-2 text-xs font-bold text-slate-200 outline-none transition focus:border-sky-300">
                                <option value="all">All Types</option>
                                @foreach($subjectTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.055] px-3 py-2">
                            <span>
                                <span class="block text-xs font-black uppercase tracking-wider text-white">Show unassigned only</span>
                                <span class="block text-[11px] font-semibold text-slate-400">Turn off to review assigned schedules and shared subject distribution.</span>
                            </span>
                            <input type="checkbox" wire:model.live="showUnassignedOnly" class="h-5 w-5 rounded border-white/20 bg-[#0b1b30] text-sky-400 focus:ring-sky-300">
                        </label>
                    </div>
                </div>

                {{-- Assignable schedules list --}}
                <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto p-4">
                    @forelse($groupedAvailableSubjects as $group)
                        @php
                            $assignedToCurrent = $group['faculty_id'] && (int) $group['faculty_id'] === (int) $currentFaculty->id;
                            $assignedElsewhere = $group['faculty_id'] && ! $assignedToCurrent;
                            $isFinalized       = $group['is_finalized'];
                        @endphp

                        <article wire:key="assignable-group-{{ $group['first_schedule_id'] }}" class="rounded-xl border border-white/10 bg-white/[0.055] p-4 transition hover:border-sky-300/40 hover:bg-white/[0.075]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md border border-amber-300/30 bg-amber-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-amber-100">{{ $group['edp_code'] }}</span>
                                        <span class="rounded-md border border-sky-300/30 bg-sky-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-100">{{ $group['subject_code'] }}</span>
                                        <span class="rounded-md border px-2 py-1 text-[10px] font-black uppercase tracking-wider {{ $group['type'] === 'Major' ? 'border-amber-300/30 bg-amber-400/10 text-amber-100' : 'border-violet-300/30 bg-violet-400/10 text-violet-100' }}">{{ $group['type'] }}</span>
                                        @if($assignedToCurrent)
                                            <span class="rounded-md border border-emerald-300/30 bg-emerald-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-100">Assigned here</span>
                                        @elseif($assignedElsewhere)
                                            <span class="rounded-md border border-slate-300/20 bg-slate-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-300">Assigned</span>
                                        @else
                                            <span class="rounded-md border border-sky-300/20 bg-sky-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-sky-100">Unassigned</span>
                                        @endif
                                        @if(count($group['schedule_ids']) > 1)
                                            <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-300">{{ count($group['schedule_ids']) }} days</span>
                                        @endif
                                    </div>

                                    <h3 class="mt-3 line-clamp-2 text-base font-black text-white">{{ $group['description'] }}</h3>

                                    <div class="mt-3 grid gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 sm:grid-cols-2">
                                        <p>Department: <span class="text-slate-200">{{ $group['department'] }}</span></p>
                                        <p>Major: <span class="text-slate-200">{{ $group['major'] }}</span></p>
                                        <p>Year / Section: <span class="text-slate-200">Y{{ $group['year'] }} / {{ $group['section'] }}</span></p>
                                        <p>Room: <span class="text-slate-200">{{ $group['room'] }}</span></p>
                                        <p>Day: <span class="text-slate-200">{{ $group['days'] }}</span></p>
                                        <p>Time: <span class="text-slate-200">{{ $group['time'] }}</span></p>
                                        <p>Units: <span class="text-slate-200">{{ $group['units'] }}</span></p>
                                        <p>Status: <span class="text-slate-200">{{ $group['faculty_name'] ?? 'Unassigned' }}</span></p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col gap-2 sm:w-40">
                                    @if($assignedToCurrent)
                                        <button
                                            type="button"
                                            wire:click="removeSubjectGroup({{ json_encode($group['schedule_ids']) }})"
                                            class="rounded-lg border border-red-300/20 bg-red-400/10 px-3 py-2 text-xs font-black uppercase tracking-widest text-red-100 transition hover:bg-red-400/20">
                                            Remove
                                        </button>
                                    @elseif($assignedElsewhere)
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-500">
                                            Assigned
                                        </button>
                                    @elseif($isFinalized)
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-500">
                                            Finalized
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="assignSubjectGroup({{ json_encode($group['schedule_ids']) }})"
                                            wire:loading.attr="disabled"
                                            wire:target="assignSubjectGroup"
                                            class="rounded-lg bg-sky-400 px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-950 shadow-lg shadow-sky-950/30 transition hover:bg-sky-300 active:scale-95 disabled:cursor-wait disabled:opacity-70">
                                            Assign
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>

                    @empty
                        <div class="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-center">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-500">No schedules match the current filters</p>
                            <p class="mt-2 text-sm font-semibold text-slate-500">Try clearing search terms or disabling the unassigned-only filter.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </aside>
    </div>

    {{-- ============================================================ --}}
    {{-- CONFLICT / OVERRIDE MODAL                                     --}}
    {{-- ============================================================ --}}
    @if($conflictModalOpen)
        <div class="fixed inset-0 z-[100] flex items-stretch justify-end bg-slate-950/75 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="custom-scrollbar h-full w-full max-w-xl overflow-y-auto border-l border-amber-300/30 bg-[#081a2d] p-5 shadow-2xl shadow-black/50">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-amber-200">Assignment Review</p>
                <h2 class="mt-2 text-2xl font-black uppercase tracking-tight text-white">Faculty Conflict Detected</h2>
                <p class="mt-2 text-sm font-semibold text-slate-400">
                    This schedule cannot be assigned until the conflict is resolved. Time, duplicate subject, load limit, and eligibility conflicts are blocked.
                </p>

                <div class="mt-4 space-y-3">
                    @foreach($pendingAssignmentWarnings as $warning)
                        @php $details = $warning['details'] ?? []; @endphp
                        <div class="rounded-xl border border-amber-300/25 bg-amber-400/10 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black uppercase tracking-wider text-amber-100">{{ $warning['title'] ?? 'Assignment Warning' }}</p>
                                    <p class="mt-1 text-sm font-semibold text-amber-100/80">{{ $warning['message'] ?? 'Review this assignment before continuing.' }}</p>
                                </div>
                                <span class="rounded-md border border-amber-200/30 bg-amber-200/10 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-amber-100">
                                    {{ str_replace('_', ' ', $warning['type'] ?? 'conflict') }}
                                </span>
                            </div>

                            @if(!empty($details))
                                <div class="mt-3 grid gap-2 rounded-lg border border-white/10 bg-slate-950/25 p-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 sm:grid-cols-2">
                                    @if(!empty($details['faculty_name']))
                                        <p>Faculty: <span class="text-slate-100">{{ $details['faculty_name'] }}</span></p>
                                    @endif
                                    @if(!empty($details['conflicting_subject']))
                                        <p>Subject: <span class="text-slate-100">{{ $details['conflicting_subject'] }}</span></p>
                                    @endif
                                    @if(!empty($details['day']))
                                        <p>Day: <span class="text-slate-100">{{ $details['day'] }}</span></p>
                                    @endif
                                    @if(!empty($details['time']))
                                        <p>Time: <span class="text-slate-100">{{ $details['time'] }}</span></p>
                                    @endif
                                    @if(!empty($details['room']))
                                        <p>Room: <span class="text-slate-100">{{ $details['room'] }}</span></p>
                                    @endif
                                    @if(!empty($details['reason']))
                                        <p class="sm:col-span-2">Reason: <span class="text-slate-100 normal-case tracking-normal">{{ $details['reason'] }}</span></p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if(!empty($assignmentRecommendations['faculty']) || !empty($assignmentRecommendations['slots']) || !empty($assignmentRecommendations['rooms']))
                    <div class="mt-5 grid gap-3">
                        @if(!empty($assignmentRecommendations['faculty']))
                            <div class="rounded-xl border border-sky-300/25 bg-sky-400/10 p-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-sky-100">Suggested Faculty</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['faculty'] as $suggestion)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-white/10 bg-white/8 px-2.5 py-2">
                                            <span class="text-[10px] font-black uppercase text-slate-100">
                                                {{ $suggestion['name'] }} / {{ $suggestion['scope'] }} / {{ $suggestion['remaining_units'] }}u open
                                            </span>
                                            <button type="button" wire:click="useFacultySuggestion({{ (int) $suggestion['id'] }})" class="rounded-md bg-sky-300 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-slate-950 transition hover:bg-sky-200">
                                                Use Suggestion
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($assignmentRecommendations['slots']))
                            <div class="rounded-xl border border-emerald-300/25 bg-emerald-400/10 p-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-100">Suggested Slots</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['slots'] as $suggestion)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-white/10 bg-white/8 px-2.5 py-2">
                                            <span class="text-[10px] font-black uppercase text-slate-100">{{ $suggestion['label'] }}</span>
                                            <button type="button" wire:click="useSlotSuggestion(@js($suggestion['day']), @js($suggestion['start_time']), @js($suggestion['end_time']))" class="rounded-md bg-emerald-300 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-slate-950 transition hover:bg-emerald-200">
                                                Use Suggestion
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($assignmentRecommendations['rooms']))
                            <div class="rounded-xl border border-violet-300/25 bg-violet-400/10 p-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-100">Suggested Rooms</p>
                                <div class="mt-2 grid gap-2">
                                    @foreach($assignmentRecommendations['rooms'] as $room)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-white/10 bg-white/8 px-2.5 py-2">
                                            <span class="text-[10px] font-black uppercase text-slate-100">{{ $room['name'] }} / {{ $room['type'] }}</span>
                                            <button type="button" wire:click="useRoomSuggestion({{ (int) $room['id'] }})" class="rounded-md bg-violet-300 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-slate-950 transition hover:bg-violet-200">
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
                    <button type="button" wire:click="cancelAssignmentOverride" class="rounded-lg border border-white/10 bg-white/8 px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-white/12">
                        Cancel
                    </button>

                    @if($canOverrideWarnings)
                        <button type="button" wire:click="confirmAssignmentOverride" class="rounded-lg bg-amber-400 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-950 transition hover:bg-amber-300">
                            Override and Assign
                        </button>
                    @else
                        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-400">
                            Resolve conflict to assign
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TOAST NOTIFICATIONS                                           --}}
    {{-- ============================================================ --}}
    <div class="faculty-loading-toasts pointer-events-none fixed right-5 top-5 z-[120] w-[min(24rem,calc(100vw-2rem))] space-y-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-6 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-6 scale-95"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-2xl"
                :class="toastClasses(toast.type)">
                <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20 text-sm font-black">
                    <span x-text="toastIcon(toast.type)"></span>
                </div>
                <p class="min-w-0 flex-1 text-sm font-bold leading-5 tracking-wide" x-text="toast.message"></p>
                <button
                    type="button"
                    x-on:click="removeToast(toast.id)"
                    class="rounded-md px-1.5 text-lg font-black leading-none text-current/80 transition hover:bg-white/15 hover:text-current"
                    aria-label="Dismiss notification">
                    &times;
                </button>
            </div>
        </template>
    </div>

    {{-- ============================================================ --}}
    {{-- STYLES                                                        --}}
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

        /* ── Department overview card entrance animation ── */
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

        /* ── Mini bar entrance when faculty is selected ── */
        .summary-mini-bar {
            animation: miniBarSlide 0.3s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes miniBarSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Load cards entrance animation ── */
        .load-card {
            animation: cardSlideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .load-card:nth-child(1) { animation-delay: 0.06s; }
        .load-card:nth-child(2) { animation-delay: 0.14s; }

        /* ── Light-mode overrides ── */
        body:not(.dark) .faculty-loading-shell [class*="bg-[#071526]"],
        body:not(.dark) .faculty-loading-shell [class*="bg-[#081a2d]"],
        body:not(.dark) .faculty-loading-shell [class*="bg-[#0b1b30]"] { background-color: rgba(255,255,255,0.92) !important; }

        body:not(.dark) .faculty-loading-shell [class*="bg-white/"],
        body:not(.dark) .faculty-loading-shell [class*="bg-white/["] { background-color: rgba(255,255,255,0.72) !important; }

        body:not(.dark) .faculty-loading-shell [class*="border-white/"] { border-color: rgba(148,163,184,0.38) !important; }

        body:not(.dark) .faculty-loading-shell input,
        body:not(.dark) .faculty-loading-shell select {
            background-color: rgba(255,255,255,0.9) !important;
            border-color: rgba(148,163,184,0.45) !important;
            color: #0f172a !important;
        }

        body:not(.dark) .faculty-loading-shell input::placeholder { color: #94a3b8 !important; }

        body:not(.dark) .faculty-loading-shell .text-white,
        body:not(.dark) .faculty-loading-shell .text-slate-100,
        body:not(.dark) .faculty-loading-shell .text-slate-200 { color: #0f172a !important; }

        body:not(.dark) .faculty-loading-shell .text-slate-300 { color: #334155 !important; }
        body:not(.dark) .faculty-loading-shell .text-slate-400,
        body:not(.dark) .faculty-loading-shell .text-slate-500 { color: #64748b !important; }

        body:not(.dark) .faculty-loading-shell .text-sky-100,
        body:not(.dark) .faculty-loading-shell .text-sky-200,
        body:not(.dark) .faculty-loading-shell .text-sky-300 { color: #0369a1 !important; }

        body:not(.dark) .faculty-loading-shell .text-emerald-100,
        body:not(.dark) .faculty-loading-shell .text-emerald-200 { color: #047857 !important; }

        body:not(.dark) .faculty-loading-shell .text-amber-100,
        body:not(.dark) .faculty-loading-shell .text-amber-200 { color: #a16207 !important; }

        body:not(.dark) .faculty-loading-shell .text-violet-100,
        body:not(.dark) .faculty-loading-shell .text-violet-200 { color: #6d28d9 !important; }

        body:not(.dark) .faculty-loading-shell .text-red-100 { color: #b91c1c !important; }

        body:not(.dark) .faculty-loading-shell [class*="shadow-black"] { box-shadow: 0 18px 45px rgba(15,23,42,0.12) !important; }

        body:not(.dark) .faculty-loading-shell .faculty-loading-toasts .text-white { color: #fff !important; }
        body:not(.dark) .faculty-loading-shell .bg-gradient-to-br.text-white { color: #fff !important; }

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
        }
    </style>
</div>