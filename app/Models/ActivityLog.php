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

    /** Kod verilemediğinde ya da anahtar reddedildiğinde kaydedilen nedenlerin ekranda görünen karşılıkları. */
    public const REASONS = [
        'not_found' => 'böyle bir anahtar yok',
        'unavailable' => 'anahtar iptal ya da hesap silinmiş',
        'taken' => 'başka hesapta kullanılmış',
        'no_mail' => 'son mailde kod yok',
        'imap_connect' => 'posta kutusuna bağlanılamadı',
        'imap_read' => 'posta okunamadı',
        'totp_secret' => '2FA anahtarı bozuk',
        'no_totp' => 'hesapta 2FA yok',
        'no_mailbox' => 'hesapta posta kutusu yok',
        'inactive' => 'hesap durdurulmuş',
        'revoked' => 'anahtar iptal',
        'expired' => 'süresi dolmuş',
        'used_up' => 'hakkı bitmiş',
        'cooldown' => 'bekleme süresinde',
        'blocked_purpose' => 'e-posta/şifre değişikliği maili, kod gizlendi',
        'quick' => 'hızlı onayda kullanılıyor',
        'package' => 'paket anahtarı hızlı onayda kullanılamaz',
    ];

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
