<?php

// The rules the public contact form uses; anything else falls back to English.
return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse courriel valide.',
    'in' => 'La valeur choisie pour :attribute n\'est pas valide.',
    'string' => 'Le champ :attribute doit être du texte.',
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'courriel',
        'phone' => 'téléphone',
        'message' => 'message',
        'topic' => 'sujet',
    ],
];
