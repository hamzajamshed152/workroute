<?php

namespace App\Domain\Job\Repositories;

use App\Domain\Job\Models\Job;
use Illuminate\Support\Collection;

interface JobRepositoryInterface
{
    public function findById(string $id): Job;
    public function findByCallId(string $callId): ?Job;
    public function findPendingForTenant(string $tenantId): Collection;
    public function save(Job $job): void;
}
