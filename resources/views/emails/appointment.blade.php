<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<style>
    body { margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #1f2937; }
    .wrapper { max-width: 560px; margin: 0 auto; padding: 24px 16px; }
    .header { background: #2C3A49; border-bottom: 4px solid #35D159; padding: 20px 24px; border-radius: 8px 8px 0 0; text-align: center; }
    .header img { height: 48px; }
    .card { background: #ffffff; padding: 24px; border-radius: 0 0 8px 8px; }
    h1 { font-size: 18px; color: #2C3A49; margin: 0 0 12px; }
    p { font-size: 14px; line-height: 1.6; margin: 0 0 12px; }
    table.details { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px; }
    table.details td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
    table.details td.label { color: #6b7280; width: 40%; }
    table.details td.value { color: #111827; font-weight: bold; text-align: right; }
    .badge { display: inline-block; background: #dcfce7; color: #166534; font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 999px; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    td.old { color: #9ca3af; text-decoration: line-through; font-weight: normal; }
    .notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 14px 16px; font-size: 13px; color: #92400e; margin: 16px 0; }
    .footer { text-align: center; font-size: 12px; color: #9ca3af; padding: 20px 0; }
</style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            {{-- Embutido como anexo inline (cid:), não como URL: assim aparece mesmo sem o site estar
                 publicamente acessível, e sem depender de o cliente de e-mail liberar imagens externas. --}}
            <img src="{{ $message->embed(public_path('images/logo-vvs.png')) }}" alt="Via Vale Sistemas">
        </div>
        <div class="card">
            <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
            <h1 style="margin-top:12px;">Olá, {{ $appointment->holder_name }}!</h1>
            <p>{{ $intro }}</p>

            <table class="details">
                <tr><td class="label">Serviço</td><td class="value">{{ $appointment->product->name }}</td></tr>
                <tr><td class="label">Data</td><td class="value">{{ $appointment->starts_at->translatedFormat('d/m/Y (l)') }}</td></tr>
                <tr><td class="label">Horário</td><td class="value">{{ $appointment->starts_at->format('H:i') }} às {{ $appointment->ends_at->format('H:i') }}</td></tr>
                @if ($previousStart)
                    <tr><td class="label">Horário anterior</td><td class="value old">{{ $previousStart->translatedFormat('d/m/Y') }} às {{ $previousStart->format('H:i') }}</td></tr>
                @endif
                <tr><td class="label">Forma de validação</td><td class="value">{{ $appointment->validationMethodLabel() }}</td></tr>
            </table>

            <p>{!! $closing !!}</p>

            @if (! $cancelled)
            <div class="notice">
                <strong>Documentação obrigatória:</strong> compareça com 10 minutos de antecedência e leve um documento original com foto — CNH (obrigatória) ou RG — além dos documentos da empresa (Requerimento ou Contrato Social).
                @if ($appointment->validation_method === \App\Models\Appointment::VALIDATION_VIDEOCONFERENCIA)
                    <br><br>Como a validação será por <strong>videoconferência</strong>, é obrigatório possuir CNH. Envie uma foto da CNH aberta (frente e verso) pelo WhatsApp (12) 3600-5110 antes do horário marcado.
                @endif
            </div>
            @endif

            @if ($rescheduleUrl)
                <p style="text-align:center; margin:20px 0;">
                    <a href="{{ $rescheduleUrl }}" style="display:inline-block; background:#2C3A49; color:#ffffff; text-decoration:none; font-weight:bold; font-size:14px; padding:12px 22px; border-radius:6px;">Precisa mudar o horário? Reagende aqui</a>
                </p>
            @endif

            <p>Tem alguma dúvida? Fale com a gente:</p>
            <p>
                WhatsApp: (12) 3600-5110<br>
                E-mail: certificacaovvs@gmail.com
            </p>
        </div>
        <div class="footer">
            Via Vale Sistemas · Rua Leopoldo Macedo, 349, Sala 01, Ponte Alta, Aparecida - SP
        </div>
    </div>
</body>
</html>
