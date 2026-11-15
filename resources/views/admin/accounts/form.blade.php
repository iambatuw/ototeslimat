@php
    $editing = $account->exists;
    $platforms = config('guard.platforms');
    $useMail = (bool) old('use_mail', $editing ? $account->hasMailbox() : true);
    $useTotp = (bool) old('use_totp', $editing ? $account->hasTotp() : false);
@endphp
<x-layouts.app :title="$editing ? $account->title : 'Hesap ekle'">
    <div class="form-page">
        <a href="{{ route('admin.accounts.index') }}" class="back">Hesaplara dön</a>
        <div class="page-head">
            <div>
                <h1>{{ $editing ? $account->title : 'Hesap ekle' }}</h1>
                <p>Müşteri yalnızca hesap adını ve kodu görür. E-posta şifresi ve 2FA anahtarı şifreli saklanır.</p>
            </div>
            @if ($editing)
                <form method="POST" action="{{ route('admin.accounts.test', $account) }}" data-busy>
                    @csrf
                    <button type="submit" class="btn btn--secondary" data-busy-text="Deneniyor">Bağlantıyı dene</button>
                </form>
            @endif
        </div>

        <form method="POST" novalidate data-busy data-account-form
            action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}"
            data-presets='@json(config('guard.mail_presets'))'>
            @csrf
            @if ($editing) @method('PUT') @endif

            <section class="card">
                <h2 class="card__title">1. Hesap</h2>
                <p class="card__lead">Müşterinin listesinde görünecek ad.</p>
                <div class="grid-2 section-gap">
                    <div class="field">
                        <label for="platform">Platform</label>
                        <select id="platform" name="platform" class="select" required>
                            @foreach ($platforms as $key => $p)
                                <option value="{{ $key }}" @selected(old('platform', $account->platform) === $key)>{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="error">@error('platform'){{ $message }}@enderror</p>
                    </div>
                    <div class="field">
                        <label for="title">Hesap adı</label>
                        <input id="title" name="title" type="text" class="input" value="{{ old('title', $account->title) }}" maxlength="120" required placeholder="Netflix Premium 1" @error('title') aria-invalid="true" @enderror>
                        <p class="error">@error('title'){{ $message }}@enderror</p>
                    </div>
                </div>
                <div class="field">
                    <label for="notes">Not <span class="optional">(yalnızca sen görürsün)</span></label>
                    <input id="notes" name="notes" type="text" class="input" value="{{ old('notes', $account->notes) }}" maxlength="1000">
                    <p class="error">@error('notes'){{ $message }}@enderror</p>
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))> Bu hesap kod versin</label>
            </section>

            <section class="card">
                <h2 class="card__title">2. Mail kodu</h2>
                <input type="hidden" name="use_mail" value="0">
                <label class="check"><input type="checkbox" name="use_mail" value="1" data-toggle="#mail-fields" @checked($useMail)> Platform giriş kodunu e-postaya gönderiyor</label>
                <p class="error">@error('use_mail'){{ $message }}@enderror</p>

                <div id="mail-fields" @unless ($useMail) data-off @endunless>
                    <p class="card__lead">Sistem bu kutudan son {{ config('guard.email_lookback_minutes') }} dakikada gelen kodu okur. Gmail için normal şifre değil, <strong>uygulama şifresi</strong> gerekir. Outlook ve Hotmail desteklenmez.</p>
                    <div class="grid-2 section-gap">
                        <div class="field">
                            <label for="mail_username">E-posta adresi</label>
                            <input id="mail_username" name="mail_username" type="email" class="input" value="{{ old('mail_username', $account->mail_username) }}" autocomplete="off" @error('mail_username') aria-invalid="true" @enderror>
                            <p class="error">@error('mail_username'){{ $message }}@enderror</p>
                        </div>
                        <div class="field">
                            <label for="mail_password">E-posta şifresi</label>
                            <input id="mail_password" name="mail_password" type="password" class="input" autocomplete="new-password" @error('mail_password') aria-invalid="true" @enderror
                                placeholder="{{ $editing && $account->mail_password ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : '' }}">
                            <p class="error">@error('mail_password'){{ $message }}@enderror</p>
                        </div>
                    </div>
                    <details class="more" @if ($errors->hasAny(['mail_host', 'mail_port', 'mail_from', 'code_format'])) open @endif>
                        <summary>Sunucu ve kod ayarları <span class="optional">(Gmail, Yandex gibi bilinen adreslerde kendiliğinden dolar)</span></summary>
                        <div class="more__body">
                            <div class="grid-3">
                                <div class="field">
                                    <label for="mail_host">IMAP sunucusu</label>
                                    <input id="mail_host" name="mail_host" type="text" class="input" value="{{ old('mail_host', $account->mail_host) }}" placeholder="imap.gmail.com" autocomplete="off" spellcheck="false" @error('mail_host') aria-invalid="true" @enderror>
                                    <p class="error">@error('mail_host'){{ $message }}@enderror</p>
                                </div>
                                <div class="field">
                                    <label for="mail_port">Port</label>
                                    <input id="mail_port" name="mail_port" type="number" class="input" value="{{ old('mail_port', $account->mail_port ?? 993) }}" inputmode="numeric" @error('mail_port') aria-invalid="true" @enderror>
                                    <p class="error">@error('mail_port'){{ $message }}@enderror</p>
                                </div>
                                <div class="field">
                                    <label for="mail_encryption">Şifreleme</label>
                                    <select id="mail_encryption" name="mail_encryption" class="select">
                                        @foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS', 'none' => 'Yok'] as $v => $l)
                                            <option value="{{ $v }}" @selected(old('mail_encryption', $account->mail_encryption ?? 'ssl') === $v)>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <p class="hint"></p>
                                </div>
                            </div>
                            <div class="grid-2">
                                <div class="field">
                                    <label for="mail_from">Gönderen süzgeci</label>
                                    <input id="mail_from" name="mail_from" type="text" class="input" value="{{ old('mail_from', $account->mail_from) }}" maxlength="120" placeholder="Boşsa platformun adresi" autocomplete="off">
                                    <p class="hint">Gönderen adresinde geçen parça, örneğin netflix.com</p>
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
            </section>

            <section class="card">
                <h2 class="card__title">3. Authenticator (2FA)</h2>
                <input type="hidden" name="use_totp" value="0">
                <label class="check"><input type="checkbox" name="use_totp" value="1" data-toggle="#totp-fields" @checked($useTotp)> Hesapta iki adımlı doğrulama açık</label>

                <div id="totp-fields" @unless ($useTotp) data-off @endunless>
                    <p class="card__lead">2FA kurulurken çıkan "kodu elle gir" anahtarını yapıştır. Kod sunucuda üretilir, anahtar müşteriye gösterilmez.</p>
                    <div class="field section-gap">
                        <label for="totp_secret">Authenticator anahtarı</label>
                        <input id="totp_secret" name="totp_secret" type="password" class="input mono" autocomplete="off" spellcheck="false"
                            placeholder="{{ $editing && $account->totp_secret ? 'Kayıtlı, değiştirmeyeceksen boş bırak' : 'JBSW Y3DP EHPK 3PXP' }}" @error('totp_secret') aria-invalid="true" @enderror>
                        <p class="error">@error('totp_secret'){{ $message }}@enderror</p>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--l" data-busy-text="Kaydediliyor">{{ $editing ? 'Kaydet' : 'Hesabı ekle' }}</button>
                <a href="{{ route('admin.accounts.index') }}" class="btn btn--ghost btn--l">Vazgeç</a>
            </div>
        </form>

        @if ($editing)
            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="card section-gap row" data-confirm="{{ $account->title }} silinsin mi? Bağlı anahtarlar artık kod vermez.">
                @csrf @method('DELETE')
                <p class="muted small">Hesabı silersen bağlı anahtarlar kod vermeyi bırakır. Kayıtlar kalır.</p>
                <button type="submit" class="btn btn--danger btn--s">Hesabı sil</button>
            </form>
        @else
            <details class="card section-gap more" @if ($errors->bulk->any()) open @endif>
                <summary>Çok hesabın mı var? Toplu ekle</summary>
                <form method="POST" action="{{ route('admin.accounts.bulk') }}" class="more__body" data-busy novalidate>
                    @csrf
                    <p class="card__lead">Her satıra bir hesap yaz: <span class="mono">eposta:şifre</span>. Bilinmeyen sağlayıcıda sonuna sunucuyu ekle: <span class="mono">eposta:şifre:imap.alanadi.com</span></p>
                    <div class="grid-2">
                        <div class="field">
                            <label for="bulk-platform">Platform</label>
                            <select id="bulk-platform" name="platform" class="select">
                                @foreach ($platforms as $key => $p)
                                    <option value="{{ $key }}">{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="hint"></p>
                        </div>
                        <div class="field">
                            <label for="bulk-title">Hesap adı</label>
                            <input id="bulk-title" name="title" type="text" class="input" maxlength="100" placeholder="Netflix Premium">
                            <p class="{{ $errors->bulk->has('title') ? 'error' : 'hint' }}">{{ $errors->bulk->first('title') ?: 'Hesaplar "Ad 1, Ad 2" diye numaralanır.' }}</p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="lines">Hesaplar</label>
                        <textarea id="lines" name="lines" class="textarea mono" rows="5" spellcheck="false" placeholder="ornek1@gmail.com:uygulama-sifresi&#10;ornek2@yandex.com:sifre"></textarea>
                        <p class="error">{{ $errors->bulk->first('lines') }}</p>
                    </div>
                    <div><button type="submit" class="btn btn--secondary" data-busy-text="Ekleniyor">Hesapları ekle</button></div>
                </form>
            </details>
        @endif
    </div>
</x-layouts.app>
