<?php

namespace Tests\Unit\Domain\Job;

use App\Domain\Job\Models\Job;
use App\Domain\Job\Repositories\JobRepositoryInterface;
use App\Domain\Job\Services\JobService;
use Mockery;
use Tests\TestCase;

class JobServiceTest extends TestCase
{
    private JobService $service;
    private JobRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(JobRepositoryInterface::class);
        $this->service    = new JobService($this->repository);
    }

    /** @test */
    public function it_transitions_assigned_job_to_in_progress(): void
    {
        $job = $this->makeJob('assigned');

        $this->repository->shouldReceive('findById')->with($job->id)->andReturn($job);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->transitionStatus($job->id, 'in_progress');

        $this->assertEquals('in_progress', $result->status);
    }

    /** @test */
    public function it_throws_on_invalid_state_transition(): void
    {
        $job = $this->makeJob('completed');

        $this->repository->shouldReceive('findById')->with($job->id)->andReturn($job);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition job from [completed] to [assigned]');

        $this->service->transitionStatus($job->id, 'assigned');
    }

    /** @test */
    public function it_sets_completed_at_when_job_is_completed(): void
    {
        $job = $this->makeJob('in_progress');

        $this->repository->shouldReceive('findById')->andReturn($job);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->transitionStatus($job->id, 'completed');

        $this->assertNotNull($result->completed_at);
        $this->assertEquals('completed', $result->status);
    }

    /** @test */
    public function it_sets_cancellation_reason_when_cancelled(): void
    {
        $job = $this->makeJob('assigned');

        $this->repository->shouldReceive('findById')->andReturn($job);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->transitionStatus($job->id, 'cancelled', ['reason' => 'Customer no longer needs service']);

        $this->assertEquals('cancelled', $result->status);
        $this->assertEquals('Customer no longer needs service', $result->cancellation_reason);
    }

    /** @test */
    public function it_assigns_pending_job_to_tradie(): void
    {
        $job = $this->makeJob('pending');

        $this->repository->shouldReceive('findById')->andReturn($job);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->assignToTradie($job->id, 'tradie-uuid-123');

        $this->assertEquals('assigned', $result->status);
        $this->assertEquals('tradie-uuid-123', $result->tradie_id);
        $this->assertNotNull($result->assigned_at);
    }

    /** @test */
    public function it_assigns_ai_created_job_to_tradie(): void
    {
        $job = $this->makeJob('ai_created');

        $this->repository->shouldReceive('findById')->andReturn($job);
        $this->repository->shouldReceive('save')->once();

        $result = $this->service->assignToTradie($job->id, 'tradie-uuid-456');

        $this->assertEquals('assigned', $result->status);
    }

    /** @test */
    public function it_cannot_assign_already_completed_job(): void
    {
        $job = $this->makeJob('completed');

        $this->repository->shouldReceive('findById')->andReturn($job);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->assignToTradie($job->id, 'tradie-uuid-789');
    }

    private function makeJob(string $status): Job
    {
        $job = new Job([
            'id'        => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => \Illuminate\Support\Str::uuid()->toString(),
            'status'    => $status,
            'source'    => 'manual',
        ]);

        // Set the key manually since we're not using the DB
        $job->exists = true;

        return $job;
    }
}
