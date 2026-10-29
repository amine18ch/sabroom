<?php
// Fichier: app/views/admin/invoices/generate_form.php - FORMULAIRE DE GÉNÉRATION DE FACTURE (ADMIN)

// Cette vue affiche un formulaire pour générer une nouvelle facture.
// Elle reçoit la variable $suppliers (un tableau d'objets User ou tableaux associatifs) du contrôleur.

// Assurez-vous que $suppliers est bien un tableau
$suppliers = $suppliers ?? [];

// Valeurs par défaut pour les dates
$today = date('Y-m-d');
$firstDayOfPreviousMonth = date('Y-m-01', strtotime('last month'));
$lastDayOfPreviousMonth = date('Y-m-t', strtotime('last month'));
$dueDateDefault = date('Y-m-d', strtotime('+30 days'));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="text-primary mb-0">Générer une Nouvelle Facture</h1>
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

<!-- Le conteneur du formulaire prend toute la largeur disponible de la zone de contenu -->
<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-primary text-white rounded-top-lg py-3">
        <h3 class="card-title mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Créer une Facture</h3>
    </div>
    <div class="card-body p-4">
        <form action="/sabroom/public/admin/invoices/generate" method="POST">

            <h4 class="mb-3 text-dark border-bottom pb-2">Informations de la Facture</h4>
            
            <div class="mb-3">
                <label for="supplier_id" class="form-label">Fournisseur : <span class="text-danger">*</span></label>
                <select class="form-select rounded-lg" id="supplier_id" name="supplier_id" required>
                    <option value="">-- Sélectionner un fournisseur --</option>
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $supplier):
                            $supplierId = htmlspecialchars($supplier['id'] ?? $supplier->id ?? '');
                            $supplierUsername = htmlspecialchars($supplier['username'] ?? $supplier->username ?? '');
                            $supplierCompanyName = htmlspecialchars($supplier['company_name'] ?? $supplier->company_name ?? 'N/A');
                        ?>
                            <option value="<?php echo $supplierId; ?>">
                                <?php echo $supplierUsername; ?> (<?php echo $supplierCompanyName; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Aucun fournisseur disponible</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Période de début des livraisons : <span class="text-danger">*</span></label>
                    <input type="date" class="form-control rounded-lg" id="start_date" name="start_date" value="<?php echo $firstDayOfPreviousMonth; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date" class="form-label">Période de fin des livraisons : <span class="text-danger">*</span></label>
                    <input type="date" class="form-control rounded-lg" id="end_date" name="end_date" value="<?php echo $lastDayOfPreviousMonth; ?>" required>
                </div>
            </div>

            <h4 class="mt-5 mb-3 text-dark border-bottom pb-2">Dates de Facturation</h4>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="invoice_date" class="form-label">Date d'émission de la facture : <span class="text-danger">*</span></label>
                    <input type="date" class="form-control rounded-lg" id="invoice_date" name="invoice_date" value="<?php echo $today; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Date d'échéance : <span class="text-danger">*</span></label>
                    <input type="date" class="form-control rounded-lg" id="due_date" name="due_date" value="<?php echo $dueDateDefault; ?>" required>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm hover-shadow-lg">
                    <i class="fas fa-plus-circle me-2"></i> Générer Facture
                </button>
                <a href="/sabroom/public/admin/invoices" class="btn btn-secondary btn-lg rounded-pill shadow-sm hover-shadow-lg">Annuler</a>
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
