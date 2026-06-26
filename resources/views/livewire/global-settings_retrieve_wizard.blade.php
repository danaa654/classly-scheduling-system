@if($showRetrieveModal)
<div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-2xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">

        <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">
            Semester Roll-Forward
        </h3>

        {{-- Already-retrieved guard --}}
        @if($alreadyRetrievedCurrentTerm)
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                <p class="text-xs font-bold uppercase tracking-widest text-red-700 dark:text-red-300">🚫 Already Retrieved This Semester</p>
                <p class="mt-1 text-xs font-semibold text-red-800 dark:text-red-200">
                    A retrieval has already been performed for the current term.
                    You can only retrieve once per semester. Please end the semester first.
                </p>
            </div>
            <div class="mt-6 flex">
                <button type="button" wire:click="$set('showRetrieveModal', false)"
                    class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Close
                </button>
            </div>

        @else

            {{-- Source archive info --}}
            @if($matchingArchive)
                <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Auto-Detected Archive</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Source Archive</p>
                            <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">
                                {{ $matchingArchive['semester_name'] ?? \App\Models\Setting::semesterDisplayName($matchingArchive['semester'], $matchingArchive['school_year']) }}
                            </p>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $matchingArchive['total_subjects'] }} subjects · {{ $matchingArchive['total_schedules'] }} schedules
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Target Workspace</p>
                            <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">
                                {{ \App\Models\Setting::semesterLabel($semester) }} {{ $school_year }}
                            </p>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $currentEdpPrefix }} prefix
                            </p>
                        </div>
                    </div>

                    @if($workspaceOccupancy['is_occupied'])
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                            <p class="text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-300">⚠️ Workspace Already Contains Data</p>
                            <p class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-200">
                                {{ $workspaceOccupancy['subject_count'] }} subjects, {{ $workspaceOccupancy['schedule_count'] }} schedules —
                                existing records will be updated, not duplicated.
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="text-sm font-bold text-amber-800 dark:text-amber-200">
                        No matching archive found for {{ \App\Models\Setting::semesterLabel($semester) }}.
                    </p>
                </div>
            @endif

            {{-- ── Retrieve Mode Cards ── --}}
            <div class="mt-5" x-data>
                <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    Retrieve Mode
                </p>
                <div class="grid gap-3 sm:grid-cols-2">

                    {{-- 1 — Subjects Only (DEFAULT) --}}
                    <label class="relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition
                        {{ $retrieveMode === 'subjects_only'
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-slate-600' }}">
                        <input type="radio" wire:model.live="retrieveMode" value="subjects_only" class="sr-only">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">📋</span>
                            @if($retrieveMode === 'subjects_only')
                                <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-white">Selected</span>
                            @endif
                        </div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Subjects Only</p>
                        <p class="text-[11px] font-semibold leading-relaxed text-slate-500 dark:text-slate-400">
                            Imports all subjects with their full metadata. Faculty, rooms, and schedule slots start completely empty.
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Subjects</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Faculty</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Rooms</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Timeslots</span>
                        </div>
                    </label>

                    {{-- 2 — Keep Faculty Assignments --}}
                    <label class="relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition
                        {{ $retrieveMode === 'keep_faculty'
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-slate-600' }}">
                        <input type="radio" wire:model.live="retrieveMode" value="keep_faculty" class="sr-only">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">👨‍🏫</span>
                            @if($retrieveMode === 'keep_faculty')
                                <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-white">Selected</span>
                            @endif
                        </div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Keep Faculty Assignments</p>
                        <p class="text-[11px] font-semibold leading-relaxed text-slate-500 dark:text-slate-400">
                            Subjects and their assigned instructors are carried over. Room assignments and timeslots start fresh.
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Subjects</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Faculty</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Rooms</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Timeslots</span>
                        </div>
                    </label>

                    {{-- 3 — Keep Faculty & Room Assignments --}}
                    <label class="relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition
                        {{ $retrieveMode === 'keep_faculty_room'
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-slate-600' }}">
                        <input type="radio" wire:model.live="retrieveMode" value="keep_faculty_room" class="sr-only">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">🏫</span>
                            @if($retrieveMode === 'keep_faculty_room')
                                <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-white">Selected</span>
                            @endif
                        </div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Keep Faculty & Room Assignments</p>
                        <p class="text-[11px] font-semibold leading-relaxed text-slate-500 dark:text-slate-400">
                            Subjects, instructors, and preferred rooms are preserved. Timeslots are left empty for the auto-scheduler.
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Subjects</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Faculty</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Rooms</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-400 dark:bg-slate-800 dark:text-slate-500">✗ Timeslots</span>
                        </div>
                    </label>

                    {{-- 4 — Complete Semester Clone --}}
                    <label class="relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition
                        {{ $retrieveMode === 'clone_timetable'
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-slate-600' }}">
                        <input type="radio" wire:model.live="retrieveMode" value="clone_timetable" class="sr-only">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">🗓️</span>
                            @if($retrieveMode === 'clone_timetable')
                                <span class="rounded-full bg-blue-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-white">Selected</span>
                            @endif
                        </div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Complete Semester Clone</p>
                        <p class="text-[11px] font-semibold leading-relaxed text-slate-500 dark:text-slate-400">
                            Recreates the previous semester as accurately as possible including all timeslots.
                            A compatibility check runs first to surface any configuration differences.
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">✓ Subjects</span>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">✓ Faculty</span>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">✓ Rooms</span>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">✓ Timeslots</span>
                        </div>
                    </label>

                </div>

                <p class="mt-3 text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                    New EDP codes are always generated. All retrieved data remains fully editable after import.
                    Modes without timeslots leave the scheduling board empty for auto-generation.
                </p>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button type="button"
                    wire:click="proceedToRetrieveConfirmation"
                    @disabled(!$matchingArchive)
                    wire:loading.attr="disabled"
                    class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition
                        {{ $matchingArchive ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-400' }}">
                    <span wire:loading.remove wire:target="proceedToRetrieveConfirmation">
                        {{ $retrieveMode === 'clone_timetable' ? 'Check Compatibility & Proceed' : 'Review & Proceed' }}
                    </span>
                    <span wire:loading wire:target="proceedToRetrieveConfirmation">Checking…</span>
                </button>
                <button type="button" wire:click="$set('showRetrieveModal', false)"
                    class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Cancel
                </button>
            </div>

        @endif {{-- alreadyRetrievedCurrentTerm --}}
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 2 — Compatibility Report (COMPLETE_CLONE only)
══════════════════════════════════════════════════════════════════════════════ --}}
@if($showCompatibilityStep && $compatibilityReport)
<div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-2xl rounded-lg border border-amber-200 bg-white p-6 shadow-2xl dark:border-amber-900 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">

        <h3 class="text-lg font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">
            ⚠️ Compatibility Report
        </h3>
        <p class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
            Differences were found between the archived semester and the current configuration.
            Choose how to proceed before cloning the timetable.
        </p>

        {{-- Config Differences --}}
        @if(!empty($compatibilityReport['config_differences']))
            <div class="mt-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Configuration Differences</p>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left pb-1 font-black text-slate-600 dark:text-slate-300">Field</th>
                            <th class="text-left pb-1 font-black text-slate-600 dark:text-slate-300">Archived</th>
                            <th class="text-left pb-1 font-black text-slate-600 dark:text-slate-300">Current</th>
                            <th class="text-left pb-1 font-black text-slate-600 dark:text-slate-300">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compatibilityReport['config_differences'] as $diff)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-1.5 font-semibold text-slate-700 dark:text-slate-200">{{ $diff['label'] }}</td>
                                <td class="py-1.5 text-slate-600 dark:text-slate-300">{{ $diff['archived'] }}</td>
                                <td class="py-1.5 text-slate-600 dark:text-slate-300">{{ $diff['current'] }}</td>
                                <td class="py-1.5">
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        {{ $diff['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Inactive Days --}}
        @if(!empty($compatibilityReport['inactive_days']))
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Days Now Inactive</p>
                <p class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-200">
                    {{ collect($compatibilityReport['inactive_days'])->map('ucfirst')->implode(', ') }}
                    — schedules on {{ count($compatibilityReport['inactive_days']) === 1 ? 'this day' : 'these days' }}
                    will be flagged as <strong>Needs Review</strong>.
                </p>
            </div>
        @endif

        {{-- Missing Faculty --}}
        @if(!empty($compatibilityReport['missing_faculty_ids']))
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-red-700 dark:text-red-300">Missing or Inactive Faculty</p>
                <p class="mt-1 text-xs font-semibold text-red-800 dark:text-red-200">
                    {{ count($compatibilityReport['missing_faculty_ids']) }} faculty member(s) from the archive no longer exist or are inactive.
                    Their assignments will be cleared on import.
                </p>
            </div>
        @endif

        {{-- Missing Rooms --}}
        @if(!empty($compatibilityReport['missing_room_ids']))
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-red-700 dark:text-red-300">Missing Rooms</p>
                <p class="mt-1 text-xs font-semibold text-red-800 dark:text-red-200">
                    {{ count($compatibilityReport['missing_room_ids']) }} room(s) from the archive no longer exist.
                    Affected schedules will be flagged as <strong>Needs Review</strong>.
                </p>
            </div>
        @endif

        {{-- Out-of-bounds count --}}
        @if(!empty($compatibilityReport['out_of_bounds']))
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Schedules Outside Configured Hours</p>
                <p class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-200">
                    {{ count($compatibilityReport['out_of_bounds']) }} schedule(s) fall outside the current semester's time window.
                </p>
            </div>
        @endif

        {{-- Resolution options --}}
        <div class="mt-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-3">How would you like to proceed?</p>

            <div class="flex flex-col gap-3">
                {{-- Option 1: Use Archived Configuration --}}
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                    {{ $compatibilityResolution === 'use_archived'
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600' }}">
                    <input type="radio" wire:model.live="compatibilityResolution" value="use_archived" class="mt-0.5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Use Archived Configuration</p>
                        <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            Automatically update the current semester configuration (start time, end time, active days)
                            to match the archived semester before cloning. The timetable will import cleanly.
                        </p>
                    </div>
                </label>

                {{-- Option 2: Keep Current Configuration --}}
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition
                    {{ $compatibilityResolution === 'keep_current'
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600' }}">
                    <input type="radio" wire:model.live="compatibilityResolution" value="keep_current" class="mt-0.5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Keep Current Configuration</p>
                        <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            Clone the timetable as-is. Schedules that conflict with the current configuration
                            will be imported and flagged as <strong class="text-amber-700 dark:text-amber-300">Needs Review</strong>
                            — they will NOT be deleted or moved automatically.
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button type="button" wire:click="resolveCompatibility('{{ $compatibilityResolution }}')"
                class="flex-1 rounded-md bg-emerald-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                Continue with This Choice
            </button>
            <button type="button" wire:click="resolveCompatibility('cancel')"
                class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                Cancel Retrieval
            </button>
        </div>

    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 3 — Final Confirmation Modal
══════════════════════════════════════════════════════════════════════════════ --}}
@if($showRetrieveConfirmation)
<div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
    <div class="w-full max-w-2xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">

        <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">
            Confirm Retrieval
        </h3>
        <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
            You are about to copy archived data into the current workspace.
        </p>

        @if($matchingArchive)
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Source Archive</p>
                    <p class="mt-2 text-sm font-black text-slate-800 dark:text-slate-100">
                        {{ $matchingArchive['semester_name'] ?? \App\Models\Setting::semesterDisplayName($matchingArchive['semester'], $matchingArchive['school_year']) }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $matchingArchive['total_subjects'] }} Subjects</p>
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $matchingArchive['total_schedules'] }} Schedules</p>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Target Workspace</p>
                    <p class="mt-2 text-sm font-black text-slate-800 dark:text-slate-100">
                        {{ \App\Models\Setting::semesterLabel($semester) }} {{ $school_year }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">EDP Prefix: {{ $currentEdpPrefix }}</p>
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                        Mode: @php
                            $labels = \App\Services\Retrieve\RetrieveMode::LABELS;
                            echo $labels[$retrieveMode] ?? str_replace('_', ' ', ucwords($retrieveMode, '_'));
                        @endphp
                    </p>
                    @if($retrieveMode === 'clone_timetable')
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                            Conflict resolution:
                            {{ $compatibilityResolution === 'use_archived' ? 'Apply archived config' : 'Keep current config (flag conflicts)' }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

        @if($workspaceOccupancy['is_occupied'])
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-sm font-bold text-amber-700 dark:text-amber-300">⚠️ Workspace Contains Existing Data</p>
                <p class="mt-2 text-xs font-semibold text-amber-800 dark:text-amber-200">
                    {{ $workspaceOccupancy['subject_count'] }} subjects and {{ $workspaceOccupancy['schedule_count'] }} schedules already exist.
                    Matching records will be updated; new records will be created.
                </p>
            </div>
        @endif

        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            <input type="checkbox" wire:model.live="archiveAcknowledged"
                class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
            <span>
                I understand that archived records remain unchanged and this action only copies data into the current workspace.
                All retrieved data will be fully editable.
            </span>
        </label>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button type="button"
                wire:click="retrieveArchivedSemester"
                @disabled(!$archiveAcknowledged)
                wire:loading.attr="disabled"
                class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition
                    {{ $archiveAcknowledged ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-400' }}">
                <span wire:loading.remove wire:target="retrieveArchivedSemester">Confirm & Retrieve</span>
                <span wire:loading wire:target="retrieveArchivedSemester">Retrieving…</span>
            </button>
            <button type="button" wire:click="$set('showRetrieveConfirmation', false)"
                class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                Back
            </button>
        </div>

    </div>
</div>
@endif