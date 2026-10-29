<?php
// Fichier: app/models/Invoice.php

// Le modèle Invoice gère les opérations de base de données pour les factures.

require_once __DIR__ . '/Database.php'; // Inclure la connexion à la base de données

class Invoice {
    private $conn;
    private $table_name = "invoices";

    // Propriétés de l'objet Invoice, correspondant aux colonnes de la table 'invoices'
    public $id;
    public $supplier_id;
    public $invoice_date;
    public $due_date;
    public $total_amount;
    public $status; // pending, paid, cancelled
    public $created_at;
    public $updated_at;

    // Propriétés jointes pour l'affichage (non stockées dans la BDD de cette table)
    public $supplier_username;
    public $supplier_company_name;

    /**
     * Constructeur de la classe Invoice.
     * Initialise la connexion à la base de données via le Singleton Database.
     */
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Crée une nouvelle facture dans la base de données.
     *
     * @return int|false L'ID de la facture créée en cas de succès, false sinon.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    supplier_id = ?,
                    invoice_date = ?,
                    due_date = ?,
                    total_amount = ?,
                    status = ?";

        $stmt = $this->conn->prepare($query);

        // Nettoyage des données
        $this->supplier_id = htmlspecialchars(strip_tags($this->supplier_id));
        $this->invoice_date = htmlspecialchars(strip_tags($this->invoice_date));
        $this->due_date = htmlspecialchars(strip_tags($this->due_date));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Liaison des valeurs
        $stmt->bind_param("issds",
            $this->supplier_id,
            $this->invoice_date,
            $this->due_date,
            $this->total_amount,
            $this->status
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id; // Retourne l'ID de la facture nouvellement créée
        }

        error_log("Invoice::create - Erreur lors de la création de la facture : " . $stmt->error);
        return false;
    }

    /**
     * Lit une facture par son ID.
     * Hydrate l'objet courant avec les données de la facture et joint les infos du fournisseur.
     *
     * @param int $id L'ID de la facture à récupérer.
     * @return Invoice|null L'objet Invoice hydraté, ou null si non trouvé/erreur.
     */
    public function readOneById($id) {
        $query = "SELECT
                    i.id, i.supplier_id, i.invoice_date, i.due_date, i.total_amount, i.status,
                    i.created_at, i.updated_at,
                    s.username as supplier_username, s.company_name as supplier_company_name
                  FROM
                    " . $this->table_name . " i
                  LEFT JOIN
                    users s ON i.supplier_id = s.id
                  WHERE
                    i.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Invoice::readOneById - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Invoice::readOneById - Erreur lors de l'exécution: " . $stmt->error . " ID: " . $id);
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
            return $this;
        }
        return null;
    }

    /**
     * Lit toutes les factures, avec des filtres optionnels pour le fournisseur et la plage de dates.
     * Joint les informations du fournisseur.
     *
     * @param int|null $supplierId L'ID du fournisseur pour filtrer, ou null pour toutes les factures.
     * @param string|null $startDate Date de début (YYYY-MM-DD) pour le filtre, ou null.
     * @param string|null $endDate Date de fin (YYYY-MM-DD) pour le filtre, ou null.
     * @param string|null $statusFilter Statut à filtrer (ex: 'pending'), ou null.
     * @return mysqli_result|null Le jeu de résultats de la requête, ou null en cas d'erreur.
     */
    public function readAll($supplierId = null, $startDate = null, $endDate = null, $statusFilter = null) {
        $query = "SELECT
                    i.id, i.supplier_id, i.invoice_date, i.due_date, i.total_amount, i.status,
                    i.created_at, i.updated_at,
                    s.username as supplier_username, s.company_name as supplier_company_name
                  FROM
                    " . $this->table_name . " i
                  LEFT JOIN
                    users s ON i.supplier_id = s.id";

        $conditions = [];
        $params = [];
        $types = "";

        if ($supplierId !== null) {
            $conditions[] = "i.supplier_id = ?";
            $params[] = $supplierId;
            $types .= "i";
        }
        if ($startDate !== null && $endDate !== null && $startDate !== '' && $endDate !== '') {
            $conditions[] = "DATE(i.invoice_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }
        if ($statusFilter !== null && $statusFilter !== '') {
            $conditions[] = "i.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY i.invoice_date DESC";

        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            error_log("Invoice::readAll - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return null;
        }

        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("Invoice::readAll - Erreur lors de l'exécution: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Met à jour le statut d'une facture.
     *
     * @param int $id L'ID de la facture.
     * @param string $newStatus Le nouveau statut (pending, paid, cancelled).
     * @return bool Vrai si la mise à jour est réussie, faux sinon.
     */
    public function updateStatus($id, $newStatus) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Invoice::updateStatus - Erreur de préparation de la requête: " . $this->conn->error . " Query: " . $query);
            return false;
        }

        $newStatus = htmlspecialchars(strip_tags($newStatus));
        $id = htmlspecialchars(strip_tags($id));

        $stmt->bind_param("si", $newStatus, $id);

        if ($stmt->execute()) {
            return true;
        }

        error_log("Invoice::updateStatus - Erreur lors de la mise à jour du statut : " . $stmt->error);
        return false;
    }

    /**
     * Convertit l'objet Invoice en un tableau associatif.
     * Utile pour renvoyer des données via JSON ou pour débogage.
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'supplier_username' => $this->supplier_username,
            'supplier_company_name' => $this->supplier_company_name,
        ];
    }
}
