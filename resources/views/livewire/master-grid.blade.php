<div class="flex h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 overflow-hidden"
     x-data="{
        roomsOpen: true,
        subjectsOpen: true,
        showConflictModal: false,
        conflictData: {
            type: '',
            title: '',
            message: '',
            details: {}
        }
     }"
     @room-selected.window="roomsOpen = false"
     @show-conflict-modal.window="
        console.log('🔴 CONFLICT MODAL EVENT RECEIVED:', \$event.detail);
        conflictData = \$event.detail.conflictData || \$event.detail[0] || \$event.detail;
        showConflictModal = true;
        console.log('✅ CONFLICT MODAL STATE UPDATED:', conflictData);
     ">

    <main class="flex-1 flex flex-col min-w-0 relative h-full">

        {{-- HEADER --}}
        <header class="h-16 bg-white/50 dark:bg-slate-900/50 border-b-2 border-slate-300 dark:border-slate-700 backdrop-blur-md flex items-center justify-between px-6 z-20 shrink-0 shadow-md">
            <div class="flex flex-col justify-center">
                <h2 class="text-xl font-black text-slate-900 dark:text-slate-50 uppercase tracking-tight">
                    Master <span class="text-blue-600 dark:text-blue-400">Grid</span>
                </h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[8px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest">{{ $schoolYear }} • {{ $semester }} SEM</span>
                    @if($selectedRoomName)
                        <span class="text-[8px] bg-emerald-100/80 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 px-2.5 py-0.5 rounded-lg font-black uppercase flex items-center gap-1 backdrop-blur-sm border-2 border-emerald-300 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 bg-emerald-600 dark:bg-emerald-400 rounded-full animate-pulse"></span>
                            🏢 {{ $selectedRoomName }}
                        </span>
                        @if($selectedRoomType)
                            <span class="text-[8px] {{ strtoupper($selectedRoomType) === 'LECTURE' ? 'bg-blue-100/80 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 border-blue-300 dark:border-blue-800' : 'bg-purple-100/80 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-300 dark:border-purple-800' }} px-2.5 py-0.5 rounded-lg font-black uppercase backdrop-blur-sm border-2">
                                {{ strtoupper($selectedRoomType) === 'LECTURE' ? '🎓 LEC' : '🔬 LAB' }}
                            </span>
                        @endif
                    @else
                        <span class="text-[8px] bg-slate-100/80 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 rounded-lg font-black uppercase backdrop-blur-sm border-2 border-slate-300 dark:border-slate-700">
                            📍 Select Room
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- AI GENERATION BUTTON -->
                @if($canAutoGenerate ?? false)
                <button 
                    wire:click="openGenerateModal"
                    wire:loading.attr="disabled"
                    wire:target="openGenerateModal,startGeneration,runGeneration,confirmGeneratedSchedules,saveGeneratedSchedules"
                    class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-indigo-400 dark:border-indigo-600 bg-indigo-100/70 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-200/70 dark:hover:bg-indigo-900/50 shadow-md hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-60"
                    title="Generate schedule using the local heuristic scheduler">
                    <span>✨</span>
                    <span wire:loading.remove wire:target="openGenerateModal,startGeneration,runGeneration">Generate</span>
                    <span wire:loading wire:target="openGenerateModal,startGeneration,runGeneration">Working</span>
                </button>
                @endif

                <div class="flex bg-slate-100/70 dark:bg-slate-800/60 p-0.5 rounded-lg border-2 border-slate-300 dark:border-slate-700 backdrop-blur-sm">
                    <button 
                        @click="subjectsOpen = !subjectsOpen"
                        :class="subjectsOpen ? 'bg-white/80 dark:bg-slate-700/80 text-blue-600 dark:text-blue-400 shadow-md' : 'text-slate-500 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-transparent"
                        :style="subjectsOpen ? 'border-color: rgb(59, 130, 246)' : ''">
                        <span :class="subjectsOpen ? 'bg-blue-600' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full transition-colors"></span>
                        📚 Subjects
                    </button>
                    <button 
                        @click="roomsOpen = !roomsOpen"
                        :class="roomsOpen ? 'bg-white/80 dark:bg-slate-700/80 text-purple-600 dark:text-purple-400 shadow-md' : 'text-slate-500 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-transparent"
                        :style="roomsOpen ? 'border-color: rgb(147, 51, 234)' : ''">
                        <span :class="roomsOpen ? 'bg-purple-600' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full transition-colors"></span>
                        🏢 Rooms
                    </button>
                </div>
            </div>
        </header>

        {{-- MAIN CONTENT AREA --}}
        <div class="flex-1 flex overflow-hidden gap-0">

            {{-- GRID AREA --}}
            <main class="flex-1 overflow-hidden p-2">
                @include('livewire.schedule-grid')
            </main>

            {{-- SIDEBARS CONTAINER --}}
            <div 
                class="flex overflow-hidden bg-white/40 dark:bg-slate-900/30 border-l-2 border-slate-300 dark:border-slate-700 backdrop-blur-md shadow-2xl transition-all"
                x-show="roomsOpen || subjectsOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                @refreshGrid.window="$wire.$refresh()">
                @include('livewire.master-grid-sidebar')
            </div>
        </div>
    </main>

    @if($showGeneratingModal)
        <div
            wire:key="generating-modal-{{ $generationProcessId }}"
            wire:init="runGeneration"
            class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xl"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;">
            <div
                x-data="{
                    step: 0,
                    messages: [
                        'Analyzing rooms and time slots',
                        'Checking section conflicts',
                        'Optimizing linked meeting patterns',
                        'Preparing the generation summary'
                    ]
                }"
                x-init="setInterval(() => step = (step + 1) % messages.length, 1700)"
                class="w-full max-w-md rounded-3xl border border-white/50 bg-white/75 p-8 text-center shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/80">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 shadow-inner dark:bg-indigo-950/40">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600 dark:border-indigo-900 dark:border-t-indigo-300"></div>
                </div>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-300">Generating Schedule...</p>
                <h3 class="mt-2 text-xl font-black uppercase text-slate-950 dark:text-white">
                    {{ $generateDepartment ?: 'Dept' }} - {{ $generateMajor ?: 'Major' }} - Year {{ $generateYearLevel ?: '?' }} - Section {{ $generateSection ?: '?' }}
                </h3>
                <p class="mt-3 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                    Please wait while the AI scheduler analyzes rooms, time slots, and conflicts.
                </p>
                <p class="mt-4 h-5 text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400" x-text="messages[step]">
                    Analyzing rooms and time slots
                </p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200/90 dark:bg-slate-700/80">
                    <div class="ai-progress-bar h-full rounded-full bg-indigo-600"></div>
                </div>
            </div>
        </div>
    @endif

    @if($showSavingModal)
        <div
            wire:key="saving-modal-{{ $saveProcessId }}"
            wire:init="saveGeneratedSchedules"
            class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xl"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;">
            <div class="w-full max-w-md rounded-3xl border border-white/50 bg-white/75 p-8 text-center shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/80">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 shadow-inner dark:bg-emerald-950/40">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600 dark:border-emerald-900 dark:border-t-emerald-300"></div>
                </div>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-300">Saving Generated Schedule...</p>
                <h3 class="mt-2 text-xl font-black uppercase text-slate-950 dark:text-white">Writing To Database</h3>
                <p class="mt-3 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                    Please wait while schedules are being saved to the database.
                </p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200/90 dark:bg-slate-700/80">
                    <div class="ai-progress-bar h-full rounded-full bg-emerald-600"></div>
                </div>
            </div>
        </div>
    @endif

    @if($showRetryingModal)
        <div
            wire:key="retrying-modal-{{ $retryProcessId }}"
            wire:init="runFailedSubjectRetry"
            class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xl"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;">
            <div
                x-data="{
                    step: 0,
                    messages: [
                        'Rechecking compatible rooms',
                        'Preserving successful generated subjects',
                        'Searching clean paired meeting slots',
                        'Rebuilding the temporary schedule preview'
                    ]
                }"
                x-init="setInterval(() => step = (step + 1) % messages.length, 1600)"
                class="w-full max-w-md rounded-3xl border border-white/50 bg-slate-950/85 p-8 text-center shadow-2xl backdrop-blur-xl">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 shadow-inner">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-red-500/20 border-t-red-400"></div>
                </div>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.28em] text-red-300">Recalculating Schedule...</p>
                <h3 class="mt-2 text-xl font-black uppercase text-white">AI Optimization Retry</h3>
                <p class="mt-3 text-sm font-semibold leading-6 text-slate-300">
                    The scheduler is retrying only this failed subject while preserving the generated schedule preview.
                </p>
                <p class="mt-4 h-5 text-[11px] font-black uppercase tracking-widest text-slate-400" x-text="messages[step]">
                    Rechecking compatible rooms
                </p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-800">
                    <div class="ai-progress-bar h-full rounded-full bg-red-500"></div>
                </div>
            </div>
        </div>
    @endif

    @if($showGenerateModal)
        <div
            class="fixed inset-0 z-[9997] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-xl"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;"
            @click.self="$wire.closeGenerateModal()">
            <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-white/50 bg-white/75 shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/80">
                <div class="shrink-0 border-b border-white/60 p-6 dark:border-slate-700/70">
                    <p class="text-xs font-black uppercase tracking-[0.26em] text-indigo-600 dark:text-indigo-300">AI Auto Generate</p>
                    <h3 class="mt-2 text-2xl font-black uppercase text-slate-950 dark:text-white">Choose Schedule Group</h3>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Department</span>
                            <select wire:model.live="generateDepartment" class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-3 text-sm font-black text-slate-900 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800/90 dark:text-white dark:focus:ring-indigo-950">
                                <option value="">Select Department</option>
                                @foreach($departmentMajors as $dept => $majors)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Major</span>
                            <select wire:model.live="generateMajor" class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-3 text-sm font-black text-slate-900 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800/90 dark:text-white dark:focus:ring-indigo-950">
                                <option value="">Select Major</option>
                                @foreach(($departmentMajors[$generateDepartment] ?? []) as $major)
                                    <option value="{{ $major }}">{{ $major }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Year Level</span>
                            <select wire:model.live="generateYearLevel" class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-3 text-sm font-black text-slate-900 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800/90 dark:text-white dark:focus:ring-indigo-950">
                                <option value="">Select Year</option>
                                @foreach([1, 2, 3, 4] as $year)
                                    <option value="{{ $year }}">{{ $year }}{{ $year === 1 ? 'st' : ($year === 2 ? 'nd' : ($year === 3 ? 'rd' : 'th')) }} Year</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Section</span>
                            <select wire:model.live="generateSection" class="w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-3 text-sm font-black text-slate-900 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800/90 dark:text-white dark:focus:ring-indigo-950">
                                <option value="">Select Section</option>
                                @foreach(['A', 'B', 'C'] as $section)
                                    <option value="{{ $section }}">Section {{ $section }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <div class="sticky bottom-0 z-10 flex shrink-0 flex-col gap-3 border-t border-white/60 bg-white/80 p-5 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/85 sm:flex-row sm:justify-end">
                    <button wire:click="closeGenerateModal" wire:loading.attr="disabled" wire:target="startGeneration" class="rounded-xl bg-slate-100 px-5 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-200 disabled:opacity-60 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button wire:click="startGeneration" wire:loading.attr="disabled" wire:target="startGeneration" class="rounded-xl bg-indigo-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="startGeneration">Start Generation</span>
                        <span wire:loading wire:target="startGeneration">Starting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($generationSummary && $showSummaryModal)
        <div
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/65 p-3 backdrop-blur-xl sm:p-4"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;"
            @click.self="$wire.closeGenerationSummary()">
            <div class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-white/50 bg-white/75 shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/80">
                <div class="shrink-0 border-b border-white/60 p-5 dark:border-slate-700/70 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.26em] text-indigo-600 dark:text-indigo-300">AI Generation Summary</p>
                            <h3 class="mt-2 break-words text-2xl font-black uppercase text-slate-950 dark:text-white">
                                {{ $generationSummary['filters']['department'] ?? 'Dept' }} - {{ $generationSummary['filters']['major'] ?? 'Major' }} - Year {{ $generationSummary['filters']['year_level'] ?? '?' }} - Section {{ $generationSummary['filters']['section'] ?? '?' }}
                            </h3>
                        </div>
                        <button wire:click="closeGenerationSummary" class="rounded-xl px-3 py-2 text-xl font-black text-slate-500 transition hover:bg-white/70 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white">&times;</button>
                    </div>
                </div>

                <div class="shrink-0 grid gap-3 border-b border-white/60 p-5 text-center dark:border-slate-700/70 sm:grid-cols-3 sm:p-6">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/85 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/30">
                        <p class="text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $generationSummary['scheduled'] }}</p>
                        <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Total Scheduled</p>
                    </div>
                    <div class="rounded-2xl border border-red-200 bg-red-50/85 p-4 shadow-sm dark:border-red-900/60 dark:bg-red-950/30">
                        <p class="text-3xl font-black text-red-700 dark:text-red-300">{{ $generationSummary['failed'] }}</p>
                        <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-red-700 dark:text-red-400">Total Failed</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/85 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/30">
                        <p class="text-3xl font-black text-amber-700 dark:text-amber-300">{{ $generationSummary['warnings'] }}</p>
                        <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Total Warnings</p>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-5 custom-scrollbar sm:p-6">
                    <div class="grid gap-6 xl:grid-cols-2">
                        <section>
                            <h4 class="mb-3 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Successfully Scheduled</h4>
                            <div class="space-y-3">
                                @forelse($generationSummary['scheduled_items'] as $item)
                                    <div class="rounded-2xl border border-white/70 bg-white/75 p-4 text-xs shadow-sm backdrop-blur-xl dark:border-slate-700 dark:bg-slate-900/65">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="break-words text-base font-black text-slate-950 dark:text-white">{{ $item['subject_code'] }}</p>
                                                <p class="mt-0.5 break-words font-black uppercase tracking-wide text-slate-500">EDP: {{ $item['edp_code'] }}</p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-[9px] font-black uppercase text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Ready</span>
                                        </div>
                                        <p class="mt-3 break-words text-sm font-black text-slate-800 dark:text-slate-100">{{ $item['subject_name'] ?? 'No subject name' }}</p>
                                        <div class="mt-4 grid gap-2 font-bold text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                            <p>{{ $item['day_pair'] ?? $item['day'] }}</p>
                                            <p>{{ $item['start_time'] }} - {{ $item['end_time'] }}</p>
                                            <p>Room: {{ $item['room'] }}</p>
                                            <p>Instructor: {{ $item['instructor'] ?? 'Unassigned' }}</p>
                                        </div>
                                        @if($canModifyGenerated ?? false)
                                            <div class="mt-4 flex flex-col gap-2 border-t border-white/60 pt-3 dark:border-slate-700/70 sm:flex-row">
                                                <button
                                                    wire:click="editGeneratedScheduleGroup('{{ $item['summary_key'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editGeneratedScheduleGroup,removeGeneratedScheduleGroup"
                                                    class="flex-1 rounded-xl border border-indigo-300 bg-indigo-50/80 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-indigo-700 transition hover:bg-indigo-100 disabled:opacity-60 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-300">
                                                    Edit
                                                </button>
                                                <button
                                                    wire:click="removeGeneratedScheduleGroup('{{ $item['summary_key'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editGeneratedScheduleGroup,removeGeneratedScheduleGroup"
                                                    class="flex-1 rounded-xl border border-red-300 bg-red-50/80 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-red-700 transition hover:bg-red-100 disabled:opacity-60 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-300">
                                                    Remove
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-white/70 bg-white/60 p-5 text-sm font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                        No schedules were created.
                                    </div>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <h4 class="mb-3 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Failed / Warnings</h4>
                            <div class="space-y-3">
                                @forelse($generationSummary['failed_items'] ?? [] as $item)
                                    <div class="rounded-2xl border border-red-400/40 bg-slate-950/80 p-4 text-xs shadow-2xl shadow-red-950/20 backdrop-blur-xl">
                                        <div class="space-y-1">
                                            <p class="break-words text-sm font-black text-red-300">{{ $item['subject_code'] }}</p>
                                            <p class="break-words font-black uppercase tracking-wide text-red-400">EDP: {{ $item['edp_code'] }}</p>
                                            <p class="break-words text-base font-black text-white">{{ $item['subject_name'] }}</p>
                                            <p class="break-words font-bold text-red-200">Reason: {{ $item['reason'] }}</p>
                                        </div>
                                        <p class="mt-4 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                            Duration comes from Manage Subjects. The AI will choose the best room, paired days, and time automatically.
                                        </p>

                                        <div class="mt-4 grid grid-cols-1 gap-3">
                                            <label class="space-y-1.5">
                                                <span class="block text-[9px] font-black uppercase tracking-widest text-slate-500">Meetings Per Week</span>
                                                <input type="number" min="1" max="{{ $maxMeetingDays ?? 1 }}" step="1" wire:model.live="failedRetryInputs.{{ $item['subject_id'] }}.meetings_per_week" class="w-full rounded-xl border border-red-500/50 bg-slate-900/90 px-3 py-3 text-sm font-black text-white outline-none transition focus:border-red-300 focus:ring-4 focus:ring-red-500/10">
                                                <p class="text-[10px] font-bold text-slate-500">
                                                    The scheduler will automatically find the room, start time, and clean paired days.
                                                </p>
                                            </label>
                                        </div>

                                        @if($canModifyGenerated ?? false)
                                            <div class="sticky bottom-0 -mx-4 -mb-4 mt-4 border-t border-red-500/20 bg-slate-950/95 p-4 backdrop-blur-xl">
                                                <button wire:click="retryFailedSubject({{ $item['subject_id'] }})" wire:loading.attr="disabled" wire:target="retryFailedSubject" class="w-full rounded-xl bg-red-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-600/20 transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <span wire:loading.remove wire:target="retryFailedSubject">Edit & Retry</span>
                                                    <span wire:loading wire:target="retryFailedSubject">Retrying...</span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                                View only. Request changes from an Admin or Registrar.
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    @if(empty($generationSummary['failure_reasons']))
                                        <div class="rounded-2xl border border-white/70 bg-white/60 p-5 text-sm font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                            No failed subjects.
                                        </div>
                                    @endif
                                @endforelse

                                @if(empty($generationSummary['failed_items']) && !empty($generationSummary['failure_reasons']))
                                    @foreach($generationSummary['failure_reasons'] as $reason)
                                        <div class="rounded-2xl border border-red-200 bg-red-50/85 p-4 text-xs font-bold text-red-700 dark:border-red-900/70 dark:bg-red-950/25 dark:text-red-300">
                                            {{ $reason }}
                                        </div>
                                    @endforeach
                                @endif

                                @foreach($generationSummary['fallback_warnings'] as $warning)
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs font-bold text-amber-800 shadow-sm dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-300">
                                        {{ $warning }}
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>

                <div class="sticky bottom-0 z-10 flex shrink-0 flex-col gap-3 border-t border-white/60 bg-white/85 p-5 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/90 sm:flex-row sm:items-center sm:justify-between">
                    <button wire:click="closeGenerationSummary" wire:loading.attr="disabled" wire:target="confirmGeneratedSchedules,saveGeneratedSchedules" class="rounded-xl bg-slate-100 px-5 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-200 disabled:opacity-60 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    @if($canModifyGenerated ?? false)
                        <button
                            wire:click="confirmGeneratedSchedules"
                            wire:loading.attr="disabled"
                            wire:target="confirmGeneratedSchedules"
                            @disabled(empty($pendingGeneratedSchedules))
                            class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-slate-950/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-indigo-100">
                            <span wire:loading.remove wire:target="confirmGeneratedSchedules">Save Generated Schedule</span>
                            <span wire:loading wire:target="confirmGeneratedSchedules">Preparing Save...</span>
                        </button>
                    @else
                        <div class="rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-xs font-black uppercase tracking-widest text-slate-400">
                            View Only
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showEditScheduleModal)
        <div
            class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xl"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;"
            @click.self="$wire.closeGeneratedScheduleEdit()">
            <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-white/15 bg-slate-950/90 shadow-2xl backdrop-blur-xl">
                <div class="shrink-0 border-b border-white/10 p-6">
                    <p class="text-xs font-black uppercase tracking-[0.26em] text-indigo-300">Temporary Generated Edit</p>
                    <h3 class="mt-2 break-words text-2xl font-black uppercase text-white">
                        {{ $generatedScheduleEditInputs['subject_code'] ?? 'Subject' }}
                    </h3>
                    <p class="mt-1 break-words text-sm font-bold text-slate-400">
                        {{ $generatedScheduleEditInputs['subject_name'] ?? 'Edit generated schedule before saving.' }}
                    </p>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Compatible Room</span>
                            <select wire:model.live="generatedScheduleEditInputs.room_id" class="w-full rounded-xl border border-indigo-500/40 bg-slate-900/90 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-500/10">
                                <option value="">Select compatible room</option>
                                @foreach($compatibleRoomsForEdit as $roomOption)
                                    <option value="{{ $roomOption['room_id'] }}">
                                        {{ $roomOption['room_name'] }} - {{ $roomOption['type'] ?? 'Room' }} @if(!empty($roomOption['capacity']))({{ $roomOption['capacity'] }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] font-bold text-slate-500">Only rooms compatible with this subject are shown.</p>
                        </label>

                        <label class="space-y-2">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Start Time</span>
                            <input type="time" wire:model.live="generatedScheduleEditInputs.start_time" class="w-full rounded-xl border border-indigo-500/40 bg-slate-900/90 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-500/10">
                            <p class="text-[10px] font-bold text-slate-500">The same time is applied to every paired meeting.</p>
                        </label>

                        <label class="space-y-2">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Meetings Per Week</span>
                            <input type="number" min="1" max="{{ $maxMeetingDays ?? 1 }}" step="1" wire:model.live="generatedScheduleEditInputs.meetings_per_week" class="w-full rounded-xl border border-indigo-500/40 bg-slate-900/90 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-500/10">
                            <p class="text-[10px] font-bold text-slate-500">Duration stays based on the subject record.</p>
                        </label>

                        <label class="space-y-2">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Instructor</span>
                            <select wire:model.live="generatedScheduleEditInputs.faculty_id" class="w-full rounded-xl border border-indigo-500/40 bg-slate-900/90 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-500/10">
                                <option value="">Unassigned</option>
                                @foreach($compatibleFacultyForEdit ?? [] as $faculty)
                                    <option value="{{ $faculty['id'] }}">{{ $faculty['full_name'] }} - {{ $faculty['department'] }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] font-bold text-slate-500">
                                Showing {{ $generatedScheduleEditInputs['faculty_department'] ?? 'matching department' }} faculty for this subject.
                            </p>
                        </label>
                    </div>

                    <div class="mt-5 space-y-2">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Meeting Days</span>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach($generationDays ?? $days ?? [] as $dayOption)
                                <label class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-[11px] font-black uppercase tracking-widest text-slate-300">
                                    <input
                                        type="checkbox"
                                        value="{{ $dayOption }}"
                                        wire:model.live="generatedScheduleEditInputs.days"
                                        class="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                                    {{ $dayOption }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[10px] font-bold text-slate-500">
                            Select the same number of days as Meetings Per Week. Paired meetings keep the same room and time.
                        </p>
                    </div>
                </div>

                <div class="sticky bottom-0 z-10 flex shrink-0 flex-col gap-3 border-t border-white/10 bg-slate-950/95 p-5 backdrop-blur-xl sm:flex-row sm:justify-between">
                    <button wire:click="closeGeneratedScheduleEdit" wire:loading.attr="disabled" wire:target="saveGeneratedScheduleEdit" class="rounded-xl bg-white/10 px-5 py-3 text-xs font-black uppercase tracking-widest text-slate-200 transition hover:bg-white/15 disabled:opacity-60">
                        Cancel
                    </button>
                    <button wire:click="saveGeneratedScheduleEdit" wire:loading.attr="disabled" wire:target="saveGeneratedScheduleEdit" class="rounded-xl bg-indigo-500 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveGeneratedScheduleEdit">Apply Temporary Edit</span>
                        <span wire:loading wire:target="saveGeneratedScheduleEdit">Checking Conflicts...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== CONFLICT ALERT MODAL (FIXED & COMPLETE) ===== --}}
    <div 
        x-show="showConflictModal"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-md z-[10050] flex items-center justify-center p-4"
        @click.self="showConflictModal = false"
    >
        
        {{-- MODAL CARD --}}
        <div 
            x-show="showConflictModal"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="scale-95 opacity-0 -translate-y-4"
            x-transition:enter-end="scale-100 opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="scale-100 opacity-100 translate-y-0"
            x-transition:leave-end="scale-95 opacity-0 -translate-y-4"
            class="relative w-full max-w-2xl bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-2xl border-2 border-red-200 dark:border-red-900/50 overflow-hidden"
            @click.stop
        >
            
            {{-- TOP ACCENT BAR --}}
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-500 via-orange-500 to-red-600"></div>

            {{-- CONTENT --}}
            <div class="p-8 space-y-6 mt-2">
                
                {{-- HEADER SECTION --}}
                <div class="flex items-start gap-4">
                    {{-- ICON --}}
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-red-100/80 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800/50">
                            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    
                    {{-- TITLE & SUBTITLE --}}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-wider break-words" x-text="conflictData.title">
                            ⚠️ SCHEDULE CONFLICT
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-semibold uppercase tracking-widest">
                            Scheduling Issue Detected
                        </p>
                    </div>
                </div>

                {{-- MAIN MESSAGE --}}
                <div class="bg-red-50/50 dark:bg-red-950/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-lg">
                    <p class="text-lg font-semibold text-red-800 dark:text-red-300" x-text="conflictData.message">
                        This scheduling request conflicts with an existing schedule.
                    </p>
                </div>

                {{-- CONFLICT DETAILS GRID --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Conflict Type --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">🔴 Conflict Type</p>
                        <p class="text-base font-black text-slate-900 dark:text-white break-words">
                            <template x-if="conflictData.type === 'SECTION_CONFLICT'">
                                <span>🎓 STUDENT GROUP</span>
                            </template>
                            <template x-if="conflictData.type === 'FACULTY_CONFLICT'">
                                <span>👨‍🏫 FACULTY</span>
                            </template>
                            <template x-if="conflictData.type === 'ROOM_CONFLICT'">
                                <span>🏢 ROOM</span>
                            </template>
                            <template x-if="!['SECTION_CONFLICT', 'FACULTY_CONFLICT', 'ROOM_CONFLICT'].includes(conflictData.type)">
                                <span x-text="conflictData.type">UNKNOWN</span>
                            </template>
                        </p>
                    </div>

                    {{-- Reason --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">💡 Issue</p>
                        <p class="text-base font-black text-orange-600 dark:text-orange-400">Already Scheduled</p>
                    </div>

                    {{-- Requested Subject --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">📝 You Want</p>
                        <p class="text-base font-black text-blue-700 dark:text-blue-300 break-words" x-text="conflictData.details?.requested_subject || 'N/A'">
                            CS-101
                        </p>
                    </div>

                    {{-- Conflicting Subject --}}
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-xs font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-2">❌ Conflict With</p>
                        <p class="text-base font-black text-red-700 dark:text-red-300 break-words" x-text="conflictData.details?.conflicting_subject || 'N/A'">
                            CS-102
                        </p>
                    </div>

                    {{-- Conflicting Room --}}
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                        <p class="text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-2">🏢 In Room</p>
                        <p class="text-base font-black text-purple-700 dark:text-purple-300 break-words" x-text="conflictData.details?.conflicting_room || 'N/A'">
                            Room 201
                        </p>
                    </div>

                    {{-- Faculty/Group Info --}}
                    <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-lg border border-slate-200 dark:border-slate-600">
                        <template x-if="conflictData.type === 'FACULTY_CONFLICT'">
                            <div>
                                <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">👨‍🏫 Faculty</p>
                                <p class="text-base font-black text-slate-900 dark:text-white break-words" x-text="conflictData.details?.faculty_name || 'Unknown'">Dr. Smith</p>
                            </div>
                        </template>
                        <template x-if="conflictData.type === 'SECTION_CONFLICT'">
                            <div>
                                <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">👥 Student Group</p>
                                <p class="text-base font-black text-slate-900 dark:text-white break-words" x-text="conflictData.details?.group || 'Unknown'">CCS-IT-1-A</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- TIME COMPARISON --}}
                <div class="space-y-3">
                    <p class="text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">⏰ Time Details</p>
                    
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Requested Time --}}
                        <div class="bg-blue-50 dark:bg-blue-950/20 border-2 border-blue-200 dark:border-blue-800 p-4 rounded-lg">
                            <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-2">✅ Requested Time</p>
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 dark:text-white" x-text="conflictData.details?.requested_day || 'Monday'">Monday</p>
                                <p class="text-lg font-black text-blue-600 dark:text-blue-400">
                                    <span x-text="(conflictData.details?.requested_start || '9:00 AM')"></span>
                                    <span class="text-sm">-</span>
                                    <span x-text="(conflictData.details?.requested_end || '10:00 AM')"></span>
                                </p>
                            </div>
                        </div>

                        {{-- Conflicting Time --}}
                        <div class="bg-red-50 dark:bg-red-950/20 border-2 border-red-200 dark:border-red-800 p-4 rounded-lg">
                            <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wide mb-2">❌ Conflicting Time</p>
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 dark:text-white" x-text="conflictData.details?.conflicting_day || 'Monday'">Monday</p>
                                <p class="text-lg font-black text-red-600 dark:text-red-400">
                                    <span x-text="(conflictData.details?.conflicting_start || '9:00 AM')"></span>
                                    <span class="text-sm">-</span>
                                    <span x-text="(conflictData.details?.conflicting_end || '10:00 AM')"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUGGESTION --}}
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border-2 border-yellow-200 dark:border-yellow-800 p-4 rounded-lg">
                    <p class="text-sm font-bold text-yellow-800 dark:text-yellow-300 flex items-start gap-3">
                        <span class="text-2xl mt-0.5 flex-shrink-0">💡</span>
                        <span x-text="conflictData.details?.suggestion || 'Please choose a different time or resource to resolve this conflict.'">
                            Suggestion text here
                        </span>
                    </p>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-slate-900/30 dark:to-slate-800/30 border-t border-red-200 dark:border-red-900/30 p-6 flex gap-3 justify-between">
                <button 
                    @click="showConflictModal = false"
                    class="flex-1 px-6 py-3 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-black uppercase text-sm tracking-wider transition-all shadow-md hover:shadow-lg transform hover:scale-105 active:scale-95">
                    ← TRY DIFFERENT TIME
                </button>
                <button 
                    @click="showConflictModal = false"
                    class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 dark:from-red-700 dark:to-orange-700 dark:hover:from-red-600 dark:hover:to-orange-600 text-white font-black uppercase text-sm tracking-wider transition-all shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95">
                    ✓ UNDERSTOOD
                </button>
            </div>
        </div>
    </div>
    {{-- ===== END CONFLICT ALERT MODAL ===== --}}
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    
    @media (prefers-color-scheme: dark) {
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748B; }
    }

    [x-cloak] {
        display: none !important;
    }

    .ai-progress-bar {
        animation: ai-progress-slide 1.6s ease-in-out infinite;
        width: 45%;
    }

    @keyframes ai-progress-slide {
        0% {
            transform: translateX(-110%);
        }

        50% {
            transform: translateX(70%);
            width: 60%;
        }

        100% {
            transform: translateX(230%);
            width: 45%;
        }
    }
</style>
