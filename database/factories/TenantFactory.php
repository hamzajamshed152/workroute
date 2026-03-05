<?php

namespace Database\Factories;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'id'                    => Str::uuid(),
            'name'                  => $name,
            'slug'                  => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'stripe_customer_id'    => 'cus_' . Str::random(14),
            'stripe_subscription_id'=> null,
            'subscription_status'   => 'active',
            'subscription_plan'     => 'basic',
            'trial_ends_at'         => null,
            'features'              => ['ai_answering', 'call_forwarding'],
        ];
    }

    public function trialing(): static
    {
        return $this->state(['subscription_status' => 'trialing', 'trial_ends_at' => now()->addDays(14)]);
    }

    public function cancelled(): static
    {
        return $this->state(['subscription_status' => 'cancelled']);
    }
}
