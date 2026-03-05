<?php

namespace App\Http\Controllers\Job;

use App\Domain\Job\Repositories\JobRepositoryInterface;
use App\Domain\Job\Services\JobService;
use App\Http\Requests\Job\AssignJobRequest;
use App\Http\Requests\Job\UpdateJobStatusRequest;
use App\Http\Resources\JobResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class JobController extends Controller
{
    public function __construct(
        private JobRepositoryInterface $jobs,
        private JobService             $jobService,
    ) {}

    /**
     * GET /jobs
     * Returns all pending/ai_created jobs for the authenticated tradie's tenant.
     * Tradies only see their own assigned jobs. Dispatchers/admins see all.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tradie = auth()->user();

        $query = \App\Domain\Job\Models\Job::where('tenant_id', $tradie->tenant_id)
            ->orderByDesc('created_at');

        // Tradies only see jobs assigned to them
        if ($tradie->role === 'tradie') {
            $query->where('tradie_id', $tradie->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return JobResource::collection($query->paginate(20));
    }

    /**
     * GET /jobs/{job}
     */
    public function show(string $jobId): JobResource
    {
        $job = $this->jobs->findById($jobId);
        $this->authorizeJobAccess($job);

        return new JobResource($job);
    }

    /**
     * PATCH /jobs/{job}/status
     * Tradie moves job through lifecycle: in_progress → completed / cancelled.
     */
    public function updateStatus(UpdateJobStatusRequest $request, string $jobId): JobResource
    {
        $job = $this->jobs->findById($jobId);
        $this->authorizeJobAccess($job);

        $job = $this->jobService->transitionStatus(
            $jobId,
            $request->input('status'),
            ['reason' => $request->input('cancellation_reason')],
        );

        return new JobResource($job);
    }

    /**
     * PATCH /jobs/{job}/assign
     * Dispatcher assigns an ai_created or pending job to a tradie.
     */
    public function assign(AssignJobRequest $request, string $jobId): JobResource
    {
        $job = $this->jobs->findById($jobId);

        // Only dispatchers and admins can reassign
        abort_unless(
            in_array(auth()->user()->role, ['admin', 'dispatcher']),
            403,
            'Only dispatchers and admins can assign jobs.'
        );

        $job = $this->jobService->assignToTradie($jobId, $request->input('tradie_id'));

        return new JobResource($job);
    }

    private function authorizeJobAccess(\App\Domain\Job\Models\Job $job): void
    {
        $tradie = auth()->user();

        // Must be same tenant
        abort_unless($job->tenant_id === $tradie->tenant_id, 403);

        // Tradies can only access their own jobs
        if ($tradie->role === 'tradie') {
            abort_unless($job->tradie_id === $tradie->id, 403);
        }
    }
}
