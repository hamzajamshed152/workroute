<?php

namespace App\Http\Controllers\Tradie;

use App\Domain\Tradie\Services\TradieAvailabilityService;
use App\Http\Requests\Tradie\UpdateAvailabilityRequest;
use App\Http\Resources\TradieResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TradieController extends Controller
{
    public function __construct(
        private TradieAvailabilityService $availability,
    ) {}

    /**
     * GET /tradie/me
     * Returns the authenticated tradie's profile.
     */
    public function me(): TradieResource
    {
        return new TradieResource(auth()->user());
    }

    /**
     * PATCH /tradie/availability
     * Tradie manually toggles their availability from the app.
     */
    public function updateAvailability(UpdateAvailabilityRequest $request): JsonResponse
    {
        $this->availability->setAvailability(
            tradieId:  auth()->id(),
            available: $request->boolean('is_available'),
        );

        return response()->json([
            'message'      => 'Availability updated.',
            'is_available' => $request->boolean('is_available'),
        ]);
    }
}
