<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveEmployeeSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $now = now();

        if (
            ! $user
            || $user->status !== 'active'
            || ($user->valid_from && $now->lt($user->valid_from))
            || ($user->valid_until && $now->gt($user->valid_until))
        ) {
            $this->expireSession($request);
            abort(401, 'حساب الموظف غير نشط أو خارج فترة الصلاحية.');
        }

        $sessionToken = $request->session()->get('employee_session_token');\n        $sessionHash = is_string($sessionToken) ? hash('sha256', $sessionToken) : null;
        $employeeSession = $user->employeeSessions()
            ->where('session_hash', $sessionHash)
            ->whereNull('logged_out_at')
            ->first();

        if (! $employeeSession) {
            $this->expireSession($request);
            abort(401, 'تم إنهاء جلسة الموظف. يرجى تسجيل الدخول مرة أخرى.');
        }

        $idleMinutes = $user->hasRole('cashier')
            ? config('security.cashier_session_idle_minutes', 30)
            : config('security.admin_session_idle_minutes', 30);

        if ($employeeSession->last_activity_at?->lt($now->copy()->subMinutes($idleMinutes))) {
            $employeeSession->update(['logged_out_at' => $now, 'ended_reason' => 'idle_timeout']);
            $this->expireSession($request);
            abort(401, 'انتهت الجلسة بسبب عدم النشاط.');
        }

        $employeeSession->update(['last_activity_at' => $now]);

        return $next($request);
    }

    private function expireSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
