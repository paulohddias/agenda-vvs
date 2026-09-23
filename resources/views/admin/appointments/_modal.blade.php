<div x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     @keydown.escape.window="close()">
    <div @click.outside="close()" x-show="open" x-transition
         class="bg-white rounded-lg shadow-lg max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-start justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900" x-text="appt.holderName"></h3>
            <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Serviço</dt><dd class="font-medium text-gray-900" x-text="appt.product"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Quando</dt><dd class="font-medium text-gray-900"><span x-text="appt.startsAtLabel"></span> – <span x-text="appt.endsAtLabel"></span></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Situação</dt><dd class="font-medium text-gray-900" x-text="appt.statusLabel"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">CPF/CNPJ</dt><dd class="font-medium text-gray-900" x-text="appt.holderDocument"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">E-mail</dt><dd class="font-medium text-gray-900" x-text="appt.holderEmail"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefone</dt><dd class="font-medium text-gray-900" x-text="appt.holderPhone"></dd></div>
            <template x-if="appt.accountantName">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Contador</dt><dd class="font-medium text-gray-900" x-text="appt.accountantName"></dd></div>
            </template>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Validação</dt><dd class="font-medium text-gray-900" x-text="appt.validationMethod"></dd></div>
        </dl>

        <template x-if="appt.notes">
            <div class="mt-3 text-sm">
                <dt class="text-gray-500">Observações</dt>
                <dd class="text-gray-900" x-text="appt.notes"></dd>
            </div>
        </template>

        <template x-if="appt.documentUrl">
            <a :href="appt.documentUrl" class="mt-3 inline-block text-brand-blue hover:underline text-sm">Baixar documento enviado</a>
        </template>

        <div class="mt-5 pt-4 border-t border-gray-100 space-y-4">
            {{-- Abrem o WhatsApp com a mensagem pronta; a equipe só aperta enviar. --}}
            <template x-if="appt.whatsappRemindUrl && (appt.status === 'pending' || appt.status === 'confirmed')">
                <div class="flex flex-wrap gap-2">
                    <a :href="appt.whatsappConfirmUrl" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-md bg-[#25D366] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#1ebe5a]">
                        <x-whatsapp-icon /> Confirmar pelo WhatsApp
                    </a>
                    <a :href="appt.whatsappRemindUrl" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-md border border-[#25D366] px-3 py-1.5 text-sm font-medium text-green-700 hover:bg-green-50">
                        <x-whatsapp-icon /> Lembrar pelo WhatsApp
                    </a>
                </div>
            </template>

            <div class="flex gap-4 flex-wrap">
                <template x-if="appt.status === 'pending'">
                    <form method="POST" :action="appt.updateUrl">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button class="text-sm font-medium text-green-700 hover:underline">Confirmar</button>
                    </form>
                </template>
                <template x-if="appt.status === 'confirmed'">
                    <form method="POST" :action="appt.updateUrl">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button class="text-sm font-medium text-gray-700 hover:underline">Concluir</button>
                    </form>
                </template>
                <template x-if="appt.status === 'pending' || appt.status === 'confirmed'">
                    <form method="POST" :action="appt.updateUrl" onsubmit="return confirm('Cancelar este agendamento?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="text-sm font-medium text-red-600 hover:underline">Cancelar</button>
                    </form>
                </template>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Alterar data e horário</p>
                <form method="POST" :action="appt.rescheduleUrl" class="flex flex-wrap items-end gap-2">
                    @csrf @method('PATCH')
                    <input type="date" name="reschedule_date" :value="appt.rescheduleDate" class="border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm text-sm" required>
                    <input type="time" name="reschedule_time" :value="appt.rescheduleTime" class="border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm text-sm" required>
                    <button class="rounded-md bg-brand-slate px-3 py-1.5 text-white text-sm hover:bg-brand-blue">Reagendar</button>
                </form>
            </div>
        </div>
    </div>
</div>
