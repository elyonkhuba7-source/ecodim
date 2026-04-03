<?php
require_once 'auth.php';
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') != 'admin') { header("Location: index.php"); exit; }

$message = "";
$erreur = "";

function isStrongPassword(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }

    return (bool) preg_match('/[A-Za-z]/', $password) && (bool) preg_match('/\d/', $password);
}

if (isset($_POST['change_admin_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['admin_new_password'] ?? '';
    $confirmPassword = $_POST['admin_confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([(int) ($_SESSION['user']['id'] ?? 0)]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $storedPassword = (string) ($admin['password'] ?? '');
    $isHashed = (bool) password_get_info($storedPassword)['algo'];
    $isValid = $isHashed ? password_verify($currentPassword, $storedPassword) : hash_equals($storedPassword, $currentPassword);

    if (!$isValid) {
        $erreur = "Le mot de passe actuel de l'administrateur est incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $erreur = "La confirmation du nouveau mot de passe ne correspond pas.";
    } elseif (!isStrongPassword($newPassword)) {
        $erreur = "Le nouveau mot de passe administrateur doit contenir au moins 8 caractères avec des lettres et des chiffres.";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $admin['id']]);
        $message = "Le mot de passe administrateur a été mis à jour.";
    }
}

if (isset($_POST['reset_password'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';

    if (!isStrongPassword($newPassword)) {
        $erreur = "Le nouveau mot de passe du moniteur doit contenir au moins 8 caractères avec des lettres et des chiffres.";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ? AND role = 'moniteur'");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        $message = "Le mot de passe du moniteur a été réinitialisé.";
    }
}

$moniteurs = $pdo->query("SELECT u.id, u.username, u.nom_complet, c.nom AS classe_nom
    FROM utilisateurs u
    LEFT JOIN classes c ON c.id = u.classe_id
    WHERE u.role = 'moniteur'
    ORDER BY u.nom_complet ASC, u.username ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Sécurité</p>
        <h2 class="page-title">Mots de passe et accès</h2>
        <p class="page-subtitle">Cette page regroupe la sécurité du compte administrateur et la réinitialisation des mots de passe des moniteurs dans une branche dédiée du menu.</p>
    </div>
    <div class="hero-chip"><?= count($moniteurs) ?> moniteur(s) géré(s)</div>
</section>

<?php if ($message): ?>
    <section class="panel" style="background:#eefbf5; border-color:rgba(31, 138, 112, 0.18); color:#175d49;">
        <strong>Succès :</strong> <?= htmlspecialchars($message) ?>
    </section>
<?php endif; ?>

<?php if ($erreur): ?>
    <section class="panel" style="background:#fff1ee; border-color:rgba(200, 76, 45, 0.18); color:#8a2f1c;">
        <strong>Erreur :</strong> <?= htmlspecialchars($erreur) ?>
    </section>
<?php endif; ?>

<section class="panel">
    <h3 class="section-title">Compte administrateur</h3>
    <form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div>
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" required>
            </div>
            <div>
                <label>Nouveau mot de passe</label>
                <input type="password" name="admin_new_password" placeholder="Minimum 8 caractères avec lettres et chiffres" required>
            </div>
            <div class="form-full">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="admin_confirm_password" required>
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" name="change_admin_password" class="btn-submit">🔐 Mettre à jour le mot de passe administrateur</button>
        </div>
    </form>
</section>

<section class="panel">
    <h3 class="section-title">Réinitialiser les mots de passe des moniteurs</h3>
    <?php if (empty($moniteurs)): ?>
        <div class="empty-state">Aucun moniteur à gérer pour le moment.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table style="margin-bottom:0;">
                <tr><th>Moniteur</th><th>Classe</th><th>Nouveau mot de passe</th></tr>
                <?php foreach ($moniteurs as $m): ?>
                    <tr>
                        <td>
                            <div style="font-weight:800;"><?= htmlspecialchars($m['nom_complet'] ?: $m['username']) ?></div>
                            <div style="color:#6d5d48; font-size:13px; margin-top:4px;"><?= htmlspecialchars($m['username']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($m['classe_nom'] ?: 'Aucune classe') ?></td>
                        <td style="min-width:320px;">
                            <form method="POST" style="display:flex; gap:8px; align-items:center; margin:0; box-shadow:none; border:none; padding:0; background:transparent;">
                                <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                                <input type="password" name="new_password" placeholder="Nouveau mot de passe" required style="margin:0;">
                                <button type="submit" name="reset_password" style="padding:12px 14px; background:#111111; color:#fff;">Réinitialiser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>

</div></body></html>
