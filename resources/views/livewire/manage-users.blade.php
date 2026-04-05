<div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-900" x-data="{ open: @entangle('showModal') }">
    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shadow-sm shrink-0">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">User Management</h2>
                <p class="text-sm text-slate-400 font-medium italic">Access Control & Faculty Directory</p>
            </div>
            
            <button @click="$wire.openModal()" class="group relative px-8 py-3 bg-red-600 text-white rounded-2xl font-black shadow-xl shadow-red-100 overflow-hidden transition-all active:scale-95">
                <span class="relative z-10">+ Add New User</span>
                <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </button>
        </header>

        <div class="p-12 overflow-y-auto">
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-slate-400 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-10 py-5">Identity</th>
                            <th class="px-10 py-5">Role & Access</th>
                            <th class="px-10 py-5">College Department</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $user)
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="px-10 py-6">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center font-black text-xs uppercase group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 tracking-tight text-base leading-none mb-1">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-black uppercase tracking-tighter">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <p class="text-xs text-slate-600 font-bold uppercase tracking-widest">{{ $user->department }}</p>
                            </td>
                            <td class="px-10 py-6 text-right space-x-2">
                                <button wire:click="editUser({{ $user->id }})" class="px-4 py-2 bg-slate-100 text-blue-600 rounded-xl font-black text-[10px] uppercase hover:bg-blue-600 hover:text-white transition-all">Edit</button>
                                <button onclick="confirm('Revoke access for this user?') || event.stopImmediatePropagation()" 
                                        wire:click="deleteUser({{ $user->id }})" 
                                        class="px-4 py-2 bg-slate-100 text-red-600 rounded-xl font-black text-[10px] uppercase hover:bg-red-600 hover:text-white transition-all">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl overflow-hidden border border-slate-200" @click.away="open = false">
            <div class="mb-8">
                <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ $editingUserId ? 'Modify' : 'Register' }} User</h3>
                <p class="text-sm text-slate-400 font-medium italic">Configure system credentials and permissions.</p>
            </div>
            
            <form wire:submit.prevent="saveUser" class="space-y-5">
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2">Full Legal Name</label>
                        <input type="text" wire:model="name" placeholder="Diodana Jane Sedemo" 
                               class="w-full p-4 bg-slate-50 border-2 border-transparent rounded-2xl font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none">
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2">Institutional Email</label>
                        <input type="email" wire:model="email" placeholder="dana@academy.edu.ph" 
                               class="w-full p-4 bg-slate-50 border-2 border-transparent rounded-2xl font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none">
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2">Security Password</label>
                        <input type="password" wire:model="password" placeholder="••••••••" 
                               class="w-full p-4 bg-slate-50 border-2 border-transparent rounded-2xl font-bold focus:bg-white focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @if($editingUserId) <p class="text-[9px] text-slate-400 mt-1 ml-2 italic">Leave blank to keep current password.</p> @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2">System Role</label>
                            <select wire:model="role" class="w-full p-4 bg-slate-50 border-2 border-transparent rounded-2xl font-bold uppercase text-xs focus:bg-white focus:border-blue-500 outline-none transition-all">
                                @foreach($roles as $r) <option value="{{ $r }}">{{ strtoupper($r) }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-2">Department</label>
                            <select wire:model="department" class="w-full p-4 bg-slate-50 border-2 border-transparent rounded-2xl font-bold text-xs focus:bg-white focus:border-blue-500 outline-none transition-all">
                                @foreach($departments as $dept) <option value="{{ $dept }}">{{ $dept }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex pt-8 space-x-4">
                    <button type="button" @click="open = false" class="flex-1 font-black text-slate-400 uppercase tracking-widest text-xs hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all uppercase text-xs">
                        Commit Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>