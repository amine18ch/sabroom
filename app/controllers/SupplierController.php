<?php
// Fichier: app/controllers/SupplierController.php - CONTRÔLEUR FOURNISSEUR

require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../models/User.php'; // Pour interagir avec le modèle User si nécessaire
require_once __DIR__ . '/../models/Invoice.php'; // Inclure le modèle Invoice
require_once __DIR__ . '/Controller.php'; // Inclusion du contrôleur de base

class SupplierController extends Controller { // Hérite de Controller
    private $deliveryModel;
    private $userModel;
    private $invoiceModel; // Propriété pour le modèle Invoice

    /**
     * Constructeur du SupplierController.
     * Initialise les modèles Delivery, User et Invoice.
     */
    public function __construct() {
        // PAS d'appel à parent::__construct() ni de checkAuth() ici
        // La vérification d'authentification se fera au début de chaque méthode publique.
        $this->deliveryModel = new Delivery();
        $this->userModel = new User();
        $this->invoiceModel = new Invoice(); // NOUVEAU: Instancier le modèle Invoice
    }

    /**
     * Affiche le tableau de bord du fournisseur.
     * Récupère les statistiques de livraisons journalières pour le fournisseur.
     */
    public function dashboard() {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle

        $supplierId = $_SESSION['user_id'];
        
        // Récupérer les dates de début et de fin depuis les paramètres GET
        // Si les dates sont vides ou non définies, utiliser la date actuelle par défaut pour le filtre
        $filterStartDate = $_GET['start_date'] ?? date('2025-05-02');
        $filterEndDate = $_GET['end_date'] ?? date('Y-m-d');

        // Récupérer les statistiques du fournisseur pour la période filtrée
        $supplierStats = $this->deliveryModel->calculateSupplierDailyStats($supplierId, $filterStartDate, $filterEndDate);

        $this->render('suppliers/dashboard', [
            'username' => $_SESSION['username'],
            'stats' => $supplierStats,
            'filter_start_date' => $filterStartDate, // Passons les dates à la vue
            'filter_end_date' => $filterEndDate       // Passons les dates à la vue
        ]); // Utilise la méthode render du contrôleur de base
    }

    /**
     * Affiche le formulaire de création de livraison.
     * Accessible uniquement par les fournisseurs.
     */
    public function showCreateDeliveryForm() {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle
        $this->render('suppliers/create_delivery'); // Utilise la méthode render du contrôleur de base
    }

    /**
     * Crée une nouvelle livraison.
     * Accessible uniquement par les fournisseurs.
     */
    public function createDelivery() {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Assigner les données du POST aux propriétés du modèle Delivery
            $this->deliveryModel->supplier_id = $_SESSION['user_id'];
            $this->deliveryModel->description = $_POST['description'] ?? '';
            $this->deliveryModel->recipient_name = $_POST['recipient_name'] ?? '';
            $this->deliveryModel->recipient_phone = $_POST['recipient_phone'] ?? '';
            $this->deliveryModel->recipient_city = $_POST['recipient_city'] ?? '';
            $this->deliveryModel->destination_address = $_POST['destination_address'] ?? '';
            $this->deliveryModel->weight_kg = $_POST['weight_kg'] ?? 0.00;
            $this->deliveryModel->length_cm = $_POST['length_cm'] ?? 0.00;
            $this->deliveryModel->L_width_cm = $_POST['width_cm'] ?? 0.00; // Assurez-vous que le nom du champ est correct
            $this->deliveryModel->height_cm = $_POST['height_cm'] ?? 0.00;
            $this->deliveryModel->declared_value = $_POST['declared_value'] ?? 0.00;
            $this->deliveryModel->is_fragile = isset($_POST['is_fragile']) ? 1 : 0; // NOUVEAU: Récupérer is_fragile
            $this->deliveryModel->accepts_exchange = isset($_POST['accepts_exchange']) ? 1 : 0; // NOUVEAU: Récupérer accepts_exchange
            $this->deliveryModel->status = 'pending'; // Statut initial

            // Validation simple des champs obligatoires
            if (empty($this->deliveryModel->description) ||
                empty($this->deliveryModel->recipient_name) ||
                empty($this->deliveryModel->recipient_phone) ||
                empty($this->deliveryModel->recipient_city) ||
                empty($this->deliveryModel->destination_address)) {
                $_SESSION['message'] = "Veuillez remplir tous les champs obligatoires du colis et du destinataire.";
                $_SESSION['message_type'] = "danger";
                header('Location: /sabroom/public/supplier/deliveries/create');
                exit();
            }

            // Calculer le prix de la livraison basé sur le nombre de colis envoyés aujourd'hui par ce fournisseur
            $today = date('Y-m-d');
            $priceToAdd = $this->deliveryModel->calculateDeliveryPrice($this->deliveryModel->supplier_id, $today);
            $this->deliveryModel->price = $priceToAdd; // Assigner le prix calculé par le système

            if ($this->deliveryModel->create()) {
                $_SESSION['message'] = "Livraison créée avec succès ! Code-barres : " . htmlspecialchars($this->deliveryModel->unique_id);
                $_SESSION['message_type'] = "success";
                header('Location: /sabroom/public/supplier/deliveries');
                exit();
            } else {
                $_SESSION['message'] = "Erreur lors de la création de la livraison.";
                $_SESSION['message_type'] = "danger";
                header('Location: /sabroom/public/supplier/deliveries/create');
                exit();
            }
        } else {
            header('Location: /sabroom/public/supplier/deliveries/create');
            exit();
        }
    }

    /**
     * Affiche la liste des livraisons pour un fournisseur spécifique.
     * Accessible uniquement par les fournisseurs.
     */
    public function listMyDeliveries() {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle
        $supplierId = $_SESSION['user_id'];

        // Récupérer les dates de début et de fin depuis les paramètres GET
        // Si les dates sont vides ou non définies, utiliser la date actuelle par default pour le filtre
        $filterStartDate = $_GET['start_date'] ?? date('Y-m-d');
        $filterEndDate = $_GET['end_date'] ?? date('Y-m-d');

        // Utilise la méthode readAll du modèle Delivery pour filtrer par supplier_id ET par date
        // readAll($supplierId = null, $driverId = null, $startDate = null, $endDate = null)
        $deliveries = $this->deliveryModel->readAll($supplierId, null, $filterStartDate, $filterEndDate);

        // Passer les dates de filtre à la vue pour pré-remplir le formulaire
        $this->render('suppliers/deliveries', [
            'deliveries' => $deliveries,
            'filter_start_date' => $filterStartDate, // Passé à la vue pour pré-remplir le champ date
            'filter_end_date' => $filterEndDate       // Passé à la vue pour pré-remplir le champ date
        ]);
    }

    /**
     * Imprime le bordereau d'expédition pour une livraison.
     * Accessible uniquement par les fournisseurs.
     * @param int $id L'ID de la livraison.
     */
    public function printBordereau($id) {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle
        $this->deliveryModel->id = $id;
        if ($this->deliveryModel->readOneById($id)) { // Pass id to readOneById
            // Assurez-vous que le fournisseur actuel est bien le propriétaire de la livraison
            if ($this->deliveryModel->supplier_id != $_SESSION['user_id']) {
                $_SESSION['message'] = "Vous n'avez pas l'autorisation d'imprimer ce bordereau.";
                $_SESSION['message_type'] = "danger";
                header('Location: /sabroom/public/supplier/deliveries');
                exit();
            }

            // Récupérer les informations complètes du fournisseur pour le bordereau
            $supplierInfo = $this->userModel->getUserById($this->deliveryModel->supplier_id);
            if (!$supplierInfo) {
                $_SESSION['message'] = "Informations fournisseur non trouvées.";
                $_SESSION['message_type'] = "danger";
                header('Location: /sabroom/public/supplier/deliveries');
                exit();
            }

            // Pour l'impression, nous n'incluons pas le layout principal
            // La vue 'suppliers/bordereau' doit être une page HTML complète et autonome
            require_once __DIR__ . '/../views/suppliers/bordereau.php'; // Charge directement la vue du bordereau
        } else {
            $_SESSION['message'] = "Livraison non trouvée.";
            $_SESSION['message_type'] = "danger";
            header('Location: /sabroom/public/supplier/deliveries');
            exit();
        }
    }

    /**
     * Affiche la liste des factures pour le fournisseur connecté.
     * Gère les filtres de date et statut.
     */
    public function myInvoices() {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle

        $supplierId = $_SESSION['user_id'];

        // Récupérer les paramètres de filtre depuis GET
        $filterStartDate = $_GET['start_date'] ?? null;
        $filterEndDate = $_GET['end_date'] ?? null;
        $filterStatus = $_GET['status'] ?? null;

        // Lire les factures spécifiques à ce fournisseur
        $invoices = $this->invoiceModel->readAll(
            $supplierId,        // supplierId
            $filterStartDate,   // startDate
            $filterEndDate,     // endDate
            $filterStatus       // statusFilter
        );

        // Passer les données à la vue
        $this->render('suppliers/invoices/list', [
            'invoices' => $invoices,
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => $filterEndDate,
            'filter_status' => $filterStatus
        ]);
    }

    /**
     * NOUVELLE METHODE: Affiche les détails d'une facture spécifique pour le fournisseur.
     * @param int $invoiceId L'ID de la facture à visualiser.
     */
    public function viewInvoice($invoiceId) {
        $this->checkAuth('supplier'); // Vérification d'authentification et de rôle

        $invoice = $this->invoiceModel->readOneById($invoiceId);

        // Vérifier si la facture existe ET si elle appartient bien au fournisseur connecté
        if (!$invoice || $invoice->supplier_id != $_SESSION['user_id']) {
            $_SESSION['message'] = "Facture non trouvée ou vous n'avez pas les permissions.";
            $_SESSION['message_type'] = "danger";
            header('Location: /sabroom/public/supplier/invoices'); // Rediriger vers la liste des factures du fournisseur
            exit();
        }

        // Récupérer toutes les livraisons liées à cette facture pour ce fournisseur
        // Appel à readAll: readAll($supplierId, $driverId, $dateFilterParam, $endDate, $statusFilter, $searchQuery, $invoiceId)
        $deliveries_on_invoice = $this->deliveryModel->readAll(
            $invoice->supplier_id, // Filtrer par le fournisseur de la facture
            null, // Pas de filtre par livreur
            null, // Pas de filtre de date (on veut tous les colis de cette facture)
            null, // Pas de filtre de date
            null, // Pas de filtre de statut (on veut tous les colis de cette facture)
            null, // Pas de recherche
            $invoiceId // Filtrer par l'ID de la facture
        );

        $this->render('suppliers/invoices/view', [
            'invoice' => $invoice,
            'deliveries_on_invoice' => $deliveries_on_invoice
        ]);
    }
}
