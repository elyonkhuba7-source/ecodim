<?php
require_once 'auth.php';
$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? ($_SESSION['user_role'] ?? 'admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, c.nom AS classe_nom
    FROM evaluations e
    LEFT JOIN classes c ON c.id = e.classe_id
    WHERE e.id = ?");
$stmt->execute([$id]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    die("<h3 style='text-align:center;color:#b00020;'>Fiche d'évaluation introuvable.</h3>");
}

if ($role !== 'admin' && (int) ($evaluation['moniteur_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiche d'évaluation PDF</title>
    <style>
        body {
            margin: 0;
            padding: 28px;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: #111;
            background: #f4efe6;
        }
        .sheet {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d8cbb8;
            box-shadow: 0 18px 40px rgba(0,0,0,0.08);
            padding: 34px;
        }
        h1, h2, h3, p { margin-top: 0; }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 24px;
            margin-bottom: 24px;
        }
        .block {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #e6dccf;
            background: #fffdf9;
        }
        .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7a6649;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .value {
            white-space: pre-wrap;
            line-height: 1.5;
        }
        .toolbar {
            max-width: 900px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
        }
        .toolbar button {
            padding: 12px 16px;
            border: none;
            border-radius: 12px;
            background: #111;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .sheet {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <div class="sheet">
        <h1>Fiche d'évaluation</h1>
        <p><?= $role === 'admin' ? 'Document de consultation administrateur' : 'Document imprimable du moniteur' ?></p>

        <div class="meta">
            <div class="block">
                <div class="label">Date</div>
                <div class="value"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($evaluation['created_at'] ?? 'now'))) ?></div>
            </div>
            <div class="block">
                <div class="label">Classe</div>
                <div class="value"><?= htmlspecialchars($evaluation['classe_nom'] ?: 'Aucune') ?></div>
            </div>
            <div class="block">
                <div class="label">Moniteur / Monitrice</div>
                <div class="value"><?= htmlspecialchars($evaluation['moniteur_nom'] ?: '-') ?></div>
            </div>
            <div class="block">
                <div class="label">Type d'évaluation</div>
                <div class="value"><?= htmlspecialchars($evaluation['type_evaluation'] ?: '-') ?></div>
            </div>
            <div class="block">
                <div class="label">Période</div>
                <div class="value"><?= htmlspecialchars($evaluation['periode'] ?: '-') ?></div>
            </div>
            <div class="block">
                <div class="label">Thème</div>
                <div class="value"><?= htmlspecialchars($evaluation['theme'] ?: '-') ?></div>
            </div>
        </div>

        <div class="block">
            <div class="label">Sous-thème</div>
            <div class="value"><?= htmlspecialchars($evaluation['sous_theme'] ?: '-') ?></div>
        </div>

        <div class="block">
            <div class="label">Détails</div>
            <div class="value"><?= htmlspecialchars($evaluation['details'] ?: '-') ?></div>
        </div>

        <div class="block">
            <div class="label">Observations</div>
            <div class="value"><?= htmlspecialchars($evaluation['observations'] ?: '-') ?></div>
        </div>
    </div>
</body>
</html>
