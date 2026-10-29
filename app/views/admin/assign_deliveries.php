<?php
// Fichier: app/views/admin/assign_deliveries.php - PAGE D'ASSIGNATION PAR SCAN

// Cette vue permet à l'administrateur d'assigner des livraisons à des livreurs par scan de code-barres.
// Elle affiche dynamiquement les colis scannés et permet d'imprimer un bordereau pour le livreur sélectionné.
// Elle reçoit la variable $drivers (un tableau d'objets User ou un tableau de tableaux associatifs).

// Assurez-vous que $drivers est bien un tableau
$drivers = $drivers ?? [];

// Statuts de livraison pour l'affichage des badges (doit être cohérent avec DeliveryStatuses ailleurs)
$deliveryStatuses = [
    'pending' => ['label' => 'En dépôt', 'color_class' => 'bg-warning', 'icon' => 'fas fa-box'],
    'assigned' => ['label' => 'En cours', 'color_class' => 'bg-info', 'icon' => 'fas fa-truck-loading'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary', 'icon' => 'fas fa-shipping-fast'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'icon' => 'fas fa-check-circle'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger', 'icon' => 'fas fa-undo-alt'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'icon' => 'fas fa-ban']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Assigner des Colis par Code-barres</h1>
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

<div class="card shadow-lg border-0 rounded-lg mb-4">
    <div class="card-header bg-primary text-white rounded-top-lg py-3">
        <h3 class="card-title mb-0"><i class="fas fa-truck-loading me-2"></i> Assignation des Livraisons</h3>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 mb-3">
                <label for="driverSelect" class="form-label">Choisir le livreur : <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="driverSelect" name="driver_id" required>
                    <option value="">-- Sélectionnez un livreur --</option>
                    <?php
                    if (is_array($drivers) && !empty($drivers)):
                        foreach ($drivers as $driver):
                            // Assurez-vous que les données sont bien des tableaux associatifs ou des objets
                            $driverId = htmlspecialchars($driver['id'] ?? $driver->id ?? '');
                            $driverUsername = htmlspecialchars($driver['username'] ?? $driver->username ?? '');
                            $driverFirstName = htmlspecialchars($driver['first_name'] ?? $driver->first_name ?? '');
                            $driverLastName = htmlspecialchars($driver['last_name'] ?? $driver->last_name ?? '');
                    ?>
                            <option value="<?php echo $driverId; ?>">
                                <?php echo $driverUsername; ?> (<?php echo trim($driverFirstName . ' ' . $driverLastName); ?>)
                            </option>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <option value="" disabled>Aucun livreur disponible</option>
                    <?php endif; ?>
                </select>
                <div class="form-text">Sélectionnez le livreur avant de scanner les colis.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="barcodeInput" class="form-label">Scanner le code-barres du colis : <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-lg"><i class="fas fa-barcode"></i></span>
                    <input type="text" class="form-control form-control-lg rounded-end-lg" id="barcodeInput" placeholder="Scannez ici..." autofocus>
                </div>
                <div class="form-text">Après chaque scan, le colis sera automatiquement assigné.</div>
            </div>
        </div>
        <div class="mt-4 text-end"> <!-- Alignement à droite pour le bouton Imprimer -->
            <button id="printBordereauBtn" class="btn btn-info rounded-pill shadow-sm hover-shadow-lg" disabled>
                <i class="fas fa-print me-2"></i>Imprimer Bordereau de Livraison
            </button>
        </div>
    </div>
</div>

<!-- Section d'affichage des colis scannés -->
<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-light border-0 rounded-top-lg py-3">
        <h5 class="mb-0 text-dark">Colis scannés pour le livreur sélectionné</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID Colis</th>
                        <th>Description</th>
                        <th>Destinataire</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="scannedDeliveriesTableBody">
                    <!-- Les colis scannés seront ajoutés ici par JavaScript -->
                    <tr>
                        <td colspan="5" class="text-center text-muted p-4" id="noScannedDeliveriesMessage">
                            <i class="fas fa-boxes me-2"></i> Aucun colis scanné pour ce livreur.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Styles généraux pour les cartes et boutons (cohérence) */
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

    /* Styles spécifiques pour les champs de formulaire */
    .form-control, .form-select, .input-group-text {
        border-radius: 0.75rem !important;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .input-group .form-control:not(:last-child) {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .input-group .input-group-text:not(:first-child) {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .form-label {
        font-weight: 600;
        color: #555;
    }
    .card-header {
        background-color: #ff6600 !important; /* Bleu primaire */
        color: white;
        font-weight: 700;
        border-bottom: none;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        border-top-left-radius: calc(0.75rem - 1px) !important;
        border-top-right-radius: calc(0.75rem - 1px) !important;
    }
    .card-header h3 {
        color: white;
        font-size: 1.5rem;
        margin-bottom: 0;
    }
    .text-dark{
        color: #ffffff !important;
    }

    /* Styles pour la table des colis scannés */
    .table-responsive {
        border-radius: 0.75rem; /* Coins arrondis pour le conteneur du tableau */
        overflow: fixed; /* Permet aux dropdowns de déborder */
        overflow-x: auto; /* Permet le défilement horizontal si le contenu dépasse */
        -webkit-overflow-scrolling: touch; /* Améliore le défilement sur iOS */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Ombre légère pour le tableau */
    }
    .table thead {
        background-color: #f0f2f5; /* Fond léger pour l'en-tête de la table de scan */
        color: #333;
    }
    .table th {
        border-bottom: none;
        padding: 0.8rem 0.75rem;
        white-space: nowrap;
    }
    .table td {
        padding: 0.75rem;
        border-top: 1px solid #e9ecef;
        white-space: nowrap;
    }
    .table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.01); /* Encore plus léger */
    }
    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.03) !important;
    }
    .badge { /* Style pour les badges de statut */
        font-weight: 600;
        padding: 0.5em 0.8em;
        font-size: 0.8em;
        border-radius: 0.5rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const driverSelect = document.getElementById('driverSelect');
    const barcodeInput = document.getElementById('barcodeInput');
    const scannedDeliveriesTableBody = document.getElementById('scannedDeliveriesTableBody');
    const noScannedDeliveriesMessage = document.getElementById('noScannedDeliveriesMessage');
    const printBordereauBtn = document.getElementById('printBordereauBtn');

    // Mettre à jour les données de statut pour qu'elles correspondent à la page
    const deliveryStatuses = {
        'pending': { label: 'En dépôt', color_class: 'bg-warning', icon: 'fas fa-box' },
        'assigned': { label: 'En cours', color_class: 'bg-info', icon: 'fas fa-truck-loading' },
        'in_transit': { label: 'En cours de livraison', color_class: 'bg-primary', icon: 'fas fa-shipping-fast' },
        'delivered': { label: 'Livrée', color_class: 'bg-success', icon: 'fas fa-check-circle' },
        'returned': { label: 'Retour', color_class: 'bg-danger', icon: 'fas fa-undo-alt' },
        'cancelled': { label: 'Annulée', color_class: 'bg-secondary', icon: 'fas fa-ban' }
    };

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

    // Helper pour échapper les caractères HTML dans le JS
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str; // Retourne non-string tel quel
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Ajoute un colis à la liste affichée des colis scannés
    function addDeliveryToScannedList(delivery) {
        // Vérifier si le colis est déjà dans la liste par son ID de base de données
        if (document.getElementById(`scanned-row-${delivery.id}`)) {
            showTemporaryMessage(`Colis ${htmlspecialchars(delivery.unique_id)} est déjà dans la liste.`, 'warning');
            return;
        }

        const row = scannedDeliveriesTableBody.insertRow(0); // Insérer en haut de la liste
        row.id = `scanned-row-${delivery.id}`;
        row.dataset.deliveryId = delivery.id; // Stocke l'ID de la DB dans un data-attribut pour l'impression

        const statusInfo = deliveryStatuses[delivery.status] || { label: 'Inconnu', color_class: 'bg-secondary', icon: 'fas fa-question-circle' };

        row.innerHTML = `
            <td>${htmlspecialchars(delivery.unique_id)}</td>
            <td>${htmlspecialchars(delivery.description)}</td>
            <td>${htmlspecialchars(delivery.recipient_name)}</td>
            <td><span class="badge ${statusInfo.color_class} text-white p-2 rounded-pill"><i class="${statusInfo.icon} me-1"></i> ${htmlspecialchars(statusInfo.label)}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-danger remove-scanned-item rounded-pill" data-delivery-id="${htmlspecialchars(delivery.id)}">
                    <i class="fas fa-times me-1"></i> Retirer
                </button>
            </td>
        `;
        // Cacher le message "Aucun colis scanné"
        noScannedDeliveriesMessage.classList.add('d-none');
        printBordereauBtn.disabled = false; // Activer le bouton d'impression
    }

    // Load assigned deliveries for selected driver (via AJAX)
    async function loadAssignedDeliveriesForSelectedDriver() {
        const selectedDriverId = driverSelect.value;
        scannedDeliveriesTableBody.innerHTML = ''; // Clear current list
        printBordereauBtn.disabled = true; // Disable by default

        if (!selectedDriverId) {
            noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-boxes me-2"></i> Sélectionnez un livreur pour voir ses colis.';
            noScannedDeliveriesMessage.classList.remove('d-none');
            return;
        }

        // Display spinner while loading
        noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Chargement des colis assignés...';
        noScannedDeliveriesMessage.classList.remove('d-none');

        try {
            const response = await fetch(`/sabroom/public/admin/deliveries/get-by-driver-assigned/${selectedDriverId}`);
            if (!response.ok) {
                const errorText = await response.text();
                console.error("HTTP error when loading assigned deliveries:", response.status, errorText);
                throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
            }
            const data = await response.json();
            console.log("Data received for assigned deliveries:", data); // Log received JSON data

            if (data.success && data.deliveries && data.deliveries.length > 0) {
                data.deliveries.forEach(delivery => addDeliveryToScannedList(delivery));
                noScannedDeliveriesMessage.classList.add('d-none'); // Hide message if deliveries loaded
                printBordereauBtn.disabled = false;
            } else {
                noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-boxes me-2"></i> Aucun colis assigné pour ce livreur.';
                noScannedDeliveriesMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error loading assigned deliveries:', error);
            showTemporaryMessage('Erreur lors du chargement des colis assignés. Voir la console.', 'danger');
            noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2 text-danger"></i> Erreur de chargement des colis. Réessayez.';
            noScannedDeliveriesMessage.classList.remove('d-none');
        }
    }


    // Handle barcode input submission
    barcodeInput.addEventListener('keydown', async function(event) {
        if (event.key === 'Enter' || event.keyCode === 13) {
            event.preventDefault(); // Prevent form submission and page reload
            const barcode = barcodeInput.value.trim();
            const driverId = driverSelect.value;

            // NOUVEAU: Log the exact barcode string captured by JavaScript
            console.log("Barcode scanned (JS side):", barcode);

            if (driverId === "") {
                showTemporaryMessage('Veuillez sélectionner un livreur d\'abord.', 'danger');
                barcodeInput.value = '';
                return;
            }

            if (barcode === "") {
                showTemporaryMessage('Veuillez scanner un code-barres.', 'warning');
                return;
            }

            // Display temporary spinner
            noScannedDeliveriesMessage.classList.remove('d-none');
            noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Assignation du colis...';

            try {
                const formData = new FormData();
                formData.append('barcode', barcode);
                formData.append('driver_id', driverId);

                const response = await fetch('/sabroom/public/admin/assignDeliveryByBarcode', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                console.log("Server response after assignment:", data); // Log full response

                if (data.success) {
                    showTemporaryMessage(data.message, 'success');
                    addDeliveryToScannedList(data.delivery); 
                } else {
                    showTemporaryMessage(data.message, 'danger');
                }
            } catch (error) {
                console.error('Fetch request failed:', error);
                showTemporaryMessage('Une erreur réseau est survenue lors de l\'assignation. Vérifiez la console.', 'danger');
            } finally {
                // Reset no-deliveries message if no other action was taken
                if (scannedDeliveriesTableBody.children.length === 0 || (scannedDeliveriesTableBody.children.length === 1 && scannedDeliveriesTableBody.children[0].id === 'noScannedDeliveriesMessage' && !noScannedDeliveriesMessage.classList.contains('d-none'))) {
                    noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-boxes me-2"></i> Aucun colis scanné pour ce livreur.';
                    noScannedDeliveriesMessage.classList.remove('d-none'); // Ensure it's visible
                }
                barcodeInput.value = ''; // Always clear field after scan
                barcodeInput.focus();    // Keep focus for next scan
            }
        }
    });

    // Handle removing a scanned delivery (local display and unassign)
    scannedDeliveriesTableBody.addEventListener('click', async function(event) {
        if (event.target.classList.contains('remove-scanned-item') || event.target.closest('.remove-scanned-item')) {
            const button = event.target.classList.contains('remove-scanned-item') ? event.target : event.target.closest('.remove-scanned-item');
            const deliveryIdToRemove = button.dataset.deliveryId;
            
            if (confirm(`Êtes-vous sûr de vouloir retirer ce colis (ID: ${deliveryIdToRemove}) et annuler son assignation ?`)) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;

                try {
                    const response = await fetch(`/sabroom/public/admin/deliveries/unassign/${deliveryIdToRemove}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ delivery_id: deliveryIdToRemove })
                    });
                    const data = await response.json();

                    if (data.success) {
                        showTemporaryMessage(data.message, 'success');
                        const rowToRemove = document.getElementById(`scanned-row-${deliveryIdToRemove}`);
                        if (rowToRemove) {
                            rowToRemove.remove();
                            if (scannedDeliveriesTableBody.children.length === 0 || (scannedDeliveriesTableBody.children.length === 1 && scannedDeliveriesTableBody.children[0].id === 'noScannedDeliveriesMessage')) {
                                noScannedDeliveriesMessage.classList.remove('d-none');
                                noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-boxes me-2"></i> Aucun colis scanné pour ce livreur.';
                                printBordereauBtn.disabled = true;
                            } else {
                                printBordereauBtn.disabled = false;
                            }
                        }
                    } else {
                        showTemporaryMessage(data.message, 'danger');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-times me-1"></i> Retirer';
                    }
                } catch (error) {
                    console.error('Error during unassignment:', error);
                    showTemporaryMessage('Une erreur réseau est survenue lors de la désassignation. Voir la console.', 'danger');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-times me-1"></i> Retirer';
                }
            }
        }
    });


    // Handle displaying scanned deliveries when driver changes
    driverSelect.addEventListener('change', function() {
        loadAssignedDeliveriesForSelectedDriver();
        barcodeInput.focus(); // Keep focus on scan field
    });

    // Handle bordereau printing
    printBordereauBtn.addEventListener('click', function() {
        const selectedDriverId = driverSelect.value;
        if (!selectedDriverId) {
            showTemporaryMessage('Veuillez sélectionner un livreur pour imprimer le bordereau.', 'danger');
            return;
        }

        const scannedDeliveryIds = [];
        scannedDeliveriesTableBody.querySelectorAll('tr[id^="scanned-row-"]').forEach(row => {
            scannedDeliveryIds.push(row.dataset.deliveryId);
        });

        if (scannedDeliveryIds.length === 0) {
            showTemporaryMessage('Aucun colis à imprimer pour ce livreur.', 'warning');
            return;
        }

        const idsQuery = scannedDeliveryIds.map(id => `ids[]=${id}`).join('&');
        window.open(`/sabroom/public/admin/deliveries/print-summary?driver_id=${selectedDriverId}&${idsQuery}`, '_blank');
    });

    // Initialization: if a driver is selected on page load (e.g., after a form error)
    // or if the user returns to the page with a driver already chosen.
    if (driverSelect.value) {
        loadAssignedDeliveriesForSelectedDriver();
    } else {
        noScannedDeliveriesMessage.innerHTML = '<i class="fas fa-boxes me-2"></i> Sélectionnez un livreur pour voir ses colis.';
        noScannedDeliveriesMessage.classList.remove('d-none');
    }
});
</script>
