<div class="flex h-screen bg-[#F8FAFC]" x-data="{ open: @entangle('showModal'), bulkOpen: @entangle('bulkOpen') }">
   

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-12 shrink-0">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Subject Catalog</h2>
            <div class="flex items-center space-x-3">
                <button @click.prevent="bulkOpen = true" class="relative z-10 px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs uppercase cursor-pointer hover:bg-slate-200">📥 Bulk Import</button>
                <button wire:click="openModal" class="px-8 py-3 bg-blue-600 text-white rounded-2xl font-black shadow-xl text-xs uppercase">+ Add Subject</button>
            </div>
        </header>

        <div class="p-12 overflow-y-auto">
            {{-- Search & Filters --}}
            <div class="grid grid-cols-4 gap-4 mb-8 bg-white p-6 rounded-[2.5rem] border border-slate-200 shadow-sm">
                <select wire:model.live="selectedDept" class="bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                    <option value="">DEPT</option>
                    <option value="CCS">CCS</option>
                    <option value="SHTM">SHTM</option>
                    <option value="COC">COC</option>
                    <option value="CTE">CTE</option>
                </select>

                <select wire:model.live="selectedYear" class="bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                    <option value="">YEAR</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>

                <select wire:model.live="selectedMajor" class="bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                    <option value="">MAJOR</option>
                    @if($selectedDept == 'SHTM')
                        <option value="HM">Hospitality (HM)</option>
                        <option value="TM">Tourism (TM)</option>
                    @elseif($selectedDept == 'CCS')
                        <option value="IT">Information Technology (IT)</option> 
                        <option value="ACT">ACT (ACT)</option>       
                    @else
                        <option value="">N/A</option>
                    @endif
                </select>

                <div class="bg-slate-50 rounded-2xl flex items-center px-4 border border-transparent focus-within:border-blue-200">
                    <input type="text" wire:model.live="search" placeholder="Search..." class="w-full bg-transparent border-none focus:ring-0 font-bold text-sm">
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest">
                        <tr>
                            <th class="px-10 py-5">EDP Code</th>
                            <th class="px-10 py-5">Subject</th>
                            <th class="px-10 py-5">Duration</th>
                            <th class="px-10 py-5">Type</th>
                            <th class="px-10 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($subjects as $subject)
                        <tr>
                            <td class="px-10 py-6 font-black text-blue-600 uppercase">{{ $subject->edp_code }}</td>
                            <td class="px-10 py-6">
                                <p class="font-bold uppercase">{{ $subject->subject_code }}</p>
                                <p class="text-[10px] text-slate-400 truncate max-w-[200px]">{{ $subject->description }}</p>
                            </td>
                            <td class="px-10 py-6 font-bold text-slate-600">{{ $subject->duration_hours }} hrs</td>
                            <td class="px-10 py-6">
                                <div class="flex flex-col items-start">
                                    <span class="px-3 py-1 border rounded-lg text-[10px] font-black uppercase {{ strtolower($subject->type) === 'major' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                                        {{ $subject->type }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 mt-1 ml-1 uppercase tracking-tighter">
                                        {{ $subject->units }} Units
                                    </span>
                                </div>
                            </td>
                            <td class="px-10 py-6 text-right space-x-4">
                                <button wire:click="editSubject({{ $subject->id }})" class="text-blue-600 font-black text-xs uppercase hover:underline">Edit</button>
                                <button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Are you sure?" class="text-red-300 font-black text-xs uppercase hover:text-red-600">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-8 mb-6">
                {{ $subjects->links('livewire.custom-pagination') }}
            </div>
            </div>
        </div>
    </main>

    {{-- Bulk Import Modal --}}
    <div x-show="bulkOpen" 
         class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-md" 
         x-cloak 
         x-transition>
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl" @click.away="bulkOpen = false">
            <h3 class="text-2xl font-black text-slate-800 mb-6 uppercase text-center italic">CSV Bulk Import</h3>
            
            <form wire:submit.prevent="importSubjects">
                <div class="border-2 border-dashed border-slate-200 rounded-3xl p-8 flex flex-col items-center bg-slate-50 relative hover:bg-slate-100 transition-all">
                    <input type="file" wire:model="importFile" class="absolute inset-0 opacity-0 cursor-pointer">
                    
                    <div class="text-center">
                        <span class="text-xs font-black text-slate-600 uppercase tracking-tighter block">
                            {{ $importFile ? $importFile->getClientOriginalName() : 'Click to select CSV' }}
                        </span>
                        @if(!$importFile)
                            <span class="text-[10px] text-slate-400 uppercase mt-2 block">Format: EDP, Subj, Desc, Units, Department, Time Duration, Type</span>
                        @endif
                    </div>

                    <div wire:loading wire:target="importFile" class="text-blue-500 text-[10px] mt-2 font-black animate-pulse uppercase">
                        Uploading...
                    </div>
                </div>

                @error('importFile') <span class="text-red-500 text-[10px] font-bold mt-2 uppercase block text-center">{{ $message }}</span> @enderror

                <div class="flex flex-col gap-3 mt-6">
                    <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl active:scale-95 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="importSubjects">Process CSV</span>
                        <span wire:loading wire:target="importSubjects">Processing...</span>
                    </button>
                    
                    <button type="button" @click="bulkOpen = false" class="text-[10px] font-black text-slate-400 uppercase hover:text-slate-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md" x-cloak>
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl" @click.away="open = false">
            <h3 class="text-2xl font-black text-slate-800 mb-6 uppercase tracking-tighter">
                {{ $isEditMode ? 'Edit Subject' : 'New Subject' }}
            </h3>
            
            <form wire:submit.prevent="saveSubject" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" wire:model="edp_code" placeholder="EDP CODE" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                    <input type="text" wire:model="subject_code" placeholder="SUBJ CODE" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                </div>
                
                <input type="text" wire:model="description" placeholder="DESCRIPTION" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" wire:model="units" placeholder="UNITS" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm">

                    <select wire:model="type" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                        <option value="Major">Major</option>
                        <option value="Minor">Minor</option>
                    </select>
                </div>

                {{-- The Requested Duration Field --}}
                <div class="mt-4">
                    <label class="text-[10px] font-black opacity-40 uppercase ml-2 mb-1 block">Duration (Contact Hours)</label>
                    <select wire:model="duration_hours" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm uppercase">
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours (Standard Minor)</option>
                        <option value="4">4 Hours</option>
                        <option value="5">5 Hours (Lab Major)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Department</label>
                    <select wire:model="department" class="w-full border-slate-200 rounded-lg text-sm font-bold uppercase focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Department</option>
                        <option value="CCS">CCS</option>
                        <option value="CTE">CTE</option>
                        <option value="COC">COC</option>
                        <option value="SHTM">SHTM</option>
                        {{-- Add other departments as needed --}}
                    </select>
                    @error('department') <span class="text-red-500 text-[10px] font-bold">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full py-4 mt-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-xl active:scale-95 transition-all">
                    {{ $isEditMode ? 'Update' : 'Save' }} Subject
                </button>
            </form>
        </div>
    </div>
</div>
<style>
    /* Target the active pagination button */
    nav[role="navigation"] span[aria-current="page"] > span {
        background-color: #7c3aed !important; /* violet-600 */
        border-color: #a78bfa !important;     /* violet-400 */
        color: white !important;
        border-radius: 12px !important;
        /* The Glowing Effect */
        box-shadow: 0 0 15px rgba(124, 58, 237, 0.5), 0 0 5px rgba(124, 58, 237, 0.3) !important;
        transition: all 0.3s ease;
    }

    /* Optional: Hover effect for other page numbers */
    nav[role="navigation"] button:hover {
        color: #7c3aed !important;
        background-color: #f5f3ff !important; /* violet-50 */
        border-radius: 12px !important;
    }
</style>