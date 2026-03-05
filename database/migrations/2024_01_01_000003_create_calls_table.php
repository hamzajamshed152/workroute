<?php
// database/migrations/2024_01_01_000003_create_calls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tradie_id')->nullable();  // was tenant_id
            $table->foreign('tradie_id')->references('id')->on('tradies');
            $table->string('twilio_call_sid')->unique();  // Idempotency key — Twilio retries use the same SID
            $table->string('caller_number');
            $table->string('called_number');
            $table->string('status', 30)->default('initiated');
            $table->string('direction', 10)->default('inbound');
            $table->string('forwarded_to')->nullable();
            $table->string('forward_status', 30)->nullable();
            $table->string('ai_session_id')->nullable()->index();
            $table->integer('duration_seconds')->default(0);
            $table->string('recording_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['tradie_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('calls'); }
};
