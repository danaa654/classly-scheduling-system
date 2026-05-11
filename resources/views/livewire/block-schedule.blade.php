
<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500 py-8 px-6">
    <div class="max-w-7xl mx-auto">

        {{-- Main Container --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden transition-colors duration-500">

            {{-- Header Section (Printable) --}}
            <div class="border-b-4 border-blue-600/20 px-12 py-10 bg-gradient-to-r from-blue-50/50 to-indigo-50/30 dark:from-blue-900/10 dark:to-indigo-900/5">
                <div class="text-center space-y-3">
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800 dark:text-slate-100">
                        📚 Study Load
                    </h1>
                    <h2 class="text-2xl font-black text-blue-600 dark:text-blue-400 uppercase tracking-tight">
                        Bachelor of Science in {{ $departmentName }}
                    </h2>
                    <div class="flex justify-center gap-6 text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">
                        <span class="bg-white/50 dark:bg-slate-800/50 px-4 py-2 rounded-lg">📅 S.Y. {{ $schoolYear }}</span>
                        <span class="bg-white/50 dark:bg-slate-800/50 px-4 py-2 rounded-lg">🎓 {{ $semesterName }}</span>
                    </div>
                </div>
            </div>

            {{-- Filter Bar (Hidden when printing) --}}
            <div class="print:hidden border-b-2 border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md px-12 py-8">
                <div class="flex flex-wrap gap-6 items-end">
                    {{-- Department Filter --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Department/Major</label>
                        <select wire:model.live="selectedDepartment" 
                                class="block px-4 py-3 bg-white dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                            <option value="IT">IT - Information Technology</option>
                            <option value="ACT">ACT - Associate in Computer Technology</option>
                            <option value="ED">ED - Education</option>
                            <option value="HM">HM - Hospitality Management</option>
                            <option value="TM">TM - Tourism Management</option>
                            <option value="FB">FB - Forensic Biology</option>
                            <option value="LD">LD - Lie Detection</option>
                            <option value="QD">QD - Questioned Document</option>
                        </select>
                    </div>

                    {{-- Year Level Filter --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Year Level</label>
                        <select wire:model.live="selectedYear" 
                                class="block px-4 py-3 bg-white dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    {{-- Section Filter --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Section</label>
                        <select wire:model.live="selectedSection" 
                                class="block px-4 py-3 bg-white dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>

                    {{-- Print Button --}}
                    <button onclick="window.print()" 
                            class="ml-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-sm font-black uppercase tracking-wider shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95 flex items-center gap-2">
                        <span>🖨️</span>
                        <span>Print Official Load</span>
                    </button>
                </div>
            </div>

            {{-- THE UNIFIED SCHEDULE TABLE --}}
            <div class="overflow-hidden">
                @php
                    // Flatten all schedules into a single array, sorted by day and time
                    $flattenedSchedules = [];
                    foreach($schedules as $day => $daySchedules) {
                        foreach($daySchedules as $sched) {
                            $flattenedSchedules[] = $sched;
                        }
                    }
                    
                    // Sort by day order and then by start time
                    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    usort($flattenedSchedules, function($a, $b) use ($dayOrder) {
                        $dayA = array_search($a->day, $dayOrder);
                        $dayB = array_search($b->day, $dayOrder);
                        
                        if ($dayA !== $dayB) {
                            return $dayA - $dayB;
                        }
                        
                        $timeA = strtotime($a->start_time);
                        $timeB = strtotime($b->start_time);
                        return $timeA - $timeB;
                    });
                @endphp

                @forelse($flattenedSchedules as $sched)
                    @if($loop->first)
                        {{-- Table Header --}}
                        <div class="overflow-x-auto rounded-none border-none shadow-none">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-900 dark:bg-slate-800 text-white text-[11px] uppercase font-black tracking-widest">
                                        <th class="px-6 py-4 text-center border-r border-slate-700 dark:border-slate-600 w-24">Time</th>
                                        <th class="px-6 py-4 text-center border-r border-slate-700 dark:border-slate-600 w-20">EDP Code</th>
                                        <th class="px-6 py-4 text-left border-r border-slate-700 dark:border-slate-600 w-32">Subject Code</th>
                                        <th class="px-6 py-4 text-left border-r border-slate-700 dark:border-slate-600 flex-1">Description</th>
                                        <th class="px-6 py-4 text-center border-r border-slate-700 dark:border-slate-600 w-16">Units</th>
                                        <th class="px-6 py-4 text-center border-r border-slate-700 dark:border-slate-600 w-20">Day</th>
                                        <th class="px-6 py-4 text-center border-r border-slate-700 dark:border-slate-600 w-24">Room</th>
                                        <th class="px-6 py-4 text-center w-36">Faculty</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                    @endif

                    {{-- Table Row --}}
                    <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                        {{-- Time --}}
                        <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap text-center">
                            <span class="bg-blue-100/50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-3 py-1.5 rounded-lg text-xs font-black tracking-tight inline-block">
                                {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} -
                                {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                            </span>
                        </td>

                        {{-- EDP Code --}}
                        <td class="px-6 py-4 font-mono text-slate-500 dark:text-slate-400 font-bold text-center">
                            {{ $sched->subject->edp_code }}
                        </td>

                        {{-- Subject Code --}}
                        <td class="px-6 py-4 font-black text-slate-800 dark:text-slate-100 uppercase tracking-tight">
                            {{ $sched->subject->subject_code }}
                        </td>

                        {{-- Description --}}
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                            {{ $sched->subject->description }}
                        </td>

                        {{-- Units --}}
                        <td class="px-6 py-4 text-center font-bold text-slate-700 dark:text-slate-300">
                            <span class="bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg text-xs font-black inline-block">
                                {{ $sched->subject->units ?? 0 }} U
                            </span>
                        </td>

                        {{-- Day --}}
                        <td class="px-6 py-4 text-center font-black text-slate-700 dark:text-slate-300">
                            <span class="bg-slate-100/50 dark:bg-slate-800/50 px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-tight inline-block">
                                {{ \Carbon\Carbon::parse('2000-01-01 00:00:00')->addDay(array_search($sched->day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']))->format('l') }}
                            </span>
                        </td>

                        {{-- Room --}}
                        <td class="px-6 py-4 text-center font-black text-blue-600 dark:text-blue-400">
                            <span class="bg-blue-100/50 dark:bg-blue-900/30 px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-tight inline-block">
                                {{ $sched->room->room_name }}
                            </span>
                        </td>

                        {{-- Faculty --}}
                        <td class="px-6 py-4 text-center font-black text-slate-700 dark:text-slate-300">
                            <span class="bg-slate-100/50 dark:bg-slate-800/50 px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-tight inline-block">
                                {{ $sched->faculty?->full_name ?? 'Unassigned' }}
                            </span>
                        </td>
                    </tr>

                    @if($loop->last)
                                </tbody>
                            </table>
                        </div>
                    @endif
                @empty
                    {{-- Empty State --}}
                    <div class="px-12 py-20 text-center">
                        <div class="inline-flex flex-col items-center space-y-4">
                            <div class="text-6xl opacity-40 animate-pulse">📚</div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter mb-2">
                                    No Blocks Found
                                </h3>
                                <p class="text-slate-500 dark:text-slate-400 text-sm italic font-medium">
                                    No subjects have been scheduled for {{ $departmentName }} Year {{ $selectedYear }} Section {{ $selectedSection }} yet.
                                </p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Print Stylesheet --}}
<style>
    @media print {
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background: white;
            color: black;
            font-family: Arial, sans-serif;
        }

        .print\:hidden {
            display: none !important;
        }

        .min-h-screen {
            background: white !important;
        }

        .bg-\[\#E6E6E6\],
        .dark\:bg-\[\#020617\] {
            background: white !important;
        }

        .rounded-\[2\.5rem\],
        .shadow-2xl {
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #1a1a1a !important;
            color: white !important;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        h1, h2, h3 {
            color: black !important;
        }

        .text-blue-600,
        .dark\:text-blue-400 {
            color: black !important;
        }

        .bg-white,
        .dark\:bg-slate-900 {
            background: white !important;
        }

        .border-b-4,
        .border-b-2 {
            border-bottom: 1px solid #000 !important;
        }

        .px-12 {
            padding-left: 20px !important;
            padding-right: 20px !important;
        }

        .bg-blue-100/50,
        .dark\:bg-blue-900/30,
        .text-blue-700,
        .dark\:text-blue-300,
        .bg-slate-100,
        .dark\:bg-slate-800,
        .bg-slate-100/50,
        .dark\:bg-slate-800/50 {
            background-color: transparent !important;
            color: black !important;
            border: 1px solid #000 !important;
        }

        .inline-block {
            display: inline-block;
        }

        @page {
            margin: 0.5in;
            size: A4;
        }
    }
</style>
