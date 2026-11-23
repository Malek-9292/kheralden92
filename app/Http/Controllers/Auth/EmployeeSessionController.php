<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AdminHome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        if (! Auth::attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            DB::table('employee_login_attempts')->insert([
                'login_identifier' => $credentials['phone'],
                'ip_address' => $request->ip(),
                'was_successful' => false,
                'attempted_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'phone' => ['رقم الهاتف أو كلمة المرور غير صحيحة، أو أن الحساب غير نشط.'],
            ]);
        }

        $user = $request->user();
        $now = now();

        if (
            ($user->valid_from && $now->lt($user->valid_from))
            || ($user->valid_until && $now->gt($user->valid_until))
        ) {
            Auth::logout();

            DB::table('employee_login_attempts')->insert([
                'user_id' => $user->id,
                'login_identifier' => $credentials['phone'],
                'ip_address' => $request->ip(),
                'was_successful' => false,
                'attempted_at' => $now,
            ]);

            throw ValidationException::withMessages([
                'phone' => ['حساب الموظف خارج فترة الصلاحية.'],
            ]);
        }

        $request->session()->regenerate();

        $sessionToken = Str::random(64);
        $request->session()->put('employee_session_token', $sessionToken);

        $user->forceFill(['last_login_at' => now()])->save();

        DB::table('employee_login_attempts')->insert([
            'user_id' => $user->id,
            'login_identifier' => $credentials['phone'],
            'ip_address' => $request->ip(),
            'was_successful' => true,
            'attempted_at' => now(),
        ]);

        $user->employeeSessions()->create([
            'branch_id' => $user->branch_id,
            'session_hash' => hash('sha256', $sessionToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $user->load(['branch', 'roles.permissions']);

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'redirect_to' => AdminHome::pathFor($user),
            'user' => $user,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionToken = $request->session()->get('employee_session_token');
        $sessionHash = is_string($sessionToken) ? hash('sha256', $sessionToken) : null;

        $user?->employeeSessions()
            ->where('session_hash', $sessionHash)
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now(), 'ended_reason' => 'user_logout']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load(['branch', 'roles.permissions']),
        ]);
    }
}
