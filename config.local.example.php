<?php
if (!defined('CONTACTS_APP')) { http_response_code(403); exit; }
return [
    'driver' => 'mysql',
    'host' => 'YOUR_MYSQL_HOST',
    'database' => 'YOUR_DATABASE_NAME',
    'username' => 'YOUR_MYSQL_USERNAME',
    'password' => 'YOUR_MYSQL_PASSWORD',
];
