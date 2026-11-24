<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_area_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('delivery_fee', 12, 3)->default(0);
            $table->decimal('minimum_order', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'name']);
            $table->index(['branch_id', 'is_active', 'sort_order']);
        });

        Schema::table('delivery_areas', function (Blueprint $table): void {
            $table->foreignId('delivery_area_group_id')->nullable()->after('branch_id')
                ->constrained('delivery_area_groups')->nullOnDelete();
            $table->decimal('delivery_fee_override', 12, 3)->nullable();
            $table->decimal('minimum_order_override', 12, 3)->nullable();
            $table->index(['branch_id', 'delivery_area_group_id']);
        });

        foreach (DB::table('branches')->select('id')->orderBy('id')->get() as $branch) {
            $groupId = DB::table('delivery_area_groups')->insertGetId([
                'branch_id' => $branch->id,
                'name' => 'المناطق الحالية',
                'delivery_fee' => '0.000',
                'minimum_order' => '0.000',
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('delivery_areas')->where('branch_id', $branch->id)->update([
                'delivery_area_group_id' => $groupId,
                'delivery_fee_override' => DB::raw('delivery_fee'),
                'minimum_order_override' => DB::raw('minimum_order'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('delivery_areas', function (Blueprint $table): void {
            $table->dropIndex(['branch_id', 'delivery_area_group_id']);
            $table->dropConstrainedForeignId('delivery_area_group_id');
            $table->dropColumn(['delivery_fee_override', 'minimum_order_override']);
        });

        Schema::dropIfExists('delivery_area_groups');
    }
};
