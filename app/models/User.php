<?php
// Fichier: app/models/User.php

// Le modèle User gère les opérations de base de données pour les utilisateurs.

require_once __DIR__ . '/Database.php'; // Assurez-vous que cette ligne est présente et correcte

class User {
    private $conn;
    private $table_name = "users"; // Assurez-vous que le nom de votre table d'utilisateurs est 'users'

    // Propriétés de l'objet User, correspondant aux colonnes de la table 'users'
    public $id;
    public $username;
    public $email;
    public $password;
    public $role; // admin, supplier, driver
    public $company_name; // Pour les fournisseurs
    public $address;      // Pour les fournisseurs
    public $phone;        // Pour les fournisseurs et livreurs
    public $siret_number; // Pour les fournisseurs
    public $first_name;   // Pour les livreurs
    public $last_name;    // Pour les livreurs
    public $id_card_number; // Pour les livreurs
    public $motorized;    // Pour les livreurs (booléen/int)
    public $is_active;    // Pour tous les utilisateurs (booléen/int)
    public $created_at;
    public $updated_at;

    /**
     * Constructeur de la classe User.
     * Initialise la connexion à la base de données via le Singleton Database.
     */
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        // Vérifier si la connexion est valide
        if (!$this->conn) {
            error_log("Erreur critique: La connexion à la base de données est NULL dans User model.");
            die("Erreur de connexion à la base de données dans User model. Vérifiez votre configuration de base de données.");
        }
    }

    /**
     * Crée un nouvel utilisateur dans la base de données.
     *
     * @return bool Vrai si la création est réussie, faux sinon.
     */
    public function create() {
        // Hacher le mot de passe avant de le stocker
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $query = "INSERT INTO " . $this->table_name . "
                  SET
                      username = ?,
                      email = ?,
                      password = ?,
                      role = ?,
                      company_name = ?,
                      address = ?,
                      phone = ?,
                      siret_number = ?,
                      first_name = ?,
                      last_name = ?,
                      id_card_number = ?,
                      motorized = ?,
                      is_active = ?";

        $stmt = $this->conn->prepare($query);

        // Ajout de débogage pour prepare()
        if ($stmt === false) {
            error_log("User::create - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        // Nettoyage des données
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->company_name = htmlspecialchars(strip_tags($this->company_name ?? ''));
        $this->address = htmlspecialchars(strip_tags($this->address ?? ''));
        $this->phone = htmlspecialchars(strip_tags($this->phone ?? ''));
        $this->siret_number = htmlspecialchars(strip_tags($this->siret_number ?? ''));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name ?? ''));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name ?? ''));
        $this->id_card_number = htmlspecialchars(strip_tags($this->id_card_number ?? ''));
        $this->motorized = $this->motorized ?? 0;
        $this->is_active = $this->is_active ?? 1; // Par défaut actif

        // Liaison des valeurs
        // Note: 's' pour string, 'i' pour integer. Assurez-vous que l'ordre et les types correspondent.
        $stmt->bind_param("ssssssssssiii",
            $this->username,
            $this->email,
            $this->password, // Mot de passe haché
            $this->role,
            $this->company_name,
            $this->address,
            $this->phone,
            $this->siret_number,
            $this->first_name,
            $this->last_name,
            $this->id_card_number,
            $this->motorized, // tinyint(1) -> i
            $this->is_active  // tinyint(1) -> i
        );

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        // Afficher l'erreur si l'exécution échoue
        error_log("User::create - Erreur lors de l'exécution: " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Authentifie un utilisateur.
     *
     * @param string $username Le nom d'utilisateur.
     * @param string $password Le mot de passe non haché.
     * @return array|false Les données de l'utilisateur si l'authentification réussit, faux sinon.
     */
    public function authenticate($username, $password) {
        $query = "SELECT id, username, password, role, is_active FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            error_log("User::authenticate - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        $username = htmlspecialchars(strip_tags($username));
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute()) {
            error_log("User::authenticate - Erreur lors de l'exécution: " . $stmt->error . " Username: " . $username);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Vérifier le mot de passe et si le compte est actif
            if (password_verify($password, $row['password']) && $row['is_active']) {
                return $row; // Authentification réussie, retourne les données de l l'utilisateur
            }
        }
        return false; // Authentification échouée
    }

    /**
     * Lit un utilisateur par son nom d'utilisateur.
     * Hydrate l'objet courant avec les données de l'utilisateur.
     *
     * @return bool Vrai si l'utilisateur est trouvé et l'objet hydraté, faux sinon.
     */
    public function findByUsername() {
        $query = "SELECT
                    id, username, email, password, role, company_name, address, phone, siret_number,
                    first_name, last_name, id_card_number, motorized, is_active, created_at, updated_at
                  FROM
                    " . $this->table_name . "
                  WHERE
                    username = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::findByUsername - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bind_param("s", $this->username);
        
        if (!$stmt->execute()) {
            error_log("User::findByUsername - Erreur lors de l'exécution: " . $stmt->error . " Username: " . $this->username);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Hydrate l'objet courant avec les données
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }
        return false;
    }

    /**
     * Lit un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur à récupérer.
     * @return User|null L'objet User hydraté ou null si non trouvé.
     */
    public function readById($id) {
        $query = "SELECT
                    id, username, email, role, company_name, address, phone, siret_number,
                    first_name, last_name, id_card_number, motorized, is_active, created_at, updated_at
                  FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::readById - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null;
        }

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("User::readById - Erreur lors de l'exécution: " . $stmt->error . " User ID: " . $id);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Hydrate l'objet courant avec les données
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            return $this; // Retourne l'objet User courant
        }
        return null;
    }

    /**
     * Récupère un utilisateur par son ID et retourne ses propriétés sous forme d'objet.
     * Cette méthode est utilisée pour obtenir les informations d'un utilisateur
     * sans hydrater l'objet User courant, par exemple pour afficher des détails dans une vue.
     *
     * @param int $id L'ID de l'utilisateur.
     * @return object|null Un objet stdClass contenant les propriétés de l'utilisateur si trouvé, null sinon.
     */
    public function getUserById($id) {
        $query = "SELECT id, username, email, role, company_name, address, phone, siret_number,
                         first_name, last_name, id_card_number, motorized, is_active, created_at, updated_at
                  FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::getUserById - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("User::getUserById - Erreur lors de l'exécution: " . $stmt->error . " User ID: " . $id);
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            return (object)$result->fetch_assoc(); // Retourne un objet stdClass
        }
        return null;
    }

    /**
     * Lit tous les utilisateurs.
     *
     * @return array Un tableau d'utilisateurs (chaque utilisateur est un tableau associatif).
     */
    public function readAll() {
        $query = "SELECT id, username, email, role, company_name, address, phone, siret_number,
                         first_name, last_name, id_card_number, motorized, is_active, created_at, updated_at
                  FROM " . $this->table_name . " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::readAll - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return []; // Retourne un tableau vide en cas d'erreur
        }

        if (!$stmt->execute()) {
            error_log("User::readAll - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $stmt->close();

        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            error_log("User::readAll - get_result() a échoué. " . $stmt->error);
        }
        return $users;
    }

    /**
     * Lit les utilisateurs par rôle spécifique.
     *
     * @param string $role Le rôle des utilisateurs à récupérer.
     * @return array Un tableau d'utilisateurs (chaque utilisateur est un tableau associatif).
     */
    public function readByRole($role) {
        $query = "SELECT id, username, email, role, company_name, address, phone, siret_number,
                         first_name, last_name, id_card_number, motorized, is_active, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE role = ?
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            error_log("User::readByRole - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return []; // Retourne un tableau vide en cas d'échec
        }

        $stmt->bind_param("s", $role);

        if (!$stmt->execute()) {
            error_log("User::readByRole - Erreur lors de l'exécution: " . $stmt->error . " Role: " . $role);
            $stmt->close();
            return []; // Retourne un tableau vide en cas d'échec
        }

        $result = $stmt->get_result();
        $stmt->close(); // Fermer le statement après avoir obtenu le résultat

        $users = [];
        if ($result) { // Vérifier si get_result() a réussi
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            error_log("User::readByRole - get_result() a échoué. " . $stmt->error);
        }
        return $users;
    }


    /**
     * Met à jour les informations d'un utilisateur.
     *
     * @return bool Vrai si la mise à jour est réussie, faux sinon.
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      username = ?,
                      email = ?,
                      role = ?,
                      company_name = ?,
                      address = ?,
                      phone = ?,
                      siret_number = ?,
                      first_name = ?,
                      last_name = ?,
                      id_card_number = ?,
                      motorized = ?,
                      is_active = ?,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE
                      id = ?";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::update - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        // Nettoyage des données
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->company_name = htmlspecialchars(strip_tags($this->company_name ?? ''));
        $this->address = htmlspecialchars(strip_tags($this->address ?? ''));
        $this->phone = htmlspecialchars(strip_tags($this->phone ?? ''));
        $this->siret_number = htmlspecialchars(strip_tags($this->siret_number ?? ''));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name ?? ''));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name ?? ''));
        $this->id_card_number = htmlspecialchars(strip_tags($this->id_card_number ?? ''));
        $this->motorized = $this->motorized ?? 0;
        $this->is_active = $this->is_active ?? 0; // S'assurer qu'il y a une valeur par défaut
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Liaison des valeurs
        // Note: Le type 'i' pour motorized et is_active est correct car ils sont TINYINT(1)
        $stmt->bind_param("sssssssssiiii",
            $this->username,
            $this->email,
            $this->role,
            $this->company_name,
            $this->address,
            $this->phone,
            $this->siret_number,
            $this->first_name,
            $this->last_name,
            $this->id_card_number,
            $this->motorized,
            $this->is_active,
            $this->id
        );

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        error_log("User::update - Erreur lors de l'exécution: " . $stmt->error);
        $stmt->close();
        return false;
    }


    /**
     * Supprime un utilisateur de la base de données.
     *
     * @return bool Vrai si la suppression est réussie, faux sinon.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::delete - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bind_param("i", $this->id);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        error_log("User::delete - Erreur lors de l'exécution: " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Compte le nombre total d'utilisateurs.
     * @return int Le nombre total d'utilisateurs.
     */
    public function countAllUsers() {
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::countAllUsers - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return 0;
        }
        if (!$stmt->execute()) {
            error_log("User::countAllUsers - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Compte le nombre d'utilisateurs pour un rôle donné.
     * @param string $role Le rôle des utilisateurs à compter (ex: 'supplier', 'driver', 'admin').
     * @return int Le nombre d'utilisateurs pour ce rôle.
     */
    public function countUsersByRole($role) {
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name . " WHERE role = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("User::countUsersByRole - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return 0;
        }
        $stmt->bind_param("s", $role);
        if (!$stmt->execute()) {
            error_log("User::countUsersByRole - Erreur lors de l'exécution: " . $stmt->error . " Role: " . $role);
            $stmt->close();
            return 0;
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
}
