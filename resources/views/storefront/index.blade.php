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
    <title>فتوش الخالدية | الطلبات أونلاين</title>
    <style>
        :root{--brand:#2f704b;--accent:#8fcf9b;font-family:Tahoma,Arial,sans-serif;color:#20392c;background:#f5f8f5;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}button,input,select,textarea{font:inherit}button{cursor:pointer}
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
        .cartbar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #dce7de;padding:12px max(16px,calc((100vw - 1180px)/2));display:flex;justify-content:space-between;align-items:center;gap:15px;z-index:5;box-shadow:0 -6px 20px #173d2610}
        .primary{border:0;background:var(--brand);color:#fff;padding:12px 18px;border-radius:11px;font-weight:700}
        .modal{position:fixed;inset:0;background:#12291db8;z-index:20;display:none;align-items:center;justify-content:center;padding:15px}
        .modal.open{display:flex}.dialog{background:#fff;border-radius:18px;width:min(560px,100%);max-height:90vh;overflow:auto;padding:20px}
        .dialog h2{margin:0 0 14px;font-size:19px}.formgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .field{display:flex;flex-direction:column;gap:6px;font-size:12px}.field input,.field select,.field textarea{border:1px solid #d9e4db;border-radius:9px;padding:10px;min-width:0}
        .field.full{grid-column:1/-1}.hint{background:#eef6ef;color:#2d6241;border-radius:10px;padding:11px;font-size:12px;line-height:1.6;margin:10px 0}
        .line{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #edf1ed;font-size:13px}
        .qty{display:flex;gap:8px;align-items:center}.qty button{width:27px;height:27px;border:0;border-radius:7px;background:#edf4ee;color:#225d3b}
        .muted{color:#718176;font-size:12px}.empty{text-align:center;padding:35px;color:#75857a}
        .actions{display:flex;gap:8px;justify-content:flex-start;margin-top:16px;flex-wrap:wrap}
        .secondary{border:1px solid #d9e4db;background:#fff;color:#355b42;border-radius:10px;padding:10px 14px}
        @media(max-width:950px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:650px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.product .body{padding:10px}.product h3{font-size:13px}.desc{font-size:11px}.hero{padding:28px 16px}.wrap{padding:16px 12px 100px}.formgrid{grid-template-columns:1fr}.field.full{grid-column:auto}.cartbar{padding:10px 12px}.logo{font-size:20px}}
    </style>
</head>
<body>
<header class="top"><div class="logo"><img id="brandLogo" alt="شعار المطعم" hidden style="width:42px;height:42px;object-fit:contain;vertical-align:middle;margin-left:8px"><span id="brandName">فتوش الخالدية</span> <small id="brandTagline">طعم يجمعنا</small></div><button class="secondary" id="installBtn" hidden>تثبيت التطبيق</button></header>
<div id="iosInstallHint" hidden style="background:#eef7ef;color:#315b3c;text-align:center;padding:10px 14px;font-size:12px">على iPhone: اضغط زر المشاركة في Safari ثم اختر «إضافة إلى الشاشة الرئيسية».</div>
<section class="hero"><h1 id="heroTitle">أهلاً بيك في فتوش الخالدية</h1><p id="heroTagline">اختار طلبك واستمتع بأكلنا الطازة</p><div id="banner" hidden style="margin:16px auto 0;padding:10px 16px;border-radius:12px;width:max-content;max-width:100%;font-weight:700"></div></section>
<main class="wrap">
    <div class="tools"><input id="search" class="search" type="search" placeholder="ابحث عن صنف..."><span id="branchName" class="muted">فتوش الخالدية</span></div>
    <nav class="categories" id="categories"><button class="chip active" data-category="">الكل</button></nav>
    <div id="products" class="grid"><div class="empty">جارٍ تحميل المنيو...</div></div>
</main>
<footer id="storeFooter" class="muted" style="text-align:center;padding:18px 14px 105px"></footer>
<div class="cartbar"><div><strong id="cartCount">السلة (0)</strong><div class="muted" id="cartTotal">0.000 د.ك</div></div><button class="primary" id="openCart">عرض السلة وإتمام الطلب</button></div>

<div class="modal" id="cartModal"><div class="dialog"><h2>سلة الطلب</h2><div id="cartItems"></div><div id="cartSummary" class="hint"></div><div class="actions"><button class="primary" id="checkoutBtn">إتمام الطلب</button><button class="secondary" data-close="cartModal">متابعة التسوق</button></div></div></div>
<div class="modal" id="checkoutModal"><div class="dialog"><h2>بيانات الطلب</h2>
    <div class="formgrid">
        <label class="field full">طريقة الاستلام
            <select id="fulfillment"><option value="pickup">استلام من المطعم</option><option value="delivery">توصيل</option></select>
        </label>
        <label class="field">الاسم<input id="customerName" required maxlength="150"></label>
        <label class="field">رقم الهاتف<input id="customerPhone" type="tel" required maxlength="30"></label>
        <label class="field full" id="areaWrap" hidden>منطقة التوصيل<select id="deliveryArea"><option value="">اختر المنطقة</option></select></label>
        <div id="deliveryInfo" class="hint field full" hidden></div>
        <label class="field" id="blockWrap" hidden>القطعة<input id="block" maxlength="30"></label>
        <label class="field" id="streetWrap" hidden>الشارع<input id="street" maxlength="100"></label>
        <label class="field" id="avenueWrap" hidden>الجادة (اختياري)<input id="avenue" maxlength="100"></label>
        <label class="field" id="houseWrap" hidden>رقم المنزل<input id="house" maxlength="50"></label>
        <label class="field" id="floorWrap" hidden>الدور (اختياري)<input id="floor" maxlength="30"></label>
        <label class="field" id="apartmentWrap" hidden>الشقة (اختياري)<input id="apartment" maxlength="30"></label>
        <label class="field full">ملاحظات (اختياري)<textarea id="notes" rows="2" maxlength="2000"></textarea></label>
    </div>
    <div id="checkoutError" class="muted" style="color:#b42318;margin-top:10px"></div>
    <div class="actions"><button class="primary" id="submitOrder">تأكيد الطلب</button><button class="secondary" data-close="checkoutModal">رجوع</button></div>
</div></div>
<div class="modal" id="successModal"><div class="dialog"><h2>تم استلام طلبك بنجاح 🎉</h2><div id="successDetails" class="hint"></div><button class="primary" id="finishOrder">تمام</button></div></div>
<div class="modal" id="productModal"><div class="dialog"><h2 id="detailName"></h2><img id="detailImage" alt="" style="width:100%;max-height:260px;object-fit:cover;border-radius:12px"><div class="detail-meta" id="detailMeta"></div><div id="detailDescription" style="white-space:pre-line;line-height:1.8;font-size:14px;margin:14px 0"></div><div id="detailIngredients" class="ingredients" hidden></div><div class="price" id="detailPrice"></div><div class="actions"><button class="primary" id="detailAdd">أضف للسلة</button><button class="secondary" data-close="productModal">إغلاق</button></div></div></div>

<script>
(() => {
    const branch = 'KHAL';
    const state = {products:[],categories:[],areas:[],cart:{},category:'',search:'',pendingProduct:null,deferredInstall:null};
    try { state.cart = JSON.parse(localStorage.getItem('fatoosh-cart') || '{}'); } catch { state.cart = {}; }
    const money = n => (Number(n)||0).toFixed(3);
    const el = id => document.getElementById(id);
    if(/iphone|ipad|ipod/i.test(navigator.userAgent)&&!window.navigator.standalone){el('iosInstallHint').hidden=false;}
    const show = id => el(id).classList.add('open');
    const hide = id => el(id).classList.remove('open');
    const esc = value => String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const cartItems = () => Object.values(state.cart);
    const subtotal = () => cartItems().reduce((sum,item)=>sum+Number(item.price)*item.quantity,0);
    const selectedArea = () => state.areas.find(area=>String(area.id)===el('deliveryArea').value);
    async function loadMenu(){
        try{
            const [catRes,prodRes,areaRes,settingsRes]=await Promise.all([
                fetch('/api/catalog/'+branch+'/categories'),fetch('/api/catalog/'+branch+'/products?per_page=60'),fetch('/api/delivery-areas/'+branch),fetch('/api/storefront/'+branch+'/settings')
            ]);
            if(!catRes.ok||!prodRes.ok||!areaRes.ok) throw new Error('تعذر تحميل بيانات المنيو');
            const cats=await catRes.json(),prods=await prodRes.json(),areas=await areaRes.json();
            if(settingsRes.ok){const settingsData=await settingsRes.json();applySettings(settingsData.settings||{});}
            state.categories=cats.categories||[];state.products=prods.products?.data||[];state.areas=areas.delivery_areas||[];
            Object.keys(state.cart).forEach(id=>{
                const current=state.products.find(p=>String(p.id)===String(id));
                if(!current) delete state.cart[id];
                else {state.cart[id].name=current.name;state.cart[id].price=Number(current.price);}
            });
            renderCartBar();
            if(!settingsRes.ok)el('branchName').textContent=cats.branch?.name||'فتوش الخالدية';
            renderCategories();renderProducts();renderAreas();
        }catch(e){el('products').innerHTML='<div class="empty">تعذر تحميل المنيو حاليًا. حدّث الصفحة وحاول مرة أخرى.</div>';}
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
        el('categories').innerHTML='<button class="chip '+(!state.category?'active':'')+'" data-category="">الكل</button>'+
            state.categories.map(c=>'<button class="chip '+(String(c.id)===state.category?'active':'')+'" data-category="'+c.id+'">'+esc(c.name)+'</button>').join('');
        el('categories').querySelectorAll('button').forEach(b=>b.addEventListener('click',()=>{state.category=b.dataset.category;renderCategories();renderProducts();}));
    }
    function renderProducts(){
        const term=state.search.trim().toLowerCase();
        const products=state.products.filter(p=>(!state.category||String(p.category_id)===state.category)&&(!term||(p.name+' '+(p.description||'')).toLowerCase().includes(term)));
        el('products').innerHTML=products.length?products.map(p=>{
            const img=p.primary_image?.url||p.images?.[0]?.url||'/images/product-placeholder.jpg';
            const badges=(p.is_best_seller?'<span class="badge-best">الأكثر طلبًا</span>':'')+(p.bread_label?'<span class="badge-bread">'+esc(p.bread_label)+'</span>':'');
            return '<article class="product"><img src="'+esc(img)+'" alt="'+esc(p.name)+'" loading="lazy"><div class="body">'+badges+'<h3>'+esc(p.name)+'</h3><div class="desc">'+esc(p.category?.name||'')+'</div><div class="price">'+money(p.price)+' د.ك</div><button class="secondary" data-details="'+p.id+'">تفاصيل الصنف</button><button class="add" data-add="'+p.id+'">＋ أضف للسلة</button></div></article>';
        }).join(''):'<div class="empty">لا توجد أصناف مطابقة لبحثك</div>';
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
    function renderAreas(){
        el('deliveryArea').innerHTML='<option value="">اختر المنطقة</option>'+state.areas.map(a=>'<option value="'+a.id+'">'+esc(a.governorate+' - '+a.name)+'</option>').join('');
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
    function renderCartBar(){localStorage.setItem('fatoosh-cart',JSON.stringify(state.cart));const count=cartItems().reduce((n,i)=>n+i.quantity,0);el('cartCount').textContent='السلة ('+count+')';el('cartTotal').textContent=money(subtotal())+' د.ك';}
    function renderCartItems(){
        const items=cartItems();
        el('cartItems').innerHTML=items.length?items.map(i=>'<div class="line"><div><strong>'+esc(i.name)+'</strong><div class="muted">'+money(i.price)+' د.ك</div></div><div class="qty"><button data-dec="'+i.id+'">−</button><strong>'+i.quantity+'</strong><button data-inc="'+i.id+'">＋</button></div></div>').join(''):'<div class="empty">السلة فارغة</div>';
        el('cartItems').querySelectorAll('[data-dec]').forEach(b=>b.onclick=()=>changeQty(Number(b.dataset.dec),-1));
        el('cartItems').querySelectorAll('[data-inc]').forEach(b=>b.onclick=()=>changeQty(Number(b.dataset.inc),1));
        const delivery=el('fulfillment').value==='delivery',area=selectedArea();
        el('cartSummary').textContent='المجموع: '+money(subtotal())+' د.ك'+(delivery&&area?' · رسوم التوصيل: '+money(area.delivery_fee)+' د.ك · الحد الأدنى: '+money(area.minimum_order)+' د.ك':'');
    }
    function updateFulfillment(){
        const delivery=el('fulfillment').value==='delivery';
        ['areaWrap','deliveryInfo','blockWrap','streetWrap','avenueWrap','houseWrap','floorWrap','apartmentWrap'].forEach(id=>el(id).hidden=!delivery);
        const area=selectedArea();
        el('deliveryInfo').textContent=area?'رسوم التوصيل: '+money(area.delivery_fee)+' د.ك — الحد الأدنى للطلب: '+money(area.minimum_order)+' د.ك':'اختار منطقتك لعرض رسوم التوصيل والحد الأدنى للطلب.';
        if(delivery&&area&&subtotal()>0&&subtotal()<Number(area.minimum_order))el('deliveryInfo').textContent+=' · باقي '+money(Number(area.minimum_order)-subtotal())+' د.ك للوصول للحد الأدنى.';
        renderCartItems();
    }
    el('search').addEventListener('input',e=>{state.search=e.target.value;renderProducts();});
    el('fulfillment').addEventListener('change',updateFulfillment);
    el('deliveryArea').addEventListener('change',()=>{updateFulfillment();if(state.pendingProduct&&el('deliveryArea').value){const id=state.pendingProduct;state.pendingProduct=null;hide('checkoutModal');addProduct(id);}});
    el('openCart').onclick=()=>{renderCartItems();show('cartModal');};
    el('checkoutBtn').onclick=()=>{if(!cartItems().length)return;hide('cartModal');show('checkoutModal');updateFulfillment();};
    document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>hide(b.dataset.close));
    el('submitOrder').onclick=async()=>{
        const delivery=el('fulfillment').value==='delivery',area=selectedArea();
        el('checkoutError').textContent='';
        if(!cartItems().length){el('checkoutError').textContent='السلة فارغة.';return;}
        if(!el('customerName').value.trim()||!el('customerPhone').value.trim()){el('checkoutError').textContent='اكتب الاسم ورقم الهاتف.';return;}
        if(delivery&&(!area||!el('block').value.trim()||!el('street').value.trim()||!el('house').value.trim())){el('checkoutError').textContent='أكمل المنطقة والقطعة والشارع ورقم المنزل.';return;}
        if(delivery&&subtotal()<Number(area.minimum_order)){el('checkoutError').textContent='الطلب أقل من الحد الأدنى لمنطقتك ('+money(area.minimum_order)+' د.ك).';return;}
        const payload={customer_name:el('customerName').value.trim(),customer_phone:el('customerPhone').value.trim(),fulfillment_type:delivery?'delivery':'pickup',items:cartItems().map(i=>({product_id:i.id,quantity:i.quantity})),customer_notes:el('notes').value.trim()};
        if(delivery)Object.assign(payload,{delivery_area_id:area.id,block:el('block').value.trim(),street:el('street').value.trim(),avenue:el('avenue').value.trim()||null,house_number:el('house').value.trim(),floor:el('floor').value.trim()||null,apartment:el('apartment').value.trim()||null});
        const btn=el('submitOrder');btn.disabled=true;btn.textContent='جارٍ إرسال الطلب...';
        try{
            const response=await fetch('/api/orders/'+branch,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)});
            const data=await response.json();
            if(!response.ok){const first=Object.values(data.errors||{})[0]?.[0];throw new Error(first||data.message||'تعذر إرسال الطلب.');}
            el('successDetails').textContent='رقم الطلب: '+data.order.order_number+' · الإجمالي: '+money(data.order.total)+' د.ك. احتفظ برمز التتبع لمتابعة الطلب: '+data.tracking_token;
            state.cart={};renderCartBar();hide('checkoutModal');show('successModal');
        }catch(e){el('checkoutError').textContent=e.message;}
        finally{btn.disabled=false;btn.textContent='تأكيد الطلب';}
    };
    el('finishOrder').onclick=()=>{hide('successModal');el('customerName').value='';el('customerPhone').value='';el('notes').value='';};
    window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();state.deferredInstall=e;el('installBtn').hidden=false;});
    el('installBtn').onclick=async()=>{if(state.deferredInstall){state.deferredInstall.prompt();state.deferredInstall=null;el('installBtn').hidden=true;}};
    if('serviceWorker' in navigator)window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').catch(()=>{}));
    loadMenu();renderCartBar();
})();
</script>
</body>
</html>
