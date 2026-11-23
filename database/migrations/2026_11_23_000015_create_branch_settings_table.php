<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('setting_group', 60)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'key']);
            $table->index(['branch_id', 'setting_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
