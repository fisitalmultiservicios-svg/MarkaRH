'use strict';
document.querySelector('#download-recovery')?.addEventListener('click', () => {
 const codes = [...document.querySelectorAll('#recovery-codes code')].map(element => element.textContent);
 const content = 'MarkaRH · Códigos de recuperación privados\nCada código funciona una sola vez. No los compartas.\n\n' + codes.join('\n') + '\n';
 const url = URL.createObjectURL(new Blob([content], {type: 'text/plain;charset=utf-8'}));
 const link = document.createElement('a');
 link.href = url;
 link.download = 'MarkaRH-codigos-recuperacion.txt';
 link.click();
 setTimeout(() => URL.revokeObjectURL(url), 1000);
});
