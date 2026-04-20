<div class="min-h-screen bg-[#E6E6E6] dark:bg-[#020617] transition-colors duration-500"  
x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header --}}
        <header class="h-24 bg-white dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-12 shadow-sm shrink-0 backdrop-blur-xl rounded-b-[3rem] transition-colors">
            <h2 class="text-2xl font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">Subject Catalog</h2>
            <div class="flex items-center space-x-3">
                <button @click.prevent="bulkOpen = true" class="px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-xs uppercase hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">📥 Bulk Import</button>
                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 dark:bg-indigo-600 text-white rounded-2xl font-black shadow-xl dark:shadow-indigo-900/20 text-xs uppercase hover:scale-105 active:scale-95 transition-all">+ Add Subject</button>
            </div>
        </header>

        <div class="p-12 overflow-y-auto">
            {{-- Search & Filters --}}
            <div class="grid grid-cols-4 gap-4 mb-8 bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm">
                <select wire:model.live="selectedDept" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">DEPT</option>
                    <option value="CCS">CCS</option>
                    <option value="SHTM">SHTM</option>
                    <option value="COC">COC</option>
                    <option value="CTE">CTE</option>
                </select>

                <select wire:model.live="selectedYear" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">YEAR</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>

                <select wire:model.live="selectedMajor" class="bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">MAJOR</option>
                    @if($selectedDept == 'SHTM')
                        <option value="HM">Hospitality (HM)</option>
                        <option value="TM">Tourism (TM)</option>
                    @elseif($selectedDept == 'CCS')
                        <option value="IT">Information Technology (IT)</option>
                        <option value="ACT">ACT (ACT)</option> 
                    @elseif($selectedDept == 'COC')
                        <option value="FB">Forensic Biology (FB)</option>
                        <option value="LD">Lie Detection (LD)</option>
                        <option value="QD">Questioned Documents (QD)</option>  
                    @else
                        <option value="">N/A</option>
                    @endif
                </select>

                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center px-4 border border-transparent focus-within:ring-2 focus-within:ring-blue-500">
                    <input type="text" wire:model.live="search" placeholder="Search..." class="w-full bg-transparent border-none focus:ring-0 font-bold text-sm text-slate-700 dark:text-slate-200 placeholder-slate-400">
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-widest">
                        <tr>
                            <th class="px-10 py-5">EDP Code</th>
                            <th class="px-10 py-5">Subject</th>
                            <th class="px-10 py-5">Duration</th>
                            <th class="px-10 py-5">Type</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($subjects as $subject)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-10 py-6 font-black text-blue-600 dark:text-indigo-400 uppercase">{{ $subject->edp_code }}</td>
                            <td class="px-10 py-6">
                                <p class="font-bold uppercase text-slate-800 dark:text-slate-200">{{ $subject->subject_code }}</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[200px]">{{ $subject->description }}</p>
                            </td>
                            <td class="px-10 py-6 font-bold text-slate-600 dark:text-slate-400">{{ $subject->duration_hours }} hrs</td>
                            <td class="px-10 py-6">
                                <div class="flex flex-col items-start">
                                    <span class="px-3 py-1 border rounded-lg text-[10px] font-black uppercase {{ strtolower($subject->type) === 'major' ? 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800' : 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800' }}">
                                        {{ $subject->type }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-1 ml-1 uppercase tracking-tighter">
                                        {{ $subject->units }} Units
                                    </span>
                                </div>
                            </td>
                            <td class="px-10 py-6 text-right space-x-4">
                                <button wire:click="editSubject({{ $subject->id }})" class="text-blue-600 dark:text-indigo-400 font-black text-xs uppercase hover:underline">Edit</button>
                                <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Are you sure?" class="text-red-300 dark:text-red-900 font-black text-xs uppercase hover:text-red-600 dark:hover:text-red-500 transition-colors">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-8 mb-6 px-10">
                    {{ $subjects->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </main>

    {{-- Dark Mode Backdrop --}}
    <div x-show="open || bulkOpen" class="fixed inset-0 z-50 bg-slate-900/60 dark:bg-black/80 backdrop-blur-md" x-cloak x-transition></div>

    {{-- Bulk Import Modal --}}
    <div x-show="bulkOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-[3rem] p-10 shadow-2xl border border-transparent dark:border-slate-800" @click.away="bulkOpen = false">
            <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 mb-6 uppercase text-center italic">CSV Bulk Import</h3>
            <form wire:submit.prevent="importSubjects">
                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl p-8 flex flex-col items-center bg-slate-50 dark:bg-slate-800 relative hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    <div class="text-center">
                        <span class="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-tighter block">
                            {{ $importFile ? $importFile->getClientOriginalName() : 'Click to select CSV' }}
                        </span>
                        @if(!$importFile)
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase mt-2 block">Format: EDP, Subj, Desc, Units, Dept, Duration, Type</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col gap-3 mt-6">
                    <button type="submit" class="w-full py-4 bg-blue-600 dark:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl active:scale-95 transition-all">Process CSV</button>
                    <button type="button" @click="bulkOpen = false" class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- New/Edit Subject Modal --}}
    <div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-cloak>
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-[3rem] p-10 shadow-2xl border border-transparent dark:border-slate-800" @click.away="open = false">
            <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 mb-6 uppercase tracking-tighter">
                {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
            </h3>
            <form wire:submit.prevent="saveSubject" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" wire:model="edp_code" placeholder="EDP CODE" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
                    <input type="text" wire:model="subject_code" placeholder="SUBJ CODE" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
                </div>
                <input type="text" wire:model="description" placeholder="DESCRIPTION" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200 placeholder-slate-400">
                
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" wire:model="units" placeholder="UNITS" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm text-slate-700 dark:text-slate-200">
                    <select wire:model="type" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200">
                        <option value="Major">Major</option>
                        <option value="Minor">Minor</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black opacity-40 dark:text-slate-400 uppercase ml-2 mb-1 block">Duration</label>
                    <select wire:model="duration_hours" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200">
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours</option>
                        <option value="4">4 Hours</option>
                        <option value="5">5 Hours</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black opacity-40 dark:text-slate-400 uppercase ml-2 mb-1 block">Department</label>
                    <select wire:model="department" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 font-bold text-sm uppercase text-slate-700 dark:text-slate-200">
                        <option value="">Select Department</option>
                        <option value="CCS">CCS</option>
                        <option value="CTE">CTE</option>
                        <option value="COC">COC</option>
                        <option value="SHTM">SHTM</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-4 mt-4 bg-blue-600 dark:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl active:scale-95 transition-all">
                    {{ $isEditMode ? 'Update' : 'Save' }} Subject
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Pagination Overrides for Dark Mode */
    .dark nav[role="navigation"] span[aria-current="page"] > span {
        background-color: #4f46e5 !important; /* indigo-600 */
        border-color: #6366f1 !important;
        box-shadow: 0 0 15px rgba(79, 70, 229, 0.4) !important;
    }
    .dark nav[role="navigation"] button:hover {
        background-color: #1e293b !important;
        color: #818cf8 !important;
    }
</style>