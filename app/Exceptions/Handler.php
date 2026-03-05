<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->renderable(function (\InvalidArgumentException $e) {
            // Catches invalid state transitions from JobService
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $this->renderable(function (\RuntimeException $e) {
            if (app()->environment('production')) {
                // Don't leak internal runtime errors (e.g. Twilio/Retell API failures) in production
                report($e);
                return response()->json(['message' => 'An internal error occurred. Please try again.'], 500);
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'message' => 'Validation failed.',
            'errors'  => $exception->errors(),
        ], 422);
    }
}
