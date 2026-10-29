<?php
// Fichier: app/views/suppliers/bordereau.php

// Vue pour l'impression du bordereau d'expédition.
// Cette vue est chargée directement et ne doit PAS inclure le layout principal.
// Elle utilise les objets $this->deliveryModel et $supplierInfo passés par le contrôleur.

// Assurez-vous que les variables nécessaires sont disponibles
if (!isset($this->deliveryModel) || !isset($supplierInfo)) {
    // Redirection ou affichage d'une erreur si les données ne sont pas présentes
    // Dans un vrai environnement, une gestion d'erreur plus robuste serait nécessaire.
    echo "Erreur: Données de bordereau manquantes.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau d'Expédition - <?php echo htmlspecialchars($this->deliveryModel->unique_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .bordereau-container {
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            background-color: #fff;
            border: 1px solid #dee2e6;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
            box-sizing: border-box;
        }
        .header, .section-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color:rgb(3, 3, 3);
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 1.1em;
            color: #6c757d;
        }
        .section {
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fdfdfe;
        }
        .section h4 {
            color:rgb(0, 0, 0);
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
        }
        .section p {
            margin-bottom: 5px;
            font-size: 0.95em;
        }
        .section p strong {
            color: #343a40;
        }
        .barcode-area {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .barcode-area svg {
            width: 100%;
            max-width: 300px; /* Taille maximale du code-barres */
            height: 80px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.85em;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }

        /* Styles pour l'impression */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .bordereau-container {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 15mm; /* Marges d'impression */
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="bordereau-container">
        <div class="header">
            <h1>Bordereau d'Expédition</h1>
            <p>Sabrooom Delivery - Votre partenaire de livraison</p>
        </div>

        <div class="barcode-area">
            <svg id="barcode"></svg>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="section">
                    <h4>Informations du Fournisseur</h4>
                    <p><strong>Nom de l'entreprise:</strong> <?php echo htmlspecialchars($supplierInfo->company_name ?? 'N/A'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($supplierInfo->email ?? 'N/A'); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($supplierInfo->phone ?? 'N/A'); ?></p>
                    <p><strong>Adresse:</strong> <?php echo nl2br(htmlspecialchars($supplierInfo->address ?? 'N/A')); ?></p>
                    <p><strong>Matricule Fiscale:</strong> <?php echo htmlspecialchars($supplierInfo->siret_number ?? 'N/A'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section">
                    <h4>Informations du Destinataire</h4>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($this->deliveryModel->recipient_name); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($this->deliveryModel->recipient_phone); ?></p>
                    <p><strong>Ville:</strong> <?php echo htmlspecialchars($this->deliveryModel->recipient_city); ?></p>
                    <p><strong>Adresse:</strong> <?php echo nl2br(htmlspecialchars($this->deliveryModel->destination_address)); ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <h4>Détails de la Livraison</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($this->deliveryModel->description)); ?></p>
                    <p><strong>Prix Colis:</strong> <?php echo number_format($this->deliveryModel->declared_value, 2); ?> DT</p>
                    <!--<p><strong>Frais Livraison:</strong> <?php echo number_format($this->deliveryModel->price, 2); ?> DT</p> -->
                    <!--<p><strong>Total à Payer:</strong><b> <?php echo number_format($this->deliveryModel->price + $this->deliveryModel->declared_value, 2); ?> DT</b></p> -->
                    <p><strong>Colis Fragile:</strong> <?php echo ($this->deliveryModel->is_fragile ? 'Oui' : 'Non'); ?></p>

                </div>
                <div class="col-md-6">
                    <p><strong>Poids:</strong> <?php echo htmlspecialchars($this->deliveryModel->weight_kg); ?> kg</p>
                    <p><strong>Dimensions (L x l x H):</strong> <?php echo htmlspecialchars($this->deliveryModel->length_cm); ?> x <?php echo htmlspecialchars($this->deliveryModel->L_width_cm); ?> x <?php echo htmlspecialchars($this->deliveryModel->height_cm); ?> cm</p>
                    <p><strong>Date de création:</strong> <?php echo date('d/m/Y H:i', strtotime($this->deliveryModel->created_at)); ?></p>
                    <!-- NOUVEAU: Affichage de Fragile et Accepte l'échange -->
                    <p><strong>Accepte l'échange:</strong> <?php echo ($this->deliveryModel->accepts_exchange ? 'Oui' : 'Non'); ?></p>
                </div>
            </div>
        </div>

        
        <div class="footer">
            <p>Merci de votre confiance. Pour toute question, contactez le support Sabroom.</p>
        </div>

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Imprimer le Bordereau
            </button>
            <a href="/sabroom/public/supplier/deliveries" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i> Retour aux livraisons
            </a>
        </div>
    </div>

    <script>
        // Génération du code-barres après le chargement du DOM
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeValue = "<?php echo htmlspecialchars($this->deliveryModel->unique_id); ?>";
            if (barcodeValue) {
                JsBarcode("#barcode", barcodeValue, {
                    format: "CODE128", // Format standard pour les codes-barres
                    displayValue: true, // Affiche la valeur sous le code-barres
                    width: 2, // Largeur des barres
                    height: 60, // Hauteur du code-barres
                    margin: 0,
                    fontSize: 25 // Taille de la police pour la valeur affichée
                });
            }
        });
    </script>
</body>
</html>
