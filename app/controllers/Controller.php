<?php
// Fichier: app/controllers/Controller.php - CONTRÔLEUR DE BASE

// Ce fichier définit la classe Controller de base que tous les autres contrôleurs étendront.
// Il fournit des méthodes utilitaires comme la gestion des vues et la vérification de l'authentification.

class Controller {

    /**
     * Charge une vue PHP et lui passe des données.
     * Le contenu de la vue est mis en mémoire tampon et injecté dans le layout principal.
     *
     * @param string $viewName Le chemin de la vue à partir de 'app/views/' (ex: 'auth/login').
     * @param array $data Un tableau associatif de données à passer à la vue.
     */
    public function render($viewName, $data = []) { // <-- DOIT ÊTRE PUBLIC
        // Extrait les données pour les rendre accessibles directement dans la vue
        extract($data);

        // Capture le contenu de la vue dans un tampon de sortie
        ob_start();
        // Ligne 22 (ou proche) : Le problème se produit ici si $viewName est vide
        require_once __DIR__ . '/../views/' . $viewName . '.php';
        $content = ob_get_clean();

        // Inclut le layout principal et y injecte le contenu de la vue
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * Vérifie si l'utilisateur est authentifié et a le rôle requis.
     * Redirige si l'authentification échoue ou si le rôle n'est pas suffisant.
     * Cette méthode est appelée par les contrôleurs qui étendent Controller.
     *
     * @param string|null $requiredRole Le rôle requis pour accéder à la page (ex: 'admin', 'supplier', 'driver').
     * Si null, seule l'authentification est vérifiée.
     */
    protected function checkAuth($requiredRole = null) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            header('Location: /sabroom/public/login');
            exit();
        }

        if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
            // Redirige vers le tableau de bord de l'utilisateur si le rôle ne correspond pas
            header('Location: /sabroom/public/dashboard'); // Assurez-vous que cette route est gérée
            exit();
        }
    }
}
