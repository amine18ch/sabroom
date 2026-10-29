<?php
// Fichier: app/controllers/DeliveryController.php - CONTRÔLEUR DE LIVRAISON

require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/Controller.php'; // Inclusion du contrôleur de base

class DeliveryController extends Controller { // Hérite de Controller
    private $deliveryModel;
    private $userModel;

    public function __construct() {
        // PAS d'appel à parent::__construct() ni de checkAuth() ici
        // La vérification d'authentification se fera au début de chaque méthode publique.
        $this->deliveryModel = new Delivery();
        $this->userModel = new User();
    }

    /**
     * Affiche la liste de toutes les livraisons.
     * Accessible uniquement par l'administrateur.
     */
    public function listAllDeliveries() {
        $this->checkAuth('admin'); // Vérification d'authentification et de rôle

        $deliveries = $this->deliveryModel->readAll(); // Récupère toutes les livraisons

        $this->render('admin/all_deliveries', ['deliveries' => $deliveries]); // Utilise la méthode render
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
            $this->deliveryModel->L_width_cm = $_POST['width_cm'] ?? 0.00;
            $this->deliveryModel->height_cm = $_POST['height_cm'] ?? 0.00;
            $this->deliveryModel->declared_value = $_POST['declared_value'] ?? 0.00;
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
            // Afficher le formulaire de création
            $this->render('suppliers/create_delivery');
        }
    }

    /**
     * Affiche la liste des livraisons pour un fournisseur spécifique.
     * Accessible uniquement par les fournisseurs.
     */
    public function listMyDeliveries() {
        $this->checkAuth('supplier');
        $supplierId = $_SESSION['user_id'];
        // Le premier paramètre est supplierId, le second est driverId (null ici)
        $deliveries = $this->deliveryModel->readAll($supplierId, null);

        $this->render('suppliers/deliveries', ['deliveries' => $deliveries]);
    }

    /**
     * Imprime le bordereau d'expédition pour une livraison.
     * Accessible uniquement par les fournisseurs.
     * @param int $id L'ID de la livraison.
     */
    public function printBordereau($id) {
        $this->checkAuth('supplier');
        $this->deliveryModel->id = $id;
        if ($this->deliveryModel->readOneById()) {
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
}
