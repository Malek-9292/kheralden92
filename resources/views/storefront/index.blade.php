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
        :root{--brand:#2f704b;--accent:#8fcf9b;font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;color:#20392c;background:#f5f8f5;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}button,input,select,textarea{font:inherit}button{cursor:pointer}
        :focus-visible{outline:3px solid #8fcf9b;outline-offset:2px}
        .top{background:var(--brand);color:white;padding:18px max(16px,calc((100vw - 1180px)/2));display:flex;align-items:center;justify-content:space-between;gap:15px}
        .logo{font-size:23px;font-weight:900}.logo small{display:block;font-size:12px;font-weight:400;color:#d3e8d8;margin-top:5px}
        .hero{background:linear-gradient(120deg,var(--brand),var(--accent));color:white;padding:36px 20px;text-align:center}
        .hero h1{font-size:clamp(25px,4vw,38px);margin:0 0 10px}.hero p{margin:0;color:#e0eee3;font-size:14px}
        .wrap{max-width:1180px;margin:auto;padding:22px 16px 100px}
        .tools{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:18px}
        .search{flex:1;min-width:200px;padding:12px 14px;border:1px solid #dce7de;border-radius:12px;background:#fff}
        .categories{display:flex;gap:8px;overflow:auto;padding:3px 0 14px}
        .chip{white-space:nowrap;border:1px solid #d9e6dc;border-radius:22px;padding:9px 14px;background:#fff;color:#365b43}
        .chip.active{background:var(--brand);color:white;border-color:var(--brand)}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
        .product{background:#fff;border:1px solid #e4ece5;border-radius:16px;overflow:hidden;box-shadow:0 4px 14px #163b2410;display:flex;flex-direction:column}
        .product img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#edf3ed}
        .product .body{padding:12px;display:flex;flex-direction:column;gap:8px;flex:1}
        .product h3{font-size:15px;margin:0}.desc{color:#75857a;font-size:12px;line-height:1.6;min-height:30px}
        .price{font-weight:800;color:#225d3b}.add{border:0;border-radius:10px;background:var(--brand);color:white;padding:10px;font-weight:700}
        .badge-best{font-size:10px;color:#8a6416;background:#fff2d3;border-radius:20px;padding:4px 7px;width:max-content}
        .badge-bread{font-size:10px;color:#24563d;background:#e7f3ea;border-radius:20px;padding:4px 7px;width:max-content;margin-inline-start:4px}
        .detail-meta{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0;font-size:12px}
        .ingredients{background:#eef6ef;border-radius:10px;padding:12px;font-size:13px;line-height:1.8;white-space:pre-line;margin:10px 0}
        .cartbar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #dce7de;padding:12px max(16px,calc((100vw - 1180px)/2));padding-bottom:max(12px,env(safe-area-inset-bottom));display:flex;justify-content:space-between;align-items:center;gap:12px;z-index:5;box-shadow:0 -6px 20px #173d2610}
        .cartbar .cart-meta{min-width:0}.cartbar .cart-meta strong{display:block;font-size:14px}.cart-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#e7f3ea;color:#225d3b;font-size:12px;font-weight:700;margin-inline-start:6px}
        .primary{border:0;background:var(--brand);color:#fff;padding:12px 18px;border-radius:11px;font-weight:700}.primary:disabled{opacity:.55;cursor:not-allowed}
        .modal{position:fixed;inset:0;background:#12291db8;z-index:20;display:none;align-items:flex-end;justify-content:center;padding:0}
        .modal.open{display:flex}.dialog{background:#fff;border-radius:18px 18px 0 0;width:min(560px,100%);max-height:92vh;overflow:auto;padding:20px 20px max(20px,env(safe-area-inset-bottom));position:relative}
        @media(min-width:700px){.modal{align-items:center;padding:15px}.dialog{border-radius:18px;max-height:90vh}}
        .dialog h2{margin:0 0 14px;font-size:19px}.dialog-section{font-size:13px;font-weight:700;color:#355b42;margin:16px 0 8px}
        .formgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .field{display:flex;flex-direction:column;gap:6px;font-size:12px}.field input,.field select,.field textarea{border:1px solid #d9e4db;border-radius:9px;padding:10px;min-width:0}
        .field.full{grid-column:1/-1}.hint{background:#eef6ef;color:#2d6241;border-radius:10px;padding:11px;font-size:12px;line-height:1.6;margin:10px 0}
        .line{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #edf1ed;font-size:13px}
        .line .line-total{font-weight:700;color:#225d3b;white-space:nowrap}
        .qty{display:flex;gap:8px;align-items:center}.qty button{width:32px;height:32px;border:0;border-radius:8px;background:#edf4ee;color:#225d3b;font-size:16px}
        .muted{color:#718176;font-size:12px}.empty{text-align:center;padding:35px;color:#75857a;line-height:1.7}
        .sticky-actions{position:sticky;bottom:0;background:linear-gradient(180deg,rgba(255,255,255,0),#fff 28%);padding-top:18px;margin-top:8px}
        .skeleton{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
        .skeleton .card{background:#fff;border:1px solid #e4ece5;border-radius:16px;overflow:hidden;min-height:220px}
        .skeleton .ph{background:linear-gradient(90deg,#edf3ed 25%,#f7faf7 50%,#edf3ed 75%);background-size:200% 100%;animation:shimmer 1.2s infinite;height:120px}
        .skeleton .line{height:12px;margin:12px;border-radius:6px;background:#edf3ed}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .offline{background:#fff4df;color:#8a6416;text-align:center;padding:10px 14px;font-size:13px}
        .error-box{background:#fdecec;color:#b42318;border-radius:12px;padding:18px;text-align:center}
        @media(max-width:950px){.skeleton{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:650px){.skeleton{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}}
        .actions{display:flex;gap:8px;justify-content:flex-start;margin-top:16px;flex-wrap:wrap}
        .secondary{border:1px solid #d9e4db;background:#fff;color:#355b42;border-radius:10px;padding:10px 14px}
        @media(max-width:950px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:650px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.product .body{padding:10px}.product h3{font-size:13px}.desc{font-size:11px}.hero{padding:28px 16px}.wrap{padding:16px 12px 110px}.formgrid{grid-template-columns:1fr}.field.full{grid-column:auto}.cartbar{padding:10px 12px;padding-bottom:max(10px,env(safe-area-inset-bottom));flex-wrap:wrap}.cartbar .primary{flex:1;min-width:160px;width:100%}.logo{font-size:20px}.top .secondary{padding:8px 10px;font-size:12px}}
    </style>
</head>
<body>
<header class="top"><div class="logo"><img id="brandLogo" alt="شعار المطعم" hidden style="width:42px;height:42px;object-fit:contain;vertical-align:middle;margin-left:8px"><span id="brandName">فتوش الخالدية</span> <small id="brandTagline">طعم يجمعنا</small></div><div style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="secondary" id="openLastOrder" hidden aria-label="تتبع آخر طلب">آخر طلب</button><button type="button" class="secondary" id="openTrack" aria-label="تتبع طلب">تتبع طلب</button><button type="button" class="secondary" id="installBtn" hidden aria-label="تثبيت التطبيق على الجهاز">تثبيت التطبيق</button></div></header>
<div id="offlineBanner" class="offline" hidden role="status">أنت غير متصل بالإنترنت. يمكنك تصفح آخر منيو محفوظ عند توفره، ولن يُرسل الطلب حتى يعود الاتصال.</div>
<div id="iosInstallHint" hidden style="background:#eef7ef;color:#315b3c;text-align:center;padding:10px 14px;font-size:12px">على iPhone: اضغط زر المشاركة في Safari ثم اختر «إضافة إلى الشاشة الرئيسية».</div>
<section class="hero"><h1 id="heroTitle">أهلاً بيك في فتوش الخالدية</h1><p id="heroTagline">اختار طلبك واستمتع بأكلنا الطازة</p><div id="banner" hidden style="margin:16px auto 0;padding:10px 16px;border-radius:12px;width:max-content;max-width:100%;font-weight:700"></div></section>
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
<div class="cartbar" role="region" aria-label="ملخص السلة"><div class="cart-meta"><strong id="cartCount">السلة <span class="cart-badge" id="cartBadge">0</span></strong><div class="muted" id="cartTotal">0.000 د.ك</div></div><button type="button" class="primary" id="openCart" aria-haspopup="dialog">فتح السلة</button></div>

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
<div class="modal" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successTitle"><div class="dialog"><h2 id="successTitle">تم استلام طلبك بنجاح</h2><div id="successDetails" class="hint"></div><div class="actions"><button type="button" class="primary" id="trackFromSuccess">تتبع الطلب</button><button type="button" class="secondary" id="finishOrder">تمام</button></div></div></div>
<div class="modal" id="trackModal" role="dialog" aria-modal="true" aria-labelledby="trackTitle"><div class="dialog"><h2 id="trackTitle">تتبع الطلب</h2>
    <div class="formgrid">
        <label class="field" for="trackOrderNumber">رقم الطلب<input id="trackOrderNumber" maxlength="40" placeholder="مثل KHAL-261123-000001" autocomplete="off"></label>
        <label class="field" for="trackToken">رمز التتبع<input id="trackToken" maxlength="128" placeholder="الرمز الذي وصلك بعد الطلب" autocomplete="off"></label>
    </div>
    <div id="trackResult" class="hint" hidden role="status"></div>
    <div id="trackError" class="error-box" hidden role="alert" style="margin-top:12px;padding:12px;text-align:right"></div>
    <div class="actions sticky-actions"><button type="button" class="primary" id="trackSubmit">عرض الحالة</button><button type="button" class="secondary" data-close="trackModal">إغلاق</button></div>
</div></div>
<div class="modal" id="productModal" role="dialog" aria-modal="true" aria-labelledby="detailName"><div class="dialog"><h2 id="detailName"></h2><img id="detailImage" alt="" style="width:100%;max-height:260px;object-fit:cover;border-radius:12px"><div class="detail-meta" id="detailMeta"></div><div id="detailDescription" style="white-space:pre-line;line-height:1.8;font-size:14px;margin:14px 0"></div><div id="detailIngredients" class="ingredients" hidden></div><div class="price" id="detailPrice"></div><div class="actions sticky-actions"><button type="button" class="primary" id="detailAdd">أضف للسلة</button><button type="button" class="secondary" data-close="productModal">إغلاق</button></div></div></div>

<script>
(() => {
    const branch = 'KHAL';
    const state = {products:[],categories:[],areas:[],governorates:[],cart:{},category:'',search:'',pendingProduct:null,deferredInstall:null,lastOrder:null,idempotencyKey:null};
    try { state.cart = JSON.parse(localStorage.getItem('fatoosh-cart') || '{}'); } catch { state.cart = {}; }
    try { state.lastOrder = JSON.parse(localStorage.getItem('fatoosh-last-order') || 'null'); } catch { state.lastOrder = null; }
    const money = n => (Number(n)||0).toFixed(3);
    const el = id => document.getElementById(id);
    if(/iphone|ipad|ipod/i.test(navigator.userAgent)&&!window.navigator.standalone){el('iosInstallHint').hidden=false;}
    const show = id => {
        const modal=el(id);
        modal.classList.add('open');
        const focusable=modal.querySelector('button, [href], input, select, textarea');
        if(focusable) focusable.focus();
    };
    const hide = id => el(id).classList.remove('open');
    document.addEventListener('keydown',e=>{
        if(e.key!=='Escape')return;
        ['productModal','cartModal','checkoutModal','trackModal','successModal'].forEach(id=>{if(el(id).classList.contains('open'))hide(id);});
    });
    const esc = value => String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const cartItems = () => Object.values(state.cart);
    const subtotal = () => cartItems().reduce((sum,item)=>sum+Number(item.price)*item.quantity,0);
    const selectedArea = () => state.areas.find(area=>String(area.id)===el('deliveryArea').value);
    const statusLabel = s => ({pending:'قيد الانتظار',confirmed:'مؤكد',preparing:'قيد التجهيز',ready:'جاهز',out_for_delivery:'خارج للتوصيل',completed:'مكتمل',cancelled:'ملغي'}[s]||s);
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
            renderCategories();renderProducts();renderGovernorates();renderAreas();
        }catch(e){el('products').innerHTML='<div class="error-box" role="alert"><div style="margin-bottom:10px">تعذر تحميل المنيو حاليًا. تحقق من الاتصال ثم أعد المحاولة.</div><button type="button" class="primary" id="retryMenu">إعادة المحاولة</button></div>';el('retryMenu').onclick=()=>{el('products').innerHTML='<div class="skeleton" role="status" aria-label="جارٍ تحميل المنيو"><div class="card"><div class="ph"></div><div class="line"></div></div><div class="card"><div class="ph"></div><div class="line"></div></div></div>';loadMenu();};}
    }
    function setOnlineState(){
        el('offlineBanner').hidden=navigator.onLine;
        el('submitOrder').disabled=!navigator.onLine;
    }
    function applySettings(settings){
        const name=settings.restaurant_name||'فتوش الخالدية';
        document.title=name+' | الطلبات أونلاين';
        el('brandName').textContent=name;
        el('brandTagline').textContent=settings.tagline||'طعم يجمعنا';
        el('heroTitle').textContent='أهلاً بيك في '+name;
        el('heroTagline').textContent=settings.tagline||'اختار طلبك واستمتع بأكلنا الطازة';
        el('branchName').textContent=name;
        if(settings.primary_color)document.documentElement.style.setProperty('--brand',settings.primary_color);
        if(settings.accent_color)document.documentElement.style.setProperty('--accent',settings.accent_color);
        if(settings.logo_url){el('brandLogo').src=settings.logo_url;el('brandLogo').hidden=false;}
        if(settings.banner_text){
            el('banner').textContent=settings.banner_text;
            el('banner').style.color=settings.banner_text_color||'#ffffff';
            el('banner').style.background=settings.banner_background||'#2f704b';
            el('banner').style.fontSize=(Number(settings.banner_font_size)||18)+'px';
            el('banner').hidden=false;
        }
        el('storeFooter').textContent=settings.footer_text||'';
        if(settings.phone||settings.whatsapp){
            const contact=document.createElement('div');
            contact.className='muted';
            contact.textContent=[settings.phone?'هاتف: '+settings.phone:'',settings.whatsapp?'واتساب: '+settings.whatsapp:''].filter(Boolean).join(' · ');
            el('storeFooter').appendChild(contact);
        }
    }
    function renderCategories(){
        el('categories').innerHTML='<button type="button" class="chip '+(!state.category?'active':'')+'" data-category="" aria-pressed="'+(!state.category)+'">الكل</button>'+
            state.categories.map(c=>'<button type="button" class="chip '+(String(c.id)===state.category?'active':'')+'" data-category="'+c.id+'" aria-pressed="'+(String(c.id)===state.category)+'">'+esc(c.name)+'</button>').join('');
        el('categories').querySelectorAll('button').forEach(b=>b.addEventListener('click',()=>{state.category=b.dataset.category;renderCategories();renderProducts();}));
    }
    function renderProducts(){
        const term=state.search.trim().toLowerCase();
        const products=state.products.filter(p=>(!state.category||String(p.category_id)===state.category)&&(!term||(p.name+' '+(p.description||'')).toLowerCase().includes(term)));
        el('products').innerHTML=products.length?products.map(p=>{
            const img=p.primary_image?.url||p.images?.[0]?.url||'/images/product-placeholder.jpg';
            const badges=(p.is_best_seller?'<span class="badge-best">الأكثر طلبًا</span>':'')+(p.bread_label?'<span class="badge-bread">'+esc(p.bread_label)+'</span>':'');
            return '<article class="product"><img src="'+esc(img)+'" alt="'+esc(p.name)+'" loading="lazy"><div class="body">'+badges+'<h3>'+esc(p.name)+'</h3><div class="desc">'+esc(p.category?.name||'')+'</div><div class="price">'+money(p.price)+' د.ك</div><button type="button" class="secondary" data-details="'+p.id+'" aria-label="تفاصيل '+esc(p.name)+'">تفاصيل الصنف</button><button type="button" class="add" data-add="'+p.id+'" aria-label="أضف '+esc(p.name)+' للسلة">＋ أضف للسلة</button></div></article>';
        }).join(''):'<div class="empty" role="status">'+(state.products.length?'لا توجد أصناف مطابقة لبحثك أو للقسم المختار. جرّب كلمة أخرى أو اختر «الكل».':'لا توجد أصناف متاحة في المنيو حاليًا.')+'</div>';
        el('products').querySelectorAll('[data-add]').forEach(b=>b.addEventListener('click',()=>addProduct(Number(b.dataset.add))));
        el('products').querySelectorAll('[data-details]').forEach(b=>b.addEventListener('click',()=>showProductDetails(Number(b.dataset.details))));
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
        el('deliveryGovernorate').innerHTML='<option value="">اختر المحافظة</option>'+available.map(g=>'<option value="'+esc(g)+'">'+esc(g)+'</option>').join('');
    }
    function renderAreas(){
        const gov=el('deliveryGovernorate').value;
        const list=state.areas.filter(a=>!gov||a.governorate===gov);
        el('deliveryArea').innerHTML='<option value="">اختر المنطقة</option>'+list.map(a=>'<option value="'+a.id+'">'+esc(a.name)+' — رسوم '+money(a.delivery_fee)+'</option>').join('');
    }
    function addProduct(id){
        const p=state.products.find(x=>x.id===id);if(!p)return;
        if(el('fulfillment').value==='delivery'&&!el('deliveryArea').value){
            state.pendingProduct=id;show('checkoutModal');updateFulfillment();el('deliveryArea').focus();return;
        }
        if(state.cart[id])state.cart[id].quantity++;else state.cart[id]={id:p.id,name:p.name,price:Number(p.price),quantity:1};
        renderCartBar();
    }
    function changeQty(id,delta){
        if(!state.cart[id])return;state.cart[id].quantity+=delta;if(state.cart[id].quantity<=0)delete state.cart[id];renderCartBar();renderCartItems();
    }
    function renderCartBar(){
        localStorage.setItem('fatoosh-cart',JSON.stringify(state.cart));
        const count=cartItems().reduce((n,i)=>n+i.quantity,0);
        el('cartCount').innerHTML=count
            ?('السلة <span class="cart-badge" id="cartBadge">'+count+'</span>')
            :'السلة فارغة';
        el('cartTotal').textContent=money(subtotal())+' د.ك';
        el('openCart').textContent=count?('فتح السلة · '+count):'تصفح المنيو أولًا';
        el('checkoutBtn').disabled=!count;
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
        el('deliveryInfo').textContent=area?'رسوم التوصيل: '+money(area.delivery_fee)+' د.ك — الحد الأدنى للطلب: '+money(area.minimum_order)+' د.ك':'اختار المحافظة ثم المنطقة لعرض رسوم التوصيل.';
        if(delivery&&area&&subtotal()>0&&subtotal()<Number(area.minimum_order))el('deliveryInfo').textContent+=' · باقي '+money(Number(area.minimum_order)-subtotal())+' د.ك للوصول للحد الأدنى.';
        renderCartItems();
    }
    el('search').addEventListener('input',e=>{state.search=e.target.value;renderProducts();});
    el('fulfillment').addEventListener('change',updateFulfillment);
    el('deliveryGovernorate').addEventListener('change',()=>{el('deliveryArea').value='';renderAreas();updateFulfillment();});
    el('deliveryArea').addEventListener('change',()=>{updateFulfillment();if(state.pendingProduct&&el('deliveryArea').value){const id=state.pendingProduct;state.pendingProduct=null;hide('checkoutModal');addProduct(id);}});
    el('openCart').onclick=()=>{renderCartItems();show('cartModal');};
    el('checkoutBtn').onclick=()=>{if(!cartItems().length)return;hide('cartModal');show('checkoutModal');updateFulfillment();};
    const refreshLastOrderButton=()=>{el('openLastOrder').hidden=!(state.lastOrder&&state.lastOrder.order_number&&state.lastOrder.tracking_token);};
    refreshLastOrderButton();
    el('openTrack').onclick=()=>{
        if(state.lastOrder){el('trackOrderNumber').value=state.lastOrder.order_number||'';el('trackToken').value=state.lastOrder.tracking_token||'';}
        el('trackResult').hidden=true;el('trackError').textContent='';show('trackModal');
    };
    el('openLastOrder').onclick=()=>{if(!state.lastOrder)return;el('openTrack').click();el('trackSubmit').click();};
    document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>hide(b.dataset.close));
    const setCheckoutError=msg=>{const box=el('checkoutError');if(!msg){box.hidden=true;box.textContent='';return;}box.hidden=false;box.textContent=msg;};
    el('submitOrder').onclick=async()=>{
        const delivery=el('fulfillment').value==='delivery',area=selectedArea();
        setCheckoutError('');
        if(!navigator.onLine){setCheckoutError('لا يوجد اتصال بالإنترنت. أعد المحاولة عند عودة الشبكة.');return;}
        if(!cartItems().length){setCheckoutError('السلة فارغة.');return;}
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
            const token=data.tracking_token||(state.lastOrder&&state.lastOrder.order_number===data.order.order_number?state.lastOrder.tracking_token:null);
            state.lastOrder={order_number:data.order.order_number,tracking_token:token};
            if(token) localStorage.setItem('fatoosh-last-order',JSON.stringify(state.lastOrder));
            refreshLastOrderButton();
            el('successDetails').textContent='رقم الطلب: '+data.order.order_number+' · الإجمالي: '+money(data.order.total)+' د.ك'+(token?' · احتفظ برمز التتبع: '+token:' · تم تأكيد الطلب السابق بنفس المفتاح.');
            state.cart={};state.idempotencyKey=null;renderCartBar();hide('checkoutModal');show('successModal');
        }catch(e){setCheckoutError(navigator.onLine?(e.message||'تعذر إرسال الطلب.'):'انقطع الاتصال أثناء الإرسال. احتفظ بالسلة وحاول مرة أخرى.');}
        finally{btn.disabled=false;btn.textContent='تأكيد الطلب';setOnlineState();}
    };
    el('trackFromSuccess').onclick=()=>{hide('successModal');el('openTrack').click();};
    el('trackSubmit').onclick=async()=>{
        el('trackError').hidden=true;el('trackError').textContent='';el('trackResult').hidden=true;
        const orderNumber=el('trackOrderNumber').value.trim(),token=el('trackToken').value.trim();
        if(!orderNumber||!token){el('trackError').hidden=false;el('trackError').textContent='أدخل رقم الطلب ورمز التتبع.';return;}
        try{
            const res=await fetch('/api/orders/'+branch+'/'+encodeURIComponent(orderNumber),{headers:{'Accept':'application/json','X-Order-Tracking-Token':token}});
            const data=await res.json().catch(()=>({}));
            if(!res.ok) throw new Error(res.status===401?'رمز التتبع مطلوب.':res.status===404?'الطلب غير موجود أو الرمز غير صحيح.':'تعذر جلب حالة الطلب.');
            const o=data.order;
            el('trackResult').hidden=false;
            el('trackResult').textContent='الحالة: '+statusLabel(o.status)+' · النوع: '+(o.fulfillment_type==='pickup'?'استلام من المطعم':'توصيل')+' · الإجمالي: '+money(o.total)+' د.ك · الأصناف: '+(o.items||[]).map(i=>i.product_name+' ×'+i.quantity).join('، ');
        }catch(e){el('trackError').hidden=false;el('trackError').textContent=e.message;}
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
