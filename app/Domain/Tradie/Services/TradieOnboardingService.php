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

            // Step 1 — Create tradie record (no number yet)
            $tradie = new Tradie([
                'tenant_id'      => $data['tenant_id'],
                'name'           => $data['name'],
                'email'          => $data['email'],
                'password'       => Hash::make($data['password']),
                'personal_phone' => $data['personal_phone'],
                'is_available'   => true,
                'role'           => 'tradie',
                'skills'         => $data['skills'] ?? [],
                'timezone'       => $data['timezone'] ?? 'Australia/Sydney',
            ]);

            $this->tradies->save($tradie);

            event(new TradieRegistered($tradie->id, $tradie->tenant_id, $tradie->email));

            // Step 2 & 3 — Buy Twilio number and configure webhooks
            $this->provisionPhoneNumber($tradie, $data['area_code'] ?? null);

            // Step 4 — Create Retell AI agent for this tradie
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
            areaCode:     $areaCode ?? '02',   // Default to Sydney area code
            friendlyName: "Tradie: {$tradie->name}",
        );

        // Configure Twilio to send inbound calls and status callbacks to our app
        $this->callProvider->configureNumberWebhooks(
            numberSid:           $result->numberSid,
            voiceUrl:            "{$appUrl}/webhooks/call/inbound",
            statusCallbackUrl:   "{$appUrl}/webhooks/call/status",
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
