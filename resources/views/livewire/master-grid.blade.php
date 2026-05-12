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
        },
        showGenerationSummary: @entangle('generationSummary').live
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
                <button 
                    wire:click="openGenerateModal"
                    wire:loading.attr="disabled"
                    wire:target="startGeneration"
                    class="px-3 py-1.5 rounded-md text-[8px] font-black uppercase transition-all flex items-center gap-1 border-2 border-indigo-400 dark:border-indigo-600 bg-indigo-100/70 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-200/70 dark:hover:bg-indigo-900/50 shadow-md hover:shadow-lg"
                    title="Generate schedule using AI">
                    <span>✨</span>
                    <span wire:loading.remove wire:target="startGeneration">AI Generate</span>
                    <span wire:loading wire:target="startGeneration">Generating</span>
                </button>

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

    <div
        wire:loading.flex
        wire:target="startGeneration"
        class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-950/60 backdrop-blur-sm">
        <div class="rounded-xl border border-white/10 bg-white p-6 text-center shadow-2xl dark:bg-slate-900">
            <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
            <p class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">AI is generating schedules...</p>
            <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                {{ $generateDepartment ?: 'Dept' }} - {{ $generateMajor ?: 'Major' }} - Year {{ $generateYearLevel ?: '?' }} - Section {{ $generateSection ?: '?' }}
            </p>
            <p class="mt-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Processing selected subject group</p>
            <div class="mt-4 h-2 w-72 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div class="h-full w-1/2 animate-pulse rounded-full bg-indigo-600"></div>
            </div>
        </div>
    </div>

    @if($showGenerateModal)
        <div class="fixed inset-0 z-[9997] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-3xl border border-white/50 bg-white/70 shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/75">
                <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                    <p class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">AI Auto Generate</p>
                    <h3 class="mt-1 text-xl font-black uppercase text-slate-900 dark:text-white">Choose Schedule Group</h3>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Department</span>
                        <select wire:model.live="generateDepartment" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select Department</option>
                            @foreach($departmentMajors as $dept => $majors)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Major</span>
                        <select wire:model.live="generateMajor" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select Major</option>
                            @foreach(($departmentMajors[$generateDepartment] ?? []) as $major)
                                <option value="{{ $major }}">{{ $major }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Year Level</span>
                        <select wire:model.live="generateYearLevel" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select Year</option>
                            @foreach([1, 2, 3, 4] as $year)
                                <option value="{{ $year }}">{{ $year }}{{ $year === 1 ? 'st' : ($year === 2 ? 'nd' : ($year === 3 ? 'rd' : 'th')) }} Year</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Section</span>
                        <select wire:model.live="generateSection" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select Section</option>
                            @foreach(['A', 'B', 'C'] as $section)
                                <option value="{{ $section }}">Section {{ $section }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 p-5 dark:border-slate-700">
                    <button wire:click="closeGenerateModal" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button wire:click="startGeneration" wire:loading.attr="disabled" wire:target="startGeneration" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-700 disabled:opacity-60">
                        Start Generation
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($generationSummary)
        <div
            x-show="showGenerationSummary"
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            @click.self="$wire.closeGenerationSummary()">
            <div class="max-h-[85vh] w-full max-w-4xl overflow-hidden rounded-3xl border border-white/50 bg-white/70 shadow-2xl backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/75">
                <div class="flex items-start justify-between border-b border-slate-200 p-5 dark:border-slate-700">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">AI Generation Summary</p>
                        <h3 class="mt-1 text-xl font-black uppercase text-slate-900 dark:text-white">
                            {{ $generationSummary['filters']['department'] }} - {{ $generationSummary['filters']['major'] }} - Year {{ $generationSummary['filters']['year_level'] }} - Section {{ $generationSummary['filters']['section'] }}
                        </h3>
                    </div>
                    <button wire:click="closeGenerationSummary" class="rounded-md px-3 py-1 text-lg font-black text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">&times;</button>
                </div>

                <div class="grid grid-cols-3 gap-3 border-b border-slate-200 p-5 text-center dark:border-slate-700">
                    <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/30">
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $generationSummary['scheduled'] }}</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Scheduled</p>
                    </div>
                    <div class="rounded-lg bg-red-50 p-3 dark:bg-red-950/30">
                        <p class="text-2xl font-black text-red-700 dark:text-red-300">{{ $generationSummary['failed'] }}</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-red-700 dark:text-red-400">Failed</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-950/30">
                        <p class="text-2xl font-black text-amber-700 dark:text-amber-300">{{ $generationSummary['warnings'] }}</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Warnings</p>
                    </div>
                </div>

                <div class="max-h-[52vh] overflow-y-auto p-5 custom-scrollbar">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <section>
                            <h4 class="mb-3 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Successfully Scheduled</h4>
                            <div class="space-y-2">
                                @forelse($generationSummary['scheduled_items'] as $item)
                                    <div class="rounded-lg border border-slate-200 p-3 text-xs dark:border-slate-700">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $item['subject_code'] }} <span class="text-slate-500">EDP: {{ $item['edp_code'] }}</span></p>
                                        <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $item['subject_name'] ?? 'No subject name' }}</p>
                                        <p class="mt-1 font-bold text-slate-600 dark:text-slate-300">{{ $item['room'] }} - {{ $item['day'] }} - {{ $item['start_time'] }} to {{ $item['end_time'] }}</p>
                                        <p class="mt-1 font-bold text-slate-500 dark:text-slate-400">Instructor: {{ $item['instructor'] ?? 'Unassigned' }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs font-bold text-slate-500">No schedules were created.</p>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <h4 class="mb-3 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Failed / Warnings</h4>
                            <div class="space-y-2">
                                @forelse($generationSummary['failed_items'] ?? [] as $item)
                                    <div class="rounded-xl border border-red-200 bg-red-50/80 p-3 text-xs dark:border-red-900 dark:bg-red-950/20">
                                        <p class="font-black text-red-700 dark:text-red-300">{{ $item['subject_code'] }} <span class="text-red-500">EDP: {{ $item['edp_code'] }}</span></p>
                                        <p class="mt-1 font-bold text-slate-700 dark:text-slate-300">{{ $item['subject_name'] }}</p>
                                        <p class="mt-1 font-bold text-red-700 dark:text-red-300">Reason: {{ $item['reason'] }}</p>

                                        <div class="mt-3 grid grid-cols-3 gap-2">
                                            <label class="space-y-1">
                                                <span class="block text-[8px] font-black uppercase text-slate-500">Meetings</span>
                                                <input type="number" min="1" max="6" step="1" wire:model.live="failedRetryInputs.{{ $item['subject_id'] }}.meetings_per_week" class="w-full rounded border border-red-200 bg-white px-2 py-1 text-[10px] font-bold dark:border-red-900 dark:bg-slate-900">
                                            </label>
                                            <label class="space-y-1">
                                                <span class="block text-[8px] font-black uppercase text-slate-500">Hours</span>
                                                <input type="number" min="0.5" max="8" step="0.5" wire:model.live="failedRetryInputs.{{ $item['subject_id'] }}.duration_hours" class="w-full rounded border border-red-200 bg-white px-2 py-1 text-[10px] font-bold dark:border-red-900 dark:bg-slate-900">
                                            </label>
                                            <label class="space-y-1">
                                                <span class="block text-[8px] font-black uppercase text-slate-500">Room Type</span>
                                                <select wire:model.live="failedRetryInputs.{{ $item['subject_id'] }}.preferred_room_type" class="w-full rounded border border-red-200 bg-white px-2 py-1 text-[10px] font-bold dark:border-red-900 dark:bg-slate-900">
                                                    <option value="">Any</option>
                                                    <option value="Lecture">Lecture</option>
                                                    <option value="Lab">Lab</option>
                                                    <option value="Laboratory">Laboratory</option>
                                                </select>
                                            </label>
                                        </div>

                                        <button wire:click="retryFailedSubject({{ $item['subject_id'] }})" wire:loading.attr="disabled" class="mt-3 w-full rounded-lg bg-red-600 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-red-700 disabled:opacity-60">
                                            Edit & Retry
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-xs font-bold text-slate-500">No failed subjects.</p>
                                @endforelse

                                @foreach($generationSummary['fallback_warnings'] as $warning)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-700 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-300">{{ $warning }}</div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 p-5 dark:border-slate-700">
                    <button wire:click="closeGenerationSummary" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button wire:click="confirmGeneratedSchedules" wire:loading.attr="disabled" wire:target="confirmGeneratedSchedules" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-emerald-700 disabled:opacity-60">
                        Yes / Save Generated Schedule
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
        class="fixed inset-0 bg-black/60 backdrop-blur-md z-[9999] flex items-center justify-center p-4"
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
</style>
