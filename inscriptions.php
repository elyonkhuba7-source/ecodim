<?php
require_once 'auth.php';
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') != 'admin') { header("Location: index.php"); exit; }
$erreur = "";
$editData = null;
$syncMessage = "";

if (isset($_GET['supprimer'])) {
    $idToDelete = (int) $_GET['supprimer'];
    try {
        $pdo->prepare("DELETE FROM presences WHERE inscription_id = ?")->execute([$idToDelete]);
        $pdo->prepare("DELETE FROM inscriptions WHERE id = ?")->execute([$idToDelete]);
        header("Location: inscriptions.php");
        exit();
    } catch (Exception $e) {
        $erreur = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

if (isset($_GET['modifier'])) {
    $stmt = $pdo->prepare("SELECT * FROM inscriptions WHERE id = ?");
    $stmt->execute([(int) $_GET['modifier']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (isset($_POST['save'])) {
    $payload = [
        trim($_POST['e_nom']),
        trim($_POST['e_prenom']),
        trim($_POST['e_ddn']),
        $_POST['e_sexe'],
        (int) $_POST['classe_id'],
        trim($_POST['urg']),
        $_POST['part'],
        trim($_POST['all']),
        $_POST['r1_type'],
        trim($_POST['r1_nom']),
        trim($_POST['r1_prenom']),
        $_POST['r1_sexe'],
        trim($_POST['r1_ad']),
        trim($_POST['r1_tel']),
        trim($_POST['r1_prof']),
        trim($_POST['r1_email']),
        $_POST['r2_type'] ?? 'parent',
        trim($_POST['r2_nom'] ?? ''),
        trim($_POST['r2_prenom'] ?? ''),
        $_POST['r2_sexe'] ?? 'M',
        trim($_POST['r2_ad'] ?? ''),
        trim($_POST['r2_tel'] ?? ''),
        trim($_POST['r2_prof'] ?? ''),
        trim($_POST['r2_email'] ?? '')
    ];

    if (!empty($_POST['inscription_id'])) {
        $stmt = $pdo->prepare("UPDATE inscriptions SET
            enfant_nom = ?, enfant_prenom = ?, enfant_ddn = ?, enfant_sexe = ?, classe_id = ?, urgence_contact = ?,
            participation_activites = ?, allergies = ?, resp1_type = ?, resp1_nom = ?, resp1_prenom = ?,
            resp1_sexe = ?, resp1_adresse = ?, resp1_tel = ?, resp1_prof = ?, resp1_email = ?,
            resp2_type = ?, resp2_nom = ?, resp2_prenom = ?, resp2_sexe = ?, resp2_adresse = ?, resp2_tel = ?, resp2_prof = ?, resp2_email = ?
            WHERE id = ?");
        $payload[] = (int) $_POST['inscription_id'];
        $stmt->execute($payload);
        $id = (int) $_POST['inscription_id'];
        $msg = "Inscription modifiée ! <a href='print_fiche.php?id=$id' target='_blank' style='color:#1f8a70; font-weight:800;'>Ouvrir la fiche d'inscription</a>";
        header("Location: inscriptions.php?modifier=$id&success=1");
        exit();
    } else {
        $stmt = $pdo->prepare("INSERT INTO inscriptions (
            enfant_nom, enfant_prenom, enfant_ddn, enfant_sexe, classe_id, urgence_contact,
            participation_activites, allergies, resp1_type, resp1_nom, resp1_prenom,
            resp1_sexe, resp1_adresse, resp1_tel, resp1_prof, resp1_email,
            resp2_type, resp2_nom, resp2_prenom, resp2_sexe, resp2_adresse, resp2_tel, resp2_prof, resp2_email
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute($payload);
        $id = $pdo->lastInsertId();
    }

    $msg = "Enfant enregistré ! <a href='print_fiche.php?id=$id' target='_blank' style='color:#1f8a70; font-weight:800;'>Ouvrir la fiche d'inscription</a>";
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$inscriptions = $pdo->query("SELECT i.*, c.nom AS classe_nom,
    (
        SELECT GROUP_CONCAT(COALESCE(NULLIF(u.nom_complet, ''), u.username) SEPARATOR ', ')
        FROM utilisateurs u
        WHERE u.role = 'moniteur' AND u.classe_id = i.classe_id
    ) AS moniteurs_classe
    FROM inscriptions i
    LEFT JOIN classes c ON i.classe_id = c.id
    ORDER BY i.id DESC")->fetchAll(PDO::FETCH_ASSOC);
require_once 'header.php';

if (isset($_GET['success']) && $editData) {
    $msg = "Inscription modifiée ! <a href='print_fiche.php?id=" . (int) $editData['id'] . "' target='_blank' style='color:#1f8a70; font-weight:800;'>Ouvrir la fiche d'inscription</a>";
}

function fieldValue(?array $data, string $key, string $default = ''): string {
    return htmlspecialchars((string) ($data[$key] ?? $default));
}

if (isset($id) && $id > 0) {
    $syncStmt = $pdo->prepare("SELECT c.nom AS classe_nom,
        GROUP_CONCAT(COALESCE(NULLIF(u.nom_complet, ''), u.username) SEPARATOR ', ') AS moniteurs_classe
        FROM inscriptions i
        LEFT JOIN classes c ON c.id = i.classe_id
        LEFT JOIN utilisateurs u ON u.role = 'moniteur' AND u.classe_id = i.classe_id
        WHERE i.id = ?
        GROUP BY i.id, c.nom");
    $syncStmt->execute([$id]);
    $syncInfo = $syncStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($syncInfo) {
        $classeNom = $syncInfo['classe_nom'] ?: 'Aucune classe';
        $moniteursClasse = $syncInfo['moniteurs_classe'] ?: 'aucun moniteur assigné pour le moment';
        $syncMessage = "Synchronisation : cet enfant est rattaché à la classe <b>" . htmlspecialchars($classeNom) . "</b> et sera visible chez <b>" . htmlspecialchars($moniteursClasse) . "</b> après rafraîchissement de leur page.";
    }
}
?>

<section class="page-intro">
    <div>
        <p class="page-kicker">Inscriptions</p>
        <h2 class="page-title">Nouvelle fiche d'inscription</h2>
        <p class="page-subtitle">Le formulaire suit maintenant la même logique que la fiche d’inscription imprimable pour éviter les écarts entre saisie et impression.</p>
    </div>
    <div class="hero-chip"><?= count($classes) ?> classe(s) disponible(s)</div>
</section>

<?php if(isset($msg)): ?>
    <div class="panel" style="background:#eefbf5; border-color:rgba(31, 138, 112, 0.18); color:#175d49;">
        <?= $msg ?>
    </div>
<?php endif; ?>

<?php if($syncMessage): ?>
    <div class="panel" style="background:#fff8e8; border-color:rgba(213, 155, 43, 0.22); color:#6b552f;">
        <?= $syncMessage ?>
    </div>
<?php endif; ?>

<?php if($erreur): ?>
    <div class="panel" style="background:#fff1ee; border-color:rgba(200, 76, 45, 0.18); color:#8a2f1c;">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="inscription_id" value="<?= (int) ($editData['id'] ?? 0) ?>">
    <h3 class="section-title"><?= $editData ? "Modifier l'inscription" : "Responsable 1" ?></h3>
    <div class="form-grid">
        <div>
            <label>Qualité</label>
            <select name="r1_type">
                <option value="parent" <?= (($editData['resp1_type'] ?? 'parent') === 'parent') ? 'selected' : '' ?>>Parent</option>
                <option value="tuteur" <?= (($editData['resp1_type'] ?? '') === 'tuteur') ? 'selected' : '' ?>>Tuteur</option>
            </select>
        </div>
        <div>
            <label>Sexe</label>
            <select name="r1_sexe">
                <option value="M" <?= (($editData['resp1_sexe'] ?? 'M') === 'M') ? 'selected' : '' ?>>Homme</option>
                <option value="F" <?= (($editData['resp1_sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Femme</option>
            </select>
        </div>
        <div>
            <label>Nom</label>
            <input type="text" name="r1_nom" value="<?= fieldValue($editData, 'resp1_nom') ?>" required>
        </div>
        <div>
            <label>Prénom</label>
            <input type="text" name="r1_prenom" value="<?= fieldValue($editData, 'resp1_prenom') ?>" required>
        </div>
        <div class="form-full">
            <label>Adresse personnelle</label>
            <input type="text" name="r1_ad" value="<?= fieldValue($editData, 'resp1_adresse') ?>">
        </div>
        <div>
            <label>Téléphone</label>
            <input type="text" name="r1_tel" value="<?= fieldValue($editData, 'resp1_tel') ?>">
        </div>
        <div>
            <label>Adresse mail</label>
            <input type="email" name="r1_email" value="<?= fieldValue($editData, 'resp1_email') ?>" placeholder="exemple@email.com">
        </div>
        <div class="form-full">
            <label>Situation professionnelle</label>
            <input type="text" name="r1_prof" value="<?= fieldValue($editData, 'resp1_prof') ?>">
        </div>
    </div>

    <h3 class="section-title" style="margin-top:28px;">Responsable 2</h3>
    <div class="form-grid">
        <div>
            <label>Qualité</label>
            <select name="r2_type">
                <option value="parent" <?= (($editData['resp2_type'] ?? 'parent') === 'parent') ? 'selected' : '' ?>>Parent</option>
                <option value="tuteur" <?= (($editData['resp2_type'] ?? '') === 'tuteur') ? 'selected' : '' ?>>Tuteur</option>
            </select>
        </div>
        <div>
            <label>Sexe</label>
            <select name="r2_sexe">
                <option value="M" <?= (($editData['resp2_sexe'] ?? 'M') === 'M') ? 'selected' : '' ?>>Homme</option>
                <option value="F" <?= (($editData['resp2_sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Femme</option>
            </select>
        </div>
        <div>
            <label>Nom</label>
            <input type="text" name="r2_nom" value="<?= fieldValue($editData, 'resp2_nom') ?>">
        </div>
        <div>
            <label>Prénom</label>
            <input type="text" name="r2_prenom" value="<?= fieldValue($editData, 'resp2_prenom') ?>">
        </div>
        <div class="form-full">
            <label>Adresse personnelle</label>
            <input type="text" name="r2_ad" value="<?= fieldValue($editData, 'resp2_adresse') ?>">
        </div>
        <div>
            <label>Téléphone</label>
            <input type="text" name="r2_tel" value="<?= fieldValue($editData, 'resp2_tel') ?>">
        </div>
        <div>
            <label>Adresse mail</label>
            <input type="email" name="r2_email" value="<?= fieldValue($editData, 'resp2_email') ?>" placeholder="exemple@email.com">
        </div>
        <div class="form-full">
            <label>Situation professionnelle</label>
            <input type="text" name="r2_prof" value="<?= fieldValue($editData, 'resp2_prof') ?>">
        </div>
    </div>

    <h3 class="section-title" style="margin-top:28px;">Inscription scolaire pour l'enfant</h3>
    <div class="form-grid">
        <div>
            <label>Nom</label>
            <input type="text" name="e_nom" value="<?= fieldValue($editData, 'enfant_nom') ?>" required>
        </div>
        <div>
            <label>Prénom</label>
            <input type="text" name="e_prenom" value="<?= fieldValue($editData, 'enfant_prenom') ?>" required>
        </div>
        <div>
            <label>Date de naissance</label>
            <input type="date" name="e_ddn" value="<?= fieldValue($editData, 'enfant_ddn') ?>">
        </div>
        <div>
            <label>Sexe</label>
            <select name="e_sexe">
                <option value="M" <?= (($editData['enfant_sexe'] ?? 'M') === 'M') ? 'selected' : '' ?>>Garçon</option>
                <option value="F" <?= (($editData['enfant_sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Fille</option>
            </select>
        </div>
        <div class="form-full">
            <label>Classe</label>
            <select name="classe_id" required>
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ((int) ($editData['classe_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-full">
            <label>A défaut, qui prévenir ? (Nom, téléphone, lien)</label>
            <textarea name="urg" rows="3"><?= fieldValue($editData, 'urgence_contact') ?></textarea>
        </div>
        <div>
            <label>Participera aux activités ?</label>
            <select name="part">
                <option value="OUI" <?= (($editData['participation_activites'] ?? 'OUI') === 'OUI') ? 'selected' : '' ?>>Oui</option>
                <option value="NON" <?= (($editData['participation_activites'] ?? '') === 'NON') ? 'selected' : '' ?>>Non</option>
            </select>
        </div>
        <div class="form-full">
            <label>Allergie ou recommandation santé</label>
            <textarea name="all" rows="3" placeholder="Indique les allergies ou recommandations importantes"><?= fieldValue($editData, 'allergies') ?></textarea>
        </div>
    </div>

    <div style="margin-top:20px;" class="form-grid">
        <div>
            <button type="submit" name="save" class="btn-submit"><?= $editData ? '💾 Enregistrer les modifications' : '💾 Enregistrer l\'inscription et générer la fiche' ?></button>
        </div>
        <div>
            <a href="inscriptions.php" class="dashboard-link" style="width:100%; justify-content:center;">↺ Nouvelle fiche</a>
        </div>
    </div>
</form>

<section class="panel">
    <h3 class="section-title">Liste des enfants inscrits</h3>
    <?php if (empty($inscriptions)): ?>
        <div class="empty-state">Aucun enfant inscrit pour le moment.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table style="margin-bottom:0;">
                <tr>
                    <th>Nom et prénom</th>
                    <th>Classe</th>
                    <th>Moniteur(s) de la classe</th>
                    <th>Date de naissance</th>
                    <th>Responsables</th>
                    <th>Actions</th>
                </tr>
                <?php foreach($inscriptions as $item): ?>
                    <tr>
                        <td style="font-weight:800;"><?= htmlspecialchars(trim(($item['enfant_nom'] ?? '') . ' ' . ($item['enfant_prenom'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($item['classe_nom'] ?: 'Aucune') ?></td>
                        <td><?= htmlspecialchars($item['moniteurs_classe'] ?: 'Aucun moniteur assigné') ?></td>
                        <td><?= htmlspecialchars($item['enfant_ddn'] ?: '-') ?></td>
                        <td>
                            <?= htmlspecialchars(trim(($item['resp1_nom'] ?? '') . ' ' . ($item['resp1_prenom'] ?? ''))) ?>
                            <?php if (!empty(trim(($item['resp2_nom'] ?? '') . ' ' . ($item['resp2_prenom'] ?? '')))): ?>
                                <br><span style="color:#6d5d48; font-size:13px;"><?= htmlspecialchars(trim(($item['resp2_nom'] ?? '') . ' ' . ($item['resp2_prenom'] ?? ''))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="print_fiche.php?id=<?= (int) $item['id'] ?>" target="_blank" class="dashboard-link" style="padding:10px 12px; margin-right:8px;">🖨️ Fiche</a>
                            <a href="inscriptions.php?modifier=<?= (int) $item['id'] ?>" class="dashboard-link" style="padding:10px 12px; margin-right:8px;">✏️ Modifier</a>
                            <a href="inscriptions.php?supprimer=<?= (int) $item['id'] ?>" class="btn-danger" onclick="return confirm('Supprimer cette inscription ? Cette action est définitive.')">🗑️ Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>

</div></body></html>
