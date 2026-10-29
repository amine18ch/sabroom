<?php
// Fichier: app/controllers/AdminController.php - CONTRÔLEUR ADMINISTRATEUR

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../models/Invoice.php'; // Inclure le modèle Invoice
require_once __DIR__ . '/Controller.php'; // Inclusion du contrôleur de base

class AdminController extends Controller { // Hérite de Controller
    private $userModel;
    private $deliveryModel;
    private $invoiceModel; // Propriété pour le modèle Invoice

    // Ajout de la BASE_URL pour des redirections cohérentes
    const BASE_URL = '/sabroom/public'; 

    /**
     * Constructeur de l'AdminController.
     * Initialise les modèles User, Delivery et Invoice.
     */
    public function __construct() {
        $this->userModel = new User();
        $this->deliveryModel = new Delivery();
        $this->invoiceModel = new Invoice(); // Instancier le modèle Invoice
    }

    // =================================================================
    // MÉTODES DE VÉRIFICATION D'ACCÈS (Implémentation locale pour le fix)
    // Ces méthodes sont incluses ici pour garantir que le Superadmin est autorisé,
    // même si la version dans Controller.php est défectueuse.
    // =================================================================

    /**
     * ✅ CORRECTION DU BUG DE REDIRECTION.
     * Vérifie l'authentification et l'autorisation de l'utilisateur.
     * Cette version locale remplace la méthode du parent (Controller.php).
     * @param string $requiredRole Le rôle minimum requis ('admin', 'supplier', 'driver', etc.).
     */
    protected function checkAuth($requiredRole = 'user') {
        // 1. Vérification de l'authentification
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['message'] = "Veuillez vous connecter pour accéder à cette page.";
            $_SESSION['message_type'] = "warning";
            header('Location: ' . self::BASE_URL . '/login');
            exit();
        }

        $userRole = $_SESSION['user_role'];
        $allowed = false;

        // 2. Vérification de l'autorisation (rôle)
        switch ($requiredRole) {
            case 'admin':
                // Autorise admin ET superadmin
                if ($userRole === 'admin' || $userRole === 'superadmin') {
                    $allowed = true;
                }
                break;
            case 'supplier':
                // Autorise supplier ET superadmin
                if ($userRole === 'supplier' || $userRole === 'superadmin') {
                    $allowed = true;
                }
                break;
            case 'driver':
                // Autorise driver ET superadmin
                if ($userRole === 'driver' || $userRole === 'superadmin') {
                    $allowed = true;
                }
                break;
            default:
                // Pour tout autre rôle spécifique
                if ($userRole === $requiredRole || $userRole === 'superadmin') {
                    $allowed = true;
                }
                break;
        }

        if (!$allowed) {
            // Pas autorisé
            $_SESSION['message'] = "Accès refusé. Vous n'avez pas l'autorisation d'accéder à cette section.";
            $_SESSION['message_type'] = "danger";
            $this->redirectBasedOnRole($userRole); 
            exit();
        }
    }
    
    /**
     * Méthode d'aide pour rediriger l'utilisateur vers son dashboard par défaut.
     * Utile pour renvoyer un utilisateur sur la bonne page en cas d'accès non autorisé.
     * (Similaire à celle utilisée dans AuthController).
     */
    private function redirectBasedOnRole($role) {
        switch ($role) {
            case 'superadmin':
            case 'admin':
                header('Location: ' . self::BASE_URL . '/admin/dashboard'); 
                break;
            case 'supplier':
                header('Location: ' . self::BASE_URL . '/supplier/dashboard');
                break;
            case 'driver':
                header('Location: ' . self::BASE_URL . '/driver/dashboard');
                break;
            default:
                header('Location: ' . self::BASE_URL . '/dashboard');
                break;
        }
        exit();
    }


    /**
     * Méthode d'aide pour vérifier si l'utilisateur est STRICTEMENT Super Administrateur.
     * Utilisée uniquement pour les fonctions de facturation qui nécessitent le plus haut niveau d'accès.
     */
    private function checkSuperAdminOnlyAccess() {
        // Vérifie si l'utilisateur est authentifié ET si son rôle est strictement 'superadmin'
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
            $_SESSION['message'] = "Accès refusé. Seul le Super Administrateur peut accéder à cette section.";
            $_SESSION['message_type'] = "danger";
            $this->redirectBasedOnRole($_SESSION['user_role'] ?? 'default');
            exit();
        }
    }
    
    // =================================================================
    // FIN DES MÉTODES D'ACCÈS
    // =================================================================

    /**
     * Affiche le tableau de bord de l'administrateur.
     * * CORRECTION APPORTÉE: S'assurer que les totaux (livraisons, utilisateurs, fournisseurs)
     * sont bien des totaux absolus (tous temps) pour le Superadmin/Admin.
     */
    public function dashboard() {
        $this->checkAuth('admin'); // Autorise admin ET superadmin

        // Récupérer le nom d'utilisateur pour l'affichage de bienvenue
        $username = $_SESSION['username'] ?? 'Admin';

        // --- GESTION DES FILTRES DE DATE (pour le dashboard) ---
        // Récupérer les dates de début et de fin depuis les paramètres GET, avec des valeurs par défaut
        $filterStartDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days')); // Par défaut, les 7 derniers jours pour une vue initiale utile
        $filterEndDate = $_GET['end_date'] ?? date('Y-m-d');

        // For charts, use the selected period.
        $chartStartDate = $filterStartDate;
        $chartEndDate = $filterEndDate;


        // Statistiques globales du tableau de bord (pour la période filtrée)
        $admin_stats_period = $this->deliveryModel->calculateAdminDailyRevenue($filterStartDate, $filterEndDate);

        // Récupérer les totaux par statut pour les badges des onglets (basé sur la période filtrée)
        $pendingDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('pending', $filterStartDate, $filterEndDate);
        $assignedDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('assigned', $filterStartDate, $filterEndDate);
        $inTransitDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('in_transit', $filterStartDate, $filterEndDate);
        $deliveredDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('delivered', $filterStartDate, $filterEndDate);
        $returnedDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('returned', $filterStartDate, $filterEndDate);
        $cancelledDeliveriesCount = $this->deliveryModel->getDeliveryStatusCountByDate('cancelled', $filterStartDate, $filterEndDate);

        // Données pour les graphiques
        $dailyRevenueData = $this->deliveryModel->getDailyRevenueForPeriod($chartStartDate, $chartEndDate);
        $deliveryStatusCountsPeriod = $this->deliveryModel->getDeliveryStatusCountsForPeriod($chartStartDate, $chartEndDate);

        // Passer les données à la vue
        $this->render('admin/dashboard', [
            'username' => $username,
            
            // --- STATISTIQUES GLOBALES (TOTAUX ABSOLUS) ---
            // Ces méthodes doivent retourner le compte TOTAL de la table, SANS FILTRE D'UTILISATEUR.
            'total_deliveries' => $this->deliveryModel->countAllDeliveries(), 
            'total_users' => $this->userModel->countAllUsers(), 
            'total_suppliers' => $this->userModel->countUsersByRole('supplier'), 

            // Statistiques pour la période filtrée (pour les badges et aperçus rapides)
            'total_revenue_delivered_period' => $admin_stats_period['total_revenue_delivered'],
            'delivered_count_period' => $admin_stats_period['delivered_count'],
            'returned_count_period' => $admin_stats_period['returned_count'],
            'cancelled_count_period' => $admin_stats_period['cancelled_count'],
            'pending_deliveries_count' => $pendingDeliveriesCount,
            'assigned_deliveries_count' => $assignedDeliveriesCount,
            'inTransitDeliveriesCount' => $inTransitDeliveriesCount,
            'delivered_deliveries_count' => $deliveredDeliveriesCount,
            'returned_deliveries_count' => $returnedDeliveriesCount,
            'cancelled_deliveries_count' => $cancelledDeliveriesCount,

            // Données pour les graphiques
            'daily_revenue_data' => $dailyRevenueData,
            'delivery_status_counts_period' => $deliveryStatusCountsPeriod,

            // Passer les dates de filtre actuelles pour pré-remplir le formulaire
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => $filterEndDate
        ]);
    }

    /**
     * Affiche la liste de tous les utilisateurs (admin, fournisseurs, livreurs) avec des onglets.
     */
    public function users() {
        $this->checkAuth('admin'); // Admin et Superadmin
        
        $adminUsers = $this->userModel->readByRole('admin');
        $supplierUsers = $this->userModel->readByRole('supplier');
        $driverUsers = $this->userModel->readByRole('driver');

        $this->render('admin/users/users', [
            'adminUsers' => $adminUsers,
            'supplierUsers' => $supplierUsers,
            'driverUsers' => $driverUsers
        ]);
    }

    public function createUserForm() {
        $this->checkAuth('admin'); // Admin et Superadmin
        $this->render('admin/users/create');
    }

    public function createUser() {
        $this->checkAuth('admin'); // Admin et Superadmin

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role'])) {
                $_SESSION['message'] = "Veuillez remplir tous les champs obligatoires.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/admin/users/create');
                exit();
            }

            $this->userModel->username = $_POST['username'];
            $this->userModel->email = $_POST['email'];
            $this->userModel->password = $_POST['password']; // Mot de passe non haché, sera haché dans le modèle
            $this->userModel->role = $_POST['role'];

            $this->userModel->company_name = $_POST['company_name'] ?? null;
            $this->userModel->address = $_POST['address'] ?? null;
            $this->userModel->phone = $_POST['phone'] ?? null;
            $this->userModel->siret_number = $_POST['siret_number'] ?? null;
            $this->userModel->first_name = $_POST['first_name'] ?? null;
            $this->userModel->last_name = $_POST['last_name'] ?? null;
            $this->userModel->id_card_number = $_POST['id_card_number'] ?? null;
            $this->userModel->motorized = isset($_POST['motorized']) ? 1 : 0;
            $this->userModel->is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($this->userModel->create()) {
                $_SESSION['message'] = "Utilisateur créé avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: ' . self::BASE_URL . '/admin/users');
                exit();
            } else {
                $_SESSION['message'] = "Erreur lors de la création de l'utilisateur.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/admin/users/create');
                exit();
            }
        } else {
            header('Location: ' . self::BASE_URL . '/admin/users');
            exit();
        }
    }

    public function editUserForm($userId) {
        $this->checkAuth('admin'); // Admin et Superadmin

        $user = $this->userModel->readById($userId);

        if ($user) {
            $this->render('admin/users/edit', ['user' => $user]);
        } else {
            $_SESSION['message'] = "Utilisateur non trouvé.";
            $_SESSION['message_type'] = "danger";
            header('Location: ' . self::BASE_URL . '/admin/users');
            exit();
        }
    }

    public function updateUser($userId) {
        $this->checkAuth('admin'); // Admin et Superadmin

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->userModel->id = $userId;

            $existingUser = $this->userModel->readById($userId);
            if (!$existingUser) {
                $_SESSION['message'] = "Utilisateur à mettre à jour non trouvé.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/admin/users');
                exit();
            }

            $this->userModel->username = $_POST['username'];
            $this->userModel->email = $_POST['email'];
            $this->userModel->role = $_POST['role'];

            // Si un nouveau mot de passe est fourni, le hacher AVANT de l'affecter au modèle
            if (!empty($_POST['password'])) {
                $this->userModel->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            } else {
                // IMPORTANT: Mettre à null pour indiquer au modèle de ne pas le changer
                $this->userModel->password = null; 
            }

            $this->userModel->company_name = $_POST['company_name'] ?? null;
            $this->userModel->address = $_POST['address'] ?? null;
            $this->userModel->phone = $_POST['phone'] ?? null;
            $this->userModel->siret_number = $_POST['siret_number'] ?? null;
            $this->userModel->first_name = $_POST['first_name'] ?? null;
            $this->userModel->last_name = $_POST['last_name'] ?? null;
            $this->userModel->id_card_number = $_POST['id_card_number'] ?? null;
            $this->userModel->motorized = isset($_POST['motorized']) ? 1 : 0;
            $this->userModel->is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($this->userModel->update()) {
                $_SESSION['message'] = "Utilisateur mis à jour avec succès !";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Erreur lors de la mise à jour de l'utilisateur.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: ' . self::BASE_URL . '/admin/users');
            exit();
        } else {
            header('Location: ' . self::BASE_URL . '/admin/users');
            exit();
        }
    }

    public function deleteUser($userId) {
        $this->checkAuth('admin'); // Admin et Superadmin
        $this->userModel->id = $userId;
        if ($this->userModel->delete()) {
            $_SESSION['message'] = "Utilisateur supprimé avec succès !";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Erreur lors de la suppression de l'utilisateur.";
            $_SESSION['message_type'] = "danger";
        }
        header('Location: ' . self::BASE_URL . '/admin/users');
        exit();
    }

    /**
     * Affiche la liste de toutes les livraisons pour l'administrateur.
     */
    public function deliveries() {
        $this->checkAuth('admin'); // Admin et Superadmin

        // Récupérer les paramètres de filtre depuis GET
        $filterStartDate = $_GET['start_date'] ?? null; // Défaut à NULL
        $filterEndDate = $_GET['end_date'] ?? null;      // Défaut à NULL
        
        // Convertir les chaînes vides en null pour le modèle si aucun filtre n'est sélectionné
        $filterSupplierId = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
        $filterDriverId = !empty($_GET['driver_id']) ? (int)$_GET['driver_id'] : null;
        $filterStatus = !empty($_GET['status_filter']) ? $_GET['status_filter'] : null;
        $searchQuery = !empty($_GET['search_query']) ? $_GET['search_query'] : null;


        // Lire toutes les livraisons avec les filtres
        $deliveries = $this->deliveryModel->readAll(
            $filterSupplierId,
            $filterDriverId,
            $filterStartDate, // Passera NULL si non spécifié
            $filterEndDate,    // Passera NULL si non spécifié
            $filterStatus,
            $searchQuery
        );

        // NOUVEAU: Calculer les statistiques récapitulatives pour les cartes
        // Ces calculs doivent être basés sur les livraisons APRÈS FILTRAGE pour être pertinents
        $total_deliveries = 0;
        $delivered_count = 0;
        $returned_count = 0;
        $cancelled_count = 0;
        $pending_count = 0;
        $assigned_count = 0;
        $in_transit_count = 0;

        // Pour calculer les stats, nous devons parcourir le jeu de résultats.
        // Si $deliveries est un objet mysqli_result, il ne peut être parcouru qu'une fois.
        // Nous allons donc le "cloner" ou le "récupérer" une deuxième fois si nécessaire.
        $deliveries_for_stats = $this->deliveryModel->readAll(
            $filterSupplierId, $filterDriverId, $filterStartDate, $filterEndDate, $filterStatus, $searchQuery
        );

        if ($deliveries_for_stats && $deliveries_for_stats->num_rows > 0) {
            while ($delivery_stat = $deliveries_for_stats->fetch_assoc()) {
                $total_deliveries++;
                switch ($delivery_stat['status']) {
                    case 'delivered': $delivered_count++; break;
                    case 'returned': $returned_count++; break;
                    case 'cancelled': $cancelled_count++; break;
                    case 'pending': $pending_count++; break;
                    case 'assigned': $assigned_count++; break;
                    case 'in_transit': $in_transit_count++; break;
                }
            }
            // IMPORTANT: Réinitialiser le pointeur du jeu de résultats $deliveries pour la boucle while suivante (affichage du tableau)
            // C'est nécessaire car readAll renvoie un objet mysqli_result
            if ($deliveries instanceof mysqli_result) {
                $deliveries->data_seek(0);
            }
        }

        $summary_counts = [
            'total_deliveries' => $total_deliveries,
            'delivered_count' => $delivered_count,
            'returned_count' => $returned_count,
            'cancelled_count' => $cancelled_count,
            'pending_count' => $pending_count,
            'assigned_count' => $assigned_count,
            'in_transit_count' => $in_transit_count
        ];

        // Récupérer les listes de fournisseurs et livreurs pour les dropdowns de filtre
        $suppliers = $this->userModel->readByRole('supplier');
        $drivers = $this->userModel->readByRole('driver');


        // Passer les données à la vue
        $this->render('admin/all_deliveries', [
            'deliveries' => $deliveries,
            'summary_counts' => $summary_counts, // NOUVEAU: Passons les statistiques à la vue
            // Passer les valeurs NULL si les filtres ne sont pas définis par l'utilisateur
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => $filterEndDate,
            'filter_supplier_id' => $filterSupplierId,
            'filter_driver_id' => $filterDriverId,
            'filter_status' => $filterStatus,
            'search_query' => $searchQuery,
            'suppliers' => $suppliers, // Passons les fournisseurs à la vue
            'drivers' => $drivers      // Passons les livreurs à la vue
        ]);
    }

    public function editDeliveryForm($deliveryId) {
        $this->checkAuth('admin'); // Admin et Superadmin
        $delivery = $this->deliveryModel->readOneById($deliveryId); // Correction: Passer $deliveryId

        if ($delivery) {
            $drivers = $this->userModel->readByRole('driver');
            $this->render('admin/deliveries/edit', [
                'delivery' => $delivery, // Passons l'objet Delivery hydraté
                'drivers' => $drivers
            ]);
        } else {
            $_SESSION['message'] = "Livraison non trouvée.";
            $_SESSION['message_type'] = "danger";
            header('Location: ' . self::BASE_URL . '/admin/deliveries');
            exit();
        }
    }

    public function updateDelivery() {
        $this->checkAuth('admin'); // Admin et Superadmin
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->deliveryModel->id = $_POST['id'];
            $this->deliveryModel->description = $_POST['description'];
            $this->deliveryModel->recipient_name = $_POST['recipient_name'];
            $this->deliveryModel->recipient_phone = $_POST['recipient_phone'];
            $this->deliveryModel->recipient_city = $_POST['recipient_city'];
            $this->deliveryModel->destination_address = $_POST['destination_address'];
            $this->deliveryModel->weight_kg = $_POST['weight_kg'];
            $this->deliveryModel->length_cm = $_POST['length_cm'];
            $this->deliveryModel->L_width_cm = $_POST['L_width_cm'];
            $this->deliveryModel->height_cm = $_POST['height_cm'];
            $this->deliveryModel->declared_value = $_POST['declared_value'];
            $this->deliveryModel->status = $_POST['status'];
            $this->deliveryModel->driver_id = $_POST['driver_id'] !== '' ? $_POST['driver_id'] : null;
            $this->deliveryModel->price = $_POST['price'];
            $this->deliveryModel->return_reason = $_POST['return_reason'] ?? null; // Assurez-vous de récupérer le motif de retour

            // NOUVEAU: Récupérer is_fragile et accepts_exchange pour la mise à jour
            $this->deliveryModel->is_fragile = isset($_POST['is_fragile']) ? 1 : 0;
            $this->deliveryModel->accepts_exchange = isset($_POST['accepts_exchange']) ? 1 : 0;


            if ($this->deliveryModel->update()) {
                $_SESSION['message'] = "Livraison mise à jour avec succès !";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Erreur lors de la mise à jour de la livraison.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: ' . self::BASE_URL . '/admin/deliveries');
            exit();
        }
    }

    public function deleteDelivery($deliveryId) {
        $this->checkAuth('admin'); // Admin et Superadmin
        // CORRECTION ICI: Utiliser deliveryModel pour supprimer la livraison
        $this->deliveryModel->id = $deliveryId; // Assigner l'ID à la propriété id du modèle de livraison
        if ($this->deliveryModel->delete()) { // Appeler la méthode delete() sur le modèle de livraison
            $_SESSION['message'] = "Livraison supprimée avec succès !";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Erreur lors de la suppression de la livraison.";
            $_SESSION['message_type'] = "danger";
        }
        header('Location: ' . self::BASE_URL . '/admin/deliveries');
        exit();
    }

    /**
     * Route pour récupérer les colis assignés à un livreur spécifique via AJAX.
     */
    public function getAssignedDeliveriesByDriverId($driverId) {
        $this->checkAuth('admin'); // Admin et Superadmin
        header('Content-Type: application/json');

        // Récupérer les colis assignés à ce livreur avec le statut 'assigned'
        $deliveries = $this->deliveryModel->readAll(
            null, // supplierId
            $driverId,
            null, // startDate (pas de filtre de date ici)
            null, // endDate (pas de filtre de date ici)
            'assigned', // Filtrer UNIQUEMENT par le statut 'assigned'
            null  // searchQuery
        );

        $results = [];
        if ($deliveries) { // Vérifier si $deliveries n'est pas null
            while ($row = $deliveries->fetch_assoc()) {
                $results[] = $row;
            }
        }
        echo json_encode(['success' => true, 'deliveries' => $results]);
        exit();
    }

    /**
     * Désassigne un colis d'un livreur et remet son statut à 'pending' (en dépôt).
     */
    public function unassignDelivery($deliveryId) {
        $this->checkAuth('admin'); // Admin et Superadmin
        header('Content-Type: application/json');

        error_log("AdminController::unassignDelivery - Request received for deliveryId: " . $deliveryId);

        if (empty($deliveryId)) {
            error_log("AdminController::unassignDelivery - Invalid deliveryId: " . $deliveryId);
            echo json_encode(['success' => false, 'message' => 'ID de livraison invalide.']);
            exit();
        }

        // Charger la livraison par son ID pour la modifier
        $delivery = new Delivery();
        $delivery->id = $deliveryId; 
        
        if ($delivery->readOneById($deliveryId)) { // Utilisez readOneById pour hydrater $delivery
            error_log("AdminController::unassignDelivery - Found delivery " . $deliveryId . ", current status: " . $delivery->status . ", current driver: " . $delivery->driver_id);
            
            // Modifier le statut et le driver_id de l'objet $delivery
            $delivery->status = 'pending'; // Remettre en dépôt
            $delivery->driver_id = null;    // Désassigner le livreur (le remettre à NULL)
            $delivery->return_reason = null; // Supprimer le motif de retour s'il y en avait un

            // Maintenant, utilisez la méthode update() qui gère correctement le driver_id=NULL
            if ($delivery->update()) { 
                error_log("AdminController::unassignDelivery - Successfully unassigned delivery " . $deliveryId . ". New status: " . var_export($delivery->status, true) . ", new driver_id: " . var_export($delivery->driver_id, true));
                echo json_encode(['success' => true, 'message' => 'Colis désassigné et remis en dépôt.']);
            } else {
                error_log("AdminController::unassignDelivery - Failed to update delivery " . $deliveryId . " in DB. Error: " . $this->deliveryModel->conn->error);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la désassignation du colis.']);
            }
        } else {
            error_log("AdminController::unassignDelivery - Delivery " . $deliveryId . " not found.");
            echo json_encode(['success' => false, 'message' => 'Colis non trouvé.']);
        }
        exit();
    }

    /**
     * Gère l'impression d'un bordereau de livraison pour les colis assignés à un livreur.
     */
    public function printDeliverySummary() {
        $this->checkAuth('admin'); // Admin et Superadmin
        
        $driverId = $_GET['driver_id'] ?? null;
        $deliveryIds = $_GET['ids'] ?? []; // Récupère les IDs sous forme de tableau

        if (!$driverId || empty($deliveryIds)) {
            $_SESSION['message'] = "Paramètres d'impression manquants ou invalides.";
            $_SESSION['message_type'] = "danger";
            header('Location: ' . self::BASE_URL . '/admin/assignDeliveriesByBarcode');
            exit();
        }

        // Récupérer les informations du livreur
        $driver = $this->userModel->readById($driverId);
        if (!$driver) {
            $_SESSION['message'] = "Livreur non trouvé.";
            $_SESSION['message_type'] = "danger";
            header('Location: ' . self::BASE_URL . '/admin/assignDeliveriesByBarcode');
            exit();
        }

        // Récupérer les détails complets de chaque livraison à imprimer
        $deliveries_to_print = [];
        foreach ($deliveryIds as $id) {
            $delivery = new Delivery(); // Créer une nouvelle instance pour chaque colis
            if ($delivery->readOneById($id)) {
                $deliveries_to_print[] = $delivery->toArray(); // Convertir en tableau pour la vue
            }
        }

        // Charger la vue du bordereau sans le layout principal (pour l'impression)
        require_once __DIR__ . '/../views/admin/deliveries/print_summary.php';
        exit();
    }

    // --- FACTURATION (RESTREINT AU SUPERADMIN) ---

    /**
     * NOUVELLE METHODE: Affiche la liste de toutes les factures.
     */
    public function invoices() {
        // 🚨 Restriction Superadmin STRICTE
        $this->checkSuperAdminOnlyAccess();

        // Récupérer les paramètres de filtre depuis GET
        $filterSupplierId = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
        $filterStartDate = $_GET['start_date'] ?? null;
        $filterEndDate = $_GET['end_date'] ?? null;
        $filterStatus = $_GET['status'] ?? null;

        // Lire toutes les factures avec les filtres
        $invoices = $this->invoiceModel->readAll(
            $filterSupplierId,
            $filterStartDate,
            $filterEndDate,
            $filterStatus
        );

        // Récupérer la liste des fournisseurs pour le dropdown de filtre
        $suppliers = $this->userModel->readByRole('supplier');

        // Passer les données à la vue
        $this->render('admin/invoices/list', [
            'invoices' => $invoices,
            'suppliers' => $suppliers,
            'filter_supplier_id' => $filterSupplierId,
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => $filterEndDate,
            'filter_status' => $filterStatus
        ]);
    }
	
	
	/**
     * NOUVELLE METHODE: Met à jour le statut d'une facture via AJAX.
     * Utilisée par la liste des factures.
     */
    public function updateInvoiceStatus() {
        $this->checkAuth('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id']) && isset($_POST['status'])) {
            $invoiceId = $_POST['invoice_id'];
            $newStatus = $_POST['status'];

            if (empty($invoiceId) || empty($newStatus)) {
                echo json_encode(['success' => false, 'message' => 'ID de facture ou statut invalide.']);
                exit();
            }

            // Valider le nouveau statut pour éviter les injections SQL ou statuts invalides
            $allowedStatuses = ['pending', 'paid', 'cancelled'];
            if (!in_array($newStatus, $allowedStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Statut non autorisé.']);
                exit();
            }

            if ($this->invoiceModel->updateStatus($invoiceId, $newStatus)) {
                echo json_encode(['success' => true, 'message' => 'Statut de la facture mis à jour avec succès !']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut de la facture.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
        }
        exit();
    }

    /**
     * NOUVELLE METHODE: Affiche les détails d'une facture spécifique et les livraisons qui y sont liées.
     * @param int $invoiceId L'ID de la facture à visualiser.
     */
    public function viewInvoice($invoiceId) {
        $this->checkAuth('admin');

        $invoice = $this->invoiceModel->readOneById($invoiceId);

        if (!$invoice) {
            $_SESSION['message'] = "Facture non trouvée.";
            $_SESSION['message_type'] = "danger";
            header('Location: /sabroom/public/admin/invoices');
            exit();
        }

        // Récupérer toutes les livraisons liées à cette facture
        // Appel à readAll: readAll($supplierId, $driverId, $dateFilterParam, $endDate, $statusFilter, $searchQuery, $invoiceId)
        $deliveries_on_invoice = $this->deliveryModel->readAll(
            null, // Pas de filtre par fournisseur
            null, // Pas de filtre par livreur
            null, // Pas de filtre de date (on veut tous les colis de cette facture)
            null, // Pas de filtre de date
            null, // Pas de filtre de statut (on veut tous les colis de cette facture)
            null, // Pas de recherche
            $invoiceId // Filtrer par l'ID de la facture
        );

        $this->render('admin/invoices/view', [
            'invoice' => $invoice,
            'deliveries_on_invoice' => $deliveries_on_invoice
        ]);
    }
	

    /**
     * NOUVELLE METHODE: Affiche le formulaire de génération de facture.
     */
    public function generateInvoiceForm() {
        // 🚨 Restriction Superadmin STRICTE
        $this->checkSuperAdminOnlyAccess();
        
        // Récupérer la liste des fournisseurs pour le dropdown
        $suppliers = $this->userModel->readByRole('supplier');
        $this->render('admin/invoices/generate_form', ['suppliers' => $suppliers]);
    }

    /**
     * NOUVELLE METHODE: Traite la génération d'une facture.
     */
    public function generateInvoice() {
        // 🚨 Restriction Superadmin STRICTE
        $this->checkSuperAdminOnlyAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supplierId = $_POST['supplier_id'] ?? null;
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d'); // Date d'aujourd'hui par default
            $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')); // 30 jours après par défaut

            if (empty($supplierId) || empty($startDate) || empty($endDate)) {
                $_SESSION['message'] = "Veuillez sélectionner un fournisseur et une période.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/admin/invoices/generate-form');
                exit();
            }

            // 1. Récupérer toutes les livraisons "delivered" pour ce fournisseur, dans cette période, et non encore facturées
            // Utilisation de readAll: readAll($supplierId, $driverId, $dateFilterParam, $endDate, $statusFilter, $searchQuery, $invoiceId)
            $deliveriesToInvoice = $this->deliveryModel->readAll(
                $supplierId,
                null, // driverId
                $startDate, // dateFilterParam (startDate)
                $endDate,    // endDate
                'delivered', // statusFilter (seulement les livrés)
                null, // searchQuery
                null // invoiceId (IMPORTANT: on cherche les colis NON facturés ici)
            );

            $totalAmount = 0;
            $deliveryIds = []; // Pour stocker les IDs des colis à lier à la facture

            if ($deliveriesToInvoice && $deliveriesToInvoice->num_rows > 0) {
                while ($delivery = $deliveriesToInvoice->fetch_assoc()) {
                    // Cette vérification est déjà faite dans le readAll si on passe null pour invoiceId
                    if ($delivery['invoice_id'] === null) { 
                        $totalAmount += $delivery['price'];
                        $deliveryIds[] = $delivery['id'];
                    }
                }
            }

            if (empty($deliveryIds)) {
                $_SESSION['message'] = "Aucune livraison livrée non facturée trouvée pour ce fournisseur sur cette période.";
                $_SESSION['message_type'] = "warning";
                header('Location: ' . self::BASE_URL . '/admin/invoices/generate-form');
                exit();
            }

            // 2. Créer la facture dans la table 'invoices'
            $this->invoiceModel->supplier_id = $supplierId;
            $this->invoiceModel->invoice_date = $invoiceDate;
            $this->invoiceModel->due_date = $dueDate;
            $this->invoiceModel->total_amount = $totalAmount;
            $this->invoiceModel->status = 'pending'; // Statut initial

            $invoiceId = $this->invoiceModel->create();

            if ($invoiceId) {
                // 3. Lier les livraisons à la facture nouvellement créée
                // Utilisez la nouvelle méthode linkDeliveriesToInvoice
                if ($this->deliveryModel->linkDeliveriesToInvoice($deliveryIds, $invoiceId)) {
                    $_SESSION['message'] = "Facture #{$invoiceId} générée avec succès pour un montant de {$totalAmount} DT.";
                    $_SESSION['message_type'] = "success";
                    header('Location: ' . self::BASE_URL . '/admin/invoices'); // Rediriger vers la liste des factures
                    exit();
                } else {
                    $_SESSION['message'] = "Facture créée, mais erreur lors de la liaison des colis.";
                    $_SESSION['message_type'] = "warning";
                    // Optionnel: annuler la facture si la liaison échoue ? Pour l'instant, on laisse et on loggue.
                    error_log("AdminController::generateInvoice - Erreur de liaison colis pour facture #{$invoiceId}. Colis IDs: " . implode(',', $deliveryIds));
                    header('Location: ' . self::BASE_URL . '/admin/invoices'); // Rediriger quand même
                    exit();
                }
            } else {
                $_SESSION['message'] = "Erreur lors de la création de la facture dans la base de données.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/admin/invoices/generate-form');
                exit();
            }

        } else {
            header('Location: ' . self::BASE_URL . '/admin/invoices/generate-form');
            exit();
        }
    }
    
	
    // Suppression du placeholder de fonction incomplète
    /*
    public function incompleteFunctionPlaceholder() {
        header('Location: ' . self::BASE_URL . '/admin/invoices/generate-form');
        exit();
    }
    */
	    public function assignDeliveriesByBarcodeForm() {
        $this->checkAuth('admin');
        // Assurez-vous que first_name et last_name sont récupérés par readByRole dans User model
        $drivers = $this->userModel->readByRole('driver');
        $this->render('admin/assign_deliveries', ['drivers' => $drivers]);
    }

    public function assignDeliveryByBarcode() {
        $this->checkAuth('admin');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode']) && isset($_POST['driver_id'])) {
            // La valeur du code-barres DOIT être utilisée telle quelle pour la recherche
            $barcode = $_POST['barcode']; 
            $driverId = (int)$_POST['driver_id'];

            // Débogage: Log les données reçues AVANT le nettoyage pour la recherche
            error_log("AdminController::assignDeliveryByBarcode - Received raw barcode: '" . $barcode . "', driverId: " . $driverId);

            if (empty($barcode) || $driverId <= 0) {
                error_log("AdminController::assignDeliveryByBarcode - Invalid input - barcode: '" . $barcode . "', driverId: " . $driverId);
                echo json_encode(['success' => false, 'message' => 'Code-barres ou livreur invalide.']);
                exit();
            }

            // Charger le colis par son code-barres pour obtenir ses détails et son ID
            // Le nettoyage strip_tags/htmlspecialchars doit se faire au niveau du modèle
            // Juste avant la liaison dans la requête SQL pour la création/mise à jour, ou pour l'affichage HTML.
            $this->deliveryModel->unique_id = $barcode;

            // Utiliser readOneByUniqueId pour charger toutes les propriétés dans cet objet $delivery local
            if ($this->deliveryModel->readOneByUniqueId()) { // readOneByUniqueId nettoie déjà unique_id
                // Débogage: Log colis trouvé
                error_log("AdminController::assignDeliveryByBarcode - Found delivery - ID: " . $this->deliveryModel->id . ", current status: " . $this->deliveryModel->status . ", Unique ID in DB: '" . $this->deliveryModel->unique_id . "'");

                // S'assurer que le colis a le bon statut pour être assigné
                if ($this->deliveryModel->status === 'pending' || $this->deliveryModel->status === 'returned' || $this->deliveryModel->status === 'cancelled') {
                    // Mettre à jour l'objet delivery avec le nouveau driver_id et status
                    $this->deliveryModel->driver_id = $driverId;
                    $this->deliveryModel->status = 'assigned'; // Statut mis à jour
                    $this->deliveryModel->return_reason = null; // Réinitialiser le motif de retour à l'assignation

                    // Appeler la méthode update sur cet objet $delivery local
                    if ($this->deliveryModel->update()) { // Utiliser update() qui prend les propriétés de l'objet
                        // Récupérer les informations complètes du colis après la mise à jour pour la réponse
                        // Utiliser readOneById() pour obtenir toutes les propriétés jointes
                        $updatedDelivery = new Delivery();
                        if ($updatedDelivery->readOneById($this->deliveryModel->id)) { // Passer l'ID du colis pour le recharger
                            $driver = $this->userModel->readById($driverId);
                            $driverFullName = $driver ? trim($driver->first_name . ' ' . $driver->last_name) : 'Inconnu';
                            
                            error_log("AdminController::assignDeliveryByBarcode - Assignment successful. Sending updated delivery: " . print_r($updatedDelivery->toArray(), true));
                            echo json_encode([
                                'success' => true,
                                'message' => "Colis '" . htmlspecialchars($barcode) . "' assigné à {$driverFullName} avec succès.",
                                'delivery' => $updatedDelivery->toArray() // Envoyer l'objet complet converti en tableau
                            ]);
                        } else {
                            error_log("AdminController::assignDeliveryByBarcode - Failed to read updated delivery with ID " . $this->deliveryModel->id);
                            echo json_encode(['success' => false, 'message' => "Colis assigné, mais échec de la récupération des détails pour l'affichage."]);
                        }
                    } else {
                        error_log("AdminController::assignDeliveryByBarcode - DB Update failed for barcode '" . $barcode . "'. Error: " . $this->deliveryModel->conn->error);
                        echo json_encode(['success' => false, 'message' => "Erreur lors de l'assignation du colis '" . htmlspecialchars($barcode) . "'. Renseignez l'administrateur."]);
                    }
                } else {
                    error_log("AdminController::assignDeliveryByBarcode - Colis '" . $barcode . "' not in assignable status. Current status: " . $this->deliveryModel->status);
                    echo json_encode(['success' => false, 'message' => "Le colis '" . htmlspecialchars($barcode) . "' a déjà le statut '{$this->deliveryModel->status}' et ne peut pas être assigné depuis cette page."]);
                }
            } else {
                error_log("AdminController::assignDeliveryByBarcode - Colis '" . $barcode . "' not found in DB. after readOneByUniqueId call");
                echo json_encode(['success' => false, 'message' => "Colis avec le code-barres '" . htmlspecialchars($barcode) . "' non trouvé."]);
            }
        } else {
            error_log("AdminController::assignDeliveryByBarcode: Invalid request method or missing parameters.");
            echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
        }
        exit();
    }
}
