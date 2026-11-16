<?php
require_once __DIR__ . '/functions.php';
exiger_connexion();

$etudiants = lire_etudiants();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$edition = $id !== null;
if ($edition && !isset($etudiants[$id])) {
    $_SESSION['flash'] = ['type' => 'error', 'texte' => 'Étudiant introuvable.'];
    header('Location: liste.php');
    exit;
}

$etudiant = $edition
    ? $etudiants[$id]
    : ['nom' => '', 'prenom' => '', 'filiere' => '', 'note' => '', 'photo' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etudiant['nom'] = nettoyer((string) ($_POST['nom'] ?? ''));
    $etudiant['prenom'] = nettoyer((string) ($_POST['prenom'] ?? ''));
    $etudiant['filiere'] = nettoyer((string) ($_POST['filiere'] ?? ''));
    $etudiant['note'] = trim((string) ($_POST['note'] ?? ''));

    if ($etudiant['nom'] === '') {
        $erreurs[] = 'Le nom est obligatoire.';
    }
    if ($etudiant['prenom'] === '') {
        $erreurs[] = 'Le prénom est obligatoire.';
    }
    if (!in_array($etudiant['filiere'], FILIERES, true)) {
        $erreurs[] = 'Veuillez choisir une filière valide.';
    }
    if (!is_numeric($etudiant['note']) || (float) $etudiant['note'] < 0 || (float) $etudiant['note'] > 20) {
        $erreurs[] = 'La note doit être un nombre compris entre 0 et 20.';
    }

    if ($erreurs === []) {
        try {
            $anciennePhoto = (string) $etudiant['photo'];
            $nouvellePhoto = traiter_photo($_FILES['photo'] ?? [], $anciennePhoto);
            $etudiant['photo'] = $nouvellePhoto;
            if ($edition) {
                $etudiants[$id] = $etudiant + ['id' => $id];
            } else {
                $nouvelId = $etudiants === [] ? 1 : max(array_keys($etudiants)) + 1;
                $etudiant['id'] = $nouvelId;
                $etudiants[$nouvelId] = $etudiant;
            }
            sauver_etudiants($etudiants);
            if ($nouvellePhoto !== $anciennePhoto && $anciennePhoto !== '') {
                supprimer_photo($anciennePhoto);
            }
            $_SESSION['flash'] = [
                'type' => 'success',
                'texte' => $edition ? 'Les modifications ont été enregistrées.' : 'Étudiant ajouté avec succès.',
            ];
            header('Location: liste.php');
            exit;
        } catch (RuntimeException $exception) {
            $erreurs[] = $exception->getMessage();
        }
    }
}

$titre = $edition ? 'Modifier un étudiant' : 'Ajouter un étudiant';
include __DIR__ . '/header.php';
?>
<section class="card form-card">
    <h1><?= $edition ? 'Modifier un étudiant' : 'Ajouter un étudiant' ?></h1>
    <?php if ($erreurs !== []): ?>
        <div class="message error" role="alert">
            <p>Veuillez corriger les erreurs suivantes :</p>
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= h($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= $edition ? 'ajouter.php?id=' . (int) $id : 'ajouter.php' ?>">
        <label for="nom">Nom</label>
        <input id="nom" name="nom" type="text" maxlength="100" value="<?= h($etudiant['nom']) ?>" required>

        <label for="prenom">Prénom</label>
        <input id="prenom" name="prenom" type="text" maxlength="100" value="<?= h($etudiant['prenom']) ?>" required>

        <label for="filiere">Filière</label>
        <select id="filiere" name="filiere" required>
            <option value="">Choisir une filière</option>
            <?php foreach (FILIERES as $filiere): ?>
                <option value="<?= h($filiere) ?>" <?= $etudiant['filiere'] === $filiere ? 'selected' : '' ?>><?= h($filiere) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="note">Note sur 20</label>
        <input id="note" name="note" type="number" min="0" max="20" step="any" value="<?= h($etudiant['note']) ?>" required>

        <label for="photo">Photo (JPG, JPEG ou PNG, 1 Mo maximum)</label>
        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
        <?php if ($edition && $etudiant['photo'] !== ''): ?>
            <p class="current-photo">Photo actuelle : <img class="avatar" src="uploads/<?= h($etudiant['photo']) ?>" alt="Photo actuelle"></p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit"><?= $edition ? 'Enregistrer les modifications' : 'Ajouter l’étudiant' ?></button>
            <a class="button secondary" href="liste.php">Annuler</a>
        </div>
    </form>
</section>
<?php include __DIR__ . '/footer.php'; ?>
