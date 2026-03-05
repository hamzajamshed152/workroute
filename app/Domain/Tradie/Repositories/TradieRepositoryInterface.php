<?php

namespace App\Domain\Tradie\Repositories;

use App\Domain\Tradie\Models\Tradie;
use Illuminate\Support\Collection;

interface TradieRepositoryInterface
{
    public function findById(string $id): Tradie;
    public function findByBusinessNumber(string $phoneNumber): ?Tradie;
    public function findAvailable(string $tenantId): ?Tradie;
    public function save(Tradie $tradie): void;
    public function findByTenant(string $tenantId): Collection;
}
