'use strict';
const menu = document.querySelector('#official-menu');
menu?.addEventListener('click', () => {
 const open = document.querySelector('#sidebar').classList.toggle('open');
 menu.setAttribute('aria-expanded', String(open));
 menu.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
});
document.addEventListener('keydown', event => {
 if (event.key === 'Escape') {
  document.querySelector('#sidebar')?.classList.remove('open');
  menu?.setAttribute('aria-expanded', 'false');
  menu?.setAttribute('aria-label', 'Abrir menú');
 }
});
