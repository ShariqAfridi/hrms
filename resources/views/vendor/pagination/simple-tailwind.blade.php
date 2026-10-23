@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-1">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-500 bg-slate-900/60 border border-slate-800/80 cursor-not-allowed rounded-xl select-none">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white hover:border-slate-700 transition-all shadow-sm">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3.5 py-1.5 ml-3 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white hover:border-slate-700 transition-all shadow-sm">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="relative inline-flex items-center px-3.5 py-1.5 ml-3 text-xs font-semibold text-slate-500 bg-slate-900/60 border border-slate-800/80 cursor-not-allowed rounded-xl select-none">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </nav>
@endif
