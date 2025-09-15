<?php
/**
 * Configuration de la base de données - Bibliothèque
 *
 * Ce fichier contient les paramètres de connexion à la base de données MySQL
 * et initialise l'objet PDO utilisé dans toute l'application
 */

// Paramètres de connexion à la base de données
$host = 'localhost';        // Serveur MySQL
$dbname = 'library';        // Nom de la base de données
$username = 'root';         // Nom d'utilisateur MySQL
$password = '';             // Mot de passe MySQL (vide pour WAMP par défaut)

// Tentative de connexion à la base de données
try {
    // Création de l'objet PDO avec gestion d'erreurs et charset UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Configuration pour afficher les erreurs SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    // Arrêt de l'application en cas d'erreur de connexion
    die("Erreur de connexion : " . $e->getMessage());
}
?>