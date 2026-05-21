<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-500">
    <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        
        <!-- HEADER WITH ACCESS CONTROL -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-slate-50 uppercase tracking-tight">
                    System <span class="text-blue-600 dark:text-blue-400">Configuration</span>
                </h1>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">30-Minute Brick Scheduling</p>
            </div>
            
            <div class="flex items-center gap-3">
                @if($config_locked)
                    <span class="px-4 py-2 bg-emerald-100/80 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-black rounded-xl backdrop-blur-sm border border-emerald-200/50 dark:border-emerald-800/50">
                        🔒 LOCKED
                    </span>
                @else
                    <span class="px-4 py-2 bg-red-100/80 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-black rounded-xl backdrop-blur-sm border border-red-200/50 dark:border-red-800/50 animate-pulse">
                        🔓 UNLOCKED
                    </span>
                @endif
                
                <button 
                    wire:click="toggleLock" 
                    class="px-4 py-2 {{ $config_locked ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white text-xs font-black rounded-xl transition-all shadow-lg hover:shadow-xl backdrop-blur-sm">
                    {{ $config_locked ? 'Unlock' : 'Lock' }}
                </button>
            </div>
        </div>

        <!-- NOTIFICATION TOAST -->
        <div 
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4"
            x-data="{ show: false, message: '', type: '', detail: '' }"
            x-on:notify.window="
                show = true; 
                message = $event.detail[0].message; 
                type = $event.detail[0].type;
                detail = $event.detail[0].detail || '';
                setTimeout(() => show = false, 6000)
            "
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            style="display: none;">
            
            <div 
                :class="{
                    'bg-emerald-500 shadow-emerald-500/25': type === 'success',
                    'bg-red-500 shadow-red-500/25': type === 'error',
                    'bg-blue-500 shadow-blue-500/25': type === 'info',
                    'bg-amber-500 shadow-amber-500/25': type === 'warning'
                }"
                class="rounded-2xl p-4 shadow-2xl flex items-start gap-3 border border-white/20 backdrop-blur-md">
                
                <div class="bg-white/20 rounded-full p-1 text-white flex-shrink-0 mt-0.5">
                    <template x-if="type === 'success'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </template>
                    <template x-if="type === 'error'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </template>
                    <template x-if="type === 'warning'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                    <template x-if="type === 'info' || type !== 'success' && type !== 'error' && type !== 'warning'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                </div>
                
                <div class="flex-1">
                    <p class="text-xs font-black text-white uppercase tracking-tight" x-text="message"></p>
                    <p class="text-xs font-medium text-white/90 mt-1 whitespace-pre-wrap" x-show="detail" x-text="detail"></p>
                </div>
            </div>
        </div>

        <!-- ACADEMIC PERIOD INFO CARD -->
        <div class="bg-white/60 dark:bg-slate-900/40 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 shadow-sm backdrop-blur-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest">📅 Academic Period</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- SCHOOL YEAR -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">School Year</label>
                    <input 
                        type="text" 
                        wire:model="school_year" 
                        placeholder="e.g., 2026-2027"
                        {{ $config_locked ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all {{ $config_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                    @error('school_year') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- SEMESTER -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Semester</label>
                    <select 
                        wire:model="semester"
                        {{ $config_locked ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all {{ $config_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <option value="">Select Semester</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                    @error('semester') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- SEMESTER DISPLAY NAME -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Display Name</label>
                    <input 
                        type="text" 
                        wire:model="semester_name" 
                        placeholder="e.g., First Semester 2026-2027"
                        {{ $config_locked ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all {{ $config_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                    @error('semester_name') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>
            </div>
        </div>

        <!-- SCHOOL DAY BOUNDS CARD -->
        <div class="bg-white/60 dark:bg-slate-900/40 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 shadow-sm backdrop-blur-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">⚙️ School Day Bounds</h2>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">Brick Duration: <span class="text-blue-600 dark:text-blue-400">30 min</span></span>
            </div>
            
            <!-- INFO BOX: 30-Minute Brick -->
            <div class="bg-blue-50/50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-800/50 p-4 rounded-xl mb-6 backdrop-blur-sm flex items-start gap-3">
                <span class="text-lg flex-shrink-0">🧱</span>
                <div>
                    <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase mb-1">30-Minute Brick Philosophy</p>
                    <p class="text-xs font-medium text-blue-700 dark:text-blue-300">All class slots are locked to 30-minute increments. Times must align to :00 or :30.</p>
                </div>
            </div>

            <!-- ACTIVE SCHEDULE DAYS -->
            <div class="mb-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Active Schedule Days</label>
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">{{ count($active_days) }} active</span>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    @foreach($availableDays as $day)
                        <label class="flex min-h-12 items-center gap-2 rounded-xl border border-slate-200/70 bg-slate-50/60 px-3 py-3 text-[10px] font-black uppercase tracking-widest text-slate-700 transition dark:border-slate-700/70 dark:bg-slate-800/50 dark:text-slate-200 {{ $config_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-blue-300 hover:bg-blue-50/60 dark:hover:border-blue-800 dark:hover:bg-blue-950/20' }}">
                            <input
                                type="checkbox"
                                value="{{ $day }}"
                                wire:model.live="active_days"
                                {{ $config_locked ? 'disabled' : '' }}
                                class="rounded border-slate-300 bg-white text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-900">
                            <span class="truncate">{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
                @error('active_days')
                    <span class="text-[10px] text-red-500 font-bold mt-2 uppercase block">{{ $message }}</span>
                @enderror
                @error('active_days.*')
                    <span class="text-[10px] text-red-500 font-bold mt-2 uppercase block">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- DAY START TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Day Start Time</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="day_start"
                            {{ $config_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $config_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">⏰</span>
                    </div>
                    @error('day_start') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- DAY END TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Day End Time</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="day_end"
                            {{ $config_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $config_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">⏰</span>
                    </div>
                    @error('day_end') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <!-- LUNCH BREAK INFO BOX (HARD-CODED) -->
            <div class="bg-amber-50/50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-800/50 p-4 rounded-xl mb-6 backdrop-blur-sm flex items-start gap-3">
                <span class="text-lg flex-shrink-0">🍽️</span>
                <div>
                    <p class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase mb-1">Institutional Lunch Break (Fixed)</p>
                    <p class="text-xs font-medium text-amber-700 dark:text-amber-300">{{ $lunchStart }} - {{ $lunchEnd }} is automatically locked. This is a system-wide constant and cannot be modified.</p>
                </div>
            </div>

            <!-- MAINTENANCE MODE TOGGLE -->
            <div class="bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 p-4 rounded-xl mb-6 backdrop-blur-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-lg">⚙️</span>
                    <div>
                        <p class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase">Maintenance Mode</p>
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-400">Prevents Deans from modifying subjects.</p>
                    </div>
                </div>
                <button 
                    wire:click="toggleMaintenanceMode"
                    class="px-4 py-2 {{ $maintenance_mode ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-400 hover:bg-slate-500' }} text-white text-xs font-black rounded-lg transition-all shadow-md">
                    {{ $maintenance_mode ? 'ON' : 'OFF' }}
                </button>
            </div>

            <!-- SAVE BUTTON -->
            <button 
                wire:click="save"
                {{ $config_locked ? 'disabled' : '' }}
                class="w-full py-4 {{ $config_locked ? 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl' }} text-white rounded-xl font-black uppercase tracking-widest transition-all">
                {{ $config_locked ? 'Unlock to Save Changes' : 'Save Configuration' }}
            </button>
        </div>

        <!-- CHANGE HISTORY / AUDIT TRAIL -->
        <div class="bg-white/60 dark:bg-slate-900/40 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 shadow-sm backdrop-blur-xl">
            <h3 class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-4">📋 Recent Changes</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($changeHistory as $log)
                    <div class="text-[11px] p-3 bg-slate-50/50 dark:bg-slate-800/50 rounded-lg border border-slate-100/50 dark:border-slate-700/50 flex justify-between items-center">
                        <span class="font-black text-slate-700 dark:text-slate-300">
                            {{ $log['setting_key'] }}: 
                            <span class="text-blue-600 dark:text-blue-400">{{ Str::limit($log['new_value'], 20) }}</span>
                        </span>
                        <span class="text-slate-400 text-[9px]">{{ \Carbon\Carbon::parse($log['changed_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-slate-400 italic uppercase font-medium">No changes recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- END OF SEMESTER TOOLS -->
        <div class="bg-red-50/60 dark:bg-red-950/20 rounded-2xl border border-red-200/50 dark:border-red-900/50 p-8 backdrop-blur-xl">
            <h3 class="text-red-600 dark:text-red-500 font-black text-sm uppercase mb-2 flex items-center gap-2">
                <span>⚠️</span> End of Semester
            </h3>
            <p class="text-xs text-red-600 dark:text-red-400 mb-6 font-bold uppercase tracking-tight">Archive current schedules and reset the master grid.</p>

            @if(!$confirmingReset)
                <button 
                    wire:click="$set('confirmingReset', true)" 
                    class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:shadow-xl transition-all">
                    Reset Semester
                </button>
            @else
                <div class="bg-white/60 dark:bg-slate-900/40 p-6 rounded-xl border-2 border-red-600 backdrop-blur-xl animate-pulse">
                    <p class="text-red-600 dark:text-red-500 font-black text-center mb-4 text-xs uppercase">⚠️ Confirm Semester Reset? This Action Is Permanent.</p>
                    <div class="flex gap-4">
                        <button 
                            wire:click="archiveAndReset" 
                            class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black uppercase text-[10px] transition-all shadow-md">
                            Yes, Archive & Wipe
                        </button>
                        <button 
                            wire:click="$set('confirmingReset', false)" 
                            class="flex-1 py-3 bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 dark:hover:bg-slate-600 text-slate-900 dark:text-slate-100 rounded-xl font-black uppercase text-[10px] transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            @endif

            <div class="mt-8 pt-8 border-t border-red-100/50 dark:border-red-900/30 space-y-3">
                <p class="text-[10px] font-black text-red-400 dark:text-red-600 uppercase mb-3">📦 Recent Archives</p>
                @forelse($archives as $archive)
                    <div class="flex items-center justify-between p-3 bg-white/50 dark:bg-slate-800/30 rounded-lg border border-red-100/50 dark:border-red-900/20">
                        <span class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase">{{ $archive->semester_name }}</span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500">
                            {{ \Carbon\Carbon::parse($archive->created_at)->format('M d, Y') }}
                        </span>
                    </div>
                @empty
                    <p class="text-[10px] text-red-300 dark:text-red-900 italic uppercase font-bold">No archives yet.</p>
                @endforelse
            </div>
        </div>
        <!-- ARCHIVED HISTORY LOGS — full-width independent block, outside the settings cards column -->
        {{-- NOTE: This closing tag ends the max-w-6xl space-y-6 cards column above. --}}
    </div><!-- /.max-w-6xl.space-y-6 (settings cards column) -->

    <!-- ═══════════════════════════════════════════════════════════════
         ARCHIVED HISTORY LOGS
         Independent full-width row — rendered outside the cards column
         so it is never clipped, constrained, or nested inside a flex
         sibling. Glassmorphism dark theme matching Classly's palette.
    ════════════════════════════════════════════════════════════════════ -->
    <div class="w-full mt-8 px-4 pb-8">
        <div class="max-w-6xl mx-auto">
        <div class="w-full p-6 bg-slate-900/40 backdrop-blur-md border border-white/10 rounded-2xl">

            {{-- Section header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-base leading-none">🗂️</span>
                    </div>
                    <div>
                        <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest">Archived History Logs</h3>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Read-only audit view of past semester records</p>
                    </div>
                </div>

                {{-- Record count badge: only shown when a semester is selected AND records were found --}}
                @if($selectedHistoricalSemester && $archivedHistoryRecords->isNotEmpty())
                    <span class="flex-shrink-0 px-3 py-1.5 bg-indigo-500/15 border border-indigo-400/25 text-indigo-300 text-[10px] font-black rounded-lg uppercase tracking-wide">
                        {{ $archivedHistoryRecords->count() }} records
                    </span>
                @endif
            </div>

            {{-- Term selector dropdown --}}
            <div class="mb-4">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">
                    Select Historical Semester to Audit
                </label>
                <select
                    wire:model.live="selectedHistoricalSemester"
                    class="w-full bg-slate-950 border border-white/10 text-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-all">
                    <option value="">— Select Past Semester to Audit —</option>
                    @forelse($archivedSemesterOptions as $option)
                        <option value="{{ $option->value }}">
                            {{ $option->label }} · {{ $option->badge }}
                            @if($option->date)
                                · Archived {{ \Carbon\Carbon::parse($option->date)->format('M d, Y') }}
                            @endif
                        </option>
                    @empty
                        <option value="" disabled>No archived semesters available</option>
                    @endforelse
                </select>
            </div>

            {{-- Empty state: no semester selected --}}
            @if(! $selectedHistoricalSemester)
                <div class="flex flex-col items-center justify-center py-12 border border-dashed border-white/10 rounded-xl">
                    <span class="text-3xl mb-3 opacity-40">🗄️</span>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">No semester selected</p>
                    <p class="text-[10px] font-medium text-slate-600 mt-1">Choose a historical period from the dropdown above to audit its records.</p>
                </div>

            {{-- Empty state: semester selected but no records found --}}
            @elseif($selectedHistoricalSemester && $archivedHistoryRecords->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 border border-dashed border-white/10 rounded-xl">
                    <span class="text-3xl mb-3 opacity-40">📭</span>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">No records found</p>
                    <p class="text-[10px] font-medium text-slate-600 mt-1">This archive period contains no schedule data.</p>
                </div>

            @else
                {{-- Read-only status banner --}}
                <div class="flex items-center gap-3 p-3 bg-amber-500/8 border border-amber-400/20 rounded-xl mb-4">
                    <span class="text-sm flex-shrink-0">🔒</span>
                    <p class="text-[10px] font-black text-amber-300 uppercase tracking-wider">
                        Viewing Historical Records — These records are completely locked and read-only.
                    </p>
                </div>

                {{-- Data table --}}
                <div class="overflow-hidden rounded-xl border border-white/8">

                    {{-- Scrollable wrapper — horizontal scroll only; no height cap so the full table is always visible --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[640px]">

                            {{-- Sticky header --}}
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-slate-950/90 backdrop-blur-sm border-b border-white/10">
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap w-28">
                                        EDP Code
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest">
                                        Subject
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap w-20 text-center">
                                        Section
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest">
                                        Assigned Instructor
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap w-16 text-center">
                                        Units
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap w-32 text-center">
                                        Schedule
                                    </th>
                                    <th class="px-4 py-3 text-[10px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap w-28 text-center">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/5">
                                @foreach($archivedHistoryRecords as $index => $record)
                                    <tr class="group transition-colors duration-100 hover:bg-white/4 {{ $index % 2 === 0 ? 'bg-transparent' : 'bg-white/2' }}">

                                        {{-- EDP Code --}}
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-block px-2 py-0.5 bg-indigo-500/15 border border-indigo-400/20 text-indigo-300 text-[10px] font-black rounded-md uppercase tracking-wide">
                                                {{ $record->edp_code }}
                                            </span>
                                        </td>

                                        {{-- Subject code + title --}}
                                        <td class="px-4 py-3">
                                            <p class="text-xs font-black text-slate-200 uppercase leading-tight">
                                                {{ $record->subject_code }}
                                            </p>
                                            @if($record->descriptive_title && $record->descriptive_title !== '—')
                                                <p class="text-[10px] font-medium text-slate-500 mt-0.5 leading-tight max-w-xs truncate">
                                                    {{ $record->descriptive_title }}
                                                </p>
                                            @endif
                                        </td>

                                        {{-- Section --}}
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-2 py-0.5 bg-white/8 border border-white/10 text-slate-300 text-[10px] font-black rounded-md uppercase">
                                                {{ $record->section }}
                                            </span>
                                        </td>

                                        {{-- Instructor name --}}
                                        <td class="px-4 py-3">
                                            @if($record->instructor_name === 'Unassigned')
                                                <span class="text-[10px] font-bold text-slate-600 uppercase italic">Unassigned</span>
                                            @else
                                                <p class="text-xs font-semibold text-slate-300 leading-tight">
                                                    {{ $record->instructor_name }}
                                                </p>
                                            @endif
                                        </td>

                                        {{-- Units --}}
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xs font-black text-slate-300">
                                                {{ $record->units !== '—' ? $record->units . ' u' : '—' }}
                                            </span>
                                        </td>

                                        {{-- Day + time --}}
                                        <td class="px-4 py-3 text-center">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $record->day }}</p>
                                            @if($record->start_time && $record->end_time)
                                                <p class="text-[9px] font-medium text-slate-600 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($record->start_time)->format('h:i A') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($record->end_time)->format('h:i A') }}
                                                </p>
                                            @endif
                                        </td>

                                        {{-- Status chip --}}
                                        <td class="px-4 py-3 text-center">
                                            @php
                                                $chipClass = match($record->status) {
                                                    'finalized'        => 'bg-emerald-500/15 border-emerald-400/25 text-emerald-300',
                                                    'faculty_assigned' => 'bg-blue-500/15 border-blue-400/25 text-blue-300',
                                                    'partial'          => 'bg-amber-500/15 border-amber-400/25 text-amber-300',
                                                    'draft'            => 'bg-slate-500/15 border-slate-400/25 text-slate-400',
                                                    default            => 'bg-white/8 border-white/10 text-slate-500',
                                                };
                                            @endphp
                                            <span class="inline-block px-2 py-0.5 border text-[9px] font-black rounded-md uppercase tracking-wide {{ $chipClass }}">
                                                {{ str_replace('_', ' ', $record->status) }}
                                            </span>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Table footer summary row --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-slate-950/60 border-t border-white/8">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">
                            {{ $archivedHistoryRecords->count() }} total records
                        </p>
                        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wide">
                            Total units:
                            <span class="text-slate-400">
                                {{ $archivedHistoryRecords->sum(fn($r) => is_numeric($r->units) ? $r->units : 0) }}
                            </span>
                        </p>
                    </div>
                </div>
            @endif
        </div><!-- /.archived-history-card -->
        </div><!-- /.max-w-6xl (centering wrapper) -->
    </div><!-- /.w-full.mt-8.px-4.pb-8 (history logs row) -->

</div><!-- /.min-h-screen (page wrapper) -->