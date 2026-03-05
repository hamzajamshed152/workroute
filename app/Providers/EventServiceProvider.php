<?php

namespace App\Providers;

use App\Application\Listeners\ReleaseUnavailableTradieOnNoAnswer;
use App\Domain\Call\Events\CallNotAnswered;
use App\Domain\Job\Events\JobAssigned;
use App\Domain\Job\Events\JobCreated;
use App\Domain\Tradie\Events\TradieBusinessNumberAssigned;
use App\Domain\Tradie\Events\TradieRegistered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        // ── Call events ───────────────────────────────────────────────────────
        CallNotAnswered::class => [
            ReleaseUnavailableTradieOnNoAnswer::class,
            // Future: \App\Application\Listeners\LogMissedCall::class,
        ],

        // ── Job events ────────────────────────────────────────────────────────
        JobCreated::class => [
            // Future: \App\Application\Listeners\NotifyDispatcherOfNewJob::class,
        ],

        JobAssigned::class => [
            // Future: \App\Application\Listeners\NotifyTradieOfAssignment::class,
            // Future: \App\Application\Listeners\NotifyCustomerJobConfirmed::class,
        ],

        // ── Tradie events ─────────────────────────────────────────────────────
        TradieRegistered::class => [
            // Future: \App\Application\Listeners\SendWelcomeEmail::class,
        ],

        TradieBusinessNumberAssigned::class => [
            // Future: \App\Application\Listeners\SendBusinessNumberSMS::class,
        ],
    ];
}
