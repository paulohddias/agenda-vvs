<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Rules\CpfOuCnpj;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('active', true)],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'holder_name' => ['required', 'string', 'max:255'],
            'holder_document' => ['required', 'string', new CpfOuCnpj],
            // Verificação real (link de confirmação) fica para a etapa de e-mails.
            'holder_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'holder_phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'accountant_name' => ['nullable', 'string', 'max:255'],
            'validation_method' => ['required', Rule::in(array_keys(Appointment::VALIDATION_METHOD_LABELS))],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'terms' => ['accepted'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Sem chave configurada (dev local) ou em teste automatizado, a verificação real é pulada.
            'g-recaptcha-response' => app()->environment('testing') || ! config('recaptcha.secret_key')
                ? ['nullable']
                : ['required', new Recaptcha],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Escolha o serviço.',
            'product_id.exists' => 'Esse serviço não está mais disponível.',
            'holder_name.required' => 'Informe o nome ou razão social.',
            'holder_email.email' => 'Informe um e-mail válido.',
            'holder_phone.regex' => 'Informe um telefone com DDD (10 ou 11 dígitos).',
            'validation_method.required' => 'Escolha a forma de validação.',
            'terms.accepted' => 'É necessário aceitar os termos para continuar.',
            'document.mimes' => 'Envie o documento em JPG, PNG ou PDF.',
            'document.max' => 'O arquivo deve ter até 8 MB.',
        ];
    }

    /** Só dígitos, para gravar limpo no banco. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'holder_document' => preg_replace('/\D/', '', (string) $this->input('holder_document')),
            'holder_phone' => preg_replace('/\D/', '', (string) $this->input('holder_phone')),
        ]);
    }
}
