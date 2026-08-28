<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('booking_request_id')->nullable()->unique()->after('public_id');
        });

        Schema::table('patient_documents', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['booking_request_id']);
            $table->dropColumn('booking_request_id');
        });
    }
};
