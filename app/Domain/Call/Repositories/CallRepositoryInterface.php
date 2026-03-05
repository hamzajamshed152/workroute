<?php

namespace App\Domain\Call\Repositories;

use App\Domain\Call\Models\Call;
use Illuminate\Support\Collection;

interface CallRepositoryInterface
{
    public function findById(string $id): Call;
    public function findByTwilioSid(string $sid): ?Call;
    public function findByAISessionId(string $sessionId): ?Call;
    public function findRecentForTenant(string $tenantId, int $limit = 50): Collection;
    public function save(Call $call): void;
}
