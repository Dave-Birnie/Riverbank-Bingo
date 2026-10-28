<?php

return [

    /*
    | Which of the two approved designs the public site wears: "classic" or
    | "black-gold". Same layout, different colours and type.
    */
    'theme' => env('SITE_THEME', 'classic'),

    /*
    | Where stream-down and other operational alerts go. Developer only; the
    | owner never sees these.
    */
    'alerts_email' => env('ALERTS_EMAIL'),

];
