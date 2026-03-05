<?php

namespace Tests\Unit\Domain\Call;

use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\Services\CallRoutingService;
use App\Domain\Tradie\Models\Tradie;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use App\Domain\Tradie\Services\TradieAvailabilityService;
use Mockery;
use Tests\TestCase;

class CallRoutingServiceTest extends TestCase
{
    private CallRoutingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider     = Mockery::mock(CallProviderInterface::class);
        $this->tradies      = Mockery::mock(TradieRepositoryInterface::class);
        $this->availability = Mockery::mock(TradieAvailabilityService::class);

        $this->service = new CallRoutingService(
            $this->provider,
            $this->tradies,
            $this->availability,
        );
    }

    /** @test */
    public function it_forwards_to_tradie_when_available(): void
    {
        $tradie = $this->makeTradie(available: true);

        $this->tradies->shouldReceive('findByBusinessNumber')
            ->with('+61298765432')
            ->andReturn($tradie);

        $this->availability->shouldReceive('claimAvailableTradie')
            ->with($tradie->tenant_id)
            ->andReturn($tradie);

        $result = $this->service->routeIncomingCall($this->makeCallData());

        $this->assertTrue($result->shouldForward);
        $this->assertEquals($tradie->id, $result->tradieId);
        $this->assertEquals('+61412000001', $result->tradiePersonalPhone);
    }

    /** @test */
    public function it_hands_to_ai_when_no_tradie_is_available(): void
    {
        $tradie = $this->makeTradie(available: false);

        $this->tradies->shouldReceive('findByBusinessNumber')->andReturn($tradie);
        $this->availability->shouldReceive('claimAvailableTradie')->andReturn(null);

        $result = $this->service->routeIncomingCall($this->makeCallData());

        $this->assertFalse($result->shouldForward);
        $this->assertEquals('tradie_unavailable', $result->reason);
    }

    /** @test */
    public function it_hands_to_ai_when_number_is_unrecognised(): void
    {
        $this->tradies->shouldReceive('findByBusinessNumber')->andReturn(null);

        $result = $this->service->routeIncomingCall($this->makeCallData());

        $this->assertFalse($result->shouldForward);
        $this->assertEquals('unknown_number', $result->reason);
    }

    private function makeCallData(): IncomingCallData
    {
        return new IncomingCallData(
            callSid:      'CA' . str_repeat('x', 32),
            callerNumber: '+61400000000',
            calledNumber: '+61298765432',
            callStatus:   'ringing',
        );
    }

    private function makeTradie(bool $available): Tradie
    {
        $tradie = new Tradie([
            'tenant_id'      => 'tenant-uuid',
            'personal_phone' => '+61412000001',
            'is_available'   => $available,
        ]);

        $tradie->id     = \Illuminate\Support\Str::uuid()->toString();
        $tradie->exists = true;

        return $tradie;
    }
}
