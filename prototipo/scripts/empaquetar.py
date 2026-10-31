"""Genera un HTML autónomo y un ZIP del prototipo, sin dependencias externas."""
from pathlib import Path
import json
import re
import zipfile
from urllib.parse import quote

root = Path(__file__).resolve().parents[1]
css = (root / 'assets/style.css').read_text()
logo = 'data:image/svg+xml,' + quote((root / 'assets/logo.svg').read_text(), safe='')
pages = {}
for key, file in [('web','index.html'),('marcador','marcador.html'),('admin','admin.html')]:
    content = (root / file).read_text()
    content = content.replace('<link rel="stylesheet" href="assets/style.css">', '<style>'+css+'</style>')
    content = content.replace('assets/logo.svg', logo)
    for script in re.findall(r'<script src="([^"]+)"></script>', content):
        js = (root / script).read_text()
        # No worker is needed in the self-contained file; it has no network dependencies.
        js = re.sub(r"if\('serviceWorker' in navigator&&location.protocol!==\x27file:\x27\)navigator.serviceWorker.register\('\./sw.js'\).catch\(\(\)=>\{\}\);", '', js)
        content = content.replace(f'<script src="{script}"></script>', '<script>\n'+js+'\n</script>')
    for filename, target in [('index.html','web'),('marcador.html','marcador'),('admin.html','admin')]:
        content = content.replace(f'href="{filename}"', f'href="?page={target}"')
    content = content.replace('assets/logo.svg', logo)
    for svg in (root / 'assets').glob('*.svg'):
        content = content.replace('assets/'+svg.name, 'data:image/svg+xml,'+quote(svg.read_text(), safe=''))
    pages[key] = content
encoded = json.dumps(pages, ensure_ascii=False).replace('<', '\\u003c')
standalone = '<!doctype html><html lang="es-CR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MarkaRH · Prototipo UX</title><script>const pages='+encoded+';const page=new URLSearchParams(location.search).get("page")||"web";document.open();document.write(pages[page]||pages.web);document.close();</script></html>'
(root/'MarkaRH-demo.html').write_text(standalone)
archive=root.parent/'MarkaRH-prototipo-UX.zip'
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
    for path in sorted(root.rglob('*')):
        if path.is_file() and '__pycache__' not in path.parts:
            z.write(path, Path('MarkaRH-prototipo')/path.relative_to(root))
print('HTML autónomo:',root/'MarkaRH-demo.html')
print('Paquete:',archive)
