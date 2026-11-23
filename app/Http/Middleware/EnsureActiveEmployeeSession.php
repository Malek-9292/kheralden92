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

        if (! $user || $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(401, 'حساب الموظف غير نشط أو انتهت الجلسة.');
        }

        $sessionHash = hash('sha256', $request->session()->getId());
        $employeeSession = $user->employeeSessions()
            ->where('session_hash', $sessionHash)
            ->whereNull('logged_out_at')
            ->first();

        if (! $employeeSession) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(401, 'تم إنهاء جلسة الموظف. يرجى تسجيل الدخول مرة أخرى.');
        }

        $employeeSession->update(['last_activity_at' => now()]);

        return $next($request);
    }
}
