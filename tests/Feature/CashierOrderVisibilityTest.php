<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cashier\Index as CashierIndex;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\EmployeeSession;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CashierOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_and_admin_see_storefront_orders_of_all_active_statuses_in_pos_queue(): void
    {
        [$branch, $product, $area] = $this->seedCatalog();

        $pickup = $this->placeStorefrontOrder($branch, $product, [
            'customer_name' => 'عميل استلام',
            'customer_phone' => '96560001001',
            'fulfillment_type' => 'pickup',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $delivery = $this->placeStorefrontOrder($branch, $product, [
            'customer_name' => 'عميل توصيل',
            'customer_phone' => '96560001002',
            'fulfillment_type' => 'delivery',
            'delivery_area_id' => $area->id,
            'block' => '1',
            'street' => '5',
            'house_number' => '10',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $confirmed = Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-VIS-CONF',
            'customer_name' => 'مؤكد',
            'status' => 'confirmed',
            'fulfillment_type' => 'pickup',
        ]));

        $preparing = Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-VIS-PREP',
            'customer_name' => 'تجهيز',
            'status' => 'preparing',
            'fulfillment_type' => 'delivery',
            'delivery_area_name' => 'الخالدية',
        ]));

        $ready = Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-VIS-READY',
            'customer_name' => 'جاهز',
            'status' => 'ready',
            'fulfillment_type' => 'pickup',
        ]));

        [$cashier, $cashierToken] = $this->createStaff($branch, 'cashier', [
            'cashier.use', 'orders.accept', 'orders.view', 'orders.cancel',
        ]);
        [$admin, $adminToken] = $this->createStaff($branch, 'system_admin', [
            'cashier.use', 'orders.accept', 'orders.view', 'orders.cancel', 'dashboard.view',
        ], branchScopedRole: false);

        foreach ([[$cashier, $cashierToken], [$admin, $adminToken]] as [$user, $token]) {
            $this->actingAs($user)->withSession(['employee_session_token' => $token]);

            Livewire::test(CashierIndex::class)
                ->assertSeeHtml('wire:poll.5s')
                ->assertSee($pickup['order_number'])
                ->assertSee($delivery['order_number'])
                ->assertSee($confirmed->order_number)
                ->assertSee($preparing->order_number)
                ->assertSee($ready->order_number)
                ->assertSee('جديد')
                ->assertSee('مؤكد')
                ->assertSee('قيد التجهيز')
                ->assertSee('جاهز')
                ->assertSee('استلام')
                ->assertSee('توصيل')
                ->assertSee('2.500')
                ->assertSee('قبول الطلب')
                ->assertSee('تحصيل نقدي');
        }
    }

    public function test_regression_confirmed_and_preparing_orders_remain_visible_after_accept(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        [$cashier, $token] = $this->createStaff($branch, 'cashier', [
            'cashier.use', 'orders.accept', 'orders.view',
        ]);

        $pending = Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-REG-PENDING',
            'customer_name' => 'قبل القبول',
            'status' => 'pending',
        ]));

        $this->actingAs($cashier)->withSession(['employee_session_token' => $token]);

        Livewire::test(CashierIndex::class)
            ->assertSee($pending->order_number)
            ->call('acceptPending', $pending->id)
            ->assertHasNoErrors();

        $this->assertSame('confirmed', $pending->fresh()->status);

        // Regression: previously the main POS query only included ready/out_for_delivery/completed,
        // so accepted (confirmed) orders vanished from the cashier page until kitchen marked ready.
        Livewire::test(CashierIndex::class)
            ->assertSee($pending->order_number)
            ->assertSee('مؤكد')
            ->assertDontSee('لا توجد طلبات في طابور الكاشير');
    }

    public function test_cancelled_orders_are_not_in_default_cashier_queue(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        [$cashier, $token] = $this->createStaff($branch, 'cashier', ['cashier.use', 'orders.accept']);

        Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-CANCELLED',
            'customer_name' => 'ملغي',
            'status' => 'cancelled',
        ]));

        Order::query()->create($this->orderAttributes($branch, [
            'order_number' => 'KHAL-ACTIVE',
            'customer_name' => 'نشط',
            'status' => 'pending',
        ]));

        $this->actingAs($cashier)->withSession(['employee_session_token' => $token]);

        Livewire::test(CashierIndex::class)
            ->assertSee('KHAL-ACTIVE')
            ->assertDontSee('KHAL-CANCELLED');
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
            'name' => 'الساندويتشات',
            'slug' => 'sandwiches',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'ساندويتش تجريبي',
            'slug' => 'test-sandwich',
            'price' => '2.500',
            'is_active' => true,
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
     * @param  array<string, mixed>  $payload
     * @return array{order_number: string, id: int}
     */
    private function placeStorefrontOrder(Branch $branch, Product $product, array $payload): array
    {
        $response = $this->postJson('/api/orders/'.$branch->code, $payload)->assertCreated();

        return [
            'order_number' => (string) $response->json('order.order_number'),
            'id' => (int) $response->json('order.id'),
        ];
    }

    /**
     * @param  list<string>  $permissionSlugs
     * @return array{0: User, 1: string}
     */
    private function createStaff(Branch $branch, string $roleSlug, array $permissionSlugs, bool $branchScopedRole = true): array
    {
        $permissions = collect($permissionSlugs)->map(
            fn (string $slug) => Permission::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => 'Orders'],
            ),
        );

        $role = Role::query()->create([
            'branch_id' => $branchScopedRole ? $branch->id : null,
            'name' => $roleSlug,
            'slug' => $roleSlug,
            'is_system' => true,
        ]);
        $role->permissions()->sync($permissions->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branchScopedRole ? $branch->id : null,
            'name' => $roleSlug.' user',
            'phone' => '9655'.random_int(1000000, 9999999),
            'password' => 'cashier-visibility-pass',
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

        return [$user, $token];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderAttributes(Branch $branch, array $overrides): array
    {
        return array_merge([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-'.Str::upper(Str::random(6)),
            'customer_name' => 'عميل',
            'customer_phone' => '96560009999',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '2.500',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '2.500',
            'tracking_token_hash' => hash('sha256', Str::random(40)),
        ], $overrides);
    }
}
