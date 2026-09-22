import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/**
 * Máscaras simples de CPF/CNPJ e telefone, sem depender de biblioteca externa.
 * Uso: oninput="VVS.maskDocument(this)" / oninput="VVS.maskPhone(this)".
 */
window.VVS = {
    maskDocument(input) {
        let digits = input.value.replace(/\D/g, '').slice(0, 14);

        if (digits.length <= 11) {
            // CPF: 000.000.000-00
            digits = digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // CNPJ: 00.000.000/0000-00
            digits = digits
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        }

        input.value = digits;

        // Volta a digitar depois de um erro: tira o aviso até o próximo blur validar de novo.
        this.clearDocumentError(input);
    },

    isValidCpf(cpf) {
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }

        for (let pos = 9; pos <= 10; pos++) {
            let sum = 0;
            for (let i = 0; i < pos; i++) {
                sum += parseInt(cpf[i], 10) * (pos + 1 - i);
            }
            let digit = (sum * 10) % 11;
            if (digit === 10) digit = 0;
            if (digit !== parseInt(cpf[pos], 10)) return false;
        }

        return true;
    },

    isValidCnpj(cnpj) {
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return false;
        }

        const checkDigit = (length) => {
            const weights = [];
            let factor = length - 7;
            for (let i = 0; i < length; i++) {
                weights.push(factor);
                factor = factor - 1 < 2 ? 9 : factor - 1;
            }
            let sum = 0;
            for (let i = 0; i < length; i++) {
                sum += parseInt(cnpj[i], 10) * weights[i];
            }
            const digit = sum % 11;
            return digit < 2 ? 0 : 11 - digit;
        };

        return checkDigit(12) === parseInt(cnpj[12], 10) && checkDigit(13) === parseInt(cnpj[13], 10);
    },

    isValidDocument(digits) {
        if (digits.length === 11) return this.isValidCpf(digits);
        if (digits.length === 14) return this.isValidCnpj(digits);

        return false;
    },

    clearDocumentError(input) {
        input.setCustomValidity('');
        input.classList.remove('border-red-400', 'focus:border-red-500', 'focus:ring-red-500');

        const errorEl = document.getElementById(input.id + '-client-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
    },

    /** Confere o dígito verificador de verdade, ao sair do campo (blur) — não só a máscara. */
    validateDocument(input) {
        const digits = input.value.replace(/\D/g, '');

        if (digits === '') {
            this.clearDocumentError(input);
            return;
        }

        if (this.isValidDocument(digits)) {
            this.clearDocumentError(input);
            this.fillFromPreviousBooking(digits);
            return;
        }

        const message = digits.length === 11 || digits.length === 14
            ? 'CPF ou CNPJ inválido.'
            : 'Informe os 11 dígitos do CPF ou os 14 do CNPJ.';

        input.setCustomValidity(message);
        input.classList.add('border-red-400', 'focus:border-red-500', 'focus:ring-red-500');

        const errorEl = document.getElementById(input.id + '-client-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    },

    /**
     * Se esse CPF/CNPJ já tiver um agendamento anterior, preenche nome, e-mail e contador —
     * só nos campos que ainda estiverem vazios, pra nunca sobrescrever o que a pessoa já digitou.
     */
    async fillFromPreviousBooking(digits) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (! csrfToken) return;

        let data;
        try {
            const response = await fetch('/agendar/consulta-documento', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ holder_document: digits }),
            });
            if (! response.ok) return;
            data = await response.json();
        } catch (e) {
            return; // comodidade, não trava o preenchimento se falhar
        }

        if (! data.found) return;

        const nameInput = document.getElementById('holder_name');
        const emailInput = document.getElementById('holder_email');
        const phoneInput = document.getElementById('holder_phone');
        const accountantInput = document.getElementById('accountant_name');

        if (nameInput && ! nameInput.value) nameInput.value = data.holder_name ?? '';
        if (emailInput && ! emailInput.value) emailInput.value = data.holder_email ?? '';
        if (accountantInput && ! accountantInput.value && data.accountant_name) {
            accountantInput.value = data.accountant_name;
        }
        if (phoneInput && ! phoneInput.value && data.holder_phone) {
            phoneInput.value = data.holder_phone;
            this.maskPhone(phoneInput); // veio só com dígitos do banco, aqui ganha a formatação
        }
    },

    maskPhone(input) {
        let digits = input.value.replace(/\D/g, '').slice(0, 11);

        if (digits.length > 10) {
            // Celular: (00) 00000-0000
            digits = digits.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        } else {
            // Fixo: (00) 0000-0000
            digits = digits.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        }

        input.value = digits.trim().replace(/-$/, '');
    },
};
