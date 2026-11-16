<?php
require_once __DIR__ . '/functions.php';
exiger_connexion();

$etudiants = lire_etudiants();
$q = trim((string) ($_GET['q'] ?? ''));
$tri = (string) ($_GET['tri'] ?? 'ajout');
$trisAutorises = ['ajout', 'nom', 'filiere', 'note'];
if (!in_array($tri, $trisAutorises, true)) {
    $tri = 'ajout';
}

$affiches = array_filter($etudiants, static function (array $etudiant) use ($q): bool {
    return $q === ''
        || stripos($etudiant['nom'], $q) !== false
        || stripos($etudiant['prenom'], $q) !== false;
});

if ($tri === 'nom') {
    uasort($affiches, static fn(array $a, array $b): int => strcasecmp($a['nom'], $b['nom']) ?: ($a['id'] <=> $b['id']));
} elseif ($tri === 'filiere') {
    uasort($affiches, static fn(array $a, array $b): int => strcasecmp($a['filiere'], $b['filiere']) ?: ($a['id'] <=> $b['id']));
} elseif ($tri === 'note') {
    uasort($affiches, static fn(array $a, array $b): int => ((float) $b['note'] <=> (float) $a['note']) ?: ($a['id'] <=> $b['id']));
} else {
    ksort($affiches);
}

$notes = array_map(static fn(array $etudiant): float => (float) $etudiant['note'], $affiches);
$nombre = count($affiches);
$moyenne = $nombre > 0 ? array_sum($notes) / $nombre : null;
$meilleureNote = $nombre > 0 ? max($notes) : null;
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$titre = 'Liste des étudiants';
include __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Étudiants</h1>
        <p>Consultez, recherchez et gérez les étudiants.</p>
    </div>
    <a class="button" href="ajouter.php">Ajouter un étudiant</a>
</div>

<?php if (is_array($message)): ?>
    <p class="message <?= h((string) ($message['type'] ?? 'success')) ?>" role="status"><?= h((string) ($message['texte'] ?? '')) ?></p>
<?php endif; ?>

<form class="filters card" method="get" action="liste.php">
    <div>
        <label for="q">Recherche (nom ou prénom)</label>
        <input id="q" name="q" type="search" value="<?= h($q) ?>" placeholder="Ex. Sara">
    </div>
    <div>
        <label for="tri">Trier par</label>
        <select id="tri" name="tri">
            <option value="ajout" <?= $tri === 'ajout' ? 'selected' : '' ?>>Ordre d’ajout</option>
            <option value="nom" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom</option>
            <option value="filiere" <?= $tri === 'filiere' ? 'selected' : '' ?>>Filière</option>
            <option value="note" <?= $tri === 'note' ? 'selected' : '' ?>>Note décroissante</option>
        </select>
    </div>
    <button type="submit">Filtrer</button>
</form>

<section class="stats" aria-label="Statistiques">
    <div class="stat"><strong><?= $nombre ?></strong><span>étudiant<?= $nombre > 1 ? 's' : '' ?> affiché<?= $nombre > 1 ? 's' : '' ?></span></div>
    <div class="stat"><strong><?= $moyenne === null ? '—' : h(number_format($moyenne, 2, ',', ' ')) ?></strong><span>Moyenne</span></div>
    <div class="stat"><strong><?= $meilleureNote === null ? '—' : h(number_format($meilleureNote, 2, ',', ' ')) ?></strong><span>Meilleure note</span></div>
</section>

<div class="table-wrap">
    <table id="etudiants-table">
        <thead>
        <tr><th>Photo</th><th>Nom</th><th>Prénom</th><th>Filière</th><th>Note</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($affiches === []): ?>
            <tr><td class="empty" colspan="6">Aucun étudiant</td></tr>
        <?php else: ?>
            <?php foreach ($affiches as $etudiant): ?>
                <?php $classeNote = (float) $etudiant['note'] >= 10 ? 'note-positive' : 'note-negative'; ?>
                <tr class="etudiant-row" data-nom="<?= h($etudiant['nom']) ?>" data-prenom="<?= h($etudiant['prenom']) ?>">
                    <td>
                        <?php if ($etudiant['photo'] !== ''): ?>
                            <img class="avatar" src="uploads/<?= h($etudiant['photo']) ?>" alt="Photo de <?= h($etudiant['prenom']) ?>">
                        <?php else: ?>
                            <span class="avatar-placeholder" aria-label="Aucune photo">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h(strtoupper($etudiant['nom'])) ?></td>
                    <td><?= h(ucfirst($etudiant['prenom'])) ?></td>
                    <td><?= h($etudiant['filiere']) ?></td>
                    <td class="<?= $classeNote ?>"><?= h($etudiant['note']) ?>/20</td>
                    <td class="actions">
                        <a href="ajouter.php?id=<?= (int) $etudiant['id'] ?>">Modifier</a>
                        <a class="delete-link" href="supprimer.php?id=<?= (int) $etudiant['id'] ?>">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
