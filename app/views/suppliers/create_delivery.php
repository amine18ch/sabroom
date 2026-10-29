<?php
// Fichier: app/views/suppliers/create_delivery.php - FORMULAIRE DE CRÉATION DE LIVRAISON

// Ce formulaire permet au fournisseur de créer de nouvelles livraisons.
// Il ne reçoit pas de variables spécifiques du contrôleur, mais peut afficher des messages flash.

// Liste des régions/gouvernorats de Tunisie pour le menu déroulant
$tunisianRegions = [
    "Ariana", "Béja", "Ben Arous", "Bizerte", "Gabès", "Gafsa", "Jendouba",
    "Kairouan", "Kasserine", "Kébili", "Kef", "Mahdia", "Manouba", "Médenine",
    "Monastir", "Nabeul", "Sfax", "Sidi Bouzid", "Siliana", "Sousse", "Tataouine",
    "Tozeur", "Tunis", "Zaghouan"
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Créer une Nouvelle Livraison</h1>
    <a href="/sabroom/public/supplier/deliveries" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour à mes livraisons
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
        <h3 class="card-title mb-0"><i class="fas fa-box-open me-2"></i> Détails de la Nouvelle Livraison</h3>
    </div>
    <div class="card-body p-4">
        <form action="/sabroom/public/supplier/deliveries/create" method="POST">

            <h4 class="mb-3 text-dark border-bottom pb-2">Informations du Colis</h4>
            <div class="mb-3">
                <label for="description" class="form-label">Description du colis <span class="text-danger">*</span></label>
                <textarea class="form-control rounded-lg" id="description" name="description" rows="3" required></textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="weight_kg" class="form-label">Poids (kg) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="weight_kg" name="weight_kg" required>
                        <span class="input-group-text rounded-end-lg">kg</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="declared_value" class="form-label">Valeur déclarée (DT) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="declared_value" name="declared_value" required>
                        <span class="input-group-text rounded-end-lg">DT</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <label for="length_cm" class="form-label">Longueur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="length_cm" name="length_cm" required>
                        <span class="input-group-text rounded-end-lg">cm</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="width_cm" class="form-label">Largeur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="width_cm" name="width_cm" required>
                        <span class="input-group-text rounded-end-lg">cm</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="height_cm" class="form-label">Hauteur (cm) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control rounded-start-lg" id="height_cm" name="height_cm" required>
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
                <input type="text" class="form-control rounded-lg" id="recipient_name" name="recipient_name" required>
            </div>
            <div class="mb-3">
                <label for="recipient_phone" class="form-label">Téléphone du Destinataire <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-lg" id="recipient_phone" name="recipient_phone" required>
            </div>
            <div class="mb-3">
                <label for="recipient_city" class="form-label">Ville / Région du Destinataire <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="recipient_city" name="recipient_city" required>
                    <option value="">-- Sélectionner une région --</option>
                    <?php foreach ($tunisianRegions as $region): ?>
                        <option value="<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="destination_address" class="form-label">Adresse Complète du Destinataire <span class="text-danger">*</span></label>
                <textarea class="form-control rounded-lg" id="destination_address" name="destination_address" rows="3" required></textarea>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm hover-shadow-lg">
                    <i class="fas fa-plus-circle me-2"></i> Créer la livraison
                </button>
                <a href="/sabroom/public/supplier/deliveries" class="btn btn-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">Annuler</a>
            </div>
        </form>
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
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .form-label {
        font-weight: 600;
        color: #555;
    }
    .card-header {
        background-color: #ff6600 !important;
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
    h4.border-bottom {
        font-size: 1.25rem;
        font-weight: 600;
        color: #333;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem !important;
    }
    /* Styles pour les switch (checkboxes custom) */
    .form-check.form-switch.fs-5 {
        font-size: 1.25rem; /* Rendre le switch plus grand */
    }
    .form-check-input {
        height: 1.5em; /* Ajuster la hauteur de l'input */
        width: 3em; /* Ajuster la largeur de l'input */
    }
    .form-check-label {
        padding-top: .15em; /* Aligner le texte avec le switch */
    }
</style>
