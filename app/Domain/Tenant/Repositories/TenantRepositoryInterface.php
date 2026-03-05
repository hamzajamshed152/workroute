<?php

namespace App\Domain\Tenant\Repositories;

use App\Domain\Tenant\Models\Tenant;

interface TenantRepositoryInterface
{
    public function findById(string $id): Tenant;
    public function findBySlug(string $slug): ?Tenant;
    public function findByStripeCustomerId(string $customerId): ?Tenant;
    public function save(Tenant $tenant): void;
}
