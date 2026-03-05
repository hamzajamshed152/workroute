<?php

namespace Database\Factories;

use App\Domain\Job\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'id'               => Str::uuid(),
            'tenant_id'        => TenantFactory::new(),
            'call_id'          => null,
            'tradie_id'        => null,
            'status'           => 'pending',
            'source'           => 'manual',
            'customer_name'    => $this->faker->name(),
            'customer_phone'   => '+614' . $this->faker->numerify('########'),
            'customer_address' => $this->faker->streetAddress() . ', ' . $this->faker->city(),
            'description'      => $this->faker->sentence(10),
            'skill_required'   => $this->faker->randomElement(['plumbing', 'electrical', 'carpentry', 'painting']),
            'notes'            => null,
            'ai_transcript'    => null,
            'scheduled_at'     => null,
            'assigned_at'      => null,
            'completed_at'     => null,
            'cancelled_at'     => null,
            'cancellation_reason' => null,
            'metadata'         => null,
        ];
    }

    public function aiCreated(): static
    {
        return $this->state([
            'status' => 'ai_created',
            'source' => 'ai',
            'ai_transcript' => json_encode([['role' => 'agent', 'content' => 'How can I help?']]),
        ]);
    }

    public function assigned(\App\Domain\Tradie\Models\Tradie $tradie): static
    {
        return $this->state([
            'tradie_id'   => $tradie->id,
            'tenant_id'   => $tradie->tenant_id,
            'status'      => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    public function completed(\App\Domain\Tradie\Models\Tradie $tradie): static
    {
        return $this->state([
            'tradie_id'    => $tradie->id,
            'status'       => 'completed',
            'assigned_at'  => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
