<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
ini_set('session.use_strict_mode', '1');
session_name('contacts_book');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
define('CONTACTS_APP', true);
require __DIR__ . '/config.php'; require __DIR__ . '/helpers.php';
$actions = ['view','add','edit','delete'];
$action = param($_GET, 'action', 'view'); if (!in_array($action, $actions, true)) $action = 'view';
$sort = param($_GET, 'sort', 'added'); if (!in_array($sort, ['added','surname','birth'], true)) $sort = 'added';
require __DIR__ . '/menu.php';
$content = ''; $flash = null;
try {
    db();
    ob_start();
    if ($action === 'view') { require __DIR__ . '/viewer.php'; echo viewer($sort, positiveInt($_GET, 'page')); }
    else { require __DIR__ . '/' . $action . '.php'; }
    $content = ob_get_clean();
} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    logFailure($e); http_response_code(503);
    $content = '<div class="notice error" role="alert"><h2>База данных недоступна</h2><p>Не удалось открыть записную книжку. Проверьте подключение и попробуйте позже.</p></div>';
}
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!doctype html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Записная книжка</title><link rel="stylesheet" href="assets/style.css"></head><body>
<a class="skip" href="#main">Перейти к содержимому</a><header><a href="index.php" aria-label="На главную"><img src="assets/logo.svg" alt="Московский Политех" width="190" height="65"></a><span>Записная книжка</span><small>Основы серверной<br>веб-разработки</small></header>
<main id="main"><div class="intro"><p class="eyebrow">Люди и связи</p><h1>Контакты под рукой</h1><p>Храните важные сведения и поддерживайте связь.</p></div><?= menu() ?><section class="workspace" aria-label="Записная книжка">
<?php if ($flash): ?><div class="notice <?= $flash['ok'] ? 'success' : 'error' ?>" role="status"><?= h($flash['text']) ?></div><?php endif; ?>
<?= $content ?></section></main><footer>Самостоятельная работа «Записная книжка»</footer></body></html>
