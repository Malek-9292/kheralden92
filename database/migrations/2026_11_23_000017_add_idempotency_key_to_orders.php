<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('idempotency_key_hash', 64)->nullable()->after('tracking_token_hash');
            $table->unique(['branch_id', 'idempotency_key_hash'], 'orders_branch_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_branch_idempotency_unique');
            $table->dropColumn('idempotency_key_hash');
        });
    }
};
