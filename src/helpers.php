<?php
// src/helpers.php

if (!function_exists('url')) {
    function url(string $path): string {
        // Pega o caminho da pasta onde está o index.php (ex: /meu-projeto/public)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Corrige barras no Windows
        $scriptDir = str_replace('\\', '/', $scriptDir);
        
        // Remove barra final se houver
        $baseUrl = rtrim($scriptDir, '/');
        
        // Monta a URL: /meu-projeto/public + /register
        return $baseUrl . '/' . ltrim($path, '/');
    }
}