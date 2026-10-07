{{-- Marca a página para recarregar sozinha quando os agendamentos mudarem (resources/js/app.js → VVSAutoRefresh).
     A versão atual vai junto para o navegador saber o que já está na tela. --}}
<div hidden data-vvs-auto-refresh
     data-url="{{ route('admin.agenda.version') }}"
     data-version="{{ \App\Models\Appointment::agendaVersion() }}"></div>
