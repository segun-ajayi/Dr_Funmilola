<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title', 150);
            $table->string('original_name', 150);
            $table->string('storage_path')->unique();
            $table->string('mime_type', 40);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width_pixels');
            $table->unsignedInteger('height_pixels');
            $table->char('checksum_sha256', 64)->index();
            $table->string('alt_text', 500)->nullable();
            $table->string('caption', 500)->nullable();
            $table->boolean('is_decorative')->default(false);
            $table->boolean('is_archived')->default(false)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media_assets');
    }
};
