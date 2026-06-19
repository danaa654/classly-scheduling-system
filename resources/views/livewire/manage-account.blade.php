<div class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 p-4 md:p-6 xl:p-8 transition-colors duration-500 overflow-x-hidden">
    <div class="max-w-7xl mx-auto relative">

        {{-- DECORATIVE BACKGROUND BLOBS --}}
        <div class="absolute -top-12 -left-10 w-40 h-40 bg-blue-400/20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute top-1/3 -right-10 w-56 h-56 bg-cyan-300/20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute bottom-10 left-1/3 w-52 h-52 bg-indigo-400/10 blur-3xl rounded-full pointer-events-none"></div>

        <div class="relative space-y-6">

            {{-- PROFILE HEADER --}}
            <div class="group flex flex-col md:flex-row md:items-center md:justify-between gap-6 p-5 md:p-6 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-[2rem] border border-white/70 dark:border-slate-800 shadow-[0_10px_40px_rgba(59,130,246,0.08)] hover:shadow-[0_14px_50px_rgba(59,130,246,0.14)] transition-all duration-500">
                <div class="flex items-center gap-4 md:gap-5">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-3xl bg-blue-500/30 blur-xl scale-110 group-hover:scale-125 transition-transform duration-500"></div>
                        <div class="relative inline-flex items-center justify-center w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-[1.6rem] font-black text-xl md:text-2xl shadow-[0_20px_40px_rgba(37,99,235,0.35)] transition-all duration-500 group-hover:-translate-y-1 group-hover:rotate-3">
                            {{ auth()->user()->initials() }}
                        </div>
                    </div>

                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-slate-100 uppercase tracking-tight leading-tight">
                            {{ $name }}
                        </h2>
                        <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 font-medium italic mt-1">
                            Manage your Classly account settings.
                        </p>
                    </div>
                </div>

                <div class="inline-flex items-center gap-3 self-start md:self-auto bg-slate-100/90 dark:bg-slate-800/80 border border-white/70 dark:border-slate-700 px-3 py-3 rounded-2xl shadow-inner hover:shadow-md transition-all duration-300">
                    <span class="text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-[0.22em] leading-none">
                        Your Role
                    </span>
                    <span class="px-4 py-2 bg-white dark:bg-slate-950 rounded-xl font-black text-blue-600 dark:text-blue-400 uppercase text-xs shadow-sm border border-slate-100 dark:border-slate-800">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>

            {{-- MAIN PANEL --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

                {{-- SECURITY CREDENTIALS --}}
                <div class="xl:col-span-2 group bg-white/75 dark:bg-slate-900/70 backdrop-blur-2xl p-6 md:p-8 xl:p-8 rounded-[2.5rem] border border-white/70 dark:border-slate-800 shadow-[0_20px_60px_rgba(59,130,246,0.10)] hover:shadow-[0_24px_80px_rgba(59,130,246,0.16)] transition-all duration-500">
                    <div class="flex items-center justify-between gap-4 mb-8">
                        <div>
                            <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-slate-100 uppercase tracking-tight">
                                Security Credentials
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                Keep your account protected with a strong password.
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="togglePassword"
                            class="group/eye relative inline-flex items-center justify-center w-12 h-12 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 bg-slate-100/90 dark:bg-slate-800/80 hover:bg-blue-50 dark:hover:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md border border-transparent hover:border-blue-200 dark:hover:border-slate-700 active:scale-95"
                        >
                            <span class="absolute inset-0 rounded-2xl bg-blue-500/0 group-hover/eye:bg-blue-500/10 blur-md transition-all duration-300"></span>

                            @if($showPassword)
                                <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="relative w-5 h-5 transition-transform duration-300 group-hover/eye:scale-110">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            @else
                                <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="relative w-5 h-5 transition-transform duration-300 group-hover/eye:scale-110">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            @endif
                        </button>
                    </div>

                    <form wire:submit.prevent="updatePassword" class="space-y-5">
                        {{-- CURRENT PASSWORD --}}
                        <div class="group/field">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.20em] ml-2 block mb-2">
                                Current Password
                            </label>
                            <div class="relative">
                                <input
                                    type="{{ $showPassword ? 'text' : 'password' }}"
                                    wire:model="current_password"
                                    placeholder="Enter current password"
                                    class="w-full p-4 bg-white/70 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 rounded-2xl font-bold text-slate-900 dark:text-slate-200 placeholder:text-slate-300 dark:placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none shadow-inner hover:border-blue-200 dark:hover:border-slate-600"
                                >
                                <div class="absolute inset-y-0 right-4 flex items-center text-slate-300 group-focus-within/field:text-blue-400 transition-colors pointer-events-none">
                                    <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5m-1.5 0h12a1.5 1.5 0 0 1 1.5 1.5v7.5A1.5 1.5 0 0 1 18 21H6a1.5 1.5 0 0 1-1.5-1.5V12A1.5 1.5 0 0 1 6 10.5Z" />
                                    </svg>
                                </div>
                            </div>
                            @error('current_password')
                                <p class="text-[10px] text-red-500 font-bold ml-2 mt-2 uppercase italic">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- NEW PASSWORD --}}
                        <div class="group/field">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.20em] ml-2 block mb-2">
                                New Password
                            </label>
                            <div class="relative">
                                <input
                                    type="{{ $showPassword ? 'text' : 'password' }}"
                                    wire:model.live="new_password"
                                    placeholder="Create new password"
                                    class="w-full p-4 bg-white/70 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 rounded-2xl font-bold text-slate-900 dark:text-slate-200 placeholder:text-slate-300 dark:placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none shadow-inner hover:border-blue-200 dark:hover:border-slate-600"
                                >
                                <div class="absolute inset-y-0 right-4 flex items-center text-slate-300 group-focus-within/field:text-blue-400 transition-colors pointer-events-none">
                                    <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v.01M8.25 9.75V7.5a3.75 3.75 0 1 1 7.5 0v2.25m-9 0h10.5A1.5 1.5 0 0 1 18.75 11.25v7.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- REAL-TIME REQUIREMENTS --}}
                            <div class="mt-4">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.22em] mb-3 ml-1">
                                    Security Requirements
                                </p>

                                @php
                                    $hasLength = strlen($new_password) >= 8;
                                    $hasUpper = preg_match('/[A-Z]/', $new_password);
                                    $hasNumber = preg_match('/[0-9]/', $new_password);
                                    $hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);
                                @endphp

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="flex items-center gap-3 px-3 py-3 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 transition-all shadow-inner">
                                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-500 {{ $hasLength ? 'bg-emerald-500 shadow-[0_0_14px_rgba(16,185,129,0.9)] scale-110' : 'bg-slate-300 dark:bg-slate-600' }}"></div>
                                        <span class="text-[11px] font-extrabold {{ $hasLength ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            8+ Characters
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3 px-3 py-3 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 transition-all shadow-inner">
                                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-500 {{ $hasUpper ? 'bg-emerald-500 shadow-[0_0_14px_rgba(16,185,129,0.9)] scale-110' : 'bg-slate-300 dark:bg-slate-600' }}"></div>
                                        <span class="text-[11px] font-extrabold {{ $hasUpper ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            Capital Letter
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3 px-3 py-3 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 transition-all shadow-inner">
                                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-500 {{ $hasNumber ? 'bg-emerald-500 shadow-[0_0_14px_rgba(16,185,129,0.9)] scale-110' : 'bg-slate-300 dark:bg-slate-600' }}"></div>
                                        <span class="text-[11px] font-extrabold {{ $hasNumber ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            Number (0-9)
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3 px-3 py-3 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 transition-all shadow-inner">
                                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-500 {{ $hasSpecial ? 'bg-emerald-500 shadow-[0_0_14px_rgba(16,185,129,0.9)] scale-110' : 'bg-slate-300 dark:bg-slate-600' }}"></div>
                                        <span class="text-[11px] font-extrabold {{ $hasSpecial ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            Special Character
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @error('new_password')
                                <p class="text-[10px] text-red-500 font-bold ml-2 mt-2 uppercase italic">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- CONFIRM PASSWORD --}}
                        <div class="group/field">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.20em] ml-2 block mb-2">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <input
                                    type="{{ $showPassword ? 'text' : 'password' }}"
                                    wire:model="new_password_confirmation"
                                    placeholder="Repeat new password"
                                    class="w-full p-4 bg-white/70 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 rounded-2xl font-bold text-slate-900 dark:text-slate-200 placeholder:text-slate-300 dark:placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none shadow-inner hover:border-blue-200 dark:hover:border-slate-600"
                                >
                                <div class="absolute inset-y-0 right-4 flex items-center text-slate-300 group-focus-within/field:text-blue-400 transition-colors pointer-events-none">
                                    <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m5.25 2.25c0 5.385-3.615 7.5-8.25 9-4.635-1.5-8.25-3.615-8.25-9V6.75L12 3l8.25 3.75V12Z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="relative w-full overflow-hidden p-4 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 hover:from-blue-700 hover:via-indigo-700 hover:to-blue-700 text-white font-black uppercase text-xs tracking-[0.24em] rounded-2xl shadow-[0_18px_35px_rgba(37,99,235,0.35)] transition-all duration-300 active:scale-[0.985] hover:-translate-y-0.5"
                            >
                                <span class="absolute inset-0 opacity-0 hover:opacity-100 transition-opacity duration-500 bg-[linear-gradient(120deg,transparent,rgba(255,255,255,0.2),transparent)] -translate-x-full hover:translate-x-full"></span>
                                <span class="relative">Update Credentials</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- RIGHT SIDE PANEL --}}
                <div class="xl:col-span-1 space-y-6">

                    {{-- INSTITUTIONAL SUMMARY --}}
                    <div class="group bg-white/80 dark:bg-slate-900/75 backdrop-blur-xl p-6 rounded-[2.25rem] border border-white/70 dark:border-slate-800 shadow-[0_12px_40px_rgba(15,23,42,0.06)] hover:shadow-[0_18px_50px_rgba(59,130,246,0.12)] transition-all duration-500">
                        <div class="flex items-center justify-between mb-5">
                            <h4 class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.25em]">
                                Institutional Summary
                            </h4>
                            <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-500/15 to-indigo-500/15 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-inner">
                                <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5M4.5 9.75V18a.75.75 0 0 0 .75.75H9v-5.25a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v5.25h3.75A.75.75 0 0 0 19.5 18V9.75M9 21h6" />
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="group/item flex items-center gap-4 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-950/70 border border-slate-100 dark:border-slate-800 hover:border-blue-200 dark:hover:border-slate-700 transition-all duration-300 shadow-inner">
                                <div class="flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-slate-800 dark:to-slate-700 text-lg shadow-sm group-hover/item:scale-105 transition-transform duration-300">
                                    📧
                                </div>
                                <div class="min-w-0">
                                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.20em] block">
                                        System Email
                                    </span>
                                    <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono break-all">
                                        {{ $email }}
                                    </span>
                                </div>
                            </div>

                            <div class="group/item flex items-center gap-4 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-950/70 border border-slate-100 dark:border-slate-800 hover:border-blue-200 dark:hover:border-slate-700 transition-all duration-300 shadow-inner">
                                <div class="flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-slate-800 dark:to-slate-700 text-lg shadow-sm group-hover/item:scale-105 transition-transform duration-300">
                                    🏫
                                </div>
                                <div class="min-w-0">
                                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.20em] block">
                                        Primary Department
                                    </span>
                                    <span class="text-sm font-black text-slate-800 dark:text-slate-200 uppercase tracking-tight break-words">
                                        {{ auth()->user()->department }}
                                    </span>
                                </div>
                            </div>

                            <div class="group/item flex items-center gap-4 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-950/70 border border-slate-100 dark:border-slate-800 hover:border-blue-200 dark:hover:border-slate-700 transition-all duration-300 shadow-inner">
                                <div class="flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-slate-800 dark:to-slate-700 text-lg shadow-sm group-hover/item:scale-105 transition-transform duration-300">
                                    🔐
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.20em] block">
                                        Account Status
                                    </span>
                                    <span class="inline-flex items-center gap-2 text-sm font-black text-emerald-600 dark:text-emerald-400">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.9)]"></span>
                                        Protected
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECURITY NOTICE --}}
                    {{-- SECURITY NOTICE --}}
<div class="group relative overflow-hidden p-4 rounded-2xl border border-amber-200/60 dark:border-amber-900/50 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100/70 dark:from-amber-950/30 dark:via-slate-900 dark:to-amber-950/20 shadow-[0_8px_20px_rgba(251,191,36,0.08)] hover:shadow-[0_12px_30px_rgba(251,191,36,0.14)] transition-all duration-500">

    <div class="absolute top-0 right-0 w-16 h-16 bg-amber-300/20 blur-xl rounded-full"></div>

    <div class="relative flex gap-3 items-start">
        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/70 dark:bg-amber-950/30 text-amber-500 shadow-sm border border-amber-100/80 dark:border-amber-900/30">
            <span class="text-sm">⚠️</span>
        </div>

        <div>
            <p class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-[0.15em]">
                Security Notice
            </p>

            <p class="text-xs text-amber-800/80 dark:text-amber-300/70 font-semibold mt-1.5 leading-relaxed">
                Changing your password will not end current sessions. Ensure you log out of other devices if you suspect a breach.
            </p>
        </div>
    </div>
</div>

                    {{-- PASSWORD TIP --}}
                    <div class="group bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-4 rounded-2xl shadow-[0_12px_25px_rgba(37,99,235,0.25)] hover:-translate-y-1 transition-all duration-500 relative overflow-hidden max-w-sm">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_35%)]"></div>

    <div class="relative">
        <p class="text-[8px] font-black uppercase tracking-[0.18em] text-blue-100 mb-2">
            Password Tip
        </p>

        <h5 class="text-sm font-black leading-snug">
            Use a passphrase that's easy for you to remember and hard for others to guess.
        </h5>

        <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/10 border border-white/10 text-[10px] font-bold text-blue-50 backdrop-blur-sm">
            Strong passwords reduce account risk
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</div>
