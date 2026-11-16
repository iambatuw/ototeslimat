@props(['autoplay' => false])
{{-- Denenebilen örnek: müşterinin gördüğü kartın aynısı. Gerçek sunucuya istek atmaz. --}}
<div class="demo glass" data-demo @if ($autoplay) data-demo-autoplay @endif>
    <div class="demo__row">
        <div>
            <p class="demo__title">Netflix Premium</p>
            <p class="demo__meta">Netflix, mail kodu</p>
        </div>
        <button type="button" class="btn btn--primary btn--s" data-demo-run>Mail kodunu al</button>
    </div>
    <div class="demo__body">
        <div class="mail" data-demo-mail aria-live="polite">
            <span class="mail__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 8 8 5.5L20 8"/></svg>
            </span>
            <div>
                <p class="mail__title" data-demo-title>Posta kutusu</p>
                <p class="mail__text" data-demo-note>Düğmeye bas, kodun gelişini izle.</p>
            </div>
            <span class="mail__time" data-demo-time></span>
        </div>
        <div class="cells" data-demo-cells data-length="4" role="img" aria-label="Kod bekleniyor">
            <span class="cell"></span><span class="cell"></span><span class="cell"></span><span class="cell"></span>
        </div>
    </div>
</div>
