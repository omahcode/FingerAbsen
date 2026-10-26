// Service Worker untuk Web Push Notification Portal Orang Tua

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {
        title: 'Presensi Siswa',
        body: 'Pembaruan status presensi ananda.',
        icon: '/favicon.svg',
        badge: '/favicon.svg',
        url: '/orang-tua'
    };

    if (event.data) {
        try {
            const data = event.data.json();
            payload = Object.assign(payload, data);
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon || '/favicon.svg',
        badge: payload.badge || '/favicon.svg',
        vibrate: [300, 100, 300],
        tag: 'presensi-' + (payload.student_id || Date.now()),
        renotify: true,
        data: {
            url: payload.url || '/orang-tua'
        },
        actions: [
            { action: 'open', title: 'Buka Portal' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options).catch((err) => {
            console.warn('showNotification with options failed, fallback without icon:', err);
            return self.registration.showNotification(payload.title, {
                body: payload.body,
                data: options.data
            });
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/orang-tua';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes('/orang-tua') && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
