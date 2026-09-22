<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'active'])]
class Staff extends Model
{
    protected $table = 'staff';

    /** Atendente usado enquanto a agenda tem um só; depois vira escolha do cliente. */
    public static function primary(): self
    {
        return static::query()->where('active', true)->orderBy('id')->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
