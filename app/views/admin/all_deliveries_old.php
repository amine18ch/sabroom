<?php
// all_deliveries.php

require_once __DIR__ . '/../../../config/database.php';

// Connexion MySQL
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) { die("Erreur de connexion MySQL: " . $mysqli->connect_error); }
$mysqli->set_charset("utf8mb4");

// --- Statuts
$deliveryStatuses = [
    'pending' => ['label' => 'Pick-up', 'color' => 'bg-warning', 'icon' => 'fas fa-box', 'text_color' => 'text-dark'],
    'assigned' => ['label' => 'En dépôt', 'color' => 'bg-info', 'icon' => 'fas fa-truck-loading', 'text_color' => 'text-dark'],
    'in_transit' => ['label' => 'En cours de livraison', 'color' => 'bg-primary', 'icon' => 'fas fa-shipping-fast', 'text_color' => 'text-white'],
    'delivered' => ['label' => 'Livrée', 'color' => 'bg-success', 'icon' => 'fas fa-check-circle', 'text_color' => 'text-white'],
    'returned' => ['label' => 'Retour', 'color' => 'bg-danger', 'icon' => 'fas fa-undo-alt', 'text_color' => 'text-white'],
    'cancelled' => ['label' => 'Annulée', 'color' => 'bg-secondary', 'icon' => 'fas fa-ban', 'text_color' => 'text-white']
];

// --- Pagination
$limit = 10;

// --- Onglet actif
$activeTab = $_GET['tab'] ?? 'pending-tab';

// --- Pagination par onglet
$currentPage = [];
foreach($deliveryStatuses as $key => $info){
    $tabParam = $key.'-tab';
    $currentPage[$key] = ($activeTab === $tabParam && isset($_GET['page'])) ? max(1,intval($_GET['page'])) : 1;
}

// --- Filtres avancés
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_supplier_id = $_GET['supplier_id'] ?? '';
$filter_driver_id = $_GET['driver_id'] ?? '';
$search_query = $_GET['search_query'] ?? '';

// --- Fonctions
function getDeliveriesByStatus($mysqli, $status, $limit, $page, $filters) {
    $offset = ($page-1)*$limit;
    $sql = "SELECT d.*, 
                   s.username AS supplier_username, s.company_name AS supplier_company_name,
                   u.username AS driver_username, u.first_name AS driver_first_name, u.last_name AS driver_last_name
            FROM deliveries d
            LEFT JOIN users s ON d.supplier_id = s.id
            LEFT JOIN users u ON d.driver_id = u.id
            WHERE d.status = ?";
    $params = [$status]; $types="s";

    if($filters['start_date']) { $sql .= " AND d.created_at >= ?"; $params[] = $filters['start_date']; $types.="s"; }
    if($filters['end_date']) { $sql .= " AND d.created_at <= ?"; $params[] = $filters['end_date']; $types.="s"; }
    if($filters['supplier_id']) { $sql .= " AND d.supplier_id = ?"; $params[] = $filters['supplier_id']; $types.="i"; }
    if($filters['driver_id']) { $sql .= " AND d.driver_id = ?"; $params[] = $filters['driver_id']; $types.="i"; }
    if($filters['search_query']){
        $sql .= " AND (d.unique_id LIKE ? OR d.description LIKE ? OR d.recipient_name LIKE ?)";
        $search = "%".$filters['search_query']."%";
        $params[]=$search; $params[]=$search; $params[]=$search; $types.="sss";
    }

    $sql .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit; $params[] = $offset; $types.="ii";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $deliveries = [];
    while($row = $res->fetch_assoc()){ $deliveries[] = $row; }
    $stmt->close();
    return $deliveries;
}

function countDeliveriesByStatus($mysqli, $status, $filters){
    $sql = "SELECT COUNT(*) AS total FROM deliveries WHERE status=?";
    $params = [$status]; $types="s";
    if($filters['start_date']) { $sql .= " AND created_at >= ?"; $params[]=$filters['start_date']; $types.="s"; }
    if($filters['end_date']) { $sql .= " AND created_at <= ?"; $params[]=$filters['end_date']; $types.="s"; }
    if($filters['supplier_id']) { $sql .= " AND supplier_id = ?"; $params[]=$filters['supplier_id']; $types.="i"; }
    if($filters['driver_id']) { $sql .= " AND driver_id = ?"; $params[]=$filters['driver_id']; $types.="i"; }
    if($filters['search_query']){
        $sql .= " AND (unique_id LIKE ? OR description LIKE ? OR recipient_name LIKE ?)";
        $search = "%".$filters['search_query']."%";
        $params[]=$search; $params[]=$search; $params[]=$search; $types.="sss";
    }
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $total = $res->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

// --- Récupérer livraisons et totaux
$deliveriesByStatus = [];
$totalByStatus = [];
$filters = [
    'start_date'=>$filter_start_date,
    'end_date'=>$filter_end_date,
    'supplier_id'=>$filter_supplier_id,
    'driver_id'=>$filter_driver_id,
    'search_query'=>$search_query
];

foreach($deliveryStatuses as $key=>$info){
    $deliveriesByStatus[$key] = getDeliveriesByStatus($mysqli, $key, $limit, $currentPage[$key], $filters);
    $totalByStatus[$key] = countDeliveriesByStatus($mysqli, $key, $filters);
}

// --- Pagination helper
function renderPagination($total, $limit, $currentPage, $tab){
    $totalPages = ceil($total / $limit);
    if($totalPages<=1) return '';
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $prev = $currentPage-1; $next = $currentPage+1;
    $html .= '<li class="page-item '.($currentPage<=1?'disabled':'').'"><a class="page-link" href="?tab='.$tab.'&page='.$prev.'"><<</a></li>';
    for($i=1;$i<=$totalPages;$i++){
        $html .= '<li class="page-item '.($currentPage==$i?'active':'').'"><a class="page-link" href="?tab='.$tab.'&page='.$i.'">'.$i.'</a></li>';
    }
    $html .= '<li class="page-item '.($currentPage>=$totalPages?'disabled':'').'"><a class="page-link" href="?tab='.$tab.'&page='.$next.'">>></a></li>';
    $html .= '</ul></nav>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Livraisons</title>
<title>Gestion des Livraisons</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
/* Design et responsive mobile */
.card { border-radius:1rem; transition: all 0.2s ease; }
.card:hover { box-shadow:0 0.8rem 1.5rem rgba(0,0,0,0.1);}
.badge { border-radius:0.5rem;font-weight:600;padding:0.4em 0.8em;font-size:0.85rem;}
.nav-tabs .nav-link { font-weight:600;padding:0.5rem 0.75rem;transition:0.3s; font-size:0.9rem;}
.nav-tabs .nav-link.active { border-radius:0.5rem 0.5rem 0 0; }
.table-responsive { border-radius:0.75rem; overflow-x:auto; box-shadow:0 4px 15px rgba(0,0,0,0.05);}
.responsive-table th, .responsive-table td { white-space:nowrap; padding:0.5rem; border-top:1px solid #e9ecef; font-size:0.85rem;}
.responsive-table tbody tr:nth-of-type(odd){background-color: rgba(0,0,0,0.03);}
.responsive-table tbody tr:hover {background-color: rgba(0,123,255,0.05);}
.delivery-card { display:none; background:#fff; border-radius:0.75rem; padding:0.75rem; margin-bottom:0.75rem; box-shadow:0 4px 12px rgba(0,0,0,0.05);}
.delivery-card h6 { margin:0 0 0.25rem 0; font-weight:600; font-size:0.95rem;}
.delivery-card p { margin:0.25rem 0; font-size:0.85rem;}
@media (max-width:768px){
    .card .fw-bold { font-size:1rem; }
    .card .small { font-size:0.8rem; }
    .btn { font-size:0.75rem; padding:0.25rem 0.5rem; }
    .responsive-table { display:none; }
    .delivery-card { display:block; }
}
</style>
</head>
<body class="bg-light p-2">
<div class="container-fluid">

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
    <h1 class="text-primary mb-2 mb-md-0">Gestion des Livraisons</h1>
    <a href="/sabroom/public/admin/dashboard" class="btn btn-secondary rounded-pill mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Retour au Dashboard</a>
</div>

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

<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" id="deliveryTabs" role="tablist">
<?php foreach($deliveryStatuses as $key=>$info): ?>
<li class="nav-item">
    <button class="nav-link <?php echo ($activeTab===$key.'-tab'?'active':'');?>" id="<?php echo $key;?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo $key;?>-deliveries" type="button">
        <?php echo $info['label'];?> (<?php echo $totalByStatus[$key];?>)
    </button>
</li>
<?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach($deliveryStatuses as $key=>$info): ?>
<div class="tab-pane fade <?php echo ($activeTab===$key.'-tab'?'show active':'');?>" id="<?php echo $key;?>-deliveries">
    <div class="table-responsive">
    <table class="table table-hover table-striped align-middle responsive-table">
        <thead>
        <tr>
            <th>ID Colis</th>
            <th>Statut</th>
            <th>Description</th>
            <th>Destinataire</th>
            <th>Ville</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $statusDeliveries = $deliveriesByStatus[$key];
        if(!empty($statusDeliveries)):
            foreach($statusDeliveries as $d): ?>
            <tr>
                <td><?php echo htmlspecialchars($d['unique_id']);?></td>
                <td><span class="badge <?php echo $info['color'].' '.$info['text_color'];?>"><i class="<?php echo $info['icon'];?> me-1"></i><?php echo $info['label'];?></span></td>
                <td><?php echo htmlspecialchars($d['description']);?></td>
                <td><?php echo htmlspecialchars($d['recipient_name']);?></td>
                <td><?php echo htmlspecialchars($d['recipient_city']);?></td>
                <td>
                    <a href="/sabroom/public/admin/deliveries/edit/<?php echo htmlspecialchars($d['id']);?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-edit"></i></a>
                    <a href="/sabroom/public/admin/deliveries/delete/<?php echo htmlspecialchars($d['id']);?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Supprimer cette livraison ?');"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
            <?php endforeach;
        else: ?>
            <tr><td colspan="6" class="text-center text-muted">Aucun colis</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Version mobile cards -->
    <?php if(!empty($statusDeliveries)): foreach($statusDeliveries as $d): ?>
    <div class="delivery-card">
        <h6><?php echo htmlspecialchars($d['unique_id']);?> <span class="badge <?php echo $info['color'].' '.$info['text_color'];?>"><?php echo $info['label'];?></span></h6>
        <p><?php echo htmlspecialchars($d['description']);?></p>
        <p><strong>Destinataire:</strong> <?php echo htmlspecialchars($d['recipient_name']);?> | <?php echo htmlspecialchars($d['recipient_city']);?></p>
        <p>
            <a href="/sabroom/public/admin/deliveries/edit/<?php echo htmlspecialchars($d['id']);?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-edit"></i></a>
            <a href="/sabroom/public/admin/deliveries/delete/<?php echo htmlspecialchars($d['id']);?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Supprimer cette livraison ?');"><i class="fas fa-trash-alt"></i></a>
        </p>
    </div>
    <?php endforeach; endif; ?>

    <!-- Pagination -->
    <?php echo renderPagination($totalByStatus[$key], $limit, $currentPage[$key], $key.'-tab'); ?>

    </div>
</div>
<?php endforeach; ?>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Onglet actif persistant
document.addEventListener('DOMContentLoaded', function(){
    const tabs = document.querySelectorAll('#deliveryTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => tab.addEventListener('shown.bs.tab', e=>{
        const url = new URL(window.location);
        url.searchParams.set('tab', e.target.id);
        history.replaceState(null,null,url);
    }));
});
</script>
</body>
</html>
