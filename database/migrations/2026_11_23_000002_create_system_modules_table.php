<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_core')->default(false);
            $table->json('dependencies')->nullable();
            $table->timestamps();
            $table->index(['is_enabled', 'is_core']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modules');
    }
};
