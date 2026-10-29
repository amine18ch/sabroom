<?php
// Fichier: app/views/admin/users/create.php - FORMULAIRE DE CRÉATION D'UTILISATEUR

// Ce formulaire permet à l'administrateur de créer de nouveaux utilisateurs (admin, fournisseur, livreur).
// Il ne reçoit pas de variables spécifiques du contrôleur, mais peut afficher des messages flash.

// Liste des rôles disponibles (à adapter si nécessaire)
$roles = [
    'admin' => 'Administrateur',
    'supplier' => 'Fournisseur',
    'driver' => 'Livreur'
];

// Liste des régions/gouvernorats de Tunisie pour le menu déroulant (pour fournisseurs/livreurs)
$tunisianRegions = [
    "Ariana", "Béja", "Ben Arous", "Bizerte", "Gabès", "Gafsa", "Jendouba",
    "Kairouan", "Kasserine", "Kébili", "Kef", "Mahdia", "Manouba", "Médenine",
    "Monastir", "Nabeul", "Sfax", "Sidi Bouzid", "Siliana", "Sousse", "Tataouine",
    "Tozeur", "Tunis", "Zaghouan"
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Créer un Nouvel Utilisateur</h1>
    <a href="/sabroom/public/admin/users" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour aux utilisateurs
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
        <h3 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i> Informations du Nouvel Utilisateur</h3>
    </div>
    <div class="card-body p-4">
        <form id="createUserForm" action="/sabroom/public/admin/users/create" method="POST">

            <h4 class="mb-3 text-dark border-bottom pb-2">Détails du Compte</h4>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="username" name="username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control rounded-lg" id="email" name="email" required>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <input type="password" class="form-control rounded-lg" id="password" name="password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                    <select class="form-select rounded-lg" id="role" name="role" required>
                        <option value="">-- Sélectionner un rôle --</option>
                        <?php foreach ($roles as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Informations Spécifiques au Rôle</h4>
            <!-- Champs pour Fournisseur -->
            <div id="supplierFields" style="display: none;">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Nom de l'entreprise</label>
                    <input type="text" class="form-control rounded-lg" id="company_name" name="company_name">
                </div>
                <div class="mb-3">
                    <label for="siret_number" class="form-label">Matricule Fiscale</label>
                    <input type="text" class="form-control rounded-lg" id="siret_number" name="siret_number">
                </div>
            </div>

            <!-- Champs pour Livreur -->
            <div id="driverFields" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Prénom</label>
                        <input type="text" class="form-control rounded-lg" id="first_name" name="first_name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Nom</label>
                        <input type="text" class="form-control rounded-lg" id="last_name" name="last_name">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label for="id_card_number" class="form-label">Numéro Carte d'identité</label>
                        <input type="text" class="form-control rounded-lg" id="id_card_number" name="id_card_number">
                    </div>
                    <div class="col-md-6 mb-3 form-check d-flex align-items-center">
                        <div>
                            <input type="checkbox" class="form-check-input mt-0" id="motorized" name="motorized" value="1">
                            <label class="form-check-label ms-2" for="motorized">Motorisé</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Champs Communs à Fournisseur et Livreur (Adresse et Téléphone) -->
            <div id="commonOptionalFields" style="display: none;">
                <div class="mb-3">
                    <label for="address" class="form-label">Adresse</label>
                    <input type="text" class="form-control rounded-lg" id="address" name="address">
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="text" class="form-control rounded-lg" id="phone" name="phone">
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Actif</label>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm hover-shadow-lg">
                    <i class="fas fa-plus-circle me-2"></i> Créer l'utilisateur
                </button>
                <a href="/sabroom/public/admin/users" class="btn btn-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">Annuler</a>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const supplierFields = document.getElementById('supplierFields');
    const driverFields = document.getElementById('driverFields');
    const commonOptionalFields = document.getElementById('commonOptionalFields');
    const createUserForm = document.getElementById('createUserForm'); // Get the form element

    function toggleRoleSpecificFields() {
        // Cacher tous les champs spécifiques au rôle par défaut
        supplierFields.style.display = 'none';
        driverFields.style.display = 'none';
        commonOptionalFields.style.display = 'none';

        // Gérer l'attribut 'required' pour les champs spécifiques au rôle
        // Retirer 'required' de tous les champs spécifiques avant d'appliquer
        supplierFields.querySelectorAll('input, select, textarea').forEach(field => field.removeAttribute('required'));
        driverFields.querySelectorAll('input, select, textarea').forEach(field => field.removeAttribute('required'));
        commonOptionalFields.querySelectorAll('input, select, textarea').forEach(field => field.removeAttribute('required'));


        const selectedRole = roleSelect.value;

        if (selectedRole === 'supplier') {
            supplierFields.style.display = 'block';
            commonOptionalFields.style.display = 'block';
            // Rendre requis les champs spécifiques au fournisseur
            document.getElementById('company_name').setAttribute('required', 'required');
            document.getElementById('phone').setAttribute('required', 'required');
            document.getElementById('address').setAttribute('required', 'required');

        } else if (selectedRole === 'driver') {
            driverFields.style.display = 'block';
            commonOptionalFields.style.display = 'block';
            // Rendre requis les champs spécifiques au livreur
            document.getElementById('first_name').setAttribute('required', 'required');
            document.getElementById('last_name').setAttribute('required', 'required');
            document.getElementById('phone').setAttribute('required', 'required');
            document.getElementById('address').setAttribute('required', 'required');
        }
        // Admin n'a pas de champs spécifiques supplémentaires ici
    }

    // Écouter le changement de rôle
    roleSelect.addEventListener('change', toggleRoleSpecificFields);

    // Appeler la fonction au chargement initial de la page pour gérer l'état par défaut
    toggleRoleSpecificFields();

    // NOUVEAU : Débogage du mot de passe avant soumission du formulaire
    createUserForm.addEventListener('submit', function(event) {
        const passwordInput = document.getElementById('password');
        console.log("DEBUG: Mot de passe entré (avant soumission) :", passwordInput.value);
    });
});
</script>
