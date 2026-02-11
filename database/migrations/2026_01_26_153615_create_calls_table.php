<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tradie_id')->constrained()->cascadeOnDelete();

            $table->string('from_number');   // Customer number
            $table->string('to_number');     // Virtual number

            $table->enum('status', [
                'answered',
                'missed',
                'ai_handled'
            ])->default('missed');

            $table->string('recording_url')->nullable();
            $table->longText('transcript')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
