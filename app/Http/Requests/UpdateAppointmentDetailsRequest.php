<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Rules\CpfOuCnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição dos dados do cliente pelo painel. Mesmas regras do formulário público,
 * sem as partes que só fazem sentido para o cliente (termos, captcha, horário).
 */
class UpdateAppointmentDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a rota já passa pelo middleware 'admin'
    }

    public function rules(): array
    {
        return [
            'holder_name' => ['required', 'string', 'max:255'],
            'holder_document' => ['required', 'string', new CpfOuCnpj],
            'holder_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'holder_phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'accountant_name' => ['nullable', 'string', 'max:255'],
            'validation_method' => ['required', Rule::in(array_keys(Appointment::VALIDATION_METHOD_LABELS))],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'holder_name.required' => 'Informe o nome ou razão social.',
            'holder_email.email' => 'Informe um e-mail válido.',
            'holder_phone.regex' => 'Informe um telefone com DDD (10 ou 11 dígitos).',
            'validation_method.required' => 'Escolha a forma de validação.',
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
