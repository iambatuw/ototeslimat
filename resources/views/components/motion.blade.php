{{-- Hareket kararı sayfa çizilmeden verilir: ziyaretçinin seçimi, yoksa (ayar açıksa) sistemin tercihi. --}}
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    var choice = null;
    try { choice = localStorage.getItem('still'); } catch (e) {}
    var system = {{ App\Models\Setting::flag('respect_reduced_motion') ? 'true' : 'false' }} && matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (choice === '1' || (choice === null && system)) document.documentElement.setAttribute('data-still', '');
})();
</script>
