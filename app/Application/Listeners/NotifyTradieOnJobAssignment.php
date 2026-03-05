<?php

namespace App\Application\Listeners;

use App\Domain\Job\Events\JobAssigned;
use App\Domain\Job\Repositories\JobRepositoryInterface;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify the tradie via SMS when a job is assigned to them.
 * Currently a stub — wire to Twilio SMS or any notification channel.
 * Queued so it doesn't block the request cycle.
 */
class NotifyTradieOnJobAssignment implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private JobRepositoryInterface   $jobs,
        private TradieRepositoryInterface $tradies,
    ) {}

    public function handle(JobAssigned $event): void
    {
        try {
            $job    = $this->jobs->findById($event->jobId);
            $tradie = $this->tradies->findById($event->tradieId);

            $message = $this->buildMessage($job, $tradie);

            // TODO: Replace with actual SMS sending via Twilio Messaging Service
            // \Twilio\Rest\Client->messages->create($tradie->personal_phone, ['from' => ..., 'body' => $message]);

            Log::info('NotifyTradieOnJobAssignment: would send SMS', [
                'tradie_id' => $tradie->id,
                'to'        => $tradie->personal_phone,
                'message'   => $message,
            ]);
        } catch (\Throwable $e) {
            // Log but don't re-throw — a failed notification should not fail the job creation
            Log::error('NotifyTradieOnJobAssignment failed', ['error' => $e->getMessage()]);
        }
    }

    private function buildMessage(\App\Domain\Job\Models\Job $job, \App\Domain\Tradie\Models\Tradie $tradie): string
    {
        $address = $job->customer_address ?? 'Address TBC';
        $name    = $job->customer_name    ?? 'Customer';

        return "New job assigned!\nCustomer: {$name}\nAddress: {$address}\nDetails: {$job->description}";
    }
}
