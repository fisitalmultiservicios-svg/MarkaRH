# MarkaRH

Prototipo UX interactivo del marcador instalado, web oficial, administración empresarial y vista Maestro.

## Diseño visual actualizado

Web oficial más gráfica y marcador guiado de tres pasos: nombre, PIN y confirmación.

- [Ver la web oficial](prototipo/capturas/01-web-oficial.png)
- [Ver el marcador](prototipo/capturas/02-marcador.png)
- [Ver el marcador en móvil](prototipo/capturas/movil-marcador-pin.png)

## Descargas desde GitHub

- [Descargar paquete completo ZIP](https://github.com/fisitalmultiservicios-svg/MarkaRH/raw/refs/heads/prototipo-ux/MarkaRH-prototipo-UX.zip)
- [Descargar HTML autónomo](https://github.com/fisitalmultiservicios-svg/MarkaRH/raw/refs/heads/prototipo-ux/prototipo/MarkaRH-demo.html)
- [Instrucciones y recorrido](prototipo/LEEME.md)
- [Validación y limitaciones](prototipo/VALIDACION.md)

El ZIP incluye el HTML autónomo, archivos fuente, capturas e instrucciones. Es una demostración local con datos ficticios, sin backend ni autenticación real. PIN demo: `123456`.

Para ejecutar las páginas separadas:

```sh
cd prototipo
python3 -m http.server 8000 --bind 127.0.0.1
```

El marcador HTML representa la futura aplicación instalada; no es una función de marcación de la web del producto. Los instaladores Windows/Android, cifrado, biometría y servicios de producción no están implementados.
