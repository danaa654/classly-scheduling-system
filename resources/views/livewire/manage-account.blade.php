<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] p-8 md:p-12 transition-colors duration-500">
    <div class="max-w-4xl mx-auto space-y-10">

        {{-- PROFILE HEADER --}}
        <div class="flex items-center justify-between gap-6 p-6 bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
            <div class="flex items-center gap-5">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-3xl font-black text-xl shadow-2xl shadow-blue-100 dark:shadow-none transition-all">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-slate-100 uppercase tracking-tighter leading-tight">{{ $name }}</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium italic">Manage your Classly account settings.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 bg-slate-100 dark:bg-slate-800 p-3 rounded-2xl">
                <span class="text-[9px] font-black text-slate-500 dark:text-slate-600 uppercase tracking-widest leading-none">Your Role</span>
                <span class="px-4 py-2 bg-white dark:bg-slate-950 rounded-xl font-black text-blue-600 dark:text-blue-400 uppercase text-xs shadow-inner">
                    {{ auth()->user()->role }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[1.5fr,1fr] gap-10">
            
            {{-- SECURITY SECTION --}}
            <div class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl p-10 rounded-[3rem] border border-white dark:border-slate-800 shadow-2xl shadow-blue-100/50 dark:shadow-none">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-lg font-black text-slate-900 dark:text-slate-100 uppercase tracking-tight">Security Credentials</h3>
                    <button type="button" wire:click="togglePassword" class="text-slate-400 hover:text-blue-500 transition-colors p-2 bg-slate-100 dark:bg-slate-800 rounded-xl">
                        @if($showPassword)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        @endif
                    </button>
                </div>

                <form wire:submit.prevent="updatePassword" class="space-y-6">
                    {{-- CURRENT PASSWORD --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider ml-2 block mb-1.5">Current Password</label>
                        <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="current_password" placeholder="••••••••" 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-2xl font-bold text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        @error('current_password') <p class="text-[10px] text-red-500 font-bold ml-2 mt-1 uppercase italic">{{ $message }}</p> @enderror
                    </div>

                    {{-- NEW PASSWORD --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider ml-2 block mb-1.5">New Password</label>
                        <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model.live="new_password" placeholder="••••••••" 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-2xl font-bold text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                        
                        {{-- REAL-TIME REQUIREMENTS CHECKLIST --}}
                        <div class="mt-4 ml-2 space-y-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Security Requirements:</p>
                            <div class="grid grid-cols-2 gap-2">
                                @php
                                    $hasLength = strlen($new_password) >= 8;
                                    $hasUpper = preg_match('/[A-Z]/', $new_password);
                                    $hasNumber = preg_match('/[0-9]/', $new_password);
                                    $hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);
                                @endphp

                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full transition-all duration-500 {{ $hasLength ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>
                                    <span class="text-[10px] font-bold {{ $hasLength ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">8+ Characters</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full transition-all duration-500 {{ $hasUpper ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>
                                    <span class="text-[10px] font-bold {{ $hasUpper ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">Capital Letter</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full transition-all duration-500 {{ $hasNumber ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>
                                    <span class="text-[10px] font-bold {{ $hasNumber ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">Number (0-9)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full transition-all duration-500 {{ $hasSpecial ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>
                                    <span class="text-[10px] font-bold {{ $hasSpecial ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">Special Char</span>
                                </div>
                            </div>
                        </div>
                        @error('new_password') <p class="text-[10px] text-red-500 font-bold ml-2 mt-2 uppercase italic">{{ $message }}</p> @enderror
                    </div>

                    {{-- CONFIRM PASSWORD --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider ml-2 block mb-1.5">Confirm New Password</label>
                        <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="new_password_confirmation" placeholder="••••••••" 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-2xl font-bold text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:border-blue-500 focus:ring-0 transition-all outline-none">
                    </div>

                    <button type="submit" class="w-full p-4 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase text-xs tracking-[0.2em] rounded-2xl shadow-lg shadow-blue-500/30 transition-all active:scale-[0.98]">
                        Update Credentials
                    </button>
                </form>
            </div>

            {{-- INSTITUTIONAL INFO --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                    <h4 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-6">Institutional Summary</h4>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl shadow-inner">
                            <span class="text-xl">📧</span>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 uppercase">System Email</span>
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200 font-mono tracking-tighter">{{ $email }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl shadow-inner">
                            <span class="text-xl">🏫</span>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 uppercase">Primary Department</span>
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-tight">{{ auth()->user()->department }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECURITY NOTICE --}}
                <div class="p-6 bg-amber-50 dark:bg-amber-950/30 rounded-3xl border border-amber-100 dark:border-amber-900/50">
                    <div class="flex gap-3">
                        <span class="text-amber-500 text-lg">⚠️</span>
                        <div>
                            <p class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-wider">Security Notice</p>
                            <p class="text-[10px] text-amber-700/70 dark:text-amber-500/60 font-bold mt-1 leading-relaxed">
                                Changing your password will not end current sessions. Ensure you logout of other devices if you suspect a breach.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>