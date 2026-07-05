self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Pas d'interception fetch : évite les erreurs "Failed to fetch" en pass-through.
// Le SW reste enregistré pour l'installation PWA sans gêner le réseau.
