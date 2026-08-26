<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('patient')->index();
            $table->string('phone', 30)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('preferred_communication', 20)->default('email');
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->text('description')->nullable();
            $table->string('icon')->default('heart-pulse');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->boolean('online_available')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(45);
            $table->unsignedSmallInteger('buffer_minutes')->default(15);
            $table->string('consultation_method')->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('starts_at')->index();
            $table->timestampTz('ends_at')->index();
            $table->string('timezone')->default('Africa/Lagos');
            $table->string('status')->default('requested')->index();
            $table->string('consultation_method')->default('in_person');
            $table->string('location')->nullable();
            $table->text('reason');
            $table->text('administrative_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['starts_at', 'ends_at', 'status']);
        });

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('authors')->nullable();
            $table->string('journal')->nullable();
            $table->date('published_at')->nullable();
            $table->string('doi')->nullable();
            $table->string('pmid')->nullable();
            $table->string('category')->default('Breast Cancer');
            $table->string('external_url')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('verification_status')->default('pending_review');
            $table->timestamps();
        });

        Schema::create('research_claims', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->text('claim');
            $table->string('source_title');
            $table->text('source_url');
            $table->date('source_date')->nullable();
            $table->string('confidence')->default('medium');
            $table->string('status')->default('pending_review');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('research_claims');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('services');
        Schema::dropIfExists('patient_profiles');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'phone', 'is_active', 'last_login_at']));
    }
};
