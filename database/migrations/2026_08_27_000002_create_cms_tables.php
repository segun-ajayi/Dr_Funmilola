<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  Schema::create('cms_pages',function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->string('template')->default('standard');$t->string('status')->default('draft')->index();$t->json('published_snapshot')->nullable();$t->timestamp('published_at')->nullable();$t->foreignId('created_by')->constrained('users')->restrictOnDelete();$t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();});
  Schema::create('cms_sections',function(Blueprint $t){$t->id();$t->foreignId('cms_page_id')->constrained()->cascadeOnDelete();$t->uuid('section_key');$t->string('type');$t->unsignedSmallInteger('sort_order')->default(0);$t->boolean('is_visible')->default(true);$t->json('content');$t->json('presentation')->nullable();$t->timestamps();$t->unique(['cms_page_id','section_key']);});
  Schema::create('cms_versions',function(Blueprint $t){$t->id();$t->foreignId('cms_page_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('version');$t->string('reason');$t->json('snapshot');$t->foreignId('created_by')->constrained('users')->restrictOnDelete();$t->timestamps();$t->unique(['cms_page_id','version']);});
  Schema::create('cms_preview_tokens',function(Blueprint $t){$t->id();$t->foreignId('cms_page_id')->constrained()->cascadeOnDelete();$t->string('token_hash',64)->unique();$t->timestamp('expires_at');$t->foreignId('created_by')->constrained('users')->restrictOnDelete();$t->timestamps();});
  Schema::create('cms_settings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->json('draft_value');$t->json('published_value')->nullable();$t->foreignId('updated_by')->constrained('users')->restrictOnDelete();$t->timestamps();});
 }
 public function down():void{Schema::dropIfExists('cms_settings');Schema::dropIfExists('cms_preview_tokens');Schema::dropIfExists('cms_versions');Schema::dropIfExists('cms_sections');Schema::dropIfExists('cms_pages');}
};
