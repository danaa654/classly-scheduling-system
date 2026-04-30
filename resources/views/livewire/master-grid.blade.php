<div class="min-h-screen bg-gray-100 dark:bg-slate-900" 
     x-data="{ roomsOpen: true, subjectsOpen: true }">
    <header class="flex justify-between items-center p-4 bg-white dark:bg-slate-800 shadow-md rounded-b-3xl border-b border-slate-200 dark:border-slate-700">
        <div>
            <h1 class="text-lg font-bold text-slate-800 dark:text-slate-100 uppercase">
                Master <span class="text-blue-600">Grid</span>
            </h1>
            <p class="text-[10px] text-slate-500 font-semibold uppercase mt-1">
                Active Filter: 
                <span class="text-blue-600 font-bold">{{ $selectedRoomName ?? 'All Facilities' }}</span>
            </p>
        </div>

        <div class="flex gap-2">
            <button @click="subjectsOpen = !subjectsOpen"
                    class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-700 text-blue-600 uppercase">
                Subjects
            </button>
            <button @click="roomsOpen = !roomsOpen"
                    class="px-3 py-1.5 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-700 text-purple-600 uppercase">
                Rooms
            </button>
            <button class="px-4 py-2 text-[10px] font-bold uppercase bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                Auto Generate
            </button>
        </div>
    </header>

    <div class="flex h-[calc(100vh-6rem)]">
        <main class="flex-1 bg-white dark:bg-slate-900 rounded-2xl shadow-md m-4 overflow-hidden border border-slate-200 dark:border-slate-800">
            @include('schedule-grid')
        </main>
        <div class="w-auto" x-show="roomsOpen || subjectsOpen">
            @include('master-grid-sidebar')
        </div>
    </div>
</div>
