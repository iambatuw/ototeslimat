<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Satılan hesap: kodun geldiği posta kutusu ve/veya Authenticator anahtarı. */
class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'platform', 'title',
        'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
        'mail_from', 'code_format', 'totp_secret', 'is_active', 'notes',
    ];

    protected $hidden = ['mail_password', 'totp_secret'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'mail_password' => 'encrypted',
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

    public function senderFilter(): ?string
    {
        return $this->mail_from ?: ($this->platformConfig()['from'] ?? null);
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
