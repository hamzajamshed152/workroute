<?php

namespace Database\Factories;

use App\Domain\Call\Models\Call;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CallFactory extends Factory
{
    protected $model = Call::class;

    public function definition(): array
    {
        return [
            'id'              => Str::uuid(),
            'tenant_id'       => TenantFactory::new(),
            'tradie_id'       => null,
            'twilio_call_sid' => 'CA' . Str::random(32),
            'caller_number'   => '+614' . $this->faker->numerify('########'),
            'called_number'   => '+612' . $this->faker->numerify('########'),
            'status'          => 'initiated',
            'direction'       => 'inbound',
            'forwarded_to'    => null,
            'forward_status'  => null,
            'ai_session_id'   => null,
            'duration_seconds'=> 0,
            'recording_url'   => null,
            'metadata'        => null,
            'started_at'      => now(),
            'ended_at'        => null,
        ];
    }

    public function forwarded(Tradie $tradie): static
    {
        return $this->state([
            'tradie_id'     => $tradie->id,
            'status'        => 'forwarded',
            'forwarded_to'  => $tradie->personal_phone,
        ]);
    }

    public function aiHandled(): static
    {
        return $this->state([
            'status'        => 'ai_handling',
            'ai_session_id' => 'retell_' . Str::random(20),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'          => 'completed',
            'duration_seconds'=> $this->faker->numberBetween(30, 600),
            'ended_at'        => now()->addMinutes(5),
        ]);
    }
}
