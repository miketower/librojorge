<?php
function page_header(string $title, string $bodyClass = ''): void
{
?>
<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d152e">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="./" aria-label="Jorge Mario Blanco, inicio">
            <img class="brand-mark" src="assets/marca.png" alt="" width="32" height="46">
            <span><span class="brand-name">Jorge Mario Blanco</span><span class="brand-line">Lidera el camino</span></span>
        </a>
        <span class="header-label">Lanzamiento del libro</span>
    </div>
</header>
<?php
}

function page_footer(): void
{
?>
<footer class="site-footer">
    <span>Jorge Mario Blanco</span>
    <span>Conecta con tu riqueza integral</span>
</footer>
</body>
</html>
<?php
}
