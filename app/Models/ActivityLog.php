<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    /** Kayıt türlerinin ekranda görünen adları. */
    public const EVENTS = [
        'code.issued' => 'Kod verildi',
        'code.failed' => 'Kod alınamadı',
        'key.redeemed' => 'Anahtar eklendi',
        'key.invalid' => 'Hatalı anahtar denemesi',
        'key.created' => 'Anahtar üretildi',
        'key.revoked' => 'Anahtar iptal edildi',
        'key.restored' => 'Anahtar geri açıldı',
        'key.purged' => 'Anahtarlar temizlendi',
        'key.picked' => 'Paketten hesap seçildi',
        'package.saved' => 'Paket kaydedildi',
        'package.deleted' => 'Paket silindi',
        'account.created' => 'Hesap eklendi',
        'account.updated' => 'Hesap güncellendi',
        'account.deleted' => 'Hesap silindi',
        'account.tested' => 'Bağlantı denendi',
        'customer.toggled' => 'Müşteri durumu değişti',
        'settings.updated' => 'Ayarlar değişti',
        'auth.login' => 'Giriş yapıldı',
        'auth.failed' => 'Hatalı giriş',
        'auth.registered' => 'Kayıt olundu',
    ];

    public function label(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(ProductKey::class, 'product_key_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }
}
