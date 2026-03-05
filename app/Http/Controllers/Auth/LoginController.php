<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Tradie\Models\Tradie;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * POST /auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tradie = Tradie::where('email', $request->email)->first();

        if (! $tradie || ! Hash::check($request->password, $tradie->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Revoke all existing tokens and issue a fresh one
        $tradie->tokens()->delete();
        $token = $tradie->createToken('auth_token')->plainTextToken;

        return response()->json([
            'tradie' => [
                'id'              => $tradie->id,
                'name'            => $tradie->name,
                'email'           => $tradie->email,
                'business_number' => $tradie->business_number,
                'is_available'    => $tradie->is_available,
                'role'            => $tradie->role,
            ],
            'token' => $token,
        ]);
    }

    /**
     * POST /auth/logout
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
