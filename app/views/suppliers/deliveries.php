
<?php
// Fichier: app/views/suppliers/deliveries.php

// Cette vue affiche la liste des livraisons créées par le fournisseur connecté.
// Elle reçoit la variable $deliveries (un objet mysqli_result) du contrôleur,
// ainsi que $filter_start_date et $filter_end_date pour les champs du filtre.

// Tableau des statuts de livraison avec libellés, classes de couleur et icônes personnalisés
$deliveryStatuses = [
    'pending' => ['label' => 'En dépôt', 'color_class' => 'bg-warning', 'text_color' => 'text-white', 'icon' => 'fas fa-box'],
    'assigned' => ['label' => 'Pik-up', 'color_class' => 'bg-info', 'text_color' => 'text-white', 'icon' => 'fas fa-truck-loading'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary', 'text_color' => 'text-white', 'icon' => 'fas fa-shipping-fast'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'text_color' => 'text-white', 'icon' => 'fas fa-check-circle'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger', 'text_color' => 'text-white', 'icon' => 'fas fa-undo-alt'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'text_color' => 'text-white', 'icon' => 'fas fa-ban']
];

// Assurez-vous que les variables de date de filtre sont définies pour le formulaire
// Si elles sont null (première visite sans filtre), on utilise la date actuelle.
// Sinon, on utilise la valeur passée par le contrôleur (qui peut être vide si l'utilisateur vide le champ).
$filter_start_date_html = $_GET['start_date'] ?? date('2025-05-02');
$filter_end_date_html = $filter_end_date ?? date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Mes Livraisons</h1>
    <a href="/sabroom/public/supplier/deliveries/create" class="btn btn-primary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-plus-circle me-2"></i>Créer une nouvelle livraison
    </a>
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

<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres des Livraisons</h5>
    </div>
    <div class="card-body">
        <!-- Le formulaire de filtre sera ici -->
        <form id="deliveryFilterForm" action="/sabroom/public/supplier/deliveries" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Date de début:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date_html); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">Date de fin:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date_html); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm hover-shadow-lg">Appliquer les filtres</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($deliveries && $deliveries->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">ID Colis</th>
                            <th scope="col">Description</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Ville</th>
                            <th scope="col">Poids (kg)</th>
                            <th scope="col">Dimensions (cm)</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Prix (DT)</th>
                            <th scope="col">Livreur Assigné</th>
                            <th scope="col">Date Création</th>
                            <th scope="col">Code-barres</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $deliveries->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['recipient_city']); ?></td>
                                <td><?php echo number_format($row['weight_kg'], 2); ?></td>
                                <td><?php echo number_format($row['length_cm'], 0) . 'x' . number_format($row['L_width_cm'], 0) . 'x' . number_format($row['height_cm'], 0); ?></td>
                                <td>
                                    <?php
                                    // Utilisation du tableau $deliveryStatuses pour obtenir le libellé, la couleur et l'icône
                                    $statusInfo = $deliveryStatuses[$row['status']] ?? ['label' => 'Retour Définitif', 'color_class' => 'bg-danger', 'text_color' => 'text-white', 'icon' => 'fas fa-question-circle'];
                                    ?>
                                    <span class="badge <?php echo $statusInfo['color_class']; ?> <?php echo $statusInfo['text_color']; ?> p-2 fs-6 rounded-pill">
                                        <i class="<?php echo $statusInfo['icon']; ?> me-1"></i> <?php echo htmlspecialchars($statusInfo['label']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['driver_username'] ?? 'Non assigné'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <!-- Conteneur pour le code-barres SVG -->
                                    <svg class="barcode-svg" data-barcode-value="<?php echo htmlspecialchars($row['unique_id']); ?>"></svg>
                                </td>
                                <td>
                                    <a href="/sabroom/public/supplier/deliveries/print-bordereau?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-info btn-sm rounded-pill" title="Imprimer Bordereau" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <!-- Les actions Modifier/Supprimer ne sont pas requises pour le fournisseur sur ses livraisons -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center no-records p-4">
                                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                <h4 class="alert-heading text-muted">Aucune livraison trouvée.</h4>
                                <p class="text-muted">Créez de nouvelles livraisons ou ajustez les filtres de date.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Styles généraux pour les cartes (si non déjà dans style.css) */
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        background-color: #ffffff;
        border-radius: 0.75rem; /* Coins arrondis */
    }
    .card:hover {
        box-shadow: 0 0.8rem 1.5rem rgba(0, 0, 0, 0.1) !important;
    }
    /* Style pour les boutons avec effet d'ombre au survol */
    .btn.shadow-sm.hover-shadow-lg {
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        transition: box-shadow .2s ease-in-out;
    }
    .btn.shadow-sm.hover-shadow-lg:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

    /* Styles spécifiques pour le tableau */
    .table-responsive {
        border-radius: 0.75rem; /* Coins arrondis pour le conteneur du tableau */
        overflow-x: auto; /* Permet le défilement horizontal si le contenu dépasse */
        -webkit-overflow-scrolling: touch; /* Améliore le défilement sur iOS */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Ombre légère pour le tableau */
    }
    .table {
        margin-bottom: 0; /* Supprime la marge en bas du tableau */
        min-width: 900px; /* Largeur minimale du tableau pour forcer le défilement sur les petits écrans */
        /* Ajustez cette valeur si le tableau est encore trop compressé ou trop large */
    }
    .table thead {
        background-color: #007bff;
        color: white;
    }
    .table th {
        border-bottom: none;
        padding: 1rem 0.75rem;
        white-space: nowrap; /* Empêche le texte de se couper dans les en-têtes */
    }
    .table td {
        padding: 0.75rem;
        border-top: 1px solid #e9ecef;
        white-space: nowrap; /* Empêche le texte de se couper dans les cellules */
    }
    .table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03); /* Légèrement plus foncé pour les lignes impaires */
    }
    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05) !important; /* Effet de survol sur la ligne */
    }
    
    /* Styles pour les badges de statut */
    .badge {
        font-weight: 600;
        padding: 0.5em 0.8em;
        font-size: 0.8em;
    }
    /* Couleurs des icônes dans le dropdown (si applicable) */
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    .text-primary { color: #5c5c5c !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-secondary { color: #6c757d !important; }

    /* Styles pour le code-barres SVG */
    .barcode-svg {
        max-width: 120px; /* Taille maximale pour le code-barres */
        height: 40px;
        display: block;
        margin: 0 auto; /* Centrer le code-barres */
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    // Exécute le code après que le DOM soit entièrement chargé
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionne tous les éléments SVG avec la classe 'barcode-svg'
        const barcodeSvgs = document.querySelectorAll('.barcode-svg');

        barcodeSvgs.forEach(svgElement => {
            const barcodeValue = svgElement.dataset.barcodeValue; // Récupère la valeur du code-barres de l'attribut data-
            if (barcodeValue) {
                // Génère le code-barres dans l'élément SVG
                JsBarcode(svgElement, barcodeValue, {
                    format: "CODE128",
                    displayValue: false, // Ne pas afficher la valeur textuelle sous le code-barres dans la liste
                    width: 1.5,
                    height: 60,
                    margin: 0
                });
            }
        });
    });
</script>
