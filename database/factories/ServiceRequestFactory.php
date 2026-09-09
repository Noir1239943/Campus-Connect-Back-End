<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'office_id' => Office::factory(),
            'request_type_id' => RequestType::factory(),
            'subject' => fake()->sentence(4),
            'details' => fake()->paragraph(),
            'attachment_path' => null,
            'status' => 'pending',
        ];
    }
}
