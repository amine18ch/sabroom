<?php
// Fichier: app/views/suppliers/invoices/list.php - LISTE MES FACTURES (FOURNISSEUR)

// Cette vue affiche la liste des factures pour le fournisseur connecté.
// Elle reçoit les variables:
// $invoices (un objet mysqli_result des factures du fournisseur)
// $filter_start_date, $filter_end_date, $filter_status (pour la persistance des filtres)

// Assurez-vous que les variables sont définies
$invoices = $invoices ?? null;

// Assurer que les variables de filtre sont définies pour le formulaire HTML
$filter_start_date_html = $filter_start_date ?? '';
$filter_end_date_html = $filter_end_date ?? '';
$filter_status_html = $filter_status ?? '';

// Statuts de facture pour l'affichage et les filtres (cohérent avec l'admin)
$invoiceStatuses = [
    'pending' => ['label' => 'En attente', 'color' => 'bg-warning'],
    'paid' => ['label' => 'Payée', 'color' => 'bg-success'],
    'cancelled' => ['label' => 'Annulée', 'color' => 'bg-secondary']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Mes Factures</h1>
    <a href="/sabroom/public/dashboard" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour au Tableau de bord
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

<!-- BLOC : FORMULAIRE DE FILTRE DES FACTURES POUR FOURNISSEUR -->
<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres des Factures</h5>
    </div>
    <div class="card-body">
        <form id="invoiceFilterForm" action="/sabroom/public/supplier/invoices" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="filter_start_date" class="form-label">Date de début (émission):</label>
                <input type="date" class="form-control" id="filter_start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date_html); ?>">
            </div>
            <div class="col-md-5">
                <label for="filter_end_date" class="form-label">Date de fin (émission):</label>
                <input type="date" class="form-control" id="filter_end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date_html); ?>">
            </div>
            <div class="col-md-2">
                <label for="filter_status" class="form-label">Statut:</label>
                <select class="form-select" id="filter_status" name="status">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($invoiceStatuses as $key => $info): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($filter_status_html == $key) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($info['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm hover-shadow-lg">Appliquer les filtres</button>
            </div>
        </form>
    </div>
</div>
<!-- FIN BLOC : FORMULAIRE DE FILTRE DES FACTURES POUR FOURNISSEUR -->

<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($invoices && $invoices->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle responsive-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Facture</th>
                            <th scope="col">Date Facture</th>
                            <th scope="col">Date Échéance</th>
                            <th scope="col">Montant Total (DT)</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td data-label="ID Facture"><?php echo htmlspecialchars($invoice['id']); ?></td>
                                <td data-label="Date Facture"><?php echo htmlspecialchars(date('d/m/Y', strtotime($invoice['invoice_date']))); ?></td>
                                <td data-label="Date Échéance"><?php echo htmlspecialchars(date('d/m/Y', strtotime($invoice['due_date']))); ?></td>
                                <td data-label="Montant Total"><?php echo number_format($invoice['total_amount'], 2); ?> DT</td>
                                <td data-label="Statut">
                                    <?php
                                    $statusInfo = $invoiceStatuses[$invoice['status']] ?? ['label' => 'Inconnu', 'color' => 'bg-secondary'];
                                    ?>
                                    <span class="badge <?php echo $statusInfo['color']; ?> text-white p-2 fs-6 rounded-pill">
                                        <?php echo htmlspecialchars($statusInfo['label']); ?>
                                    </span>
                                </td>
                                <td data-label="Actions" class="text-center">
                                    <!-- CORRECTION ICI: Le lien "Voir" doit pointer vers la route fournisseur -->
                                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="/sabroom/public/supplier/invoices/view/<?php echo htmlspecialchars($invoice['id']); ?>" target="_blank">
                                        <i class="fas fa-eye me-2"></i> Voir
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center shadow-sm rounded-lg p-4" role="alert">
                    <i class="fas fa-file-invoice fa-3x mb-3 text-muted"></i>
                    <h4 class="alert-heading text-muted">Aucune facture trouvée pour votre compte.</h4>
                    <p class="text-muted">Les factures générées par l'administrateur apparaîtront ici.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Styles généraux pour les cartes */
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        background-color: #ffffff;
        border-radius: 0.75rem;
    }
    .card:hover {
        box-shadow: 0 0.8rem 1.5rem rgba(0, 0, 0, 0.1) !important;
    }
    .btn.shadow-sm.hover-shadow-lg {
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        transition: box-shadow .2s ease-in-out;
    }
    .btn.shadow-sm.hover-shadow-lg:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

    /* Styles spécifiques pour le tableau */
    .table-responsive {
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .responsive-table thead {
        background-color: #007bff;
        color: white;
    }
    .responsive-table th,
    .responsive-table td {
        padding: 0.75rem;
        border-top: 1px solid #e9ecef;
        text-align: left;
        white-space: nowrap;
    }
    .responsive-table th {
        border-bottom: none;
    }
    .responsive-table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }
    .responsive-table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
    }
    
    /* Styles pour les badges de statut */
    .badge {
        font-weight: 600;
        padding: 0.5em 0.8em;
        font-size: 0.8em;
        border-radius: 0.5rem;
    }
    /* Couleurs des icônes/texte (cohérent avec les classes Bootstrap) */
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    .text-primary { color: #5c5c5c !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-secondary { color: #6c757d !important; }

    /* Media Queries pour la réactivité du tableau */
    @media (max-width: 991.98px) {
        .table-responsive { overflow-x: hidden; }
        .responsive-table { border: 0; }
        .responsive-table thead { display: none; }
        .responsive-table tbody, .responsive-table tr, .responsive-table td { display: block; width: 100%; }
        .responsive-table tr { margin-bottom: 1rem; border: 1px solid #e9ecef; border-radius: 0.75rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 1rem; background-color: #ffffff; }
        .responsive-table td { border: none; position: relative; padding-left: 50% !important; text-align: right; white-space: normal; }
        .responsive-table td::before {
            content: attr(data-label);
            position: absolute; left: 0.75rem; width: 45%; padding-right: 10px; white-space: nowrap; text-align: left; font-weight: 600; color: #555;
        }
        .responsive-table td:last-child { text-align: center; padding-left: 0 !important; margin-top: 1rem; border-top: 1px solid #e9ecef; padding-top: 1rem; }
        .responsive-table td:last-child::before { content: none; }
    }
</style>
