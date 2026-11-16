<?php
require_once __DIR__ . '/functions.php';

if (connecte()) {
    header('Location: liste.php');
    exit;
}

$erreur = '';
$login = (string) ($_COOKIE['dernier_user'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $mdp = (string) ($_POST['mdp'] ?? '');

    if (isset(UTILISATEURS[$login]) && hash_equals(UTILISATEURS[$login], $mdp)) {
        session_regenerate_id(true);
        $_SESSION['login'] = $login;
        $cheminCookie = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
        setcookie('dernier_user', $login, [
            'expires' => time() + 7 * 24 * 60 * 60,
            'path' => $cheminCookie,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        header('Location: liste.php');
        exit;
    }

    $erreur = 'Identifiant ou mot de passe incorrect.';
}

$titre = 'Connexion';
include __DIR__ . '/header.php';
?>
<section class="card login-card">
    <h1>Connexion</h1>
    <?php if ($erreur !== ''): ?>
        <p class="message error" role="alert"><?= h($erreur) ?></p>
    <?php endif; ?>
    <form method="post" action="index.php">
        <label for="login">Identifiant</label>
        <input id="login" name="login" type="text" value="<?= h($login) ?>" autocomplete="username" required>

        <label for="mdp">Mot de passe</label>
        <input id="mdp" name="mdp" type="password" autocomplete="current-password" required>

        <button type="submit">Se connecter</button>
    </form>
    <p class="hint">Compte de démonstration : <strong>admin</strong> / <strong>admin123</strong></p>
</section>
<?php include __DIR__ . '/footer.php'; ?>
