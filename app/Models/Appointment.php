<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'product_id', 'staff_id', 'starts_at', 'ends_at', 'status', 'notes',
    'holder_name', 'holder_document', 'holder_email', 'holder_phone',
    'accountant_name', 'validation_method', 'terms_accepted_at', 'document_path',
])]
class Appointment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_CONFIRMED => 'Confirmado',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_COMPLETED => 'Concluído',
    ];

    public const VALIDATION_PRESENCIAL = 'presencial';
    public const VALIDATION_VIDEOCONFERENCIA = 'videoconferencia';

    public const VALIDATION_METHOD_LABELS = [
        self::VALIDATION_PRESENCIAL => 'Presencial',
        self::VALIDATION_VIDEOCONFERENCIA => 'Videoconferência',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function validationMethodLabel(): string
    {
        return self::VALIDATION_METHOD_LABELS[$this->validation_method] ?? $this->validation_method ?? '—';
    }

    /** CPF (11 dígitos) formatado 000.000.000-00, ou CNPJ (14) formatado 00.000.000/0000-00. */
    public function holderDocumentFormatted(): string
    {
        $d = $this->holder_document;

        if (strlen($d) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d);
        }

        if (strlen($d) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d);
        }

        return $d ?? '';
    }

    public function holderPhoneFormatted(): string
    {
        $d = $this->holder_phone ?? '';

        return strlen($d) === 11
            ? preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $d)
            : preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $d);
    }

    /** Tudo que o modal de detalhes do painel admin precisa, prontinho para virar JSON. */
    public function toCalendarPayload(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel(),
            'product' => $this->product->name,
            'startsAtLabel' => $this->starts_at->translatedFormat('d/m/Y (D) H:i'),
            'endsAtLabel' => $this->ends_at->format('H:i'),
            'rescheduleDate' => $this->starts_at->format('Y-m-d'),
            'rescheduleTime' => $this->starts_at->format('H:i'),
            'holderName' => $this->holder_name,
            'holderDocument' => $this->holderDocumentFormatted(),
            'holderEmail' => $this->holder_email,
            'holderPhone' => $this->holderPhoneFormatted(),
            'accountantName' => $this->accountant_name,
            'validationMethod' => $this->validationMethodLabel(),
            'notes' => $this->notes,
            'documentUrl' => $this->document_path ? route('admin.appointments.document', $this) : null,
            'updateUrl' => route('admin.appointments.update', $this),
            'rescheduleUrl' => route('admin.appointments.reschedule', $this),
        ];
    }

    /** O cliente cancela sozinho só com antecedência mínima (config agenda.cancel_min_hours). */
    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true)
            && $this->starts_at->gt(now()->addHours(config('agenda.cancel_min_hours')));
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /** Agendamentos que ocupam horário (cancelados liberam a vaga). */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
