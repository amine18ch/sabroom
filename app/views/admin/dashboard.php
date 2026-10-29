<?php
// Fichier: app/views/admin/dashboard.php - TABLEAU DE BORD ADMINISTRATEUR

// Variables passées par le contrôleur (valeurs par défaut si non fournies)
$username = $username ?? ($_SESSION['username'] ?? 'Admin');
$total_deliveries = $total_deliveries ?? 0;
$total_users = $total_users ?? 0;
$total_suppliers = $total_suppliers ?? 0;
$total_revenue_delivered_period = $total_revenue_delivered_period ?? 0;
$delivered_count_period = $delivered_count_period ?? 0;
$returned_count_period = $returned_count_period ?? 0;
$cancelled_count_period = $cancelled_count_period ?? 0;
$pending_deliveries_count = $pending_deliveries_count ?? 0;
$assigned_deliveries_count = $assigned_deliveries_count ?? 0;
$in_transit_deliveries_count = $in_transit_deliveries_count ?? 0;

// Compatibilité avec certaines anciennes variables utilisées dans la version design
$inTransitDeliveriesCount = $inTransitDeliveriesCount ?? $in_transit_deliveries_count;
$delivered_deliveries_count = $delivered_deliveries_count ?? $delivered_count_period;
$cancelled_deliveries_count = $cancelled_deliveries_count ?? $cancelled_count_period;

$daily_revenue_data = $daily_revenue_data ?? [];
$delivery_status_counts_period = $delivery_status_counts_period ?? [];
$filter_start_date = $filter_start_date ?? date('Y-m-d');
$filter_end_date = $filter_end_date ?? date('Y-m-d');

// Préparer les données pour les graphiques
$chartLabels = array_keys($daily_revenue_data);
$chartValues = array_values($daily_revenue_data);

$pieLabels = [];
$pieValues = [];
$pieColors = [];
$statusColorsMap = [
    'pending' => '#f6c23e',
    'assigned' => '#36b9cc',
    'in_transit' => '#4e73df',
    'delivered' => '#1cc88a',
    'returned' => '#e74a3b',
    'cancelled' => '#858796'
];

foreach ($delivery_status_counts_period as $status => $count) {
    $pieLabels[] = ucfirst(str_replace('_', ' ', $status));
    $pieValues[] = $count;
    $pieColors[] = $statusColorsMap[$status] ?? '#cccccc';
}
?>

<!-- ===================== START DASHBOARD VIEW ===================== -->

<style>
  .card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.2s; }
  .card:hover { transform: translateY(-5px); }
  .card-title { font-weight: 600; font-size: 1rem; margin-bottom: .25rem; }
  .card-text { font-size: 1.4rem; font-weight: bold; color: #2c3e50; }
  .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; margin-right: 15px; }
  .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
  .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
  .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
  .bg-gradient-danger { background: linear-gradient(45deg, #e74a3b, #be2617); }
  .rounded-pill-small { border-radius: 50px; padding: .35rem .9rem; }
</style>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tableau de Bord</h1>
    <div class="d-flex align-items-center">
      <span class="me-2">👋 Bienvenue, <b><?php echo htmlspecialchars($username); ?></b></span>
    </div>
  </div>

  <!-- Messages flash -->
  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info'); ?> alert-dismissible fade show rounded-lg shadow-sm" role="alert">
      <?php echo htmlspecialchars($_SESSION['message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <!-- Filtres + bouton générer factures -->
  <div class="card shadow-sm mb-4 border-0 rounded-lg">
    <div class="card-header bg-white border-0 rounded-top-lg py-3 d-flex align-items-center justify-content-between">
      <h5 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filtres du Tableau de Bord</h5>
      <small class="text-muted">Affinez vos résultats</small>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <form action="/sabroom/public/admin/dashboard" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
              <label for="start_date" class="form-label fw-semibold">Date de début</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
              </div>
            </div>
            <div class="col-md-5">
              <label for="end_date" class="form-label fw-semibold">Date de fin</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
              </div>
            </div>
            <div class="col-md-2 d-grid">
              <button type="submit" class="btn btn-primary rounded-pill shadow-sm hover-shadow-lg"><i class="fas fa-check me-1"></i> Appliquer</button>
            </div>
          </form>
        </div>

        <div class="col-md-4 text-end align-self-center">
          <!-- Bouton: Générer les factures maintenant -->
          <form action="/sabroom/public/admin/generate_invoices.php" method="POST" onsubmit="return confirm('Générer les factures pour les livraisons livrées aujourd\'hui ?');">
            <button type="submit" class="btn btn-success rounded-pill shadow-sm hover-shadow-lg rounded-pill-small">
              <i class="fas fa-file-invoice-dollar me-1"></i> Générer les factures maintenant
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistiques principales -->
  <div class="row g-4 mb-5">
    <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=pending-tab" class="text-decoration-none text-dark">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="icon-circle bg-gradient-warning"><i class="fas fa-clock fs-4"></i></div>
            <div>
              <h5 class="card-title">Pickup</h5>
              <p class="card-text"><?php echo htmlspecialchars($pending_deliveries_count); ?></p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=assigned-tab" class="text-decoration-none text-dark">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="icon-circle bg-secondary"><i class="fas fa-warehouse fs-4"></i></div>
            <div>
              <h5 class="card-title">Dépôt</h5>
              <p class="card-text"><?php echo htmlspecialchars($assigned_deliveries_count); ?></p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=in_transit-tab" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="icon-circle bg-gradient-primary me-3"><i class="fas fa-shipping-fast fs-4 text-white"></i></div>
            <div>
              <h5 class="card-title mb-1">En livraison</h5>
              <p class="card-text fs-5 fw-bold"><?php echo htmlspecialchars($inTransitDeliveriesCount); ?></p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=delivered-tab" class="text-decoration-none text-dark">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="icon-circle bg-gradient-success"><i class="fas fa-check-circle fs-4"></i></div>
            <div>
              <h5 class="card-title">Colis Livrés</h5>
              <p class="card-text"><?php echo htmlspecialchars($delivered_deliveries_count); ?></p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <!-- Row 2 small cards -->
    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-info"><i class="fas fa-truck fs-4"></i></div>
          <div>
            <h5 class="card-title">En Transit</h5>
            <p class="card-text"><?php echo htmlspecialchars($in_transit_deliveries_count); ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=returned-tab" class="text-decoration-none text-dark">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="icon-circle bg-gradient-danger"><i class="fas fa-undo-alt fs-4"></i></div>
            <div>
              <h5 class="card-title">Reporter</h5>
              <p class="card-text"><?php echo htmlspecialchars($returned_count_period); ?></p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-primary"><i class="fas fa-clipboard-check fs-4"></i></div>
          <div>
            <h5 class="card-title">Vérification</h5>
            <p class="card-text"><?php echo htmlspecialchars('0'); ?></p>
          </div>
        </div>
      </div>
    </div>
	
			<div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-success">
            <i class="fas fa-hand-holding-usd fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Colis Payé</h5>
            <p class="card-text"><?php echo htmlspecialchars('0'); ?></p>
          </div>
        </div>
      </div>
    </div>
	
	    <div class="col-lg-3 col-md-6">
	<a href="/sabroom/public/admin/deliveries?tab=cancelled-tab" 
		 class="text-decoration-none text-dark">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-gradient-danger">
            <i class="fas fa-times-circle fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Retour Fournisseurs</h5>
            <p class="card-text"><?php echo htmlspecialchars($cancelled_deliveries_count); ?></p>
          </div>
        </div>
      </div>
	  </a>
    </div>
	
		<div class="col-lg-3 col-md-6">
	</div>
	<div class="col-lg-3 col-md-6">
	</div>

    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-success me-3"><i class="fas fa-dollar-sign"></i></div>
          <div>
            <h5 class="card-title">Revenus</h5>
            <p class="card-text"><?php echo number_format($total_revenue_delivered_period, 2); ?> DT</p>
          </div>
        </div>
      </div>
    </div>

  </div> <!-- end stats row -->

  <!-- Graphiques -->


</div> <!-- container-fluid -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartValues = <?php echo json_encode($chartValues); ?>;
    const pieLabels = <?php echo json_encode($pieLabels); ?>;
    const pieValues = <?php echo json_encode($pieValues); ?>;
    const pieColors = <?php echo json_encode($pieColors); ?>;

    // Graphique des Revenus (ligne)
    var ctxDailyRevenue = document.getElementById("dailyRevenueChart");
    if (ctxDailyRevenue) {
        new Chart(ctxDailyRevenue, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: "Revenu Total (DT)",
                    data: chartValues,
                    lineTension: 0.3,
                    backgroundColor: "rgba(0,123,255,0.05)",
                    borderColor: "rgba(0,123,255,1)",
                    pointRadius: 3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Graphique répartition (donut)
    var ctxDeliveryStatus = document.getElementById("deliveryStatusPieChart");
    if (ctxDeliveryStatus) {
        new Chart(ctxDeliveryStatus, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{ data: pieValues, backgroundColor: pieColors }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutoutPercentage: 80 }
        });
    }
});
</script>

<!-- ===================== END DASHBOARD VIEW ===================== -->
