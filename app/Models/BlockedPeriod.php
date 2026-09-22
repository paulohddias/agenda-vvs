<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'starts_at', 'ends_at', 'reason'])]
class BlockedPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** true quando o bloqueio cobre o(s) dia(s) inteiro(s), de 00:00 até 23:59:59. */
    public function isFullDay(): bool
    {
        return $this->starts_at->format('H:i:s') === '00:00:00'
            && $this->ends_at->format('H:i:s') === '23:59:59';
    }
}
