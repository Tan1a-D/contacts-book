<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function param(array $source, string $key, string $default = ''): string {
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : $default;
}
function positiveInt(array $source, string $key): int {
    $value = filter_var(param($source, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? 1 : $value;
}
function db(): PDO {
    static $connection = null;
    global $config;
    if ($connection instanceof PDO) return $connection;
    if ($config['driver'] === 'mysql') {
        $connection = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4', $config['username'], $config['password']);
    } else {
        if (!is_dir(__DIR__ . '/data') && !mkdir(__DIR__ . '/data', 0700, true)) throw new RuntimeException('Storage unavailable');
        $dsn = 'sqlite:' . $config['path'];
        $connection = class_exists('Pdo\\Sqlite') ? new \Pdo\Sqlite($dsn) : new PDO($dsn);
        if (class_exists('Collator')) {
            $collator = new Collator('ru_RU');
            if (method_exists($connection, 'createCollation')) $connection->createCollation('RUSSIAN', fn($a, $b) => $collator->compare($a, $b));
            else $connection->sqliteCreateCollation('RUSSIAN', fn($a, $b) => $collator->compare($a, $b));
        }
    }
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    if ($config['driver'] === 'mysql') $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $id = $config['driver'] === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail = $config['driver'] === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $connection->exec("CREATE TABLE IF NOT EXISTS contacts (
        id $id, surname VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL,
        patronymic VARCHAR(100) NOT NULL DEFAULT '', gender VARCHAR(12) NOT NULL DEFAULT '',
        birth_date DATE NULL, phone VARCHAR(40) NOT NULL DEFAULT '', address VARCHAR(300) NOT NULL DEFAULT '',
        email VARCHAR(254) NOT NULL DEFAULT '', comment TEXT NOT NULL
    )" . $tail);
    return $connection;
}
function nameOrder(): string {
    global $config;
    $collation = $config['driver'] === 'sqlite' && class_exists('Collator') ? ' COLLATE RUSSIAN' : '';
    return 'surname' . $collation . ', first_name' . $collation . ', id';
}
function blankContact(): array {
    return array_fill_keys(['surname','first_name','patronymic','gender','birth_date','phone','address','email','comment'], '');
}
function validateContact(array $source): array {
    $values = blankContact(); $errors = [];
    $limits = ['surname'=>100,'first_name'=>100,'patronymic'=>100,'gender'=>12,'birth_date'=>10,'phone'=>40,'address'=>300,'email'=>254,'comment'=>5000];
    foreach ($values as $key => $_) {
        $values[$key] = param($source, $key);
        if (mb_strlen($values[$key]) > $limits[$key]) $errors[] = 'Превышена допустимая длина поля.';
    }
    if ($values['surname'] === '' || $values['first_name'] === '') $errors[] = 'Введите фамилию и имя.';
    if (!in_array($values['gender'], ['', 'male', 'female', 'other'], true)) $errors[] = 'Выберите пол из списка.';
    if ($values['birth_date'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['birth_date']);
        if (!$date || $date->format('Y-m-d') !== $values['birth_date'] || $values['birth_date'] > date('Y-m-d') || $values['birth_date'] < '1900-01-01') $errors[] = 'Укажите корректную дату рождения от 1900 года до сегодняшнего дня.';
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Проверьте e-mail.';
    if ($values['phone'] !== '' && !preg_match('/^[+0-9() .-]{3,40}$/D', $values['phone'])) $errors[] = 'Проверьте номер телефона.';
    return [$values, array_unique($errors)];
}
function saveParams(array $values): array { if ($values['birth_date'] === '') $values['birth_date'] = null; return $values; }
function checkToken(): bool { return hash_equals($_SESSION['csrf'], param($_POST, 'csrf')); }
function flash(string $text, bool $ok = true): void { $_SESSION['flash'] = ['text'=>$text, 'ok'=>$ok]; }
function redirectTo(string $query): never { header('Location: index.php?' . $query, true, 303); exit; }
function fullName(array $row): string { return trim($row['surname'] . ' ' . $row['first_name'] . ' ' . $row['patronymic']); }
function logFailure(Throwable $e): void { error_log('Contacts: ' . $e->getMessage()); }
