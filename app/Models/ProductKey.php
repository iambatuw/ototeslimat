<?php

namespace App\Models;

use App\Support\KeyCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductKey extends Model
{
    protected $fillable = ['account_id', 'code', 'code_hash', 'label', 'max_uses', 'cooldown_minutes', 'valid_days'];

    protected $hidden = ['code', 'code_hash', 'last_code_hash'];

    protected $attributes = ['uses_count' => 0, 'cooldown_minutes' => 0];

    protected function casts(): array
    {
        return [
            'code' => 'encrypted',
            'max_uses' => 'integer',
            'cooldown_minutes' => 'integer',
            'valid_days' => 'integer',
            'uses_count' => 'integer',
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public static function findByCode(string $input): ?self
    {
        $body = KeyCode::normalize($input);

        return $body ? static::where('code_hash', KeyCode::hash($body))->first() : null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function usesLeft(): ?int
    {
        return $this->max_uses === null ? null : max(0, $this->max_uses - $this->uses_count);
    }

    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->isExpired() => 'expired',
            $this->max_uses !== null && $this->uses_count >= $this->max_uses => 'used_up',
            $this->redeemed_by !== null => 'active',
            default => 'unused',
        };
    }

    public const STATUS_LABELS = [
        'unused' => 'Satılmadı',
        'active' => 'Kullanımda',
        'used_up' => 'Hakkı bitti',
        'expired' => 'Süresi doldu',
        'revoked' => 'İptal',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status()];
    }

    /** Kural özeti: "3 hak, 30 gün, 10 dk ara" ya da "Sınırsız". */
    public function rulesSummary(): string
    {
        $parts = array_filter([
            $this->max_uses ? $this->max_uses.' hak' : null,
            $this->valid_days ? $this->valid_days.' gün' : null,
            $this->cooldown_minutes ? $this->cooldown_minutes.' dk ara' : null,
        ]);

        return $parts ? implode(', ', $parts) : 'Sınırsız';
    }

    public function scopeInStatus(Builder $query, ?string $status): Builder
    {
        $now = now();

        return match ($status) {
            'unused' => $query->whereNull('redeemed_by')->whereNull('revoked_at'),
            'active' => $query->whereNotNull('redeemed_by')->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))
                ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses')),
            'expired' => $query->whereNull('revoked_at')->whereNotNull('expires_at')->where('expires_at', '<=', $now),
            'used_up' => $query->whereNull('revoked_at')->whereNotNull('max_uses')->whereColumn('uses_count', '>=', 'max_uses'),
            'revoked' => $query->whereNotNull('revoked_at'),
            default => $query,
        };
    }
}
