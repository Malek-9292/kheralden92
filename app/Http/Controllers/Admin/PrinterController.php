<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Printer;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrinterController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = Printer::query()->withCount('jobs')->orderBy('name');

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        return response()->json(['printers' => $query->get()]);
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'in:kitchen,cashier'],
            'connection_type' => ['required', 'in:usb,network'],
        ]);

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);

        $token = Str::random(64);
        $printer = Printer::query()->create([
            ...$data,
            'branch_id' => $branchId,
            'code' => strtoupper($data['code']),
            'device_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'branch_id' => $branchId,
            'actor_id' => $request->user()->id,
            'action' => 'printer.created',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'after_values' => $printer->only(['code', 'name', 'purpose', 'connection_type', 'is_active']),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم تسجيل الطابعة. احفظ رمز الجهاز الآن؛ لن يظهر مرة أخرى.',
            'printer' => $printer,
            'device_token' => $token,
        ], 201);
    }

    public function rotateToken(Request $request, Printer $printer, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $printer->branch_id);
        $token = Str::random(64);
        $printer->update(['device_token_hash' => hash('sha256', $token)]);

        AuditLog::query()->create([
            'branch_id' => $printer->branch_id,
            'actor_id' => $request->user()->id,
            'action' => 'printer.token_rotated',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم تغيير رمز الجهاز. حدّث إعدادات Print Agent.',
            'device_token' => $token,
        ]);
    }

    public function update(Request $request, Printer $printer, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $printer->branch_id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'connection_type' => ['sometimes', 'required', 'in:usb,network'],
        ]);

        $before = $printer->only(['name', 'is_active', 'connection_type']);
        $printer->update($data);

        AuditLog::query()->create([
            'branch_id' => $printer->branch_id,
            'actor_id' => $request->user()->id,
            'action' => 'printer.updated',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'before_values' => $before,
            'after_values' => $printer->only(['name', 'is_active', 'connection_type']),
            'created_at' => now(),
        ]);

        return response()->json(['printer' => $printer->fresh()]);
    }
}
