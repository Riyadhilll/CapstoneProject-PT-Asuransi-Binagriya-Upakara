@if ($paginator->hasPages())
<nav class="flex items-center gap-1.5" aria-label="Halaman">
    @php $kotak = 'grid size-8 place-items-center rounded-md border text-xs'; @endphp
    @if ($paginator->onFirstPage())
        <span class="{{ $kotak }} border-slate-200 text-slate-300" aria-hidden="true">&lsaquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $kotak }} border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Sebelumnya">&lsaquo;</a>
    @endif
    @foreach ($elements as $el)
        @if (is_string($el))<span class="{{ $kotak }} border-slate-200 text-slate-400">…</span>@endif
        @if (is_array($el))
            @foreach ($el as $hal => $url)
                @if ($hal == $paginator->currentPage())
                    <span class="{{ $kotak }} border-primer bg-primer font-semibold text-white" aria-current="page">{{ $hal }}</span>
                @else
                    <a href="{{ $url }}" class="{{ $kotak }} border-slate-300 text-slate-700 hover:bg-slate-50">{{ $hal }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $kotak }} border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Berikutnya">&rsaquo;</a>
    @else
        <span class="{{ $kotak }} border-slate-200 text-slate-300" aria-hidden="true">&rsaquo;</span>
    @endif
</nav>
@endif