// =====================================================
// SERVICE WORKER - Caching & Offline Support
// =====================================================

const CACHE_NAME = "grip-and-grin-v1"
const STATIC_CACHE = "static-v1"
const DYNAMIC_CACHE = "dynamic-v1"

// Assets to cache immediately
const STATIC_ASSETS = [
    "/",
    "/css/base.css",
    "/css/breakpoints.css",
    "/css/components.css",
    "/css/layout.css",
    "/css/grid-system.css",
    "/css/performance.css",
    "/js/performance.js",
    "/logo.svg",
    "/offline.html",
]

// Install event - cache static assets
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => {
                return cache.addAll(STATIC_ASSETS)
            })
            .then(() => {
                return self.skipWaiting()
            }),
    )
})

// Activate event - clean up old caches
self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            return caches.delete(cacheName)
                        }
                    }),
                )
            })
            .then(() => {
                return self.clients.claim()
            }),
    )
})

// Fetch event - serve from cache, fallback to network
self.addEventListener("fetch", (event) => {
    const { request } = event
    const url = new URL(request.url)

    // Handle different types of requests
    if (request.method === "GET") {
        // Static assets - cache first
        if (
            STATIC_ASSETS.includes(url.pathname) ||
            url.pathname.startsWith("/css/") ||
            url.pathname.startsWith("/js/") ||
            url.pathname.endsWith(".svg")
        ) {
            event.respondWith(
                caches
                    .match(request)
                    .then((response) => {
                        return (
                            response ||
                            fetch(request).then((fetchResponse) => {
                                return caches.open(STATIC_CACHE).then((cache) => {
                                    cache.put(request, fetchResponse.clone())
                                    return fetchResponse
                                })
                            })
                        )
                    })
                    .catch(() => {
                        // Return offline page for navigation requests
                        if (request.mode === "navigate") {
                            return caches.match("/offline.html")
                        }
                    }),
            )
        }
        // Images - cache with expiration
        else if (request.destination === "image") {
            event.respondWith(
                caches.match(request).then((response) => {
                    if (response) {
                        return response
                    }

                    return fetch(request)
                        .then((fetchResponse) => {
                            // Only cache successful responses
                            if (fetchResponse.status === 200) {
                                const responseClone = fetchResponse.clone()
                                caches.open(DYNAMIC_CACHE).then((cache) => {
                                    cache.put(request, responseClone)
                                })
                            }
                            return fetchResponse
                        })
                        .catch(() => {
                            // Return placeholder image if offline
                            return new Response(
                                '<svg width="300" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999">Image unavailable</text></svg>',
                                { headers: { "Content-Type": "image/svg+xml" } },
                            )
                        })
                }),
            )
        }
        // HTML pages - network first, cache fallback
        else if (request.mode === "navigate") {
            event.respondWith(
                fetch(request)
                    .then((response) => {
                        // Cache successful page responses
                        if (response.status === 200) {
                            const responseClone = response.clone()
                            caches.open(DYNAMIC_CACHE).then((cache) => {
                                cache.put(request, responseClone)
                            })
                        }
                        return response
                    })
                    .catch(() => {
                        // Try to serve from cache
                        return caches.match(request).then((response) => {
                            return response || caches.match("/offline.html")
                        })
                    }),
            )
        }
        // API requests - network only (don't cache dynamic data)
        else if (url.pathname.startsWith("/api/")) {
            event.respondWith(
                fetch(request).catch(() => {
                    return new Response(JSON.stringify({ error: "Network unavailable" }), {
                        status: 503,
                        headers: { "Content-Type": "application/json" },
                    })
                }),
            )
        }
    }
})

// Background sync for form submissions
self.addEventListener("sync", (event) => {
    if (event.tag === "background-sync") {
        event.waitUntil(doBackgroundSync())
    }
})

function doBackgroundSync() {
    // Handle offline form submissions
    return caches.open(DYNAMIC_CACHE).then((cache) => {
        return cache.match("/offline-forms").then((response) => {
            if (response) {
                return response.json().then((forms) => {
                    return Promise.all(
                        forms.map((form) => {
                            return fetch(form.url, {
                                method: form.method,
                                body: form.data,
                                headers: form.headers,
                            })
                        }),
                    )
                })
            }
        })
    })
}

// Push notifications
self.addEventListener("push", (event) => {
    const options = {
        body: event.data ? event.data.text() : "New article available!",
        icon: "/logo.svg",
        badge: "/logo.svg",
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
        },
        actions: [
            {
                action: "explore",
                title: "Read Article",
                icon: "/icons/checkmark.png",
            },
            {
                action: "close",
                title: "Close",
                icon: "/icons/xmark.png",
            },
        ],
    }

    event.waitUntil(self.registration.showNotification("Grip & Grin", options))
})

// Notification click handling
self.addEventListener("notificationclick", (event) => {
    event.notification.close()

    if (event.action === "explore") {
        event.waitUntil(clients.openWindow("/"))
    }
})
