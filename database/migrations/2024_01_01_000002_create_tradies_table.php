<?php
// database/migrations/2024_01_01_000002_create_tradies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tradies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('personal_phone');
            $table->string('business_number')->nullable()->unique();
            $table->string('twilio_number_sid')->nullable();
            $table->string('retell_agent_id')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('skills')->nullable();
            $table->string('timezone')->default('Australia/Sydney');
            $table->json('notification_preferences')->nullable();

            // Subscription & billing — moved from Tenant
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('subscription_status')->default('trialing'); // trialing|active|past_due|cancelled
            $table->string('subscription_plan')->default('solo');       // solo only for now
            $table->timestamp('trial_ends_at')->nullable();

            // Usage tracking — for plan limits
            $table->integer('ai_minutes_used')->default(0);        // incremented after each AI call
            $table->integer('ai_minutes_limit')->default(100);     // set by plan
            $table->timestamp('usage_reset_at')->nullable();       // reset monthly

            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index('is_available');
            $table->index('business_number');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tradies');
    }
};
