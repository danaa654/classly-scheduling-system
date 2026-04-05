<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900" x-data="{ aiOpen: true, showSuccess: false }">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm">
            <div>
                <h2 class="text-2xl font-black text-slate-800">Admin Dashboard</h2>
                <p class="text-sm text-slate-400 font-medium">Classly Scheduling Intelligence</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <input type="file" id="faculty_upload" class="hidden" @change="showSuccess = true; setTimeout(() => showSuccess = false, 3000)">
                <button onclick="document.getElementById('faculty_upload').click()" class="px-6 py-2.5 bg-white border-2 border-slate-100 text-slate-600 rounded-2xl font-bold hover:border-blue-500 hover:text-blue-500 transition-all active:scale-95 shadow-sm">
                    📥 Bulk Import
                </button>

                <button @click="aiOpen = true" class="group relative px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 overflow-hidden transition-all active:scale-95">
                    <span class="relative z-10 flex items-center">✨ Generate AI</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-red-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-12 space-y-10">
            
            <div x-show="showSuccess" x-transition class="fixed bottom-10 right-10 bg-green-600 text-white px-6 py-4 rounded-3xl shadow-2xl font-bold z-50">
                ✅ Faculty Data Imported Successfully!
            </div>
            
            <template x-if="aiOpen">
                <div class="bg-gradient-to-r from-blue-50 to-white border-l-8 border-blue-600 p-6 rounded-3xl shadow-xl shadow-blue-500/5 flex items-center justify-between group">
                    <div class="flex items-center space-x-6">
                        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-lg text-white">🤖</div>
                        <div>
                            <h4 class="text-xs font-black text-blue-600 uppercase tracking-widest">AI Intelligence Suggestion</h4>
                            <p class="text-slate-700 font-bold text-lg">"Move Ethics (Minor) to Room 101 to resolve a faculty overlap."</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button @click="aiOpen = false" class="px-5 py-2 bg-blue-600 text-white text-xs font-black rounded-full hover:bg-red-600 transition-colors shadow-lg">APPLY FIX</button>
                        <button @click="aiOpen = false" class="text-slate-400 hover:text-red-500 font-bold px-2">Dismiss</button>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-4 gap-8">
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all transform hover:-translate-y-1">
                    <p class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Faculty</p>
                    <h3 class="text-4xl font-black text-slate-900 leading-none">{{ $totalFaculty ?? 142 }}</h3>
                </div>
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm border-b-4 border-b-red-500">
                    <p class="text-red-500 text-xs font-black uppercase tracking-widest mb-2">Pending Approvals</p>
                    <h3 class="text-4xl font-black text-slate-900 leading-none">08</h3>
                </div>
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm border-b-4 border-b-blue-500">
                    <p class="text-blue-500 text-xs font-black uppercase tracking-widest mb-2">Rooms Active</p>
                    <h3 class="text-4xl font-black text-slate-900 leading-none">{{ $roomCount ?? 24 }}<span class="text-lg text-slate-300">/30</span></h3>
                </div>
                <div class="bg-red-600 p-8 rounded-[2rem] shadow-2xl shadow-red-200 text-white">
                    <p class="text-red-100 text-xs font-black uppercase tracking-widest mb-2">Conflicts Found</p>
                    <h3 class="text-4xl font-black leading-none">02</h3>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Login & System History</h3>
                    <span class="px-4 py-1 bg-green-100 text-green-700 text-[10px] font-black rounded-full uppercase tracking-widest">System Live</span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                        <tr>
                            <th class="px-10 py-5">User Account</th>
                            <th class="px-10 py-5">Access Level</th>
                            <th class="px-10 py-5">Department</th>
                            <th class="px-10 py-5">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr class="hover:bg-blue-50/30 transition-colors cursor-pointer group">
                            <td class="px-10 py-6 font-bold text-slate-700 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 font-black text-xs group-hover:bg-blue-600 group-hover:text-white transition-colors uppercase">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                {{ auth()->user()->name }}
                            </td>
                            <td class="px-10 py-6">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-black uppercase tracking-widest">
                                    {{ auth()->user()->role }}
                                </span>
                            </td>
                            <td class="px-10 py-6 text-slate-500 text-sm font-medium">Institutional</td>
                            <td class="px-10 py-6 text-slate-400 text-sm font-mono">{{ now()->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>