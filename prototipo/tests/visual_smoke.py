"""Regresión del flujo gráfico y de las pestañas de producto."""
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
import json
ROOT=Path(__file__).resolve().parents[1]
BASE='http://127.0.0.1:8000'
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
 context=browser.new_context(viewport={'width':1440,'height':1000})
 page=context.new_page();errors=[]
 page.on('pageerror',lambda e:errors.append(str(e)))
 page.goto(BASE+'/index.html')
 page.locator('#tab-android').click()
 expect(page.locator('#product-stage')).to_have_attribute('aria-labelledby','tab-android')
 expect(page.locator('.tablet-terminal')).to_be_visible()
 page.locator('#tab-android').press('ArrowRight')
 expect(page.locator('#tab-admin')).to_have_attribute('aria-selected','true')
 expect(page.locator('.showcase-dashboard')).to_be_visible()
 page.locator('#tab-admin').press('Home')
 expect(page.locator('#tab-windows')).to_have_attribute('aria-selected','true')
 for width in [390,600,768,1024,1440]:
  page.set_viewport_size({'width':width,'height':900})
  page.goto(BASE+'/marcador.html')
  expect(page.locator('#step-person')).to_have_attribute('aria-current','step')
  page.locator('#employee-search').fill('María')
  expect(page.locator('[data-employee]')).to_have_count(1)
  page.locator('#employee-search').fill('no-existe')
  expect(page.locator('#people-grid')).to_contain_text('No encontramos')
  page.locator('#employee-search').fill('')
  page.locator('[data-employee=E001]').click()
  expect(page.locator('#step-pin')).to_have_attribute('aria-current','step')
  expect(page.locator('#selected-name')).to_have_text('¡Hola, Ana!')
  page.locator('#pin').fill('123456')
  expect(page.locator('#step-confirm')).to_have_attribute('aria-current','step')
  page.locator('[data-action="Salida"]').click()
  expect(page.locator('[data-action="Salida"]')).to_have_attribute('aria-pressed','true')
  expect(page.locator('#punch')).to_contain_text('Confirmar salida')
  assert not page.evaluate('document.documentElement.scrollWidth>innerWidth'),width
  button=page.locator('#punch').bounding_box()
  assert button['x']+button['width'] <= width, width
  if width==390:
   page.screenshot(path=str(ROOT/'capturas/movil-marcador-pin.png'),full_page=True)
  if width==1440:
   page.screenshot(path=str(ROOT/'capturas/02b-marcador-pin.png'),full_page=True)
  page.locator('#change-person').click()
  expect(page.locator('#pin-area')).to_be_hidden()
  page.locator('[data-employee=E002]').click()
  expect(page.locator('#pin')).to_have_value('')
  expect(page.locator('#punch')).to_contain_text('Confirmar entrada')
  page.locator('#accessibility').click()
  page.locator('#contrast').check();page.locator('#large').check()
  page.locator('#save-access').click()
  assert not page.evaluate('document.documentElement.scrollWidth>innerWidth'),f'Accessibility overflow {width}'
  page.locator('#accessibility').click()
  page.locator('#contrast').uncheck();page.locator('#large').uncheck()
  page.locator('#save-access').click()
 assert not errors, errors
 browser.close()
 print(json.dumps({'result':'PASS','checks':['graphic employee search and empty state','three-step progress','selected action and label','identity/PIN clearing and default action reset','product tabs with keyboard','selected and enlarged marker at 390/600/768/1024/1440 px','no JS errors']},ensure_ascii=False))
