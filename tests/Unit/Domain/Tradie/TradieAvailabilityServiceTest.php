<?php

namespace Tests\Unit\Domain\Tradie;

use App\Domain\Tradie\Models\Tradie;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use App\Domain\Tradie\Services\TradieAvailabilityService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TradieAvailabilityServiceTest extends TestCase
{
    private TradieAvailabilityService $service;
    private TradieRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TradieRepositoryInterface::class);
        $this->service    = new TradieAvailabilityService($this->repository);
    }

    /** @test */
    public function it_returns_null_when_no_tradie_is_available(): void
    {
        $this->repository->shouldReceive('findAvailable')
            ->with('tenant-123')
            ->andReturn(null);

        $result = $this->service->claimAvailableTradie('tenant-123');

        $this->assertNull($result);
    }

    /** @test */
    public function it_marks_tradie_unavailable_when_claimed(): void
    {
        $tradie = $this->makeAvailableTradie();

        $this->repository->shouldReceive('findAvailable')->andReturn($tradie);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->claimAvailableTradie($tradie->tenant_id);

        $this->assertNotNull($result);
        $this->assertFalse($result->is_available);
    }

    /** @test */
    public function it_sets_tradie_available_on_release(): void
    {
        $tradie = $this->makeAvailableTradie();
        $tradie->is_available = false;

        $this->repository->shouldReceive('findById')->andReturn($tradie);
        $this->repository->shouldReceive('save')->once();

        $this->service->releaseTradie($tradie->id);

        $this->assertTrue($tradie->is_available);
    }

    private function makeAvailableTradie(): Tradie
    {
        $tradie = new Tradie([
            'tenant_id'      => 'tenant-uuid-abc',
            'is_available'   => true,
            'personal_phone' => '+61412345678',
        ]);

        $tradie->id     = \Illuminate\Support\Str::uuid()->toString();
        $tradie->exists = true;

        return $tradie;
    }
}
