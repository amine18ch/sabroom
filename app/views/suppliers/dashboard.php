<?php
// Fichier: app/views/suppliers/dashboard.php - TABLEAU DE BORD FOURNISSEUR

// Le tableau de bord du fournisseur affiche un aperçu de ses activités de livraison.
// Design moderne et professionnel avec des icônes, des ombres et une palette de couleurs épurée.
// Il reçoit $username, $stats, $filter_start_date et $filter_end_date du SupplierController.

// Assurez-vous que les variables passées depuis le contrôleur sont définies
$username = $username ?? 'Fournisseur';
$stats = $stats ?? []; // S'assurer que $stats est un tableau même s'il est null
$filter_start_date = $filter_start_date ?? date('Y-m-d');
$filter_end_date = $filter_end_date ?? date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-dark mb-0">Tableau de Bord Fournisseur</h1>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fs-6">Bienvenue, <?php echo htmlspecialchars($username); ?> !</span>
</div>

<?php
// Affichage des messages flash (succès ou erreur)
if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show rounded-lg shadow-sm" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
endif;
?>

<div class="card shadow-sm mb-4 border-0 rounded-lg">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres du Tableau de Bord <small class="text-muted">(Période : <?php echo htmlspecialchars($filter_start_date); ?> au <?php echo htmlspecialchars($filter_end_date); ?>)</small></h5>
    </div>
    <div class="card-body">
        <form action="/sabroom/public/supplier/dashboard" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Date de début:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">Date de fin:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm hover-shadow-lg">Appliquer les filtres</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Carte : Colis en attente -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Colis en Dépôt</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-box fs-3 me-3 text-warning"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['pending_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Colis en attente d'assignation sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis Assignés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Colis En Cours</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-truck-loading fs-3 me-3 text-info"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['assigned_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Colis assignés à un livreur sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis Livrés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Colis Livrés</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fs-3 me-3 text-success"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['delivered_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Colis livrés avec succès sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis Retournés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Colis Retour</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-undo-alt fs-3 me-3 text-danger"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['returned_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Colis retournés sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis Annulés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Colis Annulés</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-ban fs-3 me-3 text-secondary"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['cancelled_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Colis annulés sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Total Colis Envoyés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Total Colis Envoyés</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-boxes-packing fs-3 me-3 text-primary"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['total_deliveries_count'] ?? 0); ?></p> <!-- Clé corrigée -->
                    </div>
                </div>
                <small class="text-muted mt-2">Total de vos colis envoyés sur la période</small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-lg p-4 text-center rounded-lg">
    <h3 class="card-title text-dark mb-4">Actions Rapides</h3>
    <div class="d-grid gap-3 d-md-block">
        <a href="/sabroom/public/supplier/deliveries/create" class="btn btn-primary btn-lg me-md-3 mb-2 mb-md-0 rounded-pill shadow-sm hover-shadow-lg">
            <i class="fas fa-plus-circle me-2"></i> Créer un Nouveau Colis
        </a>
        <a href="/sabroom/public/supplier/deliveries" class="btn btn-outline-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">
            <i class="fas fa-boxes me-2"></i> Voir Mes Livraisons
        </a>
    </div>
</div>

<style>
    /* Styles spécifiques pour cette page de livraisons du livreur */
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .btn.hover-shadow-lg:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    /* Couleurs des icônes pour un aspect plus intégré (ajustées si nécessaire) */
    .text-warning { color: #ffc107 !important; } /* Jaune Bootstrap par défaut */
    .text-info { color: #0dcaf0 !important; }    /* Cyan Bootstrap par défaut */
    .text-primary { color: #0d6efd !important; }  /* Bleu Bootstrap par défaut */
    .text-success { color: #198754 !important; } /* Vert Bootstrap par défaut */
    .text-danger { color: #dc3545 !important; }  /* Rouge Bootstrap par défaut */
    .text-secondary { color: #6c757d !important; } /* Gris Bootstrap par défaut */
    .text-dark { color: #5c5c5c !important; }    /* Noir Bootstrap par défaut */
</style>
