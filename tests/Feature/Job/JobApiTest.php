<?php

namespace Tests\Feature\Job;

use App\Domain\Job\Models\Job;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Tradie $tradie;
    private Tradie $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant     = Tenant::factory()->create();
        $this->tradie     = Tradie::factory()->forTenant($this->tenant)->withBusinessNumber()->create();
        $this->dispatcher = Tradie::factory()->forTenant($this->tenant)->dispatcher()->create();
    }

    /** @test */
    public function tradie_can_list_their_own_jobs(): void
    {
        Job::factory()->assigned($this->tradie)->count(3)->create();

        // Job belonging to a different tradie — should NOT appear
        $otherTradie = Tradie::factory()->forTenant($this->tenant)->create();
        Job::factory()->assigned($otherTradie)->create();

        $response = $this->actingAs($this->tradie)->getJson('/api/jobs');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function dispatcher_can_see_all_tenant_jobs(): void
    {
        $tradie2 = Tradie::factory()->forTenant($this->tenant)->create();

        Job::factory()->assigned($this->tradie)->count(2)->create();
        Job::factory()->assigned($tradie2)->count(3)->create();

        $response = $this->actingAs($this->dispatcher)->getJson('/api/jobs');

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    /** @test */
    public function tradie_can_mark_job_as_in_progress(): void
    {
        $job = Job::factory()->assigned($this->tradie)->create();

        $response = $this->actingAs($this->tradie)
            ->patchJson("/api/jobs/{$job->id}/status", ['status' => 'in_progress']);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('service_jobs', ['id' => $job->id, 'status' => 'in_progress']);
    }

    /** @test */
    public function tradie_cannot_assign_a_job_to_another_tradie(): void
    {
        $job         = Job::factory()->aiCreated()->create(['tenant_id' => $this->tenant->id]);
        $otherTradie = Tradie::factory()->forTenant($this->tenant)->create();

        $response = $this->actingAs($this->tradie)
            ->patchJson("/api/jobs/{$job->id}/assign", ['tradie_id' => $otherTradie->id]);

        $response->assertForbidden();
    }

    /** @test */
    public function dispatcher_can_assign_ai_created_job(): void
    {
        $job = Job::factory()->aiCreated()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->dispatcher)
            ->patchJson("/api/jobs/{$job->id}/assign", ['tradie_id' => $this->tradie->id]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'assigned');
        $response->assertJsonPath('data.tradie_id', $this->tradie->id);
    }

    /** @test */
    public function tradie_cannot_access_another_tenants_jobs(): void
    {
        $otherTenant = Tenant::factory()->create();
        $job         = Job::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->tradie)->getJson("/api/jobs/{$job->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function it_returns_validation_error_for_invalid_status_transition(): void
    {
        $job = Job::factory()->aiCreated()->create(['tenant_id' => $this->tenant->id]);

        // Trying to jump from ai_created directly to completed (invalid)
        $response = $this->actingAs($this->dispatcher)
            ->patchJson("/api/jobs/{$job->id}/status", ['status' => 'completed']);

        $response->assertStatus(422);
    }
}
