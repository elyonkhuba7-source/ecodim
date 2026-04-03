<?php
require_once 'auth.php';
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

$u = $_SESSION['user'];
$role = $u['role'] ?? 'admin';
$classeIdUser = (int) ($u['classe_id'] ?? 0);
$message = "";
$showPdfNotice = $role === 'admin';

$classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$classeId = $role === 'admin' ? (int) ($_GET['classe_id'] ?? 0) : $classeIdUser;
$totalEvaluations = 0;

if ($role === 'admin' && isset($_POST['delete_evaluation'])) {
    $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
    $stmtDelete = $pdo->prepare("DELETE FROM evaluations WHERE id = ?");
    $stmtDelete->execute([$evaluationId]);
    $message = "La fiche d'évaluation a été supprimée.";
}

if (isset($_POST['save_evaluation'])) {
    $classeIdPost = $role === 'admin' ? (int) ($_POST['classe_id'] ?? 0) : $classeIdUser;
    $stmt = $pdo->prepare("INSERT INTO evaluations (
        classe_id, moniteur_id, moniteur_nom, type_evaluation, periode, theme, sous_theme, details, observations
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $classeIdPost,
        (int) ($u['id'] ?? 0),
        (string) ($u['nom_complet'] ?? $u['username'] ?? 'Moniteur'),
        trim((string) ($_POST['type_evaluation'] ?? '')),
        trim((string) ($_POST['periode'] ?? '')),
        trim((string) ($_POST['theme'] ?? '')),
        trim((string) ($_POST['sous_theme'] ?? '')),
        trim((string) ($_POST['details'] ?? '')),
        trim((string) ($_POST['observations'] ?? ''))
    ]);
    $message = "La fiche d'évaluation a été enregistrée.";
    $classeId = $classeIdPost;
}

if ($role === 'admin') {
    $sql = "SELECT e.*, c.nom AS classe_nom
        FROM evaluations e
        LEFT JOIN classes c ON c.id = e.classe_id";
    $params = [];
    if ($classeId > 0) {
        $sql .= " WHERE e.classe_id = ?";
        $params[] = $classeId;
    }
    $sql .= " ORDER BY e.created_at DESC, e.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT e.*, c.nom AS classe_nom
        FROM evaluations e
        LEFT JOIN classes c ON c.id = e.classe_id
        WHERE e.moniteur_id = ?
        ORDER BY e.created_at DESC, e.id DESC");
    $stmt->execute([(int) ($u['id'] ?? 0)]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$totalEvaluations = count($evaluations);

require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Évaluations</p>
        <h2 class="page-title">Fiche d'évaluation</h2>
        <p class="page-subtitle">Le moniteur choisit un seul type d’évaluation à la fois. L’administrateur voit toutes les fiches et tous les rapports associés.</p>
        <div class="stats-row">
            <div class="stat-pill"><?= $totalEvaluations ?> fiche(s)</div>
            <div class="stat-pill"><?= $role === 'admin' ? 'Lecture PDF uniquement' : 'Saisie + impression' ?></div>
        </div>
    </div>
    <div class="hero-chip"><?= $role === 'admin' ? 'Vue complète administrateur' : 'Vue moniteur' ?></div>
</section>

<?php if ($message): ?>
    <div class="notice-success"><b><?= htmlspecialchars($message) ?></b></div>
<?php endif; ?>

<?php if ($role !== 'admin' && $classeIdUser <= 0): ?>
    <section class="panel">
        <h3 class="section-title">Classe non assignée</h3>
        <div class="empty-state">Ce compte moniteur n'a pas encore de classe attribuée.</div>
    </section>
<?php else: ?>
    <?php if ($role !== 'admin'): ?>
        <section class="panel">
            <h3 class="section-title">Nouvelle fiche d'évaluation</h3>
            <div class="panel" style="margin-bottom:18px; background:linear-gradient(135deg,#fffdf8,#f7efe1); border-color:rgba(124,95,57,0.12);">
                <strong>Conseil :</strong> remplis une seule fiche par type d’évaluation pour garder une lecture claire dans les impressions.
            </div>
            <form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
                <div class="form-grid">
                    <div>
                        <label>Classe</label>
                        <input type="text" value="<?php
                            $classeNom = '';
                            foreach ($classes as $c) {
                                if ((int) $c['id'] === $classeIdUser) { $classeNom = $c['nom']; break; }
                            }
                            echo htmlspecialchars($classeNom ?: 'Aucune classe');
                        ?>" disabled>
                    </div>
                    <div>
                        <label>Moniteur / Monitrice</label>
                        <input type="text" value="<?= htmlspecialchars($u['nom_complet'] ?? $u['username'] ?? 'Moniteur') ?>" disabled>
                    </div>
                    <div>
                        <label>Période</label>
                        <input type="text" name="periode" placeholder="Exemple : Semaine 4" required>
                    </div>
                    <div>
                        <label>Type d'évaluation</label>
                        <select name="type_evaluation" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="Prise de note">Prise de note</option>
                            <option value="Interrogation">Interrogation</option>
                            <option value="Devoirs">Devoirs</option>
                            <option value="Assiduité">Assiduité</option>
                            <option value="TP">TP</option>
                            <option value="Évaluations générales">Évaluations générales</option>
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
                        <label>Détails</label>
                        <textarea name="details" rows="4" placeholder="Résultats, points mesurés, déroulement..."></textarea>
                    </div>
                    <div class="form-full">
                        <label>Observations</label>
                        <textarea name="observations" rows="4"></textarea>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <button type="submit" name="save_evaluation" class="btn-submit">🧾 Enregistrer la fiche d'évaluation</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="panel">
            <h3 class="section-title">Filtrer les fiches</h3>
            <form method="GET" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
                <div class="form-grid">
                    <div>
                        <label>Classe</label>
                        <select name="classe_id">
                            <option value="">-- Toutes les classes --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= $classeId === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; align-items:end;">
                        <button type="submit" class="btn-submit">Afficher</button>
                    </div>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h3 class="section-title"><?= $role === 'admin' ? 'Toutes les fiches d\'évaluation' : 'Mes fiches d\'évaluation' ?></h3>
        <?php if ($showPdfNotice): ?>
            <div class="panel" style="margin-bottom:18px; background:#f9f5ee; border-color:rgba(124,95,57,0.12);">
                <strong>Consultation administrateur :</strong> les fiches d'évaluation sont ouvertes uniquement au format PDF imprimable.
            </div>
        <?php endif; ?>
        <?php if (empty($evaluations)): ?>
            <div class="empty-state">Aucune fiche d'évaluation enregistrée.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table style="margin-bottom:0;">
                    <?php if ($role === 'admin'): ?>
                        <tr><th>Date</th><th>Classe</th><th>Moniteur</th><th>Type</th><th>PDF</th><th>Suppression</th></tr>
                    <?php else: ?>
                        <tr><th>Date</th><th>Classe</th><th>Moniteur</th><th>Type</th><th>Contenu</th><th>Impression</th></tr>
                    <?php endif; ?>
                    <?php foreach($evaluations as $evaluation): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($evaluation['created_at'] ?? 'now'))) ?></td>
                            <td><?= htmlspecialchars($evaluation['classe_nom'] ?: 'Aucune') ?></td>
                            <td><?= htmlspecialchars($evaluation['moniteur_nom'] ?: '-') ?></td>
                            <td><strong><?= htmlspecialchars($evaluation['type_evaluation'] ?: '-') ?></strong></td>
                            <?php if ($role === 'admin'): ?>
                                <td>
                                    <a href="evaluation_pdf.php?id=<?= (int) $evaluation['id'] ?>" target="_blank" class="dashboard-link">🖨️ Ouvrir le PDF</a>
                                </td>
                                <td>
                                    <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer cette fiche d\\'évaluation ?');">
                                        <input type="hidden" name="evaluation_id" value="<?= (int) $evaluation['id'] ?>">
                                        <button type="submit" name="delete_evaluation" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                                    </form>
                                </td>
                            <?php else: ?>
                                <td>
                                    <strong>Période :</strong> <?= htmlspecialchars($evaluation['periode'] ?: '-') ?><br>
                                    <strong>Thème :</strong> <?= htmlspecialchars($evaluation['theme'] ?: '-') ?><br>
                                    <strong>Sous-thème :</strong> <?= htmlspecialchars($evaluation['sous_theme'] ?: '-') ?><br>
                                    <strong>Détails :</strong> <?= nl2br(htmlspecialchars($evaluation['details'] ?: '-')) ?><br>
                                    <strong>Observations :</strong> <?= nl2br(htmlspecialchars($evaluation['observations'] ?: '-')) ?>
                                </td>
                                <td>
                                    <a href="evaluation_pdf.php?id=<?= (int) $evaluation['id'] ?>" target="_blank" class="dashboard-link">🖨️ Imprimer</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

</div></body></html>
