<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Tek anahtarla müşterinin listeden pick_count kadar hesap seçtiği ürün. */
class Package extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'pick_count', 'is_active'];

    protected $attributes = ['is_active' => true, 'pick_count' => 1];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'pick_count' => 'integer'];
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class)->orderBy('title');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }
}
