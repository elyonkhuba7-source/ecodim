<?php
require_once 'auth.php';
if (($_SESSION['user_role'] ?? '') !== 'admin') { header("Location: index.php"); exit; }

$message = "";
$dateChoisie = $_GET['date'] ?? date('Y-m-d');
$reportType = $_GET['type_rapport'] ?? 'journalier';
$allowedTypes = ['journalier', 'hebdomadaire', 'mensuel', 'annuel'];
if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = 'journalier';
}

if (isset($_POST['delete_finance'])) {
    $financeId = (int) ($_POST['finance_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM finances WHERE id = ?");
    $stmt->execute([$financeId]);
    $redirectDate = urlencode($_POST['current_date'] ?? $dateChoisie);
    $redirectType = urlencode($_POST['current_type_rapport'] ?? $reportType);
    header("Location: finances.php?date={$redirectDate}&type_rapport={$redirectType}&deleted=1");
    exit();
}

if (isset($_POST['save_finance'])) {
    $stmt = $pdo->prepare("INSERT INTO finances (date_finance, libelle, justificatif, montant, observation, created_by)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['date_finance'] ?? date('Y-m-d'),
        $_POST['libelle'] ?? 'offrande',
        trim((string) ($_POST['justificatif'] ?? '')),
        (float) ($_POST['montant'] ?? 0),
        trim((string) ($_POST['observation'] ?? '')),
        (int) ($_SESSION['user']['id'] ?? 0)
    ]);
    $message = "Le mouvement financier a été enregistré.";
    $dateChoisie = $_POST['date_finance'] ?? $dateChoisie;
}

function getFinancePeriod(string $date, string $type): array {
    $base = new DateTime($date);

    switch ($type) {
        case 'hebdomadaire':
            $dayOfWeek = (int) $base->format('N');
            $start = (clone $base)->modify('-' . ($dayOfWeek - 1) . ' days');
            $end = (clone $start)->modify('+6 days');
            return [
                'label' => 'Rapport hebdomadaire',
                'chip' => 'Hebdomadaire',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'display' => 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y'),
            ];
        case 'mensuel':
            $start = (clone $base)->modify('first day of this month');
            $end = (clone $base)->modify('last day of this month');
            return [
                'label' => 'Rapport mensuel',
                'chip' => 'Mensuel',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'display' => $base->format('m/Y'),
            ];
        case 'annuel':
            $start = (clone $base)->setDate((int) $base->format('Y'), 1, 1);
            $end = (clone $base)->setDate((int) $base->format('Y'), 12, 31);
            return [
                'label' => 'Rapport annuel',
                'chip' => 'Annuel',
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'display' => $base->format('Y'),
            ];
        case 'journalier':
        default:
            return [
                'label' => 'Rapport journalier',
                'chip' => 'Journalier',
                'start' => $base->format('Y-m-d'),
                'end' => $base->format('Y-m-d'),
                'display' => $base->format('d/m/Y'),
            ];
    }
}

$period = getFinancePeriod($dateChoisie, $reportType);

$resumeStmt = $pdo->prepare("SELECT
    SUM(CASE WHEN libelle = 'offrande' THEN montant ELSE 0 END) AS total_offrande,
    SUM(CASE WHEN libelle = 'don' THEN montant ELSE 0 END) AS total_don,
    SUM(CASE WHEN libelle = 'depense' THEN montant ELSE 0 END) AS total_depense,
    COUNT(*) AS total_mouvements
    FROM finances
    WHERE date_finance BETWEEN ? AND ?");
$resumeStmt->execute([$period['start'], $period['end']]);
$resume = $resumeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$offrandePeriode = (float) ($resume['total_offrande'] ?? 0);
$donPeriode = (float) ($resume['total_don'] ?? 0);
$depensePeriode = (float) ($resume['total_depense'] ?? 0);
$totalMouvements = (int) ($resume['total_mouvements'] ?? 0);
$montantEpargne = $offrandePeriode + $donPeriode - $depensePeriode;
$totalPeriode = $offrandePeriode + $donPeriode;

$resumeMoisStmt = $pdo->prepare("SELECT
    SUM(CASE WHEN libelle IN ('offrande', 'don') THEN montant ELSE -montant END) AS total_mensuel
    FROM finances
    WHERE DATE_FORMAT(date_finance, '%Y-%m') = ?");
$resumeMoisStmt->execute([substr($dateChoisie, 0, 7)]);
$totalMensuel = (float) ($resumeMoisStmt->fetchColumn() ?: 0);

$resumeAnnuelStmt = $pdo->prepare("SELECT
    SUM(CASE WHEN libelle IN ('offrande', 'don') THEN montant ELSE -montant END) AS total_annuel
    FROM finances
    WHERE YEAR(date_finance) = ?");
$resumeAnnuelStmt->execute([(int) substr($dateChoisie, 0, 4)]);
$totalAnnuel = (float) ($resumeAnnuelStmt->fetchColumn() ?: 0);

$mouvementsStmt = $pdo->prepare("SELECT * FROM finances WHERE date_finance BETWEEN ? AND ? ORDER BY date_finance DESC, id DESC");
$mouvementsStmt->execute([$period['start'], $period['end']]);
$mouvements = $mouvementsStmt->fetchAll(PDO::FETCH_ASSOC);

$observations = array_values(array_filter(array_map(static function (array $row): string {
    return trim((string) ($row['observation'] ?? ''));
}, $mouvements)));

require_once 'header.php';
?>
<section class="page-intro">
    <div>
        <p class="page-kicker">Finances</p>
        <h2 class="page-title">Finances et rapports</h2>
        <p class="page-subtitle">Retrouve clairement les mouvements, les totaux et les rapports financier journalier, hebdomadaire, mensuel et annuel dans une seule vue bien organisée.</p>
        <div class="stats-row">
            <div class="stat-pill"><?= htmlspecialchars($period['label']) ?></div>
            <div class="stat-pill"><?= htmlspecialchars($period['display']) ?></div>
            <div class="stat-pill"><?= $totalMouvements ?> mouvement(s)</div>
        </div>
    </div>
    <div class="hero-chip"><?= htmlspecialchars($period['chip']) ?></div>
</section>

<?php if ($message): ?>
    <div class="notice-success"><b><?= htmlspecialchars($message) ?></b></div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="notice-success"><b>Le mouvement financier a été supprimé.</b></div>
<?php endif; ?>

<section class="panel no-print">
    <h3 class="section-title">Enregistrer un mouvement</h3>
    <form method="POST" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div>
                <label>Date</label>
                <input type="date" name="date_finance" value="<?= htmlspecialchars($dateChoisie) ?>" required>
            </div>
            <div>
                <label>Libellé</label>
                <select name="libelle" required>
                    <option value="offrande">Offrande</option>
                    <option value="don">Don</option>
                    <option value="depense">Dépense</option>
                </select>
            </div>
            <div>
                <label>Montant</label>
                <input type="number" step="0.01" min="0" name="montant" placeholder="0.00" required>
            </div>
            <div>
                <label>Justificatif</label>
                <input type="text" name="justificatif" placeholder="Référence, reçu, détail">
            </div>
            <div class="form-full">
                <label>Observation</label>
                <textarea name="observation" rows="3" placeholder="Observation à remplir"></textarea>
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" name="save_finance" class="btn-submit">💰 Enregistrer le mouvement</button>
        </div>
    </form>
</section>

<section class="panel no-print">
    <h3 class="section-title">Afficher un rapport</h3>
    <form method="GET" style="margin-bottom:0; box-shadow:none; border:none; padding:0; background:transparent;">
        <div class="form-grid">
            <div>
                <label>Date de base</label>
                <input type="date" name="date" value="<?= htmlspecialchars($dateChoisie) ?>" required>
            </div>
            <div>
                <label>Type de rapport</label>
                <select name="type_rapport" required>
                    <option value="journalier" <?= $reportType === 'journalier' ? 'selected' : '' ?>>Journalier</option>
                    <option value="hebdomadaire" <?= $reportType === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
                    <option value="mensuel" <?= $reportType === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                    <option value="annuel" <?= $reportType === 'annuel' ? 'selected' : '' ?>>Annuel</option>
                </select>
            </div>
            <div class="form-full">
                <button type="submit" class="btn-submit">Afficher le rapport financier</button>
            </div>
        </div>
    </form>
</section>

<section class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:18px;">
        <div>
            <h3 class="section-title" style="margin-bottom:6px;"><?= htmlspecialchars($period['label']) ?></h3>
            <div style="color:#6d5d48; font-weight:700;"><?= htmlspecialchars($period['display']) ?></div>
        </div>
        <button onclick="window.print()" class="btn-submit no-print" style="width:auto; margin:0; background:#111111;">🖨️ Imprimer ce rapport</button>
    </div>

    <div class="dashboard-grid" style="margin-bottom:20px;">
        <article class="dashboard-card">
            <span class="dashboard-tag">📌 Total période</span>
            <h3><?= number_format($totalPeriode, 2, ',', ' ') ?></h3>
            <p>Total journalier ou total de la période sélectionnée avant déduction des dépenses.</p>
        </article>
        <article class="dashboard-card">
            <span class="dashboard-tag">🙏 Offrande</span>
            <h3><?= number_format($offrandePeriode, 2, ',', ' ') ?></h3>
            <p>Somme des offrandes sur la période affichée.</p>
        </article>
        <article class="dashboard-card">
            <span class="dashboard-tag">🎁 Don</span>
            <h3><?= number_format($donPeriode, 2, ',', ' ') ?></h3>
            <p>Somme des dons sur la période affichée.</p>
        </article>
        <article class="dashboard-card">
            <span class="dashboard-tag">💾 Épargne</span>
            <h3><?= number_format($montantEpargne, 2, ',', ' ') ?></h3>
            <p>Montant épargné après déduction des dépenses.</p>
        </article>
    </div>

    <div class="kpi-row">
        <div class="kpi-card success">Total mensuel : <?= number_format($totalMensuel, 2, ',', ' ') ?></div>
        <div class="kpi-card success">Total annuel : <?= number_format($totalAnnuel, 2, ',', ' ') ?></div>
        <div class="kpi-card danger">Dépenses de la période : <?= number_format($depensePeriode, 2, ',', ' ') ?></div>
    </div>
</section>

<section class="panel">
    <h3 class="section-title">Observations de la période</h3>
    <?php if (empty($observations)): ?>
        <div class="empty-state">Aucune observation enregistrée pour cette période.</div>
    <?php else: ?>
        <?php foreach ($observations as $index => $observation): ?>
            <div class="panel" style="margin-bottom:12px; background:#fffdf8; border-color:rgba(124,95,57,0.10);">
                <strong>Observation <?= $index + 1 ?> :</strong><br>
                <?= nl2br(htmlspecialchars($observation)) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section class="panel">
    <h3 class="section-title">Mouvements de la période</h3>
    <?php if (empty($mouvements)): ?>
        <div class="empty-state">Aucun mouvement enregistré pour cette période.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table style="margin-bottom:0;">
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Justificatif</th>
                    <th>Montant</th>
                    <th>Observation</th>
                    <th class="no-print">Action</th>
                </tr>
                <?php foreach ($mouvements as $mouvement): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($mouvement['date_finance'] ?? 'now'))) ?></td>
                        <td><?= htmlspecialchars(ucfirst((string) ($mouvement['libelle'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($mouvement['justificatif'] ?: '-') ?></td>
                        <td><?= number_format((float) ($mouvement['montant'] ?? 0), 2, ',', ' ') ?></td>
                        <td><?= nl2br(htmlspecialchars($mouvement['observation'] ?: '-')) ?></td>
                        <td class="no-print">
                            <form method="POST" style="margin:0; box-shadow:none; border:none; padding:0; background:transparent;" onsubmit="return confirm('Supprimer ce mouvement financier ?');">
                                <input type="hidden" name="finance_id" value="<?= (int) $mouvement['id'] ?>">
                                <input type="hidden" name="current_date" value="<?= htmlspecialchars($dateChoisie) ?>">
                                <input type="hidden" name="current_type_rapport" value="<?= htmlspecialchars($reportType) ?>">
                                <button type="submit" name="delete_finance" class="btn-danger" style="border:none;">🗑️ Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>

</div></body></html>
