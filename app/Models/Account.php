<?php

namespace App\Models;

use App\Services\Guard\Totp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Satılan hesap: kodun geldiği posta kutusu ve/veya Authenticator anahtarı. */
class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'platform', 'title', 'login_username', 'login_password',
        'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
        'mail_from', 'code_format', 'totp_secret', 'is_active', 'notes', 'customer_note', 'game_id',
    ];

    protected $hidden = ['mail_password', 'totp_secret', 'login_password'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'mail_password' => 'encrypted',
            'login_password' => 'encrypted',
            'totp_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_code_at' => 'datetime',
            'mail_port' => 'integer',
        ];
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }

    /** Katalogdaki oyun (varsa): müşterinin kartında kapak olarak görünür. */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    public function platformConfig(): array
    {
        return config('guard.platforms.'.$this->platform, config('guard.platforms.other'));
    }

    public function platformLabel(): string
    {
        return $this->platformConfig()['label'];
    }

    /**
     * Rozette görünen kısa işaret. Varsayılan platform adının ilk harfi; aynı harf ve
     * benzer renge düşen platformlar (Netflix/Nintendo, Prime/PlayStation) config'te
     * kendi işaretini taşır, listede karışmasın.
     */
    public function platformMark(): string
    {
        return $this->platformConfig()['mark'] ?? mb_substr($this->platformLabel(), 0, 1);
    }

    /** Platform logosu (public/img/platforms), yoksa null. Kaynak ve lisans: o klasördeki KAYNAK.md. */
    public function platformLogo(): ?string
    {
        $file = 'img/platforms/'.$this->platform.'.svg';

        return is_file(public_path($file)) ? asset($file) : null;
    }

    public function hasMailbox(): bool
    {
        return filled($this->mail_host) && filled($this->mail_username) && filled($this->mail_password);
    }

    public function hasTotp(): bool
    {
        return filled($this->totp_secret);
    }

    public function isConfigured(): bool
    {
        return $this->hasMailbox() || $this->hasTotp();
    }

    /**
     * Gönderen adresinde aranacak parçalar. Satıcının yazdığı süzgeç (virgülle
     * birden çok olabilir) platformun varsayılanının yerine geçer.
     *
     * @return list<string>
     */
    public function senders(): array
    {
        $custom = array_values(array_filter(array_map('trim', explode(',', (string) $this->mail_from))));

        return $custom ?: array_values((array) ($this->platformConfig()['from'] ?? []));
    }

    /** @return list<string> */
    public function codeAnchors(): array
    {
        return array_values((array) ($this->platformConfig()['anchors'] ?? []));
    }

    public function isSteamGuard(): bool
    {
        return Totp::isSteam($this->totp_secret);
    }

    /** Bekleme ekranındaki kutu sayısı. */
    public function codeLength(string $type): int
    {
        return $type === '2fa' ? Totp::length($this->totp_secret) : (int) ($this->codeFormatConfig()['length'] ?? 6);
    }

    public function codeFormat(): string
    {
        $format = $this->code_format ?: ($this->platformConfig()['format'] ?? 'digits6');

        return array_key_exists($format, config('guard.code_formats')) ? $format : 'digits6';
    }

    public function codeFormatConfig(): array
    {
        return config('guard.code_formats.'.$this->codeFormat());
    }
}
