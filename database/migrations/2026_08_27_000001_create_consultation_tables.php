<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider_key')->default('unconfigured');
            $table->text('room_locator')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('patient_waiting_at')->nullable();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
        Schema::create('consultation_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('consent_version');
            $table->timestamp('accepted_at');
            $table->string('ip_address',45)->nullable();
            $table->timestamps();
            $table->unique(['consultation_id','patient_id','consent_version']);
        });
        Schema::create('consultation_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('participant_role');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('consultation_attendances');Schema::dropIfExists('consultation_consents');Schema::dropIfExists('consultations'); }
};
