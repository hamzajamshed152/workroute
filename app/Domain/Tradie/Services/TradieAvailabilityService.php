<?php

namespace App\Domain\Tradie\Services;

use App\Domain\Tradie\Models\Tradie;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class TradieAvailabilityService
{
    public function __construct(
        private TradieRepositoryInterface $tradies,
    ) {}

    /**
     * Atomically check-and-claim the first available tradie for a tenant.
     * Uses a Redis lock to prevent two simultaneous calls claiming the same tradie.
     *
     * Returns null if no tradie is available.
     */
    public function claimAvailableTradie(string $tenantId): ?Tradie
    {
        $lock = Cache::lock("availability_claim:{$tenantId}", 5);

        return $lock->block(3, function () use ($tenantId) {
            $tradie = $this->tradies->findAvailable($tenantId);

            if (! $tradie) {
                return null;
            }

            // Mark unavailable within the lock so the next simultaneous call doesn't grab them
            $tradie->markUnavailable();
            $this->tradies->save($tradie);

            return $tradie;
        });
    }

    /**
     * Release a tradie back to available after a call ends.
     */
    public function releaseTradie(string $tradieId): void
    {
        $tradie = $this->tradies->findById($tradieId);
        $tradie->markAvailable();
        $this->tradies->save($tradie);

        event(new \App\Domain\Tradie\Events\TradieAvailabilityChanged($tradieId, $tradie->tenant_id, true));
    }

    /**
     * Set a tradie's availability manually (e.g. from the app toggle).
     */
    public function setAvailability(string $tradieId, bool $available): void
    {
        $tradie = $this->tradies->findById($tradieId);

        if ($available) {
            $tradie->markAvailable();
        } else {
            $tradie->markUnavailable();
        }

        $this->tradies->save($tradie);

        event(new \App\Domain\Tradie\Events\TradieAvailabilityChanged($tradieId, $tradie->tenant_id, $available));
    }
}
