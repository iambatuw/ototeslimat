<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Guard\EmailCodeFetcher;
use App\Services\Guard\GuardException;
use App\Services\Guard\Totp;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AccountController extends Controller
{
    /** Solda hesap listesi, sağda seçili hesabın anahtarları. */
    public function index(Request $request)
    {
        $query = Account::query()
            ->withCount(['keys', 'keys as unused_count' => fn ($q) => $q->inStatus('unused')])
            ->orderBy('title');

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        if ($q = trim((string) $request->input('q'))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('mail_username', 'like', $like));
        }

        $accounts = $query->get();
        $selected = $request->integer('hesap') ? $accounts->firstWhere('id', $request->integer('hesap')) : $accounts->first();

        $keys = $selected?->keys()
            ->with('customer:id,email')
            ->inStatus($request->input('status'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'selected' => $selected,
            'keys' => $keys,
            'filters' => $request->only('q', 'platform', 'status'),
            'fresh' => session('fresh_keys', []),
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

        return redirect()->route('admin.accounts.index', ['hesap' => $account->id])
            ->with('status', $account->title.' eklendi. Şimdi bu hesaba anahtar üretebilirsin.');
    }

    public function edit(Account $account)
    {
        return view('admin.accounts.form', ['account' => $account]);
    }

    public function update(Request $request, Account $account)
    {
        $this->fill($request, $account);
        $changed = array_keys($account->getDirty());
        $account->save();

        Activity::log('account.updated', ['account' => $account, 'meta' => ['fields' => $changed]]);

        return redirect()->route('admin.accounts.index', ['hesap' => $account->id])->with('status', $account->title.' güncellendi.');
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
                $lines[] = 'Authenticator çalışıyor, şu anki kod '.$totp->code($account->totp_secret).'.';
            }

            if ($account->hasMailbox()) {
                $found = $email->fetch($account);
                $lines[] = $found
                    ? 'Posta kutusuna bağlanıldı, son kod '.$found['code'].' ('.$found['received_at']->diffForHumans().').'
                    : 'Posta kutusuna bağlanıldı. Son '.config('guard.email_lookback_minutes').' dakikada '.$account->platformLabel().' kodu yok; bu normal.';
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

    private function fill(Request $request, Account $account): void
    {
        $isNew = ! $account->exists;
        $wantsMail = $request->boolean('use_mail');
        $wantsTotp = $request->boolean('use_totp');

        $data = $request->validate([
            'platform' => ['required', Rule::in(array_keys(config('guard.platforms')))],
            'title' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],

            'mail_username' => [Rule::requiredIf($wantsMail), 'nullable', 'email', 'max:190'],
            'mail_password' => [Rule::requiredIf($wantsMail && ($isNew || ! $account->mail_password)), 'nullable', 'string', 'max:500'],
            'mail_host' => [Rule::requiredIf($wantsMail), 'nullable', 'string', 'max:190', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'mail_port' => [Rule::requiredIf($wantsMail), 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', Rule::in(['ssl', 'tls', 'starttls', 'none'])],
            'mail_from' => ['nullable', 'string', 'max:120'],
            'code_format' => ['nullable', Rule::in(array_keys(config('guard.code_formats')))],

            'totp_secret' => [Rule::requiredIf($wantsTotp && ($isNew || ! $account->totp_secret)), 'nullable', 'string', 'max:300'],
        ], [], [
            'title' => 'hesap adı',
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
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

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
                    $account->totp_secret = Totp::cleanSecret($data['totp_secret']);
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['totp_secret' => $e->getMessage()]);
                }
            }
        } else {
            $account->totp_secret = null;
        }
    }
}
