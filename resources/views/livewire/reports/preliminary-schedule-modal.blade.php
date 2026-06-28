<div
    x-on:open-preliminary-schedule.window="$wire.openModal()"
    x-on:keydown.escape.window="$wire.closeModal()"
    class="preliminary-schedule-modal-root"
>
{{-- ═══════════════════════════════════════════════════════════════
     MODAL OVERLAY
═══════════════════════════════════════════════════════════════ --}}
@if($showModal)
<div
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    x-data
    x-cloak
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        wire:click="closeModal"
    ></div>

    {{-- Modal container --}}
    <div class="relative z-10 w-full max-w-[1400px] h-[92vh] bg-white dark:bg-slate-900
                flex flex-col rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700
                overflow-hidden">

        {{-- ── MODAL HEADER ──────────────────────────────────────────── --}}
        <div class="flex items-center justify-between px-6 py-4
                    bg-gradient-to-r from-violet-700 to-indigo-700
                    dark:from-violet-800 dark:to-indigo-800 shrink-0 print:hidden">

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-white font-black text-sm uppercase tracking-widest">Preliminary Schedule</h2>
                    <p class="text-violet-200 text-[10px] font-medium">
                        {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }}
                        {{ $activePeriod['school_year'] }}
                    </p>
                </div>
                <span class="ml-2 rounded-lg bg-amber-400/20 border border-amber-300/30 px-2 py-0.5
                             text-[9px] font-black uppercase tracking-widest text-amber-200">
                    ⚠ Pre-Enrollment Draft
                </span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Print controls (only shown after a report is generated) --}}
                @if($hasGenerated && !empty($reportData['rows']))

                    {{-- ── PRINT SCOPE TOGGLE ──────────────────────────────
                         Lets the user choose whether to print only the first/
                         current group, or every group that matches the filters.
                         The selection is persisted in `$printScope` on the
                         Livewire component so the print template can react.
                    ─────────────────────────────────────────────────────── --}}
                    <div class="flex items-center rounded-xl border border-white/20 overflow-hidden text-[10px]
                                font-black uppercase tracking-widest">
                        <button
                            wire:click="$set('printScope', 'all')"
                            class="px-3 py-1.5 transition
                                   {{ $printScope === 'all'
                                       ? 'bg-white text-violet-700'
                                       : 'bg-white/15 hover:bg-white/25 text-white' }}">
                            All ({{ $reportSummary['total_groups'] }})
                        </button>
                        <button
                            wire:click="$set('printScope', 'current')"
                            class="px-3 py-1.5 transition
                                   {{ $printScope === 'current'
                                       ? 'bg-white text-violet-700'
                                       : 'bg-white/15 hover:bg-white/25 text-white' }}">
                            Current
                        </button>
                    </div>

                    {{-- Print button --}}
                    <button
                        onclick="window.print()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white/15 hover:bg-white/25
                               text-white rounded-xl font-black text-[10px] uppercase tracking-widest
                               transition border border-white/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>

                    {{-- Export PDF — reuses the browser's native print dialog so we
                         don't need an external PDF library. The person just picks
                         "Save as PDF" as the print destination. --}}
                    <button
                        onclick="window.print()"
                        title="Opens the print dialog — choose 'Save as PDF' as the destination"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white/15 hover:bg-white/25
                               text-white rounded-xl font-black text-[10px] uppercase tracking-widest
                               transition border border-white/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v11m0 0l-3.5-3.5M12 14l3.5-3.5M5 17v2a2 2 0 002 2h10a2 2 0 002-2v-2"/>
                        </svg>
                        Export PDF
                    </button>
                @endif

                {{-- Close button --}}
                <button
                    wire:click="closeModal"
                    class="w-8 h-8 rounded-xl bg-white/15 hover:bg-white/25 text-white
                           flex items-center justify-center transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── MODAL BODY ─────────────────────────────────────────────── --}}
        <div class="flex flex-1 overflow-hidden print:block print:overflow-visible">

            {{-- LEFT SIDEBAR — Filters ─────────────────────────────── --}}
            <aside class="w-64 shrink-0 border-r border-slate-200 dark:border-slate-700
                          bg-slate-50 dark:bg-slate-800/60 flex flex-col overflow-y-auto
                          print:hidden">

                <div class="p-4 space-y-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">
                        Report Filters
                    </p>

                    {{-- Semester (read-only) --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Semester
                        </label>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 border border-slate-200
                                    dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                    text-slate-700 dark:text-slate-200 opacity-70 select-none">
                            {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }}
                            {{ $activePeriod['school_year'] }}
                        </div>
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Department
                        </label>
                        <select
                            wire:model.live="filterDept"
                            @if(!$isPowerUser) disabled @endif
                            class="w-full bg-white dark:bg-slate-700 border border-slate-200
                                   dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                   text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500
                                   transition-all {{ !$isPowerUser ? 'opacity-60 cursor-not-allowed' : '' }}">
                            @if($isPowerUser)
                                <option value="">All Departments</option>
                            @endif
                            <option value="CCS">CCS</option>
                            <option value="SHTM">SHTM</option>
                            <option value="COC">COC</option>
                            <option value="CTE">CTE</option>
                        </select>
                    </div>

                    {{-- Major / Program --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Program / Major
                        </label>
                        <select
                            wire:model.live="filterMajor"
                            class="w-full bg-white dark:bg-slate-700 border border-slate-200
                                   dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                   text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500
                                   transition-all">
                            <option value="">All Programs</option>
                            @foreach($availableMajors as $major)
                                <option value="{{ $major }}">{{ $major }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Year Level --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Year Level
                        </label>
                        <select
                            wire:model.live="filterYearLevel"
                            class="w-full bg-white dark:bg-slate-700 border border-slate-200
                                   dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                   text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500
                                   transition-all">
                            <option value="">All Year Levels</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    {{-- Section --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Section
                        </label>
                        <select
                            wire:model.live="filterSection"
                            class="w-full bg-white dark:bg-slate-700 border border-slate-200
                                   dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                   text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500
                                   transition-all">
                            <option value="">All Sections</option>
                            @foreach($availableSections as $section)
                                <option value="{{ $section }}">Section {{ $section }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Subject Type --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1.5">
                            Subject Type
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="filterTypes"
                                    value="Major"
                                    class="w-3.5 h-3.5 shrink-0 accent-violet-600 cursor-pointer">
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                                    Major Subjects
                                </span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="filterTypes"
                                    value="Minor"
                                    class="w-3.5 h-3.5 shrink-0 accent-violet-600 cursor-pointer">
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-200">
                                    Minor Subjects
                                </span>
                            </label>
                        </div>
                        @if(empty($filterTypes))
                            <p class="mt-1 text-[9px] text-red-500 font-black uppercase">
                                ⚠ Select at least one type
                            </p>
                        @endif
                    </div>

                    {{-- Schedule Status --}}
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">
                            Schedule Status
                        </label>
                        <select
                            wire:model.live="filterStatus"
                            class="w-full bg-white dark:bg-slate-700 border border-slate-200
                                   dark:border-slate-600 rounded-xl px-3 py-2 text-xs font-semibold
                                   text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500
                                   transition-all">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @if($value !== 'Preliminary') disabled @endif>
                                    {{ $label }}{{ $value !== 'Preliminary' ? ' — coming soon' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-slate-200 dark:border-slate-600"></div>

                    {{-- Generate Button --}}
                    <button
                        wire:click="generateReport"
                        wire:loading.attr="disabled"
                        wire:target="generateReport"
                        @if(empty($filterTypes)) disabled @endif
                        class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl
                               font-black text-[11px] uppercase tracking-widest shadow-md
                               shadow-violet-500/30 transition-all active:scale-95
                               disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="generateReport">
                            ↻ Generate Report
                        </span>
                        <span wire:loading wire:target="generateReport"
                              class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Generating...
                        </span>
                    </button>

                    {{-- Stats (shown after generation) --}}
                    @if($hasGenerated && !empty($reportData['meta']))
                        @php $meta = $reportData['meta']; @endphp
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 font-medium">Schedule Groups</span>
                                <span class="font-black text-slate-700 dark:text-slate-200">
                                    {{ $reportSummary['total_groups'] }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 font-medium">Total Subjects</span>
                                <span class="font-black text-slate-700 dark:text-slate-200">
                                    {{ $meta['total'] }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 font-medium">Fully Assigned</span>
                                <span class="font-black text-emerald-600">{{ $meta['ready_count'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 font-medium">Has TBA Fields</span>
                                <span class="font-black text-amber-500">{{ $meta['tba_count'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 font-medium">Total Units</span>
                                <span class="font-black text-violet-600">{{ $reportSummary['total_units'] }}</span>
                            </div>
                            <div class="text-[8px] text-slate-400 pt-1 leading-tight">
                                Generated {{ $meta['generated_at'] }}
                            </div>
                        </div>
                    @endif
                </div>
            </aside>

            {{-- RIGHT PANEL — Report Preview ─────────────────────── --}}
            <div class="flex-1 overflow-y-auto bg-slate-100 dark:bg-slate-950
                        print:bg-white print:overflow-visible">

                {{-- ── PRINTABLE REPORT AREA ──────────────────────── --}}
                <div id="preliminary-schedule-print-area"
                     class="min-h-full p-6 print:p-0">

                    {{-- Loading overlay --}}
                    <div wire:loading wire:target="generateReport,filterDept,filterMajor,filterYearLevel,filterSection,filterTypes,filterStatus"
                         class="flex items-center justify-center h-64 print:hidden">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 mx-auto text-violet-500 mb-3"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm font-semibold text-slate-500">Generating report…</p>
                        </div>
                    </div>

                    {{-- Empty state --}}
                    @if(!$hasGenerated && !$isGenerating)
                        <div wire:loading.remove wire:target="generateReport"
                             class="flex items-center justify-center h-64 print:hidden">
                            <div class="text-center max-w-xs">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-violet-100 dark:bg-violet-900/30
                                            flex items-center justify-center">
                                    <svg class="w-8 h-8 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-black text-slate-700 dark:text-slate-200 mb-1">
                                    No Preview Yet
                                </p>
                                <p class="text-xs text-slate-400">
                                    Configure your filters and click <strong>Generate Report</strong> to preview the preliminary schedule.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- No results state (filters returned nothing from the database) --}}
                    @if($hasGenerated && empty($reportData['rows']))
                        <div class="flex items-center justify-center h-64 print:hidden">
                            <div class="text-center">
                                <p class="text-sm font-black text-slate-600 dark:text-slate-300 mb-1">
                                    No schedules match the selected filters
                                </p>
                                <p class="text-xs text-slate-400">
                                    Try adjusting your filters above.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Search bar --}}
                    @if($hasGenerated && !empty($reportData['rows']))
                        <div class="sticky top-0 z-10 mb-4 print:hidden">
                            <div class="flex items-center gap-2.5 bg-white dark:bg-slate-800
                                        border border-slate-200 dark:border-slate-700 rounded-xl
                                        px-3.5 py-2.5 shadow-sm">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                                </svg>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Search by code, subject, faculty, or section…"
                                    class="flex-1 bg-transparent text-xs font-semibold text-slate-700
                                           dark:text-slate-200 placeholder:text-slate-400 placeholder:font-medium
                                           focus:outline-none">
                                @if($search !== '')
                                    <button wire:click="clearSearch"
                                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200
                                                   transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400
                                             shrink-0 pl-2 border-l border-slate-200 dark:border-slate-600">
                                    {{ $searchResultCount }} / {{ $reportData['meta']['total'] ?? 0 }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════════════════════════════════════
                         PRINTABLE REPORT
                         ─────────────────────────────────────────────────
                         ARCHITECTURE NOTE
                         ─────────────────────────────────────────────────
                         The old design wrapped ALL groups inside ONE report
                         card that had a single header at the top. When the
                         browser printed it, only what fit on the first page
                         was rendered because the fixed-height modal clipped
                         the overflow — the card never actually scrolled into
                         the print buffer.

                         The new design renders EACH schedule group as its own
                         SELF-CONTAINED DOCUMENT (`.print-page`). Every page
                         has:
                           1. Its own institution header
                           2. Its own group header (dept / program / year / section)
                           3. Its own subject table
                           4. Its own footer with generated-by info

                         In screen view the pages are shown stacked with
                         subtle card styling. In print view each `.print-page`
                         gets a hard `break-after: page` so the browser
                         produces exactly N pages for N schedule groups.

                         The `$printableGroups` computed property on the
                         Livewire component controls which groups are output:
                           - printScope = 'all'     → every group
                           - printScope = 'current' → only the first group
                    ════════════════════════════════════════════════════ --}}
                    @if($hasGenerated && !empty($reportData['rows']))
                    <div wire:loading.remove wire:target="generateReport">

                        {{-- SCREEN-ONLY: report-level summary card (not printed,
                             replaced by per-page headers in the print output) --}}
                        <div class="mb-4 print:hidden bg-white dark:bg-slate-800 rounded-2xl shadow-sm
                                    border border-slate-200 dark:border-slate-700 px-8 pt-6 pb-4 text-center">

                            <div class="flex items-center justify-center gap-3 mb-3">
                                <div class="w-12 h-12 rounded-full bg-violet-100 dark:bg-violet-900/40
                                            border-2 border-violet-300 flex items-center justify-center">
                                    <span class="text-lg font-black text-violet-700">P</span>
                                </div>
                                <div class="text-left">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        Professional Academy of the Philippines
                                    </p>
                                    <p class="text-xs font-black uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                        CLASSLY — Academic Scheduling System
                                    </p>
                                </div>
                            </div>

                            <h1 class="text-xl font-black uppercase tracking-widest text-slate-800 dark:text-slate-100">
                                Preliminary Class Schedule
                            </h1>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }}
                                &nbsp;·&nbsp;
                                A.Y. {{ $activePeriod['school_year'] }}
                            </p>

                            @php $meta = $reportData['meta']; @endphp
                            <div class="mt-4 mx-auto max-w-2xl rounded-xl border border-slate-200
                                        dark:border-slate-600 overflow-hidden">
                                <div class="grid grid-cols-4 divide-x divide-slate-200 dark:divide-slate-600
                                            bg-slate-50 dark:bg-slate-700/40">
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Department</p>
                                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-0.5 truncate">
                                            {{ $filterDept ?: 'All' }}
                                        </p>
                                    </div>
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Program</p>
                                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-0.5 truncate">
                                            {{ $filterMajor ?: 'All' }}
                                        </p>
                                    </div>
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Year</p>
                                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-0.5 truncate">
                                            {{ $filterYearLevel ? \App\Services\ScheduleReportService::yearLabel($filterYearLevel) : 'All' }}
                                        </p>
                                    </div>
                                    <div class="px-3 py-2.5 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Section</p>
                                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-0.5 truncate">
                                            {{ $filterSection ? 'Section '.$filterSection : 'All' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-600
                                            bg-white dark:bg-slate-800">
                                    <div class="px-3 py-2 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Schedule Groups</p>
                                        <p class="text-base font-black text-violet-700 dark:text-violet-400">
                                            {{ $reportSummary['total_groups'] }}
                                        </p>
                                    </div>
                                    <div class="px-3 py-2 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Total Subjects</p>
                                        <p class="text-base font-black text-slate-800 dark:text-slate-100">
                                            {{ $searchResultCount }}
                                            @if($search !== '' && $searchResultCount !== $meta['total'])
                                                <span class="text-xs font-semibold text-slate-400">/ {{ $meta['total'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="px-3 py-2 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Total Units</p>
                                        <p class="text-base font-black text-slate-800 dark:text-slate-100">
                                            {{ $reportSummary['total_units'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-amber-50
                                        dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-3 py-1.5">
                                <span class="text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">
                                    ⚠ PRELIMINARY
                                </span>
                                <span class="text-[9px] text-amber-600 dark:text-amber-400">
                                    — Subject to change without prior notice
                                </span>
                            </div>

                            {{-- Print scope hint --}}
                            <p class="mt-3 text-[10px] text-slate-400">
                                Print scope:
                                <strong class="text-violet-600">
                                    {{ $printScope === 'all'
                                        ? 'All '.$reportSummary['total_groups'].' schedule group(s)'
                                        : 'Current group only' }}
                                </strong>
                                — change above before clicking Print
                            </p>
                        </div>

                        {{-- No search results --}}
                        @if(empty($groupedRows))
                            <div class="flex flex-col items-center justify-center py-16 print:hidden">
                                <p class="text-sm font-black text-slate-600 dark:text-slate-300 mb-1">
                                    No results match your search
                                </p>
                                <p class="text-xs text-slate-400 mb-3">
                                    Nothing found for "{{ $search }}". Try a different code, name, faculty, or section.
                                </p>
                                <button wire:click="clearSearch"
                                        class="text-[11px] font-black uppercase tracking-widest
                                               text-violet-600 hover:text-violet-700">
                                    Clear Search
                                </button>
                            </div>
                        @endif

                        {{-- ════════════════════════════════════════════════════
                             SCHEDULE GROUPS
                             Each group is a self-contained "print page".
                             Screen: shown as stacked cards.
                             Print:  each gets break-after:page so the browser
                                     outputs one page per schedule group.

                             We iterate $printableGroups (not $groupedRows)
                             so that the "Current" scope only outputs one group
                             to the print buffer, while the preview still shows
                             all groups using $groupedRows below.
                        ════════════════════════════════════════════════════ --}}

                        {{-- PRINT OUTPUT — driven by $printableGroups --}}
                        {{-- CRITICAL: never use display:none here. display:none removes the subtree
                             from the render tree entirely, so break-before:page never fires and the
                             browser only sees 1 page. Instead we use .print-output-block which keeps
                             the element in the render tree on screen (height:0, overflow:hidden,
                             position:absolute so it takes no visual space) and restores it in print. --}}
                        <div class="print-output-block">
                            @foreach($printableGroups as $groupIndex => $group)
                            {{-- .print-page: each iteration = one printed page --}}
                            <div class="print-page{{ $groupIndex > 0 ? ' print-page--break' : '' }}">

                                {{-- ── PRINT PAGE HEADER (repeats on every page) ── --}}
                                <div class="print-page-header">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full border-2 border-slate-400
                                                    bg-slate-100 flex items-center justify-center shrink-0">
                                            <span class="text-base font-black text-slate-700">P</span>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-600">
                                                Professional Academy of the Philippines
                                            </p>
                                            <p class="text-[11px] font-black uppercase tracking-wider text-black">
                                                CLASSLY — Academic Scheduling System
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Document Status</p>
                                        <span class="inline-block text-[9px] font-black uppercase tracking-widest
                                                     px-2 py-0.5 bg-amber-100 text-amber-700">
                                            ⚠ PRELIMINARY
                                        </span>
                                        <p class="text-[8px] text-slate-500 mt-0.5">Subject to change without prior notice</p>
                                    </div>
                                </div>

                                {{-- ── PRINT REPORT TITLE ── --}}
                                <div class="text-center my-3">
                                    <h1 class="text-base font-black uppercase tracking-widest text-black">
                                        Preliminary Class Schedule
                                    </h1>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }}
                                        &nbsp;·&nbsp;
                                        A.Y. {{ $activePeriod['school_year'] }}
                                    </p>
                                </div>

                                {{-- ── GROUP IDENTITY BLOCK ── --}}
                                <div class="print-group-header">
                                    <div class="grid grid-cols-4 gap-0 border border-slate-300">
                                        <div class="px-3 py-2 border-r border-slate-300">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Department</p>
                                            <p class="text-[12px] font-black text-black mt-0.5">{{ $group['department'] }}</p>
                                        </div>
                                        <div class="px-3 py-2 border-r border-slate-300">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Program</p>
                                            <p class="text-[12px] font-black text-black mt-0.5">{{ $group['major'] ?: '—' }}</p>
                                        </div>
                                        <div class="px-3 py-2 border-r border-slate-300">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Year Level</p>
                                            <p class="text-[12px] font-black text-black mt-0.5">{{ $group['year_label'] }}</p>
                                        </div>
                                        <div class="px-3 py-2">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Section</p>
                                            <p class="text-[12px] font-black text-black mt-0.5">Section {{ $group['section'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- ── SUBJECT TABLE ── --}}
                                <table class="w-full text-xs border-collapse border border-slate-300 mt-2">
                                    <thead>
                                        <tr class="bg-slate-100">
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[80px]">Code</th>
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300">Subject / Description</th>
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[130px]">Faculty</th>
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[80px]">Day</th>
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[110px]">Time</th>
                                            <th class="py-1.5 px-2 text-left text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[80px]">Room</th>
                                            <th class="py-1.5 px-2 text-center text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[40px]">Units</th>
                                            <th class="py-1.5 px-2 text-center text-[9px] font-black uppercase tracking-widest
                                                       text-slate-600 border border-slate-300 w-[70px]">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group['rows'] as $i => $row)
                                        <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' }} border-b border-slate-200">
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                <p class="font-black text-[10px] text-black leading-tight">{{ $row['subject_code'] }}</p>
                                                <p class="text-[8px] text-slate-500 leading-tight mt-0.5">{{ $row['edp_code'] }}</p>
                                            </td>
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                <p class="font-semibold text-[10px] text-black leading-snug">{{ $row['description'] }}</p>
                                                <span class="inline-block mt-0.5 text-[7px] font-black uppercase tracking-wider px-1 py-0.5
                                                             {{ $row['type'] === 'Major' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                                    {{ $row['type'] }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                @if($row['faculty'] === 'TBA')
                                                    <span class="text-[9px] font-black text-amber-700">TBA</span>
                                                @else
                                                    <span class="text-[10px] text-black leading-snug">{{ $row['faculty'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                @if($row['day'] === 'TBA')
                                                    <span class="text-[9px] font-black text-amber-700">TBA</span>
                                                @else
                                                    <span class="text-[10px] text-black">{{ $row['day'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                @if($row['time'] === 'TBA')
                                                    <span class="text-[9px] font-black text-amber-700">TBA</span>
                                                @else
                                                    <span class="text-[10px] text-black">{{ $row['time'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-2 align-top border border-slate-200">
                                                @if($row['room'] === 'TBA')
                                                    <span class="text-[9px] font-black text-amber-700">TBA</span>
                                                @else
                                                    <span class="text-[10px] text-black">{{ $row['room'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-2 text-center align-top border border-slate-200">
                                                <span class="text-[11px] font-black text-black">{{ $row['units'] }}</span>
                                            </td>
                                            <td class="py-2 px-2 text-center align-top border border-slate-200">
                                                <span class="inline-block text-[7px] font-black uppercase tracking-widest px-1 py-0.5
                                                             {{ $row['status_label'] === 'FINALIZED'
                                                                 ? 'bg-emerald-50 text-emerald-700'
                                                                 : 'bg-amber-50 text-amber-700' }}">
                                                    {{ $row['status_label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-100 border-t-2 border-slate-400">
                                            <td colspan="6"
                                                class="py-2 px-2 text-right text-[9px] font-black uppercase
                                                       tracking-widest text-slate-600 border border-slate-300">
                                                Total Units — Section {{ $group['section'] }}
                                            </td>
                                            <td class="py-2 px-2 text-center text-sm font-black text-black border border-slate-300">
                                                {{ $group['total_units'] }}
                                            </td>
                                            <td class="py-2 px-2 border border-slate-300"></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                {{-- ── PRINT PAGE FOOTER ── --}}
                                @if(!empty($reportData['meta']))
                                @php $meta = $reportData['meta']; @endphp
                                <div class="print-page-footer">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Generated By</p>
                                            <p class="text-[10px] font-semibold text-black mt-0.5">CLASSLY – {{ $meta['role_label'] }}</p>
                                            <p class="text-[9px] text-slate-600">{{ $meta['generated_by'] }}</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Generated On</p>
                                            <p class="text-[10px] font-semibold text-black mt-0.5">{{ $meta['generated_at'] }}</p>
                                            <p class="text-[9px] text-slate-600">
                                                {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }},
                                                A.Y. {{ $activePeriod['school_year'] }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">
                                                Page {{ $groupIndex + 1 }} of {{ count($printableGroups) }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Signature lines --}}
                                    <div class="grid grid-cols-2 gap-12 mt-8">
                                        <div class="text-center">
                                            <div class="border-t border-black pt-1 mt-10">
                                                <p class="text-[10px] font-black uppercase text-black">{{ $meta['generated_by'] }}</p>
                                                <p class="text-[9px] text-slate-600">{{ $meta['role_label'] }}</p>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="border-t border-black pt-1 mt-10">
                                                <p class="text-[10px] font-black uppercase text-black">______________________________</p>
                                                <p class="text-[9px] text-slate-600">Noted By / Dean / OIC</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>{{-- /.print-page --}}
                            @endforeach
                        </div>{{-- /print:block --}}

                        {{-- SCREEN PREVIEW — driven by $groupedRows (always shows all groups) --}}
                        <div class="screen-preview-block space-y-4">
                            @foreach($groupedRows as $groupIndex => $group)
                            <div class="schedule-group bg-white dark:bg-slate-800 rounded-2xl shadow-sm
                                        border border-slate-200 dark:border-slate-700 overflow-hidden">

                                {{-- Screen group header --}}
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700
                                            bg-slate-50 dark:bg-slate-700/40">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-black uppercase tracking-widest text-violet-700 dark:text-violet-300">
                                                    {{ $group['department'] }}
                                                    @if(!empty($group['major'])) · {{ $group['major'] }} @endif
                                                </span>
                                            </div>
                                            <p class="text-[10px] font-semibold text-slate-500 mt-0.5">
                                                {{ $group['year_label'] }}, Section {{ $group['section'] }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Subjects / Units</p>
                                            <p class="text-sm font-black text-slate-800 dark:text-slate-100 mt-0.5">
                                                {{ count($group['rows']) }} subj · {{ $group['total_units'] }} units
                                            </p>
                                            <span class="inline-block mt-0.5 text-[8px] font-black uppercase tracking-widest
                                                         px-1.5 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                PRELIMINARY
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Subject table (screen) --}}
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-slate-100 dark:bg-slate-700/60">
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[90px]">Code</th>
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600">Subject / Description</th>
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[140px]">Faculty</th>
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[90px]">Day</th>
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[130px]">Time</th>
                                                <th class="py-2 px-3 text-left text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[90px]">Room</th>
                                                <th class="py-2 px-3 text-center text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[40px]">Units</th>
                                                <th class="py-2 px-3 text-center text-[9px] font-black uppercase tracking-widest
                                                           text-slate-500 dark:text-slate-300 border-b border-slate-200
                                                           dark:border-slate-600 w-[80px]">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group['rows'] as $i => $row)
                                            <tr class="{{ $i % 2 === 0
                                                            ? 'bg-white dark:bg-slate-800'
                                                            : 'bg-slate-50/60 dark:bg-slate-800/40' }}
                                                       border-b border-slate-100 dark:border-slate-700 last:border-0">

                                                <td class="py-2.5 px-3 align-top">
                                                    <p class="font-black text-[11px] text-slate-800 dark:text-slate-100 leading-tight">
                                                        {{ $row['subject_code'] }}
                                                    </p>
                                                    <p class="text-[8px] text-slate-400 leading-tight mt-0.5">
                                                        {{ $row['edp_code'] }}
                                                    </p>
                                                </td>
                                                <td class="py-2.5 px-3 align-top">
                                                    <p class="font-semibold text-[11px] text-slate-700 dark:text-slate-200 leading-snug">
                                                        {{ $row['description'] }}
                                                    </p>
                                                    <span class="inline-block mt-0.5 text-[8px] font-black uppercase tracking-wider
                                                                 px-1.5 py-0.5 rounded
                                                                 {{ $row['type'] === 'Major'
                                                                     ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                                                     : 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                                        {{ $row['type'] }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3 align-top">
                                                    @if($row['faculty'] === 'TBA')
                                                        <span class="inline-block text-[10px] font-black text-amber-600 dark:text-amber-400">TBA</span>
                                                    @else
                                                        <span class="text-[11px] text-slate-700 dark:text-slate-200 leading-snug">{{ $row['faculty'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 align-top">
                                                    @if($row['day'] === 'TBA')
                                                        <span class="inline-block text-[10px] font-black text-amber-600 dark:text-amber-400">TBA</span>
                                                    @else
                                                        <span class="text-[11px] text-slate-700 dark:text-slate-200">{{ $row['day'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 align-top">
                                                    @if($row['time'] === 'TBA')
                                                        <span class="inline-block text-[10px] font-black text-amber-600 dark:text-amber-400">TBA</span>
                                                    @else
                                                        <span class="text-[11px] text-slate-700 dark:text-slate-200">{{ $row['time'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 align-top">
                                                    @if($row['room'] === 'TBA')
                                                        <span class="inline-block text-[10px] font-black text-amber-600 dark:text-amber-400">TBA</span>
                                                    @else
                                                        <span class="text-[11px] text-slate-700 dark:text-slate-200">{{ $row['room'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 text-center align-top">
                                                    <span class="text-[11px] font-black text-slate-700 dark:text-slate-200">
                                                        {{ $row['units'] }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3 text-center align-top">
                                                    <span class="inline-block text-[8px] font-black uppercase tracking-widest
                                                                 px-1.5 py-0.5 rounded
                                                                 {{ $row['status_label'] === 'FINALIZED'
                                                                     ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                                     : 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                                        {{ $row['status_label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-t-2
                                                       border-slate-300 dark:border-slate-500">
                                                <td colspan="6"
                                                    class="py-2 px-3 text-right text-[9px] font-black uppercase
                                                           tracking-widest text-slate-500 dark:text-slate-400">
                                                    Total Units for Section {{ $group['section'] }}
                                                </td>
                                                <td class="py-2 px-3 text-center text-sm font-black text-slate-800 dark:text-slate-100">
                                                    {{ $group['total_units'] }}
                                                </td>
                                                <td class="py-2 px-3"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>{{-- /screen preview --}}

                        {{-- Report footer (screen only) --}}
                        @if(!empty($reportData['meta']))
                        @php $meta = $reportData['meta']; @endphp
                        <div class="mt-4 px-8 py-5 border-t border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800 rounded-2xl shadow-sm
                                    print:hidden">
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Generated By</p>
                                    <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 mt-0.5">
                                        CLASSLY – {{ $meta['role_label'] }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">{{ $meta['generated_by'] }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Generated On</p>
                                    <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 mt-0.5">
                                        {{ $meta['generated_at'] }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        {{ \App\Models\Setting::semesterLabel($activePeriod['semester']) }},
                                        A.Y. {{ $activePeriod['school_year'] }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Document Status</p>
                                    <span class="inline-block mt-0.5 text-[9px] font-black uppercase tracking-widest
                                                 px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        ⚠ PRELIMINARY
                                    </span>
                                    <p class="text-[9px] text-slate-400 mt-1">Subject to change without prior notice.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>{{-- /wire:loading.remove --}}
                    @endif {{-- /hasGenerated --}}

                </div>{{-- /print area --}}
            </div>{{-- /right panel --}}

        </div>{{-- /body --}}
    </div>{{-- /modal container --}}
</div>{{-- /overlay --}}
@endif

</div>{{-- /root --}}

{{-- ══════════════════════════════════════════════════════════════════
     STYLES — Screen UX + Print layout (A4 Portrait)

     KEY ARCHITECTURE
     ────────────────
     The OLD approach: one report card, break-before on each group.
       Problem: the modal's overflow:hidden clips the DOM. When the browser
       enters print mode the "scrolled-off" groups are never sent to the
       print buffer → only Page 1 appears.

     THE FIX: separate the DOM into two sibling blocks:
       1. `print:hidden`   — the screen preview (can overflow/scroll freely)
       2. `hidden print:block` — the print output, built from `.print-page`
          divs, each with `break-after: page`. Because this block is `display:
          none` on screen, it has no height constraint and the browser renders
          ALL pages when printing.

     The visibility trick in @media print makes only
     `#preliminary-schedule-print-area` visible so the rest of the app
     (navbar, sidebar, etc.) is suppressed.
════════════════════════════════════════════════════════════════════ --}}
@once
<style>
/* ════════════════════════════════════════════════════════════════
   SCREEN: keep the print-output-block in the render tree but
   invisible and zero-height so it never affects screen layout.

   WHY NOT display:none?
   display:none removes the entire subtree from the render tree.
   When window.print() fires, the browser walks the render tree to
   build the print layout. Elements that were display:none never
   enter that walk, so break-before:page never fires for them and
   the browser produces only 1 page regardless of how many
   .print-page divs exist.

   The fix: use position:fixed + overflow:hidden + height:0 so
   the element EXISTS in the render tree on screen (taking zero
   visual space), then restore it to normal flow in print.
════════════════════════════════════════════════════════════════ */
.print-output-block {
    position: fixed;
    top: -9999px;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
    pointer-events: none;
    visibility: hidden;     /* hidden on screen, but IN the render tree */
}

/* The screen preview is a normal block on screen */
.screen-preview-block {
    display: block;
}

/* ════════════════════════════════════════════════════════════════
   PRINT: A4 portrait — show only the print-output-block
════════════════════════════════════════════════════════════════ */
@media print {
    /* Step 1: hide everything on the page */
    body * {
        visibility: hidden !important;
    }

    /* Step 2: show only the print area and its descendants */
    #preliminary-schedule-print-area,
    #preliminary-schedule-print-area * {
        visibility: visible !important;
    }

    /* Step 3: pull the print area out of the modal's clipped layout
       so it can flow freely across multiple printed pages */
    #preliminary-schedule-print-area {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        height: auto !important;
    }

    @page {
        size: A4 portrait;
        margin: 15mm 12mm 20mm 12mm;
    }

    /* ── Print output block: restore to normal document flow ── */
    .print-output-block {
        position: static !important;
        top: auto !important;
        left: auto !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
        pointer-events: auto !important;
        visibility: visible !important;
    }

    /* ── Screen preview: suppress in print ── */
    .screen-preview-block {
        display: none !important;
    }

    /* ── Each .print-page = one printed page ── */
    .print-page {
        display: block !important;
        width: 100% !important;
        overflow: visible !important;
    }

    /* Hard page break before every page after the first */
    .print-page--break {
        break-before: page !important;
        page-break-before: always !important;
    }

    /* Keep the group header glued to its first table row */
    .print-page-header {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        border-bottom: 2px solid #334155 !important;
        padding-bottom: 8px !important;
        margin-bottom: 4px !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }

    .print-group-header {
        margin-top: 8px !important;
        margin-bottom: 0 !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }

    .print-page-footer {
        margin-top: 16px !important;
        padding-top: 12px !important;
        border-top: 1px solid #cbd5e1 !important;
    }

    /* Table pagination */
    table { page-break-inside: auto; border-collapse: collapse; }
    tr    { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }

    /* Suppress all screen-only Tailwind print:hidden elements inside print area */
    .print\:hidden { display: none !important; }

    /* Preserve badge / status colours */
    * { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}
</style>
@endonce