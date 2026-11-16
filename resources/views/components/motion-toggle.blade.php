{{-- Hareketi durdurup başlatan küçük düğme. Durum ve etiket app.js içinde güncellenir. --}}
<button type="button" {{ $attributes->class('motion-btn') }} data-motion-toggle aria-pressed="false" aria-label="Hareketi durdur" title="Hareketi durdur">
    <svg class="motion-btn__pause" viewBox="0 0 16 16" aria-hidden="true"><rect x="3.5" y="3" width="3" height="10" rx="1"/><rect x="9.5" y="3" width="3" height="10" rx="1"/></svg>
    <svg class="motion-btn__play" viewBox="0 0 16 16" aria-hidden="true"><path d="M5 3.2v9.6a.6.6 0 0 0 .9.5l7.6-4.8a.6.6 0 0 0 0-1L5.9 2.7a.6.6 0 0 0-.9.5Z"/></svg>
</button>
