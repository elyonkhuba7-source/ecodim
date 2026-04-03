<?php
require_once 'auth.php';
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$u = $_SESSION['user'];
$role = $u['role'] ?? 'admin';
$classe_id_user = (int) ($u['classe_id'] ?? 0);
$message = "";

// Définir la classe à afficher
$classe_id = ($role === 'admin' && isset($_GET['classe'])) ? (int)$_GET['classe'] : $classe_id_user;
$classe_id = $classe_id > 0 ? $classe_id : 0;

$classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$moniteursClasse = [];
if ($classe_id > 0) {
    $stmtMoniteurs = $pdo->prepare("SELECT id, nom_complet, username FROM utilisateurs WHERE role = 'moniteur' AND classe_id = ? ORDER BY nom_complet ASC, username ASC");
    $stmtMoniteurs->execute([$classe_id]);
    $moniteursClasse = $stmtMoniteurs->fetchAll(PDO::FETCH_ASSOC);
}

// Ajouter Leçon
if (isset($_POST['add_lecon']) && $classe_id > 0) {
    $moniteurId = ($role === 'admin') ? (int) ($_POST['moniteur_id'] ?? 0) : (int) ($u['id'] ?? 0);
    $moniteurNom = (string) ($u['nom_complet'] ?? $u['username'] ?? 'Moniteur');

    if ($role === 'admin' && $moniteurId > 0) {
        $stmtMoniteur = $pdo->prepare("SELECT nom_complet, username FROM utilisateurs WHERE id = ? AND role = 'moniteur'");
        $stmtMoniteur->execute([$moniteurId]);
        $moniteurData = $stmtMoniteur->fetch(PDO::FETCH_ASSOC);
        if ($moniteurData) {
            $moniteurNom = (string) ($moniteurData['nom_complet'] ?: $moniteurData['username']);
        }
    }

    $isFinished = !empty($_POST['est_terminee']) ? 1 : 0;
    $ficheEvaluations = trim((string) ($_POST['fiche_evaluations'] ?? ''));
    $theme = trim((string) ($_POST['theme'] ?? ''));
    $passagesBibliques = trim((string) ($_POST['passages_bibliques'] ?? ''));
    $observations = trim((string) ($_POST['observations'] ?? ''));

    $stmt = $pdo->prepare("INSERT INTO lecons (
        classe_id, periode, titre_lecon, verset_cle, rapport, moniteur_id, moniteur_nom,
        theme, sous_theme, objectif_pedagogique, passages_bibliques, gestion_presences,
        activites_realisees, evaluations, fiche_evaluations, observations
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $classe_id,
        trim((string) ($_POST['periode'] ?? '')),
        $theme,
        $passagesBibliques,
        $observations,
        $moniteurId,
        $moniteurNom
        ,
        $theme,
        trim((string) ($_POST['sous_theme'] ?? '')),
        trim((string) ($_POST['objectif_pedagogique'] ?? '')),
        $passagesBibliques,
        trim((string) ($_POST['gestion_presences'] ?? '')),
        trim((string) ($_POST['activites_realisees'] ?? '')),
        trim((string) ($_POST['evaluations'] ?? '')),
        $ficheEvaluations,
        $observations
    ]);

    if ($isFinished) {
        $pdo->prepare("UPDATE lecons SET est_terminee = 1, date_fin = NOW() WHERE id = ?")->execute([(int) $pdo->lastInsertId()]);
    }

    $message = "Le rapport prof a été enregistré avec succès.";
}

if (isset($_POST['terminer_lecon'])) {
    $lessonId = (int) ($_POST['lecon_id'] ?? 0);
    $params = [$lessonId];
    $sql = "UPDATE lecons SET est_terminee = 1, date_fin = NOW() WHERE id = ?";
    if ($role !== 'admin') {
        $sql .= " AND moniteur_id = ?";
        $params[] = (int) ($u['id'] ?? 0);
    }
    $pdo->prepare($sql)->execute($params);
}

if (isset($_POST['supprimer_lecon'])) {
    if ($role !== 'admin') {
        $message = "Seul l'administrateur peut supprimer un rapport.";
    } else {
        $lessonId = (int) ($_POST['lecon_id'] ?? 0);
        $params = [$lessonId];
        $sql = "DELETE FROM lecons WHERE id = ?";
        $pdo->prepare($sql)->execute($params);
        $message = "Le rapport a été supprimé.";
    }
}
// Ajouter Présence
if (isset($_POST['add_presence']) && $classe_id > 0) {
    $stmt = $pdo->prepare("INSERT INTO presences (inscription_id, date_presence, statut) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['enfant_id'], $_POST['date_p'], $_POST['statut']]);
    $message = "La présence a été enregistrée.";
}

$enfants = [];
$lecons = [];
$classe_info = null;

if ($classe_id > 0) {
    $stmtEnfants = $pdo->prepare("SELECT * FROM inscriptions WHERE classe_id = ? ORDER BY enfant_nom ASC, enfant_prenom ASC");
    $stmtEnfants->execute([$classe_id]);
    $enfants = $stmtEnfants->fetchAll(PDO::FETCH_ASSOC);

    if ($role === 'admin') {
        $stmtLecons = $pdo->prepare("SELECT * FROM lecons WHERE classe_id = ? ORDER BY id DESC");
        $stmtLecons->execute([$classe_id]);
    } else {
        $stmtLecons = $pdo->prepare("SELECT * FROM lecons WHERE classe_id = ? AND moniteur_id = ? ORDER BY id DESC");
        $stmtLecons->execute([$classe_id, (int) ($u['id'] ?? 0)]);
    }
    $lecons = $stmtLecons->fetchAll(PDO::FETCH_ASSOC);

    $stmtClasse = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmtClasse->execute([$classe_id]);
    $classe_info = $stmtClasse->fetch(PDO::FETCH_ASSOC) ?: null;
}

if(!$classe_info) { $classe_info = ['nom' => 'Aucune classe']; }
require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Leçons</p>
        <h2 class="page-title">Classe : <?= htmlspecialchars($classe_info['nom']) ?></h2>
        <p class="page-subtitle">Consulte les enfants inscrits, fais l’appel, prépare le rapport prof par classe et clôture-le pour l’envoyer dans les rapports.</p>
    </div>
    <div class="hero-chip"><?= htmlspecialchars($u['nom_complet'] ?? $u['username'] ?? 'Utilisateur') ?></div>
</section>

<?php if ($message): ?>
    <div class="notice-success"><b><?= htmlspecialchars($message) ?></b></div>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<section class="panel">
    <h3 class="section-title">Choisir la classe</h3>
    <form method="GET" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div>
                <label>Classe</label>
                <select name="classe">
                    <?php foreach($classes as $classe): ?>
                        <option value="<?= $classe['id'] ?>" <?= $classe_id == $classe['id'] ? 'selected' : '' ?>><?= htmlspecialchars($classe['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; align-items:end;">
                <button type="submit" class="btn-submit">Afficher la classe</button>
            </div>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if ($classe_id <= 0): ?>
<section class="panel">
    <h3 class="section-title">Classe requise</h3>
    <div class="empty-state">
        <?= $role === 'admin'
            ? 'Choisis d’abord une classe pour gérer les présences et les rapports prof.'
            : 'Ce compte moniteur n’a pas encore de classe assignée.' ?>
    </div>
</section>
<?php else: ?>
<section class="panel">
<h3 class="section-title">1. Enfants enregistrés et présences</h3>
<div class="table-wrap">
<table><tr><th>Nom</th><th>Prénom</th><th>Sexe</th><th>Marquer Présence</th></tr>
<?php foreach($enfants as $e): ?>
    <tr>
        <td><?= htmlspecialchars($e['enfant_nom'] ?? '') ?></td><td><?= htmlspecialchars($e['enfant_prenom'] ?? '') ?></td><td><?= htmlspecialchars($e['enfant_sexe'] ?? '') ?></td>
        <td>
            <form method="POST" style="display:flex; gap:5px; align-items:center; margin:0; box-shadow:none; border:none; padding:0; background:transparent;">
                <input type="hidden" name="enfant_id" value="<?= $e['id'] ?>">
                <input type="date" name="date_p" value="<?= date('Y-m-d') ?>" required style="width:auto;">
                <select name="statut" style="width:auto;"><option value="Présent">Présent</option><option value="Absent">Absent</option></select>
                <button type="submit" name="add_presence" style="padding:10px 14px; background:#111111; color:#fff;">OK</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
</table>
</div>
</section>

<?php if ($role !== 'admin'): ?>
<section class="panel">
<h3 class="section-title">2. Ajouter un rapport prof</h3>
<form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
    <div class="form-grid">
        <div>
            <label>Période</label>
            <input type="text" name="periode" placeholder="Exemple : Dimanche 29" required>
        </div>
        <div>
            <label>Moniteur assigné</label>
            <?php if ($role === 'admin'): ?>
                <select name="moniteur_id" required>
                    <option value="">-- Sélectionner un moniteur --</option>
                    <?php foreach($moniteursClasse as $moniteur): ?>
                        <option value="<?= $moniteur['id'] ?>"><?= htmlspecialchars($moniteur['nom_complet'] ?: $moniteur['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="text" value="<?= htmlspecialchars($u['nom_complet'] ?? $u['username'] ?? 'Moniteur') ?>" disabled>
            <?php endif; ?>
        </div>
        <div>
            <label>Thème</label>
            <input type="text" name="theme" placeholder="Thème principal" required>
        </div>
        <div>
            <label>Sous-thème</label>
            <input type="text" name="sous_theme" placeholder="Sous-thème">
        </div>
        <div class="form-full">
            <label>Objectif pédagogique</label>
            <textarea name="objectif_pedagogique" rows="3" placeholder="Objectif visé pour la classe" required></textarea>
        </div>
        <div class="form-full">
            <label>Passages bibliques</label>
            <textarea name="passages_bibliques" placeholder="Exemple : Jean 3:16, Psaume 23..." rows="2"></textarea>
        </div>
        <div class="form-full">
            <label>Activités réalisées</label>
            <textarea name="activites_realisees" placeholder="Chants, jeux, révision, travaux pratiques..." rows="3"></textarea>
        </div>
        <div class="form-full">
            <label>Évaluations</label>
            <textarea name="evaluations" placeholder="Résultats globaux, difficultés, progrès..." rows="3"></textarea>
        </div>
        <div class="form-full">
            <label>Fiche d'évaluation principale</label>
            <select name="fiche_evaluations">
                <option value="">-- Sélectionner une seule fiche --</option>
                <?php foreach (['Prise de note', 'Interrogation', 'Devoirs', 'Assiduité', 'TP', 'Évaluations générales'] as $fiche): ?>
                    <option value="<?= htmlspecialchars($fiche) ?>"><?= htmlspecialchars($fiche) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-full">
            <label>Observations</label>
            <textarea name="observations" placeholder="Remarques finales du moniteur / de la monitrice..." rows="4"></textarea>
        </div>
        <div class="form-full">
            <label style="display:flex; gap:10px; align-items:center; font-weight:700;">
                <input type="checkbox" name="est_terminee" value="1" style="width:auto; margin:0;">
                Marquer ce rapport comme terminé
            </label>
        </div>
    </div>
    <div style="margin-top:16px;">
        <button type="submit" name="add_lecon" class="btn-submit">📖 Sauvegarder le rapport prof</button>
    </div>
</form>
</section>
<?php endif; ?>

<section class="panel">
<h3 class="section-title">Historique des rapports prof</h3>
<div class="table-wrap">
<table><tr><th>Période</th><th>Détails pédagogiques</th><th>Moniteur</th><th>Statut</th><th>Action</th></tr>
<?php foreach($lecons as $l): ?>
    <tr>
        <td><?= htmlspecialchars($l['periode'] ?? '') ?></td>
        <td>
            <b>Thème :</b> <?= htmlspecialchars($l['theme'] ?: $l['titre_lecon'] ?: '-') ?><br>
            <b>Sous-thème :</b> <?= htmlspecialchars($l['sous_theme'] ?: '-') ?><br>
            <b>Objectif :</b> <?= nl2br(htmlspecialchars($l['objectif_pedagogique'] ?: '-')) ?><br>
            <b>Passages :</b> <?= nl2br(htmlspecialchars($l['passages_bibliques'] ?: $l['verset_cle'] ?: '-')) ?><br>
            <b>Activités :</b> <?= nl2br(htmlspecialchars($l['activites_realisees'] ?: '-')) ?><br>
            <b>Évaluations :</b> <?= nl2br(htmlspecialchars($l['evaluations'] ?: '-')) ?><br>
            <b>Fiche :</b> <?= htmlspecialchars($l['fiche_evaluations'] ?: '-') ?><br>
            <b>Observations :</b> <?= nl2br(htmlspecialchars($l['observations'] ?: $l['rapport'] ?: '-')) ?>
        </td>
        <td><?= htmlspecialchars($l['moniteur_nom'] ?: 'Non renseigné') ?></td>
        <td><?= !empty($l['est_terminee']) ? 'Terminée' : 'En cours' ?></td>
        <td>
            <?php if ($role === 'admin' || (int) ($l['moniteur_id'] ?? 0) === (int) ($u['id'] ?? 0)): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if (empty($l['est_terminee'])): ?>
                        <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;">
                            <input type="hidden" name="lecon_id" value="<?= (int) $l['id'] ?>">
                            <button type="submit" name="terminer_lecon" style="padding:10px 14px; background:#1f8a70; color:#fff;">Clôturer</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                        <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer cette leçon ?');">
                            <input type="hidden" name="lecon_id" value="<?= (int) $l['id'] ?>">
                            <button type="submit" name="supprimer_lecon" style="padding:10px 14px; background:#d84023; color:#fff;">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?= !empty($l['date_fin']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($l['date_fin']))) : 'Aucune action' ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</table>
</div>
</section>
<?php endif; ?>
</div></body></html>
