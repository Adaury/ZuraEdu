<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Docente>
 */
class DocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'cedula'          => fake()->unique()->numerify('###-#######-#'),
            'nombres'         => fake()->firstName(),
            'apellidos'       => fake()->lastName(),
            'fecha_nacimiento'=> fake()->date('Y-m-d', '-25 years'),
            'sexo'            => fake()->randomElement(['M', 'F']),
            'telefono'        => fake()->phoneNumber(),
            'email'           => fake()->unique()->safeEmail(),
            'estado'          => 'activo',
            'area'            => 'academica',
        ];
    }
}
