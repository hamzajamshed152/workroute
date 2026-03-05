<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Tradie\Services\TradieOnboardingService;
use App\Http\Requests\Auth\RegisterTradieRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RegisterController extends Controller
{
    public function __construct(
        private TradieOnboardingService $onboarding,
    ) {}

    /**
     * POST /auth/register
     *
     * Registers a new tradie, provisions their Twilio number and Retell agent.
     * In a full SaaS product, this would also create the Tenant and initiate a
     * Stripe checkout session. That's wired in here as a comment-stub for now.
     */
    public function register(RegisterTradieRequest $request): JsonResponse
    {
        // TODO: Create Tenant record first
        // TODO: Initiate Stripe subscription checkout
        // For now, tenant_id comes from the request (future: from Stripe callback)

        $tradie = $this->onboarding->onboard($request->validated());

        $token = $tradie->createToken('auth_token')->plainTextToken;

        return response()->json([
            'tradie' => [
                'id'              => $tradie->id,
                'name'            => $tradie->name,
                'email'           => $tradie->email,
                'business_number' => $tradie->business_number,
            ],
            'token'  => $token,
        ], 201);
    }
}
