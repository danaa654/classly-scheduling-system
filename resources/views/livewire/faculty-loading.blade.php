<div class="h-screen bg-slate-50 dark:bg-slate-950 flex overflow-hidden font-sans transition-colors duration-500">
    
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
                                <p class="text-[8px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-tight mt-0.5">
                                    {{ $faculty->teaching_specialization }}
                                </p>
                            @endif
                        </div>

                        <!-- Progress Ring -->
                        <div class="flex-shrink-0 w-14 h-14 relative">
                            @php
                                $units = $faculty->subjects_sum_units ?? 0;
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
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[9px] font-black uppercase tracking-widest">Active Profile</span>
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
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95">
                            ✏️ Edit Load
                        </button>
                        <button onclick="window.print()" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                            🖨️ Print
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
                                {{ $currentFaculty->subjects->sum('units') ?? 0 }}
                            </span>
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-bold">
                                / {{ $currentFaculty->max_units }} Units
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 bg-blue-200 dark:bg-blue-900/40 rounded-full overflow-hidden">
                            @php
                                $totalUnits = $currentFaculty->subjects->sum('units') ?? 0;
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
                @if(($currentFaculty->subjects->sum('units') ?? 0) > ($currentFaculty->max_units ?? 21))
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-xl flex items-start gap-3">
                        <span class="text-lg flex-shrink-0">⚠️</span>
                        <div>
                            <p class="text-sm font-black text-red-700 dark:text-red-400 uppercase tracking-tight">Load Status: Overload</p>
                            <p class="text-xs text-red-600 dark:text-red-500 font-medium mt-1">
                                The current load exceeds the maximum allowable units. 
                                <span class="font-black">{{ ($currentFaculty->subjects->sum('units') ?? 0) - ($currentFaculty->max_units ?? 21) }} units over capacity.</span>
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Alert: No Conflicts -->
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-xl flex items-start gap-3">
                        <span class="text-lg flex-shrink-0">✓</span>
                        <div>
                            <p class="text-sm font-black text-blue-700 dark:text-blue-400 uppercase tracking-tight">No Schedule Conflicts</p>
                            <p class="text-xs text-blue-600 dark:text-blue-500 font-medium mt-1">
                                There are no scheduling conflicts detected for this load.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Tabs & Table Section -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Tabs -->
                <div class="px-6 pt-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                    <div class="flex gap-6">
                        <button wire:click="$set('activeTab', 'subjects')" class="pb-3 px-1 text-sm font-black {{ $activeTab === 'subjects' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Subjects Assigned ({{ $currentFaculty->subjects->count() }})
                        </button>
                        <button wire:click="toggleScheduleModal" class="pb-3 px-1 text-sm font-black {{ $activeTab === 'schedule' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Schedule Overview
                        </button>
                        <button wire:click="toggleSummaryModal" class="pb-3 px-1 text-sm font-black {{ $activeTab === 'summary' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-600 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-900 dark:hover:text-white' }} uppercase tracking-widest transition-all">
                            Load Summary
                        </button>
                    </div>
                </div>

                <!-- Assignment Table / Schedule / Summary -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-4">
                        <!-- SUBJECTS TAB -->
                        @if($activeTab === 'subjects')
                            @if($currentFaculty->subjects->count() > 0)
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
                                                <th class="px-4 py-3 text-left font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Type</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Status</th>
                                                <th class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                            @foreach($currentFaculty->subjects as $idx => $subject)
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                                    <td class="px-4 py-3 text-slate-900 dark:text-white font-black">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-3 font-black text-blue-600 dark:text-blue-400 uppercase">{{ $subject->edp_code }}</td>
                                                    <td class="px-4 py-3 font-black text-blue-600 dark:text-blue-400 uppercase">{{ $subject->subject_code }}</td>
                                                    <td class="px-4 py-3 text-slate-900 dark:text-white font-bold truncate max-w-xs" title="{{ $subject->description }}">
                                                        {{ $subject->description }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-bold uppercase">{{ $subject->section }}</td>
                                                    <td class="px-4 py-3 text-center font-black text-slate-900 dark:text-white">{{ $subject->units }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[9px] font-black uppercase {{ $subject->type === 'Major' ? 'bg-blue-100 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400' : 'bg-purple-100 dark:bg-purple-950/30 text-purple-700 dark:text-purple-400' }}">
                                                            {{ $subject->type }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-400 rounded-full text-[9px] font-black uppercase">
                                                            Assigned
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button wire:click="removeSubject({{ $subject->id }})" 
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
                                        {{ $currentFaculty->subjects->sum('units') ?? 0 }}<span class="text-xs text-slate-500 dark:text-slate-400"> Units</span>
                                    </p>
                                </div>
                            @else
                                <div class="h-64 flex flex-col items-center justify-center opacity-40">
                                    <p class="text-3xl mb-3">📚</p>
                                    <p class="text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">No Subjects Assigned</p>
                                    <p class="text-[9px] text-slate-500 dark:text-slate-500 font-medium mt-2">Add subjects from the Subjects Catalog on the right</p>
                                </div>
                            @endif
                        @endif

                        <!-- SCHEDULE TAB -->
                        @if($activeTab === 'schedule' && $showScheduleModal)
                            <div class="space-y-4">
                                <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-xl">
                                    <p class="text-sm font-black text-blue-700 dark:text-blue-400 uppercase tracking-tight mb-3">Weekly Schedule Grid</p>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-[10px]">
                                            <thead class="bg-slate-100 dark:bg-slate-800/50">
                                                <tr>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Time</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Monday</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Tuesday</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Wednesday</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Thursday</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Friday</th>
                                                    <th class="px-2 py-2 font-black text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Saturday</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @for($time = 6; $time <= 18; $time++)
                                                    <tr>
                                                        <td class="px-2 py-2 font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                            {{ sprintf('%02d:00', $time) }}
                                                        </td>
                                                        @for($day = 1; $day <= 6; $day++)
                                                            <td class="px-2 py-2 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                                                                <!-- Schedule slots can be populated here -->
                                                            </td>
                                                        @endfor
                                                    </tr>
                                                @endfor
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <p class="text-[9px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest italic">
                                    📋 Note: Populate this with your Schedule model data
                                </p>
                            </div>
                        @endif

                        <!-- SUMMARY TAB -->
                        @if($activeTab === 'summary' && $showSummaryModal && $facultySummary)
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Total Units Card -->
                                    <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/20 dark:to-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                        <p class="text-[9px] font-black text-blue-700 dark:text-blue-400 uppercase tracking-widest mb-2">Total Units</p>
                                        <p class="text-3xl font-black text-blue-700 dark:text-blue-300">{{ $facultySummary['total_units'] }}</p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400 font-bold mt-2">
                                            Max: {{ $facultySummary['max_units'] }} | Remaining: {{ $facultySummary['remaining_units'] }}
                                        </p>
                                    </div>

                                    <!-- Utilization Card -->
                                    <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/20 dark:to-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                        <p class="text-[9px] font-black text-purple-700 dark:text-purple-400 uppercase tracking-widest mb-2">Utilization</p>
                                        <p class="text-3xl font-black text-purple-700 dark:text-purple-300">{{ round($facultySummary['utilization_percent']) }}%</p>
                                        <div class="mt-2 h-1.5 bg-purple-200 dark:bg-purple-900/40 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600" style="width: {{ $facultySummary['utilization_percent'] }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Major Subjects Card -->
                                    <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/20 dark:to-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                                        <p class="text-[9px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest mb-2">Major Subjects</p>
                                        <div class="flex items-baseline gap-2">
                                            <p class="text-3xl font-black text-green-700 dark:text-green-300">{{ $facultySummary['lecture_count'] }}</p>
                                            <p class="text-lg font-black text-green-600 dark:text-green-400">{{ $facultySummary['lecture_units'] }} Units</p>
                                        </div>
                                    </div>

                                    <!-- Minor Subjects Card -->
                                    <div class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/20 dark:to-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                                        <p class="text-[9px] font-black text-orange-700 dark:text-orange-400 uppercase tracking-widest mb-2">Minor Subjects</p>
                                        <div class="flex items-baseline gap-2">
                                            <p class="text-3xl font-black text-orange-700 dark:text-orange-300">{{ $facultySummary['lab_count'] }}</p>
                                            <p class="text-lg font-black text-orange-600 dark:text-orange-400">{{ $facultySummary['lab_units'] }} Units</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Breakdown Table -->
                                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <p class="text-[9px] font-black text-slate-700 dark:text-slate-400 uppercase tracking-widest mb-3">Subject Breakdown</p>
                                    <table class="w-full text-[11px]">
                                        <thead class="bg-slate-100 dark:bg-slate-800/50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-black text-slate-700 dark:text-slate-300">Type</th>
                                                <th class="px-3 py-2 text-center font-black text-slate-700 dark:text-slate-300">Count</th>
                                                <th class="px-3 py-2 text-center font-black text-slate-700 dark:text-slate-300">Total Units</th>
                                                <th class="px-3 py-2 text-center font-black text-slate-700 dark:text-slate-300">Avg Units</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                            <tr>
                                                <td class="px-3 py-2 font-black text-green-700 dark:text-green-400 uppercase">Major</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">{{ $facultySummary['lecture_count'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">{{ $facultySummary['lecture_units'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">
                                                    {{ $facultySummary['lecture_count'] > 0 ? round($facultySummary['lecture_units'] / $facultySummary['lecture_count'], 1) : 0 }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 font-black text-orange-700 dark:text-orange-400 uppercase">Minor</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">{{ $facultySummary['lab_count'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">{{ $facultySummary['lab_units'] }}</td>
                                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-white">
                                                    {{ $facultySummary['lab_count'] > 0 ? round($facultySummary['lab_units'] / $facultySummary['lab_count'], 1) : 0 }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Action Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0 flex gap-3">
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95 flex items-center gap-2">
                    <span>ℹ️</span> Info
                </button>
                <button class="ml-auto px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all">
                    🔄 Refresh
                </button>
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
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Subjects Catalog</h2>
                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[8px] font-black uppercase">
                    {{ count($availableSubjects) }}
                </span>
            </div>

            <!-- Subject Search -->
            <div class="relative mb-3">
                <input type="text" 
                    wire:model.live="subjectSearch" 
                    placeholder="Search (code/desc/edp)..." 
                    class="w-full pl-8 pr-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-400">
                <span class="absolute left-2.5 top-2.5 text-slate-400">🔍</span>
            </div>

            <!-- Filters (Only show for admin/registrar when no faculty selected) -->
            @if(($userRole === 'admin' || $userRole === 'registrar') && !$selectedFacultyId)
                <div class="flex gap-2">
                    <select wire:model.live="subjectDepartmentFilter" class="flex-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                        <option value="all">All Dept</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="subjectTypeFilter" class="flex-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-bold border border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none transition-all">
                        <option value="all">All Type</option>
                        @foreach($subjectTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- Subject List (Scrollable - No Limit) -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar">
            @if($currentFaculty)
                @forelse($availableSubjects as $subject)
                    <div class="p-4 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-600 transition-all group">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">
                                    EDP: {{ $subject->edp_code }}
                                </p>
                                <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mt-0.5">
                                    {{ $subject->subject_code }}
                                </p>
                                <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight mt-1 line-clamp-2">
                                    {{ $subject->description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 mb-3 text-[8px] flex-wrap">
                            <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-bold uppercase">
                                {{ $subject->section }}
                            </span>
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 rounded font-bold uppercase">
                                {{ $subject->units }}u
                            </span>
                            <span class="px-2 py-0.5 {{ $subject->type === 'Major' ? 'bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400' : 'bg-purple-100 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400' }} rounded font-bold uppercase">
                                {{ $subject->type }}
                            </span>
                        </div>

                        <button wire:click="assignSubject({{ $subject->id }})" 
                            class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-black uppercase tracking-widest transition-all transform active:scale-95">
                            + Assign
                        </button>
                    </div>
                @empty
                    <div class="h-48 flex flex-col items-center justify-center opacity-40 text-center p-4">
                        <p class="text-3xl mb-2">🔍</p>
                        <p class="text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">
                            @if(strlen($subjectSearch) > 1)
                                No subjects match
                            @else
                                Search to view subjects
                            @endif
                        </p>
                    </div>
                @endforelse
            @else
                <div class="h-48 flex flex-col items-center justify-center opacity-40 text-center p-4">
                    <p class="text-3xl mb-2">📋</p>
                    <p class="text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">Select Faculty First</p>
                    <p class="text-[9px] text-slate-500 dark:text-slate-500 font-medium mt-2">Choose a faculty member to view available subjects</p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 shrink-0 text-center">
            <p class="text-[9px] text-slate-500 dark:text-slate-400 font-bold uppercase">
                @if($userRole === 'associate_dean')
                    🎓 Minor Subjects Only
                @elseif($userRole === 'dean' || $userRole === 'oic')
                    🎓 Major Subjects Only
                @else
                    🎓 All Subject Types
                @endif
            </p>
        </div>
    </aside>
</div>

<!-- Session Alerts -->
@if(session('success'))
    <div class="fixed top-4 right-4 px-4 py-3 bg-green-100 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-lg text-sm font-bold uppercase tracking-widest animate-pulse z-50">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="fixed top-4 right-4 px-4 py-3 bg-red-100 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-lg text-sm font-bold uppercase tracking-widest animate-pulse z-50">
        {{ session('error') }}
    </div>
@endif

@if(session('warning'))
    <div class="fixed top-4 right-4 px-4 py-3 bg-orange-100 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400 rounded-lg text-sm font-bold uppercase tracking-widest animate-pulse z-50">
        {{ session('warning') }}
    </div>
@endif

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
        body > *:not(.flex-1) { display: none; }
        main { height: auto !important; overflow: visible !important; }
        .custom-scrollbar { overflow: visible !important; }
    }
</style>
