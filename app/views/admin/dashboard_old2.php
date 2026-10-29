<style>
  .card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    transition: transform 0.2s;
  }
  .card:hover {
    transform: translateY(-5px);
  }
  .card-title {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: .25rem;
  }
  .card-text {
    font-size: 1.4rem;
    font-weight: bold;
    color: #2c3e50;
  }
  .icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    margin-right: 15px;
  }
  .bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe);
  }
  .bg-gradient-success {
    background: linear-gradient(45deg, #1cc88a, #13855c);
  }
  .bg-gradient-warning {
    background: linear-gradient(45deg, #f6c23e, #dda20a);
  }
  .bg-gradient-danger {
    background: linear-gradient(45deg, #e74a3b, #be2617);
  }
</style>

<div class="container-fluid py-4">

  <!-- Header du dashboard -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tableau de Bord</h1>
    <div class="d-flex align-items-center">
      <span class="me-2">👋 Bienvenue, <b>Admin</b></span>
      
    </div>
  </div>

<!-- Filtre par date -->
<div class="card shadow-sm mb-4 border-0 rounded-lg">
  <div class="card-header bg-white border-0 rounded-top-lg py-3 d-flex align-items-center justify-content-between">
    <h5 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filtres du Tableau de Bord</h5>
    <small class="text-muted">Affinez vos résultats</small>
  </div>
  <div class="card-body">
    <form action="/sabroom/public/admin/dashboard" method="GET" class="row g-3 align-items-end">
      
      <!-- Date de début -->
      <div class="col-md-4">
        <label for="start_date" class="form-label fw-semibold">Date de début</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
          <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
        </div>
      </div>

      <!-- Date de fin -->
      <div class="col-md-4">
        <label for="end_date" class="form-label fw-semibold">Date de fin</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
          <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
        </div>
      </div>

      <!-- Bouton appliquer -->
      <div class="col-md-4 d-grid">
        <button type="submit" class="btn btn-primary rounded-pill shadow-sm hover-shadow-lg">
          <i class="fas fa-check me-1"></i> Appliquer
        </button>
      </div>
      
    </form>
  </div>
</div>


  <!-- Statistiques -->
  <div class="row g-4">
  
     <div class="col-lg-3 col-md-6">
      <a href="/sabroom/public/admin/deliveries?tab=pending-tab" 
		 class="text-decoration-none text-dark">
	  <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-gradient-warning">
            <i class="fas fa-clock fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Pickup</h5>
            <p class="card-text"><?php echo htmlspecialchars($pending_deliveries_count); ?></p>
          </div>
        </div>
      </div>
	  </a>
    </div> 
	
	    <div class="col-lg-3 col-md-6">
		<a href="/sabroom/public/admin/deliveries?tab=assigned-tab" 
		 class="text-decoration-none text-dark">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-secondary">
            <i class="fas fa-warehouse fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Dépôt</h5>
            <p class="card-text"><?php echo htmlspecialchars($assigned_deliveries_count); ?></p>
          </div>
        </div>
      </div>
	  </a>
    </div>
  
	<div class="col-lg-3 col-md-6">
	  <a href="/sabroom/public/admin/deliveries?tab=in_transit-tab" 
		 class="text-decoration-none text-dark">
		<div class="card h-100 shadow-sm">
		  <div class="card-body d-flex align-items-center">
			<div class="icon-circle bg-gradient-primary me-3">
			  <i class="fas fa-shipping-fast fs-4 text-white"></i>
			</div>
			<div>
			  <h5 class="card-title mb-1">En livraison</h5>
			  <p class="card-text fs-5 fw-bold">
				<?php echo htmlspecialchars($inTransitDeliveriesCount); ?>
			  </p>
			</div>
		  </div>
		</div>
	  </a>
	</div>

    <div class="col-lg-3 col-md-6">
	<a href="/sabroom/public/admin/deliveries?tab=delivered-tab" 
		 class="text-decoration-none text-dark">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-gradient-success">
            <i class="fas fa-check-circle fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Colis Livrés</h5>
            <p class="card-text"><?php echo htmlspecialchars($delivered_deliveries_count); ?></p>
          </div>
        </div>
      </div>
	  </a>
    </div>

	
    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-info">
            <i class="fas fa-truck fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">En Transit</h5>
            <p class="card-text"><?php echo htmlspecialchars('0'); ?></p>
          </div>
        </div>
      </div>
    </div>
	
	<div class="col-lg-3 col-md-6">
	<a href="/sabroom/public/admin/deliveries?tab=returned-tab" 
		 class="text-decoration-none text-dark">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-gradient-danger">
            <i class="fas fa-undo-alt fs-4"></i>
          </div>
          <div>
            <h5 class="card-title">Retour</h5>
            <p class="card-text"><?php echo htmlspecialchars($assigned_deliveries_count); ?></p>
          </div>
        </div>
      </div>
	  </a>
    </div>
	
	<div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="icon-circle bg-primary">
            <i class="fas fa-clipboard-check fs-4"></i>
          </div>
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
            <h5 class="card-title">Colis Annulés</h5>
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
          <div class="icon-circle bg-success me-3">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div>
            <h5 class="card-title">Revenus</h5>
            <p class="card-text"><?php echo number_format($total_revenue_delivered_period, 2); ?> DT</p>
          </div>
        </div>
      </div>
    </div>
	

  </div>

  <!-- Graphiques -->


</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Graphique en ligne (évolution)
  const ctxLine = document.getElementById('deliveriesChart').getContext('2d');
  new Chart(ctxLine, {
    type: 'line',
    data: {
      labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
      datasets: [{
        label: 'Livraisons',
        data: [12, 19, 8, 15, 22, 30],
        borderColor: '#4e73df',
        backgroundColor: 'rgba(78, 115, 223, 0.1)',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } }
    }
  });

  // Graphique camembert (statuts)
  const ctxPie = document.getElementById('statusChart').getContext('2d');
  new Chart(ctxPie, {
    type: 'doughnut',
    data: {
      labels: ['En cours', 'Livrés', 'En attente', 'Annulés'],
      datasets: [{
        data: [
          <?php echo $in_transit_deliveries_count; ?>,
          <?php echo $delivered_deliveries_count; ?>,
          <?php echo $pending_deliveries_count; ?>,
          <?php echo $cancelled_deliveries_count; ?>
        ],
        backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>
