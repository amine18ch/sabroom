<?php
// Fichier: app/views/admin/deliveries/print_summary.php - BORDEREAU DE LIVRAISON

// Cette vue est une page autonome (sans layout) destinée à être imprimée.
// Elle reçoit les variables:
// $driver (objet User contenant les infos du livreur)
// $deliveries_to_print (tableau de Delivery objects ou tableaux associatifs des colis à imprimer)
// Ces variables sont passées par AdminController::printDeliverySummary().

// Assurez-vous que les variables sont définies et sont des tableaux/objets.
$driver = $driver ?? null;
$deliveries_to_print = $deliveries_to_print ?? [];

// Messages de statut pour l'affichage (cohérent avec les autres pages)
$deliveryStatuses = [
    'pending' => ['label' => 'En dépôt', 'color_class' => 'bg-warning', 'text_color' => 'text-white', 'icon' => 'fas fa-box'],
    'assigned' => ['label' => 'En cours', 'color_class' => 'bg-info', 'text_color' => 'text-white', 'icon' => 'fas fa-truck-loading'],
    'in_transit' => ['label' => 'En cours de livraison', 'color_class' => 'bg-primary', 'text_color' => 'text-white', 'icon' => 'fas fa-shipping-fast'],
    'delivered' => ['label' => 'Livrée', 'color_class' => 'bg-success', 'text_color' => 'text-white', 'icon' => 'fas fa-check-circle'],
    'returned' => ['label' => 'Retour', 'color_class' => 'bg-danger', 'text_color' => 'text-white', 'icon' => 'fas fa-undo-alt'],
    'cancelled' => ['label' => 'Annulée', 'color_class' => 'bg-secondary', 'text_color' => 'text-white', 'icon' => 'fas fa-ban']
];

$totalDeclaredValue = 0;
$totalDeliveryPrice = 0;
foreach ($deliveries_to_print as $delivery) {
    $totalDeclaredValue += $delivery['declared_value'] ?? 0;
    $totalDeliveryPrice += $delivery['price'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau de Livraison - <?php echo htmlspecialchars($driver->username ?? 'Livreur'); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- JsBarcode CDN pour la génération de code-barres -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
            color: #343a40;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 16px;
            line-height: 24px;
            color: #555;
            background-color: #fff;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        .invoice-box table td, .invoice-box table th {
            padding: 8px 10px;
            vertical-align: top;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 15px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box .total-row td {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .barcode-print-svg {
            max-width: 150px;
            height: 50px;
            display: block;
            margin: 5px auto;
        }
        .badge {
            font-weight: 600;
            padding: 0.4em 0.7em;
            font-size: 0.75em;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
        }
        /* Styles pour l'impression */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
            }
            .btn-print {
                display: none; /* Cache le bouton d'impression */
            }
        }
    </style>
</head>
<body>

    <div class="invoice-box">
        <div class="text-end mb-4">
            <button class="btn btn-primary btn-print rounded-pill" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Imprimer
            </button>
        </div>

        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="5"> <!-- COLSPAN AJUSTÉ POUR LES NOUVELLES COLONNES -->
                    <table>
                        <tr>
                            <td class="title">
                                <!-- Logo de votre entreprise -->
                                <img src="/sabroom/public/assets/img/sabrooom.png" style="width:100%; max-width:250px;">
                            </td>
                            <td>
                                <b>Feuille de Route</b><br>
                                Date: <?php echo date('d/m/Y'); ?><br>
                                Heure: <?php echo date('H:i:s'); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="5"> <!-- COLSPAN AJUSTÉ -->
                    <table>
                        <tr>
                            <td>
                                <!-- Informations sur l'Administrateur/Expéditeur (SABROOOM DELIVERY) -->
                                Expéditeur: <b>SABROOOM DELIVERY</b><br>
                                Adresse: 21 Av des Etat Unis 4éme Etage B1<br>
                                Contact: +216 29 472 585<br>
                                Email: contact@sabrooom-delivery.com
                            </td>
                            <td>
                                <!-- Informations sur le Livreur -->
                                <?php if ($driver): ?>
                                    Livreur:<br>
                                    <?php echo htmlspecialchars($driver->first_name ?? ''); ?> <?php echo htmlspecialchars($driver->last_name ?? ''); ?><br>
                                    
                                    
                                <?php else: ?>
                                    **Livreur non spécifié**
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>Colis</td>
                <td>Description</td>
                <td>Destinataire</td>
                <td>Prix Colis (DT)</td> <!-- Nouvelle colonne -->
                <!--<td>Frais Livraison (DT)</td>  Nouvelle colonne -->
                <!--<td>Total à payer (DT)</td>  Nouvelle colonne -->
                <td>Statut</td>
            </tr>

            <?php if (!empty($deliveries_to_print)): ?>
                <?php foreach ($deliveries_to_print as $index => $delivery):
                    $statusInfo = $deliveryStatuses[$delivery['status']] ?? ['label' => 'Inconnu', 'color_class' => 'bg-secondary', 'icon' => 'fas fa-question-circle'];
                ?>
                    <tr class="item <?php echo ($index == count($deliveries_to_print) - 1) ? 'last' : ''; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($delivery['unique_id']); ?></strong><br>
                            <svg class="barcode-print-svg" data-barcode-value="<?php echo htmlspecialchars($delivery['unique_id']); ?>"></svg>
                        </td>
                        <td><?php echo htmlspecialchars($delivery['description']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($delivery['recipient_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($delivery['recipient_phone']); ?></small><br>
                            <small class="text-muted"><?php echo htmlspecialchars($delivery['destination_address']); ?>, <?php echo htmlspecialchars($delivery['recipient_city']); ?></small>
                        </td>
                        <td><?php echo number_format($delivery['declared_value'] ?? 0, 2); ?></td> <!-- Valeur déclarée -->
                        <!--<td><?php echo number_format($delivery['price'] ?? 0, 2); ?></td>  Prix de livraison -->
                        <!--<td><?php echo number_format($delivery['price'] + $delivery['declared_value'] ?? 0, 2); ?></td>  Prix de livraison -->
                        <td>
                            <span class="badge <?php echo $statusInfo['color_class']; ?> <?php echo $statusInfo['text_color']; ?>">
                                <i class="<?php echo $statusInfo['icon']; ?> me-1"></i> <?php echo htmlspecialchars($statusInfo['label']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">Total des colis: <?php echo count($deliveries_to_print); ?></td>
                    <th>Total:</th>
                    <td colspan="2"><?php echo number_format($totalDeclaredValue, 2); ?> DT</td> <!-- Total valeur -->
                    <!--<th>Total Frais: </th> -->
                    <!-- <td><?php echo number_format($totalDeliveryPrice, 2); ?> DT</td>  Total frais -->
                    <td></td> <!-- Cellule vide pour le statut -->
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center p-4">Aucun colis à imprimer.</td> <!-- COLSPAN AJUSTÉ -->
                </tr>
            <?php endif; ?>
        </table>
        
        <div class="text-center mt-5">
            <p>Signature du livreur :</p>
            <div style="border-bottom: 1px dashed #333; width: 200px; height: 10px; margin: 0 auto 20px;"></div>
            
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Génération des codes-barres pour l'impression
            const barcodeSvgs = document.querySelectorAll('.barcode-print-svg');
            barcodeSvgs.forEach(svgElement => {
                const barcodeValue = svgElement.dataset.barcodeValue;
                if (barcodeValue) {
                    JsBarcode(svgElement, barcodeValue, {
                        format: "CODE128",
                        displayValue: true, // Ne pas afficher le texte sous le code-barres
                        width: 1.5,
                        height: 50,
                        margin: 0
                    });
                }
            });

            // Déclencher l'impression après un court délai pour que les codes-barres soient générés
            window.onload = function() {
                setTimeout(function() {
                    // window.print(); // Décommenter pour imprimer automatiquement
                }, 500); // 500ms de délai
            };
        });
    </script>
</body>
</html>
