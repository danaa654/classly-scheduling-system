<div wire:poll.3s class="master-grid-shell flex h-[calc(100vh-7rem)] min-h-[36rem] w-full max-w-full overflow-hidden rounded-none bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 shadow-xl shadow-slate-300/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/20 md:h-[calc(100vh-8rem)]"
     x-data="{
        roomsOpen: true,
        subjectsOpen: true
     }"
     @room-selected.window="roomsOpen = false">

    <main class="relative flex h-full min-w-0 flex-1 flex-col overflow-hidden">

        {{-- HEADER --}}
        <header class="z-20 flex h-16 shrink-0 items-center justify-between border-b-2 border-slate-300 bg-white/70 px-4 shadow-md backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/60 sm:px-6">
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

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
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
        <div class="flex min-h-0 min-w-0 flex-1 overflow-hidden gap-0">

            {{-- GRID AREA --}}
            <main class="min-w-0 flex-1 overflow-hidden p-2 md:p-3">
                @include('livewire.schedule-grid')
            </main>

            {{-- SIDEBARS CONTAINER --}}
            <div 
                class="master-grid-sidebars flex h-full max-w-[min(35rem,50vw)] shrink-0 overflow-hidden border-l-2 border-slate-300 bg-white/50 shadow-2xl backdrop-blur-md transition-all dark:border-slate-700 dark:bg-slate-900/30"
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
                                            <span class="shrink-0 rounded-full {{ !empty($item['auto_fixed']) ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }} px-3 py-1 text-[9px] font-black uppercase">
                                                {{ !empty($item['auto_fixed']) ? 'Auto-Fixed' : 'Ready' }}
                                            </span>
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
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Failed / Warnings</h4>
                                @if(!empty($generationSummary['failed_items']))
                                    <label class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                        <input type="checkbox" wire:model.live="selectAllFailedSubjects" class="rounded border-slate-600 bg-slate-900 text-red-500 focus:ring-red-500">
                                        Select All Failed
                                    </label>
                                @endif
                            </div>

                            @if(count($selectedFailedSubjects) > 0)
                                <div class="sticky top-0 z-10 mb-3 rounded-2xl border border-indigo-400/40 bg-slate-950/95 p-4 text-xs shadow-2xl shadow-indigo-950/20 backdrop-blur-xl">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="font-black uppercase tracking-widest text-indigo-200">{{ count($selectedFailedSubjects) }} subjects selected</p>
                                            <button type="button" wire:click="clearFailedSelection" class="text-[10px] font-black uppercase tracking-widest text-slate-400 transition hover:text-white">Clear Selection</button>
                                        </div>
                                        <div>
                                            <input type="number" min="1" max="{{ $maxMeetingDays ?? 1 }}" wire:model.live="bulkFailedInputs.meetings_per_week" placeholder="Meetings per week" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 font-black text-white placeholder-slate-500 outline-none focus:border-indigo-300">
                                        </div>
                                        <button type="button" wire:click="applyBulkFailedChanges" wire:loading.attr="disabled" wire:target="applyBulkFailedChanges" class="rounded-xl bg-indigo-500 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-400 disabled:opacity-60">
                                            <span wire:loading.remove wire:target="applyBulkFailedChanges">Apply Changes</span>
                                            <span wire:loading wire:target="applyBulkFailedChanges">Applying...</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($retryFailureDetails))
                                <div class="mb-3 rounded-xl border border-amber-400/40 bg-amber-500/10 p-3 text-xs text-amber-100">
                                    <p class="font-black uppercase tracking-widest text-amber-200">Retry explanation</p>
                                    <p class="mt-2 font-bold leading-5">{{ $retryFailureDetails['message'] ?? 'No valid schedule found.' }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($retryFailureDetails['searched'] ?? [] as $searched)
                                            <span class="rounded-full bg-slate-950/60 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-100">{{ $searched }}</span>
                                        @endforeach
                                    </div>
                                    @if(!empty($retryFailureDetails['recommendations']))
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                            @foreach($retryFailureDetails['recommendations'] as $recommendation)
                                                <div class="rounded-lg border border-white/10 bg-slate-950/50 p-2">
                                                    <p class="font-black uppercase tracking-wide text-white">{{ $recommendation['label'] ?? 'Recommendation' }}</p>
                                                    <p class="mt-1 font-semibold text-slate-300">{{ $recommendation['detail'] ?? '' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="space-y-3">
                                @forelse($generationSummary['failed_items'] ?? [] as $item)
                                    <div class="rounded-xl border border-red-400/40 bg-slate-950/80 p-3 text-xs shadow-xl shadow-red-950/10 backdrop-blur-xl">
                                        <div class="flex items-start gap-2.5">
                                            <input type="checkbox" wire:model.live="selectedFailedSubjects" value="{{ $item['subject_id'] }}" class="mt-1 rounded border-slate-600 bg-slate-900 text-red-500 focus:ring-red-500">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <p class="break-words text-sm font-black text-red-300">{{ $item['subject_code'] }}</p>
                                                    <span class="rounded-full bg-red-500/15 px-2 py-1 text-[8px] font-black uppercase tracking-widest text-red-200">Failed</span>
                                                </div>
                                                <p class="mt-0.5 break-words font-black uppercase tracking-wide text-red-400">EDP: {{ $item['edp_code'] }}</p>
                                                <p class="mt-1 break-words text-sm font-black text-white">{{ $item['subject_name'] }}</p>
                                                <p class="mt-1 break-words font-bold text-red-200">Reason: {{ $item['reason'] }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label class="space-y-1.5">
                                                <span class="block text-[9px] font-black uppercase tracking-widest text-slate-500">Meetings Per Week</span>
                                                <input type="number" min="1" max="{{ $maxMeetingDays ?? 1 }}" step="1" wire:model.live="failedRetryInputs.{{ $item['subject_id'] }}.meetings_per_week" class="w-full rounded-lg border border-red-500/50 bg-slate-900/90 px-3 py-2 text-sm font-black text-white outline-none transition focus:border-red-300 focus:ring-4 focus:ring-red-500/10">
                                            </label>
                                        </div>

                                        @if($canModifyGenerated ?? false)
                                            <div class="mt-3">
                                                <button wire:click="retryFailedSubject({{ $item['subject_id'] }})" wire:loading.attr="disabled" wire:target="retryFailedSubject({{ $item['subject_id'] }})" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-600/20 transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <span wire:loading.remove wire:target="retryFailedSubject({{ $item['subject_id'] }})">Edit &amp; Retry</span>
                                                    <span wire:loading wire:target="retryFailedSubject({{ $item['subject_id'] }})">Retrying...</span>
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
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button
                                wire:click="autoFixAllConflicts"
                                wire:loading.attr="disabled"
                                wire:target="autoFixAllConflicts"
                                @disabled(empty($generationSummary['failed_items']))
                                class="rounded-xl bg-emerald-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                                <span wire:loading.remove wire:target="autoFixAllConflicts">Auto Fix All Conflicts</span>
                                <span wire:loading wire:target="autoFixAllConflicts" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Auto Fixing...
                                </span>
                            </button>
                            <button
                                wire:click="confirmGeneratedSchedules"
                                wire:loading.attr="disabled"
                                wire:target="confirmGeneratedSchedules"
                                @disabled(empty($pendingGeneratedSchedules))
                                class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-slate-950/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-indigo-100">
                                <span wire:loading.remove wire:target="confirmGeneratedSchedules">Save Generated Schedule</span>
                                <span wire:loading wire:target="confirmGeneratedSchedules">Preparing Save...</span>
                            </button>
                        </div>
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
            wire:key="generated-edit-modal-{{ md5((string) ($editingGeneratedScheduleKey ?: 'new')) }}"
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

    <div class="{{ $showConflictModal ? '' : 'hidden' }}" wire:key="conflict-modal-shell">
        @php
            $details = $conflictData['details'] ?? [];
            $conflicting = $conflictData['conflicting_schedule'] ?? null;
            $type = $conflictData['type'] ?? strtolower((string) ($conflictData['conflict_type'] ?? 'schedule_conflict'));
            $typeLabels = [
                'room_conflict' => 'Room Conflict',
                'faculty_conflict' => 'Faculty Conflict',
                'section_conflict' => 'Section Conflict',
                'inactive_day_conflict' => 'Inactive Day Conflict',
                'room_type_conflict' => 'Room Type Conflict',
                'capacity_conflict' => 'Capacity Conflict',
                'faculty_availability_conflict' => 'Faculty Availability Conflict',
                'time_conflict' => 'Time Conflict',
            ];
            $typeLabel = $typeLabels[$type] ?? str($type)->replace('_', ' ')->title();
            $requestedTime = $details['requested_time'] ?? trim(($details['requested_start'] ?? '') . ' - ' . ($details['requested_end'] ?? ''), ' -');
        @endphp

        <div
            class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-xl sm:p-4"
            wire:key="conflict-modal-panel"
            wire:click.self="closeConflictModal"
            style="font-family: Inter, Montserrat, ui-sans-serif, system-ui, sans-serif;">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-red-300/50 bg-white/95 shadow-2xl shadow-slate-950/30 dark:border-red-900/60 dark:bg-slate-950/95">
                <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-blue-950 px-5 py-5 text-white sm:px-7">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.28em] text-red-300">Scheduling Conflict Detected</p>
                            <h3 class="mt-2 text-2xl font-black uppercase tracking-tight text-white sm:text-3xl">{{ $conflictData['title'] ?? 'Schedule Conflict' }}</h3>
                        </div>
                        <span class="shrink-0 rounded-full border border-red-400/50 bg-red-500/15 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-100">
                            {{ $typeLabel }}
                        </span>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-5 custom-scrollbar sm:p-7">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold leading-6 text-red-800 dark:border-red-900/60 dark:bg-red-950/25 dark:text-red-200">
                        {{ $conflictData['message'] ?? 'This schedule conflicts with an existing schedule.' }}
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Requested Schedule</p>
                            <div class="mt-3 space-y-3 text-sm">
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Subject</span>
                                    <p class="mt-1 font-black text-slate-950 dark:text-white">{{ $details['requested_subject'] ?? 'N/A' }}</p>
                                    @if(!empty($details['requested_subject_name']))
                                        <p class="mt-0.5 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $details['requested_subject_name'] }}</p>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Day</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $details['requested_day'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Time</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $requestedTime ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Conflict Type</span>
                                    <p class="mt-1 font-black text-blue-700 dark:text-blue-300">{{ $typeLabel }}</p>
                                </div>
                                @if(!empty($details['conflict_reason']))
                                    <div class="rounded-lg border border-blue-100 bg-white p-3 dark:border-blue-900/50 dark:bg-slate-950/50">
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Why This Conflicts</span>
                                        <p class="mt-1 text-xs font-bold leading-5 text-slate-700 dark:text-slate-200">{{ $details['conflict_reason'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/70">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Existing Conflict</p>
                            @if($conflicting)
                                <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Subject Code</span>
                                        <p class="mt-1 font-black text-red-700 dark:text-red-300">{{ $conflicting['subject_code'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Subject Name</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $conflicting['subject_name'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Room</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $conflicting['room'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Faculty</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $conflicting['faculty'] ?? 'Unassigned' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Day</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $conflicting['day'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Time</span>
                                        <p class="mt-1 font-bold text-slate-800 dark:text-slate-100">{{ $conflicting['time'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm font-semibold leading-6 text-slate-600 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-300">
                                    No existing schedule owns this conflict. The requested placement violates a scheduling rule such as active days, room type, faculty availability, or capacity.
                                </div>
                            @endif
                        </section>
                    </div>

                    <section class="mt-5 rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900/60 dark:bg-blue-950/20">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-700 dark:text-blue-300">Recommendations</p>
                            <span class="rounded-full bg-white px-2 py-1 text-[10px] font-black text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300">{{ count($recommendations) }}</span>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse($recommendations as $index => $suggestion)
                                @php($suggestionId = (string) ($suggestion['id'] ?? $index))
                                <div class="flex flex-col gap-3 rounded-lg border border-white/80 bg-white/85 p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">&check;</span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $suggestion['label'] ?? 'Alternative schedule available' }}</p>
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                <span class="rounded-md bg-blue-100 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ $suggestion['badge'] ?? str($suggestion['type'] ?? 'suggestion')->upper() }}</span>
                                                <span class="rounded-md bg-emerald-100 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ $suggestion['match_label'] ?? 'Good Match' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click.stop="useConflictSuggestion({{ $index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="useConflictSuggestion({{ $index }})"
                                        @disabled($applyingSuggestionId === $suggestionId)
                                        class="shrink-0 rounded-lg bg-blue-700 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white shadow-lg shadow-blue-900/20 transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-60 disabled:animate-pulse">
                                        <span wire:loading.remove wire:target="useConflictSuggestion({{ $index }})">Use Suggestion</span>
                                        <span wire:loading wire:target="useConflictSuggestion({{ $index }})" class="inline-flex items-center gap-2">
                                            <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Applying...
                                        </span>
                                    </button>
                                </div>
                            @empty
                                <p class="rounded-lg border border-blue-100 bg-white/70 p-3 text-sm font-semibold text-slate-600 dark:border-blue-900/40 dark:bg-slate-900/70 dark:text-slate-300">
                                    No automatic recommendation is available. Choose a different room, day, or time.
                                </p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-white/90 p-4 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90 sm:flex sm:justify-end">
                    <button
                        type="button"
                        wire:click="closeConflictModal"
                        class="w-full rounded-lg bg-slate-900 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
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
