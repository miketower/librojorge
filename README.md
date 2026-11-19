# Una dedicatoria para Jorge Mario Blanco

Formulario para celebrar el lanzamiento de *Conecta con tu riqueza integral*. Cada invitado escribe su nombre y puede enviar un mensaje, un adjunto o ambos. El panel privado muestra los envíos y permite descargar los archivos. MySQL guarda todo, incluidos los adjuntos.

## Instalación en cPanel

1. Selecciona PHP 8.1 o posterior con `pdo_mysql`, `fileinfo` y sesiones habilitados.
2. Crea una base MySQL o MariaDB y un usuario asociado desde cPanel. Importa `private/schema.sql` en esa base mediante phpMyAdmin. Para el usuario de ejecución bastan los permisos `SELECT` e `INSERT`.
3. Sube los directorios `public` y `private` como hermanos. Configura el dominio o subdominio para que su raíz pública sea **solo** `public`. Si usas el dominio principal con raíz fija, copia el contenido de `public` a `public_html` y coloca `private` junto a `public_html`, dentro del directorio de tu cuenta.
4. Copia `private/config.example.php` a `private/config.php`. Completa el host, el nombre de la base, el usuario y la contraseña de MySQL. Incluye el prefijo que asigne cPanel cuando corresponda.
5. Genera el hash de una contraseña exclusiva para el panel. En una terminal con PHP y Bash, usa estos comandos y pega solo el hash en `admin_password_hash`.

   ```bash
   read -rs -p 'Contraseña del panel: ' panel_password
   printf '\n'
   printf '%s' "$panel_password" | php -r 'echo password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT), PHP_EOL;'
   unset panel_password
   ```

6. En MultiPHP INI Editor habilita las subidas y ajusta `upload_max_filesize` a al menos `5M` y `post_max_size` a al menos `8M`. MySQL debe admitir paquetes de más de 5 MiB, por ejemplo con `max_allowed_packet` de 16 MiB.
7. Activa HTTPS para el dominio. Comparte la URL principal con los invitados y abre `/admin.php` en el mismo dominio para consultar los envíos. El formulario está disponible para cualquiera que tenga la liga.

Antes de compartir la liga, envía una dedicatoria de prueba y verifica que aparezca en el panel y que el archivo se descargue. Las fechas del panel usan la zona horaria de la sesión de MySQL.
