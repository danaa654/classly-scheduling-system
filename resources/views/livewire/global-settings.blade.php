<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 transition-colors duration-500">
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        
        <!-- HEADER WITH ACCESS CONTROL -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-slate-50 uppercase tracking-tight">
                    System <span class="text-blue-600 dark:text-blue-400">Configuration</span>
                </h1>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">30-Minute Brick Scheduling</p>
            </div>
            
            <div class="flex items-center gap-3">
                @if($is_locked)
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
                    class="px-4 py-2 {{ $is_locked ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white text-xs font-black rounded-xl transition-all shadow-lg hover:shadow-xl backdrop-blur-sm">
                    {{ $is_locked ? 'Modify' : 'Lock' }}
                </button>
            </div>
        </div>

        <!-- NOTIFICATION TOAST -->
        <div 
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4"
            x-data="{ show: false, message: '', type: '' }"
            x-on:notify.window="
                show = true; 
                message = $event.detail[0].message; 
                type = $event.detail[0].type; 
                setTimeout(() => show = false, 5000)
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
                class="rounded-2xl p-4 shadow-2xl flex items-center gap-3 border border-white/20 backdrop-blur-md">
                
                <div class="bg-white/20 rounded-full p-1 text-white flex-shrink-0">
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
                
                <p class="text-xs font-black text-white uppercase tracking-tight flex-1" x-text="message"></p>
            </div>
        </div>

        <!-- INSTITUTIONAL DEFAULTS CARD -->
        <div class="bg-white/60 dark:bg-slate-900/40 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 shadow-sm backdrop-blur-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">⚙️ Institutional Defaults</h2>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- SEMESTER NAME -->
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Semester Name</label>
                    <input 
                        type="text" 
                        wire:model="semester_name" 
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all {{ $is_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                    @error('semester_name') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- DAY START TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Day Start Time</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="start_time"
                            {{ $is_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $is_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">⏰</span>
                    </div>
                    @error('start_time') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- DAY END TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Day End Time</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="end_time"
                            {{ $is_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $is_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">⏰</span>
                    </div>
                    @error('end_time') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- LUNCH BREAK START -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Lunch Break Start</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="lunch_break_start"
                            {{ $is_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $is_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">🍽️</span>
                    </div>
                    @error('lunch_break_start') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- LUNCH BREAK END -->
                <div>
                    <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest block mb-2">Lunch Break End</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="time" 
                            wire:model="lunch_break_end"
                            {{ $is_locked ? 'disabled' : '' }}
                            class="flex-1 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 {{ $is_locked ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span class="text-xs font-bold text-slate-400">🍽️</span>
                    </div>
                    @error('lunch_break_end') 
                        <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <!-- LUNCH BREAK POLICY INFO BOX -->
            <div class="bg-amber-50/50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-800/50 p-4 rounded-xl mb-6 backdrop-blur-sm flex items-start gap-3">
                <span class="text-lg flex-shrink-0">⏸️</span>
                <div>
                    <p class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase mb-1">Lunch Break Policy</p>
                    <p class="text-xs font-medium text-amber-700 dark:text-amber-300">{{ $lunch_break_start }} - {{ $lunch_break_end }} is automatically locked. No classes scheduled during this period.</p>
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
                {{ $is_locked ? 'disabled' : '' }}
                class="w-full py-4 {{ $is_locked ? 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl' }} text-white rounded-xl font-black uppercase tracking-widest transition-all">
                {{ $is_locked ? 'Unlock to Save Changes' : 'Save Configuration' }}
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

        <!-- DEPARTMENTS MANAGEMENT SECTION -->
        <div class="bg-white/60 dark:bg-slate-900/40 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 shadow-sm backdrop-blur-xl">
            <h2 class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-6">🏢 College Departments</h2>
            
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1">
                    <input 
                        type="text" 
                        wire:model="new_dept_name" 
                        placeholder="Full Name (e.g. Information Technology)" 
                        class="w-full px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500">
                    @error('new_dept_name') 
                        <span class="text-[9px] text-red-500 font-bold mt-1 uppercase block">{{ $message }}</span> 
                    @enderror
                </div>
                
                <div class="flex gap-3">
                    <div>
                        <input 
                            type="text" 
                            wire:model="new_dept_code" 
                            placeholder="Code" 
                            class="w-24 px-4 py-3 bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl font-medium text-center uppercase text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500">
                        @error('new_dept_code') 
                            <span class="text-[9px] text-red-500 font-bold mt-1 uppercase block text-center">{{ $message }}</span> 
                        @enderror
                    </div>
                    <button 
                        wire:click="addDepartment" 
                        class="px-6 h-[42px] bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black uppercase text-[10px] transition-all shadow-md hover:shadow-lg">
                        Add
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($departments as $dept)
                    <div class="flex items-center justify-between p-4 bg-slate-50/50 dark:bg-slate-800/50 rounded-xl border border-slate-100/50 dark:border-slate-700/50 hover:border-slate-200/75 dark:hover:border-slate-600/75 transition-all">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 border border-blue-200/50 dark:border-blue-700/50 flex items-center justify-center font-black text-blue-600 dark:text-blue-400 text-xs">
                                {{ $dept->code }}
                            </span>
                            <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $dept->name }}</p>
                        </div>
                        <button 
                            wire:click="deleteDepartment({{ $dept->id }})" 
                            class="text-slate-300 dark:text-slate-600 hover:text-red-500 transition-colors text-lg">
                            ✕
                        </button>
                    </div>
                @empty
                    <p class="text-[11px] text-slate-400 italic uppercase font-medium text-center py-4">No departments added yet.</p>
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
    </div>
</div>