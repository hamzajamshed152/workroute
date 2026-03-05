<?php

namespace App\Providers;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Job\Repositories\EloquentJobRepository;
use App\Domain\Job\Repositories\JobRepositoryInterface;
use App\Domain\Tradie\Repositories\EloquentTradieRepository;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use App\Infrastructure\Providers\AI\RetellAIProvider;
use App\Infrastructure\Providers\Call\TwilioCallProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Provider bindings — swap these via config without touching domain code ──
        $this->app->bind(CallProviderInterface::class, TwilioCallProvider::class);
        $this->app->bind(AIProviderInterface::class, RetellAIProvider::class);

        // ── Repository bindings ────────────────────────────────────────────────────
        $this->app->bind(TradieRepositoryInterface::class, EloquentTradieRepository::class);
        $this->app->bind(JobRepositoryInterface::class, EloquentJobRepository::class);
    }
}
