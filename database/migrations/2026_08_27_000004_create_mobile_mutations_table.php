<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('mobile_mutations',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->uuid('client_request_id');$t->string('operation');$t->unsignedSmallInteger('response_status');$t->json('response_body');$t->timestamps();$t->unique(['user_id','client_request_id']);});}public function down():void{Schema::dropIfExists('mobile_mutations');}};
