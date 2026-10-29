<?php
// Fichier: public/index.php - POINT D'ENTRÉE PRINCIPAL

// --- ACTIVER L'AFFICHAGE DES ERREURS POUR LE DÉBOGAGE (temporaire si besoin) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN DE L'ACTIVATION DES ERREURS ---

// Démarrer la session PHP au tout début du script
session_start();

// --- Inclusions des fichiers essentiels ---
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Delivery.php';
require_once __DIR__ . '/../app/models/Invoice.php'; // Inclure le modèle Invoice
require_once __DIR__ . '/../app/controllers/Controller.php';

// Inclure les contrôleurs spécifiques (sans instanciation ici)
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/SupplierController.php';
require_once __DIR__ . '/../app/controllers/DriverController.php';
require_once __DIR__ . '/../app/controllers/DeliveryController.php';


// --- Logique de routage ---

// Récupère l'URL demandée par le client
$request_uri = $_SERVER['REQUEST_URI'];

// Définissez le chemin de base de votre application.
// C'est crucial pour que le routage fonctionne correctement sur votre serveur XAMPP.
// Exemple: Si votre projet est accessible via http://localhost/sabroom/public/
// alors $base_path doit être '/sabroom/public'.
// Si votre projet est à la racine de votre hôte virtuel (ex: http://localhost/),
// alors $base_path doit être ''.
$base_path = '/sabroom/public'; // <<< TRÈS IMPORTANT : À AJUSTER SI VOTRE CHEMIN D'ACCÈS EST DIFFÉRENT !

// --- DÉBUT DE LA CORRECTION DU ROUTAGE POUR GÉRER LES RÉÉCRITURES D'APACHE ---
$route = '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Assurez-vous que le chemin commence bien par $base_path
if (strpos($path, $base_path) === 0) {
    $route = substr($path, strlen($base_path));
} else {
    // Fallback si $base_path n'est pas dans le chemin (peut arriver si projet à la racine)
    $route = $path;
}

// Nettoie la route (supprime les barres obliques de début et de fin)
$route = trim($route, '/');
// --- FIN DE LA CORRECTION DU ROUTAGE ---

// Ignorer les requêtes pour les assets statiques AVANT toute autre logique de routage PHP
if (
    strpos($route, 'assets/') === 0 ||
    strpos($route, 'css/') === 0 ||
    strpos($route, 'js/') === 0 ||
    strpos($route, 'img/') === 0 ||
    $route === 'favicon.ico' ||
    preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|map)$/i', $route)
) {
    error_log("Index.php: Ignoré la route d'asset statique: " . $route);
    return; // Arrête l'exécution de index.php, Apache servira le fichier
}

// Débogage : Log la route qui est traitée
error_log("Index.php: Route traitée: " . $route . " (Méthode: " . $_SERVER['REQUEST_METHOD'] . ")");


// Le contrôleur d'authentification est toujours instancié en premier
$authController = new AuthController();


// --- GESTION DES ROUTES DYNAMIQUES AVANT LE SWITCH PRINCIPAL ---
// Ces routes utilisent preg_match pour capturer des IDs et doivent être traitées en premier.

// Instancier les contrôleurs utilisés par ces routes dynamiques
$adminController = new AdminController();
$supplierController = new SupplierController(); 
$driverController = new DriverController(); 


// Routes pour la gestion des UTILISATEURS (Admin) - Traitement par ID
if (preg_match('/^admin\/users\/edit\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: admin/users/edit/ (GET)");
    $userId = $matches[1];
    $adminController->editUserForm($userId);
    exit();
}
if (preg_match('/^admin\/users\/update\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Index.php: Route match: admin/users/update/ (POST)");
    $userId = $matches[1];
    $adminController->updateUser($userId); 
    exit();
}
if (preg_match('/^admin\/users\/delete\/(\d+)$/', $route, $matches)) {
    error_log("Index.php: Route match: admin/users/delete/");
    $userId = $matches[1];
    $adminController->deleteUser($userId);
    exit();
}

// Routes pour la gestion des LIVRAISONS (Admin) - Traitement par ID
// MODIFIER LIVRAISON (GET pour afficher le formulaire)
if (preg_match('/^admin\/deliveries\/edit\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: admin/deliveries/edit/ (GET)");
    $deliveryId = $matches[1];
    $adminController->editDeliveryForm($deliveryId);
    exit();
}
// SUPPRIMER LIVRAISON (GET - action directe via lien)
if (preg_match('/^admin\/deliveries\/delete\/(\d+)$/', $route, $matches)) {
    error_log("Index.php: Route match: admin/deliveries/delete/");
    $deliveryId = $matches[1];
    $adminController->deleteDelivery($deliveryId);
    exit();
}
// Désassigner un colis (AJAX POST)
if (preg_match('/^admin\/deliveries\/unassign\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Index.php: Route match: admin/deliveries/unassign/ (POST)");
    $deliveryId = $matches[1];
    $adminController->unassignDelivery($deliveryId);
    exit();
}
// Obtenir les colis assignés à un livreur (AJAX GET)
if (preg_match('/^admin\/deliveries\/get-by-driver-assigned\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: admin/deliveries/get-by-driver-assigned/ (GET)");
    $driverId = $matches[1];
    $adminController->getAssignedDeliveriesByDriverId($driverId);
    exit();
}
// Impression du bordereau de livraison (GET)
if ($route === 'admin/deliveries/print-summary' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: admin/deliveries/print-summary (GET)");
    $adminController->printDeliverySummary();
    exit();
}

// Routes de FACTURATION (Admin) - Traitement par ID (Voir une facture spécifique)
if (preg_match('/^admin\/invoices\/view\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: admin/invoices/view/ (GET)");
    $invoiceId = $matches[1];
    $adminController->viewInvoice($invoiceId); 
    exit();
}

// Routes de FACTURATION (Fournisseur) - Traitement par ID (Voir une facture spécifique)
if (preg_match('/^supplier\/invoices\/view\/(\d+)$/', $route, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Index.php: Route match: supplier/invoices/view/ (GET)");
    $invoiceId = $matches[1];
    $supplierController->viewInvoice($invoiceId); // Appel à la méthode viewInvoice() du SupplierController
    exit();
}


// --- FIN DE LA GESTION DES ROUTES DYNAMIQUES ---


// Utilise une instruction switch pour diriger les requêtes vers les bons contrôleurs et méthodes
switch ($route) {
    // Route par défaut (racine du projet ou '/dashboard')
    case '':
    case 'dashboard':
        if (!isset($_SESSION['user_id'])) {
            $authController->showLoginForm();
        } elseif ($_SESSION['user_role'] === 'admin') {
            $adminController->dashboard();
        } elseif ($_SESSION['user_role'] === 'supplier') {
            $supplierController->dashboard(); 
        } elseif ($_SESSION['user_role'] === 'driver') {
            $driverController->dashboard(); 
        } else {
            $authController->logout();
        }
        break;

    // Cas spécifiques pour les tableaux de bord avec filtres (si l'URL est explicite)
    case 'admin/dashboard':
        $adminController->dashboard();
        break;
    case 'supplier/dashboard':
        $supplierController->dashboard();
        break;
    case 'driver/dashboard':
        $driverController->dashboard();
        break;

    // Routes d'authentification
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->showLoginForm();
        }
        break;
    case 'logout':
        $authController->logout();
        break;

    // Routes Administrateur (Statiques)
    case 'admin/users':
        $adminController->users();
        break;
    case 'admin/users/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->createUser();
        } else {
            $adminController->createUserForm();
        }
        break;
    
    // Routes de LIVRAISON pour ADMIN (Statiques ou POST sans ID dans l'URL)
    case 'admin/assignDeliveriesByBarcode':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->assignDeliveriesByBarcodeForm();
        } else {
            header('Location: /sabroom/public/admin/assignDeliveriesByBarcode');
            exit();
        }
        break;
    case 'admin/assignDeliveryByBarcode': // Cette route est pour l'AJAX POST de l'assignation
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->assignDeliveryByBarcode();
        } else {
            header('Location: /sabroom/public/admin/assignDeliveriesByBarcode');
            exit();
        }
        break;
    case 'admin/deliveries': // La page "Toutes les livraisons"
        $adminController->deliveries(); // Cette méthode gère déjà les filtres via $_GET
        break;
    case 'admin/deliveries/update': // Cette route est pour la soumission du formulaire de modification
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->updateDelivery();
        } else {
            header('Location: /sabroom/public/admin/deliveries'); // Redirige si GET
            exit();
        }
        break;
    case 'admin/deliveries/print-summary': // La route d'impression du bordereau
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->printDeliverySummary();
        } else {
            header('Location: /sabroom/public/admin/assignDeliveriesByBarcode'); // Redirige si POST
            exit();
        }
        break;
    case 'admin/returns': // Route pour la gestion des retours
        $adminController->returnsManagement();
        break;
    case 'admin/returns/update-status': // NOUVEAU: Route pour la mise à jour du statut des retours par l'admin
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->updateReturnedDeliveryStatus();
        } else {
            header('Location: /sabroom/public/admin/returns');
            exit();
        }
        break;


    // Routes de FACTURATION pour ADMIN (Statiques ou POST)
    case 'admin/invoices':
        $adminController->invoices(); // Affiche la liste des factures
        break;
    case 'admin/invoices/generate-form':
        $adminController->generateInvoiceForm(); // Affiche le formulaire pour générer une facture
        break;
    case 'admin/invoices/generate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->generateInvoice(); // Traite la génération de facture
        } else {
            header('Location: /sabroom/public/admin/invoices/generate-form');
            exit();
        }
        break;
    case 'admin/invoices/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->updateInvoiceStatus(); // Traite la mise à jour du statut de facture
        } else {
            header('Location: /sabroom/public/admin/invoices');
            exit();
        }
        break;


    // Routes Fournisseur (EXISTANTES)
    case 'supplier/deliveries':
        $supplierController->listMyDeliveries();
        break;
    case 'supplier/deliveries/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supplierController->createDelivery();
        } else {
            $supplierController->showCreateDeliveryForm();
        }
        break;
    case 'supplier/deliveries/print-bordereau':
        if (isset($_GET['id'])) {
            $supplierController->printBordereau($_GET['id']);
        } else {
            header('Location: /sabroom/public/supplier/deliveries');
            exit();
        }
        break;
    case 'supplier/invoices': // Route pour la liste des factures du fournisseur
        $supplierController->myInvoices();
        break;


    // Routes Livreur
    case 'driver/deliveries':
        $driverController->listMyDeliveries();
        break;
    case 'driver/deliveries/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $driverController->updateDeliveryStatus();
        } else {
            header('Location: /sabroom/public/driver/deliveries');
            exit();
        }
        break;

    // Route par défaut (404)
    default:
        error_log("Index.php: Route non trouvée: " . $route);
        header("HTTP/1.0 404 Not Found");
        $controller = new Controller();
        $controller->render('404');
        break;
}
