<div class="p-8 bg-[#E6E6E6] dark:bg-[#020617] min-h-screen transition-colors duration-500">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tighter uppercase">
                Faculty <span class="text-blue-600 dark:text-blue-400">Loading</span>
            </h2>
            <p class="text-sm text-slate-400 font-medium italic">
                @if(auth()->user()->role === 'dean')
                    Managing workloads for the {{ auth()->user()->department }} Department
                @else
                    Institutional workload management and oversight
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-64">
                <input type="text" wire:model.live="search" 
                    placeholder="Search instructor..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-xs focus:ring-2 focus:ring-blue-500 outline-none transition-all shadow-sm dark:text-slate-200">
                <span class="absolute left-4 top-3 opacity-30 text-xs">🔍</span>
            </div>

            @if(auth()->user()->role !== 'dean')
                <select wire:model.live="selectedDepartment" class="pl-4 pr-10 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm cursor-pointer">
                    <option value="">All Departments</option>
                    <option value="CCS">CCS</option>
                    <option value="CTE">CTE</option>
                    <option value="COC">COC</option>
                    <option value="SHTM">SHTM</option>
                </select>
            @endif

            <select wire:model.live="subjectType" class="pl-4 pr-10 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm cursor-pointer">
                <option value="both">All Load Types</option>
                <option value="major">Major Only</option>
                <option value="minor">Minor Only</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($faculties as $faculty)
            <div class="bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/30 dark:shadow-none hover:scale-[1.02] transition-all duration-300 group relative overflow-hidden">
                
                <div class="absolute top-6 right-6">
                    <span class="text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-widest {{ $faculty->teaching_type == 'major' ? 'bg-indigo-100 text-indigo-600' : 'bg-amber-100 text-amber-600' }}">
                        {{ $faculty->teaching_type ?? 'Unassigned' }}
                    </span>
                </div>

                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-14 h-14 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-2xl group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-inner">
                        👨‍🏫
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 dark:text-slate-100 leading-tight uppercase tracking-tighter">{{ $faculty->full_name }}</h4>
                        <p class="text-[10px] font-bold text-slate-400 tracking-widest uppercase">{{ $faculty->department }} • {{ $faculty->employee_id }}</p>
                    </div>
                </div>
    
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center text-[11px] font-bold">
                        <span class="text-slate-400 uppercase tracking-tighter">Current Load Units</span>
                        <span class="text-slate-800 dark:text-slate-200">{{ $faculty->total_units ?? 0 }} / 24</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        @php $percent = (($faculty->total_units ?? 0) / 24) * 100; @endphp
                        <div class="bg-blue-500 h-full transition-all duration-700 ease-out shadow-[0_0_10px_rgba(59,130,246,0.5)]" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                <button class="w-full py-4 bg-slate-900 dark:bg-blue-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 dark:hover:bg-blue-500 transition-all shadow-lg shadow-slate-900/10">
                    Manage Schedule +
                </button>
            </div>
        @empty
            <div class="col-span-full py-32 flex flex-col items-center justify-center text-center">
                <div class="text-6xl mb-4 opacity-20">📂</div>
                <p class="text-slate-400 font-bold italic tracking-wide uppercase text-xs">No faculty matches found for this criteria.</p>
            </div>
        @endforelse
    </div>
</div>