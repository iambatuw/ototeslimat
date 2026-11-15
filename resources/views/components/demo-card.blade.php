@props(['autoplay' => false])
{{-- Denenebilen örnek: müşterinin gördüğü kartın aynısı. Gerçek sunucuya istek atmaz. --}}
<div class="demo" data-demo @if ($autoplay) data-demo-autoplay @endif>
    <div class="demo__row">
        <div>
            <p class="demo__title">Netflix Premium</p>
            <p class="demo__meta">Netflix, mail kodu</p>
        </div>
        <button type="button" class="btn btn--primary btn--s" data-demo-run>Mail kodunu al</button>
    </div>
    <div class="demo__body">
        <div class="cells" data-demo-cells data-length="4" aria-live="polite">
            <span class="cell"></span><span class="cell"></span><span class="cell"></span><span class="cell"></span>
        </div>
        <p class="demo__note" data-demo-note>Düğmeye bas, kodun gelişini gör.</p>
    </div>
</div>
