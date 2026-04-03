<?php
require_once 'auth.php';
$message = "";

if (isset($_POST['enregistrer_presences'])) {
    $date = $_POST['date_presence'];

    if (isset($_POST['statut_enfant'])) {
        $delete = $pdo->prepare("DELETE FROM presences WHERE inscription_id = ? AND date_presence = ?");
        $insert = $pdo->prepare("INSERT INTO presences (inscription_id, date_presence, statut) VALUES (?, ?, ?)");
        foreach ($_POST['statut_enfant'] as $enfant_id => $statut) {
            $delete->execute([(int) $enfant_id, $date]);
            $insert->execute([(int) $enfant_id, $date, $statut]);
        }
    }

    if (isset($_POST['statut_moniteur'])) {
        $delete = $pdo->prepare("DELETE FROM presences_moniteurs WHERE utilisateur_id = ? AND date_presence = ?");
        $insert = $pdo->prepare("INSERT INTO presences_moniteurs (utilisateur_id, classe_id, date_presence, statut) VALUES (?, ?, ?, ?)");
        foreach ($_POST['statut_moniteur'] as $moniteur_id => $statut) {
            $moniteurId = (int) $moniteur_id;
            if ($_SESSION['user_role'] === 'moniteur' && $moniteurId !== (int) ($_SESSION['user_id'] ?? 0)) {
                continue;
            }
            $delete->execute([$moniteurId, $date]);
            $insert->execute([$moniteurId, (int) ($_POST['classe_id'] ?? 0), $date, $statut]);
        }
    }

    $message = "Presences enregistrees avec succes.";
}

require_once 'header.php';
$date_choisie = $_GET['date'] ?? date('Y-m-d');
$classe_id = ($_SESSION['user_role'] === 'moniteur') ? $_SESSION['user_classe_id'] : ($_GET['classe_id'] ?? '');
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$classeNom = '';

if (!empty($classe_id)) {
    $stmtClasse = $pdo->prepare("SELECT nom FROM classes WHERE id = ?");
    $stmtClasse->execute([(int) $classe_id]);
    $classeNom = (string) ($stmtClasse->fetchColumn() ?: '');
}
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Présences</p>
        <h2 class="page-title">Gestion des présences</h2>
        <p class="page-subtitle">Choisis la date, charge la classe et enregistre l’appel dans une vue plus lisible sur téléphone comme sur ordinateur.</p>
    </div>
    <div class="hero-chip"><?= $_SESSION['user_role'] === 'admin' ? 'Vue administrateur' : ($classeNom ? 'Classe : ' . $classeNom : 'Aucune classe assignée') ?></div>
</section>

<?php if ($message): ?><div class="notice-success"><b><?= htmlspecialchars($message) ?></b></div><?php endif; ?>

<form method="GET" style="background: #e9ecef;">
    <div class="form-grid" style="align-items: flex-end;">
        <div><label>Date</label><input type="date" name="date" value="<?= htmlspecialchars($date_choisie) ?>" required></div>
        <div>
            <label>Classe</label>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <select name="classe_id" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($classe_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="classe_id" value="<?= htmlspecialchars($classe_id) ?>">
                <input type="text" disabled value="<?= htmlspecialchars($classeNom ?: 'Aucune classe assignée') ?>" style="background:#ddd;">
            <?php endif; ?>
        </div>
        <div class="form-full"><button type="submit" class="btn-submit">🔍 Afficher la liste</button></div>
    </div>
</form>

<?php if ($_SESSION['user_role'] === 'moniteur' && empty($classe_id)): ?>
    <div class="notice-error"><b>Ce compte moniteur n'a pas encore de classe assignée.</b></div>
<?php elseif (!empty($classe_id)): 
    $enfants = $pdo->prepare("SELECT i.id, i.enfant_nom, i.enfant_prenom, p.statut FROM inscriptions i LEFT JOIN presences p ON i.id = p.inscription_id AND p.date_presence = ? WHERE i.classe_id = ? ORDER BY i.enfant_nom ASC, i.enfant_prenom ASC");
    $enfants->execute([$date_choisie, $classe_id]); $enfants = $enfants->fetchAll();

    if ($_SESSION['user_role'] === 'admin') {
        $moniteurs = $pdo->prepare("SELECT u.id, u.nom_complet, u.username, p.statut FROM utilisateurs u LEFT JOIN presences_moniteurs p ON u.id = p.utilisateur_id AND p.date_presence = ? WHERE u.role = 'moniteur' AND u.classe_id = ? ORDER BY u.nom_complet ASC, u.username ASC");
        $moniteurs->execute([$date_choisie, $classe_id]);
    } else {
        $moniteurs = $pdo->prepare("SELECT u.id, u.nom_complet, u.username, p.statut FROM utilisateurs u LEFT JOIN presences_moniteurs p ON u.id = p.utilisateur_id AND p.date_presence = ? WHERE u.role = 'moniteur' AND u.id = ? ORDER BY u.nom_complet ASC, u.username ASC");
        $moniteurs->execute([$date_choisie, (int) ($_SESSION['user_id'] ?? 0)]);
    }
    $moniteurs = $moniteurs->fetchAll();
?>
<form method="POST">
    <input type="hidden" name="date_presence" value="<?= htmlspecialchars($date_choisie) ?>">
    <input type="hidden" name="classe_id" value="<?= htmlspecialchars($classe_id) ?>">
    <div class="panel" style="margin-bottom:20px; background:#f9f5ee; border-color:rgba(124,95,57,0.12);">
        <strong>Classe chargée :</strong> <?= htmlspecialchars($classeNom ?: 'Non définie') ?>
        <?php if (empty($enfants)): ?>
            <br>Aucun enfant n'est inscrit dans cette classe pour le moment.
        <?php endif; ?>
    </div>
    <h3>👨‍🏫 Moniteurs</h3>
    <div class="table-wrap">
    <table>
        <tr><th>Nom</th><th>Statut</th></tr>
        <?php foreach($moniteurs as $m): $statut = $m['statut'] ?? 'Absent'; ?>
        <tr>
            <td><?= htmlspecialchars($m['nom_complet'] ?: $m['username']) ?></td>
            <td>
                <div style="display:flex; flex-wrap:wrap; gap:14px;">
                    <label style="color:green; margin:0;"><input type="radio" name="statut_moniteur[<?= $m['id'] ?>]" value="Présent" <?= $statut=='Présent'?'checked':'' ?>> Présent</label>
                    <label style="color:red; margin:0;"><input type="radio" name="statut_moniteur[<?= $m['id'] ?>]" value="Absent" <?= $statut=='Absent'?'checked':'' ?>> Absent</label>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>

    <h3>👶 Enfants</h3>
    <div class="table-wrap">
    <table>
        <tr><th>Nom & Prénom</th><th>Statut</th></tr>
        <?php foreach($enfants as $e): $statut = $e['statut'] ?? 'Présent'; ?>
        <tr>
            <td><?= htmlspecialchars(trim(($e['enfant_nom'] ?? '') . ' ' . ($e['enfant_prenom'] ?? ''))) ?></td>
            <td>
                <div style="display:flex; flex-wrap:wrap; gap:14px;">
                    <label style="color:green; margin:0;"><input type="radio" name="statut_enfant[<?= $e['id'] ?>]" value="Présent" <?= $statut=='Présent'?'checked':'' ?>> Présent</label>
                    <label style="color:red; margin:0;"><input type="radio" name="statut_enfant[<?= $e['id'] ?>]" value="Absent" <?= $statut=='Absent'?'checked':'' ?>> Absent</label>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <button type="submit" name="enregistrer_presences" class="btn-submit">💾 Enregistrer l'appel</button>
</form>
<?php endif; ?>
</div></body></html>
