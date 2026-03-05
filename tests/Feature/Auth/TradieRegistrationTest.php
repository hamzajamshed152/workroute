<?php

namespace Tests\Feature\Auth;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\PurchaseNumberResult;
use App\Domain\Call\DTOs\TwimlResponse;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TradieRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_a_tradie_provisions_number_and_ai_agent(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();

        $this->mockCallProvider();
        $this->mockAIProvider();

        // Act
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'John Smith',
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'personal_phone'        => '+61412345678',
            'skills'                => ['plumbing', 'gas fitting'],
            'timezone'              => 'Australia/Sydney',
            'area_code'             => '02',
            'tenant_id'             => $tenant->id,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'tradie' => ['id', 'name', 'email', 'business_number'],
            'token',
        ]);

        // Business number was assigned
        $this->assertNotNull($response->json('tradie.business_number'));

        // Tradie record persisted correctly
        $this->assertDatabaseHas('tradies', [
            'email'           => 'john@example.com',
            'business_number' => '+61298765432',
            'retell_agent_id' => 'agent_test_abc',
        ]);
    }

    /** @test */
    public function it_rejects_registration_with_invalid_phone_format(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'John Smith',
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'personal_phone'        => '0412345678',  // Missing + prefix
            'tenant_id'             => $tenant->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['personal_phone']);
    }

    /** @test */
    public function it_rejects_duplicate_email(): void
    {
        $tenant = Tenant::factory()->create();

        \App\Domain\Tradie\Models\Tradie::factory()->forTenant($tenant)->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Another Person',
            'email'                 => 'existing@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'personal_phone'        => '+61412999999',
            'tenant_id'             => $tenant->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    private function mockCallProvider(): void
    {
        $mock = Mockery::mock(CallProviderInterface::class);

        $mock->shouldReceive('purchaseNumber')->andReturn(
            new PurchaseNumberResult('+61298765432', 'PN' . str_repeat('a', 32), 'Tradie: John Smith')
        );
        $mock->shouldReceive('configureNumberWebhooks')->andReturn(null);

        // Add other methods that may be called
        $mock->shouldReceive('validateWebhookSignature')->andReturn(null);

        $this->app->instance(CallProviderInterface::class, $mock);
    }

    private function mockAIProvider(): void
    {
        $mock = Mockery::mock(AIProviderInterface::class);

        $mock->shouldReceive('createAgent')->andReturn('agent_test_abc');

        $this->app->instance(AIProviderInterface::class, $mock);
    }
}
