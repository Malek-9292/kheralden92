<?php

namespace Tests\Feature;

use App\Models\Printer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setPreviewEnv(string $phone, string $password, string $token): void
    {
        putenv("ADMIN_PHONE={$phone}");
        putenv("ADMIN_PASSWORD={$password}");
        putenv("PRINTER_TOKEN={$token}");
        $_ENV['ADMIN_PHONE'] = $phone;
        $_ENV['ADMIN_PASSWORD'] = $password;
        $_ENV['PRINTER_TOKEN'] = $token;
        $_SERVER['ADMIN_PHONE'] = $phone;
        $_SERVER['ADMIN_PASSWORD'] = $password;
        $_SERVER['PRINTER_TOKEN'] = $token;
    }

    protected function clearPreviewEnv(): void
    {
        putenv('ADMIN_PHONE');
        putenv('ADMIN_PASSWORD');
        putenv('PRINTER_TOKEN');
        unset($_ENV['ADMIN_PHONE'], $_ENV['ADMIN_PASSWORD'], $_ENV['PRINTER_TOKEN']);
        unset($_SERVER['ADMIN_PHONE'], $_SERVER['ADMIN_PASSWORD'], $_SERVER['PRINTER_TOKEN']);
    }

    public function test_preview_bootstrap_is_idempotent_and_keeps_printer_token_hash(): void
    {
        $this->setPreviewEnv(
            '96550002222',
            'preview-admin-pass',
            'stable-printer-token-value-for-preview',
        );

        $this->artisan('fatoosh:preview:bootstrap')->assertExitCode(0);
        $this->artisan('fatoosh:preview:bootstrap')->assertExitCode(0);

        $this->assertGreaterThan(100, Product::query()->count());

        $admin = User::query()->where('phone', '96550002222')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('system_admin'));

        $printer = Printer::query()->where('code', 'KITCHEN-01')->first();
        $this->assertNotNull($printer);
        $this->assertSame(hash('sha256', 'stable-printer-token-value-for-preview'), $printer->device_token_hash);
        $this->assertSame(1, Printer::query()->where('code', 'KITCHEN-01')->count());

        $this->clearPreviewEnv();
    }

    public function test_preview_bootstrap_requires_env_secrets(): void
    {
        $this->clearPreviewEnv();

        $this->artisan('fatoosh:preview:bootstrap')->assertExitCode(1);
    }
}
