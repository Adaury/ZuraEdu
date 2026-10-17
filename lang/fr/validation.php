<?php

// Mensajes de validación (parcial: lo que falte cae en el inglés del framework).
return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'unique' => 'La valeur de :attribute est déjà utilisée.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'string' => 'Le champ :attribute doit être du texte.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'array' => 'Le champ :attribute doit être une liste.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'image' => 'Le champ :attribute doit être une image.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'url' => 'Le champ :attribute doit être une URL valide.',
    'regex' => 'Le format de :attribute est invalide.',
    'after_or_equal' => 'Le champ :attribute doit être une date égale ou postérieure au :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date égale ou antérieure au :date.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'required_if' => 'Le champ :attribute est obligatoire lorsque :other vaut :value.',
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :min Ko.',
    ],
    'max' => [
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
    ],
    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'uploaded' => 'Le téléversement de :attribute a échoué.',
];
