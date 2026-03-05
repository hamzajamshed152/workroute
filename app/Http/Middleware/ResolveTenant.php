<?php

namespace App\Http\Middleware;

use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolves the current tenant from the authenticated tradie.
 *
 * Currently, tenant context comes from the authenticated user — each tradie
 * belongs to one tenant. This middleware makes the tenant available globally
 * via app('tenant') for any cross-cutting use.
 *
 * In a future multi-tenant setup (subdomain routing, header-based, etc.),
 * only this middleware needs to change.
 */
class ResolveTenant
{
    public function __construct(private TenantRepositoryInterface $tenants) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = auth()->user()) {
            $tenant = $this->tenants->findById($user->tenant_id);

            // Bind into the container for use anywhere without passing it around
            app()->instance('tenant', $tenant);

            // Abort if subscription is not active
            if (! $tenant->isSubscriptionActive()) {
                return response()->json([
                    'message' => 'Your subscription is inactive. Please update your billing details.',
                ], 402);
            }
        }

        return $next($request);
    }
}
