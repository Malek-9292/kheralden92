<?php

namespace App\Http\Middleware;

use App\Support\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(app(ModuleManager::class)->enabled($module), 404, 'هذه الوحدة غير مفعلة حاليًا.');

        return $next($request);
    }
}
