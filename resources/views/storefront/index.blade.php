<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2f704b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="فتوش الخالدية">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="/icons/fatoosh.svg">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <title>فتوش الخالدية | الطلبات أونلاين</title>
    <style>
        :root{--brand:#2f704b;--accent:#8fcf9b;--tap:44px;font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;color:#20392c;background:#f5f8f5;font-synthesis:none}
        *{box-sizing:border-box}html,body{margin:0;max-width:100%;overflow-x:clip}button,input,select,textarea{font:inherit}button{cursor:pointer}
        :focus-visible{outline:3px solid #8fcf9b;outline-offset:2px}
        body.modal-open{overflow:hidden;touch-action:none}
        .sf-header{background:var(--brand-box-bg,#fff);border-bottom:1px solid var(--brand-box-border,#e7eee8);padding:0 0 12px;padding-top:max(0px,env(safe-area-inset-top));display:flex;flex-direction:column;align-items:stretch;gap:12px;max-width:100%}
        .sf-brand{display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:0;text-align:center;min-width:0;width:100%}
        .sf-brand-logo{width:100%;height:clamp(150px,40vw,240px);margin:0;background:var(--brand-logo-bg,#d7e6db);overflow:hidden;border-bottom:1px solid var(--brand-box-border,#e7eee8);display:flex;align-items:center;justify-content:center}
        .sf-brand-logo img{width:100%;height:100%;max-width:100%;object-fit:cover;object-position:center;border-radius:0;background:transparent;display:block;transform:scale(1.45)}
        .sf-brand-copy{width:100%;padding:16px max(12px,env(safe-area-inset-right)) 16px max(12px,env(safe-area-inset-left));background:var(--brand-box-bg,#f3f7f3);border-bottom:1px solid var(--brand-box-border,#e7eee8);box-sizing:border-box;display:block;text-align:center}
        .sf-brand .name{font-size:clamp(20px,5.2vw,26px);font-weight:900;color:var(--brand-name-color,#1d5b3d);line-height:1.3;overflow-wrap:anywhere;width:100%;display:block}
        .sf-brand .tag{font-size:13px;color:var(--brand-tagline-color,#6f8376);line-height:1.5;width:100%;margin-top:6px;display:block}
        .sf-header .sf-fulfillment,.sf-header .sf-actions{padding-inline:max(12px,env(safe-area-inset-left)) max(12px,env(safe-area-inset-right))}
        .cartbar.is-empty #openCart{display:none}
        .cartbar.is-empty{display:none}
        .sf-fulfillment{display:grid;grid-template-columns:1fr 1fr;gap:8px;width:100%;max-width:420px;margin-inline:auto}
        .sf-fulfillment button{border:1.5px solid var(--brand);background:#fff;color:var(--brand);border-radius:999px;min-height:var(--tap);padding:10px 12px;font-weight:700;font-size:13px;width:100%;line-height:1.3}
        .sf-fulfillment button.active{background:var(--brand);color:#fff}
        .sf-fulfillment button:disabled{opacity:.45;cursor:not-allowed}
        .sf-actions{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;min-width:0}
        .sf-actions .icon-btn{flex:0 0 auto;width:44px;height:44px;border:1px solid #d9e4db;border-radius:12px;background:#fff;color:#2f5a3f;display:inline-flex;align-items:center;justify-content:center;padding:0}
        .sf-actions .icon-btn.pill{width:auto;min-width:44px;padding:0 12px;border-radius:999px;font-weight:700;font-size:13px}
        .sf-actions .account-btn{flex:1 1 auto;min-width:0;min-height:44px;border:1px solid #d9e4db;border-radius:12px;background:#fff;color:#2f5a3f;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 12px;font-weight:700;font-size:13px}
        .sf-actions .account-btn span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .sf-actions svg{width:20px;height:20px;flex:0 0 auto}
        .top-tools{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;padding:0 max(12px,env(safe-area-inset-right)) 0 max(12px,env(safe-area-inset-left))}
        .top-tools .secondary{border:1px solid #d9e4db;background:#fff;color:#355b42;border-radius:10px;padding:8px 12px;min-height:40px;font-size:12px}
        .hero{background:linear-gradient(120deg,var(--brand),var(--accent));color:white;padding:28px 14px;text-align:center}
        .hero h1{font-size:clamp(22px,5.5vw,38px);margin:0 0 10px;line-height:1.35}.hero p{margin:0;color:#e0eee3;font-size:clamp(12px,3.2vw,14px);line-height:1.7}
        .wrap{max-width:1180px;margin:auto;padding:16px 12px 120px;padding-inline:max(12px,env(safe-area-inset-left)) max(12px,env(safe-area-inset-right))}
        .tools{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
        .search{flex:1 1 100%;min-width:0;min-height:var(--tap);padding:12px 14px;border:1px solid #dce7de;border-radius:12px;background:#fff}
        .categories{display:flex;gap:8px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;padding:3px 0 12px;scrollbar-width:thin;overscroll-behavior-x:contain}
        .chip{flex:0 0 auto;white-space:nowrap;border:1px solid #d9e6dc;border-radius:22px;min-height:var(--tap);padding:10px 14px;background:#fff;color:#365b43;font-size:13px}
        .chip.active{background:var(--brand);color:white;border-color:var(--brand)}
        /* Mobile-first: 1 col tiny phones, 2 cols from ~360 */
        .home-category-block{margin:0 0 26px}.home-category-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 12px}.home-category-heading h2{margin:0;font-size:1.15rem;color:var(--brand);font-weight:800}.home-category-heading button{white-space:nowrap;padding:8px 12px}.home-category-products{margin-bottom:8px}
    .grid{display:grid;grid-template-columns:1fr;gap:12px;align-items:stretch}
        .product{background:#fff;border:1px solid #e4ece5;border-radius:16px;overflow:hidden;box-shadow:0 4px 14px #163b2410;display:flex;flex-direction:column;min-width:0;height:100%}
        .product .media{background:#edf3ed;aspect-ratio:4/5;min-height:180px;display:grid;place-items:center;overflow:hidden;flex:0 0 auto}
        .product .media img{width:100%;height:100%;object-fit:cover;object-position:center;display:block}
        .product .body{padding:12px;display:flex;flex-direction:column;gap:8px;flex:1 1 auto;min-width:0;min-height:0}
        .product .badges{display:flex;flex-wrap:wrap;gap:4px;align-items:center;min-height:22px}
        .product h3{font-size:14px;margin:0;line-height:1.45;overflow-wrap:anywhere}.desc{color:#75857a;font-size:12px;line-height:1.6;min-height:1.6em}
        .price{font-weight:800;color:#225d3b}
        .product-actions{margin-top:auto;display:flex;flex-direction:column;gap:8px;padding-top:4px}
        .product-actions .secondary,.product-actions .add{width:100%}
        .add{border:0;border-radius:10px;background:var(--brand);color:white;min-height:var(--tap);padding:12px;font-weight:700;width:100%}
        .badge-best{font-size:10px;color:#8a6416;background:#fff2d3;border-radius:20px;padding:4px 7px;width:max-content;max-width:100%}
        .badge-bread{font-size:10px;color:#24563d;background:#e7f3ea;border-radius:20px;padding:4px 7px;width:max-content;max-width:100%}
        .detail-meta{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0;font-size:12px}
        .ingredients{background:#eef6ef;border-radius:10px;padding:12px;font-size:13px;line-height:1.8;white-space:pre-line;margin:10px 0;overflow-wrap:anywhere}
        .cartbar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #dce7de;padding:10px 12px;padding-bottom:max(10px,env(safe-area-inset-bottom));padding-inline:max(12px,env(safe-area-inset-left)) max(12px,env(safe-area-inset-right));display:flex;justify-content:space-between;align-items:center;gap:10px;z-index:5;box-shadow:0 -6px 20px #173d2610;flex-wrap:wrap}
        .cartbar .cart-meta{min-width:0;flex:1 1 auto}.cartbar .cart-meta strong{display:block;font-size:14px}.cart-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#e7f3ea;color:#225d3b;font-size:12px;font-weight:700;margin-inline-start:6px}
        .cartbar .primary{flex:1 1 140px;min-height:var(--tap);width:auto}
        .primary{border:0;background:var(--brand);color:#fff;padding:12px 18px;border-radius:11px;font-weight:700;min-height:var(--tap)}.primary:disabled{opacity:.55;cursor:not-allowed}
        .modal{position:fixed;inset:0;background:#12291db8;z-index:20;display:none;align-items:flex-end;justify-content:center;padding:0;padding-top:env(safe-area-inset-top)}
        .modal.open{display:flex}.category-menu-dialog{width:min(420px,100%);max-height:78vh}.category-menu-list{display:grid;grid-template-columns:1fr;gap:8px}.category-menu-list button{width:100%;min-height:48px;text-align:right;border:1px solid #d9e4db;border-radius:12px;background:#fff;color:#2f5a3f;padding:12px 14px;font-weight:700}.category-menu-list button.active{background:var(--brand);border-color:var(--brand);color:#fff}.dialog{background:#fff;border-radius:18px 18px 0 0;width:min(560px,100%);max-height:min(92vh,100%);overflow:auto;-webkit-overflow-scrolling:touch;padding:18px 16px max(18px,env(safe-area-inset-bottom));position:relative}
        .dialog h2{margin:0 0 14px;font-size:clamp(17px,4.5vw,19px)}.dialog-section{font-size:13px;font-weight:700;color:#355b42;margin:16px 0 8px}
        .formgrid{display:grid;grid-template-columns:1fr;gap:10px}
        .field{display:flex;flex-direction:column;gap:6px;font-size:12px;min-width:0}.field input,.field select,.field textarea{border:1px solid #d9e4db;border-radius:9px;padding:12px;min-width:0;min-height:var(--tap);width:100%}
        /* Keep HTML hidden effective when .field/.hint set display:flex/block */
        .field[hidden],.hint[hidden],[hidden]{display:none!important}
        .field.full{grid-column:1/-1}.hint{background:#eef6ef;color:#2d6241;border-radius:10px;padding:11px;font-size:12px;line-height:1.6;margin:10px 0;overflow-wrap:anywhere}
        .line{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid #edf1ed;font-size:13px;flex-wrap:wrap}
        .line .line-total{font-weight:700;color:#225d3b;white-space:nowrap}
        .qty{display:flex;gap:8px;align-items:center}.qty button{width:var(--tap);height:var(--tap);border:0;border-radius:8px;background:#edf4ee;color:#225d3b;font-size:18px}
        .muted{color:#718176;font-size:12px}.empty{text-align:center;padding:28px 12px;color:#75857a;line-height:1.7}
        .sticky-actions{position:sticky;bottom:0;background:linear-gradient(180deg,rgba(255,255,255,0),#fff 28%);padding-top:18px;margin-top:8px;display:flex;gap:8px;flex-wrap:wrap}
        .sticky-actions .primary,.sticky-actions .secondary{flex:1 1 140px;min-height:var(--tap)}
        .skeleton{display:grid;grid-template-columns:1fr;gap:12px;width:100%;grid-column:1/-1}
        .skeleton .card{background:#fff;border:1px solid #e4ece5;border-radius:16px;overflow:hidden;min-height:200px}
        .skeleton .ph{background:linear-gradient(90deg,#edf3ed 25%,#f7faf7 50%,#edf3ed 75%);background-size:200% 100%;animation:shimmer 1.2s infinite;aspect-ratio:4/5;min-height:180px}
        .skeleton .line{height:12px;margin:12px;border-radius:6px;background:#edf3ed}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .offline{background:#fff4df;color:#8a6416;text-align:center;padding:10px 14px;font-size:13px}
        .error-box{background:#fdecec;color:#b42318;border-radius:12px;padding:16px;text-align:center;overflow-wrap:anywhere}
        .actions{display:flex;gap:8px;justify-content:flex-start;margin-top:16px;flex-wrap:wrap}
        .secondary{border:1px solid #d9e4db;background:#fff;color:#355b42;border-radius:10px;padding:10px 14px;min-height:var(--tap)}
        .cart-area-hint{font-size:12px;color:#2f5a3f;margin-top:2px;line-height:1.45;overflow-wrap:anywhere}
        .area-pick{display:flex;flex-direction:column;gap:8px;margin-top:12px;max-height:min(48vh,360px);overflow:auto;-webkit-overflow-scrolling:touch}
        .area-pick button{width:100%;text-align:right;border:1.5px solid #d9e4db;background:#fff;border-radius:14px;padding:12px 14px;min-height:var(--tap);display:flex;flex-direction:column;gap:4px;color:#1d5b3d}
        .area-pick button:hover,.area-pick button:focus-visible{border-color:var(--brand);background:#f3f7f3}
        .area-pick .area-name{font-weight:800;font-size:14px;line-height:1.4}
        .area-pick .area-meta{font-size:12px;color:#5f7466;line-height:1.5}
        .area-pick .area-min{color:#1d5b3d;font-weight:700}
        #detailImage{width:100%;max-height:min(42vh,280px);object-fit:contain;background:#edf3ed;border-radius:12px}
        /* ≥360: two columns on small phones */
        @media(min-width:360px){.grid,.skeleton{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.search{flex:1 1 200px}}
        /* ≥700: centered dialogs + 2-col forms */
        @media(min-width:700px){
            .modal{align-items:center;padding:15px}.dialog{border-radius:18px;max-height:90vh;padding:20px}
            .formgrid{grid-template-columns:1fr 1fr}.hero{padding:36px 20px}
            .wrap{padding:22px 16px 100px}
            .sf-header{gap:14px}
            .sf-header .sf-fulfillment,.sf-header .sf-actions,.sf-brand-copy{padding-inline:max(16px,calc((100vw - 1180px)/2))}
            .sf-fulfillment{max-width:480px}
            .product .media{min-height:220px}
            .cartbar{padding-inline:max(16px,calc((100vw - 1180px)/2));flex-wrap:nowrap}.cartbar .primary{flex:0 0 auto}
        }
        /* ≥768 tablets */
        @media(min-width:768px){.grid,.skeleton{grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.product h3{font-size:15px}}
        /* ≥1024 Windows laptop */
        @media(min-width:1024px){.grid,.skeleton{grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.sf-brand .name{font-size:24px}}
        /* ≥1280 desktop */
        @media(min-width:1280px){.wrap{padding-bottom:100px}}
        @media(max-width:359px){.product .body{padding:10px}.product h3{font-size:13px}.desc{font-size:11px}.sf-fulfillment button{font-size:12px;padding:10px 8px}.sf-actions .account-btn{font-size:12px;padding:8px}}
    </style>
</head>
<body>
<header class="sf-header" role="banner">
    <div class="sf-brand">
        <div class="sf-brand-logo">
            <img id="brandLogo" src="/icons/fatoosh.svg" alt="شعار فتوش الخالدية" width="800" height="343">
        </div>
        <div class="sf-brand-copy">
            <div class="name" id="brandName">فتوش الخالدية</div>
            <div class="tag" id="brandTagline">طعم يجمعنا</div>
        </div>
    </div>
    <div class="sf-fulfillment" role="group" aria-label="طريقة الاستلام">
        <button type="button" id="fulfillmentPickup" class="active" data-fulfillment="pickup" aria-pressed="true">استلام من المطعم</button>
        <button type="button" id="fulfillmentDelivery" data-fulfillment="delivery" aria-pressed="false">التوصيل</button>
    </div>
    <div class="sf-actions" aria-label="اختصارات الصفحة">
        <button type="button" class="icon-btn" id="headerMenuBtn" aria-label="المنيو">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <button type="button" class="icon-btn pill" id="headerLangBtn" aria-label="Language">EN</button>
        <button type="button" class="icon-btn" id="headerCartBtn" aria-label="السلة">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 7h15l-1.5 9h-12z"/><path d="M6 7 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/></svg>
        </button>
    </div>
</header>
<div class="modal" id="mainCategoriesModal" role="dialog" aria-modal="true" aria-labelledby="mainCategoriesTitle"><div class="dialog category-menu-dialog"><div style="display:flex;align-items:center;justify-content:space-between;gap:12px"><h2 id="mainCategoriesTitle">الأقسام الرئيسية</h2><button type="button" class="secondary" data-close="mainCategoriesModal" aria-label="إغلاق">إغلاق</button></div><div id="mainCategoryMenuList" class="category-menu-list"></div></div></div>
<div id="storefrontTopBlock" hidden>
    <div class="top-tools">
        <button type="button" class="secondary" id="installBtn" hidden aria-label="تثبيت التطبيق على الجهاز">تثبيت التطبيق</button>
    </div>
    <div id="iosInstallHint" hidden style="background:#eef7ef;color:#315b3c;text-align:center;padding:10px 14px;font-size:12px">على iPhone: اضغط زر المشاركة في Safari ثم اختر «إضافة إلى الشاشة الرئيسية».</div>
    <section class="hero"><h1 id="heroTitle">أهلاً بيك في فتوش الخالدية</h1><p id="heroTagline">اختار طلبك واستمتع بأكلنا الطازة</p><div id="banner" hidden style="margin:16px auto 0;padding:10px 16px;border-radius:12px;width:max-content;max-width:100%;font-weight:700"></div></section>
</div>
<div id="deliveryPauseBanner" class="offline" hidden role="status" style="background:#fff4e5;color:#8a4b08;border-bottom:1px solid #f0d7a8">التوصيل متوقف مؤقتًا. يمكنك الطلب للاستلام من المطعم.</div>
<div id="offlineBanner" class="offline" hidden role="status">أنت غير متصل بالإنترنت. يمكنك تصفح آخر منيو محفوظ عند توفره، ولن يُرسل الطلب حتى يعود الاتصال.</div>
<main class="wrap" id="menuMain">
    <div class="tools"><label class="sr-only" for="search" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">بحث في المنيو</label><input id="search" class="search" type="search" placeholder="ابحث عن صنف..." aria-label="بحث في المنيو" autocomplete="off"><span id="branchName" class="muted">فتوش الخالدية</span></div>
    <nav class="categories" id="categories" aria-label="أقسام المنيو"><button type="button" class="chip active" data-category="" aria-pressed="true">الكل</button></nav>
    <div id="products" class="grid" aria-live="polite">
        <div class="skeleton" id="loadingSkeleton" role="status" aria-label="جارٍ تحميل المنيو">
            <div class="card"><div class="ph"></div><div class="line"></div><div class="line" style="width:40%"></div></div>
            <div class="card"><div class="ph"></div><div class="line"></div><div class="line" style="width:55%"></div></div>
            <div class="card"><div class="ph"></div><div class="line"></div><div class="line" style="width:35%"></div></div>
            <div class="card"><div class="ph"></div><div class="line"></div><div class="line" style="width:48%"></div></div>
        </div>
    </div>
</main>
<footer id="storeFooter" class="muted" style="text-align:center;padding:18px 14px 120px"></footer>
<div class="cartbar" role="region" aria-label="ملخص السلة"><div class="cart-meta"><strong id="cartCount">السلة <span class="cart-badge" id="cartBadge">0</span></strong><div class="muted" id="cartTotal">0.000 د.ك</div><div class="cart-area-hint" id="cartAreaHint" hidden role="status"></div></div><button type="button" class="primary" id="openCart" aria-haspopup="dialog">فتح السلة</button></div>

<div class="modal" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cartTitle"><div class="dialog">
    <h2 id="cartTitle">سلة الطلب</h2>
    <div id="cartItems"></div>
    <div id="cartSummary" class="hint"></div>
    <div class="actions sticky-actions"><button type="button" class="primary" id="checkoutBtn">متابعة لإتمام الطلب</button><button type="button" class="secondary" data-close="cartModal">متابعة التسوق</button></div>
</div></div>
<div class="modal" id="checkoutModal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle"><div class="dialog">
    <h2 id="checkoutTitle">إتمام الطلب</h2>
    <p class="muted" style="margin:0 0 12px">الدفع نقدًا عند الاستلام أو التوصيل. لا توجد بطاقات إلكترونية حاليًا.</p>
    <div class="dialog-section">طريقة الاستلام وبياناتك</div>
    <div class="formgrid">
        <label class="field full" for="fulfillment">طريقة الاستلام
            <select id="fulfillment"><option value="pickup">استلام من المطعم</option><option value="delivery">توصيل</option></select>
        </label>
        <label class="field" for="customerName">الاسم<input id="customerName" name="customer_name" required maxlength="150" autocomplete="name"></label>
        <label class="field" for="customerPhone">رقم الهاتف<input id="customerPhone" name="customer_phone" type="tel" required maxlength="30" autocomplete="tel" dir="ltr" inputmode="tel"></label>
        <label class="field" id="governorateWrap" for="deliveryGovernorate" hidden>المحافظة<select id="deliveryGovernorate" aria-label="المحافظة"><option value="">اختر المحافظة</option></select></label>
        <label class="field" id="areaWrap" for="deliveryArea" hidden>المنطقة<select id="deliveryArea" aria-label="المنطقة"><option value="">اختر المنطقة</option></select></label>
        <div id="deliveryInfo" class="hint field full" hidden role="status"></div>
        <label class="field" id="blockWrap" for="block" hidden>القطعة<input id="block" maxlength="30" autocomplete="address-level3"></label>
        <label class="field" id="streetWrap" for="street" hidden>الشارع<input id="street" maxlength="100" autocomplete="street-address"></label>
        <label class="field" id="avenueWrap" for="avenue" hidden>الجادة (اختياري)<input id="avenue" maxlength="100"></label>
        <label class="field" id="houseWrap" for="house" hidden>رقم المنزل<input id="house" maxlength="50"></label>
        <label class="field" id="floorWrap" for="floor" hidden>الدور (اختياري)<input id="floor" maxlength="30"></label>
        <label class="field" id="apartmentWrap" for="apartment" hidden>الشقة (اختياري)<input id="apartment" maxlength="30"></label>
        <label class="field full" for="notes">ملاحظات للمطبخ (اختياري)<textarea id="notes" rows="2" maxlength="2000" placeholder="مثال: بدون بصل، الاتصال عند الوصول"></textarea></label>
    </div>
    <div id="checkoutError" class="error-box" hidden role="alert" style="margin-top:12px;padding:12px;text-align:right"></div>
    <div class="actions sticky-actions"><button type="button" class="primary" id="submitOrder">تأكيد الطلب</button><button type="button" class="secondary" data-close="checkoutModal">رجوع للسلة</button></div>
</div></div>
<div class="modal" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successTitle"><div class="dialog"><h2 id="successTitle">شكرًا لك! تم استلام طلبك بنجاح</h2><div id="successDetails" class="hint" style="line-height:1.9;text-align:right"></div><div class="actions"><button type="button" class="primary" id="finishOrder">تمام</button></div></div></div>
<div class="modal" id="productModal" role="dialog" aria-modal="true" aria-labelledby="detailName"><div class="dialog"><h2 id="detailName"></h2><img id="detailImage" alt=""><div class="detail-meta" id="detailMeta"></div><div id="detailDescription" style="white-space:pre-line;line-height:1.8;font-size:14px;margin:14px 0;overflow-wrap:anywhere"></div><div id="detailIngredients" class="ingredients" hidden></div><div class="price" id="detailPrice"></div><div class="actions sticky-actions"><button type="button" class="primary" id="detailAdd">أضف للسلة</button><button type="button" class="secondary" data-close="productModal">إغلاق</button></div></div></div>
<div class="modal" id="areaGateModal" role="dialog" aria-modal="true" aria-labelledby="areaGateTitle"><div class="dialog">
    <h2 id="areaGateTitle">اختر منطقة التوصيل</h2>
    <p class="muted" style="margin:0 0 12px;line-height:1.7">قبل إضافة الأصناف، اختر منطقتك لعرض <strong>الحد الأدنى للتوصيل</strong> ورسوم التوصيل.</p>
    <label class="field full" for="gateGovernorate">المحافظة
        <select id="gateGovernorate" aria-label="المحافظة"><option value="">اختر المحافظة</option></select>
    </label>
    <div id="gateAreaList" class="area-pick" role="listbox" aria-label="مناطق التوصيل"></div>
    <div id="gateAreaEmpty" class="hint" hidden>اختر المحافظة لعرض المناطق والحد الأدنى لكل منطقة.</div>
    <div class="actions sticky-actions"><button type="button" class="secondary" data-close="areaGateModal" id="areaGateCancel">إلغاء</button></div>
</div></div>

<script>
(() => {
    const branch = 'KHAL';
    const state = {products:[],categories:[],areas:[],governorates:[],cart:{},category:'',search:'',pendingProduct:null,deferredInstall:null,idempotencyKey:null,deliveryPaused:false,deliveryPauseReason:'',featuredProductsByCategory:{}};
    try { state.cart = JSON.parse(localStorage.getItem('fatoosh-cart') || '{}'); } catch { state.cart = {}; }
    try { state.savedDelivery = JSON.parse(localStorage.getItem('fatoosh-delivery') || 'null'); } catch { state.savedDelivery = null; }
    const money = n => (Number(n)||0).toFixed(3);
    const el = id => document.getElementById(id);
    if(/iphone|ipad|ipod/i.test(navigator.userAgent)&&!window.navigator.standalone){el('iosInstallHint').hidden=false;}
    const syncBodyScroll = () => {
        const open=document.querySelector('.modal.open');
        document.body.classList.toggle('modal-open', !!open);
    };
    const show = id => {
        const modal=el(id);
        modal.classList.add('open');
        syncBodyScroll();
        modal.scrollTop=0;
        const dialog=modal.querySelector('.dialog');
        if(dialog) dialog.scrollTop=0;
        const focusable=modal.querySelector('input, select, textarea, button, [href]');
        if(focusable) focusable.focus({preventScroll:true});
    };
    const hide = id => {
        el(id).classList.remove('open');
        syncBodyScroll();
    };
    document.addEventListener('keydown',e=>{
        if(e.key!=='Escape')return;
        ['mainCategoriesModal','areaGateModal','productModal','cartModal','checkoutModal','successModal'].forEach(id=>{if(el(id).classList.contains('open'))hide(id);});
    });
    const esc = value => String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const cartItems = () => Object.values(state.cart);
    const subtotal = () => cartItems().reduce((sum,item)=>sum+Number(item.price)*item.quantity,0);
    const selectedArea = () => state.areas.find(area=>String(area.id)===el('deliveryArea').value);
    const newIdempotencyKey = () => {
        if(window.crypto?.randomUUID) return window.crypto.randomUUID()+'-'+Date.now();
        return 'ord-'+Date.now()+'-'+Math.random().toString(36).slice(2,12);
    };
    async function fetchAllProducts(){
        const collected=[];
        let page=1, lastPage=1;
        do{
            const res=await fetch('/api/catalog/'+branch+'/products?per_page=100&page='+page);
            if(!res.ok) throw new Error('تعذر تحميل بيانات المنيو');
            const body=await res.json();
            const chunk=body.products?.data||[];
            collected.push(...chunk);
            lastPage=Number(body.products?.last_page||1);
            page+=1;
        }while(page<=lastPage && page<=20);
        return collected;
    }
    async function loadMenu(){
        try{
            const [catRes,prodRes,areaRes,settingsRes]=await Promise.all([
                fetch('/api/catalog/'+branch+'/categories'),fetchAllProducts(),fetch('/api/delivery-areas/'+branch),fetch('/api/storefront/'+branch+'/settings')
            ]);
            if(!catRes.ok||!areaRes.ok) throw new Error('تعذر تحميل بيانات المنيو');
            const cats=await catRes.json(),areas=await areaRes.json();
            if(settingsRes.ok){const settingsData=await settingsRes.json();applySettings(settingsData.settings||{});}
            state.categories=cats.categories||[];state.products=prodRes||[];state.areas=areas.delivery_areas||[];
            state.governorates=areas.governorates||[...new Set(state.areas.map(a=>a.governorate))];
            Object.keys(state.cart).forEach(id=>{
                const current=state.products.find(p=>String(p.id)===String(id));
                if(!current) delete state.cart[id];
                else {state.cart[id].name=current.name;state.cart[id].price=Number(current.price);}
            });
            try{localStorage.setItem('fatoosh-cart',JSON.stringify(state.cart));}catch{}
            renderCartBar();
            if(!settingsRes.ok)el('branchName').textContent=cats.branch?.name||'فتوش الخالدية';
            renderCategories();renderProducts();renderGovernorates();renderAreas();restoreSavedDelivery();
        }catch(e){el('products').innerHTML='<div class="error-box" role="alert"><div style="margin-bottom:10px">تعذر تحميل المنيو حاليًا. تحقق من الاتصال ثم أعد المحاولة.</div><button type="button" class="primary" id="retryMenu">إعادة المحاولة</button></div>';el('retryMenu').onclick=()=>{el('products').innerHTML='<div class="skeleton" role="status" aria-label="جارٍ تحميل المنيو"><div class="card"><div class="ph"></div><div class="line"></div></div><div class="card"><div class="ph"></div><div class="line"></div></div></div>';loadMenu();};}
    }
    function setOnlineState(){
        el('offlineBanner').hidden=navigator.onLine;
        el('submitOrder').disabled=!navigator.onLine;
    }
    function applySettings(settings){
        const name=settings.restaurant_name||'فتوش الخالدية';
        state.featuredProductsByCategory=settings.featured_products_by_category||{};
        document.title=name+' | الطلبات أونلاين';
        el('brandName').textContent=name;
        el('brandTagline').textContent=settings.tagline||'طعم يجمعنا';
        el('brandLogo').alt='شعار '+name;
        el('heroTitle').textContent='أهلاً بيك في '+name;
        el('heroTagline').textContent=settings.tagline||'اختار طلبك واستمتع بأكلنا الطازة';
        el('branchName').textContent=name;
        if(settings.primary_color)document.documentElement.style.setProperty('--brand',settings.primary_color);
        if(settings.accent_color)document.documentElement.style.setProperty('--accent',settings.accent_color);
        document.documentElement.style.setProperty('--brand-box-bg',settings.brand_box_background||'#ffffff');
        document.documentElement.style.setProperty('--brand-box-border',settings.brand_box_border_color||'#e7eee8');
        document.documentElement.style.setProperty('--brand-name-color',settings.brand_name_color||'#1d5b3d');
        document.documentElement.style.setProperty('--brand-tagline-color',settings.brand_tagline_color||'#6f8376');
        document.documentElement.style.setProperty('--brand-logo-bg',settings.brand_logo_background||'#f3f7f3');
        if(settings.logo_url){el('brandLogo').src=settings.logo_url;el('brandLogo').hidden=false;}
        else{el('brandLogo').src='/icons/fatoosh.svg';el('brandLogo').hidden=false;}
        const topBlock=el('storefrontTopBlock');
        const showTop=!!settings.show_storefront_top_block;
        topBlock.hidden=!showTop;
        if(settings.banner_text){
            el('banner').textContent=settings.banner_text;
            el('banner').style.color=settings.banner_text_color||'#ffffff';
            el('banner').style.background=settings.banner_background||'#2f704b';
            el('banner').style.fontSize=(Number(settings.banner_font_size)||18)+'px';
            el('banner').hidden=false;
        }else{
            el('banner').hidden=true;
        }
        el('storeFooter').textContent=settings.footer_text||'';
        if(settings.phone||settings.whatsapp){
            const contact=document.createElement('div');
            contact.className='muted';
            contact.textContent=[settings.phone?'هاتف: '+settings.phone:'',settings.whatsapp?'واتساب: '+settings.whatsapp:''].filter(Boolean).join(' · ');
            el('storeFooter').appendChild(contact);
        }
        state.deliveryPaused=!!settings.delivery_paused;
        state.deliveryPauseReason=settings.delivery_pause_reason||'';
        const pauseBanner=el('deliveryPauseBanner');
        if(state.deliveryPaused){
            pauseBanner.hidden=false;
            pauseBanner.textContent=state.deliveryPauseReason
                ?('التوصيل متوقف مؤقتًا: '+state.deliveryPauseReason+' · الاستلام من المطعم متاح.')
                :'التوصيل متوقف مؤقتًا. يمكنك الطلب للاستلام من المطعم.';
            const deliveryOpt=el('fulfillment').querySelector('option[value="delivery"]');
            if(deliveryOpt) deliveryOpt.disabled=true;
            el('fulfillmentDelivery').disabled=true;
            if(el('fulfillment').value==='delivery'){setFulfillment('pickup');}
            else syncFulfillmentButtons();
        }else{
            pauseBanner.hidden=true;
            const deliveryOpt=el('fulfillment').querySelector('option[value="delivery"]');
            if(deliveryOpt) deliveryOpt.disabled=false;
            el('fulfillmentDelivery').disabled=false;
            syncFulfillmentButtons();
        }
    }
    function syncFulfillmentButtons(){
        const mode=el('fulfillment').value==='delivery'?'delivery':'pickup';
        el('fulfillmentPickup').classList.toggle('active',mode==='pickup');
        el('fulfillmentDelivery').classList.toggle('active',mode==='delivery');
        el('fulfillmentPickup').setAttribute('aria-pressed',mode==='pickup'?'true':'false');
        el('fulfillmentDelivery').setAttribute('aria-pressed',mode==='delivery'?'true':'false');
    }
    function setFulfillment(mode){
        if(mode==='delivery'&&state.deliveryPaused){
            el('fulfillment').value='pickup';
            syncFulfillmentButtons();
            updateFulfillment();
            return;
        }
        el('fulfillment').value=mode;
        syncFulfillmentButtons();
        updateFulfillment();
    }
    function renderMainCategoryMenu(){
        const list=el('mainCategoryMenuList');
        if(!list)return;
        const entries=[{id:'',name:'كل الأقسام'},...state.categories.map(c=>({id:String(c.id),name:c.name}))];
        list.innerHTML=entries.map(c=>'<button type="button" data-main-category="'+esc(c.id)+'" class="'+(String(c.id)===state.category?'active':'')+'">'+esc(c.name)+'</button>').join('');
        list.querySelectorAll('[data-main-category]').forEach(button=>button.addEventListener('click',()=>{
            state.category=button.dataset.mainCategory;
            state.search='';el('search').value='';
            renderCategories();renderProducts();hide('mainCategoriesModal');
            el('menuMain').scrollIntoView({behavior:'smooth',block:'start'});
        }));
    }
    function renderCategories(){
        el('categories').innerHTML='<button type="button" class="chip '+(!state.category?'active':'')+'" data-category="" aria-pressed="'+(!state.category)+'">الكل</button>'+
            state.categories.map(c=>'<button type="button" class="chip '+(String(c.id)===state.category?'active':'')+'" data-category="'+c.id+'" aria-pressed="'+(String(c.id)===state.category)+'">'+esc(c.name)+'</button>').join('');
        el('categories').querySelectorAll('button').forEach(b=>b.addEventListener('click',()=>{state.category=b.dataset.category;renderCategories();renderProducts();}));
    }
    function productCard(p){
        const img=p.primary_image?.url||p.images?.[0]?.url||'/images/product-placeholder.jpg';
        const badges='<div class="badges">'+(p.is_best_seller?'<span class="badge-best">الأكثر طلبًا</span>':'')+(p.bread_label?'<span class="badge-bread">'+esc(p.bread_label)+'</span>':'')+'</div>';
        return '<article class="product"><div class="media"><img src="'+esc(img)+'" alt="'+esc(p.name)+'" loading="lazy" decoding="async"></div><div class="body">'+badges+'<h3>'+esc(p.name)+'</h3><div class="desc">'+esc(p.category?.name||'')+'</div><div class="price">'+money(p.price)+' د.ك</div><div class="product-actions"><button type="button" class="secondary" data-details="'+p.id+'" aria-label="تفاصيل '+esc(p.name)+'">تفاصيل الصنف</button><button type="button" class="add" data-add="'+p.id+'" aria-label="أضف '+esc(p.name)+' للسلة">＋ أضف للسلة</button></div></div></article>';
    }
    function bindProductActions(root){
        root.querySelectorAll('[data-add]').forEach(b=>b.addEventListener('click',()=>addProduct(Number(b.dataset.add))));
        root.querySelectorAll('[data-details]').forEach(b=>b.addEventListener('click',()=>showProductDetails(Number(b.dataset.details))));
        root.querySelectorAll('[data-view-category]').forEach(b=>b.addEventListener('click',()=>{
            state.category=String(b.dataset.viewCategory);renderCategories();renderProducts();
            el('menuMain').scrollIntoView({behavior:'smooth',block:'start'});
        }));
    }
    function renderProducts(){
        const term=state.search.trim().toLowerCase();
        const root=el('products');
        if(!state.category&&!term){
            const sections=state.categories.map(category=>{
                const selected=state.featuredProductsByCategory[String(category.id)];
                if(!Array.isArray(selected)||!selected.length)return '';
                const ids=new Set(selected.map(String));
                const products=state.products.filter(p=>String(p.category_id)===String(category.id)&&ids.has(String(p.id)));
                if(!products.length)return '';
                return '<section class="home-category-block" aria-label="'+esc(category.name)+'"><div class="home-category-heading"><h2>'+esc(category.name)+'</h2><button type="button" class="secondary" data-view-category="'+category.id+'">عرض القسم بالكامل</button></div><div class="grid home-category-products">'+products.map(productCard).join('')+'</div></section>';
            }).join('');
            root.innerHTML=sections||'<div class="empty" role="status">لم يتم اختيار أصناف للعرض في الصفحة الرئيسية بعد. يمكنك تحديد صنفين أو 3 أصناف من كل قسم من إعدادات المطعم.</div>';
            bindProductActions(root);
            return;
        }
        const products=state.products.filter(p=>(!state.category||String(p.category_id)===state.category)&&(!term||(p.name+' '+(p.description||'')).toLowerCase().includes(term)));
        root.innerHTML=products.length?products.map(productCard).join(''):'<div class="empty" role="status">'+(state.products.length?'لا توجد أصناف مطابقة لبحثك أو للقسم المختار. جرّب كلمة أخرى أو اختر «الكل».':'لا توجد أصناف متاحة في المنيو حاليًا.')+'</div>';
        bindProductActions(root);
    }
    async function showProductDetails(id){
        const listed=state.products.find(x=>x.id===id);if(!listed)return;
        el('detailName').textContent=listed.name;
        el('detailImage').src=listed.primary_image?.url||listed.images?.[0]?.url||'/images/product-placeholder.jpg';
        el('detailImage').alt=listed.name;
        el('detailMeta').innerHTML=(listed.bread_label?'<span class="badge-bread">'+esc(listed.bread_label)+'</span>':'')+(listed.is_best_seller?'<span class="badge-best">الأكثر طلبًا</span>':'');
        el('detailDescription').textContent=listed.description||'';
        el('detailIngredients').hidden=true;el('detailIngredients').textContent='';
        el('detailPrice').textContent=money(listed.price)+' د.ك';
        el('detailAdd').onclick=()=>{hide('productModal');addProduct(id);};
        show('productModal');
        try{
            const res=await fetch('/api/catalog/'+branch+'/products/'+encodeURIComponent(listed.slug));
            if(!res.ok)return;
            const data=await res.json();
            const p=data.product||{};
            el('detailDescription').textContent=p.description||listed.description||'لا توجد تفاصيل إضافية لهذا الصنف.';
            if(p.ingredients){el('detailIngredients').hidden=false;el('detailIngredients').textContent='المكونات:\n'+p.ingredients;}
            if(p.bread_label){el('detailMeta').innerHTML='<span class="badge-bread">'+esc(p.bread_label)+'</span>'+(p.is_best_seller?'<span class="badge-best">الأكثر طلبًا</span>':'');}
        }catch(_){/* keep listed data */}
    }
    function renderGovernorates(){
        const available=state.governorates.filter(g=>state.areas.some(a=>a.governorate===g));
        const opts='<option value="">اختر المحافظة</option>'+available.map(g=>'<option value="'+esc(g)+'">'+esc(g)+'</option>').join('');
        el('deliveryGovernorate').innerHTML=opts;
        el('gateGovernorate').innerHTML=opts;
    }
    function areaOptionLabel(a){
        return esc(a.name)+' — حد أدنى '+money(a.minimum_order)+' — رسوم '+money(a.delivery_fee);
    }
    function renderAreas(){
        const gov=el('deliveryGovernorate').value;
        const list=state.areas.filter(a=>!gov||a.governorate===gov);
        const prev=el('deliveryArea').value;
        el('deliveryArea').innerHTML='<option value="">اختر المنطقة</option>'+list.map(a=>'<option value="'+a.id+'">'+areaOptionLabel(a)+'</option>').join('');
        if(prev&&list.some(a=>String(a.id)===String(prev))) el('deliveryArea').value=prev;
    }
    function persistDeliverySelection(){
        const gov=el('deliveryGovernorate').value;
        const areaId=el('deliveryArea').value;
        state.savedDelivery=areaId?{governorate:gov,areaId:String(areaId)}:null;
        try{
            if(state.savedDelivery) localStorage.setItem('fatoosh-delivery',JSON.stringify(state.savedDelivery));
            else localStorage.removeItem('fatoosh-delivery');
        }catch{}
    }
    function restoreSavedDelivery(){
        const saved=state.savedDelivery;
        if(!saved||!saved.areaId){updateCartAreaHint();return;}
        const area=state.areas.find(a=>String(a.id)===String(saved.areaId));
        if(!area){state.savedDelivery=null;try{localStorage.removeItem('fatoosh-delivery');}catch{};updateCartAreaHint();return;}
        el('deliveryGovernorate').value=area.governorate||saved.governorate||'';
        renderAreas();
        el('deliveryArea').value=String(area.id);
        el('gateGovernorate').value=el('deliveryGovernorate').value;
        updateFulfillment();
        updateCartAreaHint();
    }
    function needsDeliveryArea(){
        return el('fulfillment').value==='delivery'&&!el('deliveryArea').value;
    }
    function openAreaGate(pendingId){
        state.pendingProduct=pendingId??null;
        hide('productModal');
        hide('cartModal');
        hide('checkoutModal');
        el('gateGovernorate').value=el('deliveryGovernorate').value||'';
        renderGateAreas();
        show('areaGateModal');
    }
    function renderGateAreas(){
        const gov=el('gateGovernorate').value;
        const list=state.areas.filter(a=>!gov||a.governorate===gov);
        const empty=el('gateAreaEmpty');
        const box=el('gateAreaList');
        if(!gov){
            box.innerHTML='';
            empty.hidden=false;
            empty.textContent='اختر المحافظة لعرض المناطق والحد الأدنى لكل منطقة.';
            return;
        }
        if(!list.length){
            box.innerHTML='';
            empty.hidden=false;
            empty.textContent='لا توجد مناطق توصيل لهذه المحافظة حاليًا.';
            return;
        }
        empty.hidden=true;
        box.innerHTML=list.map(a=>'<button type="button" role="option" data-area-id="'+a.id+'"><span class="area-name">'+esc(a.name)+'</span><span class="area-meta"><span class="area-min">الحد الأدنى للتوصيل: '+money(a.minimum_order)+' د.ك</span> · رسوم التوصيل: '+money(a.delivery_fee)+' د.ك</span></button>').join('');
        box.querySelectorAll('[data-area-id]').forEach(btn=>btn.onclick=()=>selectDeliveryArea(Number(btn.dataset.areaId)));
    }
    function selectDeliveryArea(areaId){
        const area=state.areas.find(a=>Number(a.id)===Number(areaId));
        if(!area)return;
        el('deliveryGovernorate').value=area.governorate||'';
        renderAreas();
        el('deliveryArea').value=String(area.id);
        el('gateGovernorate').value=el('deliveryGovernorate').value;
        persistDeliverySelection();
        updateFulfillment();
        updateCartAreaHint();
        hide('areaGateModal');
        const pending=state.pendingProduct;
        state.pendingProduct=null;
        if(pending!=null) addProduct(pending);
    }
    function updateCartAreaHint(){
        const hint=el('cartAreaHint');
        const delivery=el('fulfillment').value==='delivery';
        const area=selectedArea();
        if(!delivery){hint.hidden=true;hint.textContent='';return;}
        hint.hidden=false;
        if(area){
            hint.textContent=area.name+' · حد أدنى '+money(area.minimum_order)+' د.ك · رسوم '+money(area.delivery_fee)+' د.ك';
        }else{
            hint.textContent='اختر منطقة التوصيل لعرض الحد الأدنى قبل إضافة الأصناف';
        }
    }
    function addProduct(id){
        const p=state.products.find(x=>x.id===id);if(!p)return;
        if(needsDeliveryArea()){openAreaGate(id);return;}
        if(state.cart[id])state.cart[id].quantity++;else state.cart[id]={id:p.id,name:p.name,price:Number(p.price),quantity:1};
        renderCartBar();
    }
    function changeQty(id,delta){
        if(!state.cart[id])return;
        if(delta>0&&needsDeliveryArea()){openAreaGate(null);return;}
        state.cart[id].quantity+=delta;if(state.cart[id].quantity<=0)delete state.cart[id];renderCartBar();renderCartItems();
    }
    function renderCartBar(){
        localStorage.setItem('fatoosh-cart',JSON.stringify(state.cart));
        const count=cartItems().reduce((n,i)=>n+i.quantity,0);
        const bar=document.querySelector('.cartbar');
        el('cartCount').innerHTML=count
            ?('السلة <span class="cart-badge" id="cartBadge">'+count+'</span>')
            :'السلة فارغة';
        el('cartTotal').textContent=money(subtotal())+' د.ك';
        el('openCart').textContent=count?('فتح السلة · '+count):'فتح السلة';
        el('openCart').hidden=!count;
        if(bar) bar.classList.toggle('is-empty',!count);
        el('checkoutBtn').disabled=!count;
        updateCartAreaHint();
    }
    function renderCartItems(){
        const items=cartItems();
        el('cartItems').innerHTML=items.length?items.map(i=>'<div class="line"><div><strong>'+esc(i.name)+'</strong><div class="muted">'+money(i.price)+' د.ك للقطعة</div></div><div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px"><div class="qty"><button type="button" data-dec="'+i.id+'" aria-label="إنقاص كمية '+esc(i.name)+'">−</button><strong aria-live="polite">'+i.quantity+'</strong><button type="button" data-inc="'+i.id+'" aria-label="زيادة كمية '+esc(i.name)+'">＋</button></div><div class="line-total">'+money(Number(i.price)*i.quantity)+' د.ك</div></div></div>').join(''):'<div class="empty" role="status">سلتك فارغة. أضف أصنافًا من المنيو ثم عد لإتمام الطلب.</div>';
        el('cartItems').querySelectorAll('[data-dec]').forEach(b=>b.onclick=()=>changeQty(Number(b.dataset.dec),-1));
        el('cartItems').querySelectorAll('[data-inc]').forEach(b=>b.onclick=()=>changeQty(Number(b.dataset.inc),1));
        const delivery=el('fulfillment').value==='delivery',area=selectedArea();
        el('cartSummary').textContent=items.length
            ?('المجموع الفرعي: '+money(subtotal())+' د.ك'+(delivery&&area?' · رسوم التوصيل المتوقعة: '+money(area.delivery_fee)+' د.ك · الحد الأدنى للمنطقة: '+money(area.minimum_order)+' د.ك':' · الدفع نقدًا عند الاستلام'))
            :'أضف أصنافًا لعرض الإجمالي.';
        el('checkoutBtn').disabled=!items.length;
    }
    function updateFulfillment(){
        const delivery=el('fulfillment').value==='delivery';
        ['governorateWrap','areaWrap','deliveryInfo','blockWrap','streetWrap','avenueWrap','houseWrap','floorWrap','apartmentWrap'].forEach(id=>el(id).hidden=!delivery);
        const area=selectedArea();
        el('deliveryInfo').textContent=area?'رسوم التوصيل: '+money(area.delivery_fee)+' د.ك — الحد الأدنى للطلب: '+money(area.minimum_order)+' د.ك':'اختار المحافظة ثم المنطقة لعرض رسوم التوصيل والحد الأدنى.';
        if(delivery&&area&&subtotal()>0&&subtotal()<Number(area.minimum_order))el('deliveryInfo').textContent+=' · باقي '+money(Number(area.minimum_order)-subtotal())+' د.ك للوصول للحد الأدنى.';
        updateCartAreaHint();
        renderCartItems();
    }
    el('search').addEventListener('input',e=>{state.search=e.target.value;renderProducts();});
    el('fulfillment').addEventListener('change',()=>{syncFulfillmentButtons();updateFulfillment();});
    el('fulfillmentPickup').onclick=()=>setFulfillment('pickup');
    el('fulfillmentDelivery').onclick=()=>setFulfillment('delivery');
    el('headerMenuBtn').onclick=()=>{renderMainCategoryMenu();show('mainCategoriesModal');};
    el('headerCartBtn').onclick=()=>{renderCartItems();el('checkoutBtn').disabled=!cartItems().length;show('cartModal');};
    el('headerLangBtn').onclick=()=>{
        const next=document.documentElement.lang==='ar'?'en':'ar';
        document.documentElement.lang=next;
        document.documentElement.dir=next==='ar'?'rtl':'ltr';
        el('headerLangBtn').textContent=next==='ar'?'EN':'ع';
        el('headerLangBtn').setAttribute('aria-label',next==='ar'?'Switch to English':'التبديل إلى العربية');
    };
    syncFulfillmentButtons();
    el('gateGovernorate').addEventListener('change',()=>{
        el('deliveryGovernorate').value=el('gateGovernorate').value;
        el('deliveryArea').value='';
        persistDeliverySelection();
        renderAreas();
        renderGateAreas();
        updateFulfillment();
    });
    el('deliveryGovernorate').addEventListener('change',()=>{
        el('deliveryArea').value='';
        el('gateGovernorate').value=el('deliveryGovernorate').value;
        persistDeliverySelection();
        renderAreas();
        updateFulfillment();
    });
    el('deliveryArea').addEventListener('change',()=>{
        persistDeliverySelection();
        updateFulfillment();
        if(state.pendingProduct&&el('deliveryArea').value){
            const id=state.pendingProduct;state.pendingProduct=null;hide('checkoutModal');hide('areaGateModal');addProduct(id);
        }
    });
    el('openCart').onclick=()=>{renderCartItems();el('checkoutBtn').disabled=!cartItems().length;show('cartModal');};
    el('checkoutBtn').onclick=()=>{
        if(!cartItems().length){renderCartItems();return;}
        if(needsDeliveryArea()){hide('cartModal');openAreaGate(null);return;}
        hide('cartModal');show('checkoutModal');updateFulfillment();el('customerName').focus({preventScroll:true});
    };
    document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>{
        if(b.dataset.close==='areaGateModal') state.pendingProduct=null;
        hide(b.dataset.close);
    });
    const setCheckoutError=msg=>{const box=el('checkoutError');if(!msg){box.hidden=true;box.textContent='';return;}box.hidden=false;box.textContent=msg;};
    el('submitOrder').onclick=async()=>{
        const delivery=el('fulfillment').value==='delivery',area=selectedArea();
        setCheckoutError('');
        if(!navigator.onLine){setCheckoutError('لا يوجد اتصال بالإنترنت. أعد المحاولة عند عودة الشبكة.');return;}
        if(!cartItems().length){setCheckoutError('السلة فارغة. أضف أصنافًا من المنيو أولًا.');return;}
        if(delivery&&state.deliveryPaused){setCheckoutError(state.deliveryPauseReason?('التوصيل متوقف مؤقتًا: '+state.deliveryPauseReason):'التوصيل متوقف مؤقتًا. اختر الاستلام من المطعم.');return;}
        if(!el('customerName').value.trim()||!el('customerPhone').value.trim()){setCheckoutError('اكتب الاسم ورقم الهاتف.');return;}
        if(delivery&&(!el('deliveryGovernorate').value||!area||!el('block').value.trim()||!el('street').value.trim()||!el('house').value.trim())){setCheckoutError('أكمل المحافظة والمنطقة والقطعة والشارع ورقم المنزل.');return;}
        if(delivery&&subtotal()<Number(area.minimum_order)){setCheckoutError('الطلب أقل من الحد الأدنى لمنطقتك ('+money(area.minimum_order)+' د.ك).');return;}
        if(!state.idempotencyKey) state.idempotencyKey=newIdempotencyKey();
        const payload={customer_name:el('customerName').value.trim(),customer_phone:el('customerPhone').value.trim(),fulfillment_type:delivery?'delivery':'pickup',items:cartItems().map(i=>({product_id:i.id,quantity:i.quantity})),customer_notes:el('notes').value.trim(),idempotency_key:state.idempotencyKey};
        if(delivery)Object.assign(payload,{delivery_area_id:area.id,block:el('block').value.trim(),street:el('street').value.trim(),avenue:el('avenue').value.trim()||null,house_number:el('house').value.trim(),floor:el('floor').value.trim()||null,apartment:el('apartment').value.trim()||null});
        const btn=el('submitOrder');btn.disabled=true;btn.textContent='جارٍ إرسال الطلب...';
        try{
            const response=await fetch('/api/orders/'+branch,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','Idempotency-Key':state.idempotencyKey},body:JSON.stringify(payload)});
            const data=await response.json();
            if(!response.ok){const first=Object.values(data.errors||{})[0]?.[0];throw new Error(first||data.message||'تعذر إرسال الطلب.');}
            const o=data.order;
            const fulfillment=o.fulfillment_type==='pickup'?'استلام من المطعم':'توصيل';
            const items=(o.items||[]).map(i=>(i.product_name||'')+' ×'+i.quantity).filter(Boolean).join('، ');
            el('successDetails').textContent='رقم الطلب: '+o.order_number+' · '+fulfillment+' · الإجمالي: '+money(o.total)+' د.ك'+(items?' · الأصناف: '+items:'');
            state.cart={};state.idempotencyKey=null;renderCartBar();hide('checkoutModal');show('successModal');
        }catch(e){setCheckoutError(navigator.onLine?(e.message||'تعذر إرسال الطلب.'):'انقطع الاتصال أثناء الإرسال. احتفظ بالسلة وحاول مرة أخرى.');}
        finally{btn.disabled=false;btn.textContent='تأكيد الطلب';setOnlineState();}
    };
    el('finishOrder').onclick=()=>{hide('successModal');el('customerName').value='';el('customerPhone').value='';el('notes').value='';};
    window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();state.deferredInstall=e;el('installBtn').hidden=false;});
    el('installBtn').onclick=async()=>{if(state.deferredInstall){state.deferredInstall.prompt();state.deferredInstall=null;el('installBtn').hidden=true;}};
    window.addEventListener('online',setOnlineState);
    window.addEventListener('offline',setOnlineState);
    setOnlineState();
    if('serviceWorker' in navigator)window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').catch(()=>{}));
    loadMenu();renderCartBar();
})();
</script>
</body>
</html>
