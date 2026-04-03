<?php
require_once 'auth.php';
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    die("<h3 style='color:red; text-align:center;'>⛔ Accès Refusé. Seul l'administrateur peut modifier les classes.</h3>");
}

$message = "";
$erreur = "";

if (isset($_POST['ajouter_classe']) && !empty(trim((string) ($_POST['nom'] ?? '')))) {
    try {
        $stmt = $pdo->prepare("INSERT INTO classes (nom) VALUES (?)");
        $stmt->execute([trim((string) $_POST['nom'])]);
        $message = "La classe a été ajoutée avec succès.";
    } catch (Exception $e) {
        $erreur = "Erreur lors de l'ajout : " . $e->getMessage();
    }
}

if (isset($_POST['supprimer_classe'])) {
    $classId = (int) ($_POST['classe_id'] ?? 0);

    try {
        $stmtCheck = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM inscriptions WHERE classe_id = ?) AS total_enfants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'moniteur' AND classe_id = ?) AS total_moniteurs,
            (SELECT COUNT(*) FROM lecons WHERE classe_id = ?) AS total_lecons,
            (SELECT COUNT(*) FROM evaluations WHERE classe_id = ?) AS total_evaluations,
            (SELECT COUNT(*) FROM rapports_hebdomadaires WHERE classe_id = ?) AS total_rapports");
        $stmtCheck->execute([$classId, $classId, $classId, $classId, $classId]);
        $usage = $stmtCheck->fetch(PDO::FETCH_ASSOC) ?: [];

        $hasUsage = ((int) ($usage['total_enfants'] ?? 0)) > 0
            || ((int) ($usage['total_moniteurs'] ?? 0)) > 0
            || ((int) ($usage['total_lecons'] ?? 0)) > 0
            || ((int) ($usage['total_evaluations'] ?? 0)) > 0
            || ((int) ($usage['total_rapports'] ?? 0)) > 0;

        if ($hasUsage) {
            $erreur = "Cette classe ne peut pas être supprimée parce qu'elle contient encore des enfants, moniteurs ou rapports liés.";
        } else {
            $stmtDelete = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmtDelete->execute([$classId]);
            $message = "La classe a été supprimée.";
        }
    } catch (Exception $e) {
        $erreur = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

$classes = $pdo->query("
    SELECT
        c.id,
        c.nom,
        (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id) AS total_enfants,
        (SELECT COUNT(*) FROM utilisateurs u WHERE u.role = 'moniteur' AND u.classe_id = c.id) AS total_moniteurs,
        (SELECT COUNT(*) FROM lecons l WHERE l.classe_id = c.id) AS total_lecons,
        (SELECT COUNT(*) FROM evaluations e WHERE e.classe_id = c.id) AS total_evaluations,
        (SELECT COUNT(*) FROM rapports_hebdomadaires r WHERE r.classe_id = c.id) AS total_rapports
    FROM classes c
    ORDER BY c.nom ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalClasses = count($classes);
$totalEnfants = 0;
$totalMoniteurs = 0;
foreach ($classes as $classe) {
    $totalEnfants += (int) ($classe['total_enfants'] ?? 0);
    $totalMoniteurs += (int) ($classe['total_moniteurs'] ?? 0);
}

require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Classes</p>
        <h2 class="page-title">Gestion des classes</h2>
        <p class="page-subtitle">Crée les classes, vois rapidement leurs effectifs, les moniteurs liés et les rapports déjà produits, puis supprime seulement les classes vraiment vides.</p>
        <div class="stats-row">
            <div class="stat-pill"><?= $totalClasses ?> classe(s)</div>
            <div class="stat-pill"><?= $totalEnfants ?> enfant(s)</div>
            <div class="stat-pill"><?= $totalMoniteurs ?> moniteur(s)</div>
        </div>
    </div>
    <div class="hero-chip">Vue administrative</div>
</section>

<?php if ($message): ?>
    <div class="notice-success"><b><?= htmlspecialchars($message) ?></b></div>
<?php endif; ?>

<?php if ($erreur): ?>
    <div class="notice-error"><b><?= htmlspecialchars($erreur) ?></b></div>
<?php endif; ?>

<section class="panel">
    <h3 class="section-title">Créer une nouvelle classe</h3>
    <form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div class="form-full">
                <label>Nom de la classe</label>
                <input type="text" name="nom" placeholder="Exemple : Les Flambeaux" required>
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" name="ajouter_classe" class="btn-submit">➕ Ajouter la classe</button>
        </div>
    </form>
</section>

<section class="panel">
    <h3 class="section-title">Rapport des classes</h3>
    <?php if (empty($classes)): ?>
        <div class="empty-state">Aucune classe n'a encore été créée.</div>
    <?php else: ?>
        <div class="dashboard-grid" style="margin-bottom:20px;">
            <?php foreach ($classes as $classe): ?>
                <article class="dashboard-card">
                    <span class="dashboard-tag">📚 Classe</span>
                    <h3><?= htmlspecialchars($classe['nom']) ?></h3>
                    <div class="stats-row">
                        <div class="stat-pill"><?= (int) $classe['total_enfants'] ?> enfant(s)</div>
                        <div class="stat-pill"><?= (int) $classe['total_moniteurs'] ?> moniteur(s)</div>
                    </div>
                    <div class="stats-row">
                        <div class="stat-pill"><?= (int) $classe['total_lecons'] ?> rapport(s) prof</div>
                        <div class="stat-pill"><?= (int) $classe['total_evaluations'] ?> fiche(s) d'évaluation</div>
                        <div class="stat-pill"><?= (int) $classe['total_rapports'] ?> rapport(s) hebdo</div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <table style="margin-bottom:0;">
                <tr>
                    <th>Classe</th>
                    <th>Enfants</th>
                    <th>Moniteurs</th>
                    <th>Rapports</th>
                    <th>Suppression</th>
                </tr>
                <?php foreach ($classes as $classe): ?>
                    <?php
                    $isDeletable = ((int) $classe['total_enfants']) === 0
                        && ((int) $classe['total_moniteurs']) === 0
                        && ((int) $classe['total_lecons']) === 0
                        && ((int) $classe['total_evaluations']) === 0
                        && ((int) $classe['total_rapports']) === 0;
                    ?>
                    <tr>
                        <td style="font-weight:800; font-size:18px;"><?= htmlspecialchars($classe['nom']) ?></td>
                        <td><?= (int) $classe['total_enfants'] ?></td>
                        <td><?= (int) $classe['total_moniteurs'] ?></td>
                        <td>
                            Prof : <?= (int) $classe['total_lecons'] ?><br>
                            Évaluations : <?= (int) $classe['total_evaluations'] ?><br>
                            Hebdo : <?= (int) $classe['total_rapports'] ?>
                        </td>
                        <td style="width:180px;">
                            <?php if ($isDeletable): ?>
                                <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer cette classe vide ?');">
                                    <input type="hidden" name="classe_id" value="<?= (int) $classe['id'] ?>">
                                    <button type="submit" name="supprimer_classe" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#6d5d48; font-weight:700;">Classe utilisée</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>

</div></body></html>
