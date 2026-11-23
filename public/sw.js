const CACHE_NAME = 'fatoosh-storefront-v2';
const SHELL = [
    '/',
    '/manifest.webmanifest',
    '/icons/fatoosh.svg',
    '/images/product-placeholder.jpg',
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/api/')) return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then(response => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put('/', copy));
                return response;
            }).catch(() => caches.match('/').then(cached => cached || new Response(
                '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>فتوش الخالدية</title></head><body style="font-family:Tahoma,Arial,sans-serif;padding:24px;text-align:center;color:#18352a"><h1>فتوش الخالدية</h1><p>أنت غير متصل. أعد المحاولة عند عودة الإنترنت.</p></body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } },
            )))
        );
        return;
    }

    if (SHELL.includes(url.pathname) || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(response => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                return response;
            }))
        );
    }
});
