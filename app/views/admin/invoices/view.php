<?php
// Fichier: app/views/admin/invoices/view.php - VUE DÉTAIL D'UNE FACTURE (ADMIN)

// Cette vue affiche les détails d'une facture spécifique et les livraisons qui y sont liées.
// Elle reçoit les variables:
// $invoice (objet Invoice contenant les détails de la facture)
// $deliveries_on_invoice (un objet mysqli_result ou un tableau de livraisons liées à cette facture)

// Assurez-vous que les variables sont définies
$invoice = $invoice ?? null;
$deliveries_on_invoice = $deliveries_on_invoice ?? [];

// Si l'objet $invoice n'est pas trouvé ou est null, on ne devrait pas afficher la page
if (!$invoice) {
    echo '<div class="alert alert-danger text-center rounded-lg shadow-sm p-4" role="alert">';
    echo '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i>';
    echo '<h4 class="alert-heading">Erreur: Facture non trouvée.</h4>';
    echo '<p>La facture que vous tentez de visualiser n\'existe pas ou a été supprimée.</p>';
    echo '<a href="/sabroom/public/admin/invoices" class="btn btn-primary mt-3 rounded-pill">Retour à la liste des factures</a>';
    echo '</div>';
    return; // Arrête l'exécution de la vue si la facture est manquante
}

// Statuts de livraison pour l'affichage (cohérent avec les autres pages)
$deliveryStatuses = [
    'pending' => ['label' => 'En dépôt', 'color_class' => 'bg-warning', 'text_color' => 'text-dark', 'icon' => 'fas fa-box'],
    'assigned' => ['label' => 'En cours', 'color_class' => 'bg-info', 'text_color' => 'text-dark', 'icon' => 'fas fa-truck-loading'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary', 'text_color' => 'text-white', 'icon' => 'fas fa-shipping-fast'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'text_color' => 'text-white', 'icon' => 'fas fa-check-circle'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger', 'text_color' => 'text-white', 'icon' => 'fas fa-undo-alt'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'text_color' => 'text-white', 'icon' => 'fas fa-ban']
];

// Statuts de facture pour l'affichage (cohérent avec les autres pages)
$invoiceStatuses = [
    'pending' => ['label' => 'En attente', 'color' => 'bg-warning'],
    'paid' => ['label' => 'Payée', 'color' => 'bg-success'],
    'cancelled' => ['label' => 'Annulée', 'color' => 'bg-secondary']
];

// Récupérer les informations de la facture (pour éviter de répéter $invoice->propriete)
$invoiceId = htmlspecialchars($invoice->id);
$supplierUsername = htmlspecialchars($invoice->supplier_username ?? 'N/A');
$supplierCompanyName = htmlspecialchars($invoice->supplier_company_name ?? 'N/A');
$invoiceDate = htmlspecialchars(date('d/m/Y', strtotime($invoice->invoice_date)));
$dueDate = htmlspecialchars(date('d/m/Y', strtotime($invoice->due_date)));
$totalAmount = number_format($invoice->total_amount, 2);
$invoiceStatus = $invoiceStatuses[$invoice->status] ?? ['label' => 'Inconnu', 'color' => 'bg-secondary'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Détail de la Facture #<?php echo $invoiceId; ?></h1>
    <a href="/sabroom/public/admin/invoices" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour aux factures
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

<!-- Nouveau: Bouton Imprimer -->
<div class="text-end mb-3">
    <button class="btn btn-info btn-print rounded-pill shadow-sm" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Imprimer la Facture
    </button>
</div>

<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-primary text-white rounded-top-lg py-3">
        <h3 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i> Informations Générales</h3>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>ID Facture :</strong> <?php echo $invoiceId; ?><br>
                <strong>Fournisseur :</strong> <?php echo $supplierUsername; ?> (<?php echo $supplierCompanyName; ?>)<br>
                <strong>Date d'émission :</strong> <?php echo $invoiceDate; ?><br>
                <strong>Date d'échéance :</strong> <?php echo $dueDate; ?>
            </div>
            <div class="col-md-6 mb-3 text-md-end">
                <strong>Montant Total :</strong> <span class="fs-4 fw-bold text-success"><?php echo $totalAmount; ?> DT</span><br>
                <strong>Statut :</strong> 
                <span class="badge <?php echo $invoiceStatus['color']; ?> text-white p-2 fs-6 rounded-pill">
                    <?php echo htmlspecialchars($invoiceStatus['label']); ?>
                </span>
                <?php if ($invoice->status === 'pending'): ?>
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="invoiceActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="invoiceActionsDropdown">
                            <li>
                                <a class="dropdown-item text-success" href="#" onclick="updateInvoiceStatus(<?php echo $invoiceId; ?>, 'paid', event);">
                                    <i class="fas fa-check-circle me-2"></i> Marquer Payée
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="updateInvoiceStatus(<?php echo $invoiceId; ?>, 'cancelled', event);">
                                    <i class="fas fa-times-circle me-2"></i> Marquer Annulée
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark"><i class="fas fa-boxes me-2"></i> Colis Facturés</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($deliveries_on_invoice && $deliveries_on_invoice->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle responsive-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Colis</th>
                            <th scope="col">Description</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Prix Colis (DT)</th>
                            <th scope="col">Frais Livraison (DT)</th>
                            <th scope="col">Créé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDeliveredValue = 0;
                        $totalDeliveryFees = 0;
                        while ($delivery = $deliveries_on_invoice->fetch_assoc()):
                            $statusInfo = $deliveryStatuses[$delivery['status']] ?? ['label' => 'Inconnu', 'color_class' => 'bg-secondary', 'text_color' => 'text-white', 'icon' => 'fas fa-question-circle'];
                            $totalDeliveredValue += $delivery['declared_value'] ?? 0;
                            $totalDeliveryFees += $delivery['price'] ?? 0;
                        ?>
                            <tr>
                                <td data-label="ID Colis"><?php echo htmlspecialchars($delivery['unique_id']); ?></td>
                                <td data-label="Description"><?php echo htmlspecialchars($delivery['description']); ?></td>
                                <td data-label="Destinataire"><?php echo htmlspecialchars($delivery['recipient_name']); ?> (<?php echo htmlspecialchars($delivery['recipient_city']); ?>)</td>
                                <td data-label="Statut">
                                    <span class="badge <?php echo $statusInfo['color_class']; ?> <?php echo $statusInfo['text_color']; ?> p-2 fs-6 rounded-pill">
                                        <i class="<?php echo $statusInfo['icon']; ?> me-1"></i> <?php echo htmlspecialchars($statusInfo['label']); ?>
                                    </span>
                                </td>
                                <td data-label="Prix Colis"><?php echo number_format($delivery['declared_value'] ?? 0, 2); ?></td>
                                <td data-label="Frais Livraison"><?php echo number_format($delivery['price'] ?? 0, 2); ?></td>
                                <td data-label="Créé le"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($delivery['created_at']))); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="total-row fw-bold">
                            <td colspan="4">Totaux des colis facturés:</td>
                            <td data-label="Total Prix Colis"><?php echo number_format($totalDeliveredValue, 2); ?> DT</td>
                            <td data-label="Total Frais Livraison"><?php echo number_format($totalDeliveryFees, 2); ?> DT</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center shadow-sm rounded-lg p-4" role="alert">
                    <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                    <h4 class="alert-heading text-muted">Aucun colis lié à cette facture.</h4>
                    <p class="text-muted">Il se peut qu'un problème soit survenu lors de la liaison des colis à la facture.</p>
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

    /* Styles spécifiques pour le tableau de colis */
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
    .total-row td {
        border-top: 2px solid #eee;
        font-weight: bold;
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
    .text-primary { color: #0d6efd !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-secondary { color: #6c757d !important; }
    .text-dark { color: #212529 !important; } /* Pour les badges sur fond clair */

    /* Styles pour le bouton d'impression */
    .btn-print {
        /* Permet de le masquer uniquement à l'impression */
        display: inline-block;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        transition: box-shadow .2s ease-in-out;
    }
    .btn-print:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cette fonction est utilisée pour les boutons "Marquer Payée" et "Marquer Annulée"
    // qui se trouvent dans la section d'informations générales de la facture
    window.updateInvoiceStatus = function(invoiceId, newStatus, event) {
        event.preventDefault(); // Empêche le comportement par défaut du lien (rechargement)

        if (confirm(`Êtes-vous sûr de vouloir marquer cette facture ${invoiceId} comme "${newStatus}" ?`)) {
            const formData = new FormData();
            formData.append('invoice_id', invoiceId);
            formData.append('status', newStatus);

            fetch('/sabroom/public/admin/invoices/update-status', {
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
