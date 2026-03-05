<?php

namespace App\Domain\Job\Repositories;

use App\Domain\Job\Models\Job;
use Illuminate\Support\Collection;

class EloquentJobRepository implements JobRepositoryInterface
{
    public function findById(string $id): Job
    {
        return Job::findOrFail($id);
    }

    public function findByCallId(string $callId): ?Job
    {
        return Job::where('call_id', $callId)->first();
    }

    public function findPendingForTenant(string $tenantId): Collection
    {
        return Job::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'ai_created'])
            ->orderBy('created_at')
            ->get();
    }

    public function save(Job $job): void
    {
        $job->save();
    }
}
