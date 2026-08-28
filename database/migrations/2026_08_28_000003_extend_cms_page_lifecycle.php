<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('seo_title', 70)->nullable()->after('template');
            $table->string('seo_description', 170)->nullable()->after('seo_title');
            $table->unsignedInteger('lock_version')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'lock_version']);
        });
    }
};
