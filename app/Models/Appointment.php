<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

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

    public const WHATSAPP_CONFIRM = 'confirm';
    public const WHATSAPP_REMIND = 'remind';

    /**
     * Link que abre o WhatsApp (app ou web) já na conversa com o cliente e com a mensagem
     * escrita — a equipe só aperta enviar. Não manda nada sozinho: é o "click to chat" gratuito.
     * Null se o agendamento não tiver telefone.
     */
    public function whatsappUrl(string $purpose): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $this->holder_phone);

        if (strlen($phone) < 10) {
            return null;
        }

        $when = $this->starts_at->translatedFormat('d/m/Y (l)').' às '.$this->starts_at->format('H:i');

        if ($purpose === self::WHATSAPP_REMIND) {
            $day = match (true) {
                $this->starts_at->isToday() => 'hoje',
                $this->starts_at->isTomorrow() => 'amanhã',
                default => 'no dia '.$this->starts_at->translatedFormat('d/m (l)'),
            };
            $intro = "Olá, {$this->holder_name}! Passando para lembrar do seu agendamento na Via Vale Sistemas: "
                ."{$this->product->name}, {$day} às {$this->starts_at->format('H:i')}.";
        } else {
            $intro = "Olá, {$this->holder_name}! Aqui é da Via Vale Sistemas. Seu agendamento está confirmado:\n\n"
                ."Serviço: {$this->product->name}\n"
                ."Data: {$when}\n"
                .'Validação: '.$this->validationMethodLabel();
        }

        $instructions = $this->validation_method === self::VALIDATION_VIDEOCONFERENCIA
            ? 'Como a validação é por videoconferência, envie aqui a foto da CNH aberta (frente e verso) antes do horário.'
            : 'Chegue com 10 minutos de antecedência e traga documento original com foto (CNH ou RG). '
                .'Endereço: Rua Leopoldo Macedo, 349, Sala 01, Ponte Alta, Aparecida - SP.'
                ."\nComo chegar: https://www.google.com/maps?cid=17307561992857966510";

        $text = $intro."\n\n".$instructions
            ."\n\nPrecisa mudar o horário? Reagende por aqui: ".$this->customerRescheduleUrl()
            ."\n\nQualquer dúvida, é só responder esta mensagem.";

        return 'https://wa.me/55'.$phone.'?text='.rawurlencode($text);
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
            'editUrl' => route('admin.appointments.edit', $this),
            'whatsappConfirmUrl' => $this->whatsappUrl(self::WHATSAPP_CONFIRM),
            'whatsappRemindUrl' => $this->whatsappUrl(self::WHATSAPP_REMIND),
        ];
    }

    /** O cliente reagenda sozinho só com antecedência mínima (config agenda.cancel_min_hours). */
    public function canBeRescheduledByCustomer(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true)
            && $this->starts_at->gt(now()->addHours(config('agenda.cancel_min_hours')));
    }

    /**
     * Link que o cliente recebe (e-mail/WhatsApp) para trocar o horário sozinho, sem login.
     * Assinado com a APP_KEY: trocar o número do agendamento na URL invalida o link.
     */
    public function customerRescheduleUrl(): string
    {
        return URL::signedRoute('booking.reschedule', $this);
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
