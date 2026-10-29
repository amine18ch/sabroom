<?php
// config/url.php

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$basePath = ''; // Vide pour la version en ligne

// Pour le local, ajoute '/sabroom/public' uniquement si en localhost
if (str_contains($host, 'localhost')) {
    $basePath = '/sabroom/public';
}

define('BASE_URL', $protocol . $host . $basePath);
