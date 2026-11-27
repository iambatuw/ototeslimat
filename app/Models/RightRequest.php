<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Hakkı dolan müşterinin satıcıdan ek kod hakkı istemesi. Satıcı onaylarsa anahtara +1 hak düşer. */
class RightRequest extends Model
{
    public const REASONS = [
        'used_up' => 'Toplam hakkı bitti',
        'period' => 'Dönem hakkı doldu',
        'cooldown' => 'Bekleme süresinde',
    ];

    protected $fillable = ['product_key_id', 'user_id', 'account_id', 'reason', 'status'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(ProductKey::class, 'product_key_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
