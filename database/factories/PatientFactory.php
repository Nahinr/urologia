<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{   
    protected $model = Patient::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         $dni = sprintf('%04d-%04d-%05d', fake()->numberBetween(0,9999), fake()->numberBetween(0,9999), fake()->numberBetween(0,99999));
       
         return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dni' => $dni,
            'sex' => fake()->randomElement(['M','F','Other']),
            'birth_date' => fake()->optional(0.2)->dateTimeBetween('-90 years', '-1 years')?->format('Y-m-d'),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'occupation' => fake()->optional()->jobTitle(),
        ];
    }
}
