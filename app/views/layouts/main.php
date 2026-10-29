<?php
// Fichier: app/views/layouts/main.php - LAYOUT PRINCIPAL
$currentUrl = $_SERVER['REQUEST_URI']; // URL courante pour détecter le menu acti

// NOUVEAU: Simplification de la vérification de rôle
$userRole = $_SESSION['user_role'] ?? null;
$isAdminOrSuperadmin = ($userRole === 'admin' || $userRole === 'superadmin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabroom - Gestion de Livraisons</title>
    <link rel="icon" href="/sabroom/public/assets/img/favicon.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link rel="stylesheet" href="/sabroom/public/assets/css/style.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .wrapper {
            display: flex;
            flex: 1;
        }
        /* Sidebar */
        #sidebar {
            width: 250px;
            min-width: 250px;
            background-color: #000000;
            color: white;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        #sidebar.active { margin-left: -250px; }
        #sidebar .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar .sidebar-header img { height: 50px; width: auto; }
        #sidebar ul { list-style: none; padding-left: 0; }
        #sidebar ul li { width: 100%; }
        #sidebar ul li a {
            color: #e0e0e0;
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        #sidebar ul li a i { margin-right: 10px; font-size: 1.1rem; }
        #sidebar ul li a:hover {
            background-color: #34495e;
            border-left-color: #ff6600;
            color: white;
        }
        #sidebar ul li a.active {
            background-color: #007bff;
            border-left-color: #ff6600;
            color: white;
        }
        #sidebar .user-info {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        #sidebar .user-info p { margin-bottom: 5px; font-size: 0.9rem; }

        /* Content */
        #content {
            flex-grow: 1;
            padding: 20px;
            transition: margin-left 0.3s ease;
            padding-bottom: 100px;
        }

        /* Sidebar toggle */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1000;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none;
        }

        /* Flash messages */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .flash-message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .flash-message.danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar-toggle { display: block; }
            #sidebar { position: fixed; margin-left: -250px; height: 100vh; z-index: 1000; box-shadow: 0 0 15px rgba(0,0,0,0.3); }
            #sidebar.active { margin-left: 0; }
            #content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Hamburger pour mobile/tablette -->
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="/sabroom/public/dashboard"><img src="/sabroom/public/assets/img/sabrooom.png" alt="Logo"></a>
            </div>
            <ul>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="/sabroom/public/dashboard" class="<?php echo (strpos($currentUrl, '/dashboard')!==false)?'active':''; ?>"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>

                    <?php if($isAdminOrSuperadmin): ?>
                        <li><a href="/sabroom/public/admin/users" class="<?php echo (strpos($currentUrl, '/admin/users')!==false)?'active':''; ?>"><i class="fas fa-users-cog"></i> Utilisateurs</a></li>
                        <li><a href="/sabroom/public/admin/assignDeliveriesByBarcode" class="<?php echo (strpos($currentUrl, '/admin/assignDeliveriesByBarcode')!==false)?'active':''; ?>"><i class="fas fa-barcode"></i> Assignation Colis</a></li>
                        <li><a href="/sabroom/public/admin/deliveries" class="<?php echo (strpos($currentUrl, '/admin/deliveries')!==false)?'active':''; ?>"><i class="fas fa-boxes"></i> Livraisons</a></li>
                        <li><a href="/sabroom/public/admin/invoices" class="<?php echo (strpos($currentUrl, '/admin/invoices')!==false)?'active':''; ?>"><i class="fas fa-file-invoice"></i> Factures</a></li>

                    <?php elseif($userRole==='supplier'): ?>
                        <li><a href="/sabroom/public/supplier/deliveries" class="<?php echo (strpos($currentUrl, '/supplier/deliveries')!==false)?'active':''; ?>"><i class="fas fa-boxes"></i> Mes livraisons</a></li>
                        <li><a href="/sabroom/public/supplier/deliveries/create" class="<?php echo (strpos($currentUrl, '/supplier/deliveries/create')!==false)?'active':''; ?>"><i class="fas fa-box-open"></i> Créer livraison</a></li>
                        <li><a href="/sabroom/public/supplier/invoices" class="<?php echo (strpos($currentUrl, '/supplier/invoices')!==false)?'active':''; ?>"><i class="fas fa-file-invoice"></i> Factures</a></li>

                    <?php elseif($userRole==='driver'): ?>
                        <li><a href="/sabroom/public/driver/deliveries" class="<?php echo (strpos($currentUrl, '/driver/deliveries')!==false)?'active':''; ?>"><i class="fas fa-truck"></i> Mes livraisons</a></li>
                    <?php endif; ?>

                    <li class="user-info mt-auto">
                        <p>Connecté :</p>
                        <p class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <a href="/sabroom/public/logout" class="btn btn-outline-light btn-sm mt-2"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li><a href="/sabroom/public/login" class="<?php echo (strpos($currentUrl, '/login')!==false)?'active':''; ?>"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Contenu -->
        <div id="content">
            <?php
            if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show flash-message" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif;
            ?>
            <?php echo $content; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');

            toggle.addEventListener('click', () => { sidebar.classList.toggle('active'); });

            const flash = document.querySelector('.flash-message');
            if(flash) setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(flash);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>
