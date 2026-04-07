<div class="p-10 bg-[#F8FAFC] min-h-screen">
    
    <div class="mb-10 flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase">
                Faculty <span class="text-blue-600">Loading</span>
            </h2>
            <p class="text-sm text-slate-400 font-medium italic">
                Assign subjects and manage workloads for {{ auth()->user()->department ?? 'All Departments' }}
            </p>
        </div>

        <div class="relative w-72">
            <input type="text" wire:model.live="search" 
                placeholder="Search instructor..." 
                class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all shadow-sm">
            <span class="absolute left-4 top-3.5 opacity-30">🔍</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($faculties as $faculty)
            <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/30 hover:scale-[1.02] transition-transform duration-300 group">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-2xl group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-inner">
                        👨‍🏫
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 leading-tight uppercase tracking-tighter">{{ $faculty->full_name }}</h4>
                        <p class="text-[10px] font-bold text-slate-400 tracking-widest uppercase">{{ $faculty->employee_id }}</p>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center text-[11px] font-bold">
                        <span class="text-slate-400 uppercase">Current Units</span>
                        <span class="text-slate-800">0 / 24</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-blue-500 h-full w-0 transition-all duration-500"></div>
                    </div>
                </div>

                <button class="w-full py-4 bg-slate-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-slate-900/10">
                    Assign Subjects +
                </button>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <p class="text-slate-400 font-bold italic">No faculty members found in this department.</p>
            </div>
        @endforelse
    </div>
</div>