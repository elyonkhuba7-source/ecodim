<?php
require_once 'auth.php';
if ($_SESSION['user_role'] !== 'admin') { die("<h3 style='color:red; text-align:center;'>⛔ Accès Refusé.</h3>"); }

if (isset($_POST['save_hebdo'])) {
    require_once 'db.php';
    $stmt = $pdo->prepare("INSERT INTO rapports_hebdomadaires (
        date_rapport, contexte, equipe_pedagogique, enseignements, moniteur, classe_id,
        theme, sous_theme, objectif_pedagogique, effectifs, presences, absences, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['date_rapport'] ?? date('Y-m-d'),
        trim((string) ($_POST['contexte'] ?? '')),
        trim((string) ($_POST['equipe_pedagogique'] ?? '')),
        trim((string) ($_POST['enseignements'] ?? '')),
        trim((string) ($_POST['moniteur'] ?? '')),
        (int) ($_POST['classe_id'] ?? 0),
        trim((string) ($_POST['theme'] ?? '')),
        trim((string) ($_POST['sous_theme'] ?? '')),
        trim((string) ($_POST['objectif_pedagogique'] ?? '')),
        (int) ($_POST['effectifs'] ?? 0),
        (int) ($_POST['presences'] ?? 0),
        (int) ($_POST['absences'] ?? 0),
        (int) ($_SESSION['user']['id'] ?? 0)
    ]);
    header("Location: rapports.php?type=hebdomadaire&success=1");
    exit();
}

if (isset($_POST['delete_lecon_report'])) {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $stmtDelete = $pdo->prepare("DELETE FROM lecons WHERE id = ?");
    $stmtDelete->execute([$reportId]);
    header("Location: rapports.php?type=lecons&deleted=1");
    exit();
}

if (isset($_POST['delete_hebdo_report'])) {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $stmtDelete = $pdo->prepare("DELETE FROM rapports_hebdomadaires WHERE id = ?");
    $stmtDelete->execute([$reportId]);
    header("Location: rapports.php?type=hebdomadaire&deleted=1");
    exit();
}

require_once 'header.php';

$type_rapport = $_GET['type'] ?? 'inscrits';
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$moniteurs = $pdo->query("SELECT id, nom_complet, username FROM utilisateurs WHERE role = 'moniteur' ORDER BY nom_complet ASC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-intro">
    <div>
        <p class="page-kicker">Rapports</p>
        <h2 class="page-title">Centre des rapports</h2>
        <p class="page-subtitle">Consulte les inscrits, les présences, les leçons terminées et les rapports hebdomadaires dans une interface plus propre et plus lisible sur mobile.</p>
    </div>
    <div class="hero-chip"><?= strtoupper(htmlspecialchars($type_rapport)) ?></div>
</section>

<?php if (isset($_GET['success'])): ?>
    <div class="notice-success"><b>Le rapport hebdomadaire a été enregistré avec succès.</b></div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="notice-success"><b>L'élément a été supprimé avec succès.</b></div>
<?php endif; ?>

<div class="no-print subnav-tabs">
    <a href="rapports.php?type=inscrits" class="btn-submit" style="background-color: <?= $type_rapport == 'inscrits' ? '#000' : '#888' ?>; width: auto; margin:0;">
        📑 Liste des Inscrits
    </a>
    <a href="rapports.php?type=presences" class="btn-submit" style="background-color: <?= $type_rapport == 'presences' ? '#000' : '#888' ?>; width: auto; margin:0;">
        📅 Historique des Présences
    </a>
    <a href="rapports.php?type=lecons" class="btn-submit" style="background-color: <?= $type_rapport == 'lecons' ? '#000' : '#888' ?>; width: auto; margin:0;">
        📖 Rapports des Leçons
    </a>
    <a href="rapports.php?type=hebdomadaire" class="btn-submit" style="background-color: <?= $type_rapport == 'hebdomadaire' ? '#000' : '#888' ?>; width: auto; margin:0;">
        🗓️ Hebdomadaire
    </a>
</div>

<?php 
// ==========================================
// RAPPORT 1 : ENFANTS INSCRITS
// ==========================================
if ($type_rapport == 'inscrits'): 
    $enfants = $pdo->query("SELECT i.*, c.nom as classe_nom FROM inscriptions i LEFT JOIN classes c ON i.classe_id = c.id ORDER BY c.nom ASC, i.enfant_nom ASC, i.enfant_prenom ASC")->fetchAll();
?>
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="margin:0;">📑 Rapport Global : <?= count($enfants) ?> Enfant(s) Inscrit(s)</h2>
        <button onclick="window.print()" class="btn-submit no-print" style="width: auto; margin:0; background: #007AFF;">🖨️ Imprimer la liste</button>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>Classe</th><th>Nom & Prénom</th><th>Lieu & Date de Naissance</th><th>Contact Tuteur</th></tr>
        </thead>
        <tbody>
            <?php foreach($enfants as $e): ?>
            <tr>
                <td style="font-weight: bold; background-color: #f9f9f9;"><?= htmlspecialchars($e['classe_nom'] ?? 'Aucune') ?></td>
                <td style="text-transform: uppercase; font-weight: bold;"><?= htmlspecialchars(trim(($e['enfant_nom'] ?? '') . ' ' . ($e['enfant_prenom'] ?? ''))) ?></td>
                <td><?= htmlspecialchars($e['enfant_ddn'] ?: '-') ?></td>
                <td><?= htmlspecialchars($e['urgence_contact'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($enfants)) echo "<tr><td colspan='4'>Aucun enfant inscrit.</td></tr>"; ?>
        </tbody>
    </table>
    </div>

<?php 
// ==========================================
// RAPPORT 2 : HISTORIQUE DES PRÉSENCES
// ==========================================
elseif ($type_rapport == 'presences'): 
    $date_choisie = $_GET['date'] ?? date('Y-m-d');
    $classe_id = $_GET['classe_id'] ?? '';
?>
    <h2 class="no-print">📅 Rechercher un appel passé</h2>
    <form method="GET" class="no-print" style="background: #e9ecef; border-top: 5px solid #007AFF;">
        <input type="hidden" name="type" value="presences">
        <div class="form-grid" style="align-items: flex-end;">
            <div><label>Date de la réunion</label><input type="date" name="date" value="<?= htmlspecialchars($date_choisie) ?>" required></div>
            <div>
                <label>Classe</label>
                <select name="classe_id" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($classe_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-full"><button type="submit" class="btn-submit" style="margin:0;">🔍 Générer le rapport</button></div>
        </div>
    </form>

    <?php 
    if (!empty($classe_id)):
        $nom_classe = $pdo->prepare("SELECT nom FROM classes WHERE id = ?");
        $nom_classe->execute([$classe_id]);
        $nom_classe = $nom_classe->fetchColumn();

        $stmt = $pdo->prepare("SELECT i.enfant_nom, i.enfant_prenom, p.statut FROM inscriptions i JOIN presences p ON i.id = p.inscription_id WHERE i.classe_id = ? AND p.date_presence = ? ORDER BY i.enfant_nom ASC, i.enfant_prenom ASC");
        $stmt->execute([$classe_id, $date_choisie]);
        $presences = $stmt->fetchAll();

        $total_presents = 0; $total_absents = 0;
        foreach ($presences as $p) { if ($p['statut'] == 'Présent') $total_presents++; else $total_absents++; }
    ?>
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
            <h2 style="margin:0;">Fiche de Présence : <?= htmlspecialchars($nom_classe) ?> (<?= date('d/m/Y', strtotime($date_choisie)) ?>)</h2>
            <button onclick="window.print()" class="btn-submit no-print" style="width: auto; margin:0; background: #007AFF;">🖨️ Imprimer</button>
        </div>

        <div class="kpi-row">
            <div class="kpi-card success">✅ Présents : <?= $total_presents ?></div>
            <div class="kpi-card danger">❌ Absents : <?= $total_absents ?></div>
        </div>

        <div class="table-wrap">
        <table>
            <thead><tr><th>Nom & Prénom de l'enfant</th><th>Statut</th></tr></thead>
            <tbody>
                <?php foreach($presences as $p): ?>
                <tr>
                    <td style="text-transform: uppercase;"><?= htmlspecialchars(trim(($p['enfant_nom'] ?? '') . ' ' . ($p['enfant_prenom'] ?? ''))) ?></td>
                    <td style="font-weight: bold; color: <?= $p['statut'] == 'Présent' ? 'green' : 'red' ?>;"><?= htmlspecialchars($p['statut']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($presences)) echo "<tr><td colspan='2'>Aucun appel enregistré pour cette date.</td></tr>"; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
<?php
// ==========================================
// RAPPORT 3 : LEÇONS PAR MONITEUR
// ==========================================
elseif ($type_rapport == 'lecons'):
    $classe_id = $_GET['classe_id'] ?? '';
    $moniteur_id = $_GET['moniteur_id'] ?? '';
?>
    <h2 class="no-print">📖 Rechercher les rapports de leçons</h2>
    <form method="GET" class="no-print" style="background: #e9ecef; border-top: 5px solid #007AFF;">
        <input type="hidden" name="type" value="lecons">
        <div class="form-grid" style="align-items: flex-end;">
            <div>
                <label>Classe</label>
                <select name="classe_id">
                    <option value="">-- Toutes les classes --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($classe_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Moniteur</label>
                <select name="moniteur_id">
                    <option value="">-- Tous les moniteurs --</option>
                    <?php foreach($moniteurs as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($moniteur_id == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nom_complet'] ?: $m['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-full"><button type="submit" class="btn-submit" style="margin:0;">🔍 Générer le rapport</button></div>
        </div>
    </form>

    <?php
    $sql = "SELECT l.*, c.nom AS classe_nom FROM lecons l LEFT JOIN classes c ON l.classe_id = c.id WHERE l.est_terminee = 1";
    $params = [];

    if ($classe_id !== '') {
        $sql .= " AND l.classe_id = ?";
        $params[] = $classe_id;
    }

    if ($moniteur_id !== '') {
        $sql .= " AND l.moniteur_id = ?";
        $params[] = $moniteur_id;
    }

    $sql .= " ORDER BY l.date_ajout DESC, l.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lecons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
        <h2 style="margin:0;">Rapports des Leçons : <?= count($lecons) ?> résultat(s)</h2>
        <button onclick="window.print()" class="btn-submit no-print" style="width: auto; margin:0; background: #007AFF;">🖨️ Imprimer</button>
    </div>

    <div class="table-wrap">
    <table>
        <thead><tr><th>Date</th><th>Classe</th><th>Détails pédagogiques</th><th>Moniteur</th><th>Observations</th><th>Suppression</th></tr></thead>
        <tbody>
            <?php foreach($lecons as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['periode'] ?: date('d/m/Y', strtotime($l['date_ajout'] ?? 'now'))) ?></td>
                    <td><?= htmlspecialchars($l['classe_nom'] ?: 'Aucune') ?></td>
                    <td>
                        <strong>Thème :</strong> <?= htmlspecialchars($l['theme'] ?: $l['titre_lecon'] ?: '-') ?><br>
                        <strong>Sous-thème :</strong> <?= htmlspecialchars($l['sous_theme'] ?: '-') ?><br>
                        <strong>Objectif :</strong> <?= nl2br(htmlspecialchars($l['objectif_pedagogique'] ?: '-')) ?><br>
                        <strong>Passages bibliques :</strong> <?= nl2br(htmlspecialchars($l['passages_bibliques'] ?: $l['verset_cle'] ?: '-')) ?><br>
                        <strong>Activités :</strong> <?= nl2br(htmlspecialchars($l['activites_realisees'] ?: '-')) ?><br>
                        <strong>Évaluations :</strong> <?= nl2br(htmlspecialchars($l['evaluations'] ?: '-')) ?><br>
                        <strong>Fiche d'évaluations :</strong> <?= htmlspecialchars($l['fiche_evaluations'] ?: '-') ?>
                    </td>
                    <td><?= htmlspecialchars($l['moniteur_nom'] ?: 'Non renseigné') ?></td>
                    <td><?= nl2br(htmlspecialchars($l['observations'] ?: $l['rapport'] ?: '')) ?></td>
                    <td>
                        <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer ce rapport prof ?');">
                            <input type="hidden" name="report_id" value="<?= (int) $l['id'] ?>">
                            <button type="submit" name="delete_lecon_report" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($lecons)) echo "<tr><td colspan='6'>Aucun rapport de leçon trouvé.</td></tr>"; ?>
        </tbody>
    </table>
    </div>
<?php
// ==========================================
// RAPPORT 4 : HEBDOMADAIRE
// ==========================================
elseif ($type_rapport == 'hebdomadaire'):
    $rapportsHebdo = $pdo->query("SELECT r.*, c.nom AS classe_nom
        FROM rapports_hebdomadaires r
        LEFT JOIN classes c ON c.id = r.classe_id
        ORDER BY r.date_rapport DESC, r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
    <h2 class="no-print">🗓️ Ajouter un rapport hebdomadaire</h2>
    <form method="POST" class="no-print" style="background: #e9ecef; border-top: 5px solid #007AFF;">
        <div class="form-grid">
            <div>
                <label>Date par rapport aux enseignements</label>
                <input type="date" name="date_rapport" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>
            <div>
                <label>Moniteur</label>
                <select name="moniteur" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach($moniteurs as $m): ?>
                        <?php $moniteurNom = $m['nom_complet'] ?: $m['username']; ?>
                        <option value="<?= htmlspecialchars($moniteurNom) ?>"><?= htmlspecialchars($moniteurNom) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-full">
                <label>Contexte</label>
                <textarea name="contexte" rows="2" required></textarea>
            </div>
            <div class="form-full">
                <label>Équipe pédagogique</label>
                <textarea name="equipe_pedagogique" rows="2"></textarea>
            </div>
            <div class="form-full">
                <label>Enseignements</label>
                <textarea name="enseignements" rows="2"></textarea>
            </div>
            <div>
                <label>Classe</label>
                <select name="classe_id" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Thème</label>
                <input type="text" name="theme" required>
            </div>
            <div>
                <label>Sous-thème</label>
                <input type="text" name="sous_theme">
            </div>
            <div class="form-full">
                <label>Objectif pédagogique</label>
                <textarea name="objectif_pedagogique" rows="2"></textarea>
            </div>
            <div>
                <label>Effectifs</label>
                <input type="number" name="effectifs" min="0" value="0">
            </div>
            <div>
                <label>Présences</label>
                <input type="number" name="presences" min="0" value="0">
            </div>
            <div>
                <label>Absences</label>
                <input type="number" name="absences" min="0" value="0">
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" name="save_hebdo" class="btn-submit">🗓️ Enregistrer le rapport hebdomadaire</button>
        </div>
    </form>

    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">
        <h2 style="margin:0;">Rapports hebdomadaires : <?= count($rapportsHebdo) ?> résultat(s)</h2>
        <button onclick="window.print()" class="btn-submit no-print" style="width: auto; margin:0; background: #007AFF;">🖨️ Imprimer</button>
    </div>

    <div class="table-wrap">
    <table>
        <thead><tr><th>Date</th><th>Classe</th><th>Moniteur</th><th>Contenu</th><th>Effectif</th><th>Suppression</th></tr></thead>
        <tbody>
            <?php foreach($rapportsHebdo as $r): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rapport'] ?? 'now'))) ?></td>
                    <td><?= htmlspecialchars($r['classe_nom'] ?: 'Aucune') ?></td>
                    <td><?= htmlspecialchars($r['moniteur'] ?: '-') ?></td>
                    <td>
                        <strong>Contexte :</strong> <?= nl2br(htmlspecialchars($r['contexte'] ?: '-')) ?><br>
                        <strong>Équipe pédagogique :</strong> <?= nl2br(htmlspecialchars($r['equipe_pedagogique'] ?: '-')) ?><br>
                        <strong>Enseignements :</strong> <?= nl2br(htmlspecialchars($r['enseignements'] ?: '-')) ?><br>
                        <strong>Thème :</strong> <?= htmlspecialchars($r['theme'] ?: '-') ?><br>
                        <strong>Sous-thème :</strong> <?= htmlspecialchars($r['sous_theme'] ?: '-') ?><br>
                        <strong>Objectif pédagogique :</strong> <?= nl2br(htmlspecialchars($r['objectif_pedagogique'] ?: '-')) ?>
                    </td>
                    <td>
                        Effectifs : <?= (int) ($r['effectifs'] ?? 0) ?><br>
                        Présences : <?= (int) ($r['presences'] ?? 0) ?><br>
                        Absences : <?= (int) ($r['absences'] ?? 0) ?>
                    </td>
                    <td>
                        <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer ce rapport hebdomadaire ?');">
                            <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" name="delete_hebdo_report" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($rapportsHebdo)) echo "<tr><td colspan='6'>Aucun rapport hebdomadaire trouvé.</td></tr>"; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</div></body></html>
