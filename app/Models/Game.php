<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Katalogdaki oyun. Hesaplar bir oyuna bağlanır; herkese açık /oyunlar sayfasında görünür. */
class Game extends Model
{
    protected $fillable = ['name', 'platform', 'cover', 'note', 'featured', 'is_visible'];

    protected $attributes = ['featured' => false, 'is_visible' => true];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'is_visible' => 'boolean'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** Oyun platformları (config/guard.php içinde group = game). */
    public static function platforms(): array
    {
        return collect(config('guard.platforms'))
            ->filter(fn ($p) => ($p['group'] ?? null) === 'game')
            ->map(fn ($p) => $p['label'])
            ->all();
    }

    public function platformLabel(): string
    {
        return config("guard.platforms.{$this->platform}.label", $this->platform);
    }

    public function platformMark(): string
    {
        return config("guard.platforms.{$this->platform}.mark") ?? mb_strtoupper(mb_substr($this->platformLabel(), 0, 1));
    }

    /** Kapağın tarayıcıdaki adresi: yüklenen dosya ya da https bağlantısı. */
    public function coverUrl(): ?string
    {
        if (! $this->cover) {
            return null;
        }

        return Str::startsWith($this->cover, 'https://') ? $this->cover : asset($this->cover);
    }
}
