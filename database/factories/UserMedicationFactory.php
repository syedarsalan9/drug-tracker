<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMedicationFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'rxcui' => $this->faker->numerify('######'),
            'drug_name' => $this->faker->word . ' ' . $this->faker->numberBetween(10, 500) . 'mg',
            'base_names' => [$this->faker->word],
            'dosage_forms' => ['Tablet']
        ];
    }
}