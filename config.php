<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
// Local launch uses SQLite. On hosting copy config.local.example.php to config.local.php.
$config = ['driver' => 'sqlite', 'path' => __DIR__ . '/data/contacts.sqlite'];
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace($config, require __DIR__ . '/config.local.php');
}
