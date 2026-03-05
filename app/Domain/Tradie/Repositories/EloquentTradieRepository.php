<?php

namespace App\Domain\Tradie\Repositories;

use App\Domain\Tradie\Models\Tradie;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentTradieRepository implements TradieRepositoryInterface
{
    public function findById(string $id): Tradie
    {
        return Tradie::findOrFail($id);
    }

    public function findByBusinessNumber(string $phoneNumber): ?Tradie
    {
        // Cache this — it's called on every inbound call
        return Cache::remember(
            "tradie:business_number:{$phoneNumber}",
            now()->addMinutes(30),
            fn () => Tradie::where('business_number', $phoneNumber)->first()
        );
    }

    public function findAvailable(string $tenantId): ?Tradie
    {
        // NOTE: This query is protected by a Redis lock in TradieAvailabilityService
        // to prevent race conditions on simultaneous calls.
        return Tradie::where('tenant_id', $tenantId)
            ->where('is_available', true)
            ->first();
    }

    public function save(Tradie $tradie): void
    {
        $tradie->save();

        // Bust number lookup cache when tradie is updated
        if ($tradie->business_number) {
            Cache::forget("tradie:business_number:{$tradie->business_number}");
        }
    }

    public function findByTenant(string $tenantId): Collection
    {
        return Tradie::where('tenant_id', $tenantId)->get();
    }
}
