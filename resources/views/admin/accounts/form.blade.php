@php
    use App\Models\Setting;
    $editing = $account->exists;
    $platforms = config('guard.platforms');
    $useMail = (bool) old('use_mail', $editing ? $account->hasMailbox() : true);
    $useTotp = (bool) old('use_totp', $editing ? $account->hasTotp() : false);
    $current = old('platform', $account->platform);
    $isSteam = $current === 'steam';
    $catalog = Setting::flag('games_catalog') ? \App\Models\Game::orderBy('platform')->orderBy('name')->get(['id', 'name', 'platform']) : collect();
    $bulkFirst = ! $editing && $errors->bulk->any();
    // Önizleme ve alanlar için platform bilgisi: işaret, grup (oyun mu).
    $meta = collect($platforms)->map(fn ($p, $key) => [
        'label' => $p['label'],
        'mark' => $p['mark'] ?? mb_strtoupper(mb_substr($p['label'], 0, 1)),
        'game' => ($p['group'] ?? null) === 'game',
    ]);
    $mailProviders = [
        'firstmail' => ['label' => 'FirstMail', 'host' => 'imap.firstmail.ltd', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@firstmail.ltd', 'passPlaceholder' => 'Posta şifresi', 'hint' => 'FirstMail hesaplarında normal posta şifresi ile doğrudan çalışır.'],
        'gmail' => ['label' => 'Gmail', 'host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@gmail.com', 'passPlaceholder' => 'Uygulama şifresi', 'hint' => 'Gmail\'de normal şifre yerine Google Uygulama Şifresi gerekir.'],
        'outlook' => ['label' => 'Outlook / Hotmail', 'host' => 'outlook.office365.com', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@outlook.com', 'passPlaceholder' => 'Posta / Uygulama şifresi', 'hint' => 'Outlook/Hotmail hesaplarında IMAP erişimi açık olmalıdır.'],
        'yandex' => ['label' => 'Yandex', 'host' => 'imap.yandex.com', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@yandex.com', 'passPlaceholder' => 'Uygulama şifresi', 'hint' => 'Yandex Mail ayarlarından IMAP ve uygulama şifresi açık olmalıdır.'],
        'mailru' => ['label' => 'Mail.ru', 'host' => 'imap.mail.ru', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@mail.ru', 'passPlaceholder' => 'Uygulama şifresi', 'hint' => 'Mail.ru güvenlik ayarlarından harici uygulama şifresi gerekir.'],
        'rambler' => ['label' => 'Rambler', 'host' => 'imap.rambler.ru', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'hesap@rambler.ru', 'passPlaceholder' => 'Posta şifresi', 'hint' => 'Rambler posta ayarlarından IMAP erişiminin açık olduğundan emin olun.'],
        'custom' => ['label' => 'Diğer (Özel IMAP)', 'host' => '', 'port' => 993, 'encryption' => 'ssl', 'emailPlaceholder' => 'ornek@alanadi.com', 'passPlaceholder' => 'Posta şifresi', 'hint' => 'Özel IMAP sunucu, port ve şifreleme bilgilerinizi aşağıya girin.'],
    ];
    $currentHost = strtolower((string) old('mail_host', $account->mail_host));
    $activeProvider = 'firstmail';
    if ($errors->hasAny(['mail_host', 'mail_port'])) {
        $activeProvider = 'custom';
    } elseif ($currentHost !== '') {
        $foundProvider = collect($mailProviders)->search(fn ($p) => $p['host'] !== '' && $p['host'] === $currentHost);
        $activeProvider = $foundProvider !== false ? $foundProvider : 'custom';
    }
    $providerInfo = $mailProviders[$activeProvider];
    $defaultHost = old('mail_host', $editing ? $account->mail_host : $providerInfo['host']);
    $defaultPort = old('mail_port', $editing ? ($account->mail_port ?? 993) : $providerInfo['port']);
    $defaultEnc = old('mail_encryption', $editing ? ($account->mail_encryption ?? 'ssl') : $providerInfo['encryption']);
@endphp
<x-layouts.app :title="$editing ? $account->title : 'Hesap ekle'">
    <div class="acctform">
        <a href="{{ route('admin.accounts.index') }}" class="back">Hesaplara dön</a>
        <header class="page-head">
            <div>
                <h1>{{ $editing ? $account->title : 'Hesap ekle' }}</h1>
                <p>Müşteri yalnızca hesap adını ve kodu görür. E-posta şifresi ve 2FA anahtarı şifreli saklanır.</p>
            </div>
            @if ($editing)
                <div class="page-head__actions">
                    <form method="POST" action="{{ route('admin.accounts.test', $account) }}" data-busy>
                        @csrf
                        <button type="submit" class="btn btn--secondary" data-busy-text="Deneniyor">Bağlantıyı dene</button>
                    </form>
                    <a href="{{ route('admin.keys.index', ['hedef' => 'account:'.$account->id]) }}" class="btn btn--primary">Anahtar üret</a>
                </div>
            @else
                <nav class="segmented" aria-label="Ekleme biçimi" data-tabs>
                    <a href="#tek" data-tab="tek" @unless ($bulkFirst) aria-current="page" @endunless>Tek hesap</a>
                    <a href="#toplu" data-tab="toplu" @if ($bulkFirst) aria-current="page" @endif>Toplu ekle</a>
                </nav>
            @endif
        </header>

        <div id="tek" class="acctform__pane" data-pane="tek" @if ($bulkFirst) hidden @endif>
        <form method="POST" novalidate data-busy data-account-form class="acctform__grid"
            action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}"
            data-presets='@json(config('guard.mail_presets'))' data-providers='@json($mailProviders)' data-platforms='@json($meta)'>
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="acctform__main">
                {{-- 1. Hesap --}}
                <section class="fcard" aria-labelledby="sec-account">
                    <header class="fcard__head">
                        <span class="fcard__num" aria-hidden="true">1</span>
                        <div>
                            <h2 id="sec-account">Hesap</h2>
                            <p>Platform ve müşterinin listesinde görünecek ad.</p>
                        </div>
                    </header>
                    <div class="grid-2">
                        <div class="field">
                            <label for="platform">Platform</label>
                            <select id="platform" name="platform" class="select" required data-platform>
                                @foreach (config('guard.platform_groups') as $group => $groupLabel)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($platforms as $key => $p)
                                            @continue(($p['group'] ?? 'tool') !== $group)
                                            <option value="{{ $key }}" @selected($current === $key)>{{ $p['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="error">@error('platform'){{ $message }}@enderror</p>
                        </div>
                        <div class="field">
                            <label for="title">Hesap adı</label>
                            <input id="title" name="title" type="text" class="input" value="{{ old('title', $account->title) }}" maxlength="120" required placeholder="Örn. Netflix Premium 1" data-preview-title @error('title') aria-invalid="true" @enderror>
                            <p class="error">@error('title'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    @if ($catalog->isNotEmpty())
                        <div class="field" data-for-game @unless ($meta[$current]['game'] ?? false) hidden @endunless>
                            <label for="game_id">Katalogdaki oyun <span class="optional">(isteğe bağlı)</span></label>
                            <select id="game_id" name="game_id" class="select">
                                <option value="">Bağlama</option>
                                @foreach ($catalog->groupBy('platform') as $platform => $items)
                                    <optgroup label="{{ config("guard.platforms.$platform.label", $platform) }}">
                                        @foreach ($items as $game)
                                            <option value="{{ $game->id }}" @selected((int) old('game_id', $account->game_id) === $game->id)>{{ $game->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="{{ $errors->has('game_id') ? 'error' : 'hint' }}">{{ $errors->first('game_id') ?: 'Bağlarsan müşterinin kartında oyunun kapağı görünür.' }}</p>
                        </div>
                    @endif
                </section>

                {{-- 2. Kod nereden gelir --}}
                <section class="fcard" aria-labelledby="sec-source">
                    <header class="fcard__head">
                        <span class="fcard__num" aria-hidden="true">2</span>
                        <div>
                            <h2 id="sec-source">Kod nereden gelir?</h2>
                            <p>Birini ya da ikisini aç. Müşteri hangisi açıksa o düğmeyi görür.</p>
                        </div>
                    </header>
                    <p class="error">@error('use_mail'){{ $message }}@enderror</p>

                    <div class="sources">
                        <label class="source">
                            <input type="hidden" name="use_mail" value="0">
                            <input type="checkbox" name="use_mail" value="1" class="switch" data-toggle="#mail-fields" data-source="mail" @checked($useMail)>
                            <span class="source__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 8 8 5.5L20 8"/></svg></span>
                            <span class="source__text"><strong>Mail kodu</strong><small>Platform kodu e-postaya gönderiyor</small></span>
                        </label>
                        <label class="source">
                            <input type="hidden" name="use_totp" value="0">
                            <input type="checkbox" name="use_totp" value="1" class="switch" data-toggle="#totp-fields" data-source="totp" @checked($useTotp)>
                            <span class="source__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                            <span class="source__text">
                                <strong data-for-platform="!steam" @if ($isSteam) hidden @endif>Authenticator (2FA)</strong>
                                <strong data-for-platform="steam" @unless ($isSteam) hidden @endunless>Steam Guard</strong>
                                <small>Hesapta iki adımlı doğrulama açık</small>
                            </span>
                        </label>
                    </div>

                    <div id="mail-fields" class="source-fields" @unless ($useMail) data-off @endunless>
                        <h3>Mail sağlayıcısı</h3>
                        <div class="provider-pills" role="group" aria-label="Mail sağlayıcısı">
                            @foreach ($mailProviders as $pKey => $pInfo)
                                <button type="button" class="provider-pill" data-provider="{{ $pKey }}" aria-pressed="{{ $activeProvider === $pKey ? 'true' : 'false' }}">{{ $pInfo['label'] }}</button>
                            @endforeach
                        </div>
                        <p class="hint" data-provider-hint>{{ $providerInfo['hint'] }}</p>

                        <div class="grid-2">
                            <div class="field">
                                <label for="mail_username">E-posta adresi</label>
                                <input id="mail_username" name="mail_username" type="email" class="input" value="{{ old('mail_username', $account->mail_username) }}" autocomplete="off" placeholder="{{ $providerInfo['emailPlaceholder'] }}" @error('mail_username') aria-invalid="true" @enderror>
                                <p class="error">@error('mail_username'){{ $message }}@enderror</p>
                            </div>
                            <div class="field">
                                <label for="mail_password">E-posta şifresi</label>
                                <input id="mail_password" name="mail_password" type="password" class="input" autocomplete="new-password" @error('mail_password') aria-invalid="true" @enderror
                                    @if ($editing && $account->mail_password) data-has-saved="1" @endif
                                    placeholder="{{ $editing && $account->mail_password ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : $providerInfo['passPlaceholder'] }}">
                                <p class="error">@error('mail_password'){{ $message }}@enderror</p>
                            </div>
                        </div>

                        <div class="grid-3 imap-manual" data-imap-manual @if ($activeProvider !== 'custom') hidden @endif>
                            <div class="field">
                                <label for="mail_host">IMAP sunucusu</label>
                                <input id="mail_host" name="mail_host" type="text" class="input" value="{{ $defaultHost }}" placeholder="imap.firstmail.ltd" autocomplete="off" spellcheck="false" @error('mail_host') aria-invalid="true" @enderror>
                                <p class="error">@error('mail_host'){{ $message }}@enderror</p>
                            </div>
                            <div class="field">
                                <label for="mail_port">Port</label>
                                <input id="mail_port" name="mail_port" type="number" class="input" value="{{ $defaultPort }}" inputmode="numeric" @error('mail_port') aria-invalid="true" @enderror>
                                <p class="error">@error('mail_port'){{ $message }}@enderror</p>
                            </div>
                            <div class="field">
                                <label for="mail_encryption">Şifreleme</label>
                                <select id="mail_encryption" name="mail_encryption" class="select">
                                    @foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS', 'none' => 'Yok'] as $v => $l)
                                        <option value="{{ $v }}" @selected($defaultEnc === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <details class="more" @if ($errors->hasAny(['mail_from', 'code_format'])) open @endif>
                            <summary>Gelişmiş kod ayarları <span class="optional">(isteğe bağlı)</span></summary>
                            <div class="more__body">
                                <div class="grid-2">
                                    <div class="field">
                                        <label for="mail_from">Gönderen süzgeci</label>
                                        <input id="mail_from" name="mail_from" type="text" class="input" value="{{ old('mail_from', $account->mail_from) }}" maxlength="190" placeholder="Boşsa platformun adresi" autocomplete="off">
                                        <p class="hint">Gönderen adresinde geçen parça, örn. netflix.com. Birden çoksa virgülle ayır.</p>
                                    </div>
                                    <div class="field">
                                        <label for="code_format">Kod biçimi</label>
                                        <select id="code_format" name="code_format" class="select">
                                            <option value="">Platformun varsayılanı</option>
                                            @foreach (config('guard.code_formats') as $v => $f)
                                                <option value="{{ $v }}" @selected(old('code_format', $account->code_format) === $v)>{{ $f['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <p class="hint">Kod yanlış okunursa buradan değiştir.</p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div id="totp-fields" class="source-fields" @unless ($useTotp) data-off @endunless>
                        <h3><span data-for-platform="!steam" @if ($isSteam) hidden @endif>Authenticator anahtarı</span><span data-for-platform="steam" @unless ($isSteam) hidden @endunless>Steam Guard dosyası</span></h3>
                        <p class="hint" data-for-platform="!steam" @if ($isSteam) hidden @endif>2FA kurulurken çıkan "kodu elle gir" anahtarını yapıştır. Epic, EA, Ubisoft, PlayStation, Nintendo, Xbox ve ChatGPT'de aynı anahtar kullanılır. Kod sunucuda üretilir, anahtar müşteriye gösterilmez.</p>
                        <p class="hint" data-for-platform="steam" @unless ($isSteam) hidden @endunless><span class="mono">.maFile</span> içeriğini olduğu gibi ya da içindeki <span class="mono">shared_secret</span> değerini yapıştır. Müşteri 5 karakterlik Steam Guard kodunu görür; dosya gösterilmez.</p>
                        <div class="field">
                            <label for="totp_secret" class="visually-hidden">Anahtar</label>
                            @php($saved = $editing && $account->totp_secret)
                            <textarea id="totp_secret" name="totp_secret" rows="2" class="textarea mono secret" autocomplete="off" spellcheck="false"
                                data-ph="{{ $saved ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : 'JBSW Y3DP EHPK 3PXP' }}"
                                data-ph-steam="{{ $saved ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : '{"shared_secret": "...", ...}' }}"
                                placeholder="{{ $saved ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : ($isSteam ? '{"shared_secret": "...", ...}' : 'JBSW Y3DP EHPK 3PXP') }}" @error('totp_secret') aria-invalid="true" @enderror></textarea>
                            <p class="{{ $errors->has('totp_secret') ? 'error' : 'hint' }}">{{ $errors->first('totp_secret') ?: ($saved ? 'Kayıtlı anahtar var'.($account->isSteamGuard() ? ' (Steam Guard)' : '').'.' : '') }}</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Sağ panel: kaydet --}}
            <aside class="acctform__side" aria-label="Kaydet">
                <div class="acctform__save">
                    <input type="hidden" name="is_active" value="0">
                    <label class="switch-row switch-row--tight">
                        <span><span class="cell-title">Kod versin</span><span class="muted small">Kapalıyken müşteri bu hesaptan kod alamaz.</span></span>
                        <input type="checkbox" class="switch" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))>
                    </label>
                    <button type="submit" class="btn btn--primary btn--l btn--block" data-busy-text="Kaydediliyor">{{ $editing ? 'Kaydet' : 'Hesabı ekle' }}</button>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn--ghost btn--block">Vazgeç</a>
                </div>
            </aside>
        </form>

        @if ($editing)
            <section class="tools-grid section-gap" aria-label="Bu hesabın müşterileri">
                <form method="POST" action="{{ route('admin.accounts.compensate', $account) }}" class="card stack" data-busy novalidate
                    data-confirm="Bu hesabı kullanan {{ $users }} müşteriye telafi verilsin mi?">
                    @csrf
                    <div>
                        <h2 class="card__title">Telafi ver</h2>
                        <p class="muted small">Hesap bir süre çalışmadıysa onu kullanan bütün müşterilere tek seferde ek kod hakkı ve ek gün ver. Şu an {{ $users }} müşteri, {{ $liveKeys }} anahtar.</p>
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label for="comp-rights">Ek kod hakkı</label>
                            <input id="comp-rights" name="rights" type="number" class="input" min="0" max="100" value="1" inputmode="numeric">
                        </div>
                        <div class="field">
                            <label for="comp-days">Ek gün</label>
                            <input id="comp-days" name="days" type="number" class="input" min="0" max="365" value="1" inputmode="numeric">
                            <p class="hint">Süresiz anahtarlara gün eklenmez.</p>
                        </div>
                    </div>
                    <div><button type="submit" class="btn btn--secondary" data-busy-text="Veriliyor" @disabled(! $liveKeys)>Herkese ver</button></div>
                </form>

                <form method="POST" action="{{ route('admin.accounts.move', $account) }}" class="card stack" data-busy novalidate
                    data-confirm="Bu hesaba bağlı bütün anahtarlar seçtiğin hesaba taşınsın mı? Müşteriler yeni hesabı görür.">
                    @csrf
                    <div>
                        <h2 class="card__title">Anahtarları başka hesaba taşı</h2>
                        <p class="muted small">Hesap kapandıysa ona bağlı anahtarları ve paket seçimlerini yeni hesaba aktar. Müşteri bir şey yapmaz; kartında yeni hesap görünür, verdiği ad korunur.</p>
                    </div>
                    <div class="field">
                        <label for="move-target">Yeni hesap</label>
                        <select id="move-target" name="target_id" class="select" @error('target_id') aria-invalid="true" @enderror>
                            <option value="">Hesap seç</option>
                            @foreach ($others as $other)
                                <option value="{{ $other->id }}" @selected((int) old('target_id') === $other->id)>{{ $other->platformLabel() }} · {{ $other->title }}</option>
                            @endforeach
                        </select>
                        <p class="error">@error('target_id'){{ $message }}@enderror</p>
                    </div>
                    <div><button type="submit" class="btn btn--secondary" data-busy-text="Taşınıyor" @disabled($others->isEmpty())>Taşı</button></div>
                </form>
            </section>

            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="danger" data-confirm="{{ $account->title }} silinsin mi? Bağlı anahtarlar artık kod vermez.">
                @csrf @method('DELETE')
                <p class="muted small">Hesabı silersen bağlı anahtarlar kod vermeyi bırakır. Kayıtlar kalır.</p>
                <button type="submit" class="btn btn--danger btn--s">Hesabı sil</button>
            </form>
        @endif
        </div>

        @unless ($editing)
            <div id="toplu" class="acctform__pane" data-pane="toplu" @unless ($bulkFirst) hidden @endunless>
                <form method="POST" action="{{ route('admin.accounts.bulk') }}" class="fcard fcard--narrow" data-busy novalidate>
                    @csrf
                    <header class="fcard__head">
                        <span class="fcard__num" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                        </span>
                        <div>
                            <h2>Toplu ekle</h2>
                            <p>Aynı platformdan çok sayıda mail kodlu hesabı tek seferde ekle.</p>
                        </div>
                    </header>
                    <div class="grid-2">
                        <div class="field">
                            <label for="bulk-platform">Platform</label>
                            <select id="bulk-platform" name="platform" class="select">
                                @foreach (config('guard.platform_groups') as $group => $groupLabel)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($platforms as $key => $p)
                                            @continue(($p['group'] ?? 'tool') !== $group)
                                            <option value="{{ $key }}" @selected(old('platform') === $key)>{{ $p['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="bulk-title">Hesap adı</label>
                            <input id="bulk-title" name="title" type="text" class="input" maxlength="100" value="{{ old('title') }}" placeholder="Netflix Premium" @if ($errors->bulk->has('title')) aria-invalid="true" @endif>
                            <p class="{{ $errors->bulk->has('title') ? 'error' : 'hint' }}">{{ $errors->bulk->first('title') ?: 'Hesaplar "Ad 1, Ad 2" diye numaralanır.' }}</p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="lines">Hesaplar <span class="optional">(her satıra bir hesap)</span></label>
                        <textarea id="lines" name="lines" class="textarea mono" rows="7" spellcheck="false" placeholder="ornek1@gmail.com:uygulama-sifresi&#10;ornek2@yandex.com:sifre&#10;ornek3@alanadi.com:sifre:imap.alanadi.com" @if ($errors->bulk->has('lines')) aria-invalid="true" @endif></textarea>
                        <p class="{{ $errors->bulk->has('lines') ? 'error' : 'hint' }}">{{ $errors->bulk->first('lines') ?: 'Biçim: eposta:şifre. Gmail, Yandex gibi bilinen sağlayıcılarda sunucu kendiliğinden bulunur; diğerlerinde sonuna :imap.sunucu ekle.' }}</p>
                    </div>
                    <div><button type="submit" class="btn btn--primary" data-busy-text="Ekleniyor">Hesapları ekle</button></div>
                </form>
            </div>
        @endunless
    </div>
</x-layouts.app>
