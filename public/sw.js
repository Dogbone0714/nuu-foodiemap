// 緩存版本控制
const CACHE_VERSION = 'v1';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const API_CACHE = `api-${CACHE_VERSION}`;

// 靜態資源緩存
const STATIC_ASSETS = [
    '/',
    '/css/responsive.css',
    '/js/app.js',
    '/manifest.json',
    '/offline',
    '/icons/icon-72x72.png',
    '/icons/icon-96x96.png',
    '/icons/icon-128x128.png',
    '/icons/icon-144x144.png',
    '/icons/icon-152x152.png',
    '/icons/icon-192x192.png',
    '/icons/icon-384x384.png',
    '/icons/icon-512x512.png'
];

// API 端點緩存配置
const API_ENDPOINTS = {
    '/api/places': { maxAge: 3600 }, // 1小時
    '/api/reviews': { maxAge: 1800 }, // 30分鐘
    '/api/user': { maxAge: 300 } // 5分鐘
};

// 安裝 Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        Promise.all([
            // 緩存靜態資源
            caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_ASSETS)),
            // 預加載離線頁面
            caches.open(STATIC_CACHE).then(cache => 
                fetch('/offline').then(response => 
                    cache.put('/offline', response)
                )
            )
        ])
    );
});

// 啟動 Service Worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // 刪除舊版本的緩存
                    if (cacheName.startsWith('static-') && cacheName !== STATIC_CACHE) {
                        return caches.delete(cacheName);
                    }
                    if (cacheName.startsWith('dynamic-') && cacheName !== DYNAMIC_CACHE) {
                        return caches.delete(cacheName);
                    }
                    if (cacheName.startsWith('api-') && cacheName !== API_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// 攔截請求
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // 處理 API 請求
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleApiRequest(event.request));
        return;
    }

    // 處理靜態資源請求
    event.respondWith(handleStaticRequest(event.request));
});

// 處理 API 請求
async function handleApiRequest(request) {
    const url = new URL(request.url);
    const endpoint = url.pathname;
    const config = API_ENDPOINTS[endpoint];

    if (!config) {
        return fetch(request);
    }

    try {
        // 嘗試從緩存獲取
        const cache = await caches.open(API_CACHE);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            const cachedData = await cachedResponse.json();
            const cacheTime = new Date(cachedData.timestamp).getTime();
            const now = Date.now();
            const age = (now - cacheTime) / 1000;

            // 檢查緩存是否過期
            if (age < config.maxAge) {
                return new Response(JSON.stringify(cachedData.data), {
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        }

        // 發送新的網絡請求
        const response = await fetch(request);
        const data = await response.json();

        // 更新緩存
        const cacheData = {
            data: data,
            timestamp: new Date().toISOString()
        };

        await cache.put(request, new Response(JSON.stringify(cacheData), {
            headers: { 'Content-Type': 'application/json' }
        }));

        return new Response(JSON.stringify(data), {
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (error) {
        // 如果請求失敗，嘗試返回緩存數據
        const cache = await caches.open(API_CACHE);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            const cachedData = await cachedResponse.json();
            return new Response(JSON.stringify(cachedData.data), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        throw error;
    }
}

// 處理靜態資源請求
async function handleStaticRequest(request) {
    try {
        // 嘗試從緩存獲取
        const staticCache = await caches.open(STATIC_CACHE);
        const cachedResponse = await staticCache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        // 發送網絡請求
        const response = await fetch(request);
        
        // 緩存新的響應
        const dynamicCache = await caches.open(DYNAMIC_CACHE);
        dynamicCache.put(request, response.clone());

        return response;
    } catch (error) {
        // 如果請求失敗且是頁面請求，返回離線頁面
        if (request.mode === 'navigate') {
            const staticCache = await caches.open(STATIC_CACHE);
            const offlineResponse = await staticCache.match('/offline');
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        throw error;
    }
}

// 推送通知處理
self.addEventListener('push', event => {
    const options = {
        body: event.data.text(),
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: '查看詳情',
                icon: '/icons/icon-72x72.png'
            },
            {
                action: 'close',
                title: '關閉',
                icon: '/icons/icon-72x72.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('NUU FoodieMap 通知', options)
    );
});

// 通知點擊處理
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
}); 