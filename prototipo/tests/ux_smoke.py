"""Pruebas funcionales del prototipo. Requiere Playwright y Chromium."""
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
import json

BASE = 'http://127.0.0.1:8000'
ROOT = Path(__file__).resolve().parents[1]

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(executable_path='/usr/bin/chromium', headless=True, args=['--no-sandbox'])
        ctx = browser.new_context(viewport={'width':1440,'height':1000}, accept_downloads=True)
        page = ctx.new_page()
        errors=[]
        page.on('pageerror', lambda e: errors.append(str(e)))
        page.goto(BASE+'/index.html')
        expect(page.get_by_role('heading',level=1)).to_contain_text('Cada jornada')
        page.screenshot(path=str(ROOT/'capturas/01-web-oficial.png'), full_page=True)
        page.get_by_role('button',name='Solicitar información').click()
        page.locator('[name=name]').fill('Empresa de prueba')
        page.locator('[name=email]').fill('prueba@example.test')
        page.locator('[name=company]').fill('Compañía ficticia')
        page.locator('input[type=checkbox]').check()
        page.locator('#contact-form button[type=submit]').click()
        assert page.evaluate('MRH.state.contacts.length') == 1
        page.goto(BASE+'/marcador.html')
        page.screenshot(path=str(ROOT/'capturas/02-marcador.png'), full_page=True)
        page.locator('#punch').click()
        expect(page.locator('#punch-error')).to_contain_text('Selecciona')
        page.locator('#employee-select').select_option('E001')
        page.locator('#pin').fill('000000')
        page.locator('#punch').click()
        expect(page.locator('#punch-error')).to_contain_text('PIN incorrecto')
        page.locator('#technical').click()
        page.locator('#offline').check()
        page.locator('#save-tech').click()
        page.locator('#employee-select').select_option('E001')
        for key in '123456': page.locator(f'[data-key="{key}"]').click()
        page.locator('[data-action="Salida"]').click()
        page.locator('#punch').click()
        expect(page.get_by_role('heading',name='Marcación guardada')).to_be_visible()
        assert page.evaluate("MRH.state.events.filter(e=>e.status==='Pendiente').length") == 1
        eid=page.evaluate('MRH.state.events.at(-1).id')
        page.reload()
        assert page.evaluate('MRH.state.events.at(-1).id') == eid
        expect(page.locator('#pending-count')).to_contain_text('1 pendiente')
        page.locator('#technical').click()
        page.locator('#sync').click()
        assert page.evaluate('MRH.state.events.at(-1).status') == 'Pendiente'
        page.locator('#offline').uncheck()
        page.locator('#save-tech').click()
        page.locator('#technical').click()
        page.locator('#sync').click()
        assert page.evaluate('MRH.state.events.at(-1).status') == 'Recibida'
        count=page.evaluate('MRH.state.events.length')
        page.locator('#sync').click()
        assert page.evaluate('MRH.state.events.length') == count
        page.locator('.close').click()
        # Actual browser-offline reload after service worker is active.
        page.evaluate('navigator.serviceWorker.ready')
        page.reload()
        ctx.set_offline(True)
        page.reload()
        expect(page.locator('#punch')).to_be_visible()
        ctx.set_offline(False)
        page.goto(BASE+'/admin.html')
        page.locator('#demo-consent').check()
        page.locator('#enter-demo').click()
        expect(page.locator('h1')).to_have_text('Vista general')
        page.screenshot(path=str(ROOT/'capturas/03-admin.png'), full_page=True)
        page.locator('a[href="#empleados"]').click()
        page.locator('#add-employee').click()
        page.locator('[name=name]').fill('Sofía Prueba')
        page.locator('[name=job]').fill('Diseño')
        page.locator('.modal form button[type=submit]').click()
        page.locator('#employee-query').fill('Sofía')
        expect(page.locator('#employees-table tbody tr')).to_have_count(1)
        expect(page.locator('#employees-table')).to_contain_text('Sofía Prueba')
        page.locator('[data-edit]').click()
        page.locator('[name=job]').fill('Experiencia de usuario')
        page.locator('.modal form button[type=submit]').click()
        page.locator('#employee-query').fill('Sofía')
        expect(page.locator('#employees-table')).to_contain_text('Experiencia de usuario')
        page.locator('[data-toggle]').click()
        page.locator('#confirm-toggle').click()
        expect(page.locator('#employees-table')).to_contain_text('Inactivo')
        page.locator('a[href="#incidencias"]').click()
        original_events=page.evaluate('JSON.stringify(MRH.state.events)')
        page.locator('[data-issue]').first.click()
        page.locator('[name=reason]').fill('Revisado con la evidencia del turno de prueba.')
        page.locator('.modal form button[type=submit]').click()
        assert page.evaluate('JSON.stringify(MRH.state.events)') == original_events
        expect(page.locator('#issues-table')).to_contain_text('Conciliada')
        page.locator('a[href="#turnos"]').click()
        page.locator('#add-shift').click()
        page.locator('[name=name]').fill('Turno prueba')
        page.locator('.modal form button[type=submit]').click()
        expect(page.locator('#content')).to_contain_text('Turno prueba')
        page.locator('a[href="#solicitudes"]').click()
        page.locator('#add-leave').click()
        page.locator('[name=reason]').fill('Permiso de prueba')
        page.locator('.modal form button[type=submit]').click()
        page.locator('[data-leave]').last.click()
        page.locator('[name=decisionReason]').fill('Solicitud revisada en la demostración')
        page.locator('.modal form button[type=submit]').click()
        expect(page.locator('#content')).to_contain_text('Aprobada')
        page.locator('a[href="#reportes"]').click()
        page.locator('#report-form button[type=submit]').click()
        expect(page.locator('#report-result')).to_contain_text('5 eventos')
        with page.expect_download() as dl:
            page.locator('#report-csv').click()
        data=Path(dl.value.path()).read_text(encoding='utf-8-sig')
        assert 'Ana Jiménez' in data and 'Salida' in data
        page.locator('a[href="#empresa"]').click()
        page.locator('#add-branch').click()
        page.locator('[name=name]').fill('Alajuela')
        page.locator('[name=address]').fill('Sucursal demo')
        page.locator('.modal form button[type=submit]').click()
        expect(page.locator('#content')).to_contain_text('Alajuela')
        page.locator('a[href="#dispositivos"]').click()
        page.locator('[data-revoke="SJ-01"]').click()
        page.locator('#confirm-device').click()
        page.goto(BASE+'/marcador.html')
        expect(page.locator('#punch')).to_be_disabled()
        # restore terminal for screenshots and subsequent demo.
        page.evaluate("MRH.transaction(s=>s.devices.find(x=>x.id==='SJ-01').state='Activo')")
        page.goto(BASE+'/admin.html')
        page.locator('#switch-role').click()
        expect(page.locator('h1')).to_have_text('Vista global')
        page.screenshot(path=str(ROOT/'capturas/04-maestro.png'), full_page=True)
        page.locator('a[href="#consultas"]').click()
        expect(page.locator('#content')).to_contain_text('Compañía ficticia')
        page.locator('a[href="#sitio"]').click()
        page.locator('[name=title]').fill('Asistencia clara para tu equipo.')
        page.locator('#cms-form button[type=submit]').click()
        assert page.evaluate('MRH.state.cms.published') is None
        page.locator('#publish-cms').click()
        page.locator('#confirm-publish').click()
        page.goto(BASE+'/index.html')
        expect(page.locator('#hero-title')).to_have_text('Asistencia clara para tu equipo.')
        # Security: text renders as text, not HTML.
        page.evaluate("MRH.transaction(s=>{s.cms.title='<img src=x onerror=alert(1)>';s.cms.published=new Date().toISOString()})")
        page.reload()
        expect(page.locator('#hero-title')).to_have_text('<img src=x onerror=alert(1)>')
        assert page.locator('#hero-title img').count()==0
        # Reset local demonstration state before mobile captures.
        page.evaluate("localStorage.removeItem('markarh-ux-v1');sessionStorage.clear()")
        page.reload()
        for width in [390,768,1024,1440]:
            page.set_viewport_size({'width':width,'height':900})
            for route in ['index.html','marcador.html','admin.html']:
                page.goto(BASE+'/'+route)
                if route=='admin.html' and page.locator('#enter-demo').count():
                    page.locator('#demo-consent').check();page.locator('#enter-demo').click()
                overflow=page.evaluate('document.documentElement.scrollWidth > innerWidth')
                assert not overflow, f'Horizontal overflow: {route} {width}'
                if width==390:
                    page.screenshot(path=str(ROOT/'capturas'/('movil-'+route.replace('.html','.png'))),full_page=True)
        assert not errors, '\n'.join(errors)
        print(json.dumps({'result':'PASS','checks':['public contact','PIN validation','offline persistence and reload','idempotent demo sync','employee create/edit/deactivate','immutable original during review','shift create','leave approval','filtered CSV export','branch create','device revocation','master dashboard','CMS draft and publish','XSS text escaping','responsive 390/768/1024/1440','no browser JS errors']},ensure_ascii=False))
        browser.close()

if __name__ == '__main__':
    run()
