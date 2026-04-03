<?php
require_once 'auth.php';
$currentUser = $_SESSION['user'] ?? [];
$currentRole = $currentUser['role'] ?? ($_SESSION['user_role'] ?? 'admin');
$currentName = $currentUser['nom_complet'] ?? ($_SESSION['user_nom'] ?? $currentUser['username'] ?? 'Utilisateur');
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

function navLink(string $href, string $label, string $currentPage, bool $highlight = false): string {
    $active = $currentPage === $href;
    $classes = [];
    if ($active) {
        $classes[] = 'nav-active';
    }
    if ($highlight) {
        $classes[] = 'nav-highlight';
    }
    $classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
    return '<a href="' . htmlspecialchars($href) . '"' . $classAttr . '>' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion Ecodim</title>
    <style>
        :root {
            --ink: #111111;
            --paper: #ffffff;
            --mist: #f6f1e8;
            --sand: #eadbc3;
            --gold: #d59b2b;
            --leaf: #1f8a70;
            --accent: #c84c2d;
            --line: #e7dccd;
            --shadow: 0 18px 40px rgba(39, 28, 14, 0.10);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(213, 155, 43, 0.12), transparent 30%),
                linear-gradient(180deg, #fbf8f3 0%, #f4efe6 100%);
        }

        .site-header {
            background:
                radial-gradient(circle at top left, rgba(213, 155, 43, 0.14), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(250, 244, 235, 0.96));
            border-bottom: 1px solid rgba(17, 17, 17, 0.08);
        }

        .site-header-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }

        .logo-container {
            position: relative;
            padding: 10px;
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(255,255,255,0.98), rgba(242, 231, 211, 0.95));
            border: 1px solid rgba(124, 95, 57, 0.14);
            box-shadow:
                0 16px 28px rgba(58, 39, 16, 0.10),
                inset 0 1px 0 rgba(255,255,255,0.9);
        }

        .logo-container::after {
            content: "";
            position: absolute;
            inset: -8px;
            border-radius: 30px;
            border: 1px solid rgba(213, 155, 43, 0.18);
            pointer-events: none;
        }

        .logo-container img {
            max-height: 72px;
            display: block;
            filter: drop-shadow(0 10px 18px rgba(17, 17, 17, 0.12));
        }

        .brand-copy {
            text-align: left;
        }

        .brand-copy p {
            margin: 0 0 4px;
            font-size: 12px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #6d5d48;
            font-weight: 700;
        }

        .brand-copy h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1;
            font-family: Georgia, "Times New Roman", serif;
        }

        nav {
            background: #111111;
            border-bottom: 4px solid var(--gold);
            box-shadow: 0 10px 25px rgba(17, 17, 17, 0.16);
        }

        .nav-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .nav-links,
        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links {
            flex: 1;
            min-width: 0;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.01em;
            padding: 10px 14px;
            border-radius: 999px;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffe39c;
            transform: translateY(-1px);
        }

        .nav-active {
            background: rgba(213, 155, 43, 0.18);
            color: #ffe39c;
            box-shadow: inset 0 0 0 1px rgba(255, 227, 156, 0.16);
        }

        .nav-highlight {
            color: #ffdb73;
        }

        .logout-link {
            background: linear-gradient(135deg, #ff6b4a, #d9391a);
        }

        .logout-link:hover {
            color: #fff;
            background: linear-gradient(135deg, #ff7a5c, #e24524);
        }

        .nav-user span {
            color: rgba(255, 255, 255, 0.92);
            font-size: 14px;
        }

        .container {
            max-width: 1180px;
            margin: 34px auto 56px;
            padding: 0 20px;
        }

        .page-intro {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
            padding: 28px 30px;
            border-radius: 26px;
            background:
                linear-gradient(140deg, rgba(255, 255, 255, 0.94), rgba(250, 242, 228, 0.96));
            border: 1px solid rgba(124, 95, 57, 0.10);
            box-shadow: var(--shadow);
        }

        .page-kicker {
            margin: 0 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #8c7451;
            font-weight: 800;
        }

        .page-title {
            margin: 0;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.06;
        }

        .page-subtitle {
            max-width: 720px;
            margin: 10px 0 0;
            color: #5d5144;
            font-size: 16px;
            line-height: 1.6;
        }

        .hero-chip {
            padding: 12px 16px;
            border-radius: 999px;
            background: #111111;
            color: #ffffff;
            font-weight: 700;
            white-space: nowrap;
        }

        .panel,
        form,
        table {
            background: var(--paper);
            border-radius: 22px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(124, 95, 57, 0.10);
        }

        .panel {
            padding: 26px 28px;
            margin-bottom: 26px;
        }

        form {
            padding: 26px 28px;
            margin-bottom: 26px;
        }

        .section-title {
            margin: 0 0 18px;
            font-size: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 800;
            color: #2c241c;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d4c8b8;
            border-radius: 14px;
            background: #fffdf9;
            font: inherit;
            color: var(--ink);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(213, 155, 43, 0.18);
        }

        .btn-submit,
        button {
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .btn-submit {
            width: 100%;
            padding: 14px 18px;
            background: linear-gradient(135deg, #21996f, #146b50);
            color: #fff;
            box-shadow: 0 12px 22px rgba(31, 138, 112, 0.24);
        }

        .btn-submit:hover,
        button:hover {
            transform: translateY(-1px);
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ff7c57, #d84023);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 16px 18px;
            border-bottom: 1px solid #efe6da;
            text-align: left;
        }

        th {
            background: #111111;
            color: #fff;
            font-size: 14px;
            letter-spacing: 0.02em;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            padding: 28px 20px;
            text-align: center;
            color: #64584c;
        }

        .notice-success,
        .notice-error {
            padding: 16px 18px;
            border-radius: 18px;
            margin-bottom: 22px;
            border: 1px solid transparent;
            box-shadow: var(--shadow);
        }

        .notice-success {
            background: #eefbf5;
            border-color: rgba(31, 138, 112, 0.18);
            color: #175d49;
        }

        .notice-error {
            background: #fff1ee;
            border-color: rgba(200, 76, 45, 0.18);
            color: #8a2f1c;
        }

        .subnav-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .subnav-tabs .btn-submit {
            width: auto;
            margin: 0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .kpi-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }

        .kpi-card {
            padding: 12px 16px;
            border-radius: 16px;
            font-weight: 800;
        }

        .kpi-card.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .kpi-card.danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            padding: 24px;
            border-radius: 24px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(249, 241, 229, 0.96));
            border: 1px solid rgba(124, 95, 57, 0.12);
            box-shadow: var(--shadow);
        }

        .dashboard-card h3 {
            margin: 8px 0 10px;
            font-size: 22px;
        }

        .dashboard-card p {
            margin: 0 0 18px;
            color: #5d5144;
        }

        .dashboard-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f4ead7;
            color: #6b552f;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .dashboard-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border-radius: 14px;
            background: #111111;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .stat-pill {
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(17, 17, 17, 0.05);
            font-weight: 700;
            color: #3f352b;
        }
        
        /* IMPRESSION PARFAITE */
        @media print { 
            @page { margin: 1cm; } /* Force le navigateur à cacher ses propres textes */
            .no-print, nav, form, .btn-submit, .btn-danger, .dashboard-link { display: none !important; } 
            body { padding: 0; margin: 0; background: white; }
            .container { margin: 0; padding: 0; width: 100%; max-width: 100%; }
            table, th, td { border: 1px solid black; } 
            th { background-color: #eee !important; color: black !important; -webkit-print-color-adjust: exact; } 
        }

        @media (max-width: 900px) {
            .site-header-inner,
            .page-intro,
            .nav-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .brand-copy {
                text-align: left;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .nav-inner {
                align-items: stretch;
            }

            .nav-links {
                width: 100%;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
                scrollbar-width: none;
            }

            .nav-links::-webkit-scrollbar {
                display: none;
            }

            .nav-user {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 640px) {
            .container {
                margin-top: 24px;
                padding: 0 14px;
            }

            .site-header-inner,
            .nav-inner,
            .page-intro,
            .panel,
            form {
                padding-left: 16px;
                padding-right: 16px;
            }

            .page-intro {
                align-items: flex-start;
            }

            .page-title {
                font-size: 30px;
            }

            .page-subtitle {
                font-size: 15px;
            }

            .hero-chip {
                width: 100%;
                text-align: center;
            }

            nav a {
                white-space: nowrap;
                padding: 10px 12px;
                font-size: 13px;
            }

            .nav-user {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .table-wrap {
                margin: 0 -4px;
                padding-bottom: 6px;
            }

            .table-wrap table {
                min-width: 640px;
            }

            th,
            td {
                padding: 14px 12px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="site-header-inner">
            <div class="logo-container">
                <img src="logo.jpg.jpg" alt="Logo">
            </div>
            <div class="brand-copy">
                <p>Plateforme Ecodim</p>
                <h1>Gestion Ecodim</h1>
            </div>
        </div>
    </header>
    
    <nav class="no-print">
        <div class="nav-inner">
        <div class="nav-links">
            <?php if ($currentRole === 'admin'): ?>
                <?= navLink('index.php', '🏠 Tableau de bord', $currentPage) ?>
                <?= navLink('inscriptions.php', '📝 Inscriptions', $currentPage) ?>
                <?= navLink('presences.php', '✅ Présences', $currentPage) ?>
                <?= navLink('lecons.php', '📖 Leçons', $currentPage) ?>
                <?= navLink('evaluations.php', '🧾 Fiche d\'évaluation', $currentPage) ?>
                <?= navLink('moniteurs.php', '👨‍🏫 Moniteurs', $currentPage) ?>
                <?= navLink('finances.php', '💰 Finances', $currentPage) ?>
                <?= navLink('securite.php', '🔐 Sécurité', $currentPage) ?>
                <?= navLink('parametres.php', '⚙️ Classes', $currentPage) ?>
                <?= navLink('rapports.php', '📊 Rapports', $currentPage, true) ?>
            <?php else: ?>
                <?= navLink('index.php', '🏠 Mon Profil', $currentPage) ?>
                <?= navLink('presences.php', '✅ Faire l\'appel', $currentPage) ?>
                <?= navLink('lecons.php', '📖 Ma Leçon', $currentPage) ?>
                <?= navLink('evaluations.php', '🧾 Fiche d\'évaluation', $currentPage) ?>
            <?php endif; ?>
        </div>
        <div class="nav-user">
            <span>Connecté : <b><?= htmlspecialchars($currentName) ?></b></span>
            <a href="logout.php" class="logout-link">🚪 Déconnexion</a>
        </div>
        </div>
    </nav>
    <div class="container">
