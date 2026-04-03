<?php
require_once 'db.php';

function isStrongPassword(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }

    return (bool) preg_match('/[A-Za-z]/', $password) && (bool) preg_match('/\d/', $password);
}

$erreur = "";
$message = "";
$activeTab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register_moniteur') {
        $activeTab = 'register';
        $nomComplet = trim((string) ($_POST['nom_complet'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $sexe = ($_POST['sexe'] ?? 'M') === 'F' ? 'F' : 'M';
        $classeId = (int) ($_POST['classe_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($nomComplet === '' || $username === '') {
            $erreur = "Le nom complet et l'identifiant sont obligatoires.";
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $erreur = "L'identifiant doit contenir 3 à 50 caractères sans espaces.";
        } elseif (!isStrongPassword($password)) {
            $erreur = "Le mot de passe doit contenir au moins 8 caractères avec des lettres et des chiffres.";
        } elseif (!hash_equals($password, $passwordConfirm)) {
            $erreur = "La confirmation du mot de passe ne correspond pas.";
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE username = ?");
            $check->execute([$username]);

            if ((int) $check->fetchColumn() > 0) {
                $erreur = "Cet identifiant existe déjà.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (
                    username, password, nom_complet, sexe, role, classe_id, est_bloque
                ) VALUES (?, ?, ?, ?, 'moniteur', ?, 0)");
                $stmt->execute([
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $nomComplet,
                    $sexe,
                    $classeId
                ]);
                $message = "Le compte moniteur a été créé. Tu peux maintenant te connecter.";
                $activeTab = 'login';
            }
        }
    } else {
        $userLogin = trim((string) ($_POST['username'] ?? ''));
        $passLogin = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
        $stmt->execute([$userLogin]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $erreur = "Identifiant ou mot de passe incorrect.";
        } else {
            $storedPassword = (string) ($user['password'] ?? '');
            $isHashed = (bool) password_get_info($storedPassword)['algo'];
            $isValid = $isHashed ? password_verify($passLogin, $storedPassword) : hash_equals($storedPassword, $passLogin);

            if (!$isValid) {
                $erreur = "Identifiant ou mot de passe incorrect.";
            } elseif (($user['role'] ?? '') === 'moniteur' && !empty($user['est_bloque'])) {
                $erreur = "Ce compte moniteur est bloqué. Contacte l'administrateur.";
            } else {
                if (!$isHashed) {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
                    $stmt->execute([password_hash($passLogin, PASSWORD_DEFAULT), $user['id']]);
                }

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_nom'] = $user['nom_complet'] ?: $user['username'];
                $_SESSION['user_role'] = $user['role'] ?: 'admin';
                $_SESSION['user_classe_id'] = (int) ($user['classe_id'] ?? 0);
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'nom_complet' => $user['nom_complet'] ?: $user['username'],
                    'role' => $user['role'] ?: 'admin',
                    'classe_id' => (int) ($user['classe_id'] ?? 0),
                    'est_bloque' => (int) ($user['est_bloque'] ?? 0),
                ];

                header("Location: index.php");
                exit();
            }
        }
    }
}

if (isset($_GET['blocked'])) {
    $erreur = "Ce compte moniteur est bloqué. Contacte l'administrateur.";
}

$classes = $pdo->query("SELECT id, nom FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Gestion Ecodim</title>
    <style>
        :root {
            --ink: #16120f;
            --paper: #ffffff;
            --mist: #f7f1e7;
            --sand: #eadbc3;
            --gold: #d59b2b;
            --leaf: #1f8a70;
            --danger: #c84c2d;
            --line: #e7dccd;
            --shadow: 0 24px 54px rgba(39, 28, 14, 0.15);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top, rgba(213, 155, 43, 0.16), transparent 26%),
                linear-gradient(180deg, #fdfbf7 0%, #f4ede2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 28px 16px;
        }

        .auth-shell {
            width: min(1100px, 100%);
            display: grid;
            grid-template-columns: minmax(290px, 390px) minmax(320px, 1fr);
            background: rgba(255, 255, 255, 0.96);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(124, 95, 57, 0.12);
        }

        .auth-brand {
            padding: 42px 34px;
            background:
                radial-gradient(circle at top right, rgba(213, 155, 43, 0.18), transparent 32%),
                linear-gradient(180deg, #151515 0%, #231b12 100%);
            color: #fffaf1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
        }

        .logo-shell {
            width: 134px;
            height: 134px;
            padding: 14px;
            border-radius: 32px;
            background: linear-gradient(145deg, rgba(255,255,255,0.98), rgba(242, 231, 211, 0.95));
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 18px 30px rgba(0,0,0,0.22);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-shell img {
            max-width: 100%;
            max-height: 96px;
            object-fit: contain;
            filter: drop-shadow(0 10px 18px rgba(0,0,0,0.18));
        }

        .auth-brand p {
            margin: 0 0 10px;
            font-size: 12px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #e9dcbf;
            font-weight: 800;
        }

        .auth-brand h1 {
            margin: 0 0 14px;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 0.98;
            font-family: Georgia, "Times New Roman", serif;
        }

        .auth-brand .lead {
            margin: 0;
            color: rgba(255, 250, 241, 0.82);
            line-height: 1.7;
            font-size: 15px;
        }

        .brand-points {
            display: grid;
            gap: 12px;
        }

        .brand-point {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 700;
        }

        .auth-main {
            padding: 34px;
            background:
                radial-gradient(circle at top, rgba(213, 155, 43, 0.08), transparent 24%),
                linear-gradient(180deg, #ffffff 0%, #fbf6ee 100%);
        }

        .tab-bar {
            display: inline-flex;
            background: #efe5d7;
            border-radius: 999px;
            padding: 6px;
            gap: 6px;
            margin-bottom: 24px;
        }

        .tab-link {
            border: none;
            background: transparent;
            color: #6b5b48;
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 800;
            cursor: pointer;
            font: inherit;
        }

        .tab-link.active {
            background: #111111;
            color: #ffffff;
            box-shadow: 0 10px 18px rgba(17, 17, 17, 0.16);
        }

        .notice {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 18px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .notice.error {
            background: #fff1ee;
            color: #8a2f1c;
            border-color: rgba(200, 76, 45, 0.16);
        }

        .notice.success {
            background: #eefbf5;
            color: #175d49;
            border-color: rgba(31, 138, 112, 0.16);
        }

        .panel {
            display: none;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(124, 95, 57, 0.10);
            border-radius: 24px;
            padding: 26px;
            box-shadow: 0 16px 34px rgba(39, 28, 14, 0.08);
        }

        .panel.active {
            display: block;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .panel p {
            margin: 0 0 22px;
            color: #655949;
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
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
        select {
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
        select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(213, 155, 43, 0.18);
        }

        .hint {
            margin-top: 8px;
            font-size: 13px;
            color: #6d5d48;
        }

        .btn-submit {
            width: 100%;
            margin-top: 18px;
            padding: 15px 18px;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, #21996f, #146b50);
            box-shadow: 0 14px 24px rgba(31, 138, 112, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
        }

        .btn-submit.dark {
            background: linear-gradient(135deg, #1f1f1f, #050505);
            box-shadow: 0 14px 24px rgba(17, 17, 17, 0.22);
        }

        .mini-note {
            margin-top: 14px;
            font-size: 13px;
            color: #6b5a44;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-brand,
            .auth-main {
                padding: 26px 22px;
            }
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 20px 18px;
            }

            .tab-bar {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .tab-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="auth-brand">
            <div>
                <div class="logo-shell">
                    <img src="logo.jpg.jpg" alt="Logo Logos Tabernacle">
                </div>
                <p>Plateforme Ecodim</p>
                <h1>Connexion et accès moniteur</h1>
                <p class="lead">La page de connexion permet maintenant aussi de créer un compte moniteur sans donner d’accès administrateur. Le profil complet pourra ensuite être enrichi dans l’espace de gestion.</p>
            </div>

            <div class="brand-points">
                <div class="brand-point">Compte admin inchangé et protégé.</div>
                <div class="brand-point">Création limitée au rôle <strong>moniteur</strong>.</div>
                <div class="brand-point">Mot de passe fort obligatoire avant validation.</div>
            </div>
        </aside>

        <main class="auth-main">
            <div class="tab-bar">
                <button type="button" class="tab-link <?= $activeTab === 'login' ? 'active' : '' ?>" data-tab="login-panel">Se connecter</button>
                <button type="button" class="tab-link <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="register-panel">Créer un compte moniteur</button>
            </div>

            <?php if ($erreur): ?>
                <div class="notice error"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="notice success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <section id="login-panel" class="panel <?= $activeTab === 'login' ? 'active' : '' ?>">
                <h2>Espace sécurisé</h2>
                <p>Connecte-toi avec ton identifiant et ton mot de passe. Les comptes bloqués restent refusés automatiquement.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-grid">
                        <div class="form-full">
                            <label>Identifiant</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-full">
                            <label>Mot de passe</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit dark">Se connecter</button>
                </form>
            </section>

            <section id="register-panel" class="panel <?= $activeTab === 'register' ? 'active' : '' ?>">
                <h2>Créer un compte moniteur</h2>
                <p>Cette inscription crée uniquement un compte moniteur. Elle ne donne jamais d’accès administrateur.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="register_moniteur">
                    <div class="form-grid">
                        <div class="form-full">
                            <label>Nom complet</label>
                            <input type="text" name="nom_complet" value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Identifiant</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($activeTab === 'register' ? ($_POST['username'] ?? '') : '') ?>" required>
                        </div>
                        <div>
                            <label>Sexe</label>
                            <select name="sexe" required>
                                <option value="M" <?= (($_POST['sexe'] ?? 'M') === 'M') ? 'selected' : '' ?>>Homme</option>
                                <option value="F" <?= (($_POST['sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Femme</option>
                            </select>
                        </div>
                        <div class="form-full">
                            <label>Classe assignée</label>
                            <select name="classe_id">
                                <option value="0">Aucune classe pour le moment</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= (int) $classe['id'] ?>" <?= ((int) ($_POST['classe_id'] ?? 0) === (int) $classe['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($classe['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="hint">Si tu ne choisis pas encore de classe, l’admin pourra l’assigner plus tard.</div>
                        </div>
                        <div>
                            <label>Mot de passe</label>
                            <input type="password" name="password" required>
                        </div>
                        <div>
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="password_confirm" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Créer le compte moniteur</button>
                    <div class="mini-note">Règle appliquée : minimum 8 caractères, avec au moins une lettre et un chiffre.</div>
                </form>
            </section>
        </main>
    </div>

    <script>
        document.querySelectorAll('.tab-link').forEach(function(button) {
            button.addEventListener('click', function() {
                document.querySelectorAll('.tab-link').forEach(function(item) {
                    item.classList.remove('active');
                });
                document.querySelectorAll('.panel').forEach(function(panel) {
                    panel.classList.remove('active');
                });

                button.classList.add('active');
                document.getElementById(button.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
