<div
    x-data="{
        toasts: [],
        addToast(toast) {
            const id = Date.now() + Math.random();
            const item = {
                id,
                type: toast.type || 'success',
                message: toast.message || '',
                timeout: null
            };

            this.toasts.push(item);
            item.timeout = setTimeout(() => this.removeToast(id), 4200);
        },
        removeToast(id) {
            const toast = this.toasts.find((item) => item.id === id);
            if (toast && toast.timeout) {
                clearTimeout(toast.timeout);
            }

            this.toasts = this.toasts.filter((item) => item.id !== id);
        },
        toastClasses(type) {
            return {
                success: 'bg-green-600 border-green-500 text-white',
                warning: 'bg-amber-500 border-amber-400 text-white',
                error: 'bg-red-600 border-red-500 text-white'
            }[type] || 'bg-slate-900 border-slate-700 text-white';
        },
        toastIcon(type) {
            return {
                success: 'OK',
                warning: '!',
                error: '!'
            }[type] || 'i';
        }
    }"
    x-on:toast.window="addToast($event.detail)"
    class="h-screen bg-slate-50 dark:bg-slate-950 flex overflow-hidden font-sans transition-colors duration-500">
    
    <!-- LEFT PANEL: FACULTY ROSTER -->
    <aside class="w-80 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col shrink-0 overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white mb-3">Faculty Roster</h2>
            
            <!-- Search -->
            <div class="relative mb-3">
                <input type="text" 
                    wire:model.live="search" 
                    placeholder="Search by name or ID..." 
                    class="w-full pl-8 pr-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-400">
                <span class="absolute left-2.5 top-2.5 text-slate-400">🔍</span>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 mb-3">
                @if(count($departments) > 1)
                    <select wire:model.live="departmentFilter" class="flex-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                        <option value="all">All Dept</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                @endif
                <select wire:model.live="statusFilter" class="flex-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                    <option value="all">All Status</option>
                    @foreach($employmentTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stats -->
            <p class="text-[9px] text-slate-500 dark:text-slate-400 font-bold uppercase">
                {{ count($faculties) }} Faculty Members
            </p>
        </div>

        <!-- Faculty List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar">
            @forelse($faculties as $faculty)
                <button wire:click="selectFaculty({{ $faculty->id }})" 
                    class="w-full p-3 rounded-xl transition-all border-2 {{ $selectedFacultyId == $faculty->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-800/50' }}">
                    
                    <div class="flex items-center gap-3">
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0">
                            {{ substr($faculty->full_name, 0, 1) }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-black text-slate-900 dark:text-white truncate uppercase tracking-tight">
                                {{ $faculty->full_name }}
                            </p>
                            <p class="text-[9px] text-slate-500 dark:text-slate-400 font-medium truncate">
                                ID: {{ $faculty->employee_id }} • {{ $faculty->department }}
                            </p>
                            @if($faculty->teaching_specialization)
                                <div class="flex items-center gap-1 mt-0.5">
                                    @if($faculty->teaching_specialization === 'Both')
                                        <span class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-950/30 text-purple-700 dark:text-purple-400 rounded text-[8px] font-black uppercase">✓ Both</span>
                                    @elseif($faculty->teaching_specialization === 'Major')
                                        <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 rounded text-[8px] font-black uppercase">★ Major</span>
                                    @else
                                        <span class="px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 rounded text-[8px] font-black uppercase">ℹ Minor</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Progress Ring -->
                        <div class="flex-shrink-0 w-14 h-14 relative">
                            @php
                                $units = $faculty->assigned_units ?? 0;
                                $max = $faculty->max_units ?? 21;
                                $percent = min(($units / $max) * 100, 100);
                                $circumference = 2 * 3.14159 * 20;
                                $strokeDashOffset = $circumference - ($percent / 100) * $circumference;
                                
                                if ($percent >= 95) {
                                    $ringColor = '#ef4444'; // Red - Overloaded
                                } elseif ($percent >= 80) {
                                    $ringColor = '#f59e0b'; // Orange - Near Cap
                                } else {
                                    $ringColor = '#3b82f6'; // Blue - Normal
                                }
                            @endphp
                            
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 44 44">
                                <circle cx="22" cy="22" r="20" stroke="currentColor" stroke-width="2" fill="none" class="text-slate-200 dark:text-slate-700"></circle>
                                <circle 
                                    cx="22" 
                                    cy="22" 
                                    r="20" 
                                    stroke="{{ $ringColor }}" 
                                    stroke-width="2" 
                                    fill="none"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $strokeDashOffset }}"
                                    stroke-linecap="round"
                                    class="transition-all duration-500">
                                </circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <p class="text-[10px] font-black text-slate-900 dark:text-white">{{ $units }}</p>
                                    <p class="text-[7px] text-slate-500 dark:text-slate-400 font-bold">/ {{ $max }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="h-48 flex items-center justify-center">
                    <p class="text-[10px] text-slate-400 font-bold uppercase text-center">No faculty found</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination Footer -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[9px] text-slate-500 dark:text-slate-400 font-bold">
            <button class="p-1 hover:text-slate-900 dark:hover:text-white transition-colors">← Prev</button>
            <span>1 of {{ ceil(count($faculties) / 10) }}</span>
            <button class="p-1 hover:text-slate-900 dark:hover:text-white transition-colors">Next →</button>
        </div>
    </aside>

    <!-- CENTER PANEL: ACTIVE WORKSPACE -->
    <main class="flex-1 flex flex-col bg-slate-50 dark:bg-slate-950 overflow-hidden">
        @if($currentFaculty)
            <!-- Header -->
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900 overflow-y-auto custom-scrollbar max-h-[45%]">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[9px] font-black uppercase tracking-widest">Active Profile</span>
                            @if($currentFaculty->teaching_specialization === 'Both')
                                <span class="px-2.5 py-1 bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 rounded-full text-[9px] font-black uppercase tracking-widest">✓ Both</span>
                            @elseif($currentFaculty->teaching_specialization === 'Major')
                                <span class="px-2.5 py-1 bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 rounded-full text-[9px] font-black uppercase tracking-widest">★ Major</span>
                            @else
                                <span class="px-2.5 py-1 bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 rounded-full text-[9px] font-black uppercase tracking-widest">ℹ Minor</span>
                            @endif
                        </div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            {{ $currentFaculty->full_name }}
                        </h1>
                        <p class="text-xs text-slate-600 dark:text-slate-400 font-bold mt-1">
                            ID: {{ $currentFaculty->employee_id }} • {{ $currentFaculty->department }} Department
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button wire:click="submitFacultyLoading" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95">
                            Submit Faculty Loading
                        </button>
                        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95">
                            🖨️ Print
                        </button>
                        <button class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                            ⋮ More
                        </button>
                    </div>
                </div>

                <!-- Load Status Cards -->
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <!-- Overall Load -->
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/20 dark:to-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-[9px] font-black text-blue-700 dark:text-blue-400 uppercase tracking-widest mb-2">Overall Load</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black text-blue-700 dark:text-blue-300">
                                {{ $assignedSubjects->sum('units') ?? 0 }}
                            </span>
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-bold">
                                / {{ $currentFaculty->max_units }} Units
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 bg-blue-200 dark:bg-blue-900/40 rounded-full overflow-hidden">
                            @php
                                $totalUnits = $assignedSubjects->sum('units') ?? 0;
                                $maxUnits = $currentFaculty->max_units ?? 21;
                                $loadPercent = min(($totalUnits / $maxUnits) * 100, 100);
                            @endphp
                            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-700" style="width: {{ $loadPercent }}%"></div>
                        </div>
                        <p class="text-[9px] text-blue-600 dark:text-blue-400 font-bold mt-1">
                            {{ round($loadPercent) }}% of Standard Load
                        </p>
                    </div>

                    <!-- Status Info -->
                    <div class="p-4 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <p class="text-[9px] font-black text-slate-700 dark:text-slate-400 uppercase tracking-widest mb-2">Status</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-400 rounded-full text-[9px] font-black uppercase">
                                {{ $currentFaculty->employment_type ?? 'Full-Time' }}
                            </span>
                            @if($currentFaculty->teaching_specialization)
                                <span class="px-2 py-1 bg-purple-100 dark:bg-purple-950/30 text-purple-700 dark:text-purple-400 rounded-full text-[9px] font-black uppercase">
                                    {{ $currentFaculty->teaching_specialization }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Alert: Overload Warning -->
                @if(($assignedSubjects->sum('units') ?? 0) > ($currentFaculty->max_units ?? 21))
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-xl flex items-start gap-3">
                        <span class="text-lg flex-shrink-0">⚠️</span>
                        <div>
                            <p class="text-sm font-black text-red-700 dark:text-red-400 uppercase tracking-tight">Load Status: Overload</p>
                            <p class="text-xs text-red-600 dark:text-red-500 font-medium mt-1">
                                The current load exceeds the maximum allowable units. 
                                <span class="font-black">{{ ($assignedSubjects->sum('units') ?? 0) - ($currentFaculty->max_units ?? 21) }} units over capacity.</span>
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Alert: No Conflicts -->
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-xl flex items-start gap-3">
                        <span class="text-lg flex-shrink-0">✓</span>
                        <div>
                            <p class="text-sm font-black text-blue-700 dark:text-blue-400 uppercase tracking-tight">Load Status: Within Capacity</p>
                            <p class="text-xs text-blue-600 dark:text-blue-500 font-medium mt-1">
                                Faculty can take {{ ($currentFaculty->max_units ?? 21) - ($assignedSubjects->sum('units') ?? 0) }} more units.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Tabs & Content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Tabs -->
                <div class="px-6 pt-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                    <div class="flex gap-6">
                        <button wire:click="toggleTab('subjects')"
                            class="pb-3 px-1 text-sm font-black {{ $activeTab === 'subjects' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Subjects Assigned ({{ $assignedSchedules->count() }})
                        </button>
                        <button wire:click="toggleTab('schedule')"
                            class="pb-3 px-1 text-sm font-black {{ $activeTab === 'schedule' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Schedule Overview
                        </button>
                        <button wire:click="toggleTab('summary')"
                            class="pb-3 px-1 text-sm font-black {{ $activeTab === 'summary' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Load Summary
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <!-- Subjects Tab -->
                    @if($activeTab === 'subjects')
                        <div class="px-6 py-4">
                            @if($assignedSchedules->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-[11px]">
                                        <thead class="bg-slate-100 dark:bg-slate-800/50 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">#</th>
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">EDP Code</th>
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Subject Code</th>
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Description</th>
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Section</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Units</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Type</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Status</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                            @foreach($assignedSchedules as $idx => $schedule)
                                                @php
                                                    $subject = $schedule->subject;
                                                    $room = $schedule->room;
                                                @endphp
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                                    <td class="px-4 py-3 text-slate-900 dark:text-white font-black">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-3 font-black text-orange-600 dark:text-orange-400 uppercase">{{ $subject->edp_code }}</td>
                                                    <td class="px-4 py-3 font-black text-blue-600 dark:text-blue-400 uppercase">{{ $subject->subject_code }}</td>
                                                    <td class="px-4 py-3 text-slate-900 dark:text-white font-bold truncate max-w-xs" title="{{ $subject->description }}">
                                                        {{ $subject->description }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-bold uppercase">
                                                        {{ $schedule->section }}
                                                        <span class="block text-[9px] font-bold text-slate-400">
                                                            {{ $room?->room_name ?? 'No room' }} · {{ $schedule->day }} {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}-{{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-black text-slate-900 dark:text-white">{{ $subject->units }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[9px] font-black uppercase {{ $subject->type === 'Major' ? 'bg-blue-100 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400' : 'bg-purple-100 dark:bg-purple-950/30 text-purple-700 dark:text-purple-400' }}">
                                                            {{ $subject->type }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-400 rounded-full text-[9px] font-black uppercase">
                                                            {{ str_replace('_', ' ', $schedule->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button wire:click="removeSubject({{ $schedule->id }})" 
                                                            class="text-slate-400 hover:text-red-600 dark:hover:text-red-400 font-bold transition-colors p-1 text-lg leading-none">
                                                            ✕
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Footer Stats -->
                                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <p class="text-[10px] text-slate-600 dark:text-slate-400 font-bold uppercase">
                                        Total Units Assigned
                                    </p>
                                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400">
                                        {{ $assignedSubjects->sum('units') ?? 0 }}<span class="text-xs text-slate-500 dark:text-slate-400"> / {{ $currentFaculty->max_units }}</span>
                                    </p>
                                </div>
                            @else
                                <div class="h-48 flex flex-col items-center justify-center opacity-40">
                                    <p class="text-3xl mb-3">📚</p>
                                    <p class="text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">No Subjects Assigned</p>
                                    <p class="text-[9px] text-slate-500 dark:text-slate-500 font-medium mt-2">Add subjects from the Subjects Catalog on the right</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Schedule Tab -->
                    @if($activeTab === 'schedule')
                        <div class="px-6 py-4">
                            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-x-auto">
                                <table class="w-full text-[9px]">
                                    <thead class="bg-slate-100 dark:bg-slate-800">
                                        <tr>
                                            <th class="px-3 py-3 text-left font-black uppercase text-slate-600 dark:text-slate-400 min-w-12">Time</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Monday</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Tuesday</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Wednesday</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Thursday</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Friday</th>
                                            <th class="px-3 py-3 text-center font-black uppercase text-slate-600 dark:text-slate-400 min-w-20">Saturday</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        @for($hour = 6; $hour < 18; $hour++)
                                            <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors">
                                                <td class="px-3 py-2 font-black text-slate-600 dark:text-slate-400">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</td>
                                                @for($day = 1; $day <= 6; $day++)
                                                    <td class="px-3 py-2 text-center bg-slate-50 dark:bg-slate-800/30 border-r border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition-colors">
                                                        <!-- Schedule slot -->
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[9px] text-slate-400 mt-4 italic">Schedule grid - ready to populate with Schedule model data</p>
                        </div>
                    @endif

                    <!-- Summary Tab -->
                    @if($activeTab === 'summary')
                        <div class="px-6 py-4">
                            @if($facultySummary)
                                <!-- Stats Cards -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <!-- Total Units Card -->
                                    <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/20 dark:to-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                        <p class="text-[9px] font-black text-blue-700 dark:text-blue-400 uppercase tracking-widest mb-2">📊 Total Units</p>
                                        <p class="text-3xl font-black text-blue-700 dark:text-blue-300">{{ $facultySummary['totalUnits'] }}</p>
                                        <p class="text-[9px] text-blue-600 dark:text-blue-400 font-bold mt-1">Max: {{ $facultySummary['maxUnits'] }} units</p>
                                    </div>

                                    <!-- Utilization Card -->
                                    <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/20 dark:to-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                        <p class="text-[9px] font-black text-purple-700 dark:text-purple-400 uppercase tracking-widest mb-2">📈 Utilization</p>
                                        <p class="text-3xl font-black text-purple-700 dark:text-purple-300">{{ $facultySummary['utilizationPercent'] }}%</p>
                                        <p class="text-[9px] text-purple-600 dark:text-purple-400 font-bold mt-1">Remaining: {{ $facultySummary['remainingUnits'] }} units</p>
                                    </div>

                                    <!-- Major Card -->
                                    <div class="p-4 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/20 dark:to-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                                        <p class="text-[9px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-2">★ Major Subjects</p>
                                        <p class="text-3xl font-black text-amber-700 dark:text-amber-300">{{ $facultySummary['majorCount'] }}</p>
                                        <p class="text-[9px] text-amber-600 dark:text-amber-400 font-bold mt-1">{{ $facultySummary['majorUnits'] }} units</p>
                                    </div>

                                    <!-- Minor Card -->
                                    <div class="p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950/20 dark:to-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                                        <p class="text-[9px] font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-widest mb-2">ℹ Minor Subjects</p>
                                        <p class="text-3xl font-black text-indigo-700 dark:text-indigo-300">{{ $facultySummary['minorCount'] }}</p>
                                        <p class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold mt-1">{{ $facultySummary['minorUnits'] }} units</p>
                                    </div>
                                </div>

                                <!-- Breakdown Table -->
                                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase mb-4">📋 Breakdown</p>
                                    <table class="w-full text-[10px]">
                                        <thead class="bg-slate-100 dark:bg-slate-800">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-black uppercase text-slate-600 dark:text-slate-400">Type</th>
                                                <th class="px-3 py-2 text-center font-black uppercase text-slate-600 dark:text-slate-400">Count</th>
                                                <th class="px-3 py-2 text-center font-black uppercase text-slate-600 dark:text-slate-400">Total Units</th>
                                                <th class="px-3 py-2 text-center font-black uppercase text-slate-600 dark:text-slate-400">Average</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                            <tr class="hover:bg-amber-50 dark:hover:bg-amber-900/10">
                                                <td class="px-3 py-2 font-black text-amber-700 dark:text-amber-400">Major</td>
                                                <td class="px-3 py-2 text-center font-black text-slate-900 dark:text-white">{{ $facultySummary['majorCount'] }}</td>
                                                <td class="px-3 py-2 text-center font-black text-amber-700 dark:text-amber-400">{{ $facultySummary['majorUnits'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-600 dark:text-slate-400">{{ $facultySummary['averageMajorUnits'] }}u</td>
                                            </tr>
                                            <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10">
                                                <td class="px-3 py-2 font-black text-indigo-700 dark:text-indigo-400">Minor</td>
                                                <td class="px-3 py-2 text-center font-black text-slate-900 dark:text-white">{{ $facultySummary['minorCount'] }}</td>
                                                <td class="px-3 py-2 text-center font-black text-indigo-700 dark:text-indigo-400">{{ $facultySummary['minorUnits'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-600 dark:text-slate-400">{{ $facultySummary['averageMinorUnits'] }}u</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="flex-1 flex flex-col items-center justify-center">
                <div class="w-40 h-40 bg-slate-200 dark:bg-slate-800 rounded-3xl mb-6 flex items-center justify-center text-6xl opacity-30">
                    👤
                </div>
                <h2 class="text-2xl font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest">No Faculty Selected</h2>
                <p class="text-xs text-slate-500 dark:text-slate-500 font-medium mt-2">Select a faculty member from the roster to begin</p>
            </div>
        @endif
    </main>

    <!-- RIGHT PANEL: SUBJECTS CATALOG -->
    <aside class="w-80 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col shrink-0 overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 shrink-0 overflow-y-auto custom-scrollbar max-h-[40%]">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Subjects Catalog</h2>
                @if($currentFaculty)
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[8px] font-black uppercase">
                        {{ count($availableSubjects) }} Available
                    </span>
                @endif
            </div>

            <!-- Subject Search -->
            <div class="relative mb-3">
                <input type="text" 
                    wire:model.live="subjectSearch" 
                    placeholder="Search subject, EDP..." 
                    class="w-full pl-8 pr-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-400">
                <span class="absolute left-2.5 top-2.5 text-slate-400">🔍</span>
            </div>

            <!-- Advanced Filters Grid -->
            @if($currentFaculty)
                <!-- Cross-Department Info -->
                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-900/30 mb-3">
                    <p class="text-[8px] font-black text-blue-700 dark:text-blue-400 uppercase mb-1">📍 Cross-Dept Assignment</p>
                    <p class="text-[9px] text-blue-600 dark:text-blue-300 font-medium">
                        @if($currentFaculty->teaching_specialization === 'Minor')
                            ℹ Minor subjects from ANY department
                        @elseif($currentFaculty->teaching_specialization === 'Major')
                            ★ Major subjects from {{ $currentFaculty->department }} only
                        @else
                            ✓ Minor (any) + Major ({{ $currentFaculty->department }})
                        @endif
                    </p>
                </div>

                <!-- Compact Advanced Filters -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[7px] font-black uppercase text-slate-600 dark:text-slate-400 block mb-0.5">Year Level</label>
                        <select wire:model.live="subjectYearLevelFilter" class="w-full px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-[9px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                            <option value="all">All</option>
                            @foreach($yearLevels as $level)
                                <option value="{{ $level }}">{{ $level }}{{ $level == 1 ? 'st' : ($level == 2 ? 'nd' : ($level == 3 ? 'rd' : 'th')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[7px] font-black uppercase text-slate-600 dark:text-slate-400 block mb-0.5">Section</label>
                        <select wire:model.live="subjectSectionFilter" class="w-full px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-[9px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                            <option value="all">All</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}">{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @else

            @endif
        </div>

        <!-- Subject List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar">
            @if($currentFaculty)
                @forelse($availableSubjects as $schedule)
                    @php
                        $subject = $schedule->subject;
                        $room = $schedule->room;
                    @endphp
                    <div class="p-3 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-600 transition-all group">
                        <div class="mb-2">
                            <p class="text-[8px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest">EDP: {{ $subject->edp_code }}</p>
                            <p class="text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mt-0.5">{{ $subject->subject_code }}</p>
                            <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight mt-1 line-clamp-2">{{ $subject->description }}</p>
                        </div>

                        <div class="flex items-center gap-1 mb-2 text-[7px] flex-wrap">
                            <span class="px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded font-bold uppercase">{{ $subject->department }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold uppercase">{{ $subject->section }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold uppercase">{{ $room?->room_name ?? 'No Room' }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold uppercase">{{ $schedule->day }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold uppercase">{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}-{{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</span>
                            <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 rounded font-bold uppercase">{{ $subject->units }}u</span>
                            <span class="px-1.5 py-0.5 {{ $subject->type === 'Major' ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400' : 'bg-purple-100 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400' }} rounded font-bold uppercase">{{ $subject->type }}</span>
                        </div>

                        <div class="mb-2 text-[8px] font-bold text-slate-500 dark:text-slate-400">
                            Faculty Status: <span class="uppercase">{{ $schedule->faculty_id ? 'Assigned' : 'Unassigned' }}</span>
                        </div>

                        <button wire:click="assignSubject({{ $schedule->id }})" 
                            class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-black rounded-lg uppercase transition-all shadow-md active:scale-95">
                            Assign to Faculty +
                        </button>   
                    </div>
                @empty
                    <div class="h-48 flex flex-col items-center justify-center opacity-40 text-center p-4">
                        <p class="text-3xl mb-2">🔍</p>
                        <p class="text-[9px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">
                            @if(strlen($subjectSearch) > 1)
                                No matching subjects
                            @else
                                Search to view available subjects
                            @endif
                        </p>
                    </div>
                @endforelse
            @else
                <div class="h-48 flex flex-col items-center justify-center opacity-40 text-center p-4">
                    <p class="text-3xl mb-2">📋</p>
                    <p class="text-[9px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">Select Faculty First</p>
                    <p class="text-[8px] text-slate-500 dark:text-slate-500 font-medium mt-2">Choose a faculty member from the left panel</p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 shrink-0 text-center text-[8px] text-slate-500 dark:text-slate-400 font-bold uppercase">
            @if($userRole === 'associate_dean')
                Minor Subjects Only
            @elseif($userRole === 'dean' || $userRole === 'oic')
                Major Subjects (Your Dept)
            @else
                All Subject Types
            @endif
        </div>
    </aside>

    <!-- Faculty Loading Toast Notifications -->
    <div class="fixed top-5 right-5 z-[9999] w-[min(24rem,calc(100vw-2rem))] space-y-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-6 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-6 scale-95"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-2xl"
                :class="toastClasses(toast.type)">
                <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20 text-sm font-black">
                    <span x-text="toastIcon(toast.type)"></span>
                </div>
                <p class="min-w-0 flex-1 text-sm font-bold leading-5 tracking-wide" x-text="toast.message"></p>
                <button
                    type="button"
                    x-on:click="removeToast(toast.id)"
                    class="rounded-md px-1.5 text-lg font-black leading-none text-white/80 transition hover:bg-white/15 hover:text-white"
                    aria-label="Dismiss notification">
                    &times;
                </button>
            </div>
        </template>
    </div>
</div>

<!-- Scrollbar Styling -->
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        @apply bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 dark:hover:bg-slate-600 transition-colors rounded-full;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    @media print {
        body > * { display: none !important; }
        main { height: auto !important; overflow: visible !important; border: none; }
    }
</style>
