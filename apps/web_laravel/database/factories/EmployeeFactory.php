<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return ['branch_id' => Branch::factory(), 'tenant_id' => fn (array $attributes) => Branch::findOrFail($attributes['branch_id'])->tenant_id, 'name' => fake()->name(), 'internal_code' => fake()->unique()->bothify('EMP-########'), 'job' => fake()->jobTitle(), 'active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
