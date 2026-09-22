<?php

return [

    /*
    | Intervalo, em minutos, entre um horário oferecido e o próximo.
    | Ex.: 30 => 09:00, 09:30, 10:00...
    */
    'slot_step_minutes' => 30,

    /* Antecedência mínima, em minutos, para o cliente marcar (evita marcar "para daqui a 5 minutos"). */
    'min_notice_minutes' => 60,

    /* Quantos dias à frente o cliente consegue agendar. */
    'max_days_ahead' => 30,

    /* O cliente só pode cancelar sozinho até X horas antes do horário marcado. */
    'cancel_min_hours' => 2,

];
