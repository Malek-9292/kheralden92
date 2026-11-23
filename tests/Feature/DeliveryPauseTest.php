<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\EmployeeSession;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryPauseTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_orders_are_rejected_when_paused_but_pickup_still_works(): void
    {
        [$branch, $product, $area] = $this->seedCatalog();

        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'operations',
            'key' => 'delivery_paused',
            'value' => '1',
        ]);
        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'operations',
            'key' => 'delivery_pause_reason',
            'value' => 'ضغط الطلبات',
        ]);

        $this->postJson('/api/orders/KHAL', [
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'delivery',
            'delivery_area_id' => $area->id,
            'block' => '1',
            'street' => '10',
            'house_number' => '5',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('fulfillment_type');

        $this->postJson('/api/orders/KHAL', [
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->getJson('/api/storefront/KHAL/settings')
            ->assertOk()
            ->assertJsonPath('settings.delivery_paused', true)
            ->assertJsonPath('settings.is_accepting_delivery', false)
            ->assertJsonPath('settings.delivery_pause_reason', 'ضغط الطلبات');
    }

    public function test_settings_can_toggle_delivery_pause(): void
    {
        [$user, $token, $branch] = $this->createSettingsUser();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(SettingsIndex::class)
            ->set('deliveryPaused', true)
            ->set('deliveryPauseReason', 'طقس سيئ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $branch->id,
            'key' => 'delivery_paused',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $branch->id,
            'key' => 'delivery_pause_reason',
            'value' => 'طقس سيئ',
        ]);
    }

    /**
     * @return array{0: Branch, 1: Product, 2: DeliveryArea}
     */
    private function seedCatalog(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'ساندويش',
            'slug' => 'sandwich',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'فتوش',
            'slug' => 'fatoosh',
            'price' => '2.500',
            'is_active' => true,
            'is_available' => true,
        ]);

        $area = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '1.000',
            'minimum_order' => '3.000',
            'is_active' => true,
        ]);

        return [$branch, $product, $area];
    }

    /**
     * @return array{0: User, 1: string, 2: Branch}
     */
    private function createSettingsUser(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'Manage settings',
            'slug' => 'settings.manage',
            'module' => 'SystemSettings',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Settings',
            'slug' => 'settings',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Settings User',
            'phone' => '96564444444',
            'password' => 'settings-password-123',
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

        return [$user, $token, $branch];
    }
}
