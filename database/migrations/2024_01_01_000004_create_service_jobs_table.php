<?php
// database/migrations/2024_01_01_000004_create_jobs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Note: table name is 'service_jobs' to avoid collision with Laravel's own 'jobs' queue table
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->foreignUuid('tradie_id')->nullable()->constrained('tradies')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('source', 20)->default('manual');  // manual | ai | forwarded
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->text('description')->nullable();
            $table->string('skill_required')->nullable();
            $table->text('notes')->nullable();
            $table->longText('ai_transcript')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tradie_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('service_jobs'); }
};
