<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول - فتوش الخالدية</title>
    <style>
        :root{font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;color:#173c2c}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#dceee2,#f7faf7 48%,#eef5f0);padding:20px}
        .card{width:min(420px,100%);background:#fff;border:1px solid #e0eae2;border-radius:22px;padding:30px;box-shadow:0 18px 55px #183d2a12}
        h1{margin:0 0 8px;font-size:24px}.sub{color:#74867a;font-size:13px;margin-bottom:25px;line-height:1.7}
        label{display:block;font-size:13px;margin:16px 0 7px}input{width:100%;padding:13px;border:1px solid #d8e3da;border-radius:11px;font:inherit;outline:none}
        input:focus{border-color:#4b9569;box-shadow:0 0 0 3px #e2f1e6}
        button{width:100%;border:0;border-radius:11px;background:#1d5b3d;color:#fff;padding:14px;font:inherit;font-weight:700;margin-top:22px;cursor:pointer}
        button:disabled{opacity:.65;cursor:wait}.error{color:#b42318;font-size:13px;min-height:20px;margin-top:12px}
        .brand{font-size:14px;font-weight:700;color:#3f7955;margin-bottom:25px}
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">فتوش الخالدية · نظام الإدارة</div>
        <h1>تسجيل دخول الموظفين</h1>
        <div class="sub">استخدم رقم هاتفك وكلمة المرور المخصصة لحسابك. لا توجد كلمة مرور افتراضية في النظام.</div>
        <form id="login-form">
            <label for="phone">رقم الهاتف</label>
            <input id="phone" name="phone" type="tel" autocomplete="username" required maxlength="30" dir="ltr">
            <label for="password">كلمة المرور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="200">
            <div class="error" id="error" role="alert"></div>
            <button id="submit" type="submit">تسجيل الدخول</button>
        </form>
    </main>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = document.getElementById('submit');
            const error = document.getElementById('error');
            button.disabled = true;
            button.textContent = 'جارٍ تسجيل الدخول...';
            error.textContent = '';
            try {
                const response = await fetch('/admin/session', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        phone: document.getElementById('phone').value.trim(),
                        password: document.getElementById('password').value,
                    }),
                });
                const data = await response.json().catch(() => ({}));
                if (response.status === 429) {
                    error.textContent = 'تم تجاوز عدد المحاولات. انتظر دقيقة ثم حاول مجددًا.';
                    return;
                }
                if (!response.ok) {
                    error.textContent = data.message || data.errors?.phone?.[0] || 'تعذر تسجيل الدخول. راجع البيانات وحاول مرة أخرى.';
                    return;
                }
                window.location.assign('/admin/dashboard');
            } catch {
                error.textContent = 'تعذر الاتصال بالخادم. حاول مرة أخرى.';
            } finally {
                button.disabled = false;
                button.textContent = 'تسجيل الدخول';
            }
        });
    </script>
</body>
</html>
