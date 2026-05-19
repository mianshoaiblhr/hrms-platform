<?php
/**
 * Database Configuration
 * Supports: .env file, Railway.app MySQL vars, and JAWSDB_URL
 */

// Railway.app provides these MySQL variable names
$host     = $_ENV['DB_HOST']     ?? $_ENV['MYSQLHOST']     ?? $_ENV['RAILWAY_MYSQL_HOST']     ?? '127.0.0.1';
$port     = $_ENV['DB_PORT']     ?? $_ENV['MYSQLPORT']     ?? $_ENV['RAILWAY_MYSQL_PORT']     ?? 3306;
$database = $_ENV['DB_DATABASE'] ?? $_ENV['MYSQLDATABASE'] ?? $_ENV['RAILWAY_MYSQL_DATABASE'] ?? 'hrms_db';
$username = $_ENV['DB_USERNAME'] ?? $_ENV['MYSQLUSER']     ?? $_ENV['RAILWAY_MYSQL_USER']     ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? $_ENV['MYSQLPASSWORD'] ?? $_ENV['RAILWAY_MYSQL_PASSWORD'] ?? '';

return [
    'driver'   => 'mysql',
    'host'     => $host,
    'port'     => (int)$port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
];
