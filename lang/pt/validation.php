<?php

// Mensajes de validación (parcial: lo que falte cae en el inglés del framework).
return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'unique' => 'O valor de :attribute já está em uso.',
    'exists' => 'O valor selecionado em :attribute é inválido.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'string' => 'O campo :attribute deve ser texto.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'image' => 'O campo :attribute deve ser uma imagem.',
    'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'url' => 'O campo :attribute deve ser uma URL válida.',
    'regex' => 'O formato de :attribute é inválido.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
        'array' => 'O campo :attribute deve ter no mínimo :min itens.',
        'file' => 'O arquivo :attribute deve ter no mínimo :min KB.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não deve ser maior que :max.',
        'string' => 'O campo :attribute não deve ter mais de :max caracteres.',
        'array' => 'O campo :attribute não deve ter mais de :max itens.',
        'file' => 'O arquivo :attribute não deve ter mais de :max KB.',
    ],
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'uploaded' => 'Falha ao enviar o arquivo :attribute.',
];
