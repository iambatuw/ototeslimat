<?php

namespace App\Services\Guard;

/**
 * Bir mailin konusundan ve metninden doğrulama kodunu çıkarır.
 *
 * Sıra: önce konu satırı, sonra kodun önünde geçen sözlerin ("login code",
 * "doğrulama kodu" ...) hemen arkası, en son mailin üst kısmındaki ilk aday.
 * Böylece alt bilgideki posta kodu, yıl, telefon gibi sayılar kod sanılmaz.
 *
 * E-posta ya da şifre değişikliği maillerindeki kodlar hiç verilmez
 * ("blocked"); müşteri bunlarla hesabı kendi üstüne geçirebilir.
 */
class CodeExtractor
{
    /** Kodun söz arkasında aranacağı en uzak mesafe (karakter). */
    private const WINDOW = 220;

    /** Söz bulunamazsa mailin baştan bu kadarına bakılır; alt bilgi dışarıda kalır. */
    private const FALLBACK_SPAN = 1500;

    /**
     * @param  array{pattern: string, type?: string}  $format
     * @param  list<string>  $anchors
     * @return array{code: string, blocked: bool}|null
     */
    public function extract(string $subject, string $text, array $format, array $anchors = []): ?array
    {
        $subject = trim(preg_replace('/\s+/u', ' ', $subject) ?? '');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        $pattern = $format['pattern'];

        if (($format['type'] ?? 'code') === 'link') {
            if (preg_match($pattern, $subject.' '.$text, $m)) {
                return ['code' => html_entity_decode($m[1] ?? $m[0], ENT_QUOTES | ENT_HTML5), 'blocked' => false];
            }

            return null;
        }

        if ($this->isBlocked($subject)) {
            $any = $this->firstCandidate($pattern, $subject.' '.$text, 0, self::FALLBACK_SPAN);

            return $any !== null ? ['code' => $any, 'blocked' => true] : null;
        }

        $anchors = array_values(array_unique(array_filter(array_merge($anchors, config('guard.common_anchors', [])))));

        // 1) Konu satırı: "Your ChatGPT code is 123456", "Your EA Security Code is: 482913"
        if ($subject !== '') {
            foreach ($anchors as $anchor) {
                $pos = mb_stripos($subject, $anchor);
                if ($pos !== false && ($code = $this->firstCandidate($pattern, $subject, $pos + mb_strlen($anchor), self::WINDOW)) !== null) {
                    // EA gibi bazı platformlar e-posta değişikliğinde de aynı konuyu kullanır;
                    // amaç metinde, kodun hemen önünde yazar.
                    $at = mb_strpos($text, $code);

                    return ['code' => $code, 'blocked' => $at !== false && $this->isBlocked($this->around($text, $at))];
                }
            }
        }

        // 2) Metin: sözün hemen arkası. Her söz için ilk geçtiği yerden başlayıp
        //    sonraki geçişlere de bakılır ("code" kelimesi birkaç kez geçebilir).
        $best = null;
        foreach ($anchors as $order => $anchor) {
            $offset = 0;
            $length = mb_strlen($anchor);
            while (($pos = mb_stripos($text, $anchor, $offset)) !== false) {
                $hit = $this->firstCandidateAt($pattern, $text, $pos + $length, self::WINDOW);
                if ($hit !== null) {
                    // Platforma özgü sözler listede önde; ilk tutan söz kazanır.
                    $best = ['code' => $hit['code'], 'at' => $pos];
                    break 2;
                }
                $offset = $pos + $length;
            }
        }

        if ($best !== null) {
            return ['code' => $best['code'], 'blocked' => $this->isBlocked($this->around($text, $best['at']))];
        }

        // 3) Son çare: mailin üst kısmındaki ilk aday.
        $code = $this->firstCandidate($pattern, $subject.' '.$text, 0, self::FALLBACK_SPAN);

        return $code !== null ? ['code' => $code, 'blocked' => false] : null;
    }

    public function isBlocked(string $text): bool
    {
        foreach (config('guard.blocked_purposes', []) as $regex) {
            if (preg_match($regex, $text)) {
                return true;
            }
        }

        return false;
    }

    /** Kodun ya da sözün önündeki 200, arkasındaki 40 karakter: mailin amacı burada yazar. */
    private function around(string $text, int $at): string
    {
        return mb_substr($text, max(0, $at - 200), min(200, $at) + 40);
    }

    private function firstCandidate(string $pattern, string $text, int $from, int $span): ?string
    {
        return $this->firstCandidateAt($pattern, $text, $from, $span)['code'] ?? null;
    }

    /** @return array{code: string, at: int}|null */
    private function firstCandidateAt(string $pattern, string $text, int $from, int $span): ?array
    {
        $slice = mb_substr($text, $from, $span);

        if (! preg_match_all($pattern, $slice, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        foreach ($matches[1] as [$raw, $byteOffset]) {
            $code = preg_replace('/[\s-]/', '', $raw);

            if ($this->looksLikeNoise($code, $slice, $byteOffset, strlen($raw))) {
                continue;
            }

            return ['code' => $code, 'at' => $byteOffset];
        }

        return null;
    }

    /** Yıl, saat, para, sıra numarası gibi kod olmayan sayıları eler. */
    private function looksLikeNoise(string $code, string $slice, int $offset, int $length): bool
    {
        if (ctype_digit($code)) {
            if (strlen($code) === 4 && preg_match('/^(19|20)\d\d$/', $code)) {
                return true; // yıl: © 2026, 25.11.2026
            }

            if (preg_match('/^(\d)\1+$/', $code)) {
                return true; // 000000, 1111
            }
        }

        $before = strtolower(substr($slice, max(0, $offset - 12), min(12, $offset)));
        $after = strtolower(substr($slice, $offset + $length, 8));

        return (bool) preg_match('/(?:no|nr|order|sipariş|ref|id|tel|phone|box|suite|ste|apt)[.:#\s]*$/u', $before)
            || (bool) preg_match('/^\s?(?:tl|usd|eur|try|gb|mb|kb|ms)\b/u', $after);
    }
}
