<?php
$host = 'mysql-ecoride-djedjed.alwaysdata.net';
$dbname = 'ecoride-djedjed_ecoride_db';
$username = 'ecoride-djedjed';
$password = 'VOTRE_MOT_DE_PASSE_ALWAYSDATA'; // Indiquez votre vrai mot de passe AlwaysData ici

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
