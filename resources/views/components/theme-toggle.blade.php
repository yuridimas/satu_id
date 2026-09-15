{{-- Toggle tema gelap/terang untuk halaman yang memuat Flux (@fluxScripts). --}}
{{-- Memakai state global $flux.appearance sehingga sinkron dengan Settings > Appearance. --}}
<flux:button
    x-data
    type="button"
    variant="ghost"
    size="sm"
    square
    data-test="theme-toggle"
    :aria-label="__('Toggle theme')"
    :title="__('Toggle theme')"
    x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'"
    {{ $attributes->merge(['class' => 'fixed end-4 top-4 z-50']) }}
>
    <flux:icon.sun x-show="! $flux.dark" x-cloak class="size-5" />
    <flux:icon.moon x-show="$flux.dark" x-cloak class="size-5" />
</flux:button>
