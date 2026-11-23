<?php

return [

    /*
    | Where stream-down and other operational alerts go. Developer only; the
    | owner never sees these.
    */
    'alerts_email' => env('ALERTS_EMAIL'),

    /*
    | Written by each deploy (deploy/release.sh): the commit and the time it
    | went live, shown in the footer as "Last update".
    */
    'build_file' => base_path('build.json'),

];
