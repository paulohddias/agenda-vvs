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

/**
 * Aviso no painel quando um cliente agendado está para chegar (Configurações → minutos antes).
 * Só funciona com alguma página do painel aberta: consulta o servidor a cada 30s e mostra
 * um cartão com som (e notificação do sistema, se permitida). Quem já foi avisado fica guardado
 * no navegador, para não repetir a cada consulta nem em outra aba.
 */
const VVSAlerts = {
    STORAGE_KEY: 'vvs-alerted',
    POLL_MS: 30000,

    init() {
        this.container = document.getElementById('vvs-alerts');
        this.template = document.getElementById('vvs-alert-template');
        this.setupNotificationButton();

        if (! this.container || ! this.template) return;

        this.check();
        setInterval(() => this.check(), this.POLL_MS);
    },

    async check() {
        let data;
        try {
            const response = await fetch(this.container.dataset.url, { headers: { Accept: 'application/json' } });
            if (! response.ok) return; // sessão expirou, servidor fora etc. — tenta de novo na próxima
            data = await response.json();
        } catch (e) {
            return;
        }

        const alerted = this.loadAlerted();
        let playedSound = false;

        for (const alert of data.alerts ?? []) {
            if (alerted[alert.key]) continue;

            alerted[alert.key] = Date.now();
            this.show(alert);
            this.notify(alert);
            if (! playedSound) {
                this.beep();
                playedSound = true;
            }
        }

        this.saveAlerted(alerted);
    },

    title(alert) {
        return alert.minutesLeft <= 0
            ? 'Cliente agendado agora'
            : `Cliente em ${alert.minutesLeft} minuto${alert.minutesLeft === 1 ? '' : 's'}`;
    },

    show(alert) {
        const card = this.template.content.firstElementChild.cloneNode(true);
        card.querySelector('[data-field="title"]').textContent = `${this.title(alert)} · ${alert.time}`;
        card.querySelector('[data-field="holderName"]').textContent = alert.holderName;
        card.querySelector('[data-field="details"]').textContent = `${alert.product} · ${alert.validationMethod}`;
        card.querySelector('[data-field="url"]').href = alert.url;
        card.querySelector('[data-action="close"]').addEventListener('click', () => card.remove());
        this.container.prepend(card);
    },

    notify(alert) {
        if (! ('Notification' in window) || Notification.permission !== 'granted') return;

        try {
            const notification = new Notification(`${this.title(alert)} · ${alert.time}`, {
                body: `${alert.holderName}\n${alert.product} · ${alert.validationMethod}`,
                icon: '/images/favicon.png',
                tag: alert.key,
            });
            notification.onclick = () => {
                window.focus();
                window.location.href = alert.url;
            };
        } catch (e) {
            // Alguns navegadores de celular só permitem notificação via service worker; o cartão na tela já basta.
        }
    },

    /** Dois toques curtos gerados na hora, sem arquivo de áudio. */
    beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.3].forEach((offset) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.2, ctx.currentTime + offset);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + offset + 0.25);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + offset);
                osc.stop(ctx.currentTime + offset + 0.25);
            });
        } catch (e) {
            // Sem som (navegador bloqueou áudio antes de qualquer clique na página): o cartão aparece mesmo assim.
        }
    },

    loadAlerted() {
        try {
            const alerted = JSON.parse(localStorage.getItem(this.STORAGE_KEY) || '{}');
            // Limpa avisos de mais de 1 dia para a lista não crescer para sempre.
            const dayAgo = Date.now() - 86400000;
            return Object.fromEntries(Object.entries(alerted).filter(([, at]) => at > dayAgo));
        } catch (e) {
            return {};
        }
    },

    saveAlerted(alerted) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(alerted));
        } catch (e) {
            // Navegação privada etc.: pode repetir o aviso, mas não quebra nada.
        }
    },

    /** Botão em Configurações: pedir permissão precisa partir de um clique. */
    setupNotificationButton() {
        const button = document.querySelector('[data-vvs-enable-notifications]');
        const status = document.querySelector('[data-vvs-notifications-status]');
        if (! button) return;

        const render = () => {
            if (! ('Notification' in window)) {
                button.classList.add('hidden');
                status.textContent = 'Este navegador não suporta notificações; o aviso aparece só na tela do painel.';
            } else if (Notification.permission === 'granted') {
                button.classList.add('hidden');
                status.textContent = 'Notificações ativadas neste navegador.';
            } else if (Notification.permission === 'denied') {
                button.classList.add('hidden');
                status.textContent = 'Notificações bloqueadas neste navegador. Para liberar, clique no cadeado ao lado do endereço do site.';
            }
        };

        button.addEventListener('click', async () => {
            await Notification.requestPermission();
            render();
        });
        render();
    },
};

document.addEventListener('DOMContentLoaded', () => VVSAlerts.init());
