<?php
// Fichier: app/models/Database.php

// Ce fichier implémente le pattern Singleton pour la connexion à la base de données.
// Cela garantit qu'il n'y a qu'une seule instance de la connexion MySQL dans toute l'application.

require_once __DIR__ . '/../../config/database.php'; // Inclut les constantes de connexion

class Database {
    private static $instance = null; // Stocke l'unique instance de la classe Database
    private $conn;                   // L'objet de connexion mysqli

    /**
     * Constructeur privé de la classe Database.
     * Empêche la création directe d'instances (pattern Singleton).
     * Tente d'établir une connexion à la base de données MySQL.
     */
    private function __construct() {
        // Tenter la connexion à la base de données en utilisant les constantes définies
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Vérifier si la connexion a échoué
        if ($this->conn->connect_error) {
            // Si la connexion échoue, enregistrer l'erreur et arrêter le script
            error_log("Échec de la connexion à la base de données: " . $this->conn->connect_error);
            die("Désolé, la connexion à la base de données a échoué. Veuillez vérifier votre configuration.");
        }

        // Définir l'encodage des caractères pour la connexion pour éviter les problèmes d'affichage
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Retourne l'unique instance de la classe Database.
     * Crée l'instance si elle n'existe pas encore.
     *
     * @return Database L'unique instance de la classe Database.
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database(); // Crée l'instance si elle n'existe pas
        }
        return self::$instance; // Retourne l'instance existante
    }

    /**
     * Retourne l'objet de connexion mysqli.
     *
     * @return mysqli L'objet de connexion à la base de données.
     */
    public function getConnection() {
        return $this->conn;
    }
}
