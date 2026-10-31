"""Comprueba el HTML autónomo servido y abierto como archivo local."""
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
import json

ROOT=Path(__file__).resolve().parents[1]

def run():
 with sync_playwright() as p:
  outcomes=[]
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
  for origin in ['http://127.0.0.1:8000/MarkaRH-demo.html', (ROOT/'MarkaRH-demo.html').as_uri()]:
   ctx=browser.new_context(viewport={'width':1440,'height':1000})
   page=ctx.new_page();errors=[];requests=[]
   page.on('pageerror',lambda e:errors.append(str(e)))
   page.on('request',lambda r:requests.append(r.url))
   try:
    page.goto(origin)
   except Exception as exc:
    if origin.startswith('file:') and 'ERR_BLOCKED_BY_ADMINISTRATOR' in str(exc):
     outcomes.append({'target':'file://','status':'SKIPPED','reason':'Política del navegador administrado bloquea archivos locales; no se elude.'})
     ctx.close()
     continue
    raise
   expect(page.locator('#hero-title')).to_contain_text('Tu equipo marca')
   page.get_by_role('link',name='Explorar marcador').click()
   expect(page.locator('[data-employee=E001]')).to_be_visible()
   page.locator('#technical').click()
   page.locator('#mode').select_option('auto')
   page.locator('#save-tech').click()
   expect(page.locator('#punch-types')).to_be_hidden()
   page.locator('[data-employee=E001]').click()
   page.locator('#pin').fill('123456')
   page.locator('#punch').click()
   expect(page.get_by_role('heading',name='Marcación guardada')).to_be_visible()
   page.reload()
   assert page.evaluate("MRH.state.events.filter(x=>x.action==='Registro automático').length")==1
   page.locator('#technical').click()
   page.locator('#sync').click()
   page.locator('.close').click()
   page.get_by_role('link',name='Administración',exact=True).click()
   page.locator('#demo-consent').check()
   page.locator('#enter-demo').click()
   page.locator('a[href="#horas-extra"]').click()
   page.locator('#add-overtime').click()
   page.locator('[name=reason]').fill('Apoyo operativo de demostración')
   page.locator('.modal form button[type=submit]').click()
   page.locator('[data-overtime]').click()
   page.locator('[name=reviewReason]').fill('Revisión de solicitud de prueba')
   page.locator('.modal form button[type=submit]').click()
   expect(page.locator('#content')).to_contain_text('Aprobada')
   expect(page.locator('#content')).to_contain_text('Pendiente de conciliar')
   page.locator('a[href="#preplanilla"]').click()
   expect(page.locator('#content')).to_contain_text('Cálculo incompleto')
   with page.expect_download():page.locator('#prepay-csv').click()
   page.locator('a[href="#marcaciones"]').click()
   expect(page.locator('#events-table')).to_contain_text('Registro automático')
   page.locator('#switch-role').click()
   page.locator('a[href="#empresas"]').first.click()
   page.locator('#add-company').click()
   page.locator('[name=name]').fill('Empresa UX')
   page.locator('.modal form button[type=submit]').click()
   expect(page.locator('#companies-table')).to_contain_text('Empresa UX')
   page.locator('a[href="#sitio"]').click()
   page.locator('[name=title]').fill('Diseño verificado de MarkaRH')
   page.locator('#publish-cms').click()
   page.locator('#confirm-publish').click()
   page.locator('a[href="?page=web"]').first.click()
   expect(page.locator('#hero-title')).to_have_text('Diseño verificado de MarkaRH')
   # Every subresource is embedded: no requests for CSS/JS/images/fonts/CDNs.
   extras=[u for u in requests if not u.startswith('data:') and 'MarkaRH-demo.html' not in u and not u.endswith('favicon.ico')]
   assert not extras, extras
   assert not errors, errors
   outcomes.append({'target':'file://' if origin.startswith('file:') else 'HTTP','status':'PASS'})
   ctx.close()
  browser.close()
  print(json.dumps({'outcomes':outcomes,'checks':['self-contained navigation (see per-target outcomes)','no external asset requests','automatic mode hides explicit actions','local event persistence','overtime request and approval','prepay incomplete export','company creation','CMS publish and cross-page persistence']},ensure_ascii=False))
if __name__=='__main__':run()
