<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\PrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintAgentController extends Controller
{
    public function claim(Request $request): JsonResponse
    {
        $printer = $this->authenticatePrinter($request);

        $job = DB::transaction(function () use ($printer): ?PrintJob {
            $jobType = $printer->purpose === 'kitchen' ? 'kitchen_ticket' : 'customer_receipt';

            $job = PrintJob::query()
                ->where('branch_id', $printer->branch_id)
                ->where('job_type', $jobType)
                ->where(function ($query) use ($printer): void {
                    $query->whereNull('printer_id')->orWhere('printer_id', $printer->id);
                })
                ->where(function ($query): void {
                    $query->where(function ($pending): void {
                        $pending->where('status', 'pending')->where('available_at', '<=', now());
                    })->orWhere(function ($stale): void {
                        $stale->where('status', 'processing')->where('claimed_at', '<=', now()->subMinutes(3));
                    });
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->update([
                'printer_id' => $printer->id,
                'status' => 'processing',
                'attempts' => $job->attempts + 1,
                'claimed_at' => now(),
                'last_error' => null,
            ]);

            return $job->fresh();
        });

        $printer->update(['last_seen_at' => now()]);

        return response()->json([
            'job' => $job ? [
                'id' => $job->id,
                'job_type' => $job->job_type,
                'attempt' => $job->attempts,
                'payload' => $job->payload,
            ] : null,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function complete(Request $request, int $jobId): JsonResponse
    {
        $printer = $this->authenticatePrinter($request);
        $job = PrintJob::query()->whereKey($jobId)->where('printer_id', $printer->id)->firstOrFail();

        // Idempotent: agent may retry complete after USB success if the HTTP ack was lost.
        if ($job->status === 'completed') {
            $printer->update(['last_seen_at' => now()]);

            return response()->json([
                'message' => 'تم تأكيد اكتمال الطباعة مسبقًا.',
                'status' => 'completed',
                'idempotent' => true,
            ]);
        }

        abort_unless($job->status === 'processing', 409, 'مهمة الطباعة ليست قيد التنفيذ.');

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
            'last_error' => null,
        ]);

        $printer->update(['last_seen_at' => now()]);

        return response()->json([
            'message' => 'تم تأكيد اكتمال الطباعة.',
            'status' => 'completed',
            'idempotent' => false,
        ]);
    }

    public function fail(Request $request, int $jobId): JsonResponse
    {
        $printer = $this->authenticatePrinter($request);
        $data = $request->validate(['error' => ['required', 'string', 'max:2000']]);
        $job = PrintJob::query()->whereKey($jobId)->where('printer_id', $printer->id)->firstOrFail();

        // Idempotent: ignore fail after a successful complete (late/duplicate fail).
        if ($job->status === 'completed') {
            $printer->update(['last_seen_at' => now()]);

            return response()->json([
                'message' => 'تم تجاهل بلاغ الفشل لأن المهمة مكتملة.',
                'status' => 'completed',
                'idempotent' => true,
            ]);
        }

        if (in_array($job->status, ['failed', 'pending'], true)) {
            $printer->update(['last_seen_at' => now()]);

            return response()->json([
                'message' => 'تم تسجيل حالة المهمة مسبقًا.',
                'status' => $job->status,
                'idempotent' => true,
            ]);
        }

        abort_unless($job->status === 'processing', 409, 'مهمة الطباعة ليست قيد التنفيذ.');

        $terminal = $job->attempts >= 5;
        $job->update([
            'status' => $terminal ? 'failed' : 'pending',
            'available_at' => now()->addSeconds(min(300, 5 * (2 ** $job->attempts))),
            'claimed_at' => null,
            'last_error' => $data['error'],
        ]);

        $printer->update(['last_seen_at' => now()]);

        return response()->json([
            'message' => $terminal ? 'تم إيقاف المهمة بعد خمس محاولات.' : 'تمت جدولة إعادة المحاولة.',
            'status' => $job->fresh()->status,
            'idempotent' => false,
        ]);
    }

    private function authenticatePrinter(Request $request): Printer
    {
        $code = strtoupper((string) $request->header('X-Printer-Code'));
        $token = (string) $request->header('X-Printer-Token');

        abort_if($code === '' || $token === '', 401, 'بيانات الطابعة مطلوبة.');

        $tokenHash = hash('sha256', $token);
        $printer = Printer::query()->where('code', $code)->where('device_token_hash', $tokenHash)->where('is_active', true)->first();
        abort_unless($printer && hash_equals($printer->device_token_hash, $tokenHash), 401, 'بيانات الطابعة غير صحيحة أو موقوفة.');

        return $printer;
    }
}
