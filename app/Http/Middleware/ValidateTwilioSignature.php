<?php

namespace App\Http\Middleware;

use App\Domain\Call\Contracts\CallProviderInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidateTwilioSignature
{
    public function __construct(private CallProviderInterface $provider) {}

    public function handle(Request $request, Closure $next): mixed
    {
        // Skip in testing
        // if (app()->environment('local') && config('services.twilio.skip_signature_check')) {
        //     return $next($request);
        // }
        // if (config('services.twilio.skip_signature_check')) {
        //     return $next($request);   // ← must be here, before ANY validation
        // }
        if (app()->environment('local')) {
            return $next($request);
        }

        try {
            $this->provider->validateWebhookSignature($request);
        } catch (\Throwable $e) {
            throw new HttpException(403, 'Invalid Twilio signature.');
        }

        return $next($request);
    }
}
