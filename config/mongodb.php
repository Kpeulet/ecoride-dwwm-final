<?php
// config/mongodb.php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$litigesCollection = null;

try {
    if (class_exists('\MongoDB\Client')) {
        // Connexion au serveur MongoDB local
        $mongoClient = new \MongoDB\Client("mongodb://127.0.0.1:27017");
        
        // Connexion à la base et à la collection configurées dans Compass
        $mongoDb = $mongoClient->selectDatabase('backend-template');
        $litigesCollection = $mongoDb->selectCollection('incidents');
    }
} catch (Exception $e) {
    error_log("Erreur de connexion MongoDB : " . $e->getMessage());
    $litigesCollection = null;
}