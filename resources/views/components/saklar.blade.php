@props(['name', 'checked' => false, 'label'])
<label class="flex cursor-pointer items-center gap-3">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked) {{ $attributes }}>
    <span class="relative h-5 w-9 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-primer peer-focus-visible:ring-2 peer-focus-visible:ring-primer/40 after:absolute after:top-0.5 after:left-0.5 after:size-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4" aria-hidden="true"></span>
    <span class="text-xs text-slate-700">{{ $label }}</span>
</label>