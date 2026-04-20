<div class="p-8 bg-[#E6E6E6] dark:bg-[#020617] min-h-screen transition-colors duration-500">
    
    <div class="max-w-4xl mx-auto space-y-6">
        
        <h1 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">
            System <span class="text-blue-600 dark:text-blue-500">Configuration</span>
        </h1>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
            <h2 class="text-xs font-black text-blue-500 dark:text-blue-400 uppercase tracking-[0.2em] mb-6">Institutional Defaults</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Semester Name</label>
                    <input type="text" wire:model="semester_name" 
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Default Slot Duration</label>
                    <select wire:model="default_duration" 
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:ring-blue-500">
                        <option value="1.0">1.0 Hour</option>
                        <option value="1.5">1.5 Hours</option>
                        <option value="2.0">2.0 Hours</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Day Start (AM)</label>
                    <input type="time" wire:model="start_time" 
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Day End (PM)</label>
                    <input type="time" wire:model="end_time" 
                        class="w-full border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-slate-700 dark:text-slate-200">
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 p-4 rounded-2xl mb-6 flex items-center gap-4">
                <span class="text-2xl">🍕</span>
                <div>
                    <p class="text-[10px] font-black text-blue-500 dark:text-blue-400 uppercase">Lunch Break Policy</p>
                    <p class="text-xs font-bold text-blue-800 dark:text-blue-300">12:00 PM - 01:00 PM is automatically set as vacant/locked.</p>
                </div>
            </div>

            <button wire:click="save" 
                class="w-full py-4 bg-blue-600 dark:bg-blue-700 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-blue-600 transition-all shadow-lg shadow-blue-100 dark:shadow-none">
                Save System Config
            </button>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
            <h2 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">College Departments</h2>
            
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <input type="text" wire:model="new_dept_name" placeholder="Full Name (e.g. Information Technology)" 
                    class="flex-1 border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-sm dark:text-slate-200">
                <div class="flex gap-4">
                    <input type="text" wire:model="new_dept_code" placeholder="Code" 
                        class="w-24 border-slate-200 dark:border-slate-700 dark:bg-slate-800 rounded-xl font-bold text-sm uppercase text-center dark:text-slate-200">
                    <button wire:click="addDepartment" 
                        class="px-6 bg-slate-800 dark:bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] hover:bg-slate-700 transition-colors">
                        Add
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                @foreach($departments as $dept)
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 group transition-all">
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
                            class="flex-1 py-3 bg-red-600 text-white rounded-xl font-black uppercase text-[10px]">Yes, Archive & Wipe</button>
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