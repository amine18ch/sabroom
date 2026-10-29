<?php
// all_deliveries.php

require_once __DIR__ . '/../../../config/database.php';

// Connexion MySQL
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) { die("Erreur de connexion MySQL: " . $mysqli->connect_error); }
$mysqli->set_charset("utf8mb4");

// --- Statuts
$deliveryStatuses = [
    'pending'    => ['label' => 'Pick-up', 'color' => 'bg-warning', 'icon' => 'fas fa-box', 'text_color' => 'text-dark'],
    'assigned'   => ['label' => 'En dépôt', 'color' => 'bg-info', 'icon' => 'fas fa-truck-loading', 'text_color' => 'text-dark'],
    'in_transit' => ['label' => 'En cours de livraison', 'color' => 'bg-primary', 'icon' => 'fas fa-shipping-fast', 'text_color' => 'text-white'],
    'delivered'  => ['label' => 'Livrée', 'color' => 'bg-success', 'icon' => 'fas fa-check-circle', 'text_color' => 'text-white'],
    'returned'   => ['label' => 'Reporter', 'color' => 'bg-danger', 'icon' => 'fas fa-undo-alt', 'text_color' => 'text-white'],
    'cancelled'  => ['label' => 'Retour fournisseur', 'color' => 'bg-secondary', 'icon' => 'fas fa-ban', 'text_color' => 'text-white']
];

// --- Pagination
$limit = 10;

// --- Récupération des filtres GET
$filter_start_date  = $_GET['start_date'] ?? '';
$filter_end_date    = $_GET['end_date'] ?? '';
$filter_supplier_id = $_GET['supplier_id'] ?? '';
$filter_driver_id   = $_GET['driver_id'] ?? '';
$filter_status      = $_GET['status_filter'] ?? '';
$search_query       = $_GET['search_query'] ?? '';

// --- Onglet actif
$activeTab = $_GET['tab'] ?? 'pending-tab';

// --- Si filtre status est appliqué, activer automatiquement l'onglet correspondant
if(!empty($filter_status) && array_key_exists($filter_status, $deliveryStatuses)){
    $activeTab = $filter_status . '-tab';
}

// --- Pagination par onglet
$currentPage = [];
foreach($deliveryStatuses as $key => $info){
    $tabParam = $key.'-tab';
    $currentPage[$key] = ($activeTab === $tabParam && isset($_GET['page'])) ? max(1,intval($_GET['page'])) : 1;
}

// --- CSRF Token

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

// --- Initialisation fournisseurs et livreurs
$suppliers = $mysqli->query("SELECT id, username, company_name FROM users WHERE role='supplier'")->fetch_all(MYSQLI_ASSOC);
$drivers   = $mysqli->query("SELECT id, username, first_name, last_name FROM users WHERE role='driver'")->fetch_all(MYSQLI_ASSOC);

// --- Traitement MAJ groupée
$alertMessage = '';
if (isset($_POST['bulk_update']) && !empty($_POST['delivery_ids']) && !empty($_POST['new_status'])) {
    $ids = array_map('intval', $_POST['delivery_ids']); // sécuriser les IDs
    $new_status = $_POST['new_status'];

    if (in_array($new_status, array_keys($deliveryStatuses))) {
        // Création des placeholders pour le IN()
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $stmt = $mysqli->prepare("UPDATE deliveries SET status=? WHERE id IN ($placeholders)");
        $stmt->bind_param("s" . $types, $new_status, ...$ids);
        $stmt->execute();
        $stmt->close();

        $alertMessage = "Statut mis à jour pour ".count($ids)." livraisons.";
    } else {
        $alertMessage = "Statut invalide sélectionné !";
    }
}

// --- Fonctions
function buildFilters($filters, &$params, &$types){
    $clauses = [];
    if($filters['start_date']) { $clauses[] = "d.created_at >= ?"; $params[] = $filters['start_date']; $types.="s"; }
    if($filters['end_date'])   { $clauses[] = "d.created_at <= ?"; $params[] = $filters['end_date']; $types.="s"; }
    if($filters['supplier_id']){ $clauses[] = "d.supplier_id = ?"; $params[] = $filters['supplier_id']; $types.="i"; }
    if($filters['driver_id'])  { $clauses[] = "d.driver_id = ?"; $params[] = $filters['driver_id']; $types.="i"; }
    if($filters['search_query']){
        $clauses[] = "(d.unique_id LIKE ? OR d.description LIKE ? OR d.recipient_name LIKE ?)";
        $search = "%".$filters['search_query']."%";
        $params[]=$search; $params[]=$search; $params[]=$search; $types.="sss";
    }
    if(!empty($filters['status_filter'])){
        $clauses[] = "d.status = ?";
        $params[] = $filters['status_filter'];
        $types .= "s";
    }
    return $clauses ? ' AND '.implode(' AND ', $clauses) : '';
}

function getDeliveriesByStatus($mysqli, $status, $limit, $page, $filters) {
    $offset = ($page-1)*$limit;
    $sql = "SELECT d.*, d.attempts,
                   s.username AS supplier_username, s.company_name AS supplier_company_name,
                   u.username AS driver_username, u.first_name AS driver_first_name, u.last_name AS driver_last_name
            FROM deliveries d
            LEFT JOIN users s ON d.supplier_id = s.id
            LEFT JOIN users u ON d.driver_id = u.id
            WHERE d.status = ?";
    $params = [$status]; $types="s";
    $sql .= buildFilters($filters, $params, $types);
    $sql .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit; $params[] = $offset; $types.="ii";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $deliveries = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $deliveries;
}

function countDeliveriesByStatus($mysqli, $status, $filters){
    $sql = "SELECT COUNT(*) AS total FROM deliveries d WHERE d.status=?";
    $params = [$status]; $types="s";
    $sql .= buildFilters($filters, $params, $types);
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

function renderPagination($total, $limit, $currentPage, $tab, $filters){
    $totalPages = ceil($total / $limit);
    if($totalPages<=1) return '';

    $queryParams = ['tab'=>$tab];
    foreach($filters as $k=>$v){ if($v) $queryParams[$k]=$v; }

    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $prev = $currentPage-1; $next = $currentPage+1;

    $prevQuery = http_build_query(array_merge($queryParams, ['page'=>$prev]));
    $html .= '<li class="page-item '.($currentPage<=1?'disabled':'').'"><a class="page-link" href="?'.$prevQuery.'"><<</a></li>';

    for($i=1;$i<=$totalPages;$i++){
        $pageQuery = http_build_query(array_merge($queryParams, ['page'=>$i]));
        $html .= '<li class="page-item '.($currentPage==$i?'active':'').'"><a class="page-link" href="?'.$pageQuery.'">'.$i.'</a></li>';
    }

    $nextQuery = http_build_query(array_merge($queryParams, ['page'=>$next]));
    $html .= '<li class="page-item '.($currentPage>=$totalPages?'disabled':'').'"><a class="page-link" href="?'.$nextQuery.'">>></a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// --- Récupérer livraisons et totaux
$deliveriesByStatus = [];
$totalByStatus = [];
$filters = [
    'start_date'=>$filter_start_date,
    'end_date'=>$filter_end_date,
    'supplier_id'=>$filter_supplier_id,
    'driver_id'=>$filter_driver_id,
    'search_query'=>$search_query,
    'status_filter'=>$filter_status
];

foreach($deliveryStatuses as $key=>$info){
    $deliveriesByStatus[$key] = getDeliveriesByStatus($mysqli, $key, $limit, $currentPage[$key], $filters);
    $totalByStatus[$key] = countDeliveriesByStatus($mysqli, $key, $filters);
}

// --- Fonctions d’affichage
function renderDeliveryRow($d, $info){
    return '<tr>
        <td><input type="checkbox" class="selectItem" name="delivery_ids[]" value="'.htmlspecialchars($d['id']).'"></td>
        <td>'.htmlspecialchars($d['unique_id']).'</td>
        <td><span class="badge '.$info['color'].' '.$info['text_color'].'"><i class="'.$info['icon'].' me-1"></i>'.$info['label'].'</span></td>
        <td>'.htmlspecialchars($d['description']).'</td>
        <td>'.htmlspecialchars($d['recipient_name']).'</td>
        <td>'.htmlspecialchars($d['supplier_username']).'</td>
        <td>'.htmlspecialchars($d['created_at']).'</td>
        <td>'.htmlspecialchars($d['updated_at']).'</td>
        <td>'.htmlspecialchars($d['recipient_city']).'</td>
        <td>'.htmlspecialchars($d['attempts']).' Fois</td>
        <td>
            <a href="/sabroom/public/admin/deliveries/edit/'.htmlspecialchars($d['id']).'" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-edit"></i></a>
            <a href="/sabroom/public/admin/deliveries/delete/'.htmlspecialchars($d['id']).'" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm(\'Supprimer cette livraison ?\');"><i class="fas fa-trash-alt"></i></a>
        </td>
    </tr>';
}

function renderDeliveryCard($d, $info){
    return '<div class="delivery-card">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="mb-0">'.htmlspecialchars($d['unique_id']).' - <span class="badge '.$info['color'].' '.$info['text_color'].'">'.$info['label'].'</span></h6>
            <input type="checkbox" class="selectItem" name="delivery_ids[]" value="'.htmlspecialchars($d['id']).'">
        </div>
        <p><strong>Description:</strong> '.htmlspecialchars($d['description']).'</p>
        <p><strong>Destinataire:</strong> '.htmlspecialchars($d['recipient_name']).'</p>
        <p><strong>Fournisseur:</strong> '.htmlspecialchars($d['supplier_username']).'</p>
        <p><strong>Ville:</strong> '.htmlspecialchars($d['recipient_city']).'</p>
        <p><strong>Tentatives:</strong> '.htmlspecialchars($d['attempts']).'</p>
        <div class="d-flex gap-1 mt-1">
            <a href="/sabroom/public/admin/deliveries/edit/'.htmlspecialchars($d['id']).'" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-edit"></i></a>
            <a href="/sabroom/public/admin/deliveries/delete/'.htmlspecialchars($d['id']).'" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm(\'Supprimer cette livraison ?\');"><i class="fas fa-trash-alt"></i></a>
        </div>
    </div>';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Livraisons</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
.card { border-radius:1rem; transition: all 0.2s ease; }
.card:hover { box-shadow:0 0.8rem 1.5rem rgba(0,0,0,0.1);}
.badge { border-radius:0.5rem;font-weight:600;padding:0.4em 0.8em;font-size:0.85rem;}
.nav-tabs .nav-link { font-weight:600;padding:0.5rem 0.75rem;transition:0.3s; font-size:0.9rem;}
.nav-tabs .nav-link.active { border-radius:0.5rem 0.5rem 0 0; }
.table-responsive { border-radius:0.75rem; overflow-x:auto; box-shadow:0 4px 15px rgba(0,0,0,0.05);}
.table th, .table td { white-space:nowrap; padding:0.5rem; font-size:0.85rem;}
.table tbody tr:nth-of-type(odd){background-color: rgba(0,0,0,0.03);}
.table tbody tr:hover {background-color: rgba(0,123,255,0.05);}
.delivery-card { background:#fff; border-radius:0.75rem; padding:0.75rem; margin-bottom:0.75rem; box-shadow:0 4px 12px rgba(0,0,0,0.05);}
.delivery-card h6 { margin:0 0 0.25rem 0; font-weight:600; font-size:0.95rem;}
.delivery-card p { margin:0.25rem 0; font-size:0.85rem;}
@media (max-width:768px){
    .btn { font-size:0.75rem; padding:0.25rem 0.5rem; }
    .table-responsive { display:none; }
    .delivery-card { display:block; }
    .mobile-select-all { margin-bottom:0.5rem; }
}
@media (min-width:769px){
    .delivery-card { display:none; }
}
</style>
</head>
<body class="bg-light p-2">
<div class="container-fluid">

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
    <h1 class="text-primary mb-2 mb-md-0">Gestion des Livraisons</h1>
    <a href="/sabroom/public/admin/dashboard" class="btn btn-secondary rounded-pill mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Retour au Dashboard</a>
</div>

<?php if($alertMessage): ?>
<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
    <?php echo htmlspecialchars($alertMessage); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Statistiques -->
<div class="row mb-3 flex-nowrap overflow-auto">
<?php foreach($deliveryStatuses as $key=>$info): ?>
<div class="col-6 col-md-2 mb-2">
    <div class="card text-white <?php echo $info['color'];?> h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <small><?php echo $info['label'];?></small>
                <div class="fw-bold"><?php echo $totalByStatus[$key];?></div>
            </div>
            <i class="<?php echo $info['icon'];?> fa-2x text-white-50"></i>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Filtres -->
<div class="card shadow-lg border-0 rounded-lg mb-4">
<div class="card-header bg-light border-0 rounded-top-lg py-3">
    <h5 class="mb-0 text-dark">Filtres Avancés</h5>
</div>
<div class="card-body">
<form id="deliveryFilterForm" action="/sabroom/public/admin/deliveries" method="GET" class="row g-3 align-items-end"> 
    <div class="col-md-3">
        <label for="start_date" class="form-label">Date de début:</label>
        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
    </div>
    <div class="col-md-3">
        <label for="end_date" class="form-label">Date de fin:</label>
        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
    </div>
    <div class="col-md-3">
        <label for="supplier_id" class="form-label">Fournisseur:</label>
        <select class="form-select" id="supplier_id" name="supplier_id">
            <option value="">Tous les fournisseurs</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($filter_supplier_id == $supplier['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($supplier['username'] . ' (' . ($supplier['company_name'] ?? 'N/A') . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label for="driver_id" class="form-label">Livreur:</label>
        <select class="form-select" id="driver_id" name="driver_id">
            <option value="">Tous les livreurs</option>
            <?php foreach ($drivers as $driver): ?>
                <option value="<?php echo htmlspecialchars($driver['id']); ?>" <?php echo ($filter_driver_id == $driver['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($driver['username'] . ' (' . ($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? '') . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3 mt-3">
        <label for="status_filter" class="form-label">Statut:</label>
        <select class="form-select" id="status_filter" name="status_filter">
            <option value="">Tous les statuts</option>
            <?php foreach ($deliveryStatuses as $key=>$info): ?>
                <option value="<?php echo $key;?>" <?php echo ($filter_status==$key)?'selected':'';?>><?php echo $info['label'];?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3 mt-3">
        <label for="search_query" class="form-label">Recherche:</label>
        <input type="text" class="form-control" id="search_query" name="search_query" placeholder="ID, description, destinataire" value="<?php echo htmlspecialchars($search_query); ?>">
    </div>
    <div class="col-md-3 mt-3">
        <button type="submit" class="btn btn-primary w-100 mt-4"><i class="fas fa-filter me-1"></i> Appliquer</button>
    </div>
</form>
</div>
</div>

<!-- Onglets -->
<ul class="nav nav-tabs mb-3" id="deliveryTabs" role="tablist">
<?php foreach($deliveryStatuses as $key=>$info): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab==$key.'-tab')?'active':'';?>" id="<?php echo $key;?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo $key;?>-content" type="button" role="tab" aria-controls="<?php echo $key;?>-content" aria-selected="<?php echo ($activeTab==$key.'-tab')?'true':'false';?>">
            <?php echo $info['label']; ?> (<?php echo $totalByStatus[$key]; ?>)
        </button>
    </li>
<?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach($deliveryStatuses as $key=>$info): ?>
<div class="tab-pane fade <?php echo ($activeTab==$key.'-tab')?'show active':'';?>" id="<?php echo $key;?>-content" role="tabpanel" aria-labelledby="<?php echo $key;?>-tab">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <!-- Desktop Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-3">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll_<?php echo $key;?>"></th>
                        <th>ID</th>
                        <th>Statut</th>
                        <th>Description</th>
                        <th>Destinataire</th>
                        <th>Fournisseur</th>
                        <th>Date de Création</th>
                        <th>Mise à jour</th>
                        <th>Ville</th>
                        <th>Tentatives</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($deliveriesByStatus[$key])): ?>
                        <?php foreach($deliveriesByStatus[$key] as $d): ?>
                            <?php echo renderDeliveryRow($d, $info); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">Aucune livraison trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <?php if(!empty($deliveriesByStatus[$key])): ?>
 <div class="mobile-select-all d-block d-md-none mb-2">
    <input type="checkbox" id="selectAllMobile_<?php echo $key; ?>"> Tout sélectionner
</div>

        <?php foreach($deliveriesByStatus[$key] as $d): ?>
            <?php echo renderDeliveryCard($d, $info); ?>
        <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center d-block d-md-none">Aucune livraison trouvée.</div>
        <?php endif; ?>

		<?php echo renderPagination($totalByStatus[$key], $limit, $currentPage[$key], $key.'-tab', $filters); ?>

        <div class="d-flex gap-2 mt-2">
            <select class="form-select w-auto" name="new_status" required>
                <option value="">Changer le statut</option>
                <?php foreach($deliveryStatuses as $k=>$i): ?>
                    <option value="<?php echo $k;?>"><?php echo $i['label'];?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="bulk_update" class="btn btn-sm btn-outline-warning rounded-pill">Appliquer</button>
        </div>
    </form>
</div>
<?php endforeach; ?>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Desktop Select All
<?php foreach($deliveryStatuses as $key=>$info): ?>
document.getElementById('selectAll_<?php echo $key;?>')?.addEventListener('change', function(){
    document.querySelectorAll('#<?php echo $key;?>-content .table .selectItem').forEach(cb=>cb.checked=this.checked);
});
<?php endforeach; ?>

// Mobile Select All
<?php foreach($deliveryStatuses as $key=>$info): ?>
document.getElementById('selectAllMobile_<?php echo $key;?>')?.addEventListener('change', function(){
    document.querySelectorAll('#<?php echo $key;?>-content .delivery-card .selectItem').forEach(cb=>cb.checked=this.checked);
});
<?php endforeach; ?>
</script>
</body>
</html>
