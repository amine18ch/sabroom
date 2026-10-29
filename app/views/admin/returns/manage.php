<?php
// Fichier: app/views/admin/returns/manage.php - GESTION DES COLIS RETOURNÉS (ADMIN)

// Cette vue permet à l'administrateur de visualiser et gérer les colis retournés.
// Elle reçoit les variables:
// $returned_deliveries (un objet mysqli_result des livraisons avec le statut 'returned')

// Assurez-vous que les variables sont définies
$returned_deliveries = $returned_deliveries ?? null;

// Statuts de livraison pour l'affichage des badges (doit être cohérent avec DeliveryStatuses ailleurs)
$deliveryStatuses = [
    'pending' => ['label' => 'En dépôt', 'color_class' => 'bg-warning', 'icon' => 'fas fa-box', 'text_color' => 'text-dark'],
    'assigned' => ['label' => 'En cours', 'color_class' => 'bg-info', 'icon' => 'fas fa-truck-loading', 'text_color' => 'text-dark'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary', 'icon' => 'fas fa-shipping-fast', 'text_color' => 'text-white'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'icon' => 'fas fa-check-circle', 'text_color' => 'text-white'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger', 'icon' => 'fas fa-undo-alt', 'text_color' => 'text-white'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'icon' => 'fas fa-ban', 'text_color' => 'text-white']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Gestion des Colis Retournés</h1>
    <a href="/sabroom/public/admin/dashboard" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
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

<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-danger text-white rounded-top-lg py-3">
        <h3 class="card-title mb-0"><i class="fas fa-undo-alt me-2"></i> Colis avec Statut "Retour"</h3>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <?php if ($returned_deliveries && $returned_deliveries->num_rows > 0): ?>
                <table class="table table-hover table-striped align-middle responsive-table">
                    <thead>
                        <tr>
                            <th scope="col">ID Colis</th>
                            <th scope="col">Fournisseur</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Ville</th>
                            <th scope="col">Motif Retour</th>
                            <th scope="col">Date Retour</th>
                            <th scope="col" class="text-center">Fragile</th>
                            <th scope="col" class="text-center">Échange</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($delivery = $returned_deliveries->fetch_assoc()): ?>
                            <tr>
                                <td data-label="ID Colis"><?php echo htmlspecialchars($delivery['unique_id']); ?></td>
                                <td data-label="Fournisseur">
                                    <?php echo htmlspecialchars($delivery['supplier_username'] ?? 'N/A'); ?>
                                    <?php echo ($delivery['supplier_company_name']) ? ' (' . htmlspecialchars($delivery['supplier_company_name']) . ')' : ''; ?>
                                </td>
                                <td data-label="Destinataire"><?php echo htmlspecialchars($delivery['recipient_name']); ?></td>
                                            <td data-label="Ville"><?php echo htmlspecialchars($delivery['recipient_city']); ?></td>
                                            <td data-label="Motif Retour"><?php echo htmlspecialchars($delivery['return_reason'] ?? 'N/A'); ?></td>
                                            <td data-label="Date Retour"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($delivery['updated_at']))); ?></td>
                                            <td data-label="Fragile" class="text-center">
                                                <?php if ($delivery['is_fragile']): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger" title="Colis Fragile"></i> Oui
                                                <?php else: ?>
                                                    <i class="fas fa-minus-circle text-muted"></i> Non
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Échange" class="text-center">
                                                <?php if ($delivery['accepts_exchange']): ?>
                                                    <i class="fas fa-exchange-alt text-success" title="Accepte l'échange"></i> Oui
                                                <?php else: ?>
                                                    <i class="fas fa-minus-circle text-muted"></i> Non
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Actions" class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="returnActions<?php echo htmlspecialchars($delivery['id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="returnActions<?php echo htmlspecialchars($delivery['id']); ?>">
                                                        <li>
                                                            <a class="dropdown-item text-primary" href="/sabroom/public/admin/deliveries/edit/<?php echo htmlspecialchars($delivery['id']); ?>" title="Modifier les détails du colis">
                                                                <i class="fas fa-edit me-2"></i> Modifier Colis
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-info" href="#" onclick="updateDeliveryStatus(<?php echo htmlspecialchars($delivery['id']); ?>, 'pending', event);">
                                                                <i class="fas fa-redo me-2"></i> Repasser en Dépôt
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" onclick="updateDeliveryStatus(<?php echo htmlspecialchars($delivery['id']); ?>, 'delivered_to_sender', event);">
                                                                <i class="fas fa-box-open me-2"></i> Retourné au Fournisseur
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-secondary" href="#" onclick="updateDeliveryStatus(<?php echo htmlspecialchars($delivery['id']); ?>, 'cancelled', event);">
                                                                <i class="fas fa-ban me-2"></i> Annuler Définitivement
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info text-center shadow-sm rounded-lg p-4" role="alert">
                                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                <h4 class="alert-heading text-muted">Aucun colis actuellement en statut "Retour".</h4>
                                <p class="text-muted">Tous les colis retournés nécessitant une action apparaîtront ici.</p>
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
                    overflow: visible !important; /* IMPORTANT: Override overflow:hidden to allow dropdowns to show */
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                }
                .responsive-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 0;
                }
                .responsive-table thead {
                    background-color: #dc3545; /* Rouge pour les retours */
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
                    background-color: rgba(220, 53, 69, 0.05) !important; /* Rouge clair au survol */
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

                /* FIX POUR LES DROPDOWNS DANS LES TABLEAUX (APPLIQUÉ ICI) */
                .dropdown {
                    position: relative; /* Nécessaire pour le positionnement absolu du menu */
                }

                .dropdown-item {
                    display: flex;
                    align-items: center;
                    font-size: 0.9rem;
                }
                .dropdown-item i {
                    margin-right: 0.75rem;
                    width: 1.25rem;
                    text-align: center;
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

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Fonction pour afficher un message temporaire (similaire aux messages flash)
                function showTemporaryMessage(message, type = 'info') {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `alert alert-${type} alert-dismissible fade show mt-3 rounded-lg shadow-sm`;
                    messageDiv.setAttribute('role', 'alert');
                    messageDiv.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                    const mainCardBody = document.querySelector('.card-body'); // Sélectionne le corps de la première carte
                    mainCardBody.prepend(messageDiv);
                    setTimeout(() => messageDiv.remove(), 5000); // Supprime le message après 5 secondes
                }

                // Fonction de mise à jour du statut de la livraison (pour les actions de retour)
                window.updateDeliveryStatus = async function(deliveryId, newStatus, event) {
                    event.preventDefault(); // Empêche le comportement par défaut du lien

                    let confirmationMessage = `Êtes-vous sûr de vouloir changer le statut de ce colis (${deliveryId}) à "${newStatus}" ?`;
                    if (newStatus === 'delivered_to_sender') {
                        confirmationMessage = `Confirmer le retour définitif du colis (${deliveryId}) au fournisseur ?`;
                    } else if (newStatus === 'pending') {
                        confirmationMessage = `Confirmer le repassage du colis (${deliveryId}) en dépôt pour une nouvelle tentative de livraison ?`;
                    } else if (newStatus === 'cancelled') {
                        confirmationMessage = `Confirmer l'annulation définitive du colis (${deliveryId}) ?`;
                    }

                    if (confirm(confirmationMessage)) {
                        const formData = new FormData();
                        formData.append('delivery_id', deliveryId);
                        formData.append('status', newStatus);
                        // Si vous avez besoin d'un motif pour 'delivered_to_sender' ou 'cancelled', vous devrez ajouter une modale ici.
                        // Pour l'instant, on suppose que le motif de 'returned' est suffisant ou non requis pour ces transitions.

                        try {
                            // NOUVEAU: Appeler la route dédiée de l'AdminController
                            const response = await fetch('/sabroom/public/admin/returns/update-status', { 
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                showTemporaryMessage(data.message, 'success');
                                window.location.reload(); // Recharger la page pour refléter le changement
                            } else {
                                showTemporaryMessage('Échec de la mise à jour : ' + data.message, 'danger');
                            }
                        } catch (error) {
                            console.error('Erreur lors de la mise à jour du statut:', error);
                            showTemporaryMessage('Une erreur réseau est survenue. Voir la console.', 'danger');
                        }
                    }
                };
            });
            </script>
