<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $request->session()->regenerate();
        $user = $request->user();

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
            'session_hash' => hash('sha256', $request->session()->getId()),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'user' => $user->load(['branch', 'roles.permissions']),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionHash = hash('sha256', $request->session()->getId());

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
