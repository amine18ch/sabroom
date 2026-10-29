<?php
// Fichier: app/controllers/AuthController.php - CONTRÔLEUR D'AUTHENTIFICATION

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/Controller.php'; // Inclusion du contrôleur de base

class AuthController extends Controller { // Hérite de Controller
    private $userModel;
    
    // Définition de la base URL pour une meilleure réutilisabilité
    const BASE_URL = '/sabroom/public'; 

    public function __construct() {
        // Le modèle User est nécessaire pour l'authentification
        $this->userModel = new User();
    }

    /**
     * Affiche le formulaire de connexion.
     */
    public function showLoginForm() {
        // Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
        if (isset($_SESSION['user_id'])) {
            // Utilise la nouvelle logique de redirection basée sur le rôle stocké en session
            $this->redirectBasedOnRole($_SESSION['user_role'] ?? 'default');
            exit();
        }
        // Charger la vue de connexion
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Gère la soumission du formulaire de connexion.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? ''); // Authentification par username
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $_SESSION['message'] = "Veuillez entrer le nom d'utilisateur et le mot de passe.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/login');
                exit();
            }

            // Utilise la méthode authenticate du modèle User
            $user = $this->userModel->authenticate($username, $password);

            if ($user) {
                // ✅ Authentification réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['message'] = "Connexion réussie ! Bienvenue, " . htmlspecialchars($user['username']) . ".";
                $_SESSION['message_type'] = "success";
                
                // 🔹 REDIRECTION CORRIGÉE : Redirige spécifiquement selon le rôle
                $this->redirectBasedOnRole($user['role']);
                
                exit();
            } else {
                // ❌ Authentification échouée
                $_SESSION['message'] = "Nom d'utilisateur ou mot de passe incorrect.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . self::BASE_URL . '/login');
                exit();
            }
        } else {
            // Si la requête n'est pas POST, rediriger vers le formulaire de connexion
            header('Location: ' . self::BASE_URL . '/login');
            exit();
        }
    }

    /**
     * Logique de redirection centralisée basée sur le rôle.
     * @param string $role Le rôle de l'utilisateur.
     */
    private function redirectBasedOnRole($role) {
        switch ($role) {
            case 'superadmin':
            case 'admin':
                // Les rôles admin et superadmin vont au tableau de bord d'administration
                header('Location: ' . self::BASE_URL . '/admin/dashboard'); 
                break;
            case 'supplier':
                header('Location: ' . self::BASE_URL . '/supplier/dashboard');
                break;
            case 'driver':
                header('Location: ' . self::BASE_URL . '/driver/dashboard');
                break;
            default:
                // Redirection par défaut
                header('Location: ' . self::BASE_URL . '/dashboard');
                break;
        }
        exit();
    }


    /**
     * Gère la déconnexion de l'utilisateur.
     */
    public function logout() {
        // Détruire toutes les variables de session
        $_SESSION = array();

        // Si vous voulez détruire complètement la session, effacez également le cookie de session.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finalement, détruire la session.
        session_destroy();

        // Après la destruction, il faut ré-ouvrir une session pour stocker le message
        session_start();
        $_SESSION['message'] = "Vous avez été déconnecté avec succès.";
        $_SESSION['message_type'] = "info";
        header('Location: ' . self::BASE_URL . '/login');
        exit();
    }
}
