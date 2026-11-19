<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

const MAX_FILE = 5 * 1024 * 1024;
const FILE_TYPES = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
];

function config(): array
{
    static $config;
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Falta configurar private/config.php.');
    }
    return $config ??= require $path;
}

function db(): PDO
{
    static $db;
    $config = config();
    return $db ??= new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function valid_csrf(): bool
{
    return is_string($_POST['csrf'] ?? null) && hash_equals(csrf(), $_POST['csrf']);
}

function text_field(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function text_length(string $text): int
{
    $count = preg_match_all('/./us', $text);
    return $count === false ? PHP_INT_MAX : $count;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function attachment(array $upload): array
{
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, null, null];
    }
    if ($error === UPLOAD_ERR_INI_SIZE || ($upload['size'] ?? 0) > MAX_FILE) {
        throw new InvalidArgumentException('El archivo debe pesar como máximo 5 MB.');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('No se pudo recibir el archivo. Selecciónalo nuevamente.');
    }

    $name = basename(str_replace(chr(92), '/', $upload['name']));
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset(FILE_TYPES[$extension]) || FILE_TYPES[$extension] !== $mime) {
        throw new InvalidArgumentException('Selecciona una imagen JPG, PNG, WebP o GIF, o un archivo PDF.');
    }
    $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '';
    if ($name === '' || text_length($name) > 180) {
        $name = 'adjunto.' . $extension;
    }
    $data = file_get_contents($upload['tmp_name']);
    if ($data === false || $data === '') {
        throw new InvalidArgumentException('El archivo está vacío o no se pudo leer.');
    }
    return [$name, $mime, $data];
}
