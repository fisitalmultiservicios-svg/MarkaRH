'use strict';
const CACHE='markarh-ux-v1';
const ASSETS=['./','./index.html','./marcador.html','./admin.html','./assets/style.css','./assets/logo.svg','./assets/core.js','./assets/public.js','./assets/marker.js','./assets/admin.js'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(ASSETS)).then(()=>self.skipWaiting())));
self.addEventListener('activate',e=>e.waitUntil(self.clients.claim()));
self.addEventListener('fetch',e=>{if(e.request.method!=='GET'||new URL(e.request.url).origin!==self.location.origin)return;e.respondWith(fetch(e.request).then(r=>{if(r.ok){const copy=r.clone();caches.open(CACHE).then(c=>c.put(e.request,copy))}return r}).catch(()=>caches.match(e.request).then(r=>r||new Response('Recurso no disponible sin conexión',{status:503}))))});
