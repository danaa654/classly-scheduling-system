<div class="p-8 bg-[#E6E6E6] dark:bg-[#020617] min-h-screen transition-colors duration-500">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">
                System <span class="text-blue-600 dark:text-blue-500">Configuration</span>
            </h1>
            
            <!-- ACCESS CONTROL INDICATORS -->
            <div class="flex items-center gap-3">
                @if($is_locked)
                    <span class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-black rounded-lg">
                        🔒 LOCKED
                    </span>
                @else
                    <span class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-black rounded-lg animate-pulse">
                        🔓 UNLOCKED
                    </span>
                @endif
                
                <button wire:click="toggleLock" 
                    class="px-4 py-2 {{ $is_locked ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white text-xs font-black rounded-lg transition-colors">
                    {{ $is_locked ? 'Modify Configuration' : 'Lock Configuration' }}
                </button>
            </div>
        </div>

        <!-- NOTIFICATION TOAST -->
        <div class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4"
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
            
            <div :class="type === 'success' ? 'bg-emerald-500 shadow-emerald-200/50' : type === 'error' ? 'bg-red-500 shadow-red-200/50' : 'bg-blue-500 shadow-blue-200/50'"
                 class="rounded-2xl p-4 shadow-2xl flex items-center gap-3 border border-white/20 transition-colors duration-300">
                <div class="bg-white/20 rounded-full p-1 text-white">
                    <template x-if="type === 'success'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </template>
                    <template x-if="type === 'error'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </template>
                    <template x-if="type !== 'success' && type !== 'error'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                </div>
                <p class="text-xs font-black text-white uppercase tracking-tight" x-text="message"></p>
            </div>
        </div>

        <!-- INSTITUTIONAL DEFAULTS CARD -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-black text-blue-500 dark:text-blue-400 uppercase tracking-[0.2em]">Institutional Defaults</h2>
                <span class="text-[10px] font-bold text-slate-400">{{ $last_updated_by ? 'Updated by User #' . $last_updated_by : 'Never modified' }}</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- SEMESTER NAME -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Semester Name</label>
                    <input type="text" wire:model="semester_name" 
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('semester_name') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- DEFAULT SLOT DURATION -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Default Slot Duration</label>
                    <select wire:model="default_duration"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:ring-blue-500 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <option value="0.5">30 Minutes</option>
                        <option value="1.0">1.0 Hour</option>
                        <option value="1.5">1.5 Hours</option>
                        <option value="2.0">2.0 Hours</option>
                        <option value="3.0">3.0 Hours</option>
                    </select>
                    @error('default_duration') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- DAY START TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Day Start (AM)</label>
                    <input type="time" wire:model="start_time"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('start_time') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- DAY END TIME -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Day End (PM)</label>
                    <input type="time" wire:model="end_time"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('end_time') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- LUNCH BREAK START -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Lunch Break Start</label>
                    <input type="time" wire:model="lunch_break_start"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('lunch_break_start') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- LUNCH BREAK END -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Lunch Break End</label>
                    <input type="time" wire:model="lunch_break_end"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('lunch_break_end') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- TURNOVER BUFFER -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Room Turnover Buffer (mins)</label>
                    <input type="number" wire:model="turnover_buffer" min="0" max="30"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('turnover_buffer') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>

                <!-- DEFAULT ROOM CAPACITY -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Default Room Capacity</label>
                    <input type="number" wire:model="default_room_capacity" min="10" max="200"
                        {{ $is_locked ? 'disabled' : '' }}
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 {{ $is_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @error('default_room_capacity') <span class="text-[10px] text-red-500 font-bold mt-1 uppercase block tracking-tight">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- INFO BOX: Lunch Break Policy -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 p-4 rounded-2xl mb-6 flex items-center gap-4">
                <span class="text-2xl">🍕</span>
                <div>
                    <p class="text-[10px] font-black text-blue-500 dark:text-blue-400 uppercase">Lunch Break Policy</p>
                    <p class="text-xs font-bold text-blue-800 dark:text-blue-300">{{ $lunch_break_start }} - {{ $lunch_break_end }} is automatically set as vacant/locked.</p>
                </div>
            </div>

            <!-- MAINTENANCE MODE TOGGLE -->
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 p-4 rounded-2xl mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚙️</span>
                    <div>
                        <p class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase">Maintenance Mode</p>
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-300">Prevents Deans from modifying subjects during setup.</p>
                    </div>
                </div>
                <button wire:click="toggleMaintenanceMode"
                    class="px-4 py-2 {{ $maintenance_mode ? 'bg-red-600' : 'bg-slate-300' }} text-white text-xs font-black rounded-lg transition-colors">
                    {{ $maintenance_mode ? 'ON' : 'OFF' }}
                </button>
            </div>

            <!-- SAVE BUTTON -->
            <button wire:click="save"
                {{ $is_locked ? 'disabled' : '' }}
                class="w-full py-4 {{ $is_locked ? 'bg-slate-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded-2xl font-black uppercase tracking-widest transition-all shadow-lg">
                {{ $is_locked ? 'Unlock to Save Changes' : 'Save System Config' }}
            </button>
        </div>

        <!-- CHANGE HISTORY / AUDIT TRAIL -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
            <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-4">Recent Changes</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($changeHistory as $log)
                    <div class="text-[11px] p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-100 dark:border-slate-700 flex justify-between items-center">
                        <span class="font-black text-slate-700 dark:text-slate-300">{{ $log['setting_key'] }}: <span class="text-blue-600">{{ Str::limit($log['new_value'], 20) }}</span></span>
                        <span class="text-slate-400 text-[9px]">{{ \Carbon\Carbon::parse($log['changed_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-slate-400 italic uppercase">No changes recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- DEPARTMENTS MANAGEMENT SECTION -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
            <h2 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">College Departments</h2>
            
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1">
                    <input type="text" wire:model="new_dept_name" placeholder="Full Name (e.g. Information Technology)" 
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-sm dark:text-slate-200">
                    @error('new_dept_name') <span class="text-[9px] text-red-500 font-bold mt-1 uppercase block italic">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex gap-4">
                    <div>
                        <input type="text" wire:model="new_dept_code" placeholder="Code" 
                            class="w-24 border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-sm uppercase text-center dark:text-slate-200">
                        @error('new_dept_code') <span class="text-[9px] text-red-500 font-bold mt-1 uppercase block text-center italic">{{ $message }}</span> @enderror
                    </div>
                    <button wire:click="addDepartment" 
                        class="px-6 h-[42px] bg-slate-800 dark:bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] hover:bg-slate-700 transition-colors">
                        Add
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($departments as $dept)
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <span class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-black text-blue-600 dark:text-blue-400 text-xs">
                            {{ $dept->code }}
                        </span>
                        <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">{{ $dept->name }}</p>
                    </div>
                    <button wire:click="deleteDepartment({{ $dept->id }})" 
                        class="text-slate-300 dark:text-slate-600 hover:text-red-500 transition-colors mr-2 text-lg">✕</button>
                </div>
                @endforeach
            </div>
        </div>

        <!-- END OF SEMESTER TOOLS -->
        <div class="bg-red-50 dark:bg-red-950/20 rounded-3xl border border-red-100 dark:border-red-900/30 p-8">
            <h3 class="text-red-600 dark:text-red-500 font-black text-sm uppercase mb-2">End of Semester Tools</h3>
            <p class="text-xs text-red-400 dark:text-red-400/60 mb-6 font-bold uppercase tracking-tight">Move all current schedules to history and wipe the master grid.</p>

            @if(!$confirmingReset)
                <button wire:click="$set('confirmingReset', true)" 
                    class="px-8 py-3 bg-red-600 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-red-100 dark:shadow-none hover:bg-red-700 transition-all">
                    Reset Semester
                </button>
            @else
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border-2 border-red-600 animate-pulse">
                    <p class="text-red-600 dark:text-red-500 font-black text-center mb-4 text-xs">⚠️ CONFIRM SEMESTER RESET? THIS ACTION IS PERMANENT.</p>
                    <div class="flex gap-4">
                        <button wire:click="archiveAndReset" 
                            class="flex-1 py-3 bg-red-600 text-white rounded-xl font-black uppercase text-[10px] hover:bg-red-700">Yes, Archive & Wipe</button>
                        <button wire:click="$set('confirmingReset', false)" 
                            class="flex-1 py-3 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl font-black uppercase text-[10px]">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="mt-8 pt-8 border-t border-red-100 dark:border-red-900/30 space-y-3">
                <p class="text-[10px] font-black text-red-300 dark:text-red-800 uppercase mb-2">Recent Archives</p>
                @forelse($archives as $archive)
                    <div class="flex items-center justify-between p-3 bg-white/50 dark:bg-slate-900/40 rounded-xl border border-red-100/50 dark:border-red-900/20">
                        <span class="text-[11px] font-black text-slate-600 dark:text-slate-300 uppercase">{{ $archive->semester_name }}</span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 italic">
                            Snapshot: {{ \Carbon\Carbon::parse($archive->created_at)->format('M d, Y') }}
                        </span>
                    </div>
                @empty
                    <p class="text-[10px] text-red-300 dark:text-red-900 italic uppercase font-bold">No archives yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>