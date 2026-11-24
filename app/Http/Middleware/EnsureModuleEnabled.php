<?php

namespace App\\Http\\Middleware;

use App\\Support\\Modules\\ModuleManager;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        try {
            abort_unless(app(ModuleManager::class)->enabled($module), 404, 'هذه الوحدة غير مفعلة حاليًا.');
        } catch (\\Throwable $exception) {
            error_log(sprintf(
                'MODULE_MIDDLEWARE_FAILURE [%s] module=%s: %s in %s:%d',
                $exception::class,
                $module,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            throw $exception;
        }

        return $next($request);
    }
}
