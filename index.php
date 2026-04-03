<?php
require_once 'header.php';

$u = $_SESSION['user'] ?? [];
$role = $u['role'] ?? 'admin';
$classe_id_user = (int)($u['classe_id'] ?? 0);

if ($role === 'admin') {
    $sql = "SELECT * FROM classes ORDER BY nom ASC";
    $statsLabel = "Vue administrateur";
    $totalInscriptions = (int) $pdo->query("SELECT COUNT(*) FROM inscriptions")->fetchColumn();
} else {
    $sql = $classe_id_user > 0
        ? "SELECT * FROM classes WHERE id = " . $classe_id_user
        : "SELECT * FROM classes WHERE 1 = 0";
    $statsLabel = "Profil moniteur";
    $totalInscriptions = $classe_id_user > 0
        ? (int) $pdo->query("SELECT COUNT(*) FROM inscriptions WHERE classe_id = " . $classe_id_user)->fetchColumn()
        : 0;
}

$classes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$totalClasses = count($classes);
$recentChildren = [];

if ($role !== 'admin' && $classe_id_user > 0) {
    $stmt = $pdo->prepare("SELECT enfant_nom, enfant_prenom, date_inscription
        FROM inscriptions
        WHERE classe_id = ?
        ORDER BY id DESC
        LIMIT 8");
    $stmt->execute([$classe_id_user]);
    $recentChildren = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<section class="page-intro">
    <div>
        <p class="page-kicker">Tableau de bord</p>
        <h2 class="page-title">Bienvenue, <?= htmlspecialchars($u['nom_complet'] ?? $u['username'] ?? 'Administrateur') ?></h2>
        <p class="page-subtitle">Retrouvez rapidement les classes, les effectifs et l'accès aux leçons dans un espace plus clair et plus agréable à utiliser.</p>
        <div class="stats-row">
            <div class="stat-pill"><?= $totalClasses ?> classe(s)</div>
            <div class="stat-pill"><?= $totalInscriptions ?> enfant(s) inscrit(s)</div>
        </div>
    </div>
    <div class="hero-chip"><?= htmlspecialchars($statsLabel) ?></div>
</section>

<?php if ($role !== 'admin' && $classe_id_user <= 0): ?>
    <section class="panel">
        <h3 class="section-title">Classe non assignée</h3>
        <div class="empty-state">
            Ce compte moniteur n'a pas encore de classe attribuée. Depuis l'administration, ouvre la page <a href="moniteurs.php">Moniteurs</a> pour lui affecter une classe.
        </div>
    </section>
<?php elseif (empty($classes)): ?>
    <section class="panel">
        <h3 class="section-title">Aucune classe pour le moment</h3>
        <div class="empty-state">
            Commencez par créer une classe dans <a href="parametres.php">la page Classes</a> pour alimenter le tableau de bord.
        </div>
    </section>
<?php elseif ($role !== 'admin'): ?>
    <?php $classe = $classes[0]; ?>
    <section>
        <div class="dashboard-grid">
            <article class="dashboard-card">
                <span class="dashboard-tag">📚 Ma classe</span>
                <h3><?= htmlspecialchars($classe['nom']) ?></h3>
                <p><strong><?= $totalInscriptions ?></strong> enfant(s) inscrits dans la classe que vous suivez.</p>
                <a href="presences.php" class="dashboard-link">Faire l'appel</a>
            </article>
            <article class="dashboard-card">
                <span class="dashboard-tag">📝 Ma leçon</span>
                <h3>Préparer et clôturer la leçon</h3>
                <p>Saisissez la période, le contenu de la leçon et cochez la fin pour l’envoyer dans les rapports.</p>
                <a href="lecons.php" class="dashboard-link">Gérer ma leçon</a>
            </article>
            <article class="dashboard-card">
                <span class="dashboard-tag">🔄 Synchronisation</span>
                <h3>Enfants visibles dans ma classe</h3>
                <?php if (empty($recentChildren)): ?>
                    <p>Aucun enfant n'est encore enregistré dans cette classe.</p>
                <?php else: ?>
                    <p>Les derniers enregistrements de ta classe apparaissent ici dès que la page est rechargée.</p>
                    <div class="stats-row">
                        <?php foreach ($recentChildren as $child): ?>
                            <div class="stat-pill"><?= htmlspecialchars(trim(($child['enfant_nom'] ?? '') . ' ' . ($child['enfant_prenom'] ?? ''))) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </div>
    </section>
<?php else: ?>
    <section>
        <div class="dashboard-grid">
            <?php foreach($classes as $c): ?>
                <?php
                $id_classe = (int) $c['id'];
                $effectif = (int) $pdo->query("SELECT COUNT(*) FROM inscriptions WHERE classe_id = " . $id_classe)->fetchColumn();
                ?>
                <article class="dashboard-card">
                    <span class="dashboard-tag">📚 Classe Ecodim</span>
                    <h3><?= htmlspecialchars($c['nom']) ?></h3>
                    <p><strong><?= $effectif ?></strong> enfant(s) actuellement inscrits dans cette classe.</p>
                    <a href="lecons.php?classe=<?= $id_classe ?>" class="dashboard-link">Ouvrir la classe</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

</div></body></html>
