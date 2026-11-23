<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('branches', function(Blueprint $table): void {
  $table->id(); $table->string('code',30)->unique(); $table->string('name'); $table->string('timezone',64)->default('Asia/Kuwait'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
 }); }
 public function down(): void { Schema::dropIfExists('branches'); }
};