<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Staff;
use App\Services\AvailabilityService;
use App\Services\BookingCalendar;
use App\Services\ReceitaWsService;
use App\Support\DocumentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Agendamento público numa página só: escolher produto, dia, horário e preencher
 * os dados, tudo em '/'. Não exige login — login é só para o painel em /admin.
 */
class BookingController extends Controller
{
    public function __construct(
        private AvailabilityService $availability,
        private BookingCalendar $calendar,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $products = Product::active()->orderBy('name')->get();

        if ($products->isEmpty()) {
            return view('booking.index', ['products' => $products, 'product' => null]);
        }

        $requestedProduct = $request->integer('product');
        $product = $products->firstWhere('id', $requestedProduct) ?? $products->first();

        return view('booking.index', [
            'products' => $products,
            'product' => $product,
        ] + $this->calendar->build($product, Staff::primary(), $request->query('date'), $request->query('month')));
    }

    /**
     * Se esse CPF/CNPJ já agendou antes, devolve nome, e-mail, telefone e contador do
     * agendamento mais recente, para o formulário se preencher sozinho. Se nunca agendou
     * e for CNPJ, devolve só a razão social consultada na ReceitaWS.
     */
    public function lookupByDocument(Request $request, ReceitaWsService $receitaWs): JsonResponse
    {
        $digits = preg_replace('/\D/', '', (string) $request->input('holder_document'));

        if (! DocumentValidator::isValid($digits)) {
            return response()->json(['found' => false]);
        }

        $appointment = Appointment::where('holder_document', $digits)
            ->latest('id') // não usa created_at: dois registros no mesmo segundo empatariam
            ->first(['holder_name', 'holder_email', 'holder_phone', 'accountant_name']);

        if (! $appointment) {
            $companyName = $receitaWs->companyName($digits);

            return response()->json($companyName
                ? ['found' => true, 'holder_name' => $companyName]
                : ['found' => false]);
        }

        return response()->json([
            'found' => true,
            'holder_name' => $appointment->holder_name,
            'holder_email' => $appointment->holder_email,
            'holder_phone' => $appointment->holder_phone,
            'accountant_name' => $appointment->accountant_name,
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::query()->where('active', true)->findOrFail($data['product_id']);
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['starts_at']);

        // Guardado fora da transação: se o horário já tiver sido ocupado, removemos o arquivo abaixo.
        $documentPath = $request->file('document')?->store('appointment-documents', 'local');

        $appointment = DB::transaction(function () use ($product, $start, $data, $documentPath) {
            // Trava o atendente: pedidos simultâneos para o mesmo horário passam um por vez,
            // e o segundo já enxerga o agendamento do primeiro na checagem abaixo.
            $staff = Staff::query()->whereKey(Staff::primary()->id)->lockForUpdate()->firstOrFail();

            if (! $this->availability->isAvailable($product, $staff, $start)) {
                return null;
            }

            return Appointment::create([
                'product_id' => $product->id,
                'staff_id' => $staff->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($product->duration_minutes),
                'status' => Appointment::STATUS_CONFIRMED, // já reservado, sem esperar a equipe confirmar
                'notes' => $data['notes'] ?? null,
                'holder_name' => $data['holder_name'],
                'holder_document' => $data['holder_document'],
                'holder_email' => $data['holder_email'],
                'holder_phone' => $data['holder_phone'],
                'accountant_name' => $data['accountant_name'] ?? null,
                'validation_method' => $data['validation_method'],
                'terms_accepted_at' => now(),
                'document_path' => $documentPath,
            ]);
        });

        if (! $appointment) {
            if ($documentPath) {
                Storage::disk('local')->delete($documentPath);
            }

            return redirect()->route('home', ['product' => $product->id, 'date' => $start->toDateString()])
                ->with('error', 'Esse horário não está mais disponível. Escolha outro.');
        }

        $this->sendConfirmationEmail($appointment);

        return redirect()->route('home')
            ->with('status', 'Agendamento confirmado para '.$start->translatedFormat('d/m/Y \à\s H:i').'.');
    }

    /**
     * Um problema no envio (SMTP fora do ar, por exemplo) não pode derrubar o agendamento,
     * que já está salvo — só registramos no log para dar pra investigar depois.
     */
    private function sendConfirmationEmail(Appointment $appointment): void
    {
        try {
            $appointment->loadMissing('product');
            Mail::to($appointment->holder_email)->send(new AppointmentRequested($appointment));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de confirmação do agendamento #'.$appointment->id.': '.$e->getMessage());
        }
    }
}
