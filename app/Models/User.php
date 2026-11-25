<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CUSTOMER = 'customer';

    protected $attributes = [
        'role' => self::ROLE_CUSTOMER,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class, 'redeemed_by');
    }

    /** Müşterinin hesaplara verdiği kendi adları; account_id => ad. */
    public function accountLabels(): array
    {
        return DB::table('account_labels')->where('user_id', $this->id)->pluck('label', 'account_id')->all();
    }

    /** Bu müşteri kod alabildiği (anahtarı ya da paket seçimi olan) bir hesap mı? */
    public function canUseAccount(Account $account): bool
    {
        return $this->keys()->where(fn ($q) => $q->where('account_id', $account->id)
            ->orWhereHas('selections', fn ($s) => $s->whereKey($account->id)))->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function homePath(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('home');
    }
}
