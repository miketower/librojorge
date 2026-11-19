<?php
declare(strict_types=1);
require __DIR__ . '/../private/bootstrap.php';
require __DIR__ . '/../private/layout.php';

$nombre = '';
$mensaje = '';
$error = '';
$sent = !empty($_SESSION['sent']);
unset($_SESSION['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = text_field('nombre');
    $mensaje = text_field('mensaje');

    try {
        if (!valid_csrf()) {
            throw new InvalidArgumentException('La sesión venció o el envío superó el límite. Intenta de nuevo con un archivo de hasta 5 MB.');
        }
        if ($nombre === '' || text_length($nombre) > 120) {
            throw new InvalidArgumentException('Escribe tu nombre con un máximo de 120 caracteres.');
        }
        if (text_length($mensaje) > 3000) {
            throw new InvalidArgumentException('Tu mensaje puede tener hasta 3000 caracteres.');
        }
        [$filename, $mime, $data] = attachment($_FILES['archivo'] ?? []);
        if ($mensaje === '' && $data === null) {
            throw new InvalidArgumentException('Escribe un mensaje o adjunta un archivo para Jorge.');
        }

        $insert = db()->prepare('INSERT INTO dedicatorias (nombre, mensaje, archivo_nombre, archivo_mime, archivo) VALUES (?, ?, ?, ?, ?)');
        $insert->bindValue(1, $nombre);
        $insert->bindValue(2, $mensaje);
        $insert->bindValue(3, $filename);
        $insert->bindValue(4, $mime);
        $insert->bindValue(5, $data, $data === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $insert->execute();
        $_SESSION['sent'] = true;
        redirect('./');
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(503);
        $error = 'No pudimos guardar tu dedicatoria en este momento. Intenta nuevamente.';
    }
}

page_header('Una dedicatoria para Jorge Mario Blanco');
?>
<main class="launch">
    <section class="intro" aria-labelledby="title">
        <p class="eyebrow"><span></span> Celebremos el lanzamiento</p>
        <h1 id="title">Conecta con tu<br><em>riqueza integral</em></h1>
        <p class="subtitle">El método para multiplicar tu dinero,<br class="wide-break"> tu paz y tu propósito.</p>
        <figure class="book">
            <img src="assets/portada.webp" alt="Portada de Conecta con tu riqueza integral, de Jorge Mario Blanco" width="1000" height="1500">
        </figure>
    </section>
    <section class="card" aria-labelledby="form-title">
        <span class="ornament" aria-hidden="true">✧</span>
        <h2 id="form-title">Una dedicatoria para Jorge</h2>
        <?php if ($sent): ?>
            <div class="success" role="status">
                <h3>¡Gracias por tus palabras!</h3>
                <p>Tu dedicatoria se guardó y estará disponible para el equipo de Jorge Mario Blanco.</p>
                <a href="./">Escribir otra dedicatoria</a>
            </div>
        <?php else: ?>
            <p class="card-intro">Felicita a Jorge Mario Blanco por el lanzamiento de su libro. Puedes escribirle unas palabras, compartir un archivo o hacer ambas cosas.</p>
            <?php if ($error !== ''): ?>
                <p class="error" role="alert"><?= e($error) ?></p>
            <?php endif ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <div class="field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" autocomplete="name" maxlength="120" placeholder="Tu nombre" required value="<?= e($nombre) ?>">
                </div>
                <div class="field">
                    <label for="mensaje">Mensaje para el autor</label>
                    <textarea id="mensaje" name="mensaje" maxlength="3000" rows="4" placeholder="Querido Jorge…" aria-describedby="message-note"><?= e($mensaje) ?></textarea>
                    <small id="message-note">Hasta 3000 caracteres.</small>
                </div>
                <div class="field">
                    <label for="archivo">Adjunta una imagen o archivo</label>
                    <div class="file-field">
                        <input id="archivo" type="file" name="archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" aria-describedby="file-note">
                        <small id="file-note">JPG, PNG, WebP, GIF o PDF · hasta 5 MB</small>
                    </div>
                </div>
                <button type="submit">Enviar dedicatoria <span aria-hidden="true">→</span></button>
                <p class="privacy">Tu dedicatoria aparecerá en un panel privado.</p>
            </form>
        <?php endif ?>
    </section>
</main>
<?php page_footer(); ?>
