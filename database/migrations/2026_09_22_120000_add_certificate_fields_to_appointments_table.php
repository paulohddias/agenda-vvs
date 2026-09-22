<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Dados de quem vai receber o certificado — podem ser diferentes de quem tem a conta
            // (ex.: um contador agendando para o cliente dele).
            $table->string('holder_name')->nullable()->after('staff_id');
            $table->string('holder_document', 14)->nullable()->after('holder_name'); // CPF/CNPJ, só dígitos
            $table->string('holder_email')->nullable()->after('holder_document');
            $table->string('holder_phone', 11)->nullable()->after('holder_email'); // só dígitos
            $table->string('accountant_name')->nullable()->after('holder_phone');
            $table->string('validation_method', 20)->nullable()->after('accountant_name'); // presencial, videoconferencia
            $table->timestamp('terms_accepted_at')->nullable()->after('validation_method');
            $table->string('document_path')->nullable()->after('terms_accepted_at'); // CNH, quando enviada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'holder_name', 'holder_document', 'holder_email', 'holder_phone',
                'accountant_name', 'validation_method', 'terms_accepted_at', 'document_path',
            ]);
        });
    }
};
