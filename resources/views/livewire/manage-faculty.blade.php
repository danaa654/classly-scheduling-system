<div class="min-h-screen bg-[#eef3f8] dark:bg-[#020617] transition-colors duration-500"
    x-data="{
        open: @entangle('showModal'),
        bulk: @entangle('bulkOpen'),
        confirmDelete: @entangle('confirmingDeletion'),
        deptOpen: false
    }">
    <main class="w-full flex flex-col">

        {{-- Header Section --}}
        <header class="mx-auto mt-3 w-[98%] max-w-[1800px] bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 flex items-center justify-between px-6 py-2 shadow-xl backdrop-blur-xl rounded-full transition-colors z-20">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-tight">
                    Faculty List
                </h2>
                <p class="text-sm text-slate-500 dark:text-indigo-400/80 font-medium italic">Academic Personnel Management</p>
            </div>

            <div class="flex items-center space-x-3">
                @if(count($selectedFaculty) > 0)
                    <button
                        @click="confirmDelete = true"
                        class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                            rounded-2xl text-sm font-black border border-red-100 dark:border-red-900/30 shadow-sm
                            transition-all duration-300 ease-out hover:-translate-y-0.5 hover:scale-[1.02]
                            hover:bg-white dark:hover:bg-red-900/30
                            hover:shadow-[0_10px_25px_rgba(239,68,68,0.16)]
                            active:scale-[0.98] active:translate-y-0 flex items-center">
                        <span class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-r from-red-200/30 via-white/70 to-red-200/30 dark:from-red-700/20 dark:via-red-500/10 dark:to-red-700/20"></span>
                        <span class="absolute -inset-y-full left-[-40%] w-1/3 rotate-12 bg-white/40 dark:bg-white/10 blur-md group-hover:left-[120%] transition-all duration-700"></span>
                        <span class="relative z-10 mr-2 text-base transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-0.5">🗑️</span>
                        <span class="relative z-10">Delete ({{ count($selectedFaculty) }})</span>
                    </button>
                @endif

                <button
                    wire:click="exportCSV"
                    wire:loading.attr="disabled"
                    class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-green-50 dark:bg-green-900/10 text-green-600 dark:text-green-400
                        rounded-2xl text-sm font-black border border-green-100 dark:border-green-900/20 shadow-sm
                        transition-all duration-300 ease-out
                        hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white dark:hover:bg-slate-700
                        hover:shadow-[0_10px_25px_rgba(34,197,94,0.18)] dark:hover:shadow-[0_10px_25px_rgba(0,0,0,0.35)]
                        active:scale-[0.98] active:translate-y-0 flex items-center disabled:opacity-60">
                    <span class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-r from-green-200/40 via-white/70 to-green-200/40 dark:from-green-700/20 dark:via-slate-600/20 dark:to-green-700/20"></span>
                    <span class="absolute -inset-y-full left-[-40%] w-1/3 rotate-12 bg-white/40 dark:bg-white/10 blur-md group-hover:left-[120%] transition-all duration-700"></span>

                    <span wire:loading.remove wire:target="exportCSV" class="relative z-10 flex items-center">
                        <span class="mr-2 text-base transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-0.5">📊</span>
                        <span>Export CSV</span>
                    </span>
                    <span wire:loading wire:target="exportCSV" class="relative z-10">⏳...</span>
                </button>

                @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                    <button
                        @click="bulk = true"
                        class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200
                            rounded-2xl text-sm font-black border border-slate-200 dark:border-slate-700 shadow-sm
                            transition-all duration-300 ease-out
                            hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white dark:hover:bg-slate-700
                            hover:shadow-[0_10px_25px_rgba(15,23,42,0.12)] dark:hover:shadow-[0_10px_25px_rgba(0,0,0,0.35)]
                            active:scale-[0.98] active:translate-y-0 flex items-center">
                        <span class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-r from-slate-200/40 via-white/70 to-slate-200/40 dark:from-slate-700/20 dark:via-slate-600/20 dark:to-slate-700/20"></span>
                        <span class="absolute -inset-y-full left-[-40%] w-1/3 rotate-12 bg-white/40 dark:bg-white/10 blur-md group-hover:left-[120%] transition-all duration-700"></span>
                        <span class="relative z-10 mr-2 text-base transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-0.5">📥</span>
                        <span class="relative z-10">Bulk Import</span>
                    </button>
                @endif

                <button
                    @click="open = true"
                    wire:click="openModal"
                    class="group relative overflow-hidden cursor-pointer px-5 py-2.5 bg-blue-600 text-white rounded-2xl text-sm
                        font-black shadow-md shadow-blue-200 dark:shadow-none
                        transition-all duration-300 ease-out active:scale-95
                        hover:-translate-y-0.5 hover:scale-[1.02]
                        hover:shadow-[0_12px_30px_rgba(37,99,235,0.35)]">
                    <span class="relative z-10">
                        + {{ in_array(auth()->user()->role, ['admin', 'registrar']) ? 'Add Faculty' : 'Request' }}
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-indigo-500 to-blue-700 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="absolute -inset-y-full left-[-30%] w-1/3 rotate-12 bg-white/30 blur-md group-hover:left-[120%] transition-all duration-700"></div>
                </button>
            </div>
        </header>

        {{-- Pending Approvals --}}
        @if($this->isAdminOrRegistrar() && $pendingRequests->count() > 0)
            <div class="mx-auto mt-6 w-[98%] max-w-[1800px] bg-white/60 dark:bg-slate-900/60 backdrop-blur-md rounded-3xl border border-white dark:border-slate-800 shadow-md shadow-blue-100/50 dark:shadow-none p-5 transition-colors">
                <div class="flex items-center justify-between mb-4 px-2">
                    <h3 class="text-base font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                        <span class="h-2 w-2 bg-amber-500 rounded-full animate-pulse"></span>
                        Pending Approvals
                    </h3>
                    <span class="text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 font-bold px-3 py-1.5 rounded-full">
                        {{ $pendingRequests->count() }} REQUESTS
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($pendingRequests as $request)
                        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-4 rounded-2xl hover:shadow-lg transition-all group">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">{{ $request->full_name }}</p>
                                    <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase">{{ $request->displayDepartment() }} / {{ $request->scopeLabel() }}</p>
                                </div>

                                <div class="flex gap-1">
                                    <button wire:click="approveFaculty({{ $request->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="approveFaculty"
                                        class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 transition-all disabled:opacity-50">
                                        <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button wire:click="declineFaculty({{ $request->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="declineFaculty"
                                        class="p-2.5 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-xl hover:bg-red-600 hover:text-white transition-all disabled:opacity-50">
                                        <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mx-auto w-[98%] max-w-[1800px] px-2 py-6">
            <div class="grid grid-cols-12 gap-6 items-start">

                {{-- Left Content: Search + Table --}}
                <div class="col-span-12 xl:col-span-10 space-y-6 min-w-0">

                    {{-- Search and Filters --}}
                    <div class="relative z-40 flex flex-col gap-4 bg-white dark:bg-slate-900/40 p-3 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-md">
                        <div class="flex flex-col xl:flex-row xl:items-center gap-3">
                            {{-- Search --}}
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-5 flex items-center text-slate-400">
                                    <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or ID..."
                                    class="w-full pl-14 pr-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-base text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>

                            {{-- Type Filter --}}
                            <div class="min-w-[180px]">
                                <select wire:model.live="filterType" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-bold uppercase py-3 px-5 shadow-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Types</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                </select>
                            </div>

                            {{-- Scope Filter --}}
                            <div class="min-w-[180px]">
                                <select wire:model.live="filterScope" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-bold uppercase py-3 px-5 shadow-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Scopes</option>
                                    @foreach($scopeOptions ?? [] as $scopeValue => $scopeLabel)
                                        <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Department Dropdown --}}
                            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'associate_dean']))
                                <div class="relative min-w-[180px]" @click.away="deptOpen = false">
                                    <button
                                        type="button"
                                        @click="deptOpen = !deptOpen"
                                        class="w-full px-5 py-3 rounded-2xl text-sm font-black transition-all bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-200 shadow-sm flex items-center justify-between gap-3">
                                        <span>{{ $filterDepartment ?: 'ALL' }}</span>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="deptOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    <div
                                        x-show="deptOpen"
                                        x-transition
                                        x-cloak
                                        class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl z-50 overflow-hidden">
                                        <button
                                            type="button"
                                            @click="$wire.set('filterDepartment', ''); deptOpen = false"
                                            class="w-full text-left px-4 py-3 text-sm font-bold uppercase transition-all hover:bg-blue-50 dark:hover:bg-slate-800 {{ $filterDepartment === '' ? 'text-blue-600 dark:text-blue-400 bg-blue-50/70 dark:bg-slate-800' : 'text-slate-700 dark:text-slate-200' }}">
                                            ALL
                                        </button>

                                        @foreach($departments ?? ['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                            <button
                                                type="button"
                                                @click="$wire.set('filterDepartment', '{{ $dept }}'); deptOpen = false"
                                                class="w-full text-left px-4 py-3 text-sm font-bold uppercase transition-all hover:bg-blue-50 dark:hover:bg-slate-800 {{ $filterDepartment === $dept ? 'text-blue-600 dark:text-blue-400 bg-blue-50/70 dark:bg-slate-800' : 'text-slate-700 dark:text-slate-200' }}">
                                                {{ $dept }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 text-blue-600 dark:text-blue-400 shadow-sm rounded-2xl text-sm font-black uppercase min-w-[180px] text-center">
                                    Dept: {{ auth()->user()->department }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Main Table --}}
<div class="relative z-10 bg-white dark:bg-slate-900/40 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-md overflow-hidden">
    <div class="w-full">
        <table class="w-full table-auto text-sm">
            <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase font-black text-xs tracking-widest border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="w-12 px-4 py-4 text-center align-middle">
                        @if(in_array(auth()->user()->role, ['admin', 'registrar']))
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                        @endif
                    </th>

                    <th class="px-4 py-4 text-left align-middle whitespace-nowrap">Status</th>
                    <th class="px-4 py-4 text-left align-middle whitespace-nowrap">ID Number</th>
                    <th class="px-4 py-4 text-left align-middle whitespace-nowrap">Full Name</th>
                    <th class="px-4 py-4 text-left align-middle whitespace-nowrap">Department</th>
                    <th class="px-4 py-4 text-left align-middle whitespace-nowrap">Employment & Workload</th>
                    <th class="px-4 py-4 text-center align-middle whitespace-nowrap">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($faculties as $faculty)
                    <tr
                        class="transition-all hover:bg-slate-100/80 dark:hover:bg-slate-800/70
                        {{ in_array($faculty->id, $selectedFaculty) ? '!bg-blue-50 dark:!bg-blue-900/20' : ($loop->odd ? 'bg-white dark:bg-slate-900/20' : 'bg-[#fcfafa] dark:bg-slate-800/60') }}">

                        {{-- Checkbox --}}
                        <td class="px-4 py-5 text-center align-middle">
                            @php
                                $canSelect = in_array(auth()->user()->role, ['admin', 'registrar']) ||
                                            (auth()->user()->role === 'dean' && $faculty->status === 'rejected') ||
                                            (auth()->user()->role === 'oic' && $faculty->status === 'rejected' && $faculty->department === auth()->user()->department);
                            @endphp

                            @if($canSelect)
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedFaculty"
                                    value="{{ $faculty->id }}"
                                    class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-5 align-middle whitespace-nowrap">
                            @if($faculty->status === 'approved')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-[11px] font-black uppercase whitespace-nowrap">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 text-[11px] font-black uppercase whitespace-nowrap">
                                    {{ $faculty->status }}
                                </span>
                            @endif
                        </td>

                        {{-- ID Number --}}
                        <td class="px-4 py-5 align-middle text-sm font-black text-slate-500 dark:text-slate-400 italic whitespace-nowrap">
                            {{ $faculty->employee_id }}
                        </td>

                        {{-- Full Name --}}
                        <td class="px-4 py-5 align-middle">
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-5 break-words">
                                {{ $faculty->full_name }}
                            </div>
                        </td>

                        {{-- Department --}}
                        <td class="px-4 py-5 align-middle">
                            <div class="flex items-center min-h-[76px]">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black uppercase whitespace-nowrap bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    {{ $faculty->displayDepartment() }}
                                </span>
                            </div>
                        </td>

                        {{-- Employment & Workload --}}
                        <td class="px-4 py-5 align-middle">
                            @php
                                $scopeClasses = match($faculty->faculty_scope) {
                                    \App\Models\Faculty::SCOPE_GENED => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                    \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
                                    default => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
                                };
                            @endphp

                            <div class="grid grid-cols-2 gap-2 max-w-[260px]">
                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-2 {{ $faculty->employment_type === 'Full-time' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' }} rounded-xl text-[10px] font-black uppercase whitespace-nowrap">
                                    @if($faculty->employment_type === 'Full-time')
                                        <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        FT
                                    @else
                                        <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
                                        </svg>
                                        PT
                                    @endif
                                </span>

                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-2 {{ $scopeClasses }} rounded-xl text-[10px] font-black uppercase text-center">
                                    {{ $faculty->scopeLabel() }}
                                </span>

                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-2 {{ $faculty->canTeachMinorSubjects() ? 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300' }} rounded-xl text-[10px] font-black uppercase text-center whitespace-nowrap">
                                    {{ $faculty->canTeachMinorSubjects() ? 'Minor OK' : 'Major Only' }}
                                </span>

                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-[10px] font-black uppercase whitespace-nowrap">
                                    📊 {{ $faculty->max_units ?? 21 }} Units
                                </span>
                            </div>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-5 align-middle text-center">
                            @php
                                $user = auth()->user();
                                $isAdminOrRegistrar = in_array($user->role, ['admin', 'registrar']);
                                $isAssocDean = $user->role === 'associate_dean';
                                $isDeptHead = in_array($user->role, ['dean', 'oic']) && ($user->department === $faculty->department);
                                $isRejected = $faculty->status === 'rejected';
                            @endphp

                            @if($isAdminOrRegistrar)
                                <div class="flex flex-col items-center gap-2">
                                    <button
                                        @click="open = true"
                                        wire:click="editFaculty({{ $faculty->id }})"
                                        class="inline-flex items-center justify-center w-[100px] px-3 py-2 rounded-xl
                                            bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                                            border border-blue-200 dark:border-blue-800
                                            hover:bg-blue-600 hover:text-white hover:border-blue-600
                                            transition-all duration-200 font-black text-xs">
                                        Edit
                                    </button>

                                    <button
                                        wire:click="deleteFaculty({{ $faculty->id }})"
                                        wire:confirm="Permanently delete {{ $faculty->full_name }}?"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center w-[100px] px-3 py-2 rounded-xl
                                            bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                            border border-red-200 dark:border-red-800
                                            hover:bg-red-600 hover:text-white hover:border-red-600
                                            transition-all duration-200 font-black text-xs">
                                        Delete
                                    </button>
                                </div>
                            @elseif(($isAssocDean || $isDeptHead) && $isRejected)
                                <div class="flex flex-col items-center gap-2">
                                    <button
                                        wire:click="deleteFaculty({{ $faculty->id }})"
                                        wire:confirm="Remove this rejected application for {{ $faculty->full_name }}?"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center w-[100px] px-3 py-2 rounded-xl
                                            bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                            border border-red-200 dark:border-red-800
                                            hover:bg-red-600 hover:text-white hover:border-red-600
                                            transition-all duration-200 font-black text-xs">
                                        Delete
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center justify-center min-h-[76px]">
                                    <span class="text-[10px] text-slate-400 font-black uppercase italic tracking-tight">
                                        Locked
                                    </span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-10 py-16 text-center text-slate-400 dark:text-slate-600 font-black uppercase text-sm italic tracking-widest bg-white dark:bg-slate-900/20">
                            No matching records.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


                    <div class="mt-4 mb-2">
                        {{ $faculties->links('livewire.custom-pagination') }}
                    </div>
                </div>

                {{-- Sidebar: Recent Activity --}}
                <div class="col-span-12 xl:col-span-2 min-w-0">
                    <div class="bg-white dark:bg-slate-900/60 p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm xl:sticky xl:top-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-black text-slate-800 dark:text-slate-200 uppercase text-[11px] tracking-[0.2em]">
                                Recent Activity
                            </h3>
                            <span class="flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                        </div>

                        <div class="space-y-3 max-h-[650px] xl:max-h-[calc(100vh-8rem)] overflow-y-auto pr-1 custom-scrollbar">
                            @forelse($recentLogs as $log)
                                <div class="group relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-100 dark:border-slate-800 p-3 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                                    <div class="absolute left-0 top-0 bottom-0 w-1
                                        {{ $log->action == 'created' ? 'bg-emerald-500' : '' }}
                                        {{ in_array($log->action, ['deleted', 'rejected']) ? 'bg-rose-500' : '' }}
                                        {{ $log->action == 'approved' ? 'bg-blue-500' : '' }}
                                        {{ $log->action == 'updated' ? 'bg-orange-500' : '' }}">
                                    </div>

                                    <div class="pl-2">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <span class="text-[10px] font-black uppercase tracking-widest
                                                {{ $log->action == 'created' ? 'text-emerald-500' : '' }}
                                                {{ in_array($log->action, ['deleted', 'rejected']) ? 'text-rose-500' : '' }}
                                                {{ $log->action == 'approved' ? 'text-blue-500' : '' }}
                                                {{ $log->action == 'updated' ? 'text-orange-500' : '' }}">
                                                {{ $log->action }}
                                            </span>

                                            <span class="text-[10px] text-slate-400 font-semibold whitespace-nowrap">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        <h4 class="text-[12px] font-bold text-slate-700 dark:text-slate-200 leading-6 break-words">
                                            {{ $log->description }}
                                        </h4>

                                        <div class="mt-3 flex flex-col gap-2">
                                            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl w-fit">
                                                <div class="w-4 h-4 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-[8px] font-bold text-white uppercase">
                                                    {{ substr($log->user->name ?? 'S', 0, 1) }}
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                                    {{ $log->user->name ?? 'System Admin' }}
                                                </span>
                                            </div>

                                            <span class="text-[9px] px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 text-slate-400 uppercase font-black tracking-tight w-fit">
                                                {{ $log->user->role ?? 'ADMIN' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-10 opacity-60">
                                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-full mb-3">
                                        <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-[11px] text-slate-400 italic font-bold uppercase tracking-widest text-center">
                                        No recent registry activity.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL: Request/Edit --}}
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
            <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" @click.away="open = false">
                <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tighter mb-6">
                    {{ $isEditMode ? '✏️ Update Record' : '➕ New Registration' }}
                </h3>

                <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">Employee ID</label>
                            <input type="text"
                                wire:model="employee_id"
                                placeholder="e.g. 2026-1001"
                                {{ $isEditMode ? 'disabled' : '' }}
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:opacity-50 @error('employee_id') ring-2 ring-red-500 @enderror">
                            @error('employee_id')
                                <span class="text-red-500 text-[11px] font-bold ml-2 italic">⚠️ {{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">Department</label>

                            @php
                                $isDeanOrOIC = in_array(auth()->user()->role, ['dean', 'oic']);
                            @endphp

                            @if($faculty_scope === \App\Models\Faculty::SCOPE_GENED)
                                <div class="w-full px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl font-bold text-sm text-blue-700 dark:text-blue-300">
                                    Institution-wide
                                </div>
                                <p class="mt-2 text-[11px] font-bold text-blue-500 dark:text-blue-300 italic">GenEd faculty may teach institution-wide subjects.</p>
                            @elseif($isDeanOrOIC && auth()->user()->department)
                                <div class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-2xl font-black text-sm text-blue-600 dark:text-blue-400 flex items-center justify-between">
                                    <span>{{ auth()->user()->department }}</span>
                                    <span class="text-[10px] bg-blue-100 dark:bg-blue-900/30 px-2.5 py-1 rounded-md text-blue-700 dark:text-blue-300">LOCKED</span>
                                </div>
                                <input type="hidden" wire:model="department" value="{{ auth()->user()->department }}">
                            @else
                                <div class="relative">
                                    <select wire:model="department"
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('department') ring-2 ring-red-500 @enderror">
                                        <option value="">Select Department</option>
                                        @foreach($departments ?? ['CCS', 'CTE', 'COC', 'SHTM'] as $dept)
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            @endif

                            @error('department')
                                <span class="text-red-500 text-[11px] font-bold ml-2">⚠️ {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">Full Name</label>
                        <input type="text"
                            wire:model.blur="full_name"
                            placeholder="Firstname Lastname"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('full_name') ring-2 ring-red-500 @enderror">
                        @error('full_name')
                            <span class="text-red-500 text-[11px] font-bold ml-2 italic">⚠️ {{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">Email Address</label>
                        <input type="email"
                            wire:model.blur="email"
                            placeholder="example@email.com"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('email') ring-2 ring-red-500 @enderror">
                        @error('email')
                            <span class="text-red-500 text-[11px] font-bold ml-2 italic">⚠️ {{ $message }}</span>
                        @enderror
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-5 mt-5">
                        <p class="text-[11px] font-black text-slate-600 dark:text-slate-400 uppercase mb-4">📅 Scheduling Information</p>

                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">⏱️ Employment Type</label>
                                <div class="relative">
                                    <select wire:model="employment_type"
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('employment_type') ring-2 ring-red-500 @enderror">
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                                @error('employment_type')
                                    <span class="text-red-500 text-[11px] font-bold ml-2">⚠️ {{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">Faculty Scope</label>
                                <div class="relative">
                                    <select wire:model.live="faculty_scope"
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 appearance-none @error('faculty_scope') ring-2 ring-red-500 @enderror">
                                        @foreach($scopeOptions ?? [] as $scopeValue => $scopeLabel)
                                            <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                                @error('faculty_scope')
                                    <span class="text-red-500 text-[11px] font-bold ml-2">⚠️ {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <label class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 dark:bg-slate-800/50 px-4 py-3 cursor-pointer">
                            <span>
                                <span class="block text-xs font-black text-slate-600 dark:text-slate-300 uppercase">Can Teach Minor / GenEd</span>
                                <span class="block text-[11px] font-bold text-slate-400 italic mt-0.5">Required for minor and general education assignments.</span>
                            </span>
                            <input type="checkbox" wire:model.live="can_teach_minor" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @if($faculty_scope === \App\Models\Faculty::SCOPE_GENED) disabled @endif>
                        </label>
                        @error('can_teach_minor')
                            <span class="text-red-500 text-[11px] font-bold ml-2">{{ $message }}</span>
                        @enderror

                        <div class="mt-4">
                            <label class="text-xs font-black text-slate-500 uppercase ml-1 block mb-2">📊 Maximum Teaching Units</label>
                            <input type="number"
                                wire:model="max_units"
                                placeholder="21"
                                min="1" max="30"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl font-bold text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('max_units') ring-2 ring-red-500 @enderror">
                            <p class="text-[11px] text-slate-400 mt-2 ml-1 italic font-semibold">ℹ️ Part-time: max 18 units | Full-time: max 30 units</p>
                            @error('max_units')
                                <span class="text-red-500 text-[11px] font-bold ml-2">⚠️ {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button wire:click="{{ $isEditMode ? 'updateFaculty' : 'saveFaculty' }}"
                        wire:loading.attr="disabled"
                        class="w-full py-3.5 mt-4 bg-blue-600 dark:bg-blue-700 text-white rounded-2xl font-black uppercase text-sm shadow-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                        <span wire:loading.remove>{{ $isEditMode ? '💾 Save Changes' : '✓ Confirm Registration' }}</span>
                        <span wire:loading wire:target="saveFaculty,updateFaculty">⏳ Processing...</span>
                    </button>

                    <button @click="open = false"
                        type="button"
                        class="w-full py-2.5 mt-2 text-slate-400 dark:text-slate-500 font-black uppercase text-xs hover:text-red-500 transition-colors">
                        ✕ Close
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL: Bulk Import --}}
        <div x-show="bulk" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak x-transition>
            <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto" @click.away="bulk = false">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tighter">📥 Batch Import</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-bold italic mt-2">
                            Upload a CSV file with faculty data and scheduling preferences
                        </p>
                    </div>
                    <button @click="bulk = false" class="text-slate-400 hover:text-red-500 text-2xl font-black transition-colors">✕</button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-900/40">
                        <p class="text-xs font-black text-blue-900 dark:text-blue-200 uppercase mb-3">📋 Required CSV Columns:</p>
                        <ul class="space-y-2 text-[11px] font-mono font-bold text-blue-900 dark:text-blue-300">
                            <li>✓ <strong>employee_id</strong> - Unique identifier</li>
                            <li>✓ <strong>full_name</strong> - First and Last name</li>
                            <li>✓ <strong>email</strong> - Valid email address</li>
                            <li>✓ <strong>department</strong> - optional for gened</li>
                            <li>✓ <strong>employment_type</strong> - Full-time / Part-time</li>
                            <li>✓ <strong>faculty_scope</strong> - gened / departmental / cross_department</li>
                            <li>✓ <strong>can_teach_minor</strong> - yes/no, true/false, or 1/0</li>
                            <li>✓ <strong>max_units</strong> - 1-30 (auto-filled if blank)</li>
                        </ul>
                    </div>

                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-900/40">
                        <p class="text-xs font-black text-green-900 dark:text-green-200 uppercase mb-3">✓ Example CSV Row:</p>
                        <div class="space-y-1 text-[11px] font-mono text-green-900 dark:text-green-300 bg-white dark:bg-slate-800 p-3 rounded-xl border border-green-100/50 dark:border-green-800/30">
                            <p><span class="text-slate-500 dark:text-slate-400">employee_id:</span> 2026-1001</p>
                            <p><span class="text-slate-500 dark:text-slate-400">full_name:</span> Juan Dela Cruz</p>
                            <p><span class="text-slate-500 dark:text-slate-400">email:</span> juan@pap.edu.ph</p>
                            <p><span class="text-slate-500 dark:text-slate-400">department:</span> GENED</p>
                            <p><span class="text-slate-500 dark:text-slate-400">employment_type:</span> Full-time</p>
                            <p><span class="text-slate-500 dark:text-slate-400">faculty_scope:</span> gened</p>
                            <p><span class="text-slate-500 dark:text-slate-400">can_teach_minor:</span> YES</p>
                            <p><span class="text-slate-500 dark:text-slate-400">max_units:</span> 30</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    @error('importFile')
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 rounded-2xl flex items-start gap-4 text-red-600 dark:text-red-400">
                            <span class="text-2xl">⚠️</span>
                            <div>
                                <p class="text-sm font-black uppercase tracking-wider">Upload Error</p>
                                <p class="text-xs font-semibold mt-1">{{ $message }}</p>
                            </div>
                        </div>
                    @enderror

                    <div class="relative border-2 border-dashed {{ $importFile ? 'border-green-400 dark:border-green-600 bg-green-50/50 dark:bg-green-900/10' : 'border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-slate-50 dark:hover:bg-slate-800/50' }} rounded-3xl p-10 text-center transition-all">
                        <input type="file" wire:model.live="importFile" accept=".csv,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="space-y-4">
                            <div class="text-4xl">{{ $importFile ? '✅' : '📂' }}</div>
                            <div class="text-slate-600 dark:text-slate-400 font-bold text-base">
                                <span wire:loading.remove wire:target="importFile">
                                    {{ $importFile ? '📄 Ready: ' . $importFile->getClientOriginalName() : 'Click or drag a CSV file to upload' }}
                                </span>
                                <span wire:loading wire:target="importFile" class="text-blue-600 dark:text-blue-400 animate-pulse">⏳ Scanning file structure...</span>
                            </div>
                        </div>
                    </div>

                    @if(count($importPreview) > 0)
                        <div>
                            <p class="text-xs font-black text-slate-600 dark:text-slate-400 uppercase mb-3">📊 Import Preview ({{ count($importPreview) }} records)</p>
                            <div class="max-h-80 overflow-auto border border-slate-200 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-900/40 custom-scrollbar">
                                <table class="w-full text-left text-[11px]">
                                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest border-b border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">ID</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Name</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Email</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Dept</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Type</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Scope</th>
                                            <th class="px-4 py-3 whitespace-nowrap z-10">Minor</th>
                                            <th class="px-4 py-3 whitespace-nowrap text-center z-10">Units</th>
                                            <th class="px-4 py-3 whitespace-nowrap text-right z-10">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        @foreach($importPreview as $preview)
                                            <tr class="hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors {{ $preview['error'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                                <td class="px-4 py-2.5 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $preview['employee_id'] }}</td>
                                                <td class="px-4 py-2.5 font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ $preview['full_name'] }}</td>
                                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400 truncate max-w-[120px]" title="{{ $preview['email'] }}">{{ $preview['email'] }}</td>
                                                <td class="px-4 py-2.5">
                                                    <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-md font-bold text-[10px]">{{ $preview['department'] }}</span>
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @if($preview['employment_type'] === 'Part-time')
                                                        <span class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400 font-black text-[10px]">⏰ PT</span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-black text-[10px]">⏱️ FT</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @php
                                                        $previewScopeClasses = match($preview['faculty_scope']) {
                                                            \App\Models\Faculty::SCOPE_GENED => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                                                            \App\Models\Faculty::SCOPE_CROSS_DEPARTMENT => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
                                                            default => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
                                                        };
                                                    @endphp
                                                    <span class="inline-flex px-2 py-1 {{ $previewScopeClasses }} rounded-md font-black text-[10px] uppercase tracking-tighter">{{ str_replace('_', '-', $preview['faculty_scope']) }}</span>
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    <span class="inline-flex px-2 py-1 {{ $preview['can_teach_minor'] ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }} rounded-md font-black text-[10px] uppercase">
                                                        {{ $preview['can_teach_minor'] ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 font-bold text-slate-700 dark:text-slate-300 text-center text-xs">{{ $preview['max_units'] }}</td>
                                                <td class="px-4 py-2.5 text-right">
                                                    @if(($preview['status'] ?? '') === 'invalid')
                                                        <span class="inline-flex px-2.5 py-1 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-md font-black text-[10px] uppercase" title="{{ implode(' ', $preview['errors'] ?? []) }}">Invalid</span>
                                                    @elseif(($preview['status'] ?? '') === 'duplicate')
                                                        <span class="inline-flex px-2.5 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-md font-black text-[10px] uppercase">Duplicate</span>
                                                    @else
                                                        <span class="inline-flex px-2.5 py-1 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-md font-black text-[10px] uppercase">OK</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <button wire:click="processImport"
                                wire:loading.attr="disabled"
                                class="w-full py-4 mt-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-2xl font-black uppercase text-sm shadow-xl hover:shadow-2xl transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                                <span wire:loading.remove>✓ Finalize Import ({{ count($importPreview) }} records)</span>
                                <span wire:loading wire:target="processImport">⏳ Inserting records into database...</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- MODAL: Bulk Delete Confirm --}}
        <div x-show="confirmDelete"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm"
            x-cloak
            x-transition
            @keydown.window.escape="confirmDelete = false">
            <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-3xl p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-800"
                @click.away="confirmDelete = false">
                <div class="w-20 h-20 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 animate-bounce border-4 border-red-100 dark:border-red-900/50">
                    ⚠️
                </div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Purge Records?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 mt-3 italic font-medium">
                    You are about to delete <span class="text-red-600 dark:text-red-400 font-black text-base">{{ count($selectedFaculty) }}</span> entries permanently.
                </p>
                <div class="flex flex-col space-y-3">
                    <button
                        wire:click="deleteSelected"
                        wire:loading.attr="disabled"
                        @keydown.window.enter="confirmDelete = false; $wire.deleteSelected()"
                        class="w-full py-3.5 bg-red-600 dark:bg-red-700 text-white rounded-2xl font-black uppercase text-sm tracking-widest shadow-lg shadow-red-500/30 hover:bg-red-700 dark:hover:bg-red-600 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                        <span wire:loading.remove>🗑️ Confirm Delete</span>
                        <span wire:loading wire:target="deleteSelected">⏳ Deleting...</span>
                    </button>
                    <button
                        @click="confirmDelete = false"
                        class="w-full py-3 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        ✕ Cancel (Esc)
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>
