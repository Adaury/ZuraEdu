<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Representante>
 */
class RepresentanteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'cedula'   => fake()->unique()->numerify('###-#######-#'),
            'nombres'  => fake()->firstName(),
            'apellidos'=> fake()->lastName(),
            'telefono' => fake()->phoneNumber(),
            'email'    => fake()->unique()->safeEmail(),
        ];
    }
}
