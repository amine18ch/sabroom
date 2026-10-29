<?php
// Fichier: app/views/drivers/deliveries.php - LISTE LIVRAISONS LIVREUR

// Cette vue affiche la liste des livraisons assignées au livreur connecté.
// Elle reçoit la variable $deliveries (un objet mysqli_result des livraisons).
// Design de tableau simplifié, moderne et fonctionnel avec corrections de dropdown et statut.

$deliveryStatuses = [
    'pending' => ['label' => 'En dêpot', 'color_class' => 'bg-primary', 'text_color' => 'text-warning', 'icon' => 'fas fa-hourglass-half'],
    'assigned' => ['label' => 'Pik-up', 'color_class' => 'bg-info', 'text_color' => 'text-info', 'icon' => 'fas fa-truck-loading'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-warning', 'text_color' => 'text-primary', 'icon' => 'fas fa-shipping-fast'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'text_color' => 'text-success', 'icon' => 'fas fa-check-circle'],
    'returned' => ['label' => 'Retournée', 'color_class' => 'bg-danger', 'text_color' => 'text-danger', 'icon' => 'fas fa-undo-alt'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'text_color' => 'text-secondary', 'icon' => 'fas fa-ban']
];

// Liste des motifs de retour (pour le dropdown de la modale)
$returnReasons = [
    'Client ne repond pas',
    'Adresse incorrecte',
    'Reporte pour une autre date',
    'Refus du client',
    'Colis endommage',
    'Autre'
];

// Assurez-vous que les variables de date de filtre sont définies pour le formulaire
// Si elles sont null (première visite sans filtre), on utilise la date actuelle.
// Sinon, on utilise la valeur passée par le contrôleur.
$filter_start_date_html = $filter_start_date ?? date('Y-m-d');
$filter_end_date_html = $filter_end_date ?? date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Mes Livraisons Assignées</h1>
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

<!-- Formulaire de Filtre de Date -->
<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Filtres des Livraisons <small class="text-muted">(Période : <?php echo htmlspecialchars($filter_start_date_html); ?> au <?php echo htmlspecialchars($filter_end_date_html); ?>)</small></h5>
    </div>
    <div class="card-body">
        <form action="/sabroom/public/driver/deliveries" method="GET" class="row g-3 align-items-end">
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
<!-- Fin Formulaire de Filtre -->


<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($deliveries && $deliveries->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle delivery-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Colis</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Description</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Téléphone Dest.</th>
                            <th scope="col">Adresse Dest.</th>
                            <th scope="col">Créé le</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($delivery = $deliveries->fetch_assoc()):
                            $currentStatus = $deliveryStatuses[$delivery['status']] ?? ['label' => 'Retour Définitif', 'color_class' => 'bg-danger', 'text_color' => 'text-secondary', 'icon' => 'fas fa-question-circle'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($delivery['unique_id']); ?></td>
                                <td>
                                    <span class="badge <?php echo $currentStatus['color_class']; ?> text-white p-2 fs-6 rounded-pill">
                                        <i class="<?php echo $currentStatus['icon']; ?> me-1"></i> <?php echo htmlspecialchars($currentStatus['label']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($delivery['description']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['recipient_name']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['recipient_phone']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['destination_address']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($delivery['created_at']))); ?></td>
                                <td class="text-center">
                                     <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle rounded-pill btn-sm shadow-sm hover-shadow-lg" type="button" id="statusDropdown<?php echo htmlspecialchars($delivery['id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            Changer Statut
                                        </button>
                                        <ul class="dropdown-menu shadow-sm" aria-labelledby="statusDropdown<?php echo htmlspecialchars($delivery['id']); ?>" style="z-index: 1050;">
                                            <?php foreach ($deliveryStatuses as $key => $info): ?>
                                                <?php
                                                if ($key === $delivery['status']) continue;
                                                // Règle de transition plus stricte: pas de retour arrière si déjà livré/retourné/annulé
                                                if (in_array($delivery['status'], ['delivered', 'returned', 'cancelled']) && !in_array($key, ['delivered', 'returned', 'cancelled'])) continue;
                                                // Règle: ne pas passer de 'in_transit' à 'pending' ou 'assigned'
                                                if ($delivery['status'] === 'in_transit' && ($key === 'pending' || $key === 'assigned')) continue;
                                                ?>
                                                <li>
                                                    <!-- L'appel onclick doit passer l'objet event, et des data-attributs pour le JavaScript -->
                                                    <a class="dropdown-item <?php echo $info['text_color']; ?>" 
                                                       href="#" 
                                                       data-delivery-id="<?php echo htmlspecialchars($delivery['id']); ?>" 
                                                       data-new-status="<?php echo htmlspecialchars($key); ?>"
                                                       data-current-status="<?php echo htmlspecialchars($delivery['status']); ?>"
                                                       onclick="handleStatusChange(event);">
                                                        <i class="<?php echo $info['icon']; ?> me-2"></i> <?php echo htmlspecialchars($info['label']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center no-records p-4">
                                <i class="fas fa-truck-ramp-box fa-3x mb-3 text-muted"></i>
                                <h4 class="alert-heading text-muted">Aucune livraison assignée pour le moment.</h4>
                                <p class="text-muted">Vos nouvelles missions de livraison apparaîtront ici.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Modale Motif de Retour -->
    <div class="modal fade" id="returnReasonModal" tabindex="-1" aria-labelledby="returnReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="returnReasonModalLabel"><i class="fas fa-undo-alt me-2"></i> Motif de Retour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Veuillez sélectionner un motif pour le retour de ce colis :</p>
                    <div class="mb-3">
                        <label for="returnReasonSelect" class="form-label">Motif :</label>
                        <select class="form-select rounded-lg" id="returnReasonSelect" required>
                            <option value="">-- Sélectionner un motif --</option>
                            <?php foreach ($returnReasons as $reason): ?>
                                <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" id="modalDeliveryId">
                    <input type="hidden" id="modalNewStatus">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger rounded-pill" id="confirmReturnBtn">Confirmer Retour</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin Modale Motif de Retour -->
	
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
    // Récupération des éléments de la modale de motif de retour
    const returnReasonModal = new bootstrap.Modal(document.getElementById('returnReasonModal'));
    const returnReasonSelect = document.getElementById('returnReasonSelect');
    const confirmReturnBtn = document.getElementById('confirmReturnBtn');
    const modalDeliveryId = document.getElementById('modalDeliveryId');
    const modalNewStatus = document.getElementById('modalNewStatus');

    // La fonction principale de changement de statut, qui déclenchera la modale si nécessaire
    // Elle sera appelée par les onclick des liens du dropdown
    window.handleStatusChange = function(event) { // Rendre global pour être accessible depuis onclick
        event.preventDefault(); // <-- LA LIGNE CRUCIALE : Empêche le comportement par défaut du lien <a>

        const deliveryId = event.target.dataset.deliveryId;
        const newStatus = event.target.dataset.newStatus;
        const currentStatus = event.target.dataset.currentStatus;

        console.log(`handleStatusChange called for Delivery ID: ${deliveryId}, New Status: ${newStatus}, Current Status: ${currentStatus}`);

        if (newStatus === 'returned') {
            // Si le nouveau statut est 'returned', ouvrir la modale pour choisir un motif
            modalDeliveryId.value = deliveryId;
            modalNewStatus.value = newStatus;
            returnReasonSelect.value = ""; // Réinitialiser le select pour un nouveau choix
            returnReasonModal.show();
        } else {
            // Pour les autres statuts (non 'returned'), procéder directement à la mise à jour
            updateDeliveryStatus(deliveryId, newStatus);
        }
    };

    // Fonction pour la mise à jour du statut (y compris le motif de retour si fourni)
    // Cette fonction sera appelée par handleStatusChange ou par le bouton de la modale
    function updateDeliveryStatus(deliveryId, newStatus, returnReason = null) {
        console.log(`updateDeliveryStatus called for Delivery ID: ${deliveryId}, New Status: ${newStatus}, Return Reason: ${returnReason}`);

        let confirmationMessage = `Êtes-vous sûr de vouloir changer le statut de ce colis à "${newStatus}" ?`;
        if (returnReason) {
            confirmationMessage += `\nMotif de retour: "${returnReason}"`;
        }
        confirmationMessage += `\nCette action est irréversible pour certains statuts.`;

        if (confirm(confirmationMessage)) {
            console.log('Confirmation received. Preparing fetch request...');
            const formData = new FormData();
            formData.append('delivery_id', deliveryId);
            formData.append('status', newStatus);
            if (returnReason) { // N'ajouter le motif que s'il est fourni
                formData.append('return_reason', returnReason); 
            }

            fetch('/sabroom/public/driver/deliveries/update-status', {
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
                    window.location.reload(); // Recharger la page pour afficher le nouveau statut
                } else {
                    alert('Échec de la mise à jour : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch request failed:', error);
                alert('Une erreur est survenue lors de la mise à jour du statut: ' + error.message);
            });
        } else {
            console.log('Status change cancelled by user.');
        }
    }

    // Gérer le clic sur le bouton "Confirmer Retour" de la modale
    confirmReturnBtn.addEventListener('click', function() {
        const deliveryId = modalDeliveryId.value;
        const newStatus = modalNewStatus.value;
        const returnReason = returnReasonSelect.value;

        if (returnReason === "") {
            // Utiliser un message box personnalisé au lieu d'alert pour les validations internes
            showTemporaryMessage('Veuillez sélectionner un motif de retour.', 'danger');
            return;
        }

        returnReasonModal.hide(); // Cacher la modale
        updateDeliveryStatus(deliveryId, newStatus, returnReason); // Procéder à la mise à jour avec le motif
    });
});
</script>