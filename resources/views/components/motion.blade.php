{{-- Hareket kararı sayfa çizilmeden verilir: ziyaretçinin seçimi, yoksa (ayar açıksa) sistemin tercihi. --}}
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    var choice = null;
    try { choice = localStorage.getItem('still'); } catch (e) {}
    var system = {{ App\Models\Setting::flag('respect_reduced_motion') ? 'true' : 'false' }} && matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (choice === '1' || (choice === null && system)) document.documentElement.setAttribute('data-still', '');
    // Giriş animasyonları (içeriğin yükselmesi, listelerin sırayla gelmesi, grafik açılışı) bir sayfada
    // oturum başına yalnızca ilk açılışta oynar; F5 ya da geri dönüşte sayfa hazır gelir.
    try {
        var seen = 'seen:' + location.pathname;
        if (sessionStorage.getItem(seen)) document.documentElement.setAttribute('data-seen', '');
        sessionStorage.setItem(seen, '1');
    } catch (e) {}
})();
</script>
