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
         class="absolute right-0 mt-3 w-80 bg-white rounded-[2rem] shadow-2xl border border-slate-100 z-50 overflow-hidden shadow-blue-200/50">
        
        <div class="p-5 border-b border-slate-50 flex justify-between items-center bg-white">
            <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest">Notifications</h3>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <button wire:click="markAllAsRead" class="text-[10px] text-blue-600 font-black uppercase hover:underline">Mark all read</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
           @forelse(auth()->user()->notifications as $notification)
            {{-- Redesign: Blue tint for unread, White for read --}}
            <div class="p-4 border-b border-slate-50 flex gap-3 transition-colors 
                {{ $notification->unread() ? 'bg-blue-50/50 border-l-4 border-blue-500' : 'bg-white' }}">
                
                <div class="flex-1">
                    <p class="text-[13px] leading-snug {{ $notification->unread() ? 'text-slate-900 font-bold' : 'text-slate-600 font-medium' }}">
                        {{ $notification->data['message'] ?? 'New Notification' }}
                    </p>
                    <span class="text-[10px] text-slate-400 italic">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                </div>

                <div class="flex items-center">
                    @if($notification->unread())
                        <button wire:click="markAsRead('{{ $notification->id }}')" 
                                class="text-blue-600 hover:text-blue-800 p-1 bg-white rounded-lg shadow-sm border border-blue-100">
                            <span class="text-[10px] font-black px-1">MARK READ</span>
                        </button>
                    @else
                        <button wire:click="deleteNotification('{{ $notification->id }}')" class="text-slate-300 hover:text-red-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-xs italic">
                All caught up!
            </div>
        @endforelse
        </div>
    </div>
</div>