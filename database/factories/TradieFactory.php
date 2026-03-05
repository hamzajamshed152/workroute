<?php

namespace Database\Factories;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TradieFactory extends Factory
{
    protected $model = Tradie::class;

    public function definition(): array
    {
        return [
            'id'              => Str::uuid(),
            'tenant_id'       => TenantFactory::new(),
            'name'            => $this->faker->name(),
            'email'           => $this->faker->unique()->safeEmail(),
            'password'        => Hash::make('password'),
            'personal_phone'  => '+614' . $this->faker->numerify('########'),
            'business_number' => null,
            'twilio_number_sid'=> null,
            'retell_agent_id' => null,
            'is_available'    => true,
            'role'            => 'tradie',
            'skills'          => ['plumbing'],
            'timezone'        => 'Australia/Sydney',
            'notification_preferences' => ['sms' => true, 'email' => true],
        ];
    }

    public function withBusinessNumber(): static
    {
        return $this->state([
            'business_number'  => '+612' . $this->faker->numerify('########'),
            'twilio_number_sid'=> 'PN' . Str::random(32),
            'retell_agent_id'  => 'agent_' . Str::random(20),
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function dispatcher(): static
    {
        return $this->state(['role' => 'dispatcher']);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }
}
