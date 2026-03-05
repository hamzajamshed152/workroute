<?php

namespace App\Domain\Tradie\Services;

use App\Domain\AI\Services\AIVoiceService;
use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Tradie\Events\TradieBusinessNumberAssigned;
use App\Domain\Tradie\Events\TradieRegistered;
use App\Domain\Tradie\Models\Tradie;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TradieOnboardingService
{
    public function __construct(
        private TradieRepositoryInterface $tradies,
        private CallProviderInterface     $callProvider,
        private AIVoiceService            $aiVoiceService,
    ) {}

    /**
     * Full onboarding flow for a new tradie:
     *  1. Create user record
     *  2. Purchase Twilio number
     *  3. Configure Twilio webhooks on that number
     *  4. Provision Retell AI agent
     *  5. Fire domain events
     *
     * This runs in a transaction. If Twilio purchase fails, nothing is persisted.
     */
    public function onboard(array $data): Tradie
    {
        return DB::transaction(function () use ($data) {

            // Create tradie — no tenant_id anymore
            $tradie = new Tradie([
                'name'           => $data['name'],
                'email'          => $data['email'],
                'password'       => Hash::make($data['password']),
                'personal_phone' => $data['personal_phone'],
                'is_available'   => true,
                'skills'         => $data['skills'] ?? [],
                'timezone'       => $data['timezone'] ?? 'Australia/Sydney',
                // Start on trial
                'subscription_status' => 'trialing',
                'subscription_plan'   => 'solo',
                'trial_ends_at'       => now()->addDays(14),
                'ai_minutes_limit'    => 100,   // Solo plan default
                'usage_reset_at'      => now()->addMonthNoOverflow()->startOfMonth(),
            ]);

            $this->tradies->save($tradie);

            // No tenant event — just tradie registered
            event(new TradieRegistered($tradie->id, $tradie->email));

            // Provision Twilio number + Retell agent
            $this->provisionPhoneNumber($tradie, $data['area_code'] ?? null);
            $this->aiVoiceService->provisionAgentForTradie($tradie);

            return $tradie->fresh();
        });
    }

    /**
     * Purchase a Twilio number and configure webhooks for a tradie.
     * Extracted so it can be retried independently if it fails during onboarding.
     */
    public function provisionPhoneNumber(Tradie $tradie, ?string $areaCode = null): void
    {
        $appUrl = config('app.url');

        // Purchase the number
        $result = $this->callProvider->purchaseNumber(
            areaCode: $areaCode ?? '02',   // Default to Sydney area code
            friendlyName: "Tradie: {$tradie->name}",
        );

        // Configure Twilio to send inbound calls and status callbacks to our app
        $this->callProvider->configureNumberWebhooks(
            numberSid: $result->numberSid,
            voiceUrl: "{$appUrl}/webhooks/call/inbound",
            statusCallbackUrl: "{$appUrl}/webhooks/call/status",
        );

        // Persist the number on the tradie
        $tradie->business_number  = $result->phoneNumber;
        $tradie->twilio_number_sid = $result->numberSid;
        $this->tradies->save($tradie);

        event(new TradieBusinessNumberAssigned(
            $tradie->id,
            $tradie->tenant_id,
            $result->phoneNumber,
            $result->numberSid,
        ));
    }

    /**
     * Tear down when a tradie is deactivated / subscription cancelled.
     */
    public function offboard(Tradie $tradie): void
    {
        DB::transaction(function () use ($tradie) {
            if ($tradie->twilio_number_sid) {
                $this->callProvider->releaseNumber($tradie->twilio_number_sid);
            }

            $tradie->update([
                'is_available'      => false,
                'business_number'   => null,
                'twilio_number_sid' => null,
            ]);
        });
    }
}
