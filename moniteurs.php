<?php
require_once 'auth.php';
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') != 'admin') { header("Location: index.php"); exit; }
$message = "";
$erreur = "";

function isStrongPassword(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }

    $hasLetter = (bool) preg_match('/[A-Za-z]/', $password);
    $hasDigit = (bool) preg_match('/\d/', $password);

    return $hasLetter && $hasDigit;
}

if (isset($_POST['add'])) {
    $username = trim($_POST['user']);
    $password = $_POST['pass'] ?? '';

    if (!isStrongPassword($password)) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères avec des lettres et des chiffres.";
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE username = ?");
        $check->execute([$username]);

        if ((int) $check->fetchColumn() > 0) {
            $erreur = "Cet identifiant existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (
                username, password, nom_complet, sexe, telephone, email, adresse, fonction_moniteur,
                date_naissance, date_service, observations, role, classe_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'moniteur', ?)");
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                trim($_POST['nom_complet']) ?: $username,
                $_POST['sexe'] ?? 'M',
                trim((string) ($_POST['telephone'] ?? '')),
                trim((string) ($_POST['email'] ?? '')),
                trim((string) ($_POST['adresse'] ?? '')),
                trim((string) ($_POST['fonction_moniteur'] ?? '')),
                !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
                !empty($_POST['date_service']) ? $_POST['date_service'] : null,
                trim((string) ($_POST['observations'] ?? '')),
                (int) $_POST['classe_id']
            ]);
            $message = "Le compte moniteur a été créé avec succès.";
        }
    }
}

if (isset($_POST['update_assignment'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $classeId = (int) ($_POST['classe_id'] ?? 0);
    $nomComplet = trim($_POST['nom_complet'] ?? '');

    if ($userId <= 0) {
        $erreur = "Moniteur introuvable.";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET
            nom_complet = ?,
            sexe = ?,
            telephone = ?,
            email = ?,
            adresse = ?,
            fonction_moniteur = ?,
            date_naissance = ?,
            date_service = ?,
            observations = ?,
            classe_id = ?
            WHERE id = ? AND role = 'moniteur'");
        $stmt->execute([
            $nomComplet ?: 'Moniteur',
            $_POST['sexe'] ?? 'M',
            trim((string) ($_POST['telephone'] ?? '')),
            trim((string) ($_POST['email'] ?? '')),
            trim((string) ($_POST['adresse'] ?? '')),
            trim((string) ($_POST['fonction_moniteur'] ?? '')),
            !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            !empty($_POST['date_service']) ? $_POST['date_service'] : null,
            trim((string) ($_POST['observations'] ?? '')),
            $classeId,
            $userId
        ]);
        $message = "Le profil du moniteur a été mis à jour.";
    }
}

if (isset($_POST['toggle_block'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $targetState = (int) ($_POST['target_state'] ?? 0);

    if ($userId <= 0) {
        $erreur = "Moniteur introuvable.";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET est_bloque = ? WHERE id = ? AND role = 'moniteur'");
        $stmt->execute([$targetState ? 1 : 0, $userId]);
        $message = $targetState ? "Le moniteur a été bloqué." : "Le moniteur a été débloqué.";
    }
}

if (isset($_POST['delete_moniteur'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        $erreur = "Moniteur introuvable.";
    } else {
        $pdo->prepare("DELETE FROM presences_moniteurs WHERE utilisateur_id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE lecons SET moniteur_id = 0 WHERE moniteur_id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE evaluations SET moniteur_id = 0 WHERE moniteur_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND role = 'moniteur'")->execute([$userId]);
        $message = "Le moniteur a été supprimé.";
    }
}

$classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);
$moniteurs = $pdo->query("SELECT u.*, c.nom as c_nom FROM utilisateurs u LEFT JOIN classes c ON u.classe_id = c.id WHERE u.role = 'moniteur' ORDER BY u.nom_complet ASC, u.username ASC")->fetchAll(PDO::FETCH_ASSOC);
require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Moniteurs</p>
        <h2 class="page-title">Créer et gérer les comptes moniteurs</h2>
        <p class="page-subtitle">Tu peux maintenant enregistrer les informations complètes de chaque moniteur : identité, contact, fonction, dates utiles, classe et statut d’accès.</p>
    </div>
    <div class="hero-chip"><?= count($moniteurs) ?> moniteur(s)</div>
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
    <h3 class="section-title">Ajouter un moniteur</h3>
    <form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div>
                <label>Nom complet</label>
                <input type="text" name="nom_complet" placeholder="Exemple : Jean Mukendi" required>
            </div>
            <div>
                <label>Identifiant</label>
                <input type="text" name="user" placeholder="Exemple : jean" required>
            </div>
            <div>
                <label>Sexe</label>
                <select name="sexe" required>
                    <option value="M">Homme</option>
                    <option value="F">Femme</option>
                </select>
            </div>
            <div>
                <label>Mot de passe</label>
                <input type="text" name="pass" placeholder="Minimum 8 caractères avec lettres et chiffres" required>
            </div>
            <div>
                <label>Classe assignée</label>
                <select name="classe_id" required>
                    <option value="">-- Assigner à une classe --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Téléphone</label>
                <input type="text" name="telephone" placeholder="Numéro de téléphone">
            </div>
            <div>
                <label>Adresse mail</label>
                <input type="email" name="email" placeholder="exemple@email.com">
            </div>
            <div class="form-full">
                <label>Adresse</label>
                <input type="text" name="adresse" placeholder="Adresse complète">
            </div>
            <div>
                <label>Fonction / service</label>
                <input type="text" name="fonction_moniteur" placeholder="Exemple : Moniteur principal">
            </div>
            <div>
                <label>Date de naissance</label>
                <input type="date" name="date_naissance">
            </div>
            <div>
                <label>Date de début de service</label>
                <input type="date" name="date_service">
            </div>
            <div class="form-full">
                <label>Observations</label>
                <textarea name="observations" rows="3" placeholder="Note interne sur le moniteur"></textarea>
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" name="add" class="btn-submit">👨‍🏫 Créer le compte moniteur</button>
        </div>
    </form>
</section>

<section class="panel">
    <h3 class="section-title">Liste des moniteurs</h3>
    <?php if (empty($moniteurs)): ?>
        <div class="empty-state">Aucun moniteur n'a encore été enregistré.</div>
    <?php else: ?>
        <div class="panel" style="margin-bottom:18px; background:#f9f5ee; border-color:rgba(124,95,57,0.12);">
            <strong>Rappel :</strong> si un enfant est ajouté dans une autre classe, il n’apparaîtra pas chez ce moniteur.
        </div>
        <div class="table-wrap">
            <table style="margin-bottom:0;">
                <tr><th>Profil complet</th><th>Statut</th><th>Classe assignée</th><th>Mise à jour</th><th>Sécurité</th><th>Suppression</th></tr>
                <?php foreach($moniteurs as $m): ?>
                    <tr>
                        <td>
                            <div style="font-weight:800;"><?= htmlspecialchars($m['nom_complet'] ?: $m['username']) ?></div>
                            <div style="color:#6d5d48; font-size:13px; margin-top:4px;"><?= htmlspecialchars($m['username']) ?></div>
                            <div style="color:#3f352b; font-size:13px; margin-top:8px; line-height:1.6;">
                                Sexe : <?= htmlspecialchars(($m['sexe'] ?? 'M') === 'F' ? 'Femme' : 'Homme') ?><br>
                                Téléphone : <?= htmlspecialchars($m['telephone'] ?: '-') ?><br>
                                Email : <?= htmlspecialchars($m['email'] ?: '-') ?><br>
                                Adresse : <?= htmlspecialchars($m['adresse'] ?: '-') ?><br>
                                Fonction : <?= htmlspecialchars($m['fonction_moniteur'] ?: '-') ?><br>
                                Naissance : <?= htmlspecialchars($m['date_naissance'] ?: '-') ?><br>
                                Début de service : <?= htmlspecialchars($m['date_service'] ?: '-') ?><br>
                                Observations : <?= nl2br(htmlspecialchars($m['observations'] ?: '-')) ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($m['est_bloque'])): ?>
                                <span style="display:inline-flex; padding:8px 12px; border-radius:999px; background:#ffebee; color:#b71c1c; font-weight:800;">Bloqué</span>
                            <?php else: ?>
                                <span style="display:inline-flex; padding:8px 12px; border-radius:999px; background:#e8f5e9; color:#1b5e20; font-weight:800;">Actif</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($m['c_nom'] ?: 'Aucune classe') ?></strong></td>
                        <td style="min-width:420px;">
                            <form method="POST" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; align-items:center; margin:0; box-shadow:none; border:none; padding:0; background:transparent;">
                                <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                                <input type="text" name="nom_complet" value="<?= htmlspecialchars($m['nom_complet'] ?: $m['username']) ?>" placeholder="Nom complet" required style="margin:0;">
                                <select name="sexe" style="margin:0;" required>
                                    <option value="M" <?= (($m['sexe'] ?? 'M') === 'M') ? 'selected' : '' ?>>Homme</option>
                                    <option value="F" <?= (($m['sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Femme</option>
                                </select>
                                <select name="classe_id" style="margin:0;" required>
                                    <option value="">-- Classe --</option>
                                    <?php foreach($classes as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= ((int) $m['classe_id'] === (int) $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="telephone" value="<?= htmlspecialchars($m['telephone'] ?? '') ?>" placeholder="Téléphone" style="margin:0;">
                                <input type="email" name="email" value="<?= htmlspecialchars($m['email'] ?? '') ?>" placeholder="Adresse mail" style="margin:0;">
                                <input type="text" name="adresse" value="<?= htmlspecialchars($m['adresse'] ?? '') ?>" placeholder="Adresse" style="margin:0;">
                                <input type="text" name="fonction_moniteur" value="<?= htmlspecialchars($m['fonction_moniteur'] ?? '') ?>" placeholder="Fonction" style="margin:0;">
                                <input type="date" name="date_naissance" value="<?= htmlspecialchars($m['date_naissance'] ?? '') ?>" style="margin:0;">
                                <input type="date" name="date_service" value="<?= htmlspecialchars($m['date_service'] ?? '') ?>" style="margin:0;">
                                <div class="form-full" style="grid-column:1 / -1;">
                                    <textarea name="observations" rows="3" placeholder="Observations" style="margin:0;"><?= htmlspecialchars($m['observations'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="update_assignment" style="padding:12px 14px; background:#1f8a70; color:#fff; grid-column:1 / -1;">Mettre à jour les informations</button>
                            </form>
                        </td>
                        <td style="min-width:280px;">
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="securite.php" class="dashboard-link" style="padding:12px 14px;">Mots de passe</a>
                                <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;">
                                    <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                                    <input type="hidden" name="target_state" value="<?= !empty($m['est_bloque']) ? 0 : 1 ?>">
                                    <button type="submit" name="toggle_block" style="padding:12px 14px; background:<?= !empty($m['est_bloque']) ? '#1f8a70' : '#111111' ?>; color:#fff; border:none; border-radius:12px;">
                                        <?= !empty($m['est_bloque']) ? 'Débloquer' : 'Bloquer' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td style="min-width:160px;">
                            <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer ce moniteur ? Son compte sera effacé, mais ses rapports historiques seront conservés.');">
                                <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>">
                                <button type="submit" name="delete_moniteur" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>
</div></body></html>
