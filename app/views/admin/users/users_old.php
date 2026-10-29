<?php
// Fichier: app/views/admin/users/users.php

// Cette vue affiche la liste de tous les utilisateurs du système, séparés par rôle.
// Elle reçoit les variables $adminUsers, $supplierUsers, $driverUsers (tableaux associatifs) du contrôleur.

$userRoles = [
    'admin' => ['label' => 'Administrateur', 'color' => 'bg-danger'],
    'supplier' => ['label' => 'Fournisseur', 'color' => 'bg-primary'],
    'driver' => ['label' => 'Livreur', 'color' => 'bg-info']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Gestion des Utilisateurs</h1>
    <a href="/sabroom/public/admin/users/create" class="btn btn-primary rounded-pill shadow-sm hover-shadow-lg">
        <i class="fas fa-plus-circle me-2"></i> Ajouter un utilisateur
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
    <div class="card-body p-4">
        <ul class="nav nav-tabs mb-4" id="userRoleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">Tous les Utilisateurs</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab" aria-controls="admins" aria-selected="false">Administrateurs</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab" aria-controls="suppliers" aria-selected="false">Fournisseurs</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers" type="button" role="tab" aria-controls="drivers" aria-selected="false">Livreurs</button>
            </li>
        </ul>

        <div class="tab-content" id="userRoleTabsContent">
            <!-- Tab Tous les Utilisateurs -->
            <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nom d'utilisateur</th>
                                <th scope="col">Email</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fusionner tous les utilisateurs pour la vue "Tous les Utilisateurs"
                            $allUsers = array_merge($adminUsers, $supplierUsers, $driverUsers);
                            if (count($allUsers) > 0):
                                foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td data-label="Nom d'utilisateur"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td data-label="Rôle">
                                            <span class="badge bg-<?php
                                                if ($user['role'] == 'admin') echo 'danger';
                                                elseif ($user['role'] == 'supplier') echo 'primary';
                                                elseif ($user['role'] == 'driver') echo 'info';
                                                else echo 'secondary';
                                            ?> p-2 fs-6 rounded-pill"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                                        </td>
                                        <td data-label="Statut">
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success p-2 fs-6 rounded-pill">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark p-2 fs-6 rounded-pill">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" class="text-center">
                                            <a href="/sabroom/public/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/sabroom/public/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center no-records p-4">
                                        <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                                        <h4 class="alert-heading text-muted">Aucun utilisateur trouvé.</h4>
                                        <p class="text-muted">Créez de nouveaux utilisateurs pour les voir apparaître ici.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Administrateurs -->
            <div class="tab-pane fade" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nom d'utilisateur</th>
                                <th scope="col">Email</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($adminUsers) > 0):
                                foreach ($adminUsers as $user): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td data-label="Nom d'utilisateur"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td data-label="Statut">
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success p-2 fs-6 rounded-pill">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark p-2 fs-6 rounded-pill">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" class="text-center">
                                            <a href="/sabroom/public/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/sabroom/public/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center no-records p-4">
                                        <i class="fas fa-user-cog fa-3x mb-3 text-muted"></i>
                                        <h4 class="alert-heading text-muted">Aucun administrateur trouvé.</h4>
                                        <p class="text-muted">Vérifiez vos données ou ajoutez de nouveaux administrateurs.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Fournisseurs -->
            <div class="tab-pane fade" id="suppliers" role="tabpanel" aria-labelledby="suppliers-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nom d'utilisateur</th>
                                <th scope="col">Entreprise</th>
                                <th scope="col">Matricule Fiscale</th>
                                <th scope="col">Email</th>
                                <th scope="col">Téléphone</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($supplierUsers) > 0):
                                foreach ($supplierUsers as $user): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td data-label="Nom d'utilisateur"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td data-label="Entreprise"><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></td>
                                        <td data-label="Matricule Fiscale"><?php echo htmlspecialchars($user['siret_number'] ?? 'N/A'); ?></td>
                                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td data-label="Téléphone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td data-label="Statut">
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success p-2 fs-6 rounded-pill">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark p-2 fs-6 rounded-pill">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" class="text-center">
                                            <a href="/sabroom/public/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/sabroom/public/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center no-records p-4">
                                        <i class="fas fa-store-alt fa-3x mb-3 text-muted"></i>
                                        <h4 class="alert-heading text-muted">Aucun fournisseur trouvé.</h4>
                                        <p class="text-muted">Vérifiez vos données ou ajoutez de nouveaux fournisseurs.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Livreurs -->
            <div class="tab-pane fade" id="drivers" role="tabpanel" aria-labelledby="drivers-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nom d'utilisateur</th>
                                <th scope="col">Prénom</th>
                                <th scope="col">Nom</th>
                                <th scope="col">Email</th>
                                <th scope="col">Téléphone</th>
                                <th scope="col">Motorisé</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($driverUsers) > 0):
                                foreach ($driverUsers as $user): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td data-label="Nom d'utilisateur"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td data-label="Prénom"><?php echo htmlspecialchars($user['first_name'] ?? 'N/A'); ?></td>
                                        <td data-label="Nom"><?php echo htmlspecialchars($user['last_name'] ?? 'N/A'); ?></td>
                                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td data-label="Téléphone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td data-label="Motorisé">
                                            <?php if ($user['motorized']): ?>
                                                <span class="badge bg-success p-2 fs-6 rounded-pill">Oui</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary p-2 fs-6 rounded-pill">Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Statut">
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success p-2 fs-6 rounded-pill">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark p-2 fs-6 rounded-pill">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" class="text-center">
                                            <a href="/sabroom/public/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/sabroom/public/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="9" class="text-center no-records p-4">
                                        <i class="fas fa-truck fa-3x mb-3 text-muted"></i>
                                        <h4 class="alert-heading text-muted">Aucun livreur trouvé.</h4>
                                        <p class="text-muted">Vérifiez vos données ou ajoutez de nouveaux livreurs.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles généraux pour les cartes */
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

    /* Styles spécifiques pour le tableau */
    .table-responsive {
        border-radius: 0.75rem; /* Coins arrondis pour le conteneur du tableau */
        overflow: hidden; /* Cache les coins du tableau */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Ombre légère pour le tableau */
    }
    /* Par défaut, le tableau est un tableau normal */
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
        white-space: nowrap; /* Empêche le texte de se couper sur les grandes largeurs */
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
    }
    /* Couleurs des icônes dans le dropdown */
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    .text-primary { color: #5c5c5c !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-secondary { color: #6c757d !important; }

    /* Styles pour les onglets (nav-tabs) */
    .nav-tabs .nav-link {
        color: #007bff;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 600;
        padding: 15px 20px;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        border-color: #a0c9f1; /* Couleur de survol plus claire */
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        background-color: #fff;
        border-color: #007bff;
        border-radius: 0;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }

    /* MEDIA QUERIES POUR LA RÉACTIVITÉ AVANCÉE (tableau se transforme en cartes) */
    /* Appliquer ces styles pour les écrans plus petits que 992px (lg breakpoint de Bootstrap) */
    @media (max-width: 991.98px) {
        .table-responsive {
            overflow-x: hidden; /* Désactive le défilement horizontal du table-responsive */
        }
        .responsive-table {
            border: 0; /* Supprime les bordures du tableau */
            min-width: unset; /* Annule la min-width pour permettre la compression */
        }
        .responsive-table thead {
            display: none; /* Cache l'en-tête du tableau */
        }
        .responsive-table tbody,
        .responsive-table tr,
        .responsive-table td {
            display: block; /* Fait des éléments de tableau des blocs */
            width: 100%; /* Chaque cellule/ligne prend toute la largeur */
        }
        .responsive-table tr {
            margin-bottom: 1rem; /* Espace entre les "cartes" */
            border: 1px solid #e9ecef;
            border-radius: 0.75rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Ombre pour chaque carte */
            padding: 1rem;
            background-color: #ffffff;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .responsive-table tr:hover {
            transform: translateY(-3px); /* Effet de soulèvement au survol */
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
        }
        .responsive-table td {
            border: none; /* Supprime les bordures des cellules */
            position: relative;
            padding-left: 50% !important; /* Espace pour le libellé */
            text-align: right; /* Aligne le contenu à droite */
            white-space: normal; /* Permet au texte de revenir à la ligne */
            min-height: 2.2em; /* Hauteur minimale pour les cellules */
            display: flex; /* Utilise flexbox pour aligner le libellé et la valeur */
            align-items: center; /* Centre verticalement le contenu */
            justify-content: flex-end; /* Justifie le contenu à droite */
        }
        .responsive-table td::before {
            content: attr(data-label); /* Utilise l'attribut data-label comme pseudo-élément */
            position: absolute;
            left: 0.75rem; /* Positionne le libellé à gauche */
            width: 45%; /* Largeur du libellé */
            padding-right: 10px;
            white-space: nowrap; /* Empêche le libellé de se couper */
            text-align: left; /* Aligne le libellé à gauche */
            font-weight: 600;
            color: #555;
            box-sizing: border-box; /* Inclure padding dans la largeur */
        }
        .responsive-table td:first-child {
            padding-top: 0.5rem !important;
        }
        .responsive-table td:last-child { /* Style spécifique pour la colonne Actions */
            text-align: center; /* Centre les boutons d'action */
            padding-left: 0 !important;
            margin-top: 1rem;
            border-top: 1px solid #e9ecef;
            padding-top: 1rem;
            justify-content: center; /* Centre les éléments à l'intérieur */
        }
        .responsive-table td:last-child::before {
            content: none; /* Cache le libellé "Actions" */
        }
        .responsive-table .badge {
            font-size: 0.9em; /* Légèrement plus grand sur mobile pour les badges */
        }
    }
    /* Ajustements pour les très petits écrans (sm breakpoint de Bootstrap) */
    @media (max-width: 575.98px) {
        .responsive-table td {
            padding-left: 45% !important; /* Ajuste le padding pour les très petits écrans */
        }
        .responsive-table td::before {
            width: 40%;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script pour la persistance de l'onglet actif après le rechargement de la page
    const deliveryTabs = document.getElementById('deliveryTabs');
    if (deliveryTabs) {
        // Au chargement de la page, activer l'onglet basé sur le paramètre 'tab' de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTabId = urlParams.get('tab');

        if (activeTabId) {
            const tabButton = document.getElementById(activeTabId);
            if (tabButton) {
                const bsTab = new bootstrap.Tab(tabButton);
                bsTab.show();
            }
        }

        // Mettre à jour le champ caché 'tab' du formulaire de filtre lors du changement d'onglet
        deliveryTabs.addEventListener('shown.bs.tab', function (event) {
            const newActiveTabId = event.target.id; // ID du bouton de l'onglet activé (ex: 'pending-tab')
            const activeTabInput = document.getElementById('activeTabInput');
            if (activeTabInput) {
                activeTabInput.value = newActiveTabId;
            }
        });
    }
});
</script>
