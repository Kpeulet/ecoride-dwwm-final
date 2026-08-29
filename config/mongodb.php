<?php
// config/mongodb.php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$mongoDb = null;
$avisCollection = null;
$litigesCollection = null;

try {
    if (class_exists('\MongoDB\Client')) {
        // 1. Récupère l'URI Atlas (Render) ou bascule sur le serveur local
        $mongoUri = getenv('MONGODB_URI') ?: "mongodb://127.0.0.1:27017";
        
        $mongoClient = new \MongoDB\Client($mongoUri);
        
        // 2. Sélection de la base principale 'ecoride'
        $mongoDb = $mongoClient->selectDatabase('ecoride');
        
        // 3. Initialisation des deux collections NoSQL
        $avisCollection = $mongoDb->selectCollection('avis');
        $litigesCollection = $mongoDb->selectCollection('incidents');
    }
} catch (Exception $e) {
    error_log("Erreur de connexion MongoDB : " . $e->getMessage());
    $mongoDb = null;
    $avisCollection = null;
    $litigesCollection = null;
}