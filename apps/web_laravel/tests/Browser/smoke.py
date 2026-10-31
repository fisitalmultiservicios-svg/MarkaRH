"""HTTP/browser smoke test. Requires isolated MySQL test fixtures, Playwright, Chromium."""
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
import json
BASE='http://127.0.0.1:8002'
ROOT=Path(__file__).resolve().parents[4]
OUT=ROOT/'docs'/'capturas-oficiales'
OUT.mkdir(exist_ok=True)
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
 context=browser.new_context(viewport={'width':1440,'height':1000})
 page=context.new_page();errors=[]
 page.on('pageerror',lambda e:errors.append(str(e)))
 page.on('console',lambda m:errors.append(m.text) if m.type=='error' and 'Content Security Policy' in m.text else None)
 page.goto(BASE)
 expect(page.locator('h1')).to_contain_text('Tu equipo marca.')
 page.locator('#tab-android').click()
 expect(page.locator('.tablet-terminal')).to_be_visible()
 page.screenshot(path=str(OUT/'web.png'),full_page=True)
 # A real HTTP request with no CSRF token must fail.
 response=context.request.post(BASE+'/contacto',form={'name':'Test'})
 assert response.status==419,response.status
 page.goto(BASE+'/acceso')
 page.locator('#email').fill('admin@browser.example')
 page.locator('#password').fill('BrowserTestPassword42!')
 page.get_by_role('button',name='Ingresar').click()
 expect(page.locator('h1')).to_contain_text('Empresa de prueba del navegador')
 page.screenshot(path=str(OUT/'admin.png'),full_page=True)
 page.get_by_role('link',name='Empleados',exact=True).click()
 expect(page.locator('table')).not_to_contain_text('Alta desde navegador')
 page.locator('#employee-name').fill('Alta desde navegador')
 page.locator('#code').fill('BROWSER2')
 page.get_by_role('button',name='Crear empleado').click()
 expect(page.get_by_role('status')).to_contain_text('Empleado creado.')
 expect(page.locator('table')).to_contain_text('Alta desde navegador')
 # The browser session cannot ingest device events without a terminal token.
 response=context.request.post(BASE+'/api/v1/device/events/batch',data={'events':[]})
 assert response.status==401,response.status
 page.get_by_role('button',name='Cerrar sesión').click()
 expect(page.locator('#email')).to_be_visible()
 page.locator('#email').fill('master@browser.example');page.locator('#password').fill('BrowserTestPassword42!')
 page.get_by_role('button',name='Ingresar').click()
 expect(page.locator('h1')).to_have_text('Empresas y terminales')
 expect(page.locator('table')).not_to_contain_text('Empresa creada desde navegador')
 page.locator('#company-name').fill('Empresa creada desde navegador')
 page.locator('#branch-name').fill('Heredia')
 page.locator('#admin-name').fill('Nuevo administrador')
 page.locator('#admin-email').fill('nuevo@browser.example')
 page.locator('#new-password').fill('InitialBrowserPassword42!');page.locator('#confirm-password').fill('InitialBrowserPassword42!')
 page.get_by_role('button',name='Crear empresa y administrador').click()
 expect(page.get_by_role('status')).to_contain_text('Empresa y cuenta administrativa creadas.')
 expect(page.locator('table')).to_contain_text('Empresa creada desde navegador')
 page.screenshot(path=str(OUT/'maestro.png'),full_page=True)
 page.get_by_role('button',name='Cerrar sesión').click()
 for width in [390,768,1440]:
  page.set_viewport_size({'width':width,'height':900})
  for route in ['/','/acceso']:
   page.goto(BASE+route)
   assert not page.evaluate('document.documentElement.scrollWidth>innerWidth'),(width,route)
 assert not errors,errors
 browser.close()
 print(json.dumps({'result':'PASS','checks':['official public site and product tabs','real CSRF 419','session login/logout','employee persistence through real forms','master company creation','session rejected by device API','responsive 390/768/1440','no JS errors']}))
