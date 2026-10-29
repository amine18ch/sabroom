<?php
// Fichier: app/views/admin/deliveries/edit.php - FORMULAIRE DE MODIFICATION DE LIVRAISON

// Cette vue affiche le formulaire pour modifier une livraison existante.
// Elle reçoit les variables:
// $delivery (objet Delivery contenant les infos de la livraison à modifier)
// $drivers (tableau d'objets User ou tableaux associatifs des livreurs pour le dropdown)

// Assurez-vous que les variables sont définies
$delivery = $delivery ?? null;
$drivers = $drivers ?? [];

// Si l'objet $delivery n'est pas trouvé ou est null, on ne devrait pas afficher le formulaire
if (!$delivery) {
    echo '<div class="alert alert-danger text-center rounded-lg shadow-sm p-4" role="alert">';
    echo '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i>';
    echo '<h4 class="alert-heading">Erreur: Livraison non trouvée.</h4>';
    echo '<p>Le colis que vous tentez de modifier n\'existe pas ou a été supprimé.</p>';
    echo '<a href="/sabroom/public/admin/deliveries" class="btn btn-primary mt-3 rounded-pill">Retour à toutes les livraisons</a>';
    echo '</div>';
    return; // Arrête l'exécution de la vue si la livraison est manquante
}

// Liste des régions/gouvernorats de Tunisie pour le menu déroulant (cohérent avec create_delivery.php)
$tunisianRegions = [
    "Ariana", "Béja", "Ben Arous", "Bizerte", "Gabès", "Gafsa", "Jendouba",
    "Kairouan", "Kasserine", "Kébili", "Kef", "Mahdia", "Manouba", "Médenine",
    "Monastir", "Nabeul", "Sfax", "Sidi Bouzid", "Siliana", "Sousse", "Tataouine",
    "Tozeur", "Tunis", "Zaghouan"
];

// Statuts de livraison pour le menu déroulant du statut (cohérent avec les autres pages)
$deliveryStatuses = [
    'pending' => ['label' => 'Pick-up', 'color_class' => 'bg-warning'],
    'assigned' => ['label' => 'En dépôt', 'color_class' => 'bg-info'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Modifier la Livraison : #<?php echo htmlspecialchars($delivery->unique_id ?? $delivery->id); ?></h1>
    <a href="/sabroom/public/admin/deliveries" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour à toutes les livraisons
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

<!-- Le conteneur du formulaire prend toute la largeur disponible de la zone de contenu -->
<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-primary text-white rounded-top-lg py-3">
        <h3 class="card-title mb-0"><i class="fas fa-edit me-2"></i> Éditer les Détails de la Livraison</h3>
    </div>
    <div class="card-body p-4">
        <form action="/sabroom/public/admin/deliveries/update" method="POST">
            <!-- Champ caché pour l'ID de la livraison (essentiel pour la mise à jour) -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($delivery->id); ?>">

            <h4 class="mb-3 text-dark border-bottom pb-2">Informations du Colis</h4>
            <div class="mb-3">
                <label for="description" class="form-label">Description du colis <span class="text-danger">*</span></label>
                <textarea class="form-control rounded-lg" id="description" name="description" rows="3" required><?php echo htmlspecialchars($delivery->description); ?></textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="weight_kg" class="form-label">Poids (kg) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="weight_kg" name="weight_kg" value="<?php echo htmlspecialchars($delivery->weight_kg); ?>" required>
                        <span class="input-group-text rounded-end-lg">kg</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="declared_value" class="form-label">Valeur déclarée (DT) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="declared_value" name="declared_value" value="<?php echo htmlspecialchars($delivery->declared_value); ?>" required>
                        <span class="input-group-text rounded-end-lg">DT</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <label for="length_cm" class="form-label">Longueur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="length_cm" name="length_cm" value="<?php echo htmlspecialchars($delivery->length_cm); ?>" required>
                        <span class="input-group-text rounded-end-lg">cm</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="L_width_cm" class="form-label">Largeur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="L_width_cm" name="L_width_cm" value="<?php echo htmlspecialchars($delivery->L_width_cm); ?>" required>
                        <span class="input-group-text rounded-end-lg">cm</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="height_cm" class="form-label">Hauteur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="height_cm" name="height_cm" value="<?php echo htmlspecialchars($delivery->height_cm); ?>" required>
                        <span class="input-group-text rounded-end-lg">cm</span>
                    </div>
                </div>
            </div>

                        <!-- NOUVEAU: Champs pour Fragile et Accepts Exchange -->
            <div class="row g-3 mt-4">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch fs-5 d-flex align-items-center">
                        <input class="form-check-input me-2" type="checkbox" id="is_fragile" name="is_fragile" value="1">
                        <label class="form-check-label pt-1" for="is_fragile">Colis fragile</label>
                    </div>
                    <div class="form-text">Cochez si le colis nécessite une manipulation délicate.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch fs-5 d-flex align-items-center">
                        <input class="form-check-input me-2" type="checkbox" id="accepts_exchange" name="accepts_exchange" value="1">
                        <label class="form-check-label pt-1" for="accepts_exchange">Accepte l'échange</label>
                    </div>
                    <div class="form-text">Cochez si le colis peut être échangé contre un autre lors de la livraison.</div>
                </div>
            </div>
            <!-- FIN NOUVEAU: Champs pour Fragile et Accepts Exchange -->

            <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Informations du Destinataire</h4>
            <div class="mb-3">
                <label for="recipient_name" class="form-label">Nom du Destinataire / Entité <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-lg" id="recipient_name" name="recipient_name" value="<?php echo htmlspecialchars($delivery->recipient_name); ?>" required>
            </div>
            <div class="mb-3">
                <label for="recipient_phone" class="form-label">Téléphone du Destinataire <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-lg" id="recipient_phone" name="recipient_phone" value="<?php echo htmlspecialchars($delivery->recipient_phone); ?>" required>
            </div>
            <div class="mb-3">
                <label for="recipient_city" class="form-label">Ville / Région du Destinataire <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="recipient_city" name="recipient_city" required>
                    <option value="">-- Sélectionner une région --</option>
                    <?php foreach ($tunisianRegions as $region): ?>
                        <option value="<?php echo htmlspecialchars($region); ?>" <?php echo ($delivery->recipient_city === $region) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($region); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="destination_address" class="form-label">Adresse Complète du Destinataire <span class="text-danger">*</span></label>
                <textarea class="form-control rounded-lg" id="destination_address" name="destination_address" rows="3" required><?php echo htmlspecialchars($delivery->destination_address); ?></textarea>
            </div>

            <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Statut et Assignation</h4>
            <div class="mb-3">
                <label for="status" class="form-label">Statut du colis <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="status" name="status" required onchange="toggleReturnReasonField()">
                    <?php foreach ($deliveryStatuses as $key => $info): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($delivery->status === $key) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($info['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Motif de retour (affiché si le statut est 'Retour') -->
            <div class="mb-3" id="returnReasonField" style="display: <?php echo ($delivery->status === 'returned') ? 'block' : 'none'; ?>;">
                <label for="return_reason" class="form-label">Motif de retour <span class="text-danger">*</span></label>
                <textarea class="form-control rounded-lg" id="return_reason" name="return_reason" rows="2"><?php echo htmlspecialchars($delivery->return_reason ?? ''); ?></textarea>
                <div class="form-text">Ce champ est requis si le statut est 'Retour'.</div>
            </div>


            <div class="mb-3">
                <label for="driver_id" class="form-label">Livreur Assigné</label>
                <select class="form-select rounded-lg" id="driver_id" name="driver_id">
                    <option value="">-- Non assigné --</option>
                    <?php if (!empty($drivers)): ?>
                        <?php foreach ($drivers as $driver):
                            $driverId = htmlspecialchars($driver['id'] ?? $driver->id ?? '');
                            $driverUsername = htmlspecialchars($driver['username'] ?? $driver->username ?? '');
                            $driverFirstName = htmlspecialchars($driver['first_name'] ?? $driver->first_name ?? '');
                            $driverLastName = htmlspecialchars($driver['last_name'] ?? $driver->last_name ?? '');
                        ?>
                            <option value="<?php echo $driverId; ?>" <?php echo ($delivery->driver_id == $driverId) ? 'selected' : ''; ?>>
                                <?php echo $driverUsername; ?> (<?php echo trim($driverFirstName . ' ' . $driverLastName); ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="price" class="form-label">Prix de la livraison (DT) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control rounded-start-lg" id="price" name="price" value="<?php echo htmlspecialchars($delivery->price); ?>" required>
                    <span class="input-group-text rounded-end-lg">DT</span>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm hover-shadow-lg">
                    <i class="fas fa-save me-2"></i> Mettre à jour la livraison
                </button>
                <a href="/sabroom/public/admin/deliveries" class="btn btn-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
    /* Styles généraux pour les cartes et boutons (cohérence avec les autres pages) */
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        background-color: #ffffff;
        border-radius: 0.75rem; /* Coins arrondis */
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
    /* Correction spécifique pour les coins des input-group */
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
    /* Styles pour l'en-tête de la carte de formulaire */
    .card-header {
        background-color:rgb(255, 102, 0) !important; /* Bleu primaire */
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
    /* Styles pour les titres de section à l'intérieur du formulaire */
    h4.border-bottom {
        font-size: 1.25rem;
        font-weight: 600;
        color: #333;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem !important; /* Marge en bas pour les titres de section */
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const returnReasonField = document.getElementById('returnReasonField');
    const returnReasonInput = document.getElementById('return_reason'); // Le champ textarea pour le motif

    function toggleReturnReasonField() {
        if (statusSelect.value === 'returned') {
            returnReasonField.style.display = 'block';
            returnReasonInput.setAttribute('required', 'required'); // Rendre requis si le statut est "returned"
        } else {
            returnReasonField.style.display = 'none';
            returnReasonInput.value = ''; // Vider le champ si le statut change
            returnReasonInput.removeAttribute('required'); // Retirer l'attribut required
        }
    }

    // Écouter les changements sur le sélecteur de statut
    statusSelect.addEventListener('change', toggleReturnReasonField);

    // Appeler la fonction une fois au chargement de la page pour gérer l'état initial
    toggleReturnReasonField();
});
</script>
