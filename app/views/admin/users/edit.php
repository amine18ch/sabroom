<?php
// Fichier: app/views/admin/users/edit.php - FORMULAIRE D'ÉDITION D'UTILISATEUR

// Cette vue affiche le formulaire pour éditer un utilisateur existant.
// Elle reçoit la variable $user (un objet User) du contrôleur.
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Modifier l'utilisateur : <?php echo htmlspecialchars($user->username); ?></h1>
    <a href="/sabroom/public/admin/users" class="btn btn-secondary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-arrow-left me-2"></i> Retour à la liste
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
        <h3 class="card-title mb-0"><i class="fas fa-user-edit me-2"></i> Modifier le Compte Utilisateur</h3>
    </div>
    <div class="card-body p-4">
        <form action="/sabroom/public/admin/users/update/<?php echo htmlspecialchars($user->id); ?>" method="POST">

            <h4 class="mb-3 text-dark border-bottom pb-2">Informations du Compte</h4>
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-lg"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control rounded-end-lg" id="username" name="username" value="<?php echo htmlspecialchars($user->username); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-lg"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control rounded-end-lg" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nouveau Mot de passe (laisser vide pour ne pas changer)</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-lg"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control rounded-end-lg" id="password" name="password" placeholder="********">
                </div>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="role" name="role" required onchange="toggleRoleSpecificFields()">
                    <option value="admin" <?php echo ($user->role === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                    <option value="supplier" <?php echo ($user->role === 'supplier') ? 'selected' : ''; ?>>Fournisseur</option>
                    <option value="driver" <?php echo ($user->role === 'driver') ? 'selected' : ''; ?>>Livreur</option>
                </select>
            </div>

            <!-- Champs spécifiques au rôle Fournisseur -->
            <div id="supplierFields" style="display: none;">
                <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Informations Fournisseur</h4>
                <div class="mb-3">
                    <label for="company_name" class="form-label">Nom de l'entreprise</label>
                    <input type="text" class="form-control rounded-lg" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user->company_name ?? ''); ?>" placeholder="Ex: Mon Entreprise SARL">
                </div>
                <div class="mb-3">
                    <label for="siret_number" class="form-label">Matricule Fiscale / Numéro SIRET</label>
                    <input type="text" class="form-control rounded-lg" id="siret_number" name="siret_number" value="<?php echo htmlspecialchars($user->siret_number ?? ''); ?>" placeholder="Ex: 1234567890">
                </div>
            </div>

            <!-- Champs spécifiques au rôle Livreur -->
            <div id="driverFields" style="display: none;">
                <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Informations Livreur</h4>
                <div class="mb-3">
                    <label for="first_name" class="form-label">Prénom</label>
                    <input type="text" class="form-control rounded-lg" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->first_name ?? ''); ?>" placeholder="Ex: Jean">
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Nom</label>
                    <input type="text" class="form-control rounded-lg" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->last_name ?? ''); ?>" placeholder="Ex: Dupont">
                </div>
                <div class="mb-3">
                    <label for="id_card_number" class="form-label">Numéro C.I.N</label>
                    <input type="text" class="form-control rounded-lg" id="id_card_number" name="id_card_number" value="<?php echo htmlspecialchars($user->id_card_number ?? ''); ?>" placeholder="Ex: 01234567">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="motorized" name="motorized" value="1" <?php echo ($user->motorized) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="motorized">Motorisé (possède un véhicule)</label>
                </div>
            </div>

            <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Informations de Contact (Optionnel)</h4>
            <div class="mb-3">
                <label for="phone" class="form-label">Téléphone</label>
                <input type="text" class="form-control rounded-lg" id="phone" name="phone" value="<?php echo htmlspecialchars($user->phone ?? ''); ?>" placeholder="Ex: +216 12 345 678">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Adresse</label>
                <textarea class="form-control rounded-lg" id="address" name="address" rows="3" placeholder="Adresse complète..."><?php echo htmlspecialchars($user->address ?? ''); ?></textarea>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo ($user->is_active) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_active">Compte actif (désactiver pour suspendre)</label>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm hover-shadow-lg">
                    <i class="fas fa-save me-2"></i> Mettre à jour l'utilisateur
                </button>
                <a href="/sabroom/public/admin/users" class="btn btn-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">Annuler</a>
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
        border-radius: 0.75rem !important; /* Coins arrondis pour inputs, selects et input-group-text */
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
        background-color: #ff6600 !important; /* Bleu primaire */
        color: white;
        font-weight: 700;
        border-bottom: none;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        border-top-left-radius: calc(0.75rem - 1px) !important; /* Arrondir le haut de la carte */
        border-top-right-radius: calc(0.75rem - 1px) !important; /* Arrondir le haut de la carte */
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
    const roleSelect = document.getElementById('role');
    const supplierFields = document.getElementById('supplierFields');
    const driverFields = document.getElementById('driverFields');
    const isActiveCheckbox = document.getElementById('is_active'); // Le checkbox 'Compte actif'

    function toggleRoleFields() {
        const selectedRole = roleSelect.value;

        // Cacher tous les champs spécifiques par défaut et les vider
        supplierFields.style.display = 'none';
        supplierFields.querySelectorAll('input, textarea').forEach(input => {
            input.value = ''; // Vider le champ
            input.removeAttribute('required'); // S'assurer que le champ n'est pas requis si caché
        });
        
        driverFields.style.display = 'none';
        driverFields.querySelectorAll('input, textarea').forEach(input => {
            input.value = ''; // Vider le champ
            input.removeAttribute('required'); // S'assurer que le champ n'est pas requis si caché
        });
        driverFields.querySelector('#motorized').checked = false; // Réinitialiser le checkbox

        // Afficher les champs pertinents
        if (selectedRole === 'supplier') {
            supplierFields.style.display = 'block';
            // Optionnel: rendre certains champs requis si nécessaire pour ce rôle
            // supplierFields.querySelector('#company_name').setAttribute('required', 'required');
        } else if (selectedRole === 'driver') {
            driverFields.style.display = 'block';
            // Optionnel: rendre certains champs requis si nécessaire pour ce rôle
            // driverFields.querySelector('#first_name').setAttribute('required', 'required');
            // driverFields.querySelector('#last_name').setAttribute('required', 'required');
        }
    }

    // Écouter les changements sur le sélecteur de rôle
    roleSelect.addEventListener('change', toggleRoleFields);

    // Appeler la function une fois au chargement de la page pour gérer l'état initial
    // et pour les cas où la page est rechargée avec une valeur de rôle déjà sélectionnée
    toggleRoleFields();
});
</script>
