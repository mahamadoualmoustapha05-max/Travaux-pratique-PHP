<?php
declare(strict_types=1);

session_start();

const FICHIER = __DIR__ . '/data/etudiants.txt';
const FILIERES = ['GI', 'IDIA', 'GE', 'GC'];
const UTILISATEURS = [
    'admin' => 'admin123',
];

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function connecte(): bool
{
    return isset($_SESSION['login']) && is_string($_SESSION['login']);
}

function exiger_connexion(): void
{
    if (!connecte()) {
        header('Location: index.php');
        exit;
    }
}

function lire_etudiants(): array
{
    if (!is_file(FICHIER)) {
        throw new RuntimeException('Le fichier des étudiants est introuvable.');
    }

    $lignes = file(FICHIER, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lignes === false) {
        throw new RuntimeException('Impossible de lire le fichier des étudiants.');
    }

    $etudiants = [];
    foreach ($lignes as $ligne) {
        $champs = explode(';', $ligne);
        if (count($champs) !== 6 || !ctype_digit($champs[0])) {
            throw new RuntimeException('Une ligne du fichier des étudiants est invalide.');
        }

        $id = (int) $champs[0];
        $etudiants[$id] = [
            'id' => $id,
            'nom' => $champs[1],
            'prenom' => $champs[2],
            'filiere' => $champs[3],
            'note' => $champs[4],
            'photo' => $champs[5],
        ];
    }

    return $etudiants;
}

function sauver_etudiants(array $liste): void
{
    $lignes = [];
    foreach ($liste as $id => $etudiant) {
        $lignes[] = implode(';', [
            (string) (int) $id,
            nettoyer((string) $etudiant['nom']),
            nettoyer((string) $etudiant['prenom']),
            nettoyer((string) $etudiant['filiere']),
            nettoyer((string) $etudiant['note']),
            nettoyer((string) ($etudiant['photo'] ?? '')),
        ]);
    }

    if (file_put_contents(FICHIER, implode(PHP_EOL, $lignes) . ($lignes === [] ? '' : PHP_EOL), LOCK_EX) === false) {
        throw new RuntimeException('Impossible d’enregistrer les étudiants.');
    }
}

function nettoyer(string $s): string
{
    return trim(str_replace([";", "\r", "\n"], '', $s));
}

function traiter_photo(array $fichier, string $ancienne = ''): string
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $ancienne;
    }
    if (($fichier['error'] ?? null) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement de la photo a échoué.');
    }
    if (($fichier['size'] ?? 0) > 1024 * 1024) {
        throw new RuntimeException('La photo ne doit pas dépasser 1 Mo.');
    }

    $nomOriginal = (string) ($fichier['name'] ?? '');
    $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        throw new RuntimeException('La photo doit être au format JPG, JPEG ou PNG.');
    }

    $tmp = (string) ($fichier['tmp_name'] ?? '');
    $image = $tmp !== '' ? @getimagesize($tmp) : false;
    $typesAutorises = [
        'jpg' => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
    ];
    if ($image === false || $image[2] !== $typesAutorises[$extension]) {
        throw new RuntimeException('Le fichier envoyé n’est pas une image valide.');
    }

    $nomPhoto = uniqid('etu_', true) . '.' . $extension;
    $destination = __DIR__ . '/uploads/' . $nomPhoto;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Impossible d’enregistrer la photo.');
    }

    return $nomPhoto;
}

function supprimer_photo(string $photo): void
{
    if ($photo === '' || basename($photo) !== $photo) {
        return;
    }

    $chemin = __DIR__ . '/uploads/' . $photo;
    if (is_file($chemin) && !unlink($chemin)) {
        throw new RuntimeException('Impossible de supprimer la photo.');
    }
}
