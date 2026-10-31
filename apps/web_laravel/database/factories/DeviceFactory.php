<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return ['branch_id' => Branch::factory(), 'tenant_id' => fn (array $attributes) => Branch::findOrFail($attributes['branch_id'])->tenant_id, 'name' => 'Terminal '.fake()->unique()->numerify('####'), 'token_hash' => hash('sha256', Str::random(80)), 'revoked_at' => null];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
