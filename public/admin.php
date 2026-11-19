<?php
declare(strict_types=1);
require __DIR__ . '/../private/bootstrap.php';
require __DIR__ . '/../private/layout.php';

$error = '';
$entries = [];
$page = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000000]]) ?: 1;
$hasNext = false;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!valid_csrf()) {
            http_response_code(422);
            $error = 'La sesión venció. Intenta nuevamente.';
        } elseif (isset($_SESSION['admin']) && isset($_POST['salir'])) {
            unset($_SESSION['admin'], $_SESSION['csrf']);
            session_regenerate_id(true);
            redirect('admin.php');
        } elseif (!isset($_SESSION['admin'])) {
            $hash = config()['admin_password_hash'];
            if ($hash === '') {
                throw new RuntimeException('Falta configurar la contraseña del panel.');
            }
            $password = $_POST['password'] ?? null;
            if (is_string($password) && strlen($password) <= 1024 && password_verify($password, $hash)) {
                session_regenerate_id(true);
                unset($_SESSION['csrf']);
                $_SESSION['admin'] = true;
                redirect('admin.php');
            }
            http_response_code(401);
            $error = 'Contraseña incorrecta.';
        }
    }

    if (isset($_SESSION['admin'])) {
        $id = filter_input(INPUT_GET, 'archivo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id) {
            $query = db()->prepare('SELECT archivo_nombre, archivo_mime, archivo FROM dedicatorias WHERE id = ? AND archivo IS NOT NULL');
            $query->execute([$id]);
            $file = $query->fetch(PDO::FETCH_ASSOC);
            if (!$file) {
                http_response_code(404);
                exit('Archivo no encontrado.');
            }
            $preview = isset($_GET['vista']) && str_starts_with($file['archivo_mime'], 'image/');
            header('Content-Type: ' . ($preview ? $file['archivo_mime'] : 'application/octet-stream'));
            $disposition = $preview ? 'inline' : 'attachment';
            header("Content-Disposition: $disposition; filename*=UTF-8''" . rawurlencode($file['archivo_nombre']));
            header('Content-Length: ' . strlen($file['archivo']));
            echo $file['archivo'];
            exit;
        }

        $query = db()->prepare('SELECT id, nombre, mensaje, archivo_nombre, archivo_mime, creado FROM dedicatorias ORDER BY id DESC LIMIT 21 OFFSET ?');
        $query->bindValue(1, ($page - 1) * 20, PDO::PARAM_INT);
        $query->execute();
        $entries = $query->fetchAll(PDO::FETCH_ASSOC);
        $hasNext = count($entries) > 20;
        if ($hasNext) {
            array_pop($entries);
        }
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(503);
    $error = 'El panel no está disponible en este momento.';
}

page_header('Panel de dedicatorias · Jorge Mario Blanco');
?>
<main class="panel">
    <section class="card">
        <p class="eyebrow">Acceso privado</p>
        <h1>Dedicatorias recibidas</h1>
        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= e($error) ?></p>
        <?php endif ?>
        <?php if (!isset($_SESSION['admin'])): ?>
            <form class="login" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <div class="field">
                    <label for="password">Contraseña del panel</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button type="submit">Entrar <span aria-hidden="true">→</span></button>
            </form>
        <?php else: ?>
            <form class="logout" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <button type="submit" name="salir" value="1">Cerrar sesión</button>
            </form>
            <?php if ($entries === [] && $error === ''): ?>
                <p><?= $page === 1 ? 'Aún no se han recibido dedicatorias.' : 'No hay dedicatorias en esta página.' ?></p>
            <?php endif ?>
            <?php foreach ($entries as $entry): ?>
                <article class="entry">
                    <time><?= e($entry['creado']) ?></time>
                    <h2><?= e($entry['nombre']) ?></h2>
                    <?php if ($entry['mensaje'] !== ''): ?>
                        <p><?= e($entry['mensaje']) ?></p>
                    <?php endif ?>
                    <?php if ($entry['archivo_nombre'] !== null): ?>
                        <?php if (str_starts_with($entry['archivo_mime'], 'image/')): ?>
                            <img class="entry-image" src="?archivo=<?= (int) $entry['id'] ?>&amp;vista=1" alt="Imagen adjunta de <?= e($entry['nombre']) ?>" loading="lazy">
                        <?php endif ?>
                        <a href="?archivo=<?= (int) $entry['id'] ?>">Descargar <?= e($entry['archivo_nombre']) ?></a>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
            <?php if ($page > 1 || $hasNext): ?>
                <nav class="pagination" aria-label="Páginas de dedicatorias">
                    <?php if ($page > 1): ?><a href="?pagina=<?= $page - 1 ?>">Anterior</a><?php endif ?>
                    <?php if ($hasNext): ?><a href="?pagina=<?= $page + 1 ?>">Siguiente</a><?php endif ?>
                </nav>
            <?php endif ?>
        <?php endif ?>
    </section>
</main>
<?php page_footer(); ?>
