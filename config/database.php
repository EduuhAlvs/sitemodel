<?php

// config/database.php

return [
    'host'     => $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: 'localhost',
    'dbname'   => $_ENV['DB_NAME']     ?? getenv('DB_NAME')     ?: 'topmodelsite',
    'user'     => $_ENV['DB_USER']     ?? getenv('DB_USER')     ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',

    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
