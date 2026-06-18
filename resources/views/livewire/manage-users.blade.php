<div class="min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500"
     x-data="{ open: @entangle('showModal'), showPassword: false }">

    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header Section --}}
        <header class="mx-auto mt-3 w-[98%] max-w-[1800px] bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-5 py-2.5 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">
            <div class="flex items-center gap-3">
                <h2 class="text-sm md:text-base font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">
                    User Management
                </h2>
                <p class="text-xs md:text-sm text-slate-500 dark:text-indigo-400/80 font-medium italic">
                    Access Control & Faculty Directory
                </p>
            </div>

            <button @click="$wire.openModal()"
                class="group relative px-6 md:px-8 py-2.5 md:py-3 bg-red-600 text-white rounded-2xl font-black text-sm md:text-base shadow-xl shadow-red-100 dark:shadow-none overflow-hidden transition-all active:scale-95">
                <span class="relative z-10">+ Add New User</span>
                <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </button>
        </header>

        {{-- Table Container --}}
        <div class="p-5 md:p-8 lg:p-10 overflow-y-auto">
            {{-- Success Message Flash --}}
            @if (session()->has('message'))
                <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 rounded-2xl font-bold text-sm">
                    {{ session('message') }}
                </div>
            @endif

            <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/70 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 uppercase font-black tracking-widest text-[10px] md:text-xs">
                        <tr>
                            <th class="px-6 md:px-8 py-4">Identity</th>
                            <th class="px-6 md:px-8 py-4">Role & Access</th>
                            <th class="px-6 md:px-8 py-4">College Department</th>
                            <th class="px-6 md:px-8 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($users as $user)
                            <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors group">
                                <td class="px-6 md:px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center font-black text-sm uppercase group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div class="leading-tight">
                                            <p class="font-black text-slate-800 dark:text-slate-200 tracking-tight text-sm md:text-base mb-1">
                                                {{ $user->name }}
                                            </p>
                                            <p class="text-[11px] md:text-xs text-slate-400 dark:text-slate-500 font-mono italic break-all">
                                                {{ $user->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 md:px-8 py-5">
                                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 rounded-lg text-[10px] md:text-[11px] font-black uppercase tracking-tight">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>

                                <td class="px-6 md:px-8 py-5">
                                    <p class="text-[11px] md:text-xs text-slate-600 dark:text-slate-400 font-bold uppercase tracking-widest">
                                        {{ $user->department ?? 'Global / All Access' }}
                                    </p>
                                </td>

                                <td class="px-6 md:px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="editUser({{ $user->id }})"
                                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-xl font-black text-[10px] md:text-[11px] uppercase hover:bg-blue-600 hover:text-white transition-all">
                                            Edit
                                        </button>

                                        @if($user->id !== auth()->id())
                                            <button
                                                onclick="confirm('Revoke access for this user?') || event.stopImmediatePropagation()"
                                                wire:click="deleteUser({{ $user->id }})"
                                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-red-600 dark:text-red-400 rounded-xl font-black text-[10px] md:text-[11px] uppercase hover:bg-red-600 hover:text-white transition-all">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-slate-400 font-medium italic text-sm">
                                    No registered users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- Registration Modal --}}
    <div x-show="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-md px-4"
         x-cloak
         x-transition>
        <div class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-[2.25rem] p-6 md:p-8 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800"
             @click.away="open = false">

            <div class="mb-5">
                <h3 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-slate-100 tracking-tight">
                    {{ $editingUserId ? 'Modify' : 'Register' }} User
                </h3>
                <p class="text-xs md:text-sm text-slate-400 dark:text-slate-500 font-medium italic mt-1">
                    Configure system credentials and permissions.
                </p>
            </div>

            <form wire:submit.prevent="saveUser" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-[10px] md:text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-1 mb-2">
                            Full Legal Name
                        </label>
                        <input type="text"
                               wire:model="name"
                               placeholder="Simon Demo"
                               class="w-full h-12 md:h-13 px-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-semibold text-sm md:text-base focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @error('name')
                            <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] md:text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-1 mb-2">
                            Institutional Email
                        </label>
                        <input type="email"
                               wire:model="email"
                               placeholder="email@pap.edu.ph"
                               class="w-full h-12 md:h-13 px-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-semibold text-sm md:text-base focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @error('email')
                            <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    @if (!$editingUserId)
                        <div>
                            <label class="block text-[10px] md:text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-1 mb-2">
                                Security Password
                            </label>

                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       wire:model="password"
                                       placeholder="••••••••"
                                       class="w-full h-12 md:h-13 pl-4 pr-12 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-semibold text-sm md:text-base focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">

                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 px-4 flex items-center text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                        tabindex="-1">
                                    <svg x-show="!showPassword" xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>

                                    <svg x-show="showPassword" x-cloak xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M6.228 6.228A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a2.99 2.99 0 00-2.121.879M12 9l0 0m-9 12L21 3" />
                                    </svg>
                                </button>
                            </div>

                            @error('password')
                                <span class="text-[10px] text-red-500 ml-2 font-bold">{{ $message }}</span>
                            @enderror
                        </div>
                    @else
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                            <p class="text-[10px] md:text-[11px] text-slate-400 dark:text-slate-500 font-bold uppercase text-center tracking-tight">
                                Password management is handled by the user via their Account Settings.
                            </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] md:text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-1 mb-2">
                                System Role
                            </label>
                            <select wire:model="role"
                                class="w-full h-12 md:h-13 px-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold uppercase text-xs md:text-sm focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 outline-none transition-all">
                                @foreach($roles as $r)
                                    <option value="{{ $r }}">{{ strtoupper(str_replace('_', ' ', $r)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] md:text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest ml-1 mb-2">
                                Department Scope
                            </label>
                            <select wire:model="department"
                                class="w-full h-12 md:h-13 px-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent dark:text-slate-200 rounded-2xl font-bold text-xs md:text-sm focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 outline-none transition-all">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <button type="button"
                            @click="open = false"
                            class="flex-1 h-12 font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest text-xs hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                        Cancel
                    </button>

                    <button type="submit"
                            class="flex-1 h-12 bg-blue-600 dark:bg-blue-700 text-white rounded-[1.25rem] font-black shadow-xl shadow-blue-100 dark:shadow-none hover:bg-blue-700 dark:hover:bg-blue-600 transition-all uppercase text-xs md:text-sm">
                        {{ $editingUserId ? 'Update User' : 'Create Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
