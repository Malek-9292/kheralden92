<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    @if($newDeviceToken)
        <div class="panel" style="border-color:#8db99a;margin-bottom:16px">
            <strong>رمز الطابعة الجديد — انسخه واحفظه الآن</strong>
            <p class="muted">لن يمكن عرض الرمز مرة أخرى. خزّنه داخل Print Agent على جهاز أندرويد ولا ترسله لأي شخص.</p>
            <input id="new-printer-token" readonly value="{{ $newDeviceToken }}" style="width:100%;padding:12px;border:1px solid #d8e3da;border-radius:9px;direction:ltr">
            <div style="display:flex;gap:8px;margin-top:10px">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-printer-token').value)" class="primary">نسخ الرمز</button>
                <button type="button" wire:click="dismissToken" class="secondary">إخفاء الرمز</button>
            </div>
        </div>
    @endif
    <div class="panel" style="margin-bottom:18px">
        <h2>تسجيل طابعة جديدة</h2>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            @if($isSystemAdmin)
                <label style="font-size:13px">الفرع
                    <select wire:model="branchId" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                        @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label style="font-size:13px">اسم الطابعة
                <input wire:model="name" required maxlength="150" placeholder="طابعة المطبخ" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">كود الجهاز
                <input wire:model="code" required maxlength="50" placeholder="KITCHEN-01" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;direction:ltr">
                @error('code')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">الاستخدام
                <select wire:model="purpose" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    <option value="kitchen">تذاكر المطبخ</option><option value="cashier">فواتير الكاشير</option>
                </select>
            </label>
            <label style="font-size:13px">طريقة الاتصال
                <select wire:model="connectionType" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    <option value="usb">USB</option><option value="network">شبكة / Wi-Fi</option>
                </select>
            </label>
            <div style="grid-column:1/-1"><button type="submit" wire:loading.attr="disabled" class="primary">تسجيل الطابعة وإنشاء رمز الجهاز</button></div>
        </form>
    </div>
    <div class="panel">
        <h2>الطابعات المسجلة</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>الطابعة</th><th>الكود</th><th>الاستخدام</th><th>الاتصال</th><th>المهام المعلقة</th><th>آخر اتصال</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                @forelse($printers as $printer)
                    <tr wire:key="printer-{{ $printer->id }}">
                        <td data-label="الطابعة"><strong>{{ $printer->name }}</strong></td>
                        <td data-label="الكود" dir="ltr">{{ $printer->code }}</td>
                        <td data-label="الاستخدام">{{ $printer->purpose==='kitchen' ? 'المطبخ' : 'الكاشير' }}</td>
                        <td data-label="الاتصال">{{ strtoupper($printer->connection_type) }}</td>
                        <td data-label="المهام المعلقة">{{ $printer->pending_jobs_count }}</td>
                        <td data-label="آخر اتصال">{{ $printer->last_seen_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') ?? 'لم تتصل بعد' }}</td>
                        <td data-label="الحالة"><span class="status">{{ $printer->is_active ? 'نشطة' : 'متوقفة' }}</span></td>
                        <td data-label="إجراءات"><div style="display:flex;gap:6px;flex-wrap:wrap">
                            <button wire:click="rotateToken({{ $printer->id }})" wire:confirm="تغيير رمز الطابعة سيوقف الرمز القديم. متابعة؟" class="secondary" style="padding:7px 9px;font-size:11px">تغيير الرمز</button>
                            <button wire:click="toggleActive({{ $printer->id }})" wire:confirm="تأكيد تغيير حالة الطابعة؟" class="secondary" style="padding:7px 9px;font-size:11px">{{ $printer->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty">لم يتم تسجيل طابعات بعد</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $printers->links() }}</div>
    </div>
    @if($failedJobs->isNotEmpty())
        <div class="panel" style="margin-top:16px">
            <h2>مهام طباعة فشلت وتحتاج مراجعة</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>رقم الطلب</th><th>نوع المهمة</th><th>آخر خطأ</th><th>إجراء</th></tr></thead>
                <tbody>
                @foreach($failedJobs as $job)
                    <tr wire:key="failed-print-job-{{ $job->id }}">
                        <td data-label="رقم الطلب">{{ $job->order?->order_number }}</td>
                        <td data-label="نوع المهمة">{{ $job->job_type==='kitchen_ticket' ? 'تذكرة مطبخ' : 'فاتورة عميل' }}</td>
                        <td data-label="آخر خطأ">{{ \Illuminate\Support\Str::limit($job->last_error,90) }}</td>
                        <td data-label="إجراء"><button wire:click="retryFailedJob({{ $job->id }})" wire:confirm="إعادة المهمة للطباعة من البداية؟" class="secondary">إعادة المحاولة</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    @endif
    <div class="panel" style="margin-top:16px">
        <h2>إعداد Print Agent</h2>
        <p class="muted">يستخدم جهاز الطباعة الرمز السري وكود الجهاز للاتصال بهذه الواجهات:</p>
        <div dir="ltr" style="font-family:monospace;font-size:12px;line-height:2;overflow-wrap:anywhere">
            POST /api/print-agent/jobs/claim<br>
            POST /api/print-agent/jobs/{jobId}/complete<br>
            POST /api/print-agent/jobs/{jobId}/fail
        </div>
        <p class="muted">يتم إرسال التذكرة بصيغة JSON. ربط الطابعة الفعلية عبر USB على أندرويد يحتاج تثبيت Print Agent ومنحه صلاحية USB واختباره على طابعة Epson TM-T20.</p>
    </div>
</section>
