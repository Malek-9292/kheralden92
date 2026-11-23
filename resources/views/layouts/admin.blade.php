<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لوحة إدارة فتوش الخالدية' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        :root{
            --brand:#1d5b3d;
            --brand-deep:#153f2d;
            --brand-mid:#2c684b;
            --brand-soft:#e8f3ec;
            --ink:#18352a;
            --muted:#6c8175;
            --line:#e5ece6;
            --surface:#ffffff;
            --page:#f3f7f4;
            --danger:#b42318;
            --radius:14px;
            --tap:44px;
            font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;
            color:var(--ink);
            background:var(--page);
            font-synthesis:none;
        }
        *{box-sizing:border-box}html,body{margin:0;max-width:100%;overflow-x:clip}
        body{background:radial-gradient(circle at top left,#e7f2ea 0%,var(--page) 42%,#eef4ef 100%);padding:env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left)}
        [x-cloak]{display:none!important}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}
        .shell{min-height:100vh;display:grid;grid-template-columns:260px 1fr}
        .sidebar{background:linear-gradient(180deg,var(--brand-deep),#1a4a34);color:#fff;padding:22px 16px;display:flex;flex-direction:column;gap:18px;position:sticky;top:0;height:100vh;min-width:0}
        .brand{font-size:clamp(17px,4vw,20px);font-weight:800;letter-spacing:.2px;line-height:1.35}.brand small{display:block;color:#b9d7c5;font-size:12px;margin-top:6px;font-weight:500}
        .nav{display:grid;gap:6px;overflow:auto;padding-inline-end:2px;-webkit-overflow-scrolling:touch}
        .nav a{padding:12px 13px;min-height:var(--tap);display:flex;align-items:center;border-radius:12px;color:#e5f2e9;font-size:14px;transition:background .15s ease,color .15s ease}
        .nav a:hover,.nav a.active{background:var(--brand-mid);color:#fff}
        .sidebar-user{color:#b9d7c5;font-size:13px;margin-top:auto;overflow-wrap:anywhere}
        .logout-btn{border:0;background:#24563d;color:#fff;border-radius:12px;padding:12px 13px;min-height:var(--tap);width:100%;cursor:pointer}
        .logout-btn:hover{background:#2d6849}
        .main{min-width:0;padding:24px clamp(12px,3vw,36px);max-width:100%}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap}
        .topbar h1{font-size:clamp(18px,4vw,22px);margin:0;line-height:1.35}.muted{color:var(--muted);font-size:13px}
        .menu-toggle{display:none;border:1px solid #d5e3d9;background:#fff;border-radius:12px;padding:10px 12px;min-height:var(--tap);min-width:var(--tap);cursor:pointer;color:var(--brand);align-items:center;justify-content:center}
        .search{position:relative;width:min(460px,100%);flex:1 1 220px}.search input{width:100%;border:1px solid #d8e3da;border-radius:12px;padding:12px 14px;min-height:var(--tap);background:#fff;outline:none}
        .search input:focus{border-color:#5d9b76;box-shadow:0 0 0 3px #dceee1}
        .results{position:absolute;top:calc(100% + 8px);right:0;left:0;background:#fff;border:1px solid #e1e9e2;border-radius:12px;box-shadow:0 12px 30px #173e2b18;z-index:30;max-height:min(420px,60vh);overflow:auto;-webkit-overflow-scrolling:touch}
        .results a{display:block;padding:12px 13px;min-height:var(--tap);border-bottom:1px solid #eef2ee}.results a:last-child{border:0}.results a:hover{background:#f3f8f3}
        .results strong{display:block;font-size:13px}.results small{color:#718176;font-size:11px}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
        .stat,.panel{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:clamp(14px,2vw,18px);box-shadow:0 3px 12px #18352a08;min-width:0}
        .stat span{color:var(--muted);font-size:13px}.stat strong{display:block;font-size:clamp(22px,4vw,28px);margin-top:10px;color:#17432f}
        .panel h2{font-size:17px;margin:0 0 14px}
        .table-wrap{overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;max-width:100%;border:1px solid var(--line);border-radius:12px;background:#fff}
        .table-wrap::-webkit-scrollbar{height:8px}.table-wrap::-webkit-scrollbar-thumb{background:#c5d7cb;border-radius:8px}
        table{width:100%;border-collapse:collapse;min-width:560px}th,td{text-align:right;padding:12px 10px;border-bottom:1px solid #edf1ed;font-size:13px;vertical-align:top}
        th{color:#718176;font-weight:600;background:#fafcfa;position:sticky;top:0}
        .status{display:inline-block;padding:5px 9px;border-radius:20px;background:#edf5ef;color:#286344;font-size:11px}
        .status.warn{background:#fff4df;color:#8a6416}.status.danger{background:#fdecec;color:#b42318}
        .empty{padding:28px;text-align:center;color:#819086;font-size:13px}
        .field{display:flex;flex-direction:column;gap:6px;font-size:13px;min-width:0}
        .field input,.field select,.field textarea{border:1px solid #d8e3da;border-radius:9px;padding:12px;min-width:0;min-height:var(--tap);background:#fff}
        .field.full{grid-column:1/-1}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .flash{padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px;overflow-wrap:anywhere}
        .flash.error{background:#fdecec;color:var(--danger)}
        .primary,.secondary{border-radius:11px;padding:11px 16px;min-height:var(--tap);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px}
        .primary{border:0;background:var(--brand);color:#fff;font-weight:700}.primary:hover{background:var(--brand-mid)}
        .secondary{border:1px solid #d9e4db;background:#fff;color:#355b42}.secondary:hover{background:#f4f8f5}
        .primary:disabled,.secondary:disabled{opacity:.65;cursor:wait}
        :focus-visible{outline:3px solid #8fcf9b;outline-offset:2px}
        .search-loading{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--muted)}
        .quick-nav{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px}
        .quick-nav a{display:block;padding:16px;border-radius:14px;background:var(--surface);border:1px solid var(--line);box-shadow:0 3px 12px #18352a08;min-height:var(--tap)}
        .quick-nav a strong{display:block;font-size:14px;margin-bottom:4px}.quick-nav a span{color:var(--muted);font-size:12px}
        .quick-nav a:hover{border-color:#b7d4c2;background:#f7fbf8}
        .backdrop{display:none;position:fixed;inset:0;background:#12291db8;z-index:40}
        /* Card-style tables on narrow screens */
        @media(max-width:720px){
            .table-wrap{border:0;background:transparent;overflow:visible}
            .table-wrap table{min-width:0;border-collapse:separate;border-spacing:0 10px}
            .table-wrap thead{display:none}
            .table-wrap tbody tr{display:block;background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px 12px;box-shadow:0 2px 8px #18352a08;margin:0}
            .table-wrap tbody td{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;border:0;padding:8px 2px;text-align:left}
            .table-wrap tbody td::before{content:attr(data-label);color:var(--muted);font-weight:600;font-size:12px;flex:0 0 42%;text-align:right}
            .table-wrap tbody td:first-child{font-weight:700;color:#17432f}
        }
        /* Drawer from RTL start (right) on tablets/phones */
        @media(max-width:1023px){
            .shell{grid-template-columns:1fr}
            .sidebar{position:fixed;top:0;right:0;bottom:0;left:auto;width:min(300px,88vw);transform:translateX(110%);transition:transform .2s ease;z-index:50;height:100dvh;padding-top:max(22px,env(safe-area-inset-top));padding-bottom:max(16px,env(safe-area-inset-bottom))}
            .shell.nav-open .sidebar{transform:translateX(0)}
            .shell.nav-open .backdrop{display:block}
            .shell.nav-open{overflow:hidden}
            .menu-toggle{display:inline-flex}
            .stats,.quick-nav{grid-template-columns:repeat(2,minmax(0,1fr))}
            .main{padding:16px 12px}
            .form-grid{grid-template-columns:1fr}
        }
        @media(max-width:560px){
            .topbar{align-items:stretch;flex-direction:column}
            .search{width:100%;flex-basis:100%}
            .stats,.quick-nav{grid-template-columns:1fr;gap:10px}
            .stat strong{font-size:24px}
        }
        @media(min-width:1024px){
            .shell{grid-template-columns:260px 1fr}
        }
        @media(min-width:1280px){
            .main{padding:28px clamp(20px,3vw,40px)}
        }
    </style>
</head>
<body>
@php($moduleManager = app(\App\Support\Modules\ModuleManager::class))
@php($currentPath = '/'.ltrim(request()->path(), '/'))
<div class="shell" x-data="{navOpen:false}" :class="{'nav-open':navOpen}">
    <div class="backdrop" @click="navOpen=false"></div>
    <aside class="sidebar">
        <div class="brand">فتوش الخالدية <small>نظام إدارة المطعم · الخالدية</small></div>
        <nav class="nav" id="admin-nav" aria-label="قائمة الإدارة" @click="navOpen=false">
            @if(auth()->user()->hasPermissionTo('dashboard.view')) <a class="{{ $currentPath === '/admin/dashboard' ? 'active' : '' }}" href="/admin/dashboard">الرئيسية</a> @endif
            @if(auth()->user()->hasPermissionTo('orders.view') && $moduleManager->enabled('Orders')) <a class="{{ str_starts_with($currentPath, '/admin/orders') ? 'active' : '' }}" href="/admin/orders">الطلبات</a> @endif
            @if(auth()->user()->hasPermissionTo('catalog.view') && $moduleManager->enabled('Catalog'))
                <a class="{{ str_starts_with($currentPath, '/admin/products') ? 'active' : '' }}" href="/admin/products">الأصناف</a>
                <a class="{{ str_starts_with($currentPath, '/admin/categories') ? 'active' : '' }}" href="/admin/categories">الأقسام</a>
            @endif
            @if(auth()->user()->hasPermissionTo('delivery.manage') && $moduleManager->enabled('Delivery')) <a class="{{ str_starts_with($currentPath, '/admin/delivery-areas') ? 'active' : '' }}" href="/admin/delivery-areas">مناطق التوصيل</a> @endif
            @if(auth()->user()->hasPermissionTo('employees.manage') && $moduleManager->enabled('Identity')) <a class="{{ str_starts_with($currentPath, '/admin/employees') ? 'active' : '' }}" href="/admin/employees">الموظفون</a> @endif
            @if(auth()->user()->hasPermissionTo('branches.manage') && $moduleManager->enabled('Branches')) <a class="{{ str_starts_with($currentPath, '/admin/branches') ? 'active' : '' }}" href="/admin/branches">الفروع</a> @endif
            @if(auth()->user()->hasPermissionTo('roles.manage') && $moduleManager->enabled('AccessControl')) <a class="{{ str_starts_with($currentPath, '/admin/roles') ? 'active' : '' }}" href="/admin/roles">الأدوار والصلاحيات</a> @endif
            @if(auth()->user()->hasPermissionTo('cashier.use') && $moduleManager->enabled('Cashier')) <a class="{{ str_starts_with($currentPath, '/admin/cashier') ? 'active' : '' }}" href="/admin/cashier">الكاشير</a> @endif
            @if(auth()->user()->hasPermissionTo('printing.manage') && $moduleManager->enabled('Printing')) <a class="{{ str_starts_with($currentPath, '/admin/printers') ? 'active' : '' }}" href="/admin/printers">الطابعات</a> @endif
            @if(auth()->user()->hasPermissionTo('settings.manage') && $moduleManager->enabled('SystemSettings')) <a class="{{ str_starts_with($currentPath, '/admin/settings') ? 'active' : '' }}" href="/admin/settings">إعدادات المطعم</a> @endif
            @if(auth()->user()->hasPermissionTo('reports.view') && $moduleManager->enabled('Reporting')) <a class="{{ str_starts_with($currentPath, '/admin/reports') ? 'active' : '' }}" href="/admin/reports">التقارير</a> @endif
            @if(auth()->user()->hasPermissionTo('modules.manage') && $moduleManager->enabled('SystemSettings')) <a class="{{ str_starts_with($currentPath, '/admin/modules') ? 'active' : '' }}" href="/admin/modules">مدير الوحدات</a> @endif
        </nav>
        <div class="sidebar-user" aria-label="المستخدم الحالي">{{ auth()->user()->name }}</div>
        <button type="button" class="logout-btn" aria-label="تسجيل الخروج من لوحة الإدارة" onclick="fetch('/admin/session',{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},credentials:'same-origin'}).then(()=>location.href='/admin/login')">تسجيل الخروج</button>
    </aside>
    <main class="main" id="admin-main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:10px">
                <button type="button" class="menu-toggle" @click="navOpen=!navOpen" :aria-expanded="navOpen.toString()" aria-controls="admin-nav" aria-label="فتح قائمة التنقل">القائمة</button>
                <div>
                    <h1>{{ $heading ?? 'لوحة التحكم' }}</h1>
                    <div class="muted">متابعة تشغيل فتوش الخالدية</div>
                </div>
            </div>
            <div class="search" x-data="{q:'',groups:{},open:false,loading:false}">
                <label for="admin-global-search" class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">بحث سريع في لوحة الإدارة</label>
                <input id="admin-global-search" type="search" placeholder="بحث سريع في الأقسام والطلبات والموظفين..." aria-label="بحث سريع في لوحة الإدارة" x-model="q"
                    @input.debounce.300ms="if(q.trim().length>=2){loading=true;fetch('/admin/search?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{groups=d.results||{};open=true}).finally(()=>loading=false)}else{groups={};open=false}">
                <span class="search-loading" x-show="loading" x-cloak role="status">جارٍ البحث...</span>
                <div class="results" x-show="open" x-cloak @click.outside="open=false" role="listbox" aria-label="نتائج البحث">
                    <template x-for="(items,group) in groups" :key="group">
                        <template x-for="item in items" :key="item.type+'-'+item.title">
                            <a :href="item.url" role="option"><strong x-text="item.title"></strong><small x-text="item.subtitle||'فتح القسم'"></small></a>
                        </template>
                    </template>
                    <div class="empty" x-show="!loading && Object.values(groups).flat().length===0" role="status">لا توجد نتائج مطابقة لبحثك</div>
                </div>
            </div>
        </header>
        {{ $slot }}
    </main>
</div>
@livewireScripts
</body>
</html>
