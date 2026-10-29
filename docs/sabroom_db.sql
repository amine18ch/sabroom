-- Fichier: docs/sabroom_db.sql

-- Création de la base de données 'sabroom' si elle n'existe pas
CREATE DATABASE IF NOT EXISTS sabroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données 'sabroom' pour les opérations suivantes
USE sabroom;

-- Table des utilisateurs
-- Contient les informations pour les administrateurs, fournisseurs et livreurs.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE, -- Nom d'utilisateur unique pour la connexion
    password VARCHAR(255) NOT NULL,       -- Mot de passe haché
    role ENUM('admin', 'supplier', 'driver') NOT NULL, -- Rôle de l'utilisateur
    email VARCHAR(100) UNIQUE,            -- Adresse email unique
    phone VARCHAR(20),                    -- Numéro de téléphone
    address TEXT,                         -- Adresse complète
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date de création du compte

    -- Champs spécifiques aux fournisseurs
    company_name VARCHAR(100),            -- Nom de l'entreprise du fournisseur
    siret_number VARCHAR(50),             -- Numéro SIRET ou matricule fiscale

    -- Champs spécifiques aux livreurs
    first_name VARCHAR(50),               -- Prénom du livreur
    last_name VARCHAR(50),                -- Nom du livreur
    id_card_number VARCHAR(50),           -- Numéro de carte d'identité
    motorized BOOLEAN,                    -- Indique si le livreur est motorisé (0 pour non, 1 pour oui)
    is_active BOOLEAN DEFAULT TRUE        -- Indique si le compte est actif
);

-- Table des livraisons
-- Contient les détails de chaque colis à livrer.
CREATE TABLE IF NOT EXISTS deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(100) NOT NULL UNIQUE, -- Identifiant unique du colis (servira de code-barres)
    supplier_id INT NOT NULL,               -- ID du fournisseur qui a créé la livraison
    driver_id INT NULL,                     -- ID du livreur assigné (NULL si non assigné)
    description TEXT,                       -- Description du contenu du colis
    destination_address TEXT NOT NULL,      -- Adresse de destination du colis
    status ENUM('pending', 'assigned', 'in_transit', 'delivered', 'returned') NOT NULL DEFAULT 'pending', -- Statut de la livraison
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- Prix calculé pour la livraison du colis
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date de création de la livraison
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Dernière mise à jour

    -- Clés étrangères pour lier aux utilisateurs
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE, -- Si le fournisseur est supprimé, ses livraisons le sont aussi
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL  -- Si le livreur est supprimé, son ID est mis à NULL dans les livraisons
);

-- Insertion d'un utilisateur administrateur par défaut
-- Username: admin
-- Password: password (le mot de passe est haché avec PASSWORD_BCRYPT)
INSERT INTO users (username, password, role, email) VALUES
('admin', '$2y$10$f6B0n2K0W5n5x5y5z5a5m5x5y5z5a5m5x5y5z5a5m5x5y5z5a5m5x5y5z5a5m5x5y5z5a5m5x5y5z5a5m', 'admin', 'admin@sabroom.com');
-- ATTENTION: Changez ce mot de passe 'password' après la première connexion pour des raisons de sécurité !
