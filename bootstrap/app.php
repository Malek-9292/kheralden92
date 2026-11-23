<?php
use App\Http\Middleware\SetKuwaitTimezone;
use App\Http\Middleware\EnsurePermission;\nuse App\Http\Middleware\EnsureActiveEmployeeSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
 ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
 ->withMiddleware(function (Middleware $middleware): void {\n  $middleware->append(SetKuwaitTimezone::class);\n  $middleware->alias([\n   'permission' => EnsurePermission::class,\n   'employee.active' => EnsureActiveEmployeeSession::class,\n  ]);\n })
 ->withExceptions(fn (Exceptions $exceptions) => null)
 ->withProviders([App\Providers\AppServiceProvider::class, App\Providers\ModuleRegistryServiceProvider::class])
 ->create();