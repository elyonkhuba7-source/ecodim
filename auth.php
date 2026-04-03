<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

// Recharge l'utilisateur depuis la base à chaque requête pour garder
// la classe et les droits synchronisés entre ordinateur et téléphone.
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (($currentUser['role'] ?? '') === 'moniteur' && !empty($currentUser['est_bloque'])) {
    session_unset();
    session_destroy();
    header("Location: login.php?blocked=1");
    exit();
}

$_SESSION['user_nom'] = $currentUser['nom_complet'] ?: $currentUser['username'];
$_SESSION['user_role'] = $currentUser['role'] ?: 'admin';
$_SESSION['user_classe_id'] = (int) ($currentUser['classe_id'] ?? 0);
$_SESSION['user'] = [
    'id' => (int) $currentUser['id'],
    'username' => $currentUser['username'],
    'nom_complet' => $currentUser['nom_complet'] ?: $currentUser['username'],
    'role' => $currentUser['role'] ?: 'admin',
    'classe_id' => (int) ($currentUser['classe_id'] ?? 0),
    'est_bloque' => (int) ($currentUser['est_bloque'] ?? 0),
];
?>
