<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TEMP-{uuid} is 41 chars; PostgreSQL enforces varchar(40) (SQLite did not).
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number', 40)->change();
        });
    }
};
