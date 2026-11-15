<?php

namespace App\Services\Guard;

use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;
use Webklex\PHPIMAP\ClientManager;

/**
 * Hesabın posta kutusuna IMAP ile bağlanır, platformdan son gelen
 * doğrulama mailini bulur ve içindeki kodu (ya da bağlantıyı) çıkarır.
 */
class EmailCodeFetcher
{
    /**
     * @return array{code: string, received_at: CarbonInterface}|null
     *
     * @throws GuardException bağlantı ya da okuma hatasında
     */
    public function fetch(Account $account): ?array
    {
        $lookback = now()->subMinutes(config('guard.email_lookback_minutes'));
        $pattern = $account->codeFormatConfig()['pattern'];

        try {
            $client = (new ClientManager(['options' => ['fetch_order' => 'desc']]))->make([
                'host' => $account->mail_host,
                'port' => $account->mail_port ?: 993,
                'encryption' => $account->mail_encryption === 'none' ? false : ($account->mail_encryption ?: 'ssl'),
                'validate_cert' => true,
                'username' => $account->mail_username,
                'password' => $account->mail_password,
                'protocol' => 'imap',
                'timeout' => 15,
            ]);
            $client->connect();
        } catch (Throwable $e) {
            throw new GuardException('Posta kutusuna bağlanılamadı. Satıcıya haber ver.', 'imap_connect', $e);
        }

        try {
            // IMAP SINCE gün bazında çalışır; dakika süzgeci aşağıda.
            $query = $client->getFolderByPath('INBOX')->query()
                ->whereSince(Carbon::now()->subDay()->startOfDay())
                ->setFetchOrder('desc')
                ->leaveUnread()
                ->limit(20);

            if ($from = $account->senderFilter()) {
                $query->whereFrom($from);
            }

            foreach ($query->get() as $message) {
                $received = Carbon::instance($message->getDate()->toDate());

                if ($received->lt($lookback)) {
                    continue;
                }

                $text = (string) $message->getSubject().' '.$this->plainText(
                    $message->hasTextBody() ? $message->getTextBody() : '',
                    $message->hasHTMLBody() ? $message->getHTMLBody() : '',
                );

                if (preg_match($pattern, $text, $m)) {
                    return ['code' => html_entity_decode($m[1] ?? $m[0], ENT_QUOTES | ENT_HTML5), 'received_at' => $received];
                }
            }

            return null;
        } catch (Throwable $e) {
            throw new GuardException('Posta kutusu okunurken hata oluştu. Biraz sonra tekrar dene.', 'imap_read', $e);
        } finally {
            try {
                $client->disconnect();
            } catch (Throwable) {
                // bağlantı zaten kapanmış olabilir
            }
        }
    }

    /**
     * HTML'den stil, script ve renk kodlarını atıp düz metin üretir; desen yanlış
     * sayıya takılmasın. Bağlantı adresleri metne eklenir ki link biçimi bulabilsin.
     */
    private function plainText(string $text, string $html): string
    {
        if ($html !== '') {
            $html = preg_replace('#<(style|script|head)[^>]*>.*?</\1>#si', ' ', $html);
            preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $html, $links);
            $html = preg_replace('/#[0-9a-f]{3,8}\b/i', ' ', $html);
            $html = str_replace(['<br', '</p', '</td', '</div'], [' <br', ' </p', ' </td', ' </div'], $html);
            $text .= ' '.html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8').' '.implode(' ', $links[1]);
        }

        return preg_replace('/\s+/u', ' ', $text);
    }
}
