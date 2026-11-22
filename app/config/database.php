<?php

$env = static function (string $name, $default = null) {
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
};
$driver = strtolower((string) $env('DB_CONNECTION', 'mysql'));
$defaultPort = $driver === 'pgsql' ? 5432 : ($driver === 'sqlsrv' || $driver === 'mssql' ? 1433 : 3306);

return [
    'default' => [
        'type' => $driver,
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => (int) $env('DB_PORT', $defaultPort),
        'username' => $env('DB_USERNAME', ''),
        'password' => $env('DB_PASSWORD', ''),
        'database' => $env('DB_DATABASE', ''),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
    ],
];
