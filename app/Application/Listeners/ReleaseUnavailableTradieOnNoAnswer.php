<?php

namespace App\Application\Listeners;

use App\Domain\Call\Events\CallNotAnswered;
use App\Domain\Tradie\Services\TradieAvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * When a forwarded call is not answered, release the tradie back to available.
 * Queued so it doesn't block the webhook response.
 */
class ReleaseUnavailableTradieOnNoAnswer implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private TradieAvailabilityService $availability) {}

    public function handle(CallNotAnswered $event): void
    {
        $call = \App\Domain\Call\Models\Call::find($event->callId);

        if ($call && $call->tradie_id) {
            $this->availability->releaseTradie($call->tradie_id);
        }
    }
}
