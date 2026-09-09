<?php

namespace Database\Factories;

use App\Models\RequestType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestType>
 */
class RequestTypeFactory extends Factory
{
    protected $model = RequestType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
