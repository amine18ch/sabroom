<?php
// Fichier: app/views/auth/login.php - FORMULAIRE DE CONNEXION

// Vue du formulaire de connexion avec un design élégant, une modale pour les messages
// et un préchargeur pour une meilleure UX, avec une image de fond.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Sabroom</title>
    <link rel="icon" href="/sabroom/public/assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* background: linear-gradient(135deg, #f0f2f5 0%, #e0e5ec 100%); /* Ancien dégradé */
            background-image: url('https://my.sabrooomdelivery.com/sabroom/public/assets/img/bgg.jpg'); /* Image de fond */
            background-size: cover; /* Couvre toute la zone */
            background-position: center; /* Centre l'image */
            background-repeat: no-repeat; /* Ne répète pas l'image */
            background-attachment: fixed; /* L'image reste fixe lors du défilement */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .login-container {
            max-width: 450px; /* Largeur maximale du conteneur de connexion */
            width: 90%; /* S'adapte aux petits écrans */
            padding: 2.5rem;
            background-color: rgba(255, 255, 255, 0.95); /* Fond blanc légèrement transparent pour la lisibilité */
            border-radius: 1.5rem; /* Coins très arrondis */
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15); /* Ombre élégante et prononcée */
            position: relative;
            z-index: 1;
            overflow: hidden; /* Cache les débordements des éléments internes */
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h3 {
            font-weight: 700;
            color: #2c3e50; /* Couleur de texte sombre pour le contraste */
            font-size: 2.2rem;
        }
        .login-header p {
            color: #6c757d;
            font-size: 1rem;
        }
        .login-header img {
            width: 300px; /* Largeur du logo, ajustez si nécessaire */
            height: auto; /* Hauteur automatique pour conserver les proportions */
            max-height: 100px; /* Hauteur maximale pour éviter un logo trop grand */
            object-fit: contain; /* Assure que l'image est contenue sans être coupée */
            margin-bottom: 0.5rem; /* Marge réduite sous le logo */
        }
        .form-label {
            font-weight: 600;
            color: #555;
        }
        .form-control {
            border-radius: 0.75rem; /* Coins arrondis pour les inputs */
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            border-radius: 0.75rem; /* Coins arrondis pour le bouton */
            padding: 0.8rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            background-color: #1a1a1a; /* Bleu primaire de Bootstrap */
            border-color: #1a1a1a;
            transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 0.3rem 1rem rgba(184, 184, 184, 0.3); /* Ombre pour le bouton */
        }
        .btn-primary:hover {
            background-color:#1a1a1a;;
            border-color: #1a1a1a;;
            box-shadow: 0 0.5rem 1.5rem rgba(184, 184, 184, 0.3);
        }
        .text-center a {
            color:  #ff6600 !important;
            text-decoration: none;
            font-weight: 500;
        }
        .text-center a:hover {
            text-decoration: underline;
        }
        .fa-envelope {
            color: #ff6600 !important;
        }
        .fa-phone-alt{
            color: #ff6600 !important;
        }

        /* Styles pour le préchargeur (spinner) */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8); /* Fond semi-transparent */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Au-dessus de tout */
            opacity: 0; /* Caché par défaut */
            visibility: hidden; /* Caché par défaut */
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #preloader.show {
            opacity: 1;
            visibility: visible;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }

        /* Styles pour les messages flash (modale) */
        .modal-header.bg-success { background-color: #198754 !important; color: white; }
        .modal-header.bg-danger { background-color: #dc3545 !important; color: white; }
        .modal-header.bg-info { background-color: #ff6600 !important; color: white; }
        .modal-header .btn-close { filter: invert(1); } /* Rendre la croix blanche */
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="https://my.sabrooomdelivery.com/sabroom/public/assets/img/sabrooom.png" alt="Logo de l'entreprise" class="mb-2">
            <h4>Connexion à votre compte</h4>
            <p class="text-muted">Veuillez vous connecter à votre compte</p>
        </div>

        <form id="loginForm" action="/sabroom/public/login" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Entrez votre nom d'utilisateur">
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Entrez votre mot de passe">
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Mot de passe oublié ?</a>
        </div>
    </div>

    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-lg">
                <div class="modal-header" id="messageModalHeader">
                    <h5 class="modal-title" id="messageModalLabel">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="messageModalBody">
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="forgotPasswordModalLabel"><i class="fas fa-info-circle me-2"></i> Mot de passe oublié ?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead text-center">Pour des raisons de sécurité, la réinitialisation directe du mot de passe n'est pas disponible.</p>
                    <p class="text-center">Veuillez contacter notre service client pour obtenir de l'aide.</p>
                    <hr>
                    <h6 class="text-secondary text-center mb-3">Nos contacts :</h6>
                    <ul class="list-unstyled text-center">
                        <li><i class="fas fa-phone-alt me-2 text-primary"></i> <strong>Téléphone :</strong> +216 29 472 585</li>
                        
                        <li><i class="fas fa-envelope me-2 text-primary"></i> <strong>Email :</strong> contact@sabrooom-delivery.com</li>
                    </ul>
                    <p class="text-center text-muted small mt-4">Notre équipe vous assistera dans les plus brefs délais.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div id="preloader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const preloader = document.getElementById('preloader');
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            const messageModalHeader = document.getElementById('messageModalHeader');
            const messageModalLabel = document.getElementById('messageModalLabel');
            const messageModalBody = document.getElementById('messageModalBody');

            // Afficher le préchargeur lors de la soumission du formulaire
            loginForm.addEventListener('submit', function() {
                preloader.classList.add('show');
            });

            // Gérer l'affichage des messages flash via la modale
            const sessionMessage = "<?php echo isset($_SESSION['message']) ? htmlspecialchars($_SESSION['message']) : ''; ?>";
            const sessionMessageType = "<?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : ''; ?>";

            if (sessionMessage) {
                messageModalHeader.className = 'modal-header text-white ' + (sessionMessageType === 'success' ? 'bg-success' : (sessionMessageType === 'danger' ? 'bg-danger' : 'bg-info'));
                messageModalLabel.textContent = sessionMessageType === 'success' ? 'Succès' : (sessionMessageType === 'danger' ? 'Erreur de connexion' : 'Information');
                messageModalBody.textContent = sessionMessage;
                messageModal.show();

                // Cacher le préchargeur si un message est affiché (en cas de rechargement avec message)
                preloader.classList.remove('show');
            }
        });
    </script>
</body>
</html>
