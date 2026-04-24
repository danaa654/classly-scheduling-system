<div x-data="{ open: false }" class="relative">
    {{-- Bell Icon --}}
    <button @click="open = !open" class="relative p-2 text-slate-400 hover:text-blue-600 transition-colors focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute top-2 right-2 flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
            </span>
        @endif
    </button>

    {{-- Dropdown Menu --}}
    <div x-show="open" 
         @click.away="open = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-3 w-96 bg-white rounded-[1.5rem] shadow-2xl border border-slate-100 z-50 overflow-hidden shadow-blue-200/50">
        
        {{-- Header --}}
        <div class="p-5 border-b border-slate-50 flex justify-between items-end bg-white sticky top-0 z-10">
            <div>
                <h3 class="font-black text-slate-800 text-sm tracking-tight uppercase">Notifications</h3>
                <div class="flex gap-3 mt-1">
                    <button wire:click="markAllAsRead" class="text-[10px] text-blue-600 font-bold uppercase hover:underline">Mark all read</button>
                    @if(auth()->user()->readNotifications->count() > 0)
                        <button wire:click="deleteAllRead" class="text-[10px] text-slate-400 font-bold uppercase hover:text-red-500 transition-colors">Clear read</button>
                    @endif
                </div>
            </div>
            <span class="text-[10px] bg-blue-50 px-2 py-1 rounded-full font-black text-blue-600 uppercase">
                {{ auth()->user()->unreadNotifications->count() }} New
            </span>
        </div>

        {{-- Notifications List --}}
        <div class="max-h-[25rem] overflow-y-auto custom-scrollbar bg-white">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'info';
                    
                    // Unified Color Logic for Faculty and Rooms
                    $style = match($type) {
                        // BLUE: Room Imports & Faculty Creation
                        'room_import', 'created', 'bulk_added' => [
                            'icon' => 'bg-blue-600', 
                            'text' => 'text-blue-600', 
                            'border' => 'bg-blue-600', 
                            'bg' => 'bg-blue-50/40'
                        ],
                        // DARK RED: Room Deletions & Faculty Deletions
                        'room_delete', 'deleted' => [
                            'icon' => 'bg-rose-900', 
                            'text' => 'text-rose-900', 
                            'border' => 'bg-rose-900', 
                            'bg' => 'bg-rose-100/30'
                        ],
                        // LIGHT RED: Rejections/Declines
                        'rejected', 'declined' => [
                            'icon' => 'bg-rose-500', 
                            'text' => 'text-rose-500', 
                            'border' => 'bg-rose-500', 
                            'bg' => 'bg-rose-50/40'
                        ],
                        // YELLOW: Pending Actions
                        'pending' => [
                            'icon' => 'bg-amber-500', 
                            'text' => 'text-amber-600', 
                            'border' => 'bg-amber-500', 
                            'bg' => 'bg-amber-50/40'
                        ],
                        // GREEN: Approvals
                        'approved' => [
                            'icon' => 'bg-emerald-600', 
                            'text' => 'text-emerald-600', 
                            'border' => 'bg-emerald-600', 
                            'bg' => 'bg-emerald-50/40'
                        ],
                        default => [
                            'icon' => 'bg-slate-600', 
                            'text' => 'text-slate-600', 
                            'border' => 'bg-slate-600', 
                            'bg' => 'bg-slate-50/40'
                        ],
                    };

                    $senderName = $data['sender_name'] ?? 'System';
                    $words = explode(' ', trim($senderName));
                    $initials = count($words) >= 2 
                        ? substr($words[0], 0, 1) . substr(end($words), 0, 1) 
                        : substr($senderName, 0, 2);
                    $initials = strtoupper($initials);
                    
                    $notifDate = \Carbon\Carbon::parse($notification->created_at)->timezone('Asia/Manila');
                    $isUnread = $notification->unread();
                @endphp

                <div wire:click="markAsRead('{{ $notification->id }}')" 
                    class="p-4 border-b border-slate-50 flex items-start gap-4 transition-all relative group cursor-pointer {{ $isUnread ? $style['bg'] : 'bg-white' }} hover:bg-slate-50">
                    
                    {{-- Side Accent Strip --}}
                    @if($isUnread)
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $style['border'] }}"></div>
                    @endif

                    {{-- Avatar Icon --}}
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center font-black text-xs border-2 border-white shadow-sm {{ $isUnread ? $style['icon'] : 'bg-slate-200' }} {{ $isUnread ? 'text-white' : 'text-slate-500' }}">
                            {{ $initials }}
                        </div>
                    </div>

                    <div class="flex-1 pr-4">
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="text-[9px] font-black uppercase tracking-widest {{ $style['text'] }}">
                                {{ str_replace('_', ' ', $type) }}
                            </span>
                        </div>

                        <p class="text-[12px] leading-tight {{ $isUnread ? 'text-slate-900 font-bold' : 'text-slate-600 font-medium' }}">
                            <span class="font-black {{ $isUnread ? $style['text'] : 'text-slate-700' }}">
                                {{ $senderName }}
                            </span> 
                            {{ $data['message'] ?? 'action performed' }}
                        </p>

                        @if(isset($data['faculty_name']))
                            <span class="text-[9px] font-bold text-slate-400 uppercase mt-1 block tracking-tighter">
                                Registry: {{ $data['faculty_name'] }}
                            </span>
                        @endif
                        
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mt-1 block">
                            {{ $notifDate->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Action/Status Area --}}
                    <div class="flex flex-col items-center justify-center self-center">
                        @if($isUnread)
                            <div class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $style['border'] }} opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $style['border'] }}"></span>
                            </div>
                        @else
                            <button wire:click.stop="deleteNotification('{{ $notification->id }}')" 
                                    class="text-slate-300 hover:text-rose-500 transition-colors opacity-0 group-hover:opacity-100 p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="bg-slate-50 h-12 w-12 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Inbox Empty</p>
                </div>
            @endforelse
        </div>

        <div class="p-4 bg-slate-50/50 border-t border-slate-50 text-center">
             <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Classly Registry v2.0</p>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>