<div class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight">System Configuration</h1>
                <p class="mt-1 text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Semester lifecycle control center</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-md border px-3 py-2 text-xs font-black uppercase tracking-wide {{ $config_locked ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300' }}">
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
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">EDP Prefix</p>
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
                        Next EDP prefix: {{ $nextPeriod['edp_prefix'] }}
                    </p>
                </div>
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
                        <label class="flex min-h-11 items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 text-xs font-black uppercase tracking-wide text-slate-700 transition dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-200 {{ $config_locked ? 'opacity-50' : 'cursor-pointer hover:border-blue-400' }}">
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
                    <h2 class="text-sm font-black uppercase tracking-widest text-red-700 dark:text-red-300">Reset Semester</h2>
                    <p class="mt-2 max-w-2xl text-xs font-semibold text-red-700/80 dark:text-red-300/80">
                        Archives active subjects and schedules, advances the academic period, and leaves the new workspace empty for the next term.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="$set('confirmingReset', true)"
                    class="rounded-md bg-red-600 px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-red-700">
                    Reset Semester
                </button>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest">Archive History</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Batch ledger for completed semester archives.</p>
                </div>

                <button
                    type="button"
                    wire:click="$set('showRetrieveModal', true)"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-emerald-700">
                    Retrieve Previous Semester
                </button>
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
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($archiveBatches as $archive)
                            <tr>
                                <td class="px-4 py-3 font-black text-slate-800 dark:text-slate-100">{{ $archive->archive_batch_id ?? 'Legacy #' . $archive->id }}</td>
                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">
                                    {{ $archive->semester_name ?: \App\Models\Setting::semesterDisplayName($archive->semester, $archive->school_year) }}
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">
                                    {{ $archive->total_subjects }} subjects / {{ $archive->total_schedules }} schedules
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $archive->archived_by_name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">
                                    {{ $archive->archived_at ? \Carbon\Carbon::parse($archive->archived_at)->format('M d, Y h:i A') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">
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
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest">Read-only Audit History</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Archived records are locked and displayed from historical batches.</p>
                </div>

                @if($selectedHistoricalSemester && $archivedHistoryRecords->isNotEmpty())
                    <span class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-black uppercase tracking-wide text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300">
                        {{ $archivedHistoryRecords->count() }} records
                    </span>
                @endif
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
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">No archive selected</p>
                </div>
            @elseif($archivedHistoryRecords->isEmpty())
                <div class="rounded-md border border-dashed border-slate-300 py-10 text-center dark:border-slate-700">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">No records found for this archive</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-md border border-slate-200 dark:border-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-950/60">
                            <tr>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">EDP</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Subject</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Section</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Instructor</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Units</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Schedule</th>
                                <th class="px-4 py-3 font-black uppercase tracking-widest text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($archivedHistoryRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 font-black text-indigo-700 dark:text-indigo-300">{{ $record->edp_code }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-black text-slate-800 dark:text-slate-100">{{ $record->subject_code }}</p>
                                        <p class="mt-1 max-w-sm truncate font-semibold text-slate-500">{{ $record->descriptive_title }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $record->section }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $record->instructor_name }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $record->units }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">
                                        @if($record->start_time && $record->end_time)
                                            {{ $record->day }} {{ \Carbon\Carbon::parse($record->start_time)->format('h:i A') }} to {{ \Carbon\Carbon::parse($record->end_time)->format('h:i A') }}
                                        @else
                                            {{ $record->day }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md border border-slate-300 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                            {{ str_replace('_', ' ', $record->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-5 text-sm font-black uppercase tracking-widest">Recent Setting Changes</h2>

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
        </section>
    </div>

    @if($confirmingReset)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-lg border border-red-200 bg-white p-6 shadow-2xl dark:border-red-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-red-700 dark:text-red-300">Confirm Reset Semester</h3>
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
                        class="flex-1 rounded-md px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition {{ $archiveAcknowledged ? 'bg-red-600 hover:bg-red-700' : 'cursor-not-allowed bg-slate-300 dark:bg-slate-700' }}">
                        Reset Semester
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

    @if($showRetrieveModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-lg border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900 dark:bg-slate-900">
                <h3 class="text-lg font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Retrieve Previous Semester</h3>
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    Archived subjects are copied into {{ \App\Models\Setting::semesterLabel($semester) }} {{ $school_year }} with new EDP codes using prefix {{ $currentEdpPrefix }}.
                </p>

                <div class="mt-5">
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Archived Semester</label>
                    <select
                        wire:model.live="retrieveArchiveBatch"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-sm font-bold outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-emerald-950">
                        <option value="">Select an archive batch</option>
                        @foreach($retrievableArchiveOptions as $archive)
                            <option value="{{ $archive->archive_batch_id }}">
                                {{ $archive->archive_batch_id }} - {{ $archive->semester_name ?: \App\Models\Setting::semesterDisplayName($archive->semester, $archive->school_year) }} - {{ $archive->total_subjects }} subjects
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="retrieveArchivedSemester"
                        class="flex-1 rounded-md bg-emerald-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-emerald-700">
                        Retrieve Semester
                    </button>
                    <button
                        type="button"
                        wire:click="$set('showRetrieveModal', false)"
                        class="flex-1 rounded-md bg-slate-200 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
