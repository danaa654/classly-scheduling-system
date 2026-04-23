<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500" 
     x-data="{ open: @entangle('showModal') }">
    
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header Section --}}
        <header class="h-24 bg-white dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-12 shadow-sm shrink-0 backdrop-blur-xl rounded-b-[3rem] transition-colors">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">User Management</h2>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium italic">Access Control & Faculty Directory</p>
            </div>
            
            <button @click="$wire.openModal()" class="group relative px-8 py-3 bg-red-600 text-white rounded-2xl font-black shadow-xl shadow-red-100 dark:shadow-none overflow-hidden transition-all active:scale-95">
                <span class="relative z-10">+ Add New User</span>
                <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </button>
        </header>

        {{-- Table Container --}}
        <div class="p-12 overflow-y-auto">
            {{-- Success Message Flash --}}
            @if (session()->has('message'))
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 rounded-2xl font-bold text-sm animate-bounce">
                    {{ session('message') }}
                </div>
            @endif

            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 uppercase font-black tracking-widest text-[10px]">
                        <tr>
                            <th class="px-10 py-5">Identity</th>
                            <th class="px-10 py-5">Role & Access</th>
                            <th class="px-10 py-5">College Department</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($users as $user)
                        <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors group">
                            <td class="px-10 py-6">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center font-black text-xs uppercase group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 dark:text-slate-200 tracking-tight text-base leading-none mb-1">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 font-mono italic">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-tighter">
                                    {{ str_replace('_', ' ', $user->role) }}
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <p class="text-xs text-slate-600 dark:text-slate-400 font-bold uppercase tracking-widest">
                                    {{ $user->department ?? 'Global / All Access' }}
                                </p>
                            </td>
                            <td class="px-10 py-6 text-right space-x-2">
                                <button wire:click="editUser({{ $user->id }})" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-xl font-black text-[10px] uppercase hover:bg-blue-600 hover:text-white transition-all">Edit</button>
                                
                                @if($user->id !== auth()->id())
                                <button onclick="confirm('Revoke access for this user?') || event.stopImmediatePropagation()" 
                                        wire:click="deleteUser({{ $user->id }})" 
                                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-red-600 dark:text-red-400 rounded-xl font-black text-[10px] uppercase hover:bg-red-600 hover:text-white transition-all">Delete</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-10 py-12 text-center text-slate-400 font-medium italic">No registered users found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- Registration Modal --}}
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-[3rem] p-10 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800" @click.away="open = false">
            <div class="mb-8">
                <h3 class="text-3xl font-black text-slate-800 dark:text-slate-100 tracking-tighter">{{ $editingUserId ? 'Modify' : 'Register' }} User</h3>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium italic">Configure system credentials and permissions.</p>
            </div>
            
            <form wire:submit.prevent="saveUser" class="space-y-5">
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-2">Full Legal Name</label>
                        <input type="text" wire:model="name" placeholder="John Doe" 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @error('name') <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-2">Institutional Email</label>
                        <input type="email" wire:model="email" placeholder="email@pap.edu.ph" 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @error('email') <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Show Password field ONLY when NOT editing (New Registration Only) --}}
                        @if (!$editingUserId)
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-2">Security Password</label>
                                <input type="password" wire:model="password" placeholder="••••••••" 
                                    class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                                @error('password') <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span> @enderror
                            </div>
                        @else
                            {{-- Optional: Show a small helpful note instead for the Admin --}}
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase text-center tracking-tighter">
                                    Password management is handled by the user via their Account Settings.
                                </p>
                            </div>
                        @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-2">System Role</label>
                            <select wire:model="role" class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold uppercase text-xs focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 outline-none transition-all">
                                @foreach($roles as $r) 
                                    <option value="{{ $r }}">{{ strtoupper(str_replace('_', ' ', $r)) }}</option> 
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-2">Department Scope</label>
                            <select wire:model="department" class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold text-xs focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 outline-none transition-all">
                                @foreach($departments as $dept) 
                                    <option value="{{ $dept }}">{{ $dept }}</option> 
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex pt-8 space-x-4">
                    <button type="button" @click="open = false" class="flex-1 font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest text-xs hover:text-slate-800 dark:hover:text-slate-300 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 dark:bg-blue-700 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 dark:shadow-none hover:bg-blue-700 dark:hover:bg-blue-600 transition-all uppercase text-xs">
                        {{ $editingUserId ? 'Update User' : 'Create Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>