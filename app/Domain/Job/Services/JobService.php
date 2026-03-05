<?php

namespace App\Domain\Job\Services;

use App\Domain\AI\DTOs\ExtractedJobDetails;
use App\Domain\Job\Events\JobAssigned;
use App\Domain\Job\Events\JobCreated;
use App\Domain\Job\Events\JobStatusChanged;
use App\Domain\Job\Models\Job;
use App\Domain\Job\Repositories\JobRepositoryInterface;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function __construct(
        private JobRepositoryInterface $jobs,
    ) {}

    /**
     * Create a job when a tradie answered the forwarded call.
     * Status is 'assigned' immediately since the tradie is already on the call.
     */
    public function createFromForwardedCall(
        string $tenantId,
        string $callId,
        string $tradieId,
        string $callerNumber,
    ): Job {
        return DB::transaction(function () use ($tenantId, $callId, $tradieId, $callerNumber) {
            $job = new Job([
                'tenant_id'      => $tenantId,
                'call_id'        => $callId,
                'tradie_id'      => $tradieId,
                'status'         => 'assigned',
                'source'         => 'forwarded',
                'customer_phone' => $callerNumber,
                'assigned_at'    => now(),
            ]);

            $this->jobs->save($job);

            event(new JobCreated($job->id, $tenantId, $tradieId, $callId, 'forwarded', 'assigned'));
            event(new JobAssigned($job->id, $tenantId, $tradieId));

            return $job;
        });
    }

    /**
     * Create a job from AI-extracted details after the AI handled the call.
     * Status is 'ai_created' — needs dispatcher review before assignment.
     */
    public function createFromAICall(
        string           $tenantId,
        string           $callId,
        ExtractedJobDetails $details,
    ): Job {
        return DB::transaction(function () use ($tenantId, $callId, $details) {
            $job = new Job([
                'tenant_id'       => $tenantId,
                'call_id'         => $callId,
                'status'          => 'ai_created',
                'source'          => 'ai',
                'customer_name'   => $details->customerName,
                'customer_phone'  => null, // available from the Call record
                'customer_address'=> $details->customerAddress,
                'description'     => $details->description,
                'skill_required'  => $details->skillRequired,
                'ai_transcript'   => json_encode($details->rawTranscript),
                'metadata'        => ['preferred_time' => $details->preferredTime],
            ]);

            $this->jobs->save($job);

            event(new JobCreated($job->id, $tenantId, null, $callId, 'ai', 'ai_created'));

            return $job;
        });
    }

    /**
     * Transition a job to a new status with full guard checking.
     */
    public function transitionStatus(string $jobId, string $newStatus, array $extra = []): Job
    {
        $job = $this->jobs->findById($jobId);

        throw_unless(
            $job->canTransitionTo($newStatus),
            \InvalidArgumentException::class,
            "Cannot transition job from [{$job->status}] to [{$newStatus}]."
        );

        $oldStatus = $job->status;
        $job->status = $newStatus;

        if ($newStatus === 'completed') $job->completed_at = now();
        if ($newStatus === 'cancelled') {
            $job->cancelled_at = now();
            $job->cancellation_reason = $extra['reason'] ?? null;
        }

        $this->jobs->save($job);

        event(new JobStatusChanged($job->id, $job->tenant_id, $oldStatus, $newStatus));

        return $job;
    }

    /**
     * Assign an ai_created or pending job to a specific tradie.
     */
    public function assignToTradie(string $jobId, string $tradieId): Job
    {
        $job = $this->jobs->findById($jobId);

        throw_unless(
            in_array($job->status, ['pending', 'ai_created']),
            \InvalidArgumentException::class,
            "Job [{$jobId}] cannot be assigned from status [{$job->status}]."
        );

        $oldStatus    = $job->status;
        $job->tradie_id  = $tradieId;
        $job->status     = 'assigned';
        $job->assigned_at = now();

        $this->jobs->save($job);

        event(new JobStatusChanged($job->id, $job->tenant_id, $oldStatus, 'assigned'));
        event(new JobAssigned($job->id, $job->tenant_id, $tradieId));

        return $job;
    }
}
