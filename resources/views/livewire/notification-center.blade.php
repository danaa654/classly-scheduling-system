<div x-data="{ open: false }" class="relative">
    {{-- Bell Icon --}}
    <button @click="open = !open" class="relative p-2 text-slate-400 hover:text-blue-600 transition-colors">
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
    <div x-show="open" @click.away="open = false" x-transition 
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

        {{-- Notifications List - SCROLLABLE AREA START --}}
        <div class="max-h-[25rem] overflow-y-auto custom-scrollbar bg-white">
           @forelse(auth()->user()->notifications as $notification)
            @php
                $senderName = $notification->data['sender_name'] ?? 'System';
                $words = explode(' ', $senderName);
                $initials = '';
                if (count($words) >= 2) {
                    $initials = substr($words[0], 0, 1) . substr($words[1], 0, 1);
                } else {
                    $initials = substr($senderName, 0, 2);
                }
                $initials = strtoupper($initials);
                $notifDate = \Carbon\Carbon::parse($notification->created_at)->timezone('Asia/Manila');
            @endphp

            {{-- Notification Row --}}
            <div wire:click="markAsRead('{{ $notification->id }}')" 
                class="p-4 border-b border-slate-50 flex items-start gap-4 transition-all relative group cursor-pointer 
                {{ $notification->unread() ? 'bg-blue-50/30' : 'bg-white hover:bg-slate-50' }}">
                
                {{-- Dynamic Avatar --}}
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full transition-colors flex items-center justify-center font-black text-xs border-2 border-white shadow-sm
                        {{ $notification->unread() ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                        {{ $initials }}
                    </div>
                </div>

              {{-- Content Area --}}
                <div class="flex-1 pr-4">
                    <p class="text-[12px] leading-[1.4] {{ $notification->unread() ? 'text-slate-900 font-black' : 'text-slate-500 font-medium' }}">
                        {{-- 1. Show the Sender Name (Blue if unread) --}}
                        <span class="{{ $notification->unread() ? 'text-blue-600' : 'text-slate-700' }}">
                            {{ $notification->data['sender_name'] ?? 'System' }}
                        </span> 
                        
                        {{-- 2. Show the Message/Action once (Bold if unread) --}}
                        <span class="ml-1 {{ $notification->unread() ? 'text-slate-900' : 'text-slate-700' }}">
                            {{ $notification->data['message'] ?? 'updated the registry' }}
                        </span>
                    </p>
                    
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                            @if($notifDate->isToday())
                                Today at {{ $notifDate->format('h:i A') }}
                            @else
                                {{ $notifDate->format('M d, Y | h:i A') }}
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Blinking Indicator / Trash --}}
                <div class="flex flex-col items-center justify-center self-center">
                    @if($notification->unread())
                        <div class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-600"></span>
                        </div>
                    @else
                        <button wire:click.stop="deleteNotification('{{ $notification->id }}')" 
                                class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
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
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">All caught up!</p>
            </div>
        @endforelse
        </div>
        {{-- Notifications List - SCROLLABLE AREA END --}}

        {{-- Optional Footer --}}
        <div class="p-4 bg-slate-50/50 border-t border-slate-50 text-center">
             <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">End of Notifications</p>
        </div>
    </div>
</div>

<style>
    /* Thin modern scrollbar for the notification list */
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>