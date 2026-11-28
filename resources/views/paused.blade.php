{{-- Lisans bitince ziyaretçi ve müşterilerin gördüğü sayfa (App\Http\Middleware\EnsureLicense). $contact --}}
<x-layouts.guest title="Hizmet durduruldu" :bare="true">
    <section class="paused" aria-labelledby="paused-title">
        <span class="paused__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="6" y="5" width="4" height="14" rx="1.5"/><rect x="14" y="5" width="4" height="14" rx="1.5"/></svg>
        </span>
        <h1 id="paused-title">Hizmet geçici olarak durduruldu</h1>
        <p>Bu mağazada kod teslimi şu anda kapalı. Hesapların ve anahtarların silinmedi; hizmet yeniden açılınca kaldığın yerden devam edersin.</p>
        <div class="paused__actions">
            @if ($contact)
                <a href="{{ $contact }}" class="btn btn--primary" rel="noopener" target="_blank">Satıcıyla iletişime geç</a>
            @endif
            <a href="{{ route('login') }}" class="btn btn--ghost">Yönetici girişi</a>
        </div>
    </section>
</x-layouts.guest>
