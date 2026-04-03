<?php
require_once 'db.php';

$data = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT i.*, c.nom AS classe_nom FROM inscriptions i LEFT JOIN classes c ON i.classe_id = c.id WHERE i.id = ?");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

function isChecked($value, $expected): string {
    return $value === $expected ? 'checked' : '';
}

function formatDateFr($date): string {
    if (!$date) {
        return '';
    }
    $timestamp = strtotime((string) $date);
    return $timestamp ? date('d/m/Y', $timestamp) : (string) $date;
}

function safeText($value): string {
    return htmlspecialchars((string) ($value ?? ''));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiche d'inscription</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            margin: 0;
            background: #f3f3f3;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.35;
        }

        .toolbar {
            padding: 18px 0 8px;
            text-align: center;
        }

        .toolbar button {
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            background: #111;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .sheet {
            width: 190mm;
            min-height: 277mm;
            margin: 0 auto 20px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
            padding: 10mm 11mm;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .brand {
            flex: 1;
        }

        .brand .brand-line {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .brand h1 {
            margin: 4px 0 0;
            font-size: 21px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .logo {
            width: 98px;
            text-align: right;
        }

        .logo img {
            max-width: 100%;
            max-height: 58px;
            object-fit: contain;
        }

        .main-title {
            border: 1.6px solid #000;
            background: #e4e4e4;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            padding: 8px 10px;
            margin: 8px 0 14px;
            text-transform: uppercase;
        }

        .section-title {
            border: 1.3px solid #000;
            background: #ececec;
            text-align: center;
            font-weight: 700;
            padding: 6px 8px;
            margin: 12px 0 10px;
            text-transform: uppercase;
        }

        .field-grid-2,
        .field-grid-3 {
            display: grid;
            gap: 8px 12px;
        }

        .field-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field-grid-3 {
            grid-template-columns: 1.3fr 1.3fr 0.8fr;
        }

        .field,
        .field-full {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            min-height: 28px;
        }

        .field-full {
            margin-bottom: 8px;
        }

        .label {
            font-weight: 700;
            white-space: nowrap;
        }

        .line {
            flex: 1;
            min-height: 22px;
            border-bottom: 1.5px dotted #000;
            padding: 0 4px 2px;
            font-weight: 700;
        }

        .checkbox-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .box {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .box.checked::after {
            content: "X";
        }

        .spacer {
            height: 6px;
        }

        .text-block {
            border: 1px solid #000;
            min-height: 46px;
            padding: 8px;
            margin-top: 4px;
            white-space: pre-wrap;
            font-weight: 700;
        }

        .foot-note {
            margin-top: 22px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-end;
        }

        .signature-box {
            width: 48%;
        }

        .signature-line {
            border-bottom: 1.5px solid #000;
            min-height: 28px;
            margin-top: 18px;
        }

        .small-note {
            margin-top: 18px;
            font-size: 11px;
            line-height: 1.5;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Imprimer la fiche</button>
    </div>

    <div class="sheet">
        <div class="topbar">
            <div class="brand">
                <div class="brand-line">Logos Tabernacle - Ecodim</div>
                <h1>Fiche d'inscription</h1>
            </div>
            <div class="logo">
                <img src="logo.jpg.jpg" alt="Logo Logos">
            </div>
        </div>

        <div class="main-title">Ecodim - Fiche d'inscription</div>

        <div class="section-title">Responsable 1</div>

        <div class="field-full">
            <span class="label">Qualité :</span>
            <span class="checkbox-group">
                Parent <span class="box <?= isChecked($data['resp1_type'] ?? '', 'parent') ?>"></span>
                Tuteur <span class="box <?= isChecked($data['resp1_type'] ?? '', 'tuteur') ?>"></span>
            </span>
        </div>

        <div class="field-grid-3">
            <div class="field">
                <span class="label">Nom :</span>
                <span class="line"><?= safeText($data['resp1_nom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Prénom :</span>
                <span class="line"><?= safeText($data['resp1_prenom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Sexe :</span>
                <span class="checkbox-group">
                    F <span class="box <?= isChecked($data['resp1_sexe'] ?? '', 'F') ?>"></span>
                    M <span class="box <?= isChecked($data['resp1_sexe'] ?? '', 'M') ?>"></span>
                </span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-grid-2">
            <div class="field">
                <span class="label">Adresse personnelle :</span>
                <span class="line"><?= safeText($data['resp1_adresse'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Téléphone :</span>
                <span class="line"><?= safeText($data['resp1_tel'] ?? '') ?></span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-full">
            <span class="label">Situation professionnelle :</span>
            <span class="line"><?= safeText($data['resp1_prof'] ?? '') ?></span>
        </div>

        <div class="field-full">
            <span class="label">Adresse mail :</span>
            <span class="line"><?= safeText($data['resp1_email'] ?? '') ?></span>
        </div>

        <div class="section-title">Responsable 2</div>

        <div class="field-full">
            <span class="label">Qualité :</span>
            <span class="checkbox-group">
                Parent <span class="box <?= isChecked($data['resp2_type'] ?? '', 'parent') ?>"></span>
                Tuteur <span class="box <?= isChecked($data['resp2_type'] ?? '', 'tuteur') ?>"></span>
            </span>
        </div>

        <div class="field-grid-3">
            <div class="field">
                <span class="label">Nom :</span>
                <span class="line"><?= safeText($data['resp2_nom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Prénom :</span>
                <span class="line"><?= safeText($data['resp2_prenom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Sexe :</span>
                <span class="checkbox-group">
                    F <span class="box <?= isChecked($data['resp2_sexe'] ?? '', 'F') ?>"></span>
                    M <span class="box <?= isChecked($data['resp2_sexe'] ?? '', 'M') ?>"></span>
                </span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-grid-2">
            <div class="field">
                <span class="label">Adresse personnelle :</span>
                <span class="line"><?= safeText($data['resp2_adresse'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Téléphone :</span>
                <span class="line"><?= safeText($data['resp2_tel'] ?? '') ?></span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-full">
            <span class="label">Situation professionnelle :</span>
            <span class="line"><?= safeText($data['resp2_prof'] ?? '') ?></span>
        </div>

        <div class="field-full">
            <span class="label">Adresse mail :</span>
            <span class="line"><?= safeText($data['resp2_email'] ?? '') ?></span>
        </div>

        <div class="section-title">Inscription scolaire pour l'enfant</div>

        <div class="field-grid-2">
            <div class="field">
                <span class="label">Nom :</span>
                <span class="line"><?= safeText($data['enfant_nom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Prénom :</span>
                <span class="line"><?= safeText($data['enfant_prenom'] ?? '') ?></span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-grid-3">
            <div class="field">
                <span class="label">Date de naissance :</span>
                <span class="line"><?= safeText(formatDateFr($data['enfant_ddn'] ?? '')) ?></span>
            </div>
            <div class="field">
                <span class="label">Classe :</span>
                <span class="line"><?= safeText($data['classe_nom'] ?? '') ?></span>
            </div>
            <div class="field">
                <span class="label">Sexe :</span>
                <span class="checkbox-group">
                    F <span class="box <?= isChecked($data['enfant_sexe'] ?? '', 'F') ?>"></span>
                    M <span class="box <?= isChecked($data['enfant_sexe'] ?? '', 'M') ?>"></span>
                </span>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="field-full">
            <span class="label">À défaut, qui prévenir ?</span>
        </div>
        <div class="text-block"><?= safeText($data['urgence_contact'] ?? '') ?></div>

        <div class="field-full" style="margin-top:12px;">
            <span class="label">Participera aux activités ?</span>
            <span class="checkbox-group">
                Oui <span class="box <?= isChecked($data['participation_activites'] ?? '', 'OUI') ?>"></span>
                Non <span class="box <?= isChecked($data['participation_activites'] ?? '', 'NON') ?>"></span>
            </span>
        </div>

        <div class="field-full" style="margin-top:12px;">
            <span class="label">Allergies ou recommandations santé :</span>
        </div>
        <div class="text-block"><?= safeText($data['allergies'] ?? '') ?></div>

        <div class="small-note">
            Je certifie exacts les renseignements fournis dans cette fiche d'inscription.
        </div>

        <div class="foot-note">
            <div class="signature-box">
                <div><strong>Lieu et date :</strong></div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div><strong>Signature du responsable :</strong></div>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>
</body>
</html>
