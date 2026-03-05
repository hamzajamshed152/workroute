<?php

namespace App\Domain\Tenant\Services;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        private TenantRepositoryInterface $tenants,
    ) {}

    /**
     * Create a new tenant during signup.
     * In a full SaaS flow, this is called before tradie registration.
     */
    public function create(string $name): Tenant
    {
        $tenant = new Tenant([
            'name'                => $name,
            'slug'                => $this->generateUniqueSlug($name),
            'subscription_status' => 'trialing',
            'trial_ends_at'       => now()->addDays(14),
            'features'            => ['ai_answering', 'call_forwarding'],
        ]);

        $this->tenants->save($tenant);

        return $tenant;
    }

    /**
     * Update subscription status — called from Stripe webhook.
     */
    public function updateSubscription(string $tenantId, string $status, ?string $subscriptionId = null): void
    {
        $tenant = $this->tenants->findById($tenantId);
        $tenant->subscription_status = $status;

        if ($subscriptionId) {
            $tenant->stripe_subscription_id = $subscriptionId;
        }

        $this->tenants->save($tenant);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
