<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div class="flex items-center space-x-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter leading-none">Master Schedule</h2>
                    <p class="text-sm text-slate-400 font-medium mt-1">Classly Visual Intelligence Grid</p>
                </div>
                <div class="h-10 w-[1px] bg-slate-200"></div>
                <span class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded-full border border-blue-100 uppercase tracking-widest">
                    1st SEM 2026
                </span>
            </div>
            
            <div class="flex items-center space-x-4">
                <button class="px-6 py-2.5 bg-white border-2 border-slate-100 text-slate-600 rounded-2xl font-bold hover:border-blue-500 hover:text-blue-500 transition-all active:scale-95 shadow-sm">
                    📥 Export PDF
                </button>
                <button class="group relative px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 overflow-hidden transition-all active:scale-95">
                    <span class="relative z-10 flex items-center">✨ Auto-Generate</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-auto p-12 space-y-8">
            
            <div class="bg-gradient-to-r from-blue-50 to-white border-l-8 border-blue-600 p-6 rounded-3xl shadow-xl shadow-blue-500/5 flex items-center justify-between group animate-pulse-subtle">
                <div class="flex items-center space-x-6">
                    <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-lg text-white">🤖</div>
                    <div>
                        <h4 class="text-xs font-black text-blue-600 uppercase tracking-widest">Conflict detected: Prof. Agujetas</h4>
                        <p class="text-slate-700 font-bold text-lg leading-tight">Move "Advanced IT" to <b>Room 204</b> to resolve faculty overlap at 10:30 AM.</p>
                    </div>
                </div>
                <button class="px-6 py-3 bg-blue-600 text-white text-xs font-black rounded-full hover:bg-blue-700 transition-all shadow-lg active:scale-95">APPLY AUTO-FIX</button>
            </div>

            <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/50">
                    <div class="p-6 border-r border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center justify-center">
                        Timeline
                    </div>
                    @foreach(['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $day)
                        <div class="p-6 text-center font-black text-slate-800 text-xs tracking-[0.2em] border-r border-slate-100 last:border-r-0">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>

                <div class="divide-y divide-slate-50">
                    @foreach(['7:30 AM', '9:00 AM', '10:30 AM', '12:00 PM', '1:30 PM', '3:00 PM'] as $time)
                    <div class="grid grid-cols-7 min-h-[120px]">
                        <div class="p-6 text-[11px] font-black text-slate-400 border-r border-slate-100 bg-slate-50/20 flex items-center justify-center">
                            {{ $time }}
                        </div>

                        @for($i=0; $i < 6; $i++)
                        <div class="p-3 border-r border-slate-100 hover:bg-blue-50/30 transition-colors relative group">
                            
                            @if($time == '7:30 AM' && $i == 0)
                                <div class="h-full bg-blue-600 text-white p-4 rounded-[1.5rem] shadow-lg shadow-blue-200 cursor-grab active:cursor-grabbing transition-transform hover:-translate-y-1">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[9px] font-black bg-white/20 px-2 py-0.5 rounded-full uppercase">Lab 1</span>
                                        <span class="text-[9px] font-black opacity-60">IT-4A</span>
                                    </div>
                                    <p class="text-xs font-black leading-tight mb-3">Advanced Web Programming</p>
                                    <div class="flex items-center space-x-2 border-t border-white/10 pt-2">
                                        <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-[8px] font-black">JA</div>
                                        <span class="text-[9px] font-bold opacity-90 truncate">Prof. Agujetas</span>
                                    </div>
                                </div>
                            
                            @elseif($time == '10:30 AM' && $i == 0)
                                <div class="h-full bg-red-50 border-2 border-dashed border-red-200 p-4 rounded-[1.5rem] flex flex-col justify-center items-center text-center group-hover:bg-red-100/50 transition-all">
                                    <div class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center text-xs mb-2">⚠️</div>
                                    <p class="text-[10px] font-black text-red-600 uppercase tracking-tighter">Scheduling Conflict</p>
                                    <p class="text-[9px] text-red-400 font-bold">Faculty Overlap</p>
                                </div>

                            @else
                                <button class="opacity-0 group-hover:opacity-100 absolute inset-0 flex items-center justify-center transition-opacity">
                                    <div class="w-8 h-8 rounded-full bg-white shadow-md border border-slate-100 text-blue-600 flex items-center justify-center font-bold">+</div>
                                </button>
                            @endif
                        </div>
                        @endfor
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.95; }
    }
    .animate-pulse-subtle {
        animation: pulse-subtle 3s ease-in-out infinite;
    }
</style>