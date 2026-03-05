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
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('personal_phone');           // Where calls are forwarded
            $table->string('business_number')->nullable()->unique(); // Twilio number
            $table->string('twilio_number_sid')->nullable();
            $table->string('retell_agent_id')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('role')->default('tradie');  // admin | tradie | dispatcher
            $table->json('skills')->nullable();
            $table->string('timezone')->default('Australia/Sydney');
            $table->json('notification_preferences')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_available']); // Hot query path for availability check
            $table->index('business_number');              // Hot query path for inbound call lookup
        });
    }

    public function down(): void { Schema::dropIfExists('tradies'); }
};
