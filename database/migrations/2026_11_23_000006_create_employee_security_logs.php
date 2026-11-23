<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('login_identifier', 190)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('approximate_location', 190)->nullable();
            $table->boolean('was_successful')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
            $table->index(['user_id', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });

        Schema::create('employee_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->string('ended_reason', 80)->nullable();
            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_sessions');
        Schema::dropIfExists('employee_login_attempts');
    }
};
