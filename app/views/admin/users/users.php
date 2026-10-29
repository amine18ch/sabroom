<?php
// File: app/views/admin/users/users.php
// Modern + server-side paginated, optimized users list.
// Requires: config/database.php with DB_HOST, DB_USER, DB_PASS, DB_NAME

if (session_status() === PHP_SESSION_NONE) session_start();

// --- DB connection (view-level fallback) ---
require_once __DIR__ . '/../../../../config/database.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    // Fail fast: display friendly message
    die('<div style="padding:20px;font-family:Arial;">Erreur de connexion à la base de données : ' . htmlspecialchars($mysqli->connect_error) . '</div>');
}
$mysqli->set_charset('utf8mb4');

// --- Config ---
$limit = 20; // items per page
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$tab   = $_GET['tab'] ?? 'all'; // 'all' or 'admin' or 'supplier' or 'driver'
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = $_GET['status'] ?? ''; // 'active' | 'inactive' | ''

// roles definition (for UI)
$userRoles = [
    'admin' => ['label' => 'Administrateur', 'color' => 'bg-danger', 'badge' => 'danger'],
    'supplier' => ['label' => 'Fournisseur', 'color' => 'bg-primary', 'badge' => 'primary'],
    'driver' => ['label' => 'Livreur', 'color' => 'bg-info', 'badge' => 'info']
];

// --- Helpers: build WHERE conditions and params safely ---
function buildWhereAndParams($role, $search, $status) {
    $where = [];
    $types = '';
    $params = [];

    if ($role && $role !== 'all') {
        $where[] = 'role = ?';
        $types .= 's';
        $params[] = $role;
    }
    if ($status === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($search !== '') {
        // search on username or email or first_name/last_name
        $where[] = '(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
        $types .= 'ssss';
        $q = '%' . $search . '%';
        $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
    }

    $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    return [$whereSql, $types, $params];
}

// --- Get counts per role (single queries) for dashboard cards: optimized -->
$counts = [];
$rolesForCounts = array_merge(['all'], array_keys($userRoles));
foreach ($rolesForCounts as $r) {
    list($whereSql, $types, $params) = buildWhereAndParams($r === 'all' ? '' : $r, '', '');
    $sql = "SELECT COUNT(*) as c FROM users $whereSql";
    $stmt = $mysqli->prepare($sql);
    if ($stmt && $types !== '') {
        $stmt->bind_param($types, ...$params);
    } elseif (!$stmt) {
        $counts[$r] = 0;
        continue;
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $counts[$r] = intval($row['c'] ?? 0);
    $stmt->close();
}

// --- Fetch paginated users for current tab with JOINs if needed (single optimized query) ---
list($whereSql, $types, $params) = buildWhereAndParams($tab === 'all' ? '' : $tab, $search, $statusFilter);
$sql = "SELECT id, username, email, role,
               COALESCE(first_name, '') AS first_name,
               COALESCE(last_name, '') AS last_name,
               COALESCE(company_name, '') AS company_name,
               COALESCE(siret_number, '') AS siret_number,
               COALESCE(phone, '') AS phone,
               COALESCE(motorized, 0) AS motorized,
               COALESCE(is_active,0) AS is_active,
               created_at
        FROM users
        $whereSql
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";
$typesWithLimit = $types . 'ii';
$paramsWithLimit = array_merge($params, [$limit, $offset]);

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die('<div style="padding:20px;">Erreur SQL prepare: ' . htmlspecialchars($mysqli->error) . '</div>');
}
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$result = $stmt->get_result();
$usersPage = [];
while ($r = $result->fetch_assoc()) $usersPage[] = $r;
$stmt->close();

// --- Total for pagination (COUNT) ---
$countSql = "SELECT COUNT(*) as total FROM users $whereSql";
$stmt2 = $mysqli->prepare($countSql);
if ($stmt2 === false) {
    $totalItems = 0;
} else {
    if ($types !== '') $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $totalItems = intval(($res2->fetch_assoc()['total'] ?? 0));
    $stmt2->close();
}
$totalPages = max(1, (int)ceil($totalItems / $limit));

// --- Render pagination HTML (server-side) ---
function renderPagination($totalItems, $limit, $currentPage, $tab, $search, $status) {
    $totalPages = (int)ceil($totalItems/$limit);
    if ($totalPages <= 1) return '';
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';
    $prev = max(1, $currentPage - 1);
    $next = min($totalPages, $currentPage + 1);
    // Previous
    $disabledPrev = $currentPage <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabledPrev . '"><a class="page-link" href="?tab='.urlencode($tab).'&page='.($currentPage-1).'&search='.urlencode($search).'&status='.urlencode($status).'">Précédent</a></li>';
    // Pages (show limited window if many)
    $window = 5;
    $start = max(1, $currentPage - intval($window/2));
    $end = min($totalPages, $start + $window - 1);
    if ($end - $start < $window - 1) $start = max(1, $end - $window + 1);
    if ($start > 1) $html .= '<li class="page-item"><a class="page-link" href="?tab='.urlencode($tab).'&page=1&search='.urlencode($search).'&status='.urlencode($status).'">1</a></li><li class="page-item disabled"><span class="page-link">…</span></li>';
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item'.$active.'"><a class="page-link" href="?tab='.urlencode($tab).'&page='.$i.'&search='.urlencode($search).'&status='.urlencode($status).'">'.$i.'</a></li>';
    }
    if ($end < $totalPages) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li><li class="page-item"><a class="page-link" href="?tab='.urlencode($tab).'&page='.$totalPages.'&search='.urlencode($search).'&status='.urlencode($status).'">'.$totalPages.'</a></li>';
    // Next
    $disabledNext = $currentPage >= $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabledNext . '"><a class="page-link" href="?tab='.urlencode($tab).'&page='.($currentPage+1).'&search='.urlencode($search).'&status='.urlencode($status).'">Suivant</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// --- small helper for escaping displayed text ---
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestion des utilisateurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
  :root{
    --accent1: #ff8e2d;
    --accent2: #ffb961;
    --card-radius: 14px;
    --muted: #ffffff;
  }
  body{background:#f4f6fb;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;}
  .page-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:1.25rem 0;}
  .title{font-weight:700;font-size:1.6rem;display:flex;align-items:center;gap:.6rem;color:#737373;}
  .btn-gradient{background:linear-gradient(90deg,var(--accent1),var(--accent2));color:#fff;border:none;box-shadow:0 6px 18px rgba(99,102,241,.12);padding:.55rem .9rem;}
  .card-modern{border-radius:var(--card-radius);box-shadow:0 6px 18px rgba(16,24,40,.06);border:0;}
  .stat-card{min-height:72px;border-radius:10px;padding:.9rem;}
  .stat-label{font-weight:500;font-size:.85rem;color:var(--muted)}
  .stat-value{font-weight:700;font-size:1.2rem}
  .table-responsive-modern{border-radius:10px;overflow:hidden;box-shadow:0 6px 18px rgba(16,24,40,.04)}
  table thead th{background:#fff;border-bottom:1px solid #eef2f6;font-weight:700}
  .badge-role{padding:.4rem .6rem;border-radius:999px;font-weight:600;}
  /* responsive -> convert rows to cards on mobile */
  @media (max-width:991.98px){
    table thead{display:none}
    table tbody tr{display:block;margin-bottom:1rem;border-radius:10px;background:#fff;padding:1rem;box-shadow:0 6px 18px rgba(16,24,40,.04)}
    table tbody td{display:flex;justify-content:space-between;padding:.45rem 0;border:none}
    table tbody td::before{content:attr(data-label);font-weight:700;color:#495057;margin-right:.5rem}
    .actions-cell{justify-content:center}
  }
  /* small niceties */
  .search-input{max-width:420px}
  .filters-row .form-control, .filters-row .form-select{height:40px}
</style>
</head>
<body>
<nav class="container">
  <div class="page-header">
    <div class="title"><i class="fas fa-users"></i> Gestion des utilisateurs</div>
    <div class="d-flex gap-2 align-items-center">
      <a href="/sabroom/public/admin/users/create" class="btn btn-gradient rounded-pill"><i class="fas fa-plus me-2"></i> Nouveau utilisateur</a>
    </div>
  </div>
</nav>

<main class="container">
  <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= e($_SESSION['message_type'] ?? 'info') ?> alert-dismissible fade show">
      <?= e($_SESSION['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <!-- stats row -->
  <div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
      <div class="card card-modern stat-card d-flex align-items-center justify-content-between" style="background:linear-gradient(32deg,#e8f0ff,#585858)">
        <div>
          <div class="stat-label">Tous les utilisateurs</div>
          <div class="stat-value"><?= e($counts['all'] ?? 0) ?></div>
        </div>
        <div class="text-muted"><i class="fas fa-users fa-2x"></i></div>
      </div>
    </div>
    <?php foreach($userRoles as $rk=>$ri): ?>
      <div class="col-md-3 col-6">
        <div class="card card-modern stat-card d-flex align-items-center justify-content-between <?= $ri['color'] ?> text-white">
          <div>
            <div class="stat-label"><?= e($ri['label']) ?></div>
            <div class="stat-value"><?= e($counts[$rk] ?? 0) ?></div>
          </div>
          <div class="opacity-75"><i class="fas <?php
            echo $rk==='admin' ? 'fa-user-shield' : ($rk==='supplier' ? 'fa-store' : 'fa-truck');
          ?> fa-2x"></i></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- filters -->
  <div class="card card-modern mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-center filters-row">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div class="col-lg-4 col-md-6">
          <input name="search" value="<?= e($search) ?>" class="form-control search-input" placeholder="Rechercher nom, email..." />
        </div>
        <div class="col-lg-2 col-md-3">
          <select name="status" class="form-select">
            <option value="">Tous statuts</option>
            <option value="active" <?= $statusFilter==='active' ? 'selected' : '' ?>>Actif</option>
            <option value="inactive" <?= $statusFilter==='inactive' ? 'selected' : '' ?>>Inactif</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-3 d-grid">
          <button class="btn btn-outline-primary">Filtrer</button>
        </div>
        <div class="col-lg-4 col-md-12 text-end">
          <div class="btn-group" role="group" aria-label="tabs">
            <a href="?tab=all" class="btn btn-sm <?= $tab==='all' ? 'btn-primary text-white' : 'btn-outline-secondary' ?>">Tous</a>
            <a href="?tab=admin" class="btn btn-sm <?= $tab==='admin' ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">Admins</a>
            <a href="?tab=supplier" class="btn btn-sm <?= $tab==='supplier' ? 'btn-primary text-white' : 'btn-outline-secondary' ?>">Fournisseurs</a>
            <a href="?tab=driver" class="btn btn-sm <?= $tab==='driver' ? 'btn-warning text-white' : 'btn-outline-secondary' ?>">Livreurs</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- table -->
  <div class="card card-modern">
    <div class="card-body">
      <div class="table-responsive-modern">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Rôle</th>
              <th>État</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($usersPage) === 0): ?>
              <tr><td colspan="5" class="text-center py-5 text-muted">Aucun utilisateur trouvé.</td></tr>
            <?php else: foreach ($usersPage as $u): ?>
              <tr>
                <td data-label="Utilisateur">
                  <div style="font-weight:700"><?= e($u['username']) ?></div>
                  <?php if ($u['first_name'] || $u['last_name']): ?>
                    <div class="text-muted" style="font-size:.9rem"><?= e(trim($u['first_name'].' '.$u['last_name'])) ?></div>
                  <?php endif; ?>
                </td>
                <td data-label="Email"><?= e($u['email']) ?></td>
                <td data-label="Télephone"><?= e($u['phone']) ?></td>
                <td data-label="Rôle">
                  <?php $rk = $u['role'] ?? 'unknown'; $rlabel = $userRoles[$rk]['label'] ?? ucfirst($rk); $rcolor = $userRoles[$rk]['badge'] ?? 'secondary'; ?>
                  <span class="badge badge-role bg-<?= e($rcolor) ?>"><?= e($rlabel) ?></span>
                </td>
                <td data-label="État">
                  <?php if (!empty($u['is_active'])): ?>
                    <span class="badge bg-success">Actif</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Inactif</span>
                  <?php endif; ?>
                </td>
                <td data-label="Actions" class="actions-cell text-center">
                  <a href="/sabroom/public/admin/users/edit/<?= urlencode($u['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Modifier">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="/sabroom/public/admin/users/delete/<?= urlencode($u['id']) ?>" class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- pagination -->
      <div class="mt-3">
        <?= renderPagination($totalItems, $limit, $page, $tab, $search, $statusFilter) ?>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // keep tab state on client side (so UI reflects ?tab=)
  (function(){
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'all';
    // nothing to do since we use server-driven tab (buttons link to ?tab=)
    // optional: trap Enter on search to reset to page 1
    document.querySelectorAll('form').forEach(f => f.addEventListener('submit', function(){
      // ensure page resets to 1 when filtering
      const p = new URL(window.location).searchParams;
      p.set('page', '1');
      const action = window.location.pathname + '?' + p.toString();
      // allow normal submit to include current inputs
    }));
  })();
</script>
</body>
</html>
