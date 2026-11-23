<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لوحة إدارة فتوش الخالدية' }}</title>
    @livewireStyles
    <style>
        :root{font-family:Tahoma,Arial,sans-serif;color:#18352a;background:#f4f7f4;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}a{color:inherit;text-decoration:none}
        .shell{min-height:100vh;display:grid;grid-template-columns:250px 1fr}
        .sidebar{background:#153f2d;color:#fff;padding:24px 18px;display:flex;flex-direction:column;gap:22px}
        .brand{font-size:20px;font-weight:800}.brand small{display:block;color:#b9d7c5;font-size:12px;margin-top:6px}
        .nav{display:grid;gap:7px}.nav a{padding:11px 13px;border-radius:10px;color:#e5f2e9;font-size:14px}
        .nav a:hover,.nav a.active{background:#2c684b;color:#fff}
        .main{min-width:0;padding:26px clamp(16px,3vw,38px)}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:26px}
        .topbar h1{font-size:22px;margin:0}.muted{color:#6c8175;font-size:13px}
        .search{position:relative;width:min(460px,55vw)}.search input{width:100%;border:1px solid #d8e3da;border-radius:12px;padding:12px 14px;background:#fff;font:inherit;outline:none}
        .search input:focus{border-color:#5d9b76;box-shadow:0 0 0 3px #dceee1}
        .results{position:absolute;top:calc(100% + 8px);right:0;left:0;background:#fff;border:1px solid #e1e9e2;border-radius:12px;box-shadow:0 12px 30px #173e2b18;z-index:20;max-height:420px;overflow:auto}
        .results a{display:block;padding:10px 13px;border-bottom:1px solid #eef2ee}.results a:last-child{border:0}.results a:hover{background:#f3f8f3}
        .results strong{display:block;font-size:13px}.results small{color:#718176;font-size:11px}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:24px}
        .stat,.panel{background:#fff;border:1px solid #e5ece6;border-radius:16px;padding:18px;box-shadow:0 3px 12px #18352a08}
        .stat span{color:#6f8175;font-size:13px}.stat strong{display:block;font-size:28px;margin-top:10px;color:#17432f}
        .panel h2{font-size:17px;margin:0 0 14px}.table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;min-width:580px}th,td{text-align:right;padding:12px 10px;border-bottom:1px solid #edf1ed;font-size:13px}
        th{color:#718176;font-weight:600;background:#fafcfa}.status{display:inline-block;padding:5px 9px;border-radius:20px;background:#edf5ef;color:#286344;font-size:11px}
        .empty{padding:28px;text-align:center;color:#819086;font-size:13px}
        @media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{padding:14px;gap:12px}.nav{display:flex;overflow:auto}.nav a{white-space:nowrap}.brand small{display:inline;margin-right:8px}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.main{padding:18px}}
        @media(max-width:520px){.topbar{align-items:flex-start;flex-direction:column}.search{width:100%}.stats{gap:9px}.stat{padding:14px}.stat strong{font-size:23px}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">فتوش الخالدية <small>نظام إدارة المطعم</small></div>
        <nav class="nav">
            @if(auth()->user()->hasPermissionTo('dashboard.view')) <a class="active" href="/admin/dashboard">الرئيسية</a> @endif
            @if(auth()->user()->hasPermissionTo('orders.view')) <a href="/admin/orders">الطلبات</a> @endif
            @if(auth()->user()->hasPermissionTo('catalog.view')) <a href="/admin/products">الأصناف</a><a href="/admin/categories">الأقسام</a> @endif
            @if(auth()->user()->hasPermissionTo('delivery.manage')) <a href="/admin/delivery-areas">مناطق التوصيل</a> @endif
            @if(auth()->user()->hasPermissionTo('employees.manage')) <a href="/admin/employees">الموظفون</a> @endif
            @if(auth()->user()->hasPermissionTo('branches.manage')) <a href="/admin/branches">الفروع</a> @endif
            @if(auth()->user()->hasPermissionTo('roles.manage')) <a href="/admin/roles">الأدوار والصلاحيات</a> @endif
            @if(auth()->user()->hasPermissionTo('cashier.use')) <a href="/admin/cashier">الكاشير</a> @endif
        </nav>
        <div class="muted" style="color:#b9d7c5;margin-top:auto">{{ auth()->user()->name }}</div>
        <form method="POST" action="/admin/session" onsubmit="return false">
            <button type="button" onclick="fetch('/admin/session',{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},credentials:'same-origin'}).then(()=>location.href='/admin/login')" style="border:0;background:#24563d;color:#fff;border-radius:10px;padding:10px 13px;width:100%;font:inherit;cursor:pointer">تسجيل الخروج</button>
        </form>
    </aside>
    <main class="main">
        <header class="topbar">
            <div><h1>{{ $heading ?? 'لوحة التحكم' }}</h1><div class="muted">متابعة تشغيل فتوش الخالدية</div></div>
            <div class="search" x-data="{q:'',groups:{},open:false,loading:false}">
                <input type="search" placeholder="بحث سريع في الأقسام والطلبات والموظفين..." x-model="q"
                    @input.debounce.300ms="if(q.trim().length>=2){loading=true;fetch('/admin/search?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{groups=d.results||{};open=true}).finally(()=>loading=false)}else{groups={};open=false}">
                <div class="results" x-show="open" x-cloak @click.outside="open=false">
                    <template x-for="(items,group) in groups" :key="group">
                        <template x-for="item in items" :key="item.type+'-'+item.title">
                            <a :href="item.url"><strong x-text="item.title"></strong><small x-text="item.subtitle||'فتح القسم'"></small></a>
                        </template>
                    </template>
                    <div class="empty" x-show="Object.values(groups).flat().length===0">لا توجد نتائج</div>
                </div>
            </div>
        </header>
        {{ $slot }}
    </main>
</div>
@livewireScripts
</body>
</html>
