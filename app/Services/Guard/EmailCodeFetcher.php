<?php

namespace App\Services\Guard;

use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

/**
 * Hesabın posta kutusuna IMAP ile bağlanır, platformdan son gelen
 * doğrulama mailini bulur ve içindeki kodu (ya da bağlantıyı) çıkarır.
 *
 * Gelen kutusu ile birlikte Spam/Gereksiz klasörlerine de bakar, birden çok
 * gönderen adresini dener ve mailleri en yeniden eskiye sıralar.
 */
class EmailCodeFetcher
{
    /** Her klasör ve gönderen için en fazla bu kadar mail okunur. */
    private const PER_QUERY = 10;

    public function __construct(private readonly CodeExtractor $extractor) {}

    /**
     * @return array{code: string, received_at: CarbonInterface, subject: string, folder: string, blocked: bool}|null
     *
     * @throws GuardException bağlantı ya da okuma hatasında
     */
    public function fetch(Account $account): ?array
    {
        $client = $this->connect($account);

        try {
            return $this->pick($this->collect($client, $account), $account);
        } catch (GuardException $e) {
            throw $e;
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
     * Mailler arasından kodu seçer. En yeni mail kazanır; en yeni kod maili
     * bir e-posta/şifre değişikliği mailiyse "blocked" döner ve kod verilmez.
     *
     * @param  list<array{subject: string, text: string, from: string, received_at: CarbonInterface, folder: string}>  $messages
     * @return array{code: string, received_at: CarbonInterface, subject: string, folder: string, blocked: bool}|null
     */
    public function pick(array $messages, Account $account): ?array
    {
        $lookback = now()->subMinutes(config('guard.email_lookback_minutes'));
        $senders = array_map('strtolower', $account->senders());
        $format = $account->codeFormatConfig();
        $anchors = $account->codeAnchors();

        usort($messages, fn ($a, $b) => $b['received_at'] <=> $a['received_at']);

        foreach ($messages as $message) {
            if ($message['received_at']->lt($lookback)) {
                continue;
            }

            if ($senders && ! $this->fromMatches(strtolower($message['from']), $senders)) {
                continue;
            }

            $found = $this->extractor->extract($message['subject'], $message['text'], $format, $anchors);

            if ($found) {
                return [
                    'code' => $found['code'],
                    'blocked' => $found['blocked'],
                    'received_at' => $message['received_at'],
                    'subject' => mb_substr($message['subject'], 0, 160),
                    'folder' => $message['folder'],
                ];
            }
        }

        return null;
    }

    private function fromMatches(string $from, array $senders): bool
    {
        foreach ($senders as $sender) {
            if ($sender !== '' && str_contains($from, $sender)) {
                return true;
            }
        }

        return false;
    }

    private function connect(Account $account): Client
    {
        try {
            $client = (new ClientManager(['options' => ['fetch_order' => 'desc']]))->make([
                'host' => $account->mail_host,
                'port' => $account->mail_port ?: 993,
                'encryption' => $account->mail_encryption === 'none' ? false : ($account->mail_encryption ?: 'ssl'),
                'validate_cert' => true,
                'username' => $account->mail_username,
                'password' => $account->mail_password,
                'protocol' => 'imap',
                'timeout' => 12,
            ]);
            $client->connect();

            return $client;
        } catch (Throwable $e) {
            throw new GuardException('Posta kutusuna bağlanılamadı. Satıcıya haber ver.', 'imap_connect', $e);
        }
    }

    /** @return list<array{subject: string, text: string, from: string, received_at: CarbonInterface, folder: string}> */
    private function collect(Client $client, Account $account): array
    {
        $lookback = now()->subMinutes(config('guard.email_lookback_minutes'));
        $senders = $account->senders() ?: [null];
        $messages = [];
        $seen = [];

        foreach ($this->folders($client) as $index => $path) {
            try {
                $folder = $client->getFolderByPath($path);
            } catch (Throwable $e) {
                if ($index === 0) {
                    throw $e;
                }

                continue;
            }

            if (! $folder) {
                continue;
            }

            foreach ($senders as $sender) {
                try {
                    // IMAP SINCE gün bazında çalışır; dakika süzgeci pick() içinde.
                    $query = $folder->query()
                        ->whereSince(Carbon::now()->subDay()->startOfDay())
                        ->setFetchOrder('desc')
                        ->leaveUnread()
                        ->limit(self::PER_QUERY);

                    if ($sender) {
                        $query->whereFrom($sender);
                    }

                    $found = $query->get();
                } catch (Throwable $e) {
                    if ($index === 0) {
                        throw $e;
                    }

                    continue; // spam klasörü okunamazsa gelen kutusu yeter
                }

                foreach ($found as $message) {
                    try {
                        $date = $message->getDate()?->toDate();
                    } catch (Throwable) {
                        $date = null; // tarihi bozuk mail
                    }
                    if (! $date) {
                        continue;
                    }

                    $received = Carbon::instance($date);
                    if ($received->lt($lookback)) {
                        continue;
                    }

                    $id = (string) $message->getMessageId() ?: $path.'#'.$message->getUid();
                    if (isset($seen[$id])) {
                        continue;
                    }
                    $seen[$id] = true;

                    $messages[] = [
                        'subject' => $this->decode((string) $message->getSubject()),
                        'text' => $this->plainText(
                            $message->hasTextBody() ? $message->getTextBody() : '',
                            $message->hasHTMLBody() ? $message->getHTMLBody() : '',
                        ),
                        'from' => $this->fromAddress($message),
                        'received_at' => $received,
                        'folder' => $path,
                    ];
                }
            }
        }

        return $messages;
    }

    /** @return list<string> INBOX her zaman ilk sırada. */
    private function folders(Client $client): array
    {
        $paths = ['INBOX'];

        if (! config('guard.scan_spam')) {
            return $paths;
        }

        try {
            foreach ($client->getFolders(false) as $folder) {
                $this->spamFolders($folder, $paths);
            }
        } catch (Throwable) {
            // klasör listesi alınamazsa yalnızca gelen kutusu
        }

        return array_values(array_unique($paths));
    }

    private function spamFolders($folder, array &$paths): void
    {
        if (preg_match(config('guard.spam_folder_pattern'), $folder->name.' '.$folder->path)) {
            $paths[] = $folder->path;
        }

        foreach ($folder->children ?? [] as $child) {
            $this->spamFolders($child, $paths);
        }
    }

    private function fromAddress($message): string
    {
        try {
            $from = $message->getFrom();
            $first = $from?->first();

            return (string) ($first->mail ?? $from);
        } catch (Throwable) {
            return '';
        }
    }

    private function decode(string $value): string
    {
        if (str_contains($value, '=?')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * HTML'den stil, script ve renk kodlarını atıp düz metin üretir; desen yanlış
     * sayıya takılmasın. Bağlantı adresleri metne eklenir ki link biçimi bulabilsin.
     */
    public function plainText(string $text, string $html): string
    {
        if ($html !== '') {
            $html = preg_replace('#<(style|script|head|title)[^>]*>.*?</\1>#si', ' ', $html);
            preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $html, $links);
            $html = preg_replace('/#[0-9a-f]{3,8}\b/i', ' ', $html);
            $html = preg_replace('#<(br|/p|/td|/tr|/div|/h[1-6]|/li|/table)\b#i', ' <$1', $html);
            $html = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Bazı mailin düz metni yalnızca "tarayıcıda aç" der; ikisi birlikte okunur.
            $text .= ' '.$html.' '.implode(' ', $links[1]);
        }

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
