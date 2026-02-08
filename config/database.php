<?php
// config/database.php

return [
    'host' => 'localhost',
    'dbname' => 'u669181089_topmodel',
    'user' => 'u669181089_topmodel',      // Altere para seu usuário
    'password' => 'Tp102030=',      // Altere para sua senha
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];