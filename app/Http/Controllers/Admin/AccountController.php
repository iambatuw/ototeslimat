<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Game;
use App\Models\ProductKey;
use App\Services\Guard\EmailCodeFetcher;
use App\Services\Guard\GuardException;
use App\Services\Guard\Totp;
use App\Support\Activity;
use App\Support\SteamCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AccountController extends Controller
{
    /** Hesap listesi. */
    public function index(Request $request)
    {
        $query = Account::query()
            ->with('game:id,name,cover')
            ->withCount(['keys', 'keys as unused_count' => fn ($q) => $q->inStatus('unused')])
            ->orderBy('title');

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        if ($q = trim((string) $request->input('q'))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('mail_username', 'like', $like));
        }

        return view('admin.accounts.index', [
            'accounts' => $query->get(),
            'filters' => $request->only('q', 'platform'),
        ]);
    }

    public function create()
    {
        return view('admin.accounts.form', ['account' => new Account(['platform' => 'netflix'])]);
    }

    public function store(Request $request)
    {
        $account = new Account;
        $this->fill($request, $account);
        $account->save();

        Activity::log('account.created', ['account' => $account, 'meta' => ['platform' => $account->platform]]);

        return redirect()->route('admin.keys.index', ['hedef' => 'account-'.$account->id])
            ->with('status', $account->title.' eklendi. Şimdi müşterilerine vereceğin anahtarları üret.');
    }

    public function edit(Account $account)
    {
        return view('admin.accounts.form', [
            'account' => $account,
            'users' => $this->liveKeys($account)->distinct()->count('redeemed_by'),
            'liveKeys' => $this->liveKeys($account)->count(),
            'others' => Account::whereKeyNot($account->id)->orderBy('platform')->orderBy('title')->get(['id', 'title', 'platform']),
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $this->fill($request, $account);
        $changed = array_keys($account->getDirty());
        $account->save();

        Activity::log('account.updated', ['account' => $account, 'meta' => ['fields' => $changed]]);

        return redirect()->route('admin.accounts.index')->with('status', $account->title.' güncellendi.');
    }

    public function destroy(Account $account)
    {
        $account->delete();
        Activity::log('account.deleted', ['account' => $account]);

        return redirect()->route('admin.accounts.index')->with('status', $account->title.' silindi. Bağlı anahtarlar artık kod vermez.');
    }

    public function test(Account $account, Totp $totp, EmailCodeFetcher $email)
    {
        if (! $account->isConfigured()) {
            return back()->with('error', 'Önce hesaba posta kutusu ya da Authenticator anahtarı ekle.');
        }

        $lines = [];

        try {
            if ($account->hasTotp()) {
                $lines[] = ($account->isSteamGuard() ? 'Steam Guard mobil' : 'Authenticator').' çalışıyor, şu anki kod '.$totp->code($account->totp_secret).'.';
            }

            if ($account->hasMailbox()) {
                $found = $email->fetch($account);

                if (! $found) {
                    $lines[] = 'Posta kutusuna bağlanıldı. Son '.config('guard.email_lookback_minutes').' dakikada '.$account->platformLabel().' kodu yok; bu normal.';
                } else {
                    $where = strcasecmp($found['folder'], 'INBOX') === 0 ? '' : ' ('.$found['folder'].' klasöründe)';
                    $lines[] = 'Posta kutusuna bağlanıldı, son kod '.$found['code'].$where.', '.$found['received_at']->diffForHumans().' geldi. Konu: "'.str($found['subject'])->limit(80).'".';

                    if ($found['blocked']) {
                        $lines[] = 'Bu mail e-posta ya da şifre değişikliği için; müşteriye gösterilmez.';
                    }
                }
            }
        } catch (GuardException|InvalidArgumentException $e) {
            Activity::log('account.tested', ['account' => $account, 'meta' => ['ok' => false]]);

            return back()->with('error', $e->getMessage().($e->getPrevious() ? ' ('.str($e->getPrevious()->getMessage())->limit(140).')' : ''));
        }

        Activity::log('account.tested', ['account' => $account, 'meta' => ['ok' => true]]);

        return back()->with('status', implode(' ', $lines));
    }

    /** Toplu hesap ekleme: her satır "eposta:şifre" ya da "eposta:şifre:imap.sunucu". */
    public function bulk(Request $request)
    {
        $data = $request->validateWithBag('bulk', [
            'platform' => ['required', Rule::in(array_keys(config('guard.platforms')))],
            'title' => ['required', 'string', 'max:100'],
            'lines' => ['required', 'string', 'max:20000'],
        ], [], ['title' => 'hesap adı', 'lines' => 'hesap listesi']);

        $presets = config('guard.mail_presets');
        $rows = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['lines']))));
        $skipped = [];
        $created = 0;

        DB::transaction(function () use ($rows, $data, $presets, &$skipped, &$created) {
            foreach ($rows as $i => $row) {
                [$email, $password, $host] = array_pad(preg_split('/[:;|\t]/', $row, 3), 3, null);
                $domain = strtolower((string) substr(strrchr((string) $email, '@'), 1));
                $preset = $host ? ['host' => $host, 'port' => 993, 'encryption' => 'ssl'] : ($presets[$domain] ?? null);

                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $password || ! $preset || ! preg_match('/^[A-Za-z0-9.\-]+$/', $preset['host'])) {
                    $skipped[] = $i + 1;

                    continue;
                }

                Account::create([
                    'platform' => $data['platform'],
                    'title' => $data['title'].' '.(++$created),
                    'mail_username' => $email,
                    'mail_password' => $password,
                    'mail_host' => strtolower($preset['host']),
                    'mail_port' => $preset['port'],
                    'mail_encryption' => $preset['encryption'],
                ]);
            }
        });

        Activity::log('account.created', ['meta' => ['bulk' => $created]]);

        $message = $created.' hesap eklendi.';
        if ($skipped) {
            $message .= ' Atlanan satırlar: '.implode(', ', $skipped).' (adres, şifre ya da bilinmeyen sunucu).';
        }

        return redirect()->route('admin.accounts.index')->with($skipped ? 'error' : 'status', $message);
    }

    /** Bu hesabı kullanan (anahtarı eklenmiş, iptal edilmemiş) anahtarlar; paket seçimleri dahil. */
    private function liveKeys(Account $account)
    {
        return ProductKey::whereNotNull('redeemed_at')->whereNull('revoked_at')
            ->where(fn ($q) => $q->where('account_id', $account->id)
                ->orWhereIn('id', DB::table('key_selections')->where('account_id', $account->id)->select('product_key_id')));
    }

    /**
     * Telafi: hesap bir süre çalışmadıysa bu hesabı kullanan bütün müşterilere tek seferde
     * ek kod hakkı ve/veya ek gün verir.
     */
    public function compensate(Request $request, Account $account)
    {
        $data = $request->validate([
            'rights' => ['nullable', 'integer', 'between:0,100'],
            'days' => ['nullable', 'integer', 'between:0,365'],
        ], [], ['rights' => 'ek hak', 'days' => 'ek gün']);

        $rights = (int) ($data['rights'] ?? 0);
        $days = (int) ($data['days'] ?? 0);

        if ($rights === 0 && $days === 0) {
            return back()->with('error', 'Ek hak ya da ek gün gir.');
        }

        $count = 0;

        DB::transaction(function () use ($account, $rights, $days, &$count) {
            foreach ($this->liveKeys($account)->lockForUpdate()->get() as $key) {
                $changes = [];

                if ($rights) {
                    $changes['bonus_uses'] = $key->bonus_uses + $rights;
                }

                if ($days && $key->expires_at) {
                    $changes['expires_at'] = ($key->expires_at->isPast() ? now() : $key->expires_at)->copy()->addDays($days);
                }

                if ($changes) {
                    $key->forceFill($changes)->save();
                    $count++;
                }
            }
        });

        Activity::log('account.compensated', ['account' => $account, 'meta' => ['rights' => $rights, 'days' => $days, 'keys' => $count]]);

        return back()->with('status', $count ? $count.' müşteri anahtarına telafi verildi.' : 'Bu hesabı kullanan müşteri yok.');
    }

    /**
     * Hesap kapandıysa ona bağlı anahtarları (ve paket seçimlerini) başka bir hesaba taşır.
     * Müşteri bir şey yapmaz; kartında yeni hesap görünür, verdiği ad da taşınır.
     */
    public function move(Request $request, Account $account)
    {
        $data = $request->validate([
            'target_id' => ['required', 'integer', Rule::notIn([$account->id]), Rule::exists('accounts', 'id')->whereNull('deleted_at')],
        ], ['target_id.not_in' => 'Aynı hesaba taşınamaz.'], ['target_id' => 'yeni hesap']);

        $target = Account::findOrFail($data['target_id']);

        $moved = DB::transaction(function () use ($account, $target) {
            $keys = ProductKey::where('account_id', $account->id)->update(['account_id' => $target->id, 'last_code_hash' => null]);

            // Paket seçimi: müşteri yeni hesabı zaten seçmişse eski seçim silinir.
            $selections = DB::table('key_selections')->where('account_id', $account->id)->get();
            foreach ($selections as $row) {
                $exists = DB::table('key_selections')->where(['product_key_id' => $row->product_key_id, 'account_id' => $target->id])->exists();
                $query = DB::table('key_selections')->where(['product_key_id' => $row->product_key_id, 'account_id' => $account->id]);
                $exists ? $query->delete() : $query->update(['account_id' => $target->id]);
            }

            foreach (DB::table('account_labels')->where('account_id', $account->id)->get() as $label) {
                $taken = DB::table('account_labels')->where(['user_id' => $label->user_id, 'account_id' => $target->id])->exists();
                $query = DB::table('account_labels')->where('id', $label->id);
                $taken ? $query->delete() : $query->update(['account_id' => $target->id]);
            }

            return $keys + $selections->count();
        });

        Activity::log('key.moved', ['account' => $target, 'meta' => ['from' => $account->title, 'to' => $target->title, 'count' => $moved]]);

        return redirect()->route('admin.accounts.edit', $target)->with('status', $moved.' anahtar '.$account->title.' hesabından buraya taşındı.');
    }

    private function fill(Request $request, Account $account): void
    {
        $isNew = ! $account->exists;
        $wantsMail = $request->boolean('use_mail');
        $wantsTotp = $request->boolean('use_totp');

        $data = $request->validate([
            'platform' => ['required', Rule::in(array_keys(config('guard.platforms')))],
            'title' => ['required', 'string', 'max:120'],
            'login_username' => ['nullable', 'string', 'max:190'],
            'login_password' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:300'],
            'game_id' => ['nullable', 'integer', Rule::exists('games', 'id')],
            'is_active' => ['boolean'],

            'mail_username' => [Rule::requiredIf($wantsMail), 'nullable', 'email', 'max:190'],
            'mail_password' => [Rule::requiredIf($wantsMail && ($isNew || ! $account->mail_password)), 'nullable', 'string', 'max:500'],
            'mail_host' => [Rule::requiredIf($wantsMail), 'nullable', 'string', 'max:190', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'mail_port' => [Rule::requiredIf($wantsMail), 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', Rule::in(['ssl', 'tls', 'starttls', 'none'])],
            'mail_from' => ['nullable', 'string', 'max:190'],
            'code_format' => ['nullable', Rule::in(array_keys(config('guard.code_formats')))],

            'totp_secret' => [Rule::requiredIf($wantsTotp && ($isNew || ! $account->totp_secret)), 'nullable', 'string', 'max:10000'],
        ], [], [
            'title' => 'hesap adı',
            'customer_note' => 'müşteriye not',
            'mail_username' => 'e-posta adresi',
            'mail_password' => 'e-posta şifresi',
            'mail_host' => 'IMAP sunucusu',
            'mail_port' => 'port',
            'totp_secret' => 'Authenticator anahtarı',
        ]);

        if (! $wantsMail && ! $wantsTotp) {
            throw ValidationException::withMessages(['use_mail' => 'Mail kodu ya da Authenticator kodundan en az birini aç.']);
        }

        $account->fill([
            'platform' => $data['platform'],
            'title' => $data['title'],
            'login_username' => $data['login_username'] ?? null,
            'notes' => $data['notes'] ?? null,
            'customer_note' => $data['customer_note'] ?? null,
            'game_id' => $data['game_id'] ?? $this->resolveGameId($data['platform'], $data['title']),
            'is_active' => $request->boolean('is_active'),
        ]);

        if (filled($data['login_password'] ?? null)) {
            $account->login_password = $data['login_password'];
        }

        if ($wantsMail) {
            $account->fill([
                'mail_username' => $data['mail_username'],
                'mail_host' => strtolower($data['mail_host']),
                'mail_port' => (int) $data['mail_port'],
                'mail_encryption' => $data['mail_encryption'] ?? 'ssl',
                'mail_from' => $data['mail_from'] ?? null,
                'code_format' => $data['code_format'] ?? null,
            ]);

            if (filled($data['mail_password'] ?? null)) {
                $account->mail_password = $data['mail_password'];
            }
        } else {
            $account->fill(['mail_username' => null, 'mail_host' => null, 'mail_port' => null, 'mail_encryption' => null, 'mail_from' => null, 'code_format' => null]);
            $account->mail_password = null;
        }

        if ($wantsTotp) {
            if (filled($data['totp_secret'] ?? null)) {
                try {
                    $account->totp_secret = Totp::cleanSecret($data['totp_secret'], $data['platform'] === 'steam');
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['totp_secret' => $e->getMessage()]);
                }
            }
        } else {
            $account->totp_secret = null;
        }
    }

    /** Oyun platformu ise ada göre katalogdan oyun bulur ya da Steam'den otomatik ekler. */
    private function resolveGameId(string $platform, string $title): ?int
    {
        if ((config("guard.platforms.{$platform}.group") ?? null) !== 'game') {
            return null;
        }

        $clean = trim($title);
        if ($clean === '') {
            return null;
        }

        $existing = Game::whereRaw('LOWER(name) = ?', [mb_strtolower($clean)])->first(['id']);
        if ($existing) {
            return $existing->id;
        }

        $matches = SteamCatalog::bestMatches([$clean]);
        $hit = $matches[$clean] ?? null;

        if (! $hit) {
            return null;
        }

        $cover = $hit['cover'] ?: $hit['wide'];
        if (! $cover) {
            return null;
        }

        $game = Game::create([
            'name' => $hit['name'] ?: $clean,
            'platform' => $platform,
            'cover' => $cover,
            'note' => $hit['note'] ?? null,
            'is_visible' => true,
        ]);

        return $game->id;
    }
}
