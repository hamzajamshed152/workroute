<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Webhook\CallWebhookController;
use App\Http\Controllers\Webhook\RetellWebhookController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login',    [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});

// ── Twilio Webhooks ──────────────────────────────────────────────────────────
// These routes are called BY Twilio — not by logged-in users.
// Protected by ValidateTwilioSignature middleware, NOT auth:sanctum.
// CRITICAL: These must be excluded from CSRF protection (add to VerifyCsrfToken $except).
// Route::prefix('webhooks')->middleware(['validate.twilio'])->group(function () {
// Route::post('/webhooks/call/status', [CallWebhookController::class, 'status']);
Route::prefix('webhooks')->group(function () {
    Route::post('/call/inbound', [CallWebhookController::class, 'inbound']);
    Route::post('/call/status',  [CallWebhookController::class, 'status']);
        //  ->where('callId', '[0-9a-f-]+');
});

// ── Retell AI Webhooks ────────────────────────────────────────────────────────
// Protected by Retell's API key validation inside the controller.
Route::prefix('webhooks')->group(function () {
    Route::post('/retell/events', [RetellWebhookController::class, 'handle']);
});

// ── Authenticated API ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Tradie profile & availability
    Route::prefix('tradie')->group(function () {
        Route::get('/me',           [\App\Http\Controllers\Tradie\TradieController::class, 'me']);
        Route::patch('/availability', [\App\Http\Controllers\Tradie\TradieController::class, 'updateAvailability']);
    });

    // Jobs
    Route::prefix('jobs')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Job\JobController::class, 'index']);
        Route::get('/{job}',           [\App\Http\Controllers\Job\JobController::class, 'show']);
        Route::patch('/{job}/status',  [\App\Http\Controllers\Job\JobController::class, 'updateStatus']);
        Route::patch('/{job}/assign',  [\App\Http\Controllers\Job\JobController::class, 'assign']);
    });
});
