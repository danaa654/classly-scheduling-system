<div wire:poll.3s class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900 p-4 sm:p-6 lg:p-8 transition-colors duration-300 selection:bg-blue-600 selection:text-white">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-11 h-11 flex items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500/15 to-indigo-500/10 dark:from-blue-400/15 dark:to-indigo-400/10 ring-1 ring-blue-500/10 shadow-inner shadow-blue-500/10 text-xl select-none">
                        🛡️
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase text-slate-900 dark:text-white leading-none">
                            System Audit Logs
                        </h1>
                        <p class="mt-1 text-sm sm:text-[15px] text-slate-600 dark:text-slate-300 font-medium italic leading-relaxed">
                            Track and monitor academic staff login histories and security access patterns.
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-80 xl:w-96 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <input
                    wire:model.live="search"
                    type="text"
                    placeholder="Search by staff name..."
                    class="w-full pl-11 pr-4 py-3 text-sm rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md text-slate-800 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm hover:shadow-md focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all duration-300"
                >

                <div wire:loading wire:target="search" class="absolute bottom-0 left-4 right-4 h-[2px] bg-gradient-to-r from-transparent via-blue-500 to-transparent animate-pulse"></div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="relative overflow-hidden rounded-3xl border border-slate-200/70 dark:border-slate-800/80 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl shadow-[0_10px_35px_rgba(15,23,42,0.08)] dark:shadow-[0_10px_35px_rgba(0,0,0,0.35)] transition-all duration-300">
            <!-- Decorative Glow -->
            <div class="pointer-events-none absolute -top-20 -right-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl"></div>

            <div class="overflow-x-auto relative">
                <table class="w-full border-collapse text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-950 dark:bg-slate-950 text-[11px] sm:text-xs font-bold uppercase tracking-[0.12em] text-slate-200 border-b border-slate-800">
                            <th class="py-4 px-5 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <span>User Profile</span>
                                </div>
                            </th>
                            <th class="py-4 px-5 sm:px-6">Assigned System Role</th>
                            <th class="py-4 px-5 sm:px-6">IP Network Address</th>
                            <th class="py-4 px-5 sm:px-6">Login Time</th>
                            <th class="py-4 px-5 sm:px-6">Logout Time</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm text-slate-700 dark:text-slate-300">
                        @forelse($logs as $log)
                            <tr class="group hover:bg-gradient-to-r hover:from-blue-50/70 hover:to-slate-50 dark:hover:from-slate-800/60 dark:hover:to-slate-900 transition-all duration-300">
                                <td class="py-4 px-5 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[13px] font-extrabold text-slate-600 dark:text-slate-300 shadow-sm transition-all duration-300 group-hover:scale-105">
                                            {{ strtoupper(substr($log->user_name, 0, 2)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <span class="block text-sm sm:text-[15px] font-bold text-slate-800 dark:text-slate-100 tracking-normal transition-colors duration-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate max-w-[180px] sm:max-w-none">
                                                {{ $log->user_name }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-5 sm:px-6">
                                    @php
                                        $nameLower = strtolower($log->user_name);
                                        $roleLower = strtolower($log->user_role);

                                        if (str_contains($nameLower, 'ccs')) {
                                            $badgeClasses = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50';
                                        } elseif (str_contains($nameLower, 'cte')) {
                                            $badgeClasses = 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/50';
                                        } elseif (str_contains($nameLower, 'coc')) {
                                            $badgeClasses = 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/50';
                                        } elseif (str_contains($nameLower, 'shtm')) {
                                            $badgeClasses = 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/50';
                                        } else {
                                            $badgeClasses = match($roleLower) {
                                                'admin' => 'bg-slate-900 text-slate-100 border-slate-800 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
                                                'registrar' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50',
                                                'associate_dean', 'associate dean' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
                                                'oic' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/30 dark:text-cyan-400 dark:border-cyan-900/50',
                                                default => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700'
                                            };
                                        }
                                    @endphp

                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs sm:text-sm font-semibold border shadow-sm transition-all duration-300 group-hover:translate-x-0.5 {{ $badgeClasses }}">
                                        {{ ucwords(str_replace('_', ' ', $log->user_role)) }}
                                    </span>
                                </td>

                                <td class="py-4 px-5 sm:px-6">
                                    <span class="inline-flex items-center font-mono text-xs sm:text-sm bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-xl text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/50 shadow-sm transition-all duration-300 group-hover:bg-white dark:group-hover:bg-slate-900">
                                        {{ $log->ip_address }}
                                    </span>
                                </td>

                                <td class="py-4 px-5 sm:px-6 text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-300">
                                    {{ $log->login_at ? \Carbon\Carbon::parse($log->login_at)->format('M d, Y - h:i A') : '—' }}
                                </td>

                                <td class="py-4 px-5 sm:px-6 text-xs sm:text-sm font-medium">
                                    @if($log->logout_at)
                                        <span class="text-slate-600 dark:text-slate-300 transition-colors duration-300 group-hover:text-slate-800 dark:group-hover:text-white">
                                            {{ \Carbon\Carbon::parse($log->logout_at)->format('M d, Y - h:i A') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs sm:text-sm font-semibold bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-400/20 shadow-sm">
                                            <span class="relative flex h-2.5 w-2.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                            </span>
                                            Active Session
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-14 px-6 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl shadow-inner">
                                            🔍
                                        </div>
                                        <div>
                                            <p class="text-sm sm:text-base font-semibold text-slate-600 dark:text-slate-300">
                                                No system access records found.
                                            </p>
                                            <p class="text-xs sm:text-sm text-slate-400 dark:text-slate-500 mt-1">
                                                Try adjusting the staff name in the search field.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
                <div class="mt-3 mb-3 flex justify-center">
                    {{ $logs->links('livewire.custom-pagination') }}
                </div>
        </div>
    </div>
</div>
