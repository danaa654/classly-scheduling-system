{{-- ============================================================
     BLOCK SCHEDULE — livewire/block-schedule.blade.php
     ============================================================
     Changes in this version:
       • PERMISSION TOGGLE  – Admin-only panel to grant/revoke Registrar
                              finalization access with visual toggle pill
       • AUDIT LOG PANEL    – Mini log inside permission panel (last 10 events)
       • HEADER LAYOUT      – Left: title/semester. Right: permission toggle
                              button + finalize + print, all cleanly spaced
       • READABILITY        – Larger fonts throughout (labels, table, badges)
       • SAFETY RULES       – Finalize still blocked if conflicts or unassigned
       • PRINT              – Robust @media print; hides all new UI chrome
     ============================================================ --}}

<div class="min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500 py-6 px-5">
    <div class="max-w-[1500px] mx-auto space-y-4">

        {{-- ── Flash Message ─────────────────────────────────────────────── --}}
        @if($flashMessage)
            <div @class([
                    'flex items-start gap-3 px-5 py-4 rounded-xl text-sm font-semibold shadow-sm border',
                    'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300' => $flashType === 'success',
                    'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300'             => $flashType === 'error',
                    'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300' => $flashType === 'warning',
                ])
                 x-data
                 x-init="setTimeout(() => $el.remove(), 6000)">
                <span class="text-base mt-0.5 flex-shrink-0">
                    @if($flashType === 'success') ✅ @elseif($flashType === 'error') ⛔ @else ⚠️ @endif
                </span>
                <span class="text-[14px]">{{ $flashMessage }}</span>
            </div>
        @endif

        {{-- ── Finalization Error List ────────────────────────────────────── --}}
        @if(!empty($finalizationErrors))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700
                        rounded-xl px-5 py-4 space-y-2">
                <p class="text-[14px] font-black text-red-700 dark:text-red-400 uppercase tracking-wide mb-2.5 flex items-center gap-2">
                    <span>⛔</span>
                    <span>Finalization Blocked — Resolve these issues:</span>
                </p>
                @foreach($finalizationErrors as $err)
                    <div class="flex items-start gap-2 text-[13px] text-red-600 dark:text-red-400">
                        <span class="flex-shrink-0 mt-0.5 font-bold">→</span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             MAIN CONTAINER
             ════════════════════════════════════════════════════════════════ --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg overflow-hidden transition-colors duration-500"
             id="study-load-container">

            {{-- ──────────────────────────────────────────────────────────────
                 HEADER  (visible in print — do NOT add print:hidden here)
                 ────────────────────────────────────────────────────────────── --}}
            <div class="border-b border-blue-600/20 px-8 py-6
                        bg-gradient-to-r from-blue-50/70 to-indigo-50/40
                        dark:from-blue-900/10 dark:to-indigo-900/5 print-header">
                <div class="flex flex-wrap items-center gap-5">

                    {{-- Icon (hidden in print via screen-only) --}}
                    <div class="flex-shrink-0 rounded-xl
                                bg-gradient-to-br from-blue-500 to-indigo-600
                                flex items-center justify-center shadow-md shadow-blue-200/50
                                dark:shadow-none screen-only w-12 h-12">
                        <span class="text-2xl leading-none">📚</span>
                    </div>

                    {{-- Title block --}}
                    <div>
                        <h1 class="text-[28px] font-black uppercase tracking-tighter
                                   text-slate-800 dark:text-slate-100 leading-none">
                            Study Load
                        </h1>
                        <h2 class="text-[17px] font-bold text-blue-600 dark:text-blue-400
                                   uppercase tracking-tight mt-1">
                            {{ $departmentName }}
                        </h2>
                    </div>

                    {{-- Period badges --}}
                    <div class="flex flex-wrap items-center gap-2.5 ml-2">
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            S.Y. {{ $schoolYear }}
                        </span>
                        <span class="text-slate-300 dark:text-slate-600 text-lg">•</span>
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            {{ $semesterName }}
                        </span>
                        <span class="bg-white/80 dark:bg-slate-800/60 border border-slate-200
                                     dark:border-slate-700 px-3.5 py-1.5 rounded-lg text-[13px]
                                     font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            Year {{ $selectedYear }} · Section {{ $selectedSection }}
                        </span>
                    </div>

                    {{-- ════════════════════════════════════════════════════
                         RIGHT SIDE: Registrar Access Control (Admin only)
                         + Finalized badge — both pinned to the far right.
                         ════════════════════════════════════════════════════ --}}
                    <div class="ml-auto flex items-center gap-2.5 screen-only">

                        {{-- ADMIN-ONLY: Global Registrar Permission Control --}}
                        @if($canManagePermission)
                            <button
                                wire:click="openPermissionPanel"
                                title="Manage global Registrar finalization access"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg
                                       text-[11px] font-bold uppercase tracking-wider
                                       transition-all active:scale-95 border
                                       {{ $registrarWithPermission
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'
                                            : 'bg-white/70 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                            >
                                {{-- Shield icon (small) --}}
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>

                                <span class="leading-none">Registrar Access</span>

                                {{-- Compact toggle pill --}}
                                <span class="flex-shrink-0 w-8 h-4 rounded-full relative transition-colors
                                             {{ $registrarWithPermission ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                                    <span class="absolute top-0.5 w-3 h-3 rounded-full bg-white shadow-sm transition-all
                                                 {{ $registrarWithPermission ? 'left-4' : 'left-0.5' }}">
                                    </span>
                                </span>

                                {{-- Status dot label --}}
                                <span class="text-[10px] normal-case tracking-normal
                                             {{ $registrarWithPermission ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $registrarWithPermission ? 'ON' : 'OFF' }}
                                </span>
                            </button>
                        @endif

                        {{-- Finalized badge (shown when ALL rows are locked) --}}
                        @if($allFinalized)
                            <span class="flex items-center gap-1.5
                                         bg-green-100 dark:bg-green-900/30
                                         border border-green-300 dark:border-green-700
                                         text-green-700 dark:text-green-400
                                         px-3 py-1.5 rounded-lg text-[11px] font-black
                                         uppercase tracking-wider">
                                🔒 Finalized
                            </span>
                        @endif

                    </div>{{-- /right side --}}
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────────
                 STATS BAR  (screen only)
                 ────────────────────────────────────────────────────────────── --}}
            @if($totalRows > 0)
                <div class="print:hidden border-b border-slate-100 dark:border-slate-800
                            bg-slate-50/60 dark:bg-slate-800/30 px-8 py-3
                            flex flex-wrap items-center gap-x-8 gap-y-2 text-[13px]">

                    {{-- Total count --}}
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 flex-shrink-0"></span>
                        <span class="font-bold text-slate-600 dark:text-slate-400">
                            {{ $totalRows }} subject(s)
                        </span>
                    </div>

                    {{-- Assigned count --}}
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <span class="font-bold text-emerald-700 dark:text-emerald-400">
                            {{ $totalRows - $unassignedCount }} assigned
                        </span>
                    </div>

                    {{-- Unassigned --}}
                    @if($unassignedCount > 0)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold text-amber-600 dark:text-amber-400">
                                {{ $unassignedCount }} unassigned
                            </span>
                        </div>
                    @endif

                    {{-- Conflicts --}}
                    @if($conflictCount > 0)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                {{ $conflictCount }} conflict(s) detected
                            </span>
                        </div>
                    @endif

                    {{-- Ready / blocked indicator --}}
                    @if($unassignedCount === 0 && $conflictCount === 0 && !$allFinalized)
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20
                                         border border-emerald-200 dark:border-emerald-700
                                         text-emerald-700 dark:text-emerald-400
                                         px-3.5 py-1.5 rounded-lg text-[13px] font-bold">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Ready to Finalize
                            </span>
                        </div>
                    @elseif($unassignedCount > 0 || $conflictCount > 0)
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20
                                         border border-red-200 dark:border-red-700
                                         text-red-700 dark:text-red-400
                                         px-3.5 py-1.5 rounded-lg text-[13px] font-bold">
                                ⚠ Cannot finalize — resolve issues above
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ──────────────────────────────────────────────────────────────
                 FILTER + ACTION BAR  (screen only)
                 ────────────────────────────────────────────────────────────── --}}
            <div class="print:hidden border-b border-slate-200 dark:border-slate-800
                        bg-slate-50/90 dark:bg-slate-800/40 px-8 py-4">
                <div class="flex flex-wrap gap-5 items-end">

                    {{-- ── Left: Department / Year / Section ──── --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Department / Major</label>
                        <select wire:model.live="selectedDepartment"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[240px]">
                            @foreach($availableDepts as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Year Level</label>
                        <select wire:model.live="selectedYear"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[140px]">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                      uppercase tracking-widest">Section</label>
                        <select wire:model.live="selectedSection"
                                class="px-4 py-2.5 bg-white dark:bg-slate-700
                                       border border-slate-200 dark:border-slate-600 rounded-lg
                                       text-[14px] font-bold text-slate-800 dark:text-slate-200
                                       focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       outline-none transition-all min-w-[140px]">
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>

                    {{-- ── Right: Action Buttons ──────────────────────────── --}}
                    <div class="ml-auto flex flex-wrap items-center gap-3">

                        {{-- NOTE: Registrar Access control has moved to the page header above. --}}

                        {{-- ── Finalize Button ──────────────────────────────
                             Shown when:
                              1. User has finalization permission
                              2. Schedules exist and not fully finalized
                             Disabled (not hidden) when: conflicts or unassigned
                             ─────────────────────────────────────────────── --}}
                        @if($canFinalize && !$allFinalized && $totalRows > 0)
                            <button
                                wire:click="finalizeSchedule"
                                wire:loading.attr="disabled"
                                wire:confirm="Finalize all {{ $totalRows }} schedule(s) for {{ $departmentName }} · Year {{ $selectedYear }} · Section {{ $selectedSection }}? This will lock the schedule for Dean/OIC editing."
                                @class([
                                    'relative px-5 py-2.5 rounded-xl text-[13px] font-black uppercase tracking-wider',
                                    'transition-all active:scale-95 flex items-center gap-2.5',
                                    'text-white shadow-md',
                                    'bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 shadow-emerald-200 dark:shadow-none cursor-pointer'
                                        => ($unassignedCount === 0 && $conflictCount === 0),
                                    'bg-gradient-to-r from-slate-400 to-slate-500 cursor-not-allowed opacity-50 shadow-none'
                                        => ($unassignedCount > 0 || $conflictCount > 0),
                                ])
                                @if($unassignedCount > 0 || $conflictCount > 0) disabled title="Resolve all issues before finalizing" @endif
                            >
                                <span class="text-base leading-none">🔒</span>
                                <span>Finalize Schedule</span>
                                @if($unassignedCount > 0 || $conflictCount > 0)
                                    <span class="text-[11px] opacity-70 font-medium normal-case tracking-normal">
                                        (resolve issues first)
                                    </span>
                                @endif
                            </button>
                        @endif

                        {{-- ── Print Button ──────────────────────────────── --}}
                        <button onclick="window.print()"
                                class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600
                                       hover:from-blue-700 hover:to-indigo-700
                                       text-white rounded-xl text-[13px] font-black uppercase tracking-wider
                                       shadow-md shadow-blue-200 dark:shadow-none
                                       transition-all active:scale-95 flex items-center gap-2.5">
                            <span class="text-base leading-none">🖨️</span>
                            <span>Print Official Load</span>
                        </button>

                    </div>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────────
                 SCHEDULE TABLE
                 ────────────────────────────────────────────────────────────── --}}
            <div class="overflow-x-auto" id="schedule-table-wrapper">

                @forelse($scheduleRows as $sched)
                    @if($loop->first)
                        <table class="w-full border-collapse" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 13%">  {{-- Time --}}
                                <col style="width: 9%">   {{-- EDP Code --}}
                                <col style="width: 9%">   {{-- Subject Code --}}
                                <col style="width: 21%">  {{-- Description --}}
                                <col style="width: 5%">   {{-- Units --}}
                                <col style="width: 8%">   {{-- Day --}}
                                <col style="width: 10%">  {{-- Room --}}
                                <col style="width: 17%">  {{-- Faculty --}}
                                <col style="width: 8%">   {{-- Status --}}
                            </colgroup>

                            <thead>
                                <tr class="bg-slate-900 dark:bg-slate-800 text-white
                                           text-[13px] uppercase font-black tracking-wider">
                                    <th class="px-4 py-3.5 text-center border-r border-slate-700">Time</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">EDP Code</th>
                                    <th class="px-3 py-3.5 text-left  border-r border-slate-700">Subject Code</th>
                                    <th class="px-3 py-3.5 text-left  border-r border-slate-700">Description</th>
                                    <th class="px-2 py-3.5 text-center border-r border-slate-700">Units</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Day(s)</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Room</th>
                                    <th class="px-3 py-3.5 text-center border-r border-slate-700">Faculty</th>
                                    <th class="px-3 py-3.5 text-center">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @endif

                    {{-- ── Data Row ─────────────────────────────────────────── --}}
                    <tr @class([
                            'transition-colors group',
                            'bg-red-50/70 dark:bg-red-900/10 hover:bg-red-50 dark:hover:bg-red-900/20 border-l-4 border-red-500'
                                => $sched->has_conflict,
                            'bg-amber-50/50 dark:bg-amber-900/5 hover:bg-amber-50 dark:hover:bg-amber-900/10 border-l-4 border-amber-400'
                                => (! $sched->has_conflict && is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED),
                            'bg-emerald-50/20 dark:bg-emerald-900/5 hover:bg-emerald-50/40 border-l-4 border-emerald-300'
                                => $sched->status === \App\Models\Schedule::STATUS_FINALIZED,
                            'hover:bg-blue-50/30 dark:hover:bg-blue-900/10'
                                => (! $sched->has_conflict && ! is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED),
                            'bg-slate-50/50 dark:bg-slate-800/20'
                                => ($loop->even && ! $sched->has_conflict && ! is_null($sched->faculty) && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED),
                        ])>

                        {{-- ── Time ─────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center whitespace-nowrap">
                            <span class="bg-blue-50 dark:bg-blue-900/30
                                         text-blue-700 dark:text-blue-300
                                         border border-blue-100 dark:border-blue-800/40
                                         px-2.5 py-1.5 rounded-md
                                         text-[13px] font-black tracking-tight inline-block">
                                {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}
                                <span class="opacity-40">–</span>
                                {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                            </span>
                        </td>

                        {{-- ── EDP Code ─────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center
                                   font-mono text-[12px] font-semibold
                                   text-slate-400 dark:text-slate-500">
                            {{ $sched->subject?->edp_code ?? '—' }}
                        </td>

                        {{-- ── Subject Code ─────────────────────────────── --}}
                        <td class="px-3 py-3
                                   text-[15px] font-black uppercase tracking-tight
                                   text-slate-800 dark:text-slate-100">
                            {{ $sched->subject?->subject_code ?? '—' }}
                        </td>

                        {{-- ── Description ──────────────────────────────── --}}
                        <td class="px-3 py-3
                                   text-[13px] text-slate-600 dark:text-slate-400 leading-snug">
                            {{ $sched->subject?->description ?? '—' }}
                        </td>

                        {{-- ── Units ────────────────────────────────────── --}}
                        <td class="px-2 py-3 text-center">
                            <span class="bg-slate-100 dark:bg-slate-800
                                         text-slate-700 dark:text-slate-300
                                         border border-slate-200 dark:border-slate-700
                                         px-2 py-0.5 rounded text-[12px] font-black inline-block">
                                {{ $sched->subject?->units ?? 0 }}u
                            </span>
                        </td>

                        {{-- ── Day ──────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            <span class="text-[12px] font-black uppercase tracking-tight
                                         text-slate-600 dark:text-slate-400">
                                {{ $sched->day_display }}
                            </span>
                        </td>

                        {{-- ── Room ─────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            <span class="bg-blue-50 dark:bg-blue-900/30
                                         text-blue-600 dark:text-blue-400
                                         border border-blue-100 dark:border-blue-800/40
                                         px-2.5 py-1 rounded text-[12px] font-black uppercase tracking-tight inline-block">
                                {{ $sched->room?->room_name ?? 'No Room' }}
                            </span>
                        </td>

                        {{-- ── Faculty Cell ─────────────────────────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @if($canAssign && $sched->status !== \App\Models\Schedule::STATUS_FINALIZED)

                                <button
                                    wire:click="openFacultyModal(
                                        '{{ $sched->pairing_key }}',
                                        {{ json_encode($sched->ids) }},
                                        {{ $sched->subject?->id ?? 0 }}
                                    )"
                                    @class([
                                        'w-full text-left px-2.5 py-2 rounded-xl transition-all',
                                        'border-2 border-dashed',
                                        'group/btn hover:scale-[1.01] active:scale-95',
                                        'border-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30'
                                            => $sched->has_conflict,
                                        'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                            => (! $sched->has_conflict && ! is_null($sched->faculty)),
                                        'border-amber-300 dark:border-amber-700 bg-amber-50/60 dark:bg-amber-900/10 hover:border-amber-400 hover:bg-amber-50'
                                            => (! $sched->has_conflict && is_null($sched->faculty)),
                                    ])"
                                    title="{{ $sched->has_conflict
                                        ? $sched->conflict_reason
                                        : (is_null($sched->faculty) ? 'Click to assign faculty' : 'Click to change faculty') }}"
                                >
                                    @if($sched->has_conflict)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-red-500 text-sm flex-shrink-0">⚠</span>
                                            <span class="text-[12px] font-bold text-red-700 dark:text-red-400 truncate">
                                                {{ $sched->faculty?->full_name ?? 'Conflict!' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-red-500 dark:text-red-400 mt-0.5 truncate">
                                            {{ $sched->conflict_reason }}
                                        </div>

                                    @elseif($sched->faculty)
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-emerald-500 flex-shrink-0
                                                         flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 truncate leading-snug">
                                                {{ $sched->faculty->full_name }}
                                            </span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 ml-auto flex-shrink-0
                                                        opacity-0 group-hover/btn:opacity-100 transition-opacity"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </div>

                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full border-2 border-dashed border-amber-400
                                                         flex-shrink-0 flex items-center justify-center">
                                                <span class="text-[10px] text-amber-500 font-bold">+</span>
                                            </div>
                                            <span class="text-[12px] italic font-medium text-amber-600 dark:text-amber-500">
                                                Tap to assign
                                            </span>
                                        </div>
                                    @endif

                                </button>

                            @elseif($sched->faculty)
                                <div class="flex items-center justify-center gap-2 px-2 py-2">
                                    <span class="text-[13px] font-semibold text-slate-600 dark:text-slate-400">
                                        {{ $sched->faculty->full_name }}
                                    </span>
                                    <span class="text-slate-400 dark:text-slate-500 text-xs">🔒</span>
                                </div>

                            @else
                                <span class="text-[12px] italic text-slate-400 dark:text-slate-500">
                                    Unassigned
                                </span>
                            @endif
                        </td>

                        {{-- ── Status Badge ──────────────────────────────── --}}
                        <td class="px-3 py-3 text-center">
                            @php
                                $statusMap = [
                                    \App\Models\Schedule::STATUS_PARTIAL          => [
                                        'label' => 'Partial',
                                        'cls'   => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700',
                                    ],
                                    \App\Models\Schedule::STATUS_FACULTY_ASSIGNED => [
                                        'label' => 'Assigned',
                                        'cls'   => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
                                    ],
                                    \App\Models\Schedule::STATUS_FINALIZED        => [
                                        'label' => 'Finalized',
                                        'cls'   => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700',
                                    ],
                                ];
                                $s = $statusMap[$sched->status]
                                    ?? ['label' => $sched->status, 'cls' => 'bg-slate-100 text-slate-500 border-slate-200'];
                            @endphp

                            <span class="inline-block px-2.5 py-1 rounded-lg text-[12px] font-black
                                         uppercase tracking-wide border {{ $s['cls'] }}">
                                {{ $s['label'] }}
                            </span>

                            @if($sched->has_conflict)
                                <div class="mt-1 text-[11px] font-bold text-red-600 dark:text-red-400 uppercase tracking-tight">
                                    ⚠ Conflict
                                </div>
                            @endif
                        </td>

                    </tr>

                    @if($loop->last)
                            </tbody>
                        </table>
                    @endif

                @empty
                    <div class="py-24 text-center">
                        <div class="inline-flex flex-col items-center gap-4">
                            <div class="text-6xl opacity-20 animate-pulse">📚</div>
                            <h3 class="text-[20px] font-black text-slate-800 dark:text-slate-100
                                       uppercase tracking-tighter">
                                No Schedules Found
                            </h3>
                            <p class="text-[14px] text-slate-400 dark:text-slate-500 max-w-xs">
                                No subjects scheduled for <strong>{{ $departmentName }}</strong>
                                · Year {{ $selectedYear }} · Section {{ $selectedSection }} yet.
                            </p>
                        </div>
                    </div>
                @endforelse

            </div>{{-- /table wrapper --}}

        </div>{{-- /main container --}}
    </div>{{-- /max-w --}}


    {{-- ════════════════════════════════════════════════════════════════════════
         ADMIN PERMISSION PANEL MODAL
         ════════════════════════════════════════════════════════════════════════
         Only rendered when $canManagePermission (Admin) AND $showPermissionPanel.
         Allows Admin to:
           - See the current state (enabled / disabled)
           - Grant to any active Registrar
           - Revoke from a Registrar who currently has it
           - View recent permission audit log
         ════════════════════════════════════════════════════════════════════════ --}}
    @if($canManagePermission && $showPermissionPanel)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
                 wire:click="closePermissionPanel"></div>

            {{-- Panel Card --}}
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl
                         border border-slate-200 dark:border-slate-700
                         w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                {{-- ── Panel Header ─────────────────────────────────────── --}}
                <div class="flex items-start justify-between px-7 py-5
                             border-b border-slate-200 dark:border-slate-700
                             bg-gradient-to-r from-indigo-50/60 to-blue-50/30
                             dark:from-indigo-900/10 dark:to-blue-900/5 flex-shrink-0">
                    <div>
                        <h3 class="text-[18px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight
                                   flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Registrar Finalization Access
                        </h3>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                            Grant <strong>global</strong> finalization access — covers ALL departments, year levels, and sections.
                        </p>
                    </div>
                    <button wire:click="closePermissionPanel"
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200
                                   transition-colors p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 min-h-0">

                    {{-- ── Current Status Banner ────────────────────────── --}}
                    <div class="mx-6 mt-5">
                        @if($registrarWithPermission)
                            {{-- ENABLED state --}}
                            <div class="flex items-center gap-4 px-5 py-4 rounded-xl
                                         bg-emerald-50 dark:bg-emerald-900/20
                                         border-2 border-emerald-300 dark:border-emerald-600">
                                {{-- Animated pulse indicator --}}
                                <div class="flex-shrink-0 relative">
                                    <span class="absolute -inset-1 rounded-full bg-emerald-400/30 animate-ping"></span>
                                    <span class="relative w-4 h-4 rounded-full bg-emerald-500 block"></span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[14px] font-black text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">
                                        Access Enabled
                                    </p>
                                    <p class="text-[13px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                        <strong>{{ $registrarWithPermission->name }}</strong> can finalize schedules across <em>all departments</em>.
                                    </p>
                                </div>
                                {{-- Revoke quick action --}}
                                <button
                                    wire:click="revokeRegistrarPermission({{ $registrarWithPermission->id }})"
                                    wire:confirm="Revoke finalization access from {{ $registrarWithPermission->name }}? They will no longer be able to finalize schedules."
                                    wire:loading.attr="disabled"
                                    class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                           bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                           border border-red-200 dark:border-red-700
                                           hover:bg-red-200 dark:hover:bg-red-900/50 transition-all active:scale-95">
                                    Revoke
                                </button>
                            </div>
                        @else
                            {{-- DISABLED state --}}
                            <div class="flex items-center gap-4 px-5 py-4 rounded-xl
                                         bg-slate-50 dark:bg-slate-800/60
                                         border-2 border-slate-200 dark:border-slate-700">
                                <span class="flex-shrink-0 w-4 h-4 rounded-full bg-slate-300 dark:bg-slate-600 block"></span>
                                <div class="flex-1">
                                    <p class="text-[14px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                                        Access Disabled
                                    </p>
                                    <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Only Administrators can currently finalize schedules.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ── Registrar List ────────────────────────────────── --}}
                    <div class="px-6 mt-5">
                        <p class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                   uppercase tracking-widest mb-3">
                            Active Registrar Accounts
                        </p>

                        @if($allRegistrars->isEmpty())
                            <div class="py-8 text-center text-slate-400 dark:text-slate-500 text-[13px]">
                                No active Registrar accounts found.
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($allRegistrars as $reg)
                                    @php $hasPermission = (bool) $reg->can_finalize_schedule; @endphp
                                    <div @class([
                                            'flex items-center gap-4 px-4 py-3.5 rounded-xl border-2 transition-all',
                                            'border-emerald-300 dark:border-emerald-600 bg-emerald-50/60 dark:bg-emerald-900/10'
                                                => $hasPermission,
                                            'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-slate-300 dark:hover:border-slate-600'
                                                => ! $hasPermission,
                                        ])>

                                        {{-- User avatar --}}
                                        <div @class([
                                                'w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-[14px] uppercase',
                                                'bg-emerald-500 text-white' => $hasPermission,
                                                'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' => ! $hasPermission,
                                            ])>
                                            {{ substr($reg->name, 0, 2) }}
                                        </div>

                                        {{-- User info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-[14px] font-bold text-slate-800 dark:text-slate-100 truncate">
                                                    {{ $reg->name }}
                                                </span>
                                                @if($hasPermission)
                                                    <span class="flex-shrink-0 text-[11px] bg-emerald-500 text-white
                                                                 px-2 py-0.5 rounded font-black uppercase tracking-wide">
                                                        ✓ Access Granted
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                {{ $reg->email }}
                                                @if($reg->department)
                                                    · {{ $reg->department }}
                                                @endif
                                            </p>
                                        </div>

                                        {{-- Grant / Revoke action --}}
                                        @if($hasPermission)
                                            <button
                                                wire:click="revokeRegistrarPermission({{ $reg->id }})"
                                                wire:confirm="Revoke finalization access from {{ $reg->name }}?"
                                                wire:loading.attr="disabled"
                                                class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                                       bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                                       border border-red-200 dark:border-red-700
                                                       hover:bg-red-100 dark:hover:bg-red-900/40 transition-all active:scale-95">
                                                Revoke
                                            </button>
                                        @else
                                            <button
                                                wire:click="grantRegistrarPermission({{ $reg->id }})"
                                                wire:confirm="Grant GLOBAL schedule finalization access to {{ $reg->name }}? They will be able to finalize ALL departments and sections. Any other Registrar's access will be revoked."
                                                wire:loading.attr="disabled"
                                                class="flex-shrink-0 px-3 py-2 rounded-lg text-[12px] font-black uppercase tracking-wider
                                                       bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                                                       border border-blue-200 dark:border-blue-700
                                                       hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-all active:scale-95">
                                                Grant
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ── Audit Log ─────────────────────────────────────── --}}
                    @if($recentPermissionLogs->isNotEmpty())
                        <div class="px-6 mt-6 pb-2">
                            <p class="text-[12px] font-black text-slate-400 dark:text-slate-500
                                       uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Recent Activity
                            </p>
                            <div class="space-y-1.5">
                                @foreach($recentPermissionLogs as $log)
                                    <div class="flex items-start gap-2.5 px-3.5 py-2.5 rounded-lg
                                                 bg-slate-50 dark:bg-slate-800/50
                                                 border border-slate-100 dark:border-slate-700/50">
                                        <span class="text-base flex-shrink-0 mt-0.5">{{ $log->action_icon }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[12px] text-slate-700 dark:text-slate-300 font-semibold truncate">
                                                {{ $log->description ?? $log->action_label }}
                                            </p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                {{ $log->created_at->diffForHumans() }}
                                                @if($log->performer)
                                                    · by {{ $log->performer->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>{{-- /scrollable body --}}

                {{-- ── Panel Footer ──────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-7 py-4 flex-shrink-0
                             border-t border-slate-200 dark:border-slate-700
                             bg-slate-50/80 dark:bg-slate-800/50">
                    <p class="text-[12px] text-slate-400 dark:text-slate-500">
                        Permission is <strong>global</strong> — covers all depts, sections &amp; years. All changes are logged.
                    </p>
                    <button wire:click="closePermissionPanel"
                            class="px-5 py-2.5 bg-slate-200 dark:bg-slate-700
                                   text-slate-700 dark:text-slate-300 font-bold rounded-xl
                                   text-[13px] hover:bg-slate-300 dark:hover:bg-slate-600
                                   transition-all active:scale-95">
                        Close
                    </button>
                </div>

            </div>{{-- /panel card --}}
        </div>{{-- /modal overlay --}}
    @endif


    {{-- ════════════════════════════════════════════════════════════════
         FACULTY ASSIGNMENT MODAL
         ════════════════════════════════════════════════════════════════ --}}
    @if($showFacultyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
                 wire:click="closeFacultyModal"></div>

            {{-- Modal Card --}}
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl
                         border border-slate-200 dark:border-slate-700
                         w-full max-w-2xl max-h-[90vh] flex flex-col
                         overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                {{-- Modal Header --}}
                <div class="flex items-start justify-between px-7 py-5
                             border-b border-slate-200 dark:border-slate-700
                             bg-gradient-to-r from-blue-50/60 to-indigo-50/30
                             dark:from-blue-900/10 dark:to-indigo-900/5 flex-shrink-0">
                    <div>
                        <h3 class="text-[18px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">
                            Assign Faculty
                        </h3>
                        @if($modalSubject)
                            <p class="text-[13px] text-blue-600 dark:text-blue-400 font-semibold mt-1">
                                {{ $modalSubject->subject_code }} — {{ $modalSubject->description }}
                                <span class="text-slate-400 dark:text-slate-500 font-normal ml-1.5">
                                    ({{ $modalSubject->units }}u · {{ strtoupper($modalSubject->type ?? 'Major') }})
                                </span>
                            </p>
                        @endif
                    </div>
                    <button wire:click="closeFacultyModal"
                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200
                                   transition-colors p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Error Banner --}}
                @if($assignError)
                    <div class="flex-shrink-0 mx-6 mt-4 flex items-start gap-2.5
                                 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700
                                 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-[13px] font-medium">
                        <span class="flex-shrink-0 mt-0.5">⛔</span>
                        <span>{{ $assignError }}</span>
                    </div>
                @endif

                {{-- Subject type guidance --}}
                @if($modalSubject)
                    <div class="flex-shrink-0 mx-6 mt-3 px-4 py-2.5 rounded-xl text-[12px]
                                @if(strtolower($modalSubject->type ?? '') === 'minor')
                                    bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-400
                                @else
                                    bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400
                                @endif">
                        @if(strtolower($modalSubject->type ?? '') === 'minor')
                            <strong>Minor/GenEd Subject:</strong> Showing GenEd and minor-eligible faculty only.
                        @else
                            <strong>Major Subject ({{ $modalSubject->department ?? $modalSubject->major }}):</strong>
                            Showing departmental and cross-department faculty for this college.
                        @endif
                    </div>
                @endif

                {{-- Search Input --}}
                <div class="px-6 pt-4 pb-2 flex-shrink-0">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="facultySearch"
                               type="text"
                               placeholder="Search by name or department…"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800
                                      border border-slate-200 dark:border-slate-700 rounded-xl
                                      text-[14px] text-slate-800 dark:text-slate-200
                                      placeholder-slate-400 dark:placeholder-slate-500
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      outline-none transition-all">
                    </div>
                </div>

                {{-- Faculty List --}}
                <div class="overflow-y-auto flex-1 px-6 pb-4 space-y-2 min-h-0 mt-1">

                    @forelse($modalFaculty as $faculty)
                        @php
                            $isCurrentlyAssigned = ($faculty->id === $currentFacultyId);
                            $currentUnits        = $this->getFacultyCurrentUnits($faculty->id);
                            $maxUnits            = $faculty->max_units ?? 21;
                            $newUnits            = (int) ($modalSubject?->units ?? 0);
                            $wouldOverload       = ($currentUnits + $newUnits) > $maxUnits;
                        @endphp

                        <button
                            wire:click="assignFaculty({{ $faculty->id }})"
                            wire:loading.attr="disabled"
                            @class([
                                'w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl transition-all text-left group',
                                'border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    => $isCurrentlyAssigned,
                                'border border-slate-200 dark:border-slate-700 hover:border-blue-400 hover:bg-blue-50/60 dark:hover:bg-blue-900/10 bg-white dark:bg-slate-800/50'
                                    => (! $isCurrentlyAssigned && ! $wouldOverload),
                                'border border-amber-200 dark:border-amber-700/50 bg-amber-50/60 dark:bg-amber-900/5 opacity-75'
                                    => ($wouldOverload && ! $isCurrentlyAssigned),
                            ])>

                            {{-- Avatar --}}
                            <div @class([
                                    'w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-[14px] uppercase',
                                    'bg-blue-500 text-white'                                                           => $isCurrentlyAssigned,
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'     => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'gened'),
                                    'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400'         => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'cross_department'),
                                    'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'                => (! $isCurrentlyAssigned && $faculty->faculty_scope === 'departmental'),
                                ])>
                                {{ substr($faculty->full_name, 0, 2) }}
                            </div>

                            {{-- Name + Department --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[14px] font-bold text-slate-800 dark:text-slate-100 truncate">
                                        {{ $faculty->full_name }}
                                    </span>
                                    @if($isCurrentlyAssigned)
                                        <span class="flex-shrink-0 text-[11px] bg-blue-500 text-white
                                                     px-2 py-0.5 rounded font-black uppercase">
                                            Current
                                        </span>
                                    @endif
                                    @if($wouldOverload)
                                        <span class="flex-shrink-0 text-[11px] bg-amber-100 text-amber-700
                                                     dark:bg-amber-900/30 dark:text-amber-400
                                                     px-2 py-0.5 rounded font-black uppercase border border-amber-200 dark:border-amber-700">
                                            ⚠ Overload
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $faculty->displayDepartment() }}
                                    </span>
                                    <span @class([
                                            'text-[11px] font-bold px-1.5 py-0.5 rounded uppercase',
                                            'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 dark:text-emerald-400'
                                                => $faculty->faculty_scope === 'gened',
                                            'text-violet-600 bg-violet-50 dark:bg-violet-900/20 dark:text-violet-400'
                                                => $faculty->faculty_scope === 'cross_department',
                                            'text-slate-500 bg-slate-100 dark:bg-slate-800 dark:text-slate-400'
                                                => $faculty->faculty_scope === 'departmental',
                                        ])>
                                        {{ $faculty->scopeLabel() }}
                                    </span>
                                </div>
                            </div>

                            {{-- Units Load Bar --}}
                            <div class="flex-shrink-0 text-right min-w-[60px]">
                                <div class="text-[12px] font-bold
                                            @if($wouldOverload) text-amber-600 dark:text-amber-400 @else text-slate-600 dark:text-slate-400 @endif">
                                    {{ $currentUnits + $newUnits }}/{{ $maxUnits }}u
                                </div>
                                <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full mt-1.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all
                                                @if($wouldOverload)
                                                    bg-amber-400
                                                @elseif(($currentUnits + $newUnits) >= $maxUnits * 0.8)
                                                    bg-yellow-400
                                                @else
                                                    bg-emerald-500
                                                @endif"
                                         style="width: {{ min(100, round((($currentUnits + $newUnits) / max(1, $maxUnits)) * 100)) }}%">
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    +{{ $newUnits }}u this subj.
                                </div>
                            </div>

                        </button>

                    @empty
                        <div class="py-10 text-center">
                            <div class="text-4xl opacity-25 mb-3">👤</div>
                            <p class="text-[14px] text-slate-400 dark:text-slate-500">
                                @if(blank($facultySearch))
                                    No eligible faculty found for this subject type.
                                @else
                                    No faculty matching "{{ $facultySearch }}".
                                @endif
                            </p>
                        </div>
                    @endforelse

                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between px-7 py-4 flex-shrink-0
                             border-t border-slate-200 dark:border-slate-700
                             bg-slate-50/80 dark:bg-slate-800/50">
                    <div>
                        @if($currentFacultyId)
                            <button wire:click="removeFacultyAssignment"
                                    wire:confirm="Remove the faculty assignment from this schedule slot?"
                                    class="text-[13px] font-bold text-red-600 dark:text-red-400
                                           hover:text-red-800 dark:hover:text-red-300
                                           flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Remove Assignment
                            </button>
                        @endif
                    </div>

                    <button wire:click="closeFacultyModal"
                            class="px-5 py-2.5 bg-slate-200 dark:bg-slate-700
                                   text-slate-700 dark:text-slate-300 font-bold rounded-xl
                                   text-[13px] hover:bg-slate-300 dark:hover:bg-slate-600
                                   transition-all active:scale-95">
                        Cancel
                    </button>
                </div>

            </div>{{-- /modal card --}}
        </div>{{-- /modal overlay --}}
    @endif

</div>{{-- /root --}}


{{-- ============================================================
     PRINT STYLESHEET
     A4 Landscape · Hides ALL chrome UI · Full-width table
     No blank pages · Proper header repeats on multi-page
     ============================================================ --}}
<style>
    @page {
        size: A4 landscape;
        margin: 8mm 10mm 12mm 10mm;
    }

    @media print {
        aside, nav, header, footer,
        .sidebar, .navbar,
        [class*="sidebar"], [class*="navbar"],
        [class*="nav-bar"], [class*="top-bar"], [class*="topbar"],
        .screen-only, button, [wire\:confirm] {
            display: none !important;
        }

        *, *::before, *::after {
            box-shadow: none !important;
            text-shadow: none !important;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            overflow: visible !important;
            font-family: 'Calibri', 'Arial', sans-serif;
        }

        [wire\:id], [data-livewire], [data-livewire] > div {
            all: unset !important;
            display: block !important;
        }

        .min-h-screen, div[class*="max-w-"], div[class*="space-y-"] {
            min-height: unset !important;
            max-width: 100% !important;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #study-load-container {
            box-shadow: none !important;
            border-radius: 0 !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .print-header {
            background: white !important;
            border-bottom: 3px solid #1e3a5f !important;
            padding: 3mm 5mm 3mm !important;
            display: flex !important;
            align-items: flex-start !important;
            flex-wrap: wrap !important;
            gap: 4mm !important;
        }

        .print-header h1 {
            font-size: 15pt !important;
            color: #1e293b !important;
            font-weight: 900 !important;
            margin: 0 !important;
            letter-spacing: -0.02em !important;
            line-height: 1 !important;
        }

        .print-header h2 {
            font-size: 11pt !important;
            color: #1d4ed8 !important;
            font-weight: 700 !important;
            margin: 2px 0 0 !important;
            line-height: 1.2 !important;
        }

        .print-header span {
            font-size: 8.5pt !important;
            background: transparent !important;
            border: 1px solid #94a3b8 !important;
            color: #475569 !important;
            padding: 1px 5px !important;
            border-radius: 3px !important;
        }

        #schedule-table-wrapper { overflow: visible !important; }

        table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-size: 9pt !important;
            page-break-inside: auto;
        }

        thead { display: table-header-group; }

        thead tr {
            background-color: #1e293b !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        th {
            border: 1px solid #334155 !important;
            padding: 4px 6px !important;
            font-size: 8.5pt !important;
            font-weight: 900 !important;
            text-align: center !important;
            letter-spacing: 0.05em !important;
            color: white !important;
        }

        td {
            border: 1px solid #e2e8f0 !important;
            padding: 4px 6px !important;
            font-size: 9pt !important;
            color: #1e293b !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        td:nth-child(3) { font-size: 10pt !important; font-weight: 900 !important; }

        tbody tr:nth-child(even) td {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        tbody tr:nth-child(odd) td { background-color: #ffffff !important; }

        tr { page-break-inside: avoid; }

        td span, td div, td button {
            background: transparent !important;
            border: none !important;
            color: inherit !important;
            padding: 0 !important;
            border-radius: 0 !important;
            font-size: inherit !important;
            font-weight: inherit !important;
            display: inline !important;
            box-shadow: none !important;
        }

        td:nth-child(1) span { color: #1d4ed8 !important; font-weight: 900 !important; }
        td:nth-child(7) span { color: #1d4ed8 !important; font-weight: 700 !important; }

        td:nth-child(8) button, td:nth-child(8) span {
            display: inline !important;
            color: #1e293b !important;
            font-size: 9pt !important;
            font-weight: 600 !important;
        }

        th:last-child, td:last-child { display: none !important; }
    }
</style>