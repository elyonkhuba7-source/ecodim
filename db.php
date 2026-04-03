<?php
session_start();

// Sécurité anti-crash : si la session a un ancien format incohérent, on la réinitialise.
if (isset($_SESSION['user']) && !is_array($_SESSION['user'])) {
    unset($_SESSION['user']);
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecodim_db;charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    // Si la base ecodim_db n'existe pas, on tente de la créer.
    $pdo_init = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS ecodim_db");
    $pdo = new PDO("mysql:host=localhost;dbname=ecodim_db;charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

// Création ou mise à niveau du schéma principal.
$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255),
    nom_complet VARCHAR(100) DEFAULT '',
    sexe CHAR(1) DEFAULT 'M',
    telephone VARCHAR(50) DEFAULT '',
    email VARCHAR(150) DEFAULT '',
    adresse VARCHAR(255) DEFAULT '',
    fonction_moniteur VARCHAR(100) DEFAULT '',
    date_naissance DATE DEFAULT NULL,
    date_service DATE DEFAULT NULL,
    observations TEXT,
    role VARCHAR(20) DEFAULT 'admin',
    classe_id INT DEFAULT 0,
    est_bloque TINYINT(1) DEFAULT 0
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enfant_nom VARCHAR(100),
    enfant_prenom VARCHAR(100),
    enfant_ddn VARCHAR(50),
    enfant_sexe CHAR(1),
    classe_id INT DEFAULT 0,
    urgence_contact VARCHAR(255),
    participation_activites VARCHAR(5),
    allergies VARCHAR(255),
    resp1_type VARCHAR(20) DEFAULT 'parent',
    resp1_nom VARCHAR(100),
    resp1_prenom VARCHAR(100),
    resp1_sexe CHAR(1),
    resp1_adresse VARCHAR(255),
    resp1_tel VARCHAR(50),
    resp1_prof VARCHAR(100),
    resp1_email VARCHAR(150) DEFAULT '',
    resp2_type VARCHAR(20) DEFAULT 'parent',
    resp2_nom VARCHAR(100),
    resp2_prenom VARCHAR(100),
    resp2_sexe CHAR(1),
    resp2_adresse VARCHAR(255),
    resp2_tel VARCHAR(50),
    resp2_prof VARCHAR(100),
    resp2_email VARCHAR(150) DEFAULT '',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS lecons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classe_id INT DEFAULT 0,
    periode VARCHAR(100),
    titre_lecon VARCHAR(255),
    verset_cle VARCHAR(255),
    rapport TEXT,
    moniteur_id INT DEFAULT 0,
    moniteur_nom VARCHAR(100) DEFAULT '',
    theme VARCHAR(255) DEFAULT '',
    sous_theme VARCHAR(255) DEFAULT '',
    objectif_pedagogique TEXT,
    passages_bibliques TEXT,
    gestion_presences TEXT,
    activites_realisees TEXT,
    evaluations TEXT,
    fiche_evaluations VARCHAR(255) DEFAULT '',
    observations TEXT,
    est_terminee TINYINT(1) DEFAULT 0,
    date_fin DATETIME DEFAULT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS presences (id INT AUTO_INCREMENT PRIMARY KEY, inscription_id INT DEFAULT 0, date_presence DATE, statut VARCHAR(20))");
$pdo->exec("CREATE TABLE IF NOT EXISTS presences_moniteurs (id INT AUTO_INCREMENT PRIMARY KEY, utilisateur_id INT DEFAULT 0, classe_id INT DEFAULT 0, date_presence DATE, statut VARCHAR(20))");
$pdo->exec("CREATE TABLE IF NOT EXISTS rapports_hebdomadaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_rapport DATE,
    contexte TEXT,
    equipe_pedagogique TEXT,
    enseignements TEXT,
    moniteur VARCHAR(100) DEFAULT '',
    classe_id INT DEFAULT 0,
    theme VARCHAR(255) DEFAULT '',
    sous_theme VARCHAR(255) DEFAULT '',
    objectif_pedagogique TEXT,
    effectifs INT DEFAULT 0,
    presences INT DEFAULT 0,
    absences INT DEFAULT 0,
    created_by INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS finances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_finance DATE,
    libelle VARCHAR(20) DEFAULT 'offrande',
    justificatif TEXT,
    montant DECIMAL(12,2) DEFAULT 0,
    observation TEXT,
    created_by INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classe_id INT DEFAULT 0,
    moniteur_id INT DEFAULT 0,
    moniteur_nom VARCHAR(100) DEFAULT '',
    type_evaluation VARCHAR(100) DEFAULT '',
    periode VARCHAR(100) DEFAULT '',
    theme VARCHAR(255) DEFAULT '',
    sous_theme VARCHAR(255) DEFAULT '',
    details TEXT,
    observations TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Mises à jour sécurisées si la base provient d'une ancienne version.
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN role VARCHAR(20) DEFAULT 'admin'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN classe_id INT DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN nom_complet VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs MODIFY password VARCHAR(255)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN sexe CHAR(1) DEFAULT 'M'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN telephone VARCHAR(50) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN email VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN adresse VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN fonction_moniteur VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN date_naissance DATE DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN date_service DATE DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN observations TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN est_bloque TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp1_type VARCHAR(20) DEFAULT 'parent'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp1_email VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_type VARCHAR(20) DEFAULT 'parent'"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_nom VARCHAR(100)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_prenom VARCHAR(100)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_sexe CHAR(1)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_adresse VARCHAR(255)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_tel VARCHAR(50)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_prof VARCHAR(100)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inscriptions ADD COLUMN resp2_email VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN moniteur_id INT DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN moniteur_nom VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN theme VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN sous_theme VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN objectif_pedagogique TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN passages_bibliques TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN gestion_presences TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN activites_realisees TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN evaluations TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN fiche_evaluations VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN observations TEXT"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN est_terminee TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE lecons ADD COLUMN date_fin DATETIME DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE presences_moniteurs ADD COLUMN utilisateur_id INT DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE presences_moniteurs ADD COLUMN moniteur_id INT DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE presences_moniteurs ADD COLUMN classe_id INT DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE presences_moniteurs ADD COLUMN date_presence DATE"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE presences_moniteurs ADD COLUMN statut VARCHAR(20)"); } catch (Exception $e) {}
try { $pdo->exec("UPDATE presences_moniteurs SET utilisateur_id = moniteur_id WHERE (utilisateur_id IS NULL OR utilisateur_id = 0) AND moniteur_id IS NOT NULL AND moniteur_id <> 0"); } catch (Exception $e) {}

// Uniformise les noms complets si absents.
$pdo->exec("UPDATE utilisateurs SET nom_complet = username WHERE (nom_complet IS NULL OR nom_complet = '')");

// Vérification de l'admin et des classes.
$checkAdmin = $pdo->query("SELECT * FROM utilisateurs WHERE username='admin'")->fetch();
if (!$checkAdmin) {
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (username, password, nom_complet, role, classe_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), 'Administrateur', 'admin', 0]);
} elseif (!password_get_info($checkAdmin['password'] ?? '')['algo']) {
    $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ?, nom_complet = ? WHERE id = ?");
    $stmt->execute([password_hash((string) $checkAdmin['password'], PASSWORD_DEFAULT), $checkAdmin['nom_complet'] ?: 'Administrateur', $checkAdmin['id']]);
}

?>
