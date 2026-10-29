<?php
// Fichier: app/controllers/DriverController.php - CONTRÔLEUR LIVREUR

require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../models/User.php'; // Pour interagir avec le modèle User si nécessaire
require_once __DIR__ . '/Controller.php'; // Inclusion du contrôleur de base

class DriverController extends Controller { // Hérite de Controller
    private $deliveryModel;
    private $userModel;

    /**
     * Constructeur du DriverController.
     * Initialise les modèles Delivery et User.
     */
    public function __construct() {
        // PAS d'appel à parent::__construct() ici, car Controller.php n'a pas de __construct()
        $this->deliveryModel = new Delivery();
        $this->userModel = new User();
    }

    /**
     * Affiche le tableau de bord du livreur.
     * Récupère les statistiques de livraisons journalières pour le livreur.
     */
    public function dashboard() {
        $this->checkAuth('driver'); // Vérification d'authentification et de rôle

        $driverId = $_SESSION['user_id'];
        
        // Récupérer les dates de début et de fin depuis les paramètres GET
        // Si les dates sont vides ou non définies, utiliser la date actuelle par défaut pour le filtre
        $filterStartDate = $_GET['start_date'] ?? date('Y-m-d');
        $filterEndDate = $_GET['end_date'] ?? date('Y-m-d');

        // Récupérer les statistiques du livreur pour la période filtrée
        $driverStats = $this->deliveryModel->calculateDriverDailyStats($driverId, $filterStartDate, $filterEndDate); 

        $this->render('drivers/dashboard', [
            'username' => $_SESSION['username'],
            'stats' => $driverStats,
            'filter_start_date' => $filterStartDate, // Passons les dates à la vue
            'filter_end_date' => $filterEndDate       // Passons les dates à la vue
        ]); // Utilise la méthode render du contrôleur de base
    }

    /**
     * Affiche la liste des livraisons assignées au livreur connecté.
     * Accessible uniquement par les livreurs.
     */
    public function listMyDeliveries() {
        $this->checkAuth('driver'); // Vérification d'authentification et de rôle
        $driverId = $_SESSION['user_id'];

        // Récupérer les dates de début et de fin depuis les paramètres GET
        // Si les dates sont vides ou non définies, utiliser la date actuelle par default pour le filtre
        $filterStartDate = $_GET['start_date'] ?? date('Y-m-d');
        $filterEndDate = $_GET['end_date'] ?? date('Y-m-d');

        // Utilise la méthode readAll du modèle Delivery pour filtrer par driver_id ET par date
        // readAll($supplierId = null, $driverId = null, $startDate = null, $endDate = null)
        $deliveries = $this->deliveryModel->readAll(null, $driverId, $filterStartDate, $filterEndDate);

        // Passer les dates de filtre à la vue pour pré-remplir le formulaire
        $this->render('drivers/deliveries', [
            'deliveries' => $deliveries,
            'filter_start_date' => $filterStartDate, // Passé à la vue pour pré-remplir le champ date
            'filter_end_date' => $filterEndDate       // Passé à la vue pour pré-remplir le champ date
        ]);
    }

    /**
     * Gère la mise à jour du statut d'une livraison par le livreur.
     * Accessible uniquement par les livreurs.
     */
    public function updateDeliveryStatus() {
        $this->checkAuth('driver'); // Vérification d'authentification et de rôle

        // Assurez-vous que la réponse est en JSON, même en cas d'erreur
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_id']) && isset($_POST['status'])) {
            $deliveryId = $_POST['delivery_id'];
            $newStatus = $_POST['status'];
            $returnReason = $_POST['return_reason'] ?? null; // NOUVEAU: Récupérer le motif de retour

            $driverId = $_SESSION['user_id']; // Le livreur qui met à jour

            // Charger la livraison en passant l'ID en paramètre
            $delivery = $this->deliveryModel->readOneById($deliveryId);

            if ($delivery) { // Vérifier si la livraison a été trouvée
                if ($delivery->driver_id == $driverId) {
                    $delivery->status = $newStatus; // Utiliser l'objet $delivery chargé
                    
                    // NOUVEAU: Assigner le motif de retour à l'objet delivery si le statut est 'returned'
                    if ($newStatus === 'returned') {
                        $delivery->return_reason = $returnReason; 
                    } else {
                        // Si le statut n'est pas 'returned', s'assurer que le motif est NULL dans l'objet
                        $delivery->return_reason = null; 
                    }

                    // La méthode update() gère toutes les propriétés de l'objet model, y compris return_reason
                    if ($this->deliveryModel->update()) { 
                        echo json_encode([
                            'success' => true,
                            'message' => "Statut du colis mis à jour avec succès !"
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => "Erreur lors de la mise à jour du statut du colis."
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Vous n'êtes pas autorisé à modifier le statut de ce colis."
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Colis non trouvé."
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Requête invalide. Paramètres manquants."
            ]);
        }
        exit(); // Important d'arrêter l'exécution après la réponse JSON
    }
}
