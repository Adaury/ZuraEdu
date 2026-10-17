<?php

// Mensajes de validación (parcial: lo que falte cae en el inglés del framework).
return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'unique' => 'El valor de :attribute ya está en uso.',
    'exists' => 'El valor seleccionado en :attribute no es válido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'string' => 'El campo :attribute debe ser texto.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'array' => 'El campo :attribute debe ser una lista.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'regex' => 'El formato de :attribute no es válido.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El archivo :attribute debe pesar al menos :min KB.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El archivo :attribute no debe pesar más de :max KB.',
    ],
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'uploaded' => 'No se pudo subir el archivo :attribute.',
];
