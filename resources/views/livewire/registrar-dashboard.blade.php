<div class="flex h-screen bg-[#F8FAFC]" x-data>
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Registrar Command Center</h2>
                <p class="text-sm text-slate-400 font-medium italic">Overview of Institutional Resources</p>
            </div>
            
            <div class="text-right">
                <p class="text-slate-800 font-bold text-lg">{{ $todayString }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Academic Period</p>
            </div>
        </header>

        <div class="p-12 overflow-y-auto space-y-10">
            
            <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-100/50 flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter mb-2">Welcome Back, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
                    <p class="text-lg text-slate-500 max-w-xl">You are logged into the Registrar’s Portal. From here you can manage all classroom space and track user activity.</p>
                </div>
                <div class="text-9xl p-4 bg-slate-100 rounded-3xl">🏛️</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-100/40 relative overflow-hidden group">
                    <div class="absolute -right-6 -bottom-6 text-9xl text-slate-100 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-500">🏫</div>
                    <p class="text-[11px] font-black uppercase text-slate-400 tracking-widest relative z-10 mb-2">Total Managed Rooms</p>
                    <p class="text-6xl font-black text-blue-600 tracking-tighter relative z-10">{{ $totalRooms }}</p>
                    <a href="{{ route('manage-rooms') }}" class="text-xs font-bold text-slate-500 mt-4 block relative z-10 hover:underline">View Room List →</a>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-100/40 relative overflow-hidden group">
                    <div class="absolute -right-6 -bottom-6 text-9xl text-slate-100 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-500">👥</div>
                    <p class="text-[11px] font-black uppercase text-slate-400 tracking-widest relative z-10 mb-2">System Users</p>
                    <p class="text-6xl font-black text-blue-600 tracking-tighter relative z-10">{{ $totalUsers }}</p>
                    <a href="{{ route('manage-users') }}" class="text-xs font-bold text-slate-500 mt-4 block relative z-10 hover:underline">Manage Accounts →</a>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-100/40 relative overflow-hidden group">
                    <div class="absolute -right-6 -bottom-6 text-9xl text-slate-100 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-500">📅</div>
                    <p class="text-[11px] font-black uppercase text-slate-400 tracking-widest relative z-10 mb-2">Classes Scheduled Today</p>
                    <p class="text-6xl font-black text-{{ $scheduledTodayCount > 0 ? 'green-500' : 'slate-500' }} tracking-tighter relative z-10">{{ $scheduledTodayCount }}</p>
                    <a href="{{ route('scheduler') }}" class="text-xs font-bold text-slate-500 mt-4 block relative z-10 hover:underline">View Master Grid →</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg">
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-5">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('manage-rooms') }}" class="py-4 text-center bg-blue-50 text-blue-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-100">+ Add New Room</a>
                        <a href="{{ route('manage-users') }}" class="py-4 text-center bg-blue-50 text-blue-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-100">+ New User Account</a>
                        <a href="{{ route('scheduler') }}" class="py-4 text-center bg-blue-50 text-blue-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-100">📅 Create Schedule</a>
                        <button class="py-4 text-center bg-slate-100 text-slate-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-200">📥 Import Data</button>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-lg">
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter mb-5">System Announcements</h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="font-bold text-slate-800">Master Data Import Notice</p>
                            <p class="text-xs text-slate-400 mb-1">Posted by Admin, Today 9:15 AM</p>
                            <p>The system is currently processing the new enrollment data. Expect scheduling tools to be slightly slower until 10:00 AM.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>