@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center transition-colors duration-500">
        <div class="flex items-center bg-white dark:bg-slate-900/40 px-6 py-3 rounded-full shadow-sm border border-slate-100 dark:border-slate-800 space-x-2">
            
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="flex items-center text-slate-300 dark:text-slate-700 cursor-not-allowed px-3 py-2 text-sm font-bold uppercase tracking-tighter">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Prev
                </span>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled" class="flex items-center text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 text-sm font-bold uppercase tracking-tighter">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Prev
                </button>
            @endif

            {{-- Pagination Elements --}}
            <div class="flex items-center space-x-1 px-4 border-x border-slate-100 dark:border-slate-800">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="px-3 py-2 text-slate-400 dark:text-slate-600 text-sm">...</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-full text-sm font-black shadow-lg shadow-blue-200 dark:shadow-none ring-4 ring-blue-50 dark:ring-blue-900/20">
                                    {{ $page }}
                                </span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="w-10 h-10 flex items-center justify-center text-slate-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full text-sm font-bold transition-all">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled" class="flex items-center text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 text-sm font-bold uppercase tracking-tighter">
                    Next
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            @else
                <span class="flex items-center text-slate-300 dark:text-slate-700 cursor-not-allowed px-3 py-2 text-sm font-bold uppercase tracking-tighter">
                    Next
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </span>
            @endif

            {{-- Results Counter --}}
            <div class="hidden lg:block ml-4 pl-6 border-l border-slate-100 dark:border-slate-800">
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-black uppercase tracking-widest">
                    Showing <span class="text-slate-800 dark:text-slate-200">{{ $paginator->firstItem() }}</span> - <span class="text-slate-800 dark:text-slate-200">{{ $paginator->lastItem() }}</span> of <span class="text-slate-800 dark:text-slate-200">{{ number_format($paginator->total()) }}</span>
                </p>
            </div>
        </div>
    </nav>
@endif