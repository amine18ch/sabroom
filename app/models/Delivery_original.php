<?php
// Fichier: app/models/Delivery.php

// Le modèle Delivery gère les opérations de base de données pour les livraisons.

require_once __DIR__ . '/Database.php'; // Assurez-vous que cette ligne est présente

class Delivery {
    private $conn;
    private $table_name = "deliveries";

    // Propriétés de l'objet Delivery, correspondant aux colonnes de la table 'deliveries'
    public $id;
    public $unique_id; // Code-barres unique
    public $supplier_id;
    public $driver_id;
    public $description;
    public $recipient_name;
    public $recipient_phone;
    public $recipient_city;
    public $destination_address;
    public $weight_kg;
    public $length_cm;
    public $L_width_cm; // Renommé pour éviter le conflit avec 'width'
    public $height_cm;
    public $declared_value;
    public $status; // pending, assigned, in_transit, delivered, returned, cancelled
    public $price;
    public $created_at;
    public $updated_at;
    public $return_reason; // Propriété pour le motif de retour
    public $invoice_id; // Propriété pour l'ID de la facture liée
    public $is_fragile; // Propriété pour indiquer si le colis est fragile
    public $accepts_exchange; // Propriété pour indiquer si le fournisseur accepte l'échange

    // Propriétés jointes pour l'affichage (non stockées dans la BDD de cette table)
    public $supplier_username;
    public $supplier_company_name;
    public $driver_username;
    public $driver_first_name;
    public $driver_last_name;
    public $driver_phone;


    /**
     * Constructeur de la classe Delivery.
     * Initialise la connexion à la base de données via le Singleton Database.
     */
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Génère un ID unique pour le colis (code-barres) au format SAB-000000000001.
     * @return string L'ID unique généré.
     */
    private function generateUniqueId() {
        // Obtenir le dernier numéro séquentiel utilisé pour les IDs "SAB-"
        $query = "SELECT MAX(CAST(SUBSTRING(unique_id, 5) AS UNSIGNED)) AS last_number 
                  FROM " . $this->table_name . " 
                  WHERE unique_id LIKE 'SAB-%'";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $lastNumber = $row['last_number'] ?? 0; // Si aucun, commencer à 0

        $newNumber = $lastNumber + 1;
        
        // Formater le nouveau numéro avec des zéros en tête (ex: 000000000001)
        // Pour un total de 12 chiffres après "SAB-"
        $paddedNumber = str_pad($newNumber, 16, '0', STR_PAD_LEFT);

        return "SAB-" . $paddedNumber;
    }

    /**
     * Crée une nouvelle livraison dans la base de données.
     *
     * @return bool Vrai si la création est réussie, faux sinon.
     */
    public function create() {
        // Générer un ID unique AVANT l'insertion, en utilisant la nouvelle logique
        $this->unique_id = htmlspecialchars(strip_tags($this->generateUniqueId()));


        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    unique_id = ?,
                    supplier_id = ?,
                    description = ?,
                    recipient_name = ?,
                    recipient_phone = ?,
                    recipient_city = ?,
                    destination_address = ?,
                    weight_kg = ?,
                    length_cm = ?,
                    L_width_cm = ?,
                    height_cm = ?,
                    declared_value = ?,
                    is_fragile = ?, 
                    accepts_exchange = ?, 
                    status = ?,
                    price = ?";

        $stmt = $this->conn->prepare($query);

        // Nettoyage et conversion des données pour la liaison
        $params_to_bind = [
            htmlspecialchars(strip_tags($this->unique_id)),
            htmlspecialchars(strip_tags($this->supplier_id)),
            htmlspecialchars(strip_tags($this->description)),
            htmlspecialchars(strip_tags($this->recipient_name)),
            htmlspecialchars(strip_tags($this->recipient_phone)),
            htmlspecialchars(strip_tags($this->recipient_city)),
            htmlspecialchars(strip_tags($this->destination_address)),
            floatval(htmlspecialchars(strip_tags($this->weight_kg))),
            floatval(htmlspecialchars(strip_tags($this->length_cm))),
            floatval(htmlspecialchars(strip_tags($this->L_width_cm))),
            floatval(htmlspecialchars(strip_tags($this->height_cm))),
            floatval(htmlspecialchars(strip_tags($this->declared_value))),
            intval(htmlspecialchars(strip_tags($this->is_fragile ?? 0))), // 0 ou 1 pour TINYINT(1)
            intval(htmlspecialchars(strip_tags($this->accepts_exchange ?? 0))), // 0 ou 1 pour TINYINT(1)
            htmlspecialchars(strip_tags($this->status)),
            floatval(htmlspecialchars(strip_tags($this->price)))
        ];

        // Définition des types de paramètres basée sur l'ordre des variables
        // s (unique_id), i (supplier_id), s (description), s (recipient_name), s (recipient_phone), s (recipient_city), s (destination_address)
        // d (weight_kg), d (length_cm), d (L_width_cm), d (height_cm), d (declared_value)
        // i (is_fragile), i (accepts_exchange)
        // s (status), d (price)
        $types = "sisssssdddddiisd"; // Longueur: 16 caractères. Cette chaîne est CORRECTE.

        // Débogage Avancé : Log les détails avant bind_param
        error_log("Delivery::create - DEBUG: Query prepared: " . $query);
        error_log("Delivery::create - DEBUG: Bind Types String: '" . $types . "' (Length: " . strlen($types) . ")");
        error_log("Delivery::create - DEBUG: Number of Bind Params: " . count($params_to_bind));
        error_log("Delivery::create - DEBUG: Bind Params Array: " . print_r($params_to_bind, true));


        // Liaison des valeurs
        // Utilisation de la syntaxe '...' pour unpack le tableau de paramètres
        if (!($stmt->bind_param($types, ...$params_to_bind))) {
            error_log("Delivery::create - Erreur bind_param: " . $stmt->error);
            $stmt->close();
            return false;
        }

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        error_log("Delivery::create - Erreur lors de l'exécution: " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Lit toutes les livraisons, avec des filtres optionnels pour le fournisseur, le livreur, la plage de dates,
     * le statut, une chaîne de recherche et l'ID de la facture.
     * Joint les informations du fournisseur et du livreur pour l'affichage.
     *
     * @param int|null $supplierId L'ID du fournisseur pour filtrer, ou null pour toutes les livraisons.
     * @param int|null $driverId L'ID du livreur pour filtrer, ou null pour toutes les livraisons.
     * @param string|array|null $dateFilterParam Peut être startDate (string) ou tableau de statuts (array).
     * @param string|null $endDate Date de fin (YYYY-MM-DD) pour le filtre de date, ou null.
     * @param string|null $statusFilter Statut à filtrer (ex: 'pending'), ou null.
     * @param string|null $searchQuery Chaîne de recherche pour description/destinataire/ID unique, ou null.
     * @param int|null $invoiceId L'ID de la facture à filtrer, ou null.
     * @return mysqli_result|null Le jeu de résultats de la requête, ou null en cas d'erreur.
     */
    public function readAll($supplierId = null, $driverId = null, $dateFilterParam = null, $endDate = null, $statusFilter = null, $searchQuery = null, $invoiceId = null) {
        $query = "SELECT
                    d.id, d.unique_id, d.supplier_id, d.driver_id, d.description,
                    d.recipient_name, d.recipient_phone, d.recipient_city, d.destination_address,
                    d.weight_kg, d.length_cm, d.L_width_cm, d.height_cm, d.declared_value,
                    d.is_fragile, d.accepts_exchange, -- NOUVEAU
                    d.status, d.price, d.created_at, d.updated_at, d.return_reason, d.invoice_id,
                    s.username as supplier_username,
                    s.company_name as supplier_company_name,
                    dr.username as driver_username,
                    dr.first_name as driver_first_name,
                    dr.last_name as driver_last_name,
                    dr.phone as driver_phone
                  FROM
                    " . $this->table_name . " d
                  LEFT JOIN
                    users s ON d.supplier_id = s.id
                  LEFT JOIN
                    users dr ON d.driver_id = dr.id";

        $conditions = [];
        $params = [];
        $types = "";

        if ($supplierId !== null) {
            $conditions[] = "d.supplier_id = ?";
            $params[] = $supplierId;
            $types .= "i";
        }
        if ($driverId !== null) {
            $conditions[] = "d.driver_id = ?";
            $params[] = $driverId;
            $types .= "i";
        }
        
        // Gérer le filtrage par plage de dates OU par tableau de statuts
        if (is_array($dateFilterParam)) { // Si $dateFilterParam est un tableau (de statuts)
            $placeholders = implode(',', array_fill(0, count($dateFilterParam), '?'));
            $conditions[] = "d.status IN ($placeholders)";
            foreach ($dateFilterParam as $stat) {
                $params[] = $stat;
                $types .= "s";
            }
        } elseif ($dateFilterParam !== null && $endDate !== null && $dateFilterParam !== '' && $endDate !== '') {
            // Si $dateFilterParam et $endDate sont des chaînes de date
            $conditions[] = "DATE(d.created_at) BETWEEN ? AND ?";
            $params[] = $dateFilterParam;
            $params[] = $endDate;
            $types .= "ss";
        }

        // Le statusFilter est un paramètre séparé, il est utilisé si $dateFilterParam n'est PAS un tableau
        if ($statusFilter !== null && $statusFilter !== '') {
            $conditions[] = "d.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }
        if ($searchQuery !== null && $searchQuery !== '') {
            $searchLike = "%" . $searchQuery . "%";
            $conditions[] = "(d.unique_id LIKE ? OR d.description LIKE ? OR d.recipient_name LIKE ? OR d.recipient_city LIKE ?)";
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $types .= "ssss";
        }

        // Filtrer par invoice_id
        if ($invoiceId !== null) {
            $conditions[] = "d.invoice_id = ?";
            $params[] = $invoiceId;
            $types .= "i";
        }


        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY d.created_at DESC";

        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            error_log("Delivery::readAll - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null; // Retourne null en cas d'erreur
        }

        if (count($params) > 0) {
            // Utilisation de la syntaxe '...' pour unpack le tableau de paramètres
            // pour bind_param
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("Delivery::readAll - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Lit une livraison par son ID.
     * Hydrate l'objet courant avec les données de la livraison, y compris les jointures.
     *
     * @param int $id L'ID de la livraison à récupérer.
     * @return Delivery|null L'objet Delivery hydraté avec toutes les données, ou null si non trouvé/erreur.
     */
    public function readOneById($id) {
        $query = "SELECT
                    d.id, d.unique_id, d.supplier_id, d.driver_id, d.description,
                    d.recipient_name, d.recipient_phone, d.recipient_city, d.destination_address,
                    d.weight_kg, d.length_cm, d.L_width_cm, d.height_cm, d.declared_value,
                    d.is_fragile, d.accepts_exchange, -- NOUVEAU
                    d.status, d.price, d.created_at, d.updated_at, d.return_reason, d.invoice_id,
                    s.username as supplier_username,
                    s.company_name as supplier_company_name,
                    dr.username as driver_username,
                    dr.first_name as driver_first_name,
                    dr.last_name as driver_last_name,
                    dr.phone as driver_phone
                  FROM
                    " . $this->table_name . " d
                  LEFT JOIN
                    users s ON d.supplier_id = s.id
                  LEFT JOIN
                    users dr ON d.driver_id = dr.id
                  WHERE
                    d.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::readOneById - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null; // Retourne null en cas d'erreur
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Delivery::readOneById - Erreur lors de l'exécution: " . $stmt->error . " ID: " . $id);
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Hydrate l'objet courant
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            // Retourne l'objet courant (déjà hydraté)
            return $this; 
        }
        return null; // Retourne null si non trouvé
    }

    /**
     * Lit une livraison par son ID unique (code-barres).
     * Hydrate l'objet courant avec les données de la livraison.
     *
     * @return bool Vrai si la livraison est trouvée et l'objet hydraté, faux sinon.
     */
    public function readOneByUniqueId() {
        $query = "SELECT
                    id, unique_id, supplier_id, driver_id, description,
                    recipient_name, recipient_phone, recipient_city, destination_address,
                    weight_kg, length_cm, L_width_cm, height_cm, declared_value,
                    is_fragile, accepts_exchange, -- NOUVEAU
                    status, price, created_at, updated_at, return_reason, invoice_id
                  FROM
                    " . $this->table_name . "
                  WHERE
                    unique_id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::readOneByUniqueId - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }
        $this->unique_id = htmlspecialchars(strip_tags($this->unique_id));
        $stmt->bind_param("s", $this->unique_id);
        if (!$stmt->execute()) {
            error_log("Delivery::readOneByUniqueId - Erreur lors de l'exécution: " . $stmt->error . " Unique ID: " . $this->unique_id);
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
     * Convertit l'objet Delivery en un tableau associatif.
     * Utile pour renvoyer des données via JSON.
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'supplier_id' => $this->supplier_id,
            'driver_id' => $this->driver_id,
            'description' => $this->description,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'recipient_city' => $this->recipient_city,
            'destination_address' => $this->destination_address,
            'weight_kg' => $this->weight_kg,
            'length_cm' => $this->length_cm,
            'L_width_cm' => $this->L_width_cm,
            'height_cm' => $this->height_cm,
            'declared_value' => $this->declared_value,
            'is_fragile' => $this->is_fragile, // NOUVEAU
            'accepts_exchange' => $this->accepts_exchange, // NOUVEAU
            'status' => $this->status,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'return_reason' => $this->return_reason, // Inclure le motif de retour
            'invoice_id' => $this->invoice_id, // Inclure l'ID de la facture
            'supplier_username' => $this->supplier_username,
            'supplier_company_name' => $this->supplier_company_name,
            'driver_username' => $this->driver_username,
            'driver_first_name' => $this->driver_first_name,
            'driver_last_name' => $this->driver_last_name,
            'driver_phone' => $this->driver_phone,
        ];
    }

    /**
     * Met à jour les informations d'une livraison.
     *
     * @return bool Vrai si la mise à jour est réussie, faux sinon.
     */
    public function update() {
        // Débogage : log des propriétés de l'objet AVANT la requête
        error_log("Delivery::update - Object properties before update: " . print_r($this, true));

        $query_parts = [];
        $bind_types = "";
        $bind_params = [];

        // Champs de texte et leurs types (string)
        $text_fields = ['description', 'recipient_name', 'recipient_phone', 'recipient_city', 'destination_address', 'status'];
        foreach ($text_fields as $field) {
            $query_parts[] = "`{$field}` = ?";
            $bind_types .= "s";
            $bind_params[] = htmlspecialchars(strip_tags($this->{$field}));
        }

        // Ajouter le motif de retour
        $query_parts[] = "`return_reason` = ?"; 
        $bind_types .= "s"; 
        $returnReasonValue = $this->return_reason ?? null;
        $bind_params[] = $returnReasonValue; 
        
        // Mettre à jour invoice_id
        $query_parts[] = "`invoice_id` = ?";
        $bind_types .= "i";
        $invoiceIdValue = $this->invoice_id ?? null;
        $bind_params[] = $invoiceIdValue;

        // Mettre à jour is_fragile et accepts_exchange
        $query_parts[] = "`is_fragile` = ?";
        $bind_types .= "i";
        $bind_params[] = intval(htmlspecialchars(strip_tags($this->is_fragile ?? 0)));

        $query_parts[] = "`accepts_exchange` = ?";
        $bind_types .= "i";
        $bind_params[] = intval(htmlspecialchars(strip_tags($this->accepts_exchange ?? 0)));


        // Champs numériques et leurs types (double)
        $numeric_fields = ['weight_kg', 'length_cm', 'L_width_cm', 'height_cm', 'declared_value', 'price'];
        foreach ($numeric_fields as $field) {
            $query_parts[] = "`{$field}` = ?";
            $bind_types .= "d";
            $bind_params[] = floatval(htmlspecialchars(strip_tags($this->{$field})));
        }

        // Gestion spéciale de driver_id (peut être NULL)
        if ($this->driver_id === null) {
            $query_parts[] = "`driver_id` = NULL";
        } else {
            $query_parts[] = "`driver_id` = ?";
            $bind_types .= "i";
            $bind_params[] = intval(htmlspecialchars(strip_tags($this->driver_id)));
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $query_parts) . " WHERE `id` = ?";
        
        $bind_types .= "i"; // Pour l'ID de la livraison dans la clause WHERE
        $bind_params[] = intval(htmlspecialchars(strip_tags($this->id)));


        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::update - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }
        
        // Débogage : log des types et paramètres à lier
        error_log("Delivery::update - Bind Types: " . $bind_types);
        error_log("Delivery::update - Bind Params (count " . count($bind_params) . "): " . print_r($bind_params, true));
        error_log("Delivery::update - Final Query: " . $query);


        // Liens des valeurs
        if (!($stmt->bind_param($bind_types, ...$bind_params))) {
            error_log("Delivery::update - Erreur bind_param: " . $stmt->error);
            $stmt->close();
            return false;
        }


        if ($stmt->execute()) {
            return true;
        }

        error_log("Delivery::update - Erreur lors de l'exécution: " . $stmt->error);
        return false;
    }

    /**
     * Met à jour le statut et/ou le livreur d'une livraison.
     * Utilisé principalement pour l'assignation par scan.
     *
     * @return bool Vrai si la mise à jour est réussie, faux sinon.
     */
    public function updateStatusAndDriver() {
        // Débogage: log des propriétés avant updateStatusAndDriver
        error_log("Delivery::updateStatusAndDriver - Object properties before update: " . print_r($this, true));

        $bind_types = "s"; // Pour le status

        $bind_params = [
            htmlspecialchars(strip_tags($this->status))
        ];

        // Construire la partie de la requête pour driver_id
        $driver_id_set_clause = "";
        if ($this->driver_id === null) {
            $driver_id_set_clause = "driver_id = NULL";
        } else {
            $driver_id_set_clause = "driver_id = ?";
            $bind_types .= "i"; // Type pour driver_id
            $bind_params[] = intval(htmlspecialchars(strip_tags($this->driver_id)));
        }

        $unique_id_val = htmlspecialchars(strip_tags($this->unique_id));
        $bind_types .= "s"; // Type pour unique_id
        $bind_params[] = $unique_id_val;

        $query = "UPDATE " . $this->table_name . " SET status = ?, " . $driver_id_set_clause . " WHERE unique_id = ?";
        
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::updateStatusAndDriver - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }
        
        // Débogage : log des types et paramètres à lier pour updateStatusAndDriver
        error_log("Delivery::updateStatusAndDriver - Bind Types: " . $bind_types);
        error_log("Delivery::updateStatusAndDriver - Bind Params (count " . count($bind_params) . "): " . print_r($bind_params, true));
        error_log("Delivery::updateStatusAndDriver - Final Query: " . $query);


        if (!($stmt->bind_param($types, ...$bind_params))) {
            error_log("Delivery::updateStatusAndDriver - Erreur bind_param: " . $stmt->error);
            $stmt->close();
            return false;
        }


        if ($stmt->execute()) {
            return true;
        }

        error_log("Delivery::updateStatusAndDriver - Erreur lors de l'exécution: " . $stmt->error);
        return false;
    }

    /**
     * Supprime une livraison de la base de données.
     *
     * @return bool Vrai si la suppression est réussie, faux sinon.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::delete - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bind_param("i", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        printf("Erreur lors de la suppression de la livraison : %s.\n", $stmt->error);
        return false;
    }

    /**
     * Calcule le prix d'une livraison en fonction du nombre de colis envoyés par le fournisseur aujourd'hui.
     *
     * @param int $supplierId L'ID du fournisseur.
     * @param string $date La date pour laquelle calculer le nombre de colis (formatYYYY-MM-DD).
     * @return float The calculated price.
     */
    public function calculateDeliveryPrice($supplierId, $date) {
        $query = "SELECT COUNT(*) as total_deliveries FROM " . $this->table_name . " WHERE supplier_id = ? AND DATE(created_at) = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::calculateDeliveryPrice - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return 0.00;
        }
        $stmt->bind_param("is", $supplierId, $date);
        if (!$stmt->execute()) {
            error_log("Delivery::calculateDeliveryPrice - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return 0.00;
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $totalDeliveries = $row['total_deliveries'] ?? 0;

        // Nouvelle logique de tarification basée sur le volume quotidien
        if ($totalDeliveries >= 1 && $totalDeliveries <= 10) {
            return 8.00; // Prix unitaire pour 1 à 10 colis
        } elseif ($totalDeliveries >= 11 && $totalDeliveries <= 20) {
            return 7.50; // Prix unitaire pour 11 à 20 colis
        } elseif ($totalDeliveries >= 21 && $totalDeliveries <= 40) {
            return 6.50; // Prix unitaire pour 21 à 40 colis
        } elseif ($totalDeliveries >= 41 && $totalDeliveries <= 100) {
            return 6.00; // Prix unitaire pour 41 à 100 colis
        } elseif ($totalDeliveries >= 100) {
            return 5.00; // Prix unitaire pour 41 à 100 colis    
        } else {
            return 8.00; // Prix par défaut si hors des tranches définies (ou pour 0 colis)
        }
    }

    /**
     * Calcule les statistiques de revenus et de livraisons pour l'administrateur sur une période donnée.
     *
     * @param string $startDate La date de début (YYYY-MM-DD).
     * @param string $endDate La date de fin (YYYY-MM-DD).
     * @return array Un tableau associatif des statistiques.
     */
    public function calculateAdminDailyRevenue($startDate, $endDate) {
        $query = "SELECT
                    SUM(CASE WHEN status = 'delivered' THEN price ELSE 0 END) as total_revenue_delivered,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 ELSE NULL END) as delivered_count,
                    COUNT(CASE WHEN status = 'returned' THEN 1 ELSE NULL END) as returned_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 ELSE NULL END) as cancelled_count
                  FROM
                    " . $this->table_name . "
                  WHERE
                    DATE(created_at) BETWEEN ? AND ?"; // Utilisation de BETWEEN pour la plage de dates

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::calculateAdminDailyRevenue - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return ['total_revenue_delivered' => 0, 'delivered_count' => 0, 'returned_count' => 0, 'cancelled_count' => 0];
        }
        $stmt->bind_param("ss", $startDate, $endDate); // Deux 's' pour les deux dates
        if (!$stmt->execute()) {
            error_log("Delivery::calculateAdminDailyRevenue - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return ['total_revenue_delivered' => 0, 'delivered_count' => 0, 'returned_count' => 0, 'cancelled_count' => 0];
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();

        // Assurez-vous que toutes les clés sont présentes, même si null de la BDD
        return [
            'total_revenue_delivered' => $row['total_revenue_delivered'] ?? 0,
            'delivered_count' => $row['delivered_count'] ?? 0,
            'returned_count' => $row['returned_count'] ?? 0,
            'cancelled_count' => $row['cancelled_count'] ?? 0
        ];
    }

    /**
     * Récupère le nombre de livraisons par statut pour une période donnée.
     * Utilisé pour le tableau de bord administrateur et autres.
     *
     * @param string $status Le statut à compter.
     * @param string $startDate La date de début (YYYY-MM-DD).
     * @param string $endDate La date de fin (YYYY-MM-DD).
     * @return int Le nombre de livraisons pour ce statut et cette période.
     */
    public function getDeliveryStatusCountByDate($status, $startDate, $endDate) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE status = ? AND DATE(created_at) BETWEEN ? AND ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::getDeliveryStatusCountByDate - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return 0;
        }
        $stmt->bind_param("sss", $status, $startDate, $endDate); // Trois 's' pour statut et deux dates
        if (!$stmt->execute()) {
            error_log("Delivery::getDeliveryStatusCountByDate - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }


    /**
     * Récupère les revenus journaliers pour une période donnée.
     *
     * @param string $startDate Date de début (YYYY-MM-DD).
     * @param string $endDate Date de fin (YYYY-MM-DD).
     * @return array Tableau associatif des revenus par date.
     */
    public function getDailyRevenueForPeriod($startDate, $endDate) {
        $query = "SELECT DATE(created_at) as delivery_date, SUM(price) as daily_revenue
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'delivered'
                  GROUP BY delivery_date
                  ORDER BY delivery_date ASC";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::getDailyRevenueForPeriod - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return [];
        }
        $stmt->bind_param("ss", $startDate, $endDate);
        if (!$stmt->execute()) {
            error_log("Delivery::getDailyRevenueForPeriod - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return [];
        }
        $result = $stmt->get_result();
        $stmt->close();

        $dailyRevenues = [];
        // Initialiser toutes les dates de la période à 0
        $currentDate = $startDate;
        while (strtotime($currentDate) <= strtotime($endDate)) {
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        while ($row = $result->fetch_assoc()) {
            $dailyRevenues[$row['delivery_date']] = $row['daily_revenue'];
        }
        return $dailyRevenues;
    }

    /**
     * Récupère le nombre de livraisons par statut pour une période donnée.
     *
     * @param string $startDate Date de début (YYYY-MM-DD).
     * @param string $endDate Date de fin (YYYY-MM-DD).
     * @return array Tableau associatif des comptes par statut.
     */
    public function getDeliveryStatusCountsForPeriod($startDate, $endDate) {
        $query = "SELECT status, COUNT(*) as count
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::getDeliveryStatusCountsForPeriod - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return ['pending' => 0, 'assigned' => 0, 'in_transit' => 0, 'delivered' => 0, 'returned' => 0, 'cancelled' => 0];
        }
        $stmt->bind_param("ss", $startDate, $endDate);
        if (!$stmt->execute()) {
            error_log("Delivery::getDeliveryStatusCountsForPeriod - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return ['pending' => 0, 'assigned' => 0, 'in_transit' => 0, 'delivered' => 0, 'returned' => 0, 'cancelled' => 0];
        }
        $result = $stmt->get_result();
        $stmt->close();

        $statusCounts = [
            'pending' => 0,
            'assigned' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'returned' => 0,
            'cancelled' => 0
        ];

        while ($row = $result->fetch_assoc()) {
            $statusCounts[$row['status']] = $row['count'];
        }
        return $statusCounts;
    }

    /**
     * Calcule les statistiques journalières pour le tableau de bord du fournisseur.
     *
     * @param int $supplierId L'ID du fournisseur.
     * @param string $startDate La date de début (YYYY-MM-DD).
     * @param string $endDate La date de fin (YYYY-MM-DD).
     * @return array Un tableau associatif des statistiques.
     */
    public function calculateSupplierDailyStats($supplierId, $startDate, $endDate) {
        $query = "SELECT
                    COUNT(CASE WHEN status = 'pending' THEN 1 ELSE NULL END) as pending_count,
                    COUNT(CASE WHEN status = 'assigned' THEN 1 ELSE NULL END) as assigned_count,
                    COUNT(CASE WHEN status = 'in_transit' THEN 1 ELSE NULL END) as in_transit_count,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 ELSE NULL END) as delivered_count,
                    COUNT(CASE WHEN status = 'returned' THEN 1 ELSE NULL END) as returned_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 ELSE NULL END) as cancelled_count,
                    COUNT(*) as total_deliveries_count
                  FROM
                    " . $this->table_name . "
                  WHERE
                    supplier_id = ? AND DATE(created_at) BETWEEN ? AND ?";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::calculateSupplierDailyStats - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return [
                'pending_count' => 0, 'assigned_count' => 0, 'in_transit_count' => 0,
                'delivered_count' => 0, 'returned_count' => 0, 'cancelled_count' => 0,
                'total_deliveries_count' => 0
            ];
        }
        $stmt->bind_param("iss", $supplierId, $startDate, $endDate);
        if (!$stmt->execute()) {
            error_log("Delivery::calculateSupplierDailyStats - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return [
                'pending_count' => 0, 'assigned_count' => 0, 'in_transit_count' => 0,
                'delivered_count' => 0, 'returned_count' => 0, 'cancelled_count' => 0,
                'total_deliveries_count' => 0
            ];
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();

        // Assurez-vous que toutes les clés sont présentes, même si la requête n'a pas de résultats
        return [
            'pending_count' => $row['pending_count'] ?? 0,
            'assigned_count' => $row['assigned_count'] ?? 0,
            'in_transit_count' => $row['in_transit_count'] ?? 0,
            'delivered_count' => $row['delivered_count'] ?? 0,
            'returned_count' => $row['returned_count'] ?? 0,
            'cancelled_count' => $row['cancelled_count'] ?? 0,
            'total_deliveries_count' => $row['total_deliveries_count'] ?? 0
        ];
    }

    /**
     * Calcule les statistiques journalières pour un livreur.
     *
     * @param int $driverId L'ID du livreur.
     * @param string $startDate La date de début (YYYY-MM-DD).
     * @param string $endDate La date de fin (YYYY-MM-DD).
     * @return array Un tableau contenant les statistiques (total, livrés, retournés, en cours).
     */
    public function calculateDriverDailyStats($driverId, $startDate, $endDate) {
        $stats = [
            'total_assigned' => 0,
            'delivered_count' => 0,
            'returned_count' => 0,
            'in_transit_count' => 0,
            'pending_count' => 0, // Ajouté pour les livraisons encore "pending" mais assignées
            'assigned_count' => 0 // Ajouté pour les livraisons "assigned"
        ];

        // Requête pour récupérer les statuts des colis assignés au livreur pour la période spécifiée
        $query = "SELECT
                    status
                  FROM
                    " . $this->table_name . "
                  WHERE
                    driver_id = ? AND DATE(created_at) BETWEEN ? AND ?"; // Filtrer par created_at dans la plage

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::calculateDriverDailyStats - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return $stats; // Retourne les stats par default en cas d'erreur
        }
        $stmt->bind_param("iss", $driverId, $startDate, $endDate);
        if (!$stmt->execute()) {
            error_log("Delivery::calculateDriverDailyStats - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return $stats; // Retourne les stats par default en cas d'erreur
        }
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $stats['total_assigned']++; // Compte tous les colis liés au livreur ce jour-là
            switch ($row['status']) {
                case 'delivered':
                    $stats['delivered_count']++;
                    break;
                case 'returned':
                    $stats['returned_count']++;
                    break;
                case 'in_transit':
                    $stats['in_transit_count']++;
                    break;
                case 'pending':
                    $stats['pending_count']++;
                    break;
                case 'assigned':
                    $stats['assigned_count']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Compte le nombre total de livraisons.
     * @return int Le nombre total de livraisons.
     */
    public function countAllDeliveries() {
        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::countAllDeliveries - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return 0;
        }
        if (!$stmt->execute()) {
            error_log("Delivery::countAllDeliveries - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * NOUVELLE METHODE: Récupère les livraisons facturables pour un fournisseur dans une période donnée.
     * Ces livraisons doivent être au statut 'delivered' et ne pas encore avoir d'invoice_id.
     *
     * @param int $supplierId L'ID du fournisseur.
     * @param string $startDate Date de début de la période de livraisons (YYYY-MM-DD).
     * @param string $endDate Date de fin de la période de livraisons (YYYY-MM-DD).
     * @return mysqli_result|null Le jeu de résultats des livraisons facturables, ou null en cas d'erreur.
     */
    public function getDeliveriesForInvoicing($supplierId, $startDate, $endDate) {
        $query = "SELECT
                    id, unique_id, supplier_id, description, price, created_at
                  FROM
                    " . $this->table_name . "
                  WHERE
                    supplier_id = ?
                    AND status = 'delivered'
                    AND invoice_id IS NULL
                    AND DATE(created_at) BETWEEN ? AND ?
                  ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::getDeliveriesForInvoicing - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null;
        }

        $stmt->bind_param("iss", $supplierId, $startDate, $endDate);

        if (!$stmt->execute()) {
            error_log("Delivery::getDeliveriesForInvoicing - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * NOUVELLE METHODE: Lie une liste de livraisons à un ID de facture.
     *
     * @param array $deliveryIds Tableau des IDs des livraisons à lier.
     * @param int $invoiceId L'ID de la facture à lier.
     * @return bool Vrai si la mise à jour est réussie pour toutes les livraisons, faux sinon.
     */
    public function linkDeliveriesToInvoice(array $deliveryIds, $invoiceId) {
        if (empty($deliveryIds)) {
            return true; // Rien à lier
        }

        // Créer une chaîne de placeholders pour l'IN clause
        $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));
        
        $query = "UPDATE " . $this->table_name . " SET invoice_id = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Delivery::linkDeliveriesToInvoice - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        // Construire la chaîne de types: 'i' pour invoice_id, puis 'i' pour chaque delivery_id
        $types = "i" . str_repeat("i", count($deliveryIds));
        
        // Construire le tableau de paramètres: invoice_id, puis tous les delivery_ids
        $params = array_merge([$invoiceId], $deliveryIds);

        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Delivery::linkDeliveriesToInvoice - Erreur bind_param: " . $stmt->error);
            $stmt->close();
            return false;
        }

        if (!$stmt->execute()) {
            error_log("Delivery::linkDeliveriesToInvoice - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }
}
