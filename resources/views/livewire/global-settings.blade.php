<div class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight">System Configuration</h1>
                <p class="mt-1 text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Semester lifecycle control center</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-md border px-3 py-2 text-xs font-black uppercase tracking-wide {{ $config_locked ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-300' }}">
                    {{ $config_locked ? 'Locked' : 'Unlocked' }}
                </span>

                <button
                    type="button"
                    wire:click="toggleLock"
                    class="rounded-md px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition {{ $config_locked ? 'bg-blue-600 hover:bg-blue-700' : 'bg-slate-700 hover:bg-slate-800' }}">
                    {{ $config_locked ? 'Unlock' : 'Lock' }}
                </button>
            </div>
        </div>

        <div
            class="fixed left-1/2 top-6 z-50 w-full max-w-md -translate-x-1/2 px-4"
            x-data="{ show: false, message: '', type: 'info', detail: '' }"
            x-on:notify.window="
                let payload = Array.isArray($event.detail) ? $event.detail[0] : $event.detail;
                if (Array.isArray(payload)) payload = payload[0];
                show = true;
                message = payload?.message || '';
                type = payload?.type || 'info';
                detail = payload?.detail || '';
                setTimeout(() => show = false, 7000);
            "
            x-on:semester-ended.window="
                let semPayload = Array.isArray($event.detail) ? $event.detail[0] : $event.detail;
                if (Array.isArray(semPayload)) semPayload = semPayload[0];
                let url = semPayload?.redirectTo || '/';
                setTimeout(() => window.location.href = url, 2500);
            "
            x-show="show"
            x-transition
            style="display: none;">
            <div
                class="rounded-lg border p-4 shadow-xl"
                :class="{
                    'border-emerald-200 bg-emerald-600 text-white': type === 'success',
                    'border-red-200 bg-red-600 text-white': type === 'error',
                    'border-amber-200 bg-amber-500 text-white': type === 'warning',
                    'border-blue-200 bg-blue-600 text-white': type === 'info'
                }">
                <p class="text-xs font-black uppercase tracking-wide" x-text="message"></p>
                <p class="mt-2 whitespace-pre-wrap text-xs font-semibold text-white/90" x-show="detail" x-text="detail"></p>
            </div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-blue-600 px-3 py-2 text-xs font-black uppercase tracking-widest text-white">
                            Active Semester
                        </span>
                        <span class="text-sm font-black uppercase tracking-wide text-slate-700 dark:text-slate-200">
                            {{ \App\Models\Setting::semesterLabel($semester) }} {{ $school_year }}
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Active Subjects</p>
                            <p class="mt-2 text-2xl font-black">{{ $activeSubjectsCount }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Active Schedules</p>
                            <p class="mt-2 text-2xl font-black">{{ $activeSchedulesCount }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">EDP Term Prefix</p>
                            <p class="mt-2 text-2xl font-black">{{ $currentEdpPrefix }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full rounded-md border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30 lg:max-w-md">
                    <p class="text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Semester Progression Preview</p>
                    <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-sm font-black">
                        <div class="rounded-md bg-white px-3 py-3 text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                            {{ \App\Models\Setting::semesterLabel($semester) }}<br>
                            <span class="text-xs text-slate-500">{{ $school_year }}</span>
                        </div>
                        <span class="text-indigo-500">to</span>
                        <div class="rounded-md bg-white px-3 py-3 text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                            {{ \App\Models\Setting::semesterLabel($nextPeriod['semester']) }}<br>
                            <span class="text-xs text-slate-500">{{ $nextPeriod['school_year'] }}</span>
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                        Next EDP term prefix: {{ $nextPeriod['edp_prefix'] }}
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border p-6 shadow-sm {{ $systemReady ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20' }}">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest {{ $systemReady ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                        Semester Readiness
                    </p>
                    <h2 class="mt-2 text-lg font-black uppercase tracking-tight text-slate-900 dark:text-slate-100">
                        {{ $systemReady ? 'System is ready' : 'New semester setup required' }}
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm font-semibold {{ $systemReady ? 'text-emerald-800/80 dark:text-emerald-200/80' : 'text-amber-800/80 dark:text-amber-200/80' }}">
                        @if($systemReady)
                            Dean, OIC, and Assistant Dean dashboards can now access the MasterGrid and room view.
                        @else
                            Save the academic period, active days, and schedule bounds. Once the checklist is complete, mark the system as ready for this semester.
                        @endif
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                    @if(! $systemReady)
                        <button
                            type="button"
                            wire:click="openMarkReadyConfirmation"
                            @disabled(! $setupComplete)
                            class="rounded-md px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition {{ $setupComplete ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-400' }}">
                            Mark as Ready
                        </button>
                        @if(! $setupComplete)
                            <p class="text-center text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">
                                {{ collect($setupChecklist)->where('done', false)->count() }} step(s) remaining
                            </p>
                        @endif
                    @else
                        <span class="rounded-md border border-emerald-200 bg-white px-5 py-3 text-center text-xs font-black uppercase tracking-widest text-emerald-700 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-300">
                            Ready
                        </span>
                        <button
                            type="button"
                            wire:click="openMarkNotReadyConfirmation"
                            class="rounded-md bg-slate-700 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-slate-800">
                            Reopen Setup
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-5">
                @foreach($setupChecklist as $item)
                    <div class="rounded-md border p-4 {{ $item['done'] ? 'border-emerald-200 bg-white dark:border-emerald-900 dark:bg-slate-900' : 'border-amber-200 bg-white/70 dark:border-amber-900 dark:bg-slate-900/70' }}">
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-black {{ $item['done'] ? 'bg-emerald-600 text-white' : 'bg-amber-200 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                                {{ $item['done'] ? '✓' : '!' }}
                            </span>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ $item['label'] }}</p>
                        </div>
                        <p class="mt-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-sm font-black uppercase tracking-widest">Academic Period Source of Truth</h2>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">School Year</label>
                    <input
                        type="text"
                        wire:model.live="school_year"
                        placeholder="2026-2027"
                        @disabled($config_locked)
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-blue-950">
                    @error('school_year')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Semester</label>
                    <select
                        wire:model.live="semester"
                        @disabled($config_locked)
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-blue-950">
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                    @error('semester')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Display Name</label>
                    <input
                        type="text"
                        wire:model="semester_name"
                        @disabled($config_locked)
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-blue-950">
                    @error('semester_name')
                        <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-sm font-black uppercase tracking-widest">Scheduling Bounds</h2>
                <span class="text-xs font-black uppercase tracking-widest text-slate-500">30 minute brick</span>
            </div>

            <div class="mb-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Active Schedule Days</label>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ count($active_days) }} active</span>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    @foreach($availableDays as $day)
                        <label class="flex min-h-11 items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 text-xs font-black uppercase tracking-wide text-slate-700 transition dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-200">
                            <input
                                type="checkbox"
                                value="{{ $day }}"
                                wire:model.live="active_days"
                                @disabled($config_locked)
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
                @error('active_days')
                    <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Day Start</label>
                    <input
                        type="time"
                        wire:model="day_start"
                        @disabled($config_locked)
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-blue-950">
                </div>

                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Day End</label>
                    <input
                        type="time"
                        wire:model="day_end"
                        @disabled($config_locked)
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-blue-950">
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Institutional Lunch Break</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $lunchStart }} to {{ $lunchEnd }}</p>
                </div>

                <button
                    type="button"
                    wire:click="toggleMaintenanceMode"
                    class="rounded-md px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition {{ $maintenance_mode ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-600 hover:bg-slate-700' }}">
                    Maintenance {{ $maintenance_mode ? 'On' : 'Off' }}
                </button>
            </div>

            <button
                type="button"
                wire:click="save"
                @disabled($config_locked)
                class="mt-5 w-full rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ $config_locked ? 'cursor-not-allowed bg-slate-300 dark:bg-slate-800' : 'bg-blue-600 hover:bg-blue-700' }}">
                {{ $config_locked ? 'Unlock to Save Changes' : 'Save Configuration' }}
            </button>
        </section>

        <section class="rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm dark:border-red-950 dark:bg-red-950/20">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-red-700 dark:text-red-300">End Semester</h2>
                    <p class="mt-2 max-w-2xl text-xs font-semibold text-red-700/80 dark:text-red-300/80">
                        Archives active subjects and schedules, advances the academic period, and leaves the new workspace empty for the next term.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="openEndSemesterConfirmation"
                    class="rounded-md bg-red-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-red-700">
                    End Semester
                </button>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest">Archive History</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Batch ledger for completed semester archives.</p>
                </div>

                @if($alreadyRetrievedCurrentTerm)
                    <div class="flex items-center gap-2">
                        <span class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                            ✓ Retrieved This Semester
                        </span>
                        <button
                            type="button"
                            disabled
                            title="You have already retrieved once this semester. End the semester first to retrieve again."
                            class="cursor-not-allowed rounded-md bg-slate-300 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-500 shadow-sm dark:bg-slate-700 dark:text-slate-400">
                            Retrieve Previous Semester
                        </button>
                    </div>
                @else
                    <button
                        type="button"
                        wire:click="openRetrieveModal"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-emerald-700">
                        Retrieve Previous Semester
                    </button>
                @endif
            </div>

            <div class="mb-5 grid gap-3 md:grid-cols-2">
                <select wire:model.live="archiveFilterSemester" class="rounded-md border border-slate-300 bg-white px-3 py-3 text-xs font-bold uppercase outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                    <option value="">All Semesters</option>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
                <input type="text" wire:model.live="archiveFilterSchoolYear" placeholder="School year" class="rounded-md border border-slate-300 bg-white px-3 py-3 text-xs font-bold uppercase outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
            </div>

            <div class="overflow-x-auto rounded-md border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-950/60">
                        <tr>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Batch</th>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Archived Term</th>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Totals</th>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Archived By</th>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Archived At</th>
                            <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Advanced To</th>
                        </tr>
                    </thead>
                </table>
                {{-- Scrollable body: 4 rows visible (~52px each), max 15 rows --}}
                <div class="overflow-y-auto" style="max-height: 208px;">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($archiveBatches as $archive)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/30">
                                    <td class="px-4 py-3 font-black text-slate-800 dark:text-slate-100" style="min-width:180px">{{ $archive->archive_batch_id ?? 'Legacy #' . $archive->id }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300" style="min-width:200px">
                                        {{ $archive->semester_name ?: \App\Models\Setting::semesterDisplayName($archive->semester, $archive->school_year) }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300" style="min-width:180px">
                                        {{ $archive->total_subjects }} subjects / {{ $archive->total_schedules }} schedules
                                    </td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300" style="min-width:120px">{{ $archive->archived_by_name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300" style="min-width:160px">
                                        {{ $archive->archived_at ? \Carbon\Carbon::parse($archive->archived_at)->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300" style="min-width:160px">
                                        @if($archive->next_semester && $archive->next_school_year)
                                            {{ \App\Models\Setting::semesterLabel($archive->next_semester) }} {{ $archive->next_school_year }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-xs font-bold uppercase tracking-widest text-slate-400">No archive batches yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest">Read-only Audit History</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Archived records are locked. Select a semester batch to download the full audit as an Excel file.</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Historical Batch</label>
                <select
                    wire:model.live="selectedHistoricalSemester"
                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                    <option value="">Select an archived semester</option>
                    @foreach($archivedSemesterOptions as $option)
                        <option value="{{ $option->value }}">
                            {{ $option->batch ?: 'Legacy' }} - {{ $option->label }} - {{ $option->badge }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(! $selectedHistoricalSemester)
                <div class="rounded-md border border-dashed border-slate-300 py-10 text-center dark:border-slate-700">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Select a semester archive above</p>
                    <p class="mt-2 text-[11px] font-semibold text-slate-400">Each semester can contain 300+ records across all departments. Download as Excel to review them.</p>
                </div>
            @else
                <div class="rounded-md border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-900 dark:bg-indigo-950/30">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Ready to Export</p>
                            <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">
                                @foreach($archivedSemesterOptions as $opt)
                                    @if($opt->value === $selectedHistoricalSemester)
                                        {{ $opt->batch ?: 'Legacy' }} — {{ $opt->label }}
                                    @endif
                                @endforeach
                            </p>
                            @if($archivedHistoryRecords->isNotEmpty())
                                <p class="mt-1 text-xs font-semibold text-indigo-700/80 dark:text-indigo-300/80">
                                    {{ $archivedHistoryRecords->count() }} records across all departments
                                </p>
                            @endif
                            <p class="mt-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                The export includes EDP code, subject, section, instructor, units, schedule, status, and department columns — one row per schedule entry.
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="downloadAuditHistory"
                            @if($archivedHistoryRecords->isEmpty()) disabled @endif
                            class="flex shrink-0 items-center gap-2 rounded-md px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition
                                {{ $archivedHistoryRecords->isNotEmpty() ? 'bg-indigo-600 hover:bg-indigo-700' : 'cursor-not-allowed bg-slate-400' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                            Download Excel (.csv)
                        </button>
                    </div>

                    @if($archivedHistoryRecords->isEmpty())
                        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-400">
                            ⚠ No records found for this archive batch.
                        </p>
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-black uppercase tracking-widest">Recent Setting Changes</h2>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    Current semester · up to 15 entries
                </span>
            </div>

            {{-- Scrollable container: 6 rows visible (each ~78px), scroll to see up to 15 --}}
            <div class="overflow-y-auto pr-1" style="max-height: 468px;">
                <div class="grid gap-3 md:grid-cols-2">
                    @forelse($changeHistory as $log)
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ $log['setting_key'] }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                        {{ \Illuminate\Support\Str::limit((string) ($log['new_value'] ?? ''), 72) }}
                                    </p>
                                </div>
                                <span class="shrink-0 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    {{ isset($log['changed_at']) ? \Carbon\Carbon::parse($log['changed_at'])->diffForHumans() : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">No changes recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    @if($showSemesterBlockerModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-2xl rounded-lg border border-amber-200 bg-white p-6 shadow-2xl dark:border-amber-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Semester Cannot Be Ended</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    Resolve these blocking requirements before archiving and opening the next semester.
                </p>
                <div class="mt-5 max-h-72 space-y-3 overflow-y-auto custom-scrollbar">
                    @foreach($semesterEndBlockers as $blocker)
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                            {{ $blocker }}
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        wire:click="$set('showSemesterBlockerModal', false)"
                        class="rounded-md bg-slate-900 px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950">
                        Review Requirements
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($confirmingReset)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-lg border border-red-200 bg-white p-6 shadow-2xl dark:border-red-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-red-700 dark:text-red-300">Confirm End Semester</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    This will archive {{ $activeSubjectsCount }} active subjects and {{ $activeSchedulesCount }} active schedules, then switch to {{ \App\Models\Setting::semesterLabel($nextPeriod['semester']) }} {{ $nextPeriod['school_year'] }}.
                </p>

                <label class="mt-5 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                    <input type="checkbox" wire:model.live="archiveAcknowledged" class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500">
                    <span>I understand this archives records without deleting them and starts a clean active workspace.</span>
                </label>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="endSemester"
                        @disabled(! $archiveAcknowledged)
                        class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ $archiveAcknowledged ? 'bg-red-600 hover:bg-red-700' : 'cursor-not-allowed bg-slate-400' }}">
                        End Semester
                    </button>
                    <button
                        type="button"
                        wire:click="$set('confirmingReset', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($confirmingMarkReady)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Mark System as Ready?</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    This will notify Dean, OIC, and Assistant Dean users that the semester configuration is ready. MasterGrid and room views will become available to them.
                </p>

                <div class="mt-5 space-y-2 rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    @foreach($setupChecklist as $item)
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $item['done'] ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-700' }}">
                                {{ $item['done'] ? '✓' : '!' }}
                            </span>
                            <span>{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="markSystemReady"
                        class="flex-1 rounded-md bg-emerald-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                        Yes, Mark Ready
                    </button>
                    <button
                        type="button"
                        wire:click="$set('confirmingMarkReady', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($confirmingMarkNotReady)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-lg border border-amber-200 bg-white p-6 shadow-2xl dark:border-amber-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Reopen Semester Setup?</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    This will mark the system as not ready again. Dean-level roles will see the waiting state until you mark the system ready once more.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="markSystemNotReady"
                        class="flex-1 rounded-md bg-amber-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-amber-700">
                        Reopen Setup
                    </button>
                    <button
                        type="button"
                        wire:click="$set('confirmingMarkNotReady', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showRetrieveModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-2xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Retrieve Previous Semester</h3>

                @if($alreadyRetrievedCurrentTerm)
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                        <p class="text-xs font-bold uppercase tracking-widest text-red-700 dark:text-red-300">🚫 Already Retrieved This Semester</p>
                        <p class="mt-1 text-xs font-semibold text-red-800 dark:text-red-200">
                            A retrieval has already been performed for the current term. You can only retrieve once per semester. Please end the semester first before retrieving again.
                        </p>
                    </div>
                    <div class="mt-6 flex">
                        <button
                            type="button"
                            wire:click="$set('showRetrieveModal', false)"
                            class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            Close
                        </button>
                    </div>
                @else
                    @if($matchingArchive)
                    <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Auto-Detected Latest Archive</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Source Archive</p>
                                <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">
                                    {{ $matchingArchive['semester_name'] ?? \App\Models\Setting::semesterDisplayName($matchingArchive['semester'], $matchingArchive['school_year']) }}
                                </p>
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    {{ $matchingArchive['total_subjects'] }} subjects / {{ $matchingArchive['total_schedules'] }} schedules
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
                                    {{ $workspaceOccupancy['subject_count'] }} subjects, {{ $workspaceOccupancy['schedule_count'] }} schedules
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

                <div class="mt-5">
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Retrieval Mode</label>
                    <select
                        wire:model.live="retrieveMode"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-emerald-950">
                        <option value="subjects_only">Subjects Only</option>
                        <option value="full_template">Subject + Faculty + Room + Time (Full Template)</option>
                        <option value="faculty_only">Subject + Faculty</option>
                        <option value="faculty_room">Subject + Faculty + Room</option>
                        <option value="room_only">Subject + Room + Time</option>
                        <option value="time_only">Subject + Time</option>
                    </select>
                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        New EDP codes are always generated for all modes. Modes without time leave the scheduling board fresh for re-generation.
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="proceedToRetrieveConfirmation"
                        @disabled(!$matchingArchive)
                        class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ $matchingArchive ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-400' }}">
                        Review & Proceed
                    </button>
                    <button
                        type="button"
                        wire:click="$set('showRetrieveModal', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                </div>
                @endif {{-- end @else alreadyRetrievedCurrentTerm --}}
            </div>
        </div>
    @endif

    @if($showRetrieveConfirmation)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-2xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Confirm Retrieval</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    You are about to retrieve and copy archived data into the current workspace.
                </p>

                @if($matchingArchive)
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Source Archive</p>
                            <p class="mt-2 text-sm font-black text-slate-800 dark:text-slate-100">
                                {{ $matchingArchive['semester_name'] ?? \App\Models\Setting::semesterDisplayName($matchingArchive['semester'], $matchingArchive['school_year']) }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $matchingArchive['total_subjects'] }} Subjects
                            </p>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $matchingArchive['total_schedules'] }} Schedules
                            </p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Target Workspace</p>
                            <p class="mt-2 text-sm font-black text-slate-800 dark:text-slate-100">
                                {{ \App\Models\Setting::semesterLabel($semester) }} {{ $school_year }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                EDP Prefix: {{ $currentEdpPrefix }}
                            </p>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                Mode: @php
                                    $modeLabels = [
                                        'subjects_only' => 'Subjects Only',
                                        'full_template' => 'Subject + Faculty + Room + Time',
                                        'faculty_only'  => 'Subject + Faculty',
                                        'faculty_room'  => 'Subject + Faculty + Room',
                                        'room_only'     => 'Subject + Room + Time',
                                        'time_only'     => 'Subject + Time',
                                    ];
                                    echo $modeLabels[$retrieveMode] ?? str_replace('_', ' ', $retrieveMode);
                                @endphp
                            </p>
                        </div>
                    </div>
                @endif

                @if($workspaceOccupancy['is_occupied'])
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                        <p class="text-sm font-bold text-red-700 dark:text-red-300">⚠️ Current Workspace Already Contains Data</p>
                        <p class="mt-2 text-xs font-semibold text-red-800 dark:text-red-200">
                            Existing records: {{ $workspaceOccupancy['subject_count'] }} subjects and {{ $workspaceOccupancy['schedule_count'] }} schedules
                        </p>
                        <p class="mt-2 text-xs font-semibold text-red-800 dark:text-red-200">
                            Retrieving may result in duplicate subjects if they already exist.
                        </p>
                    </div>
                @endif

                <label class="mt-4 flex items-start gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                    <input type="checkbox" wire:model.live="archiveAcknowledged" class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                    <span>I understand that archived records remain unchanged and this only copies data into the current workspace.</span>
                </label>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="retrieveArchivedSemester"
                        @disabled(!$archiveAcknowledged)
                        class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ $archiveAcknowledged ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-400' }}">
                        Confirm & Retrieve
                    </button>
                    <button
                        type="button"
                        wire:click="$set('showRetrieveConfirmation', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Back
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>