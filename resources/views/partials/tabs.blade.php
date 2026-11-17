<nav class="flex gap-7 overflow-x-auto overflow-y-hidden border-b border-slate-200 px-5" aria-label="Saring">
    @foreach ($tabs as $nilai => [$label, $n])
        @php $on = (string) $aktif === (string) $nilai; @endphp
        <a href="{{ request()->fullUrlWithQuery([$param => $nilai ?: null, 'page' => null]) }}" @if ($on) aria-current="page" @endif @class([
            '-mb-px border-b-2 py-3.5 text-[13px] whitespace-nowrap',
            'border-primer font-semibold text-primer' => $on,
            'border-transparent text-slate-600 hover:text-slate-900' => ! $on,
        ])>{{ $label }} <span class="ml-1 {{ $on ? '' : 'text-slate-500' }}">{{ $n }}</span></a>
    @endforeach
</nav>