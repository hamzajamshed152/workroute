<?php

namespace App\Domain\Call\Repositories;

use App\Domain\Call\Models\Call;
use Illuminate\Support\Collection;

class EloquentCallRepository implements CallRepositoryInterface
{
    public function findById(string $id): Call
    {
        return Call::findOrFail($id);
    }

    public function findByTwilioSid(string $sid): ?Call
    {
        return Call::where('twilio_call_sid', $sid)->first();
    }

    public function findByAISessionId(string $sessionId): ?Call
    {
        return Call::where('ai_session_id', $sessionId)->first();
    }

    public function findRecentForTenant(string $tenantId, int $limit = 50): Collection
    {
        return Call::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function save(Call $call): void
    {
        $call->save();
    }
}
