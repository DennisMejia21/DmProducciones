# DM Producciones

Laboratorio de gestión de solicitudes y reservas para una productora de fotografía y video. Hecho con PHP 8.3, MySQL 8.4, HTML, CSS y JavaScript, sin framework.

## Ejecutar en tu computadora

Necesitas Git y Docker Desktop abierto (con Docker Compose).

```bash
git clone https://github.com/DennisMejia21/DmProducciones.git
cd DmProducciones
code .
```

Si ya lo tienes descargado, entra a la carpeta y ejecuta `git switch main` y `git pull --ff-only origin main`. Guarda antes tus cambios si los tienes.

1. Copia `.env.example` como `.env`. En PowerShell: `Copy-Item .env.example .env`. En Linux o macOS: `cp .env.example .env`.
2. Completa `DB_PASSWORD`, `DB_ROOT_PASSWORD` y `ADMIN_PASSWORD` con contraseñas propias y diferentes. La del administrador debe tener entre 10 y 72 caracteres. Puedes dejar WhatsApp vacío para probar el resto.
3. Ejecuta:

```bash
docker compose up -d --build --wait
docker compose exec web php crear_admin.php
```

En tu navegador local, abre `http://localhost:8080`. En **Administración**, ingresa con el usuario `admin` y la contraseña que elegiste en `ADMIN_PASSWORD`.

Para detenerlo: `docker compose down`. Los datos se conservan en el volumen `datos`. El SQL inicial se importa solo cuando MySQL arranca con un volumen vacío. No borres ese volumen si quieres conservar tus reservas.

El script del administrador crea la cuenta una sola vez. Cambiar `ADMIN_PASSWORD` después no cambia la contraseña guardada en MySQL. No subas `.env` a GitHub.

## Qué se puede probar

- La página de inicio tiene portafolio, Sobre mí, Equipo, Tomas con drone y preguntas frecuentes.
- Cada una de las tres publicaciones abre su propia galería de fotos.
- Un cliente solicita una cobertura sin registrarse y selecciona uno o más servicios.
- Se validan los campos, el correo, el teléfono, la fecha futura y los horarios.
- La solicitud queda pendiente. El administrador puede confirmarla o rechazarla.
- Una reserva confirmada se puede cancelar o marcar realizada cuando termina.
- El panel permite filtrar por fecha y estado, y muestra los datos de contacto.
- Al confirmar, se intenta enviar un mensaje por WhatsApp si está configurado.
- Desde el panel se consulta el clima del evento, eligiendo la ciudad correcta.

### Cómo se evitan las superposiciones

Para este laboratorio se supone que hay **un solo equipo de cobertura**. Cada evento tiene fecha, hora de inicio y hora de fin. Debe terminar el mismo día; por ejemplo, para una fiesta que cruza medianoche todavía habría que ampliar el modelo.

Si existe una reserva confirmada de 14:00 a 16:00, se rechaza una nueva solicitud de 15:00 a 17:00. Se permite una que comience a las 16:00. Las pendientes no bloquean la agenda: se vuelve a verificar al confirmarlas. Un bloqueo de MySQL protege la operación para que dos confirmaciones simultáneas no ocupen el mismo horario. Al cancelar, se libera la franja horaria.

Los servicios y sus precios iniciales son ejemplos, en pesos argentinos. No hay pagos ni edición de precios desde el panel.

## Agregar las fotos del portafolio

La portada está en `/` y el formulario ahora está en `/reservas.php`. Para completar las tres publicaciones:

1. Copia tus imágenes a `public/fotos/`.
2. Para la primera cobertura, usa nombres como `cobertura-1-1.jpg`, `cobertura-1-2.jpg` y `cobertura-1-3.jpg`.
3. Para la segunda, usa `cobertura-2-1.jpg`, `cobertura-2-2.jpg`, etc. Para la tercera, `cobertura-3-1.jpg`, etc.
4. Abre `portafolio.php` y cambia el título, la categoría, el resumen y el texto de cada publicación por los datos de tu cobertura.

Las fotos aparecen automáticamente al recargar la página. La primera de cada grupo se usa como portada de la publicación. Se aceptan JPG, JPEG, PNG y WebP; no hace falta cambiar la extensión real de tus archivos. Por ejemplo, también sirve `cobertura-1-1.png`.

Puedes agregar imágenes para otras secciones con estos nombres: `portada.jpg`, `sobre-mi.jpg`, `equipo.jpg` y `drone.jpg`, usando cualquiera de los formatos admitidos. Hasta que las agregues, se muestran espacios de espera. Los textos iniciales de las coberturas son editables; no incluyen nombres ni fechas de clientes.

No hay un panel de carga de fotos: para mantener el laboratorio sencillo se copian a esa carpeta. Los textos de presentación, equipo y preguntas frecuentes se editan en `public/index.php`.

## APIs

**Open-Meteo:** no requiere clave. Busca la ciudad y consulta el pronóstico diario de la fecha seleccionada. Solo se consulta desde hoy hasta 15 días hacia adelante; para eventos más lejanos se muestra un aviso. Necesita acceso a `geocoding-api.open-meteo.com` y `api.open-meteo.com`.

**WhatsApp:** usa la API oficial de WhatsApp Business de Meta. Necesitas un número configurado, un token válido y una plantilla de utilidad aprobada. Completa en `.env`:

```dotenv
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_TEMPLATE_NAME=
WHATSAPP_TEMPLATE_LANGUAGE=es_AR
WHATSAPP_API_VERSION=v23.0
```

La plantilla debe tener tres parámetros de texto en el cuerpo, en este orden: nombre, fecha y horario. Por ejemplo: «Hola {{1}}, confirmamos tu reserva para el {{2}}, de {{3}}. DM Producciones». El idioma tiene que coincidir con el de la plantilla aprobada. El cliente debe haber aceptado recibir la confirmación. Con un número de prueba de Meta, el destinatario también debe estar habilitado en su panel.

Después de cambiar la configuración, ejecuta `docker compose up -d` para recrear el servicio web. Necesita acceso a `graph.facebook.com`.

Sin credenciales, la reserva se confirma y el panel muestra **no configurado**. Un error de la API tampoco borra la reserva. Puedes reintentar desde el panel. Si hubo un corte de conexión, comprueba antes en Meta si el mensaje fue aceptado para evitar duplicados. **Enviado** significa aceptado por la API, no entregado: este laboratorio no implementa webhooks de entrega. Si un proceso se interrumpe mientras envía, revisa el estado antes de volver a intentar.

## Archivos

- `public/`: páginas, estilos y JavaScript que ve el navegador.
- `config.php`: conexión PDO y sesión.
- `funciones.php`: validaciones y operaciones de reservas.
- `portafolio.php`: textos de las tres publicaciones y búsqueda de sus fotos.
- `integraciones.php`: llamadas a Open-Meteo y WhatsApp.
- `crear_admin.php`: alta del administrador desde la terminal.
- `database.sql`: las cinco tablas y servicios de ejemplo.
- `tests/pruebas.py`: pruebas HTTP con Python, sin paquetes adicionales.
- `compose.yaml` y `Dockerfile`: aplicación y base de datos.

Las contraseñas se guardan con `password_hash`; los formularios usan tokens CSRF y las consultas usan parámetros. El servidor incluido es el servidor de desarrollo de PHP, pensado para la demostración local. Un despliegue público requiere configurar HTTPS y un servidor apropiado.

## Pruebas

Usa una base de datos **descartable**, porque las pruebas crean solicitudes. Puedes copiar `.env` a `.env.pruebas`, cambiar `APP_PORT` a `8081` y `DB_NAME` a `dm_lab_test`, y levantar otro proyecto:

```bash
docker compose -p dm-pruebas --env-file .env.pruebas up -d --build --wait
docker compose -p dm-pruebas --env-file .env.pruebas exec web php crear_admin.php
```

Con Python 3 instalado, configura `ADMIN_PASSWORD` en la terminal con la contraseña de ese entorno y ejecuta `python tests/pruebas.py` (en Linux puede llamarse `python3`). Las pruebas se conectan al puerto `8081`; puedes cambiarlo con `TEST_URL`. No configures credenciales de WhatsApp en ese entorno: las pruebas de confirmación esperan el modo sin credenciales.

Se comprueban el acceso de administrador, los datos inválidos, la creación de solicitudes, el conflicto de horarios, las reservas contiguas, las cancelaciones y el clima fuera del rango de pronóstico. No envían mensajes reales.

## Ramas

`main` contiene el laboratorio completo y `develop` sirve para continuar trabajando. Las ramas `funcionalidad/reservas`, `funcionalidad/administracion`, `funcionalidad/integraciones` y `funcionalidad/portafolio` organizan los avances de cada parte. Para ejecutar el proyecto completo, usa `main`.
