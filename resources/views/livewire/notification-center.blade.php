<div class="p-6 min-h-screen bg-[#f8fafc]">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Notification Center</h2>
            <p class="text-slate-500 text-sm font-medium">Manage and review all system requests and alerts.</p>
        </div>
        
        @if(auth()->user()->unreadNotifications->count() > 0)
            <button wire:click="markAllAsRead" class="px-4 py-2 bg-blue-600/10 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 hover:text-white transition-all">
                Mark All as Read
            </button>
        @endif
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Message</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Received</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse(auth()->user()->notifications as $notification)
                        <tr class="group hover:bg-slate-50/80 transition-all {{ $notification->unread() ? 'bg-blue-50/30' : '' }}">
                            <td class="px-6 py-4">
                                @if($notification->unread())
                                    <span class="flex h-2 w-2 rounded-full bg-blue-600 shadow-[0_0_8px_rgba(37,99,235,0.5)]"></span>
                                @else
                                    <span class="flex h-2 w-2 rounded-full bg-slate-300"></span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold text-slate-700 leading-tight">
                                        {{ $notification->data['message'] ?? 'System Notification' }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        Sent by: {{ $notification->data['sender_name'] ?? 'System' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-[11px] font-semibold text-slate-500 whitespace-nowrap uppercase">
                                    {{ $notification->created_at->format('M d, Y') }}
                                    <span class="block text-[9px] text-slate-400 font-normal lowercase italic">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ $notification->data['link'] ?? '#' }}" 
                                       wire:navigate
                                       wire:click="markAsRead('{{ $notification->id }}')"
                                       class="px-3 py-1.5 bg-[#0f172a] text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-blue-600 hover:scale-105 transition-all active:scale-95 shadow-sm">
                                        View Details
                                    </a>
                                    
                                    <button wire:click="deleteNotification('{{ $notification->id }}')" 
                                            class="p-1.5 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <span class="text-2xl">📪</span>
                                    </div>
                                    <h3 class="text-slate-400 font-black text-[10px] uppercase tracking-[0.2em]">No Notifications Found</h3>
                                    <p class="text-slate-400 text-[10px] mt-1">You're all caught up for today!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>