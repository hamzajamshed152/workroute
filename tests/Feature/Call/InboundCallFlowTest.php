<?php

namespace Tests\Feature\Call;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\AI\DTOs\RetellCallResponse;
use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\DTOs\TwimlResponse;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InboundCallFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tradie $tradie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tradie = Tradie::factory()->create([
            'business_number' => '+61298765432',
            'personal_phone'  => '+61412345678',
            'retell_agent_id' => 'agent_test_123',
            'is_available'    => true,
        ]);
    }

    /** @test */
    public function it_forwards_call_to_tradie_when_available(): void
    {
        // Arrange — mock Twilio to skip real HTTP and signature validation
        $this->mockCallProvider(
            forwardXml: '<Response><Dial><Number>+61412345678</Number></Dial></Response>',
        );

        // Act
        $response = $this->postJson('/api/webhooks/call/inbound', [
            'CallSid'    => 'CA' . str_repeat('a', 32),
            'From'       => '+61400000000',
            'To'         => '+61298765432',
            'CallStatus' => 'ringing',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');

        $this->assertDatabaseHas('service_jobs', [
            'tradie_id' => $this->tradie->id,
            'status'    => 'assigned',
            'source'    => 'forwarded',
        ]);

        $this->assertDatabaseHas('calls', [
            'status' => 'forwarded',
        ]);
    }

    /** @test */
    public function it_routes_to_ai_when_tradie_is_unavailable(): void
    {
        // Arrange
        $this->tradie->update(['is_available' => false]);

        $this->mockCallProvider();
        $this->mockAIProvider();

        // Act
        $response = $this->postJson('/api/webhooks/call/inbound', [
            'CallSid'    => 'CB' . str_repeat('b', 32),
            'From'       => '+61400000001',
            'To'         => '+61298765432',
            'CallStatus' => 'ringing',
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('calls', [
            'status' => 'ai_handling',
        ]);

        // No job created yet — job is created after Retell webhook fires
        $this->assertDatabaseCount('service_jobs', 0);
    }

    /** @test */
    public function it_routes_to_ai_when_tradie_doesnt_answer(): void
    {
        // Arrange — simulate a forwarded call that was not answered
        $this->mockCallProvider();
        $this->mockAIProvider();

        $call = \App\Domain\Call\Models\Call::factory()->create([
            'tradie_id'       => $this->tradie->id,
            'twilio_call_sid' => 'CC' . str_repeat('c', 32),
            'status'          => 'forwarded',
        ]);

        // Act — Twilio fires the statusCallback with DialCallStatus=no-answer
        $response = $this->postJson("/api/webhooks/call/status/{$call->id}", [
            'CallSid'        => $call->twilio_call_sid,
            'DialCallStatus' => 'no-answer',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('calls', [
            'id'     => $call->id,
            'status' => 'ai_handling',
        ]);
    }

    /** @test */
    public function it_creates_job_from_retell_post_call_analysis(): void
    {
        $this->mockAIProvider();

        $call = \App\Domain\Call\Models\Call::factory()->create([
            'tradie_id'     => $this->tradie->id,
            'ai_session_id' => 'retell_call_xyz',
            'status'        => 'ai_handling',
        ]);

        $response = $this->postJson('/api/webhooks/retell/events', [
            'event' => 'call_analyzed',
            'call'  => [
                'call_id'      => 'retell_call_xyz',
                'duration_ms'  => 180000,
                'call_analysis'=> [
                    'custom_analysis_data' => [
                        'customer_name'    => 'Jane Smith',
                        'customer_address' => '42 Main St, Sydney',
                        'job_description'  => 'Leaking tap in kitchen',
                        'skill_required'   => 'plumbing',
                        'preferred_time'   => 'Monday morning',
                    ],
                ],
                'transcript_object' => [],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'job_created']);

        $this->assertDatabaseHas('service_jobs', [
            'customer_name'   => 'Jane Smith',
            'customer_address'=> '42 Main St, Sydney',
            'skill_required'  => 'plumbing',
            'status'          => 'ai_created',
            'source'          => 'ai',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockCallProvider(string $forwardXml = '<Response></Response>'): void
    {
        $mock = Mockery::mock(CallProviderInterface::class);

        $mock->shouldReceive('validateWebhookSignature')->andReturn(null);
        $mock->shouldReceive('parseIncoming')->andReturnUsing(
            fn ($request) => IncomingCallData::fromTwilioRequest($request)
        );
        $mock->shouldReceive('buildForwardResponse')->andReturn(
            new TwimlResponse($forwardXml)
        );
        $mock->shouldReceive('buildAIHandoffResponse')->andReturn(
            new TwimlResponse('<Response><Connect><Stream url="wss://test"/></Connect></Response>')
        );
        $mock->shouldReceive('buildNoAnswerFallback')->andReturn(
            new TwimlResponse('<Response><Redirect>/ai</Redirect></Response>')
        );

        $this->app->instance(CallProviderInterface::class, $mock);
    }

    private function mockAIProvider(): void
    {
        $mock = Mockery::mock(AIProviderInterface::class);

        $mock->shouldReceive('validateWebhookSignature')->andReturn(null);
        $mock->shouldReceive('registerCall')->andReturn(
            new RetellCallResponse('retell_call_xyz', 'wss://retell.test/socket')
        );

        $this->app->instance(AIProviderInterface::class, $mock);
    }
}
