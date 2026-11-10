<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\CustomerReview;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerReview>
 */
class CustomerReviewFactory extends Factory
{
    protected $model = CustomerReview::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'queue_ticket_id' => QueueTicket::factory(),
            'service_id' => Service::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}

