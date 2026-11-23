<?php

use App\Http\Middleware\EnsureActiveEmployeeSession;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SetKuwaitTimezone;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleRegistryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetKuwaitTimezone::class);
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin/dashboard');
        $middleware->alias([
            'permission' => EnsurePermission::class,
            'employee.active' => EnsureActiveEmployeeSession::class,
            'module.enabled' => EnsureModuleEnabled::class,
        ]);
    })
    ->withExceptions(fn (Exceptions $exceptions) => null)
    ->withProviders([AppServiceProvider::class, ModuleRegistryServiceProvider::class])
    ->create();
