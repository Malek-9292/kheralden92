<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class SetKuwaitTimezone {
 public function handle(Request $request, Closure $next): Response {
  date_default_timezone_set(config('app.timezone', 'Asia/Kuwait'));
  return $next($request);
 }
}