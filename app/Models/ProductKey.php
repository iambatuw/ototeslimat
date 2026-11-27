<?php

namespace App\Models;

use App\Support\KeyCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class ProductKey extends Model
{
    protected $fillable = ['account_id', 'package_id', 'code', 'code_hash', 'label', 'max_uses', 'cooldown_minutes', 'valid_days', 'period_limit', 'period_hours'];

    protected $hidden = ['code', 'code_hash', 'last_code_hash'];

    protected $attributes = ['uses_count' => 0, 'cooldown_minutes' => 0, 'period_hours' => 24, 'period_count' => 0, 'bonus_uses' => 0];

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
            'period_limit' => 'integer',
            'period_hours' => 'integer',
            'period_count' => 'integer',
            'period_started_at' => 'datetime',
            'bonus_uses' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    /** Paket anahtarında müşterinin seçtiği hesaplar. */
    public function selections(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'key_selections')->withTrashed()->withPivot('created_at')->orderBy('title');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function isPackage(): bool
    {
        return $this->package_id !== null;
    }

    /** Listelerde görünen ad: hesabın ya da paketin adı. */
    public function targetTitle(): string
    {
        return $this->isPackage()
            ? ($this->package?->name ?? 'Silinmiş paket').' (paket)'
            : ($this->account?->title ?? 'Silinmiş hesap');
    }

    /** Hedef (hesap ya da paket) silinmiş ya da kapatılmış mı? */
    public function targetUnavailable(): bool
    {
        $target = $this->isPackage() ? $this->package : $this->account;

        return ! $target || $target->trashed() || ! $target->is_active;
    }

    /** Bu anahtarla kod alınabilecek hesap mı? */
    public function grantsAccount(Account $account): bool
    {
        return $this->isPackage()
            ? $this->selections()->whereKey($account->id)->exists()
            : $this->account_id === $account->id;
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

    /** Toplam haktan kalan (satıcının verdiği ek haklar dahil). Sınırsızsa null. */
    public function usesLeft(): ?int
    {
        return $this->max_uses === null ? null : max(0, $this->max_uses - $this->uses_count) + $this->bonus_uses;
    }

    /** Toplam hak bitti mi? Satıcının verdiği ek hak varsa bitmiş sayılmaz. */
    public function isUsedUp(): bool
    {
        return $this->max_uses !== null && $this->uses_count >= $this->max_uses && $this->bonus_uses === 0;
    }

    /** Şu anki dönem hâlâ sürüyor mu? Sürmüyorsa sayaç sıfırdan başlar. */
    public function periodRunning(): bool
    {
        return $this->period_started_at !== null && $this->period_started_at->copy()->addHours($this->period_hours)->isFuture();
    }

    /** Bu dönemde kalan kod. Dönem sınırı yoksa null. */
    public function periodLeft(): ?int
    {
        if ($this->period_limit === null) {
            return null;
        }

        return max(0, $this->period_limit - ($this->periodRunning() ? $this->period_count : 0));
    }

    /** Dönem hakkı dolduysa yeni hakkın geleceği an. */
    public function periodResetsAt(): ?Carbon
    {
        return $this->periodLeft() === 0 ? $this->period_started_at->copy()->addHours($this->period_hours) : null;
    }

    /** "24 saatte 1 kod" gibi okunur dönem kuralı. */
    public function periodLabel(): ?string
    {
        if ($this->period_limit === null) {
            return null;
        }

        $window = match (true) {
            $this->period_hours === 24 => '24 saatte',
            $this->period_hours % 24 === 0 => intdiv($this->period_hours, 24).' günde',
            default => $this->period_hours.' saatte',
        };

        return $window.' '.$this->period_limit.' kod';
    }

    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->isExpired() => 'expired',
            $this->isUsedUp() => 'used_up',
            $this->redeemed_at !== null => 'active', // hesaba eklendi ya da hızlı onayda kullanıldı
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
            $this->periodLabel(),
            $this->cooldown_minutes ? $this->cooldown_minutes.' dk ara' : null,
        ]);

        return $parts ? implode(', ', $parts) : 'Sınırsız';
    }

    public function scopeInStatus(Builder $query, ?string $status): Builder
    {
        $now = now();

        return match ($status) {
            'unused' => $query->whereNull('redeemed_at')->whereNull('revoked_at'),
            'active' => $query->whereNotNull('redeemed_at')->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))
                ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses')->orWhere('bonus_uses', '>', 0)),
            'expired' => $query->whereNull('revoked_at')->whereNotNull('expires_at')->where('expires_at', '<=', $now),
            'used_up' => $query->whereNull('revoked_at')->whereNotNull('max_uses')->whereColumn('uses_count', '>=', 'max_uses')->where('bonus_uses', 0),
            'revoked' => $query->whereNotNull('revoked_at'),
            default => $query,
        };
    }
}
