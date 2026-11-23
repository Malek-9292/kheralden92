<?php

namespace Tests\Feature;

use App\Livewire\Admin\DeliveryAreas\Index as DeliveryAreasIndex;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Models\EmployeeSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryAreaFeeReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_delivery_fee_requires_reason_and_persists_audit(): void
    {
        [$user, $token, $area] = $this->createStaffAndArea();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(DeliveryAreasIndex::class)
            ->call('edit', $area->id)
            ->set('deliveryFee', '1.500')
            ->set('changeReason', '')
            ->call('save')
            ->assertHasErrors(['changeReason']);

        $this->assertSame('1.000', (string) $area->fresh()->delivery_fee);

        Livewire::test(DeliveryAreasIndex::class)
            ->call('edit', $area->id)
            ->set('deliveryFee', '1.500')
            ->set('changeReason', 'زيادة تكلفة الوقود')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1.500', (string) $area->fresh()->delivery_fee);

        $log = AuditLog::query()
            ->where('action', 'delivery_area.updated')
            ->where('auditable_id', $area->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('زيادة تكلفة الوقود', $log->reason);
        $this->assertSame('زيادة تكلفة الوقود', data_get($log->after_values, 'reason'));
    }

    public function test_non_fee_update_does_not_require_reason(): void
    {
        [$user, $token, $area] = $this->createStaffAndArea();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(DeliveryAreasIndex::class)
            ->call('edit', $area->id)
            ->set('sortOrder', 5)
            ->set('changeReason', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5, $area->fresh()->sort_order);
    }

    /**
     * @return array{0: User, 1: string, 2: DeliveryArea}
     */
    private function createStaffAndArea(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'Manage delivery',
            'slug' => 'delivery.manage',
            'module' => 'Delivery',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Delivery Ops',
            'slug' => 'delivery-ops',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Delivery User',
            'phone' => '96563333333',
            'password' => 'delivery-password-123',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $area = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '1.000',
            'minimum_order' => '5.000',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return [$user, $token, $area];
    }
}
