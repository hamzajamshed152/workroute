<?php

namespace App\Domain\Tenant\Repositories;

use App\Domain\Tenant\Models\Tenant;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findById(string $id): Tenant
    {
        return Tenant::findOrFail($id);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    public function findByStripeCustomerId(string $customerId): ?Tenant
    {
        return Tenant::where('stripe_customer_id', $customerId)->first();
    }

    public function save(Tenant $tenant): void
    {
        $tenant->save();
    }
}
