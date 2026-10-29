<?php
// Fichier: app/views/admin/invoices/list.php - LISTE DES FACTURES (ADMIN)

// Cette vue affiche la liste de toutes les factures pour l'administrateur.
// Elle reçoit les variables:
// $invoices (un objet mysqli_result des factures)
// $suppliers (un tableau d'objets User ou tableaux associatifs des fournisseurs pour le filtre)
// $filter_supplier_id, $filter_start_date, $filter_end_date, $filter_status (pour la persistance des filtres)

// Assurez-vous que les variables sont définies
$invoices = $invoices ?? null;
$suppliers = $suppliers ?? [];

// Assurer que les variables de filtre sont définies pour le formulaire HTML
$filter_supplier_id_html = $filter_supplier_id ?? '';
$filter_start_date_html = $filter_start_date ?? '';
$filter_end_date_html = $filter_end_date ?? '';
$filter_status_html = $filter_status ?? '';

// Statuts de facture pour l'affichage et les filtres
$invoiceStatuses = [
    'pending' => ['label' => 'En attente', 'color' => 'bg-warning'],
    'paid' => ['label' => 'Payée', 'color' => 'bg-success'],
    'cancelled' => ['label' => 'Annulée', 'color' => 'bg-secondary']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Gestion des Factures</h1>
    <a href="/sabroom/public/admin/invoices/generate-form" class="btn btn-primary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-file-invoice-dollar me-2"></i> Générer une nouvelle facture
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

<!-- BLOC : FORMULAIRE DE FILTRE DES FACTURES -->
<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres des Factures</h5>
    </div>
    <div class="card-body">
        <form id="invoiceFilterForm" action="/sabroom/public/admin/invoices" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filter_supplier_id" class="form-label">Fournisseur:</label>
                <select class="form-select" id="filter_supplier_id" name="supplier_id">
                    <option value="">Tous les fournisseurs</option>
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($filter_supplier_id_html == $supplier['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['username'] . ' (' . ($supplier['company_name'] ?? 'N/A') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_start_date" class="form-label">Date de début:</label>
                <input type="date" class="form-control" id="filter_start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date_html); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter_end_date" class="form-label">Date de fin:</label>
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
<!-- FIN BLOC : FORMULAIRE DE FILTRE DES FACTURES -->

<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($invoices && $invoices->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle responsive-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Facture</th>
                            <th scope="col">Fournisseur</th>
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
                                <td data-label="Fournisseur">
                                    <?php echo htmlspecialchars($invoice['supplier_username'] ?? 'N/A'); ?>
                                    <?php echo ($invoice['supplier_company_name']) ? ' (' . htmlspecialchars($invoice['supplier_company_name']) . ')' : ''; ?>
                                </td>
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
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="invoiceActions<?php echo htmlspecialchars($invoice['id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="invoiceActions<?php echo htmlspecialchars($invoice['id']); ?>">
                                            <li>
                                                <a class="dropdown-item text-primary" href="/sabroom/public/admin/invoices/view/<?php echo htmlspecialchars($invoice['id']); ?>" target="_blank">
                                                    <i class="fas fa-eye me-2"></i> Voir
                                                </a>
                                            </li>
                                            <?php if ($invoice['status'] === 'pending'): ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="#" onclick="updateInvoiceStatus(<?php echo htmlspecialchars($invoice['id']); ?>, 'paid', event);">
                                                    <i class="fas fa-check-circle me-2"></i> Marquer Payée
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="updateInvoiceStatus(<?php echo htmlspecialchars($invoice['id']); ?>, 'cancelled', event);">
                                                    <i class="fas fa-times-circle me-2"></i> Marquer Annulée
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center shadow-sm rounded-lg p-4" role="alert">
                    <i class="fas fa-file-invoice fa-3x mb-3 text-muted"></i>
                    <h4 class="alert-heading text-muted">Aucune facture trouvée.</h4>
                    <p class="text-muted">Générez de nouvelles factures pour les voir apparaître ici.</p>
                    <a href="/sabroom/public/admin/invoices/generate-form" class="btn btn-primary mt-3 rounded-pill">
                        Générer une facture
                    </a>
                </div>
            <?php endif; ?>
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
        overflow: fixed; /* Permet aux dropdowns de déborder */
        overflow-x: auto; /* Permet le défilement horizontal si le contenu dépasse */
        -webkit-overflow-scrolling: touch; /* Améliore le défilement sur iOS */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Ombre légère pour le tableau */
    }

    

    .table {
        margin-bottom: 0; /* Supprime la marge en bas du tableau */
    }
    .table thead {
        background-color: #007bff;
        color: white;
    }
    .table th {
        border-bottom: none;
        padding: 1rem 0.75rem;
    }
    .table td {
        padding: 0.75rem;
        border-top: 1px solid #e9ecef;
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
    /* Couleurs des icônes dans le dropdown */
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    .text-primary { color: #5c5c5c !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-secondary { color: #6c757d !important; }

    /* FIX POUR LES DROPDOWNS DANS LES TABLEAUX */
    .dropdown {
        position: relative; /* Ensure position: relative for dropdown menu to position correctly */
    }
   
    /* Si le dropdown est dans une cellule à droite, le faire s'ouvrir vers la gauche */
    /* Ceci est une règle générique, peut nécessiter des ajustements si la colonne est très à droite */
    .table-responsive .dropdown-menu.dropdown-menu-end {
        right: 0 !important;
        left: auto !important;
        transform: none !important;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }
    .dropdown-item i {
        margin-right: 0.75rem;
        width: 1.25rem; /* Alignement des icônes */
        text-align: center;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction de mise à jour du statut de la facture
    window.updateInvoiceStatus = function(invoiceId, newStatus, event) {
        event.preventDefault(); // Empêche le rechargement de la page

        if (confirm(`Êtes-vous sûr de vouloir marquer cette facture ${invoiceId} comme "${newStatus}" ?`)) {
            const formData = new FormData();
            formData.append('invoice_id', invoiceId);
            formData.append('status', newStatus);

            fetch('/sabroom/public/admin/invoices/update-status', { // Nouvelle route à créer
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Fetch response received. Status:', response.status);
                if (!response.ok) {
                    // Tenter de lire le message d'erreur du serveur si la réponse n'est pas OK
                    return response.json().then(errorData => {
                        console.error('Server error data:', errorData);
                        throw new Error(errorData.message || 'Erreur réseau ou serveur.');
                    }).catch(e => {
                        console.error('Error parsing server error response:', e);
                        throw new Error('Erreur réseau ou serveur inconnue, ou réponse non JSON.');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response data:', data);
                if (data.success) {
                    alert(data.message);
                    window.location.reload(); // Recharger la page après succès
                } else {
                    alert('Échec de la mise à jour : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour du statut de la facture.');
            });
        }
    };
});
</script>
