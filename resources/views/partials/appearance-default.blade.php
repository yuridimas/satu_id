{{-- Force light theme on all guest (non-authenticated) pages. --}}
{{-- Wajib dimuat tepat setelah @fluxAppearance agar berlaku sebelum halaman di-paint. --}}
<script id="theme-default">
    try {
        @if (!auth()->check())
            window.Flux.applyAppearance('light');
        @elseif (window.localStorage.getItem('flux.appearance') === null)
            window.Flux.applyAppearance('light');
        @endif
    } catch (error) {
        document.documentElement.classList.remove('dark');
    }
</script>
