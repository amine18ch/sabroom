<?php
// Fichier: app/views/drivers/dashboard.php - TABLEAU DE BORD LIVREUR

// Vue du tableau de bord du livreur.
// $username, $stats, $filter_start_date et $filter_end_date sont passés par le contrôleur.
// Design moderne et professionnel avec des icônes, des ombres et une palette de couleurs épurée.

// Assurez-vous que les variables passées depuis le contrôleur sont définies
$username = $username ?? 'Livreur';
$stats = $stats ?? []; // S'assurer que $stats est un tableau même s'il est null
$filter_start_date = $filter_start_date ?? date('Y-m-d'); // Date actuelle par défaut si non définie
$filter_end_date = $filter_end_date ?? date('Y-m-d');     // Date actuelle par défaut si non définie
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-dark mb-0">Tableau de Bord Livreur</h1>
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

<!-- NOUVEAU BLOC : FORMULAIRE DE FILTRE DE DATE -->
<div class="card shadow-sm mb-4 border-0 rounded-lg">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres du Tableau de Bord <small class="text-muted">(Période : <?php echo htmlspecialchars($filter_start_date); ?> au <?php echo htmlspecialchars($filter_end_date); ?>)</small></h5>
    </div>
    <div class="card-body">
        <form action="/sabroom/public/driver/dashboard" method="GET" class="row g-3 align-items-end">
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
<!-- FIN NOUVEAU BLOC -->

<div class="row g-4 mb-5">
    <!-- Carte : Colis assignés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                    <h5 class="card-title text-muted mb-2">Total des colis Pick-up</h5>
                    <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['total_assigned'] ?? 0); ?></p>
                </div>
                <small class="text-muted mt-2">Total des colis qui vous sont assignés</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis livrés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="card-title text-muted mb-2">Colis livrés</h5>
                    <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['delivered_count'] ?? 0); ?></p>
                </div>
                <small class="text-muted mt-2">Colis livrés avec succès sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis en transit -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                    <h5 class="card-title text-muted mb-2">Colis en cours de livraison</h5>
                    <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['in_transit_count'] ?? 0); ?></p>
                </div>
                <small class="text-muted mt-2">Colis actuellement en cours de livraison</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis retournés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <i class="fas fa-undo-alt fa-3x text-danger mb-3"></i>
                    <h5 class="card-title text-muted mb-2">Colis retournés</h5>
                    <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['returned_count'] ?? 0); ?></p>
                </div>
                <small class="text-muted mt-2">Colis retournés sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Colis Annulés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body text-center d-flex flex-column justify-content-between">
                <div>
                    <i class="fas fa-ban fs-3 me-3 text-secondary"></i>
                    <h5 class="card-title text-muted mb-2">Colis Annulés</h5>
                    <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['cancelled_count'] ?? 0); ?></p>
                </div>
                <small class="text-muted mt-2">Colis annulés sur la période</small>
            </div>
        </div>
    </div>

    <!-- Carte : Total des colis que vous avez gérés -->
    <div class="col-md-4 col-sm-6">
        <div class="card h-100 border-0 rounded-lg shadow-sm hover-shadow-lg transition-all">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title text-muted mb-3">Total des colis gérés</h5>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-boxes-packing fs-3 me-3 text-info"></i>
                        <p class="card-text fs-2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($stats['total_assigned'] ?? 0); ?></p>
                    </div>
                </div>
                <small class="text-muted mt-2">Total de vos colis traités sur la période</small>
            </div>
        </div>
    </div>
</div>

<!-- NOUVELLE SECTION : ACTIONS RAPIDES -->
<div class="card shadow-lg p-4 text-center rounded-lg">
    <h3 class="card-title text-dark mb-4">Actions Rapides</h3>
    <div class="d-grid gap-3 d-md-block">
        <a href="/sabroom/public/driver/deliveries" class="btn btn-primary btn-lg me-md-3 mb-2 mb-md-0 rounded-pill shadow-sm hover-shadow-lg">
            <i class="fas fa-boxes me-2"></i> Voir Mes Livraisons
        </a>
    </div>
</div>
<!-- FIN NOUVELLE SECTION -->

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
    .text-primary { color: #5c5c5c !important; }  /* Bleu Bootstrap par défaut */
    .text-success { color: #198754 !important; } /* Vert Bootstrap par défaut */
    .text-danger { color: #dc3545 !important; }  /* Rouge Bootstrap par défaut */
    .text-secondary { color: #6c757d !important; } /* Gris Bootstrap par défaut */
    .text-dark { color: #5c5c5c !important; }    /* Noir Bootstrap par défaut */
    .fa-shipping-fast { color: #ffc107 !important; }
</style>
