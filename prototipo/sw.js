'use strict';
const CACHE='markarh-ux-v2';
const ASSETS=['./','./index.html','./marcador.html','./admin.html','./assets/style.css','./assets/ui.js','./assets/escena-equipo.svg','./assets/logo.svg','./assets/core.js','./assets/public.js','./assets/marker.js','./assets/admin.js'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(ASSETS)).then(()=>self.skipWaiting())));
self.addEventListener('activate',e=>e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('markarh-ux-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',e=>{if(e.request.method!=='GET'||new URL(e.request.url).origin!==self.location.origin)return;e.respondWith(fetch(e.request).then(r=>{if(r.ok){const copy=r.clone();caches.open(CACHE).then(c=>c.put(e.request,copy))}return r}).catch(()=>caches.match(e.request).then(r=>r||new Response('Recurso no disponible sin conexión',{status:503}))))});
