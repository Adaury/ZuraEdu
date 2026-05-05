<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estudiante>
 */
class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'numero_matricula' => fake()->unique()->numerify('MAT-####'),
            'cedula'           => fake()->unique()->numerify('###-#######-#'),
            'nombres'          => fake()->firstName(),
            'apellidos'        => fake()->lastName(),
            'fecha_nacimiento' => fake()->date('Y-m-d', '-10 years'),
            'sexo'             => fake()->randomElement(['M', 'F']),
            'nacionalidad'     => 'Dominicana',
            'estado'           => 'activo',
        ];
    }
}
