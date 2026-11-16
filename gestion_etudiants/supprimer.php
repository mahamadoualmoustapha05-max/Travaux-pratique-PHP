<?php
require_once __DIR__ . '/functions.php';
exiger_connexion();

$id = (int) ($_GET['id'] ?? 0);
$etudiants = lire_etudiants();
if ($id <= 0 || !isset($etudiants[$id])) {
    $_SESSION['flash'] = ['type' => 'error', 'texte' => 'Étudiant introuvable.'];
    header('Location: liste.php');
    exit;
}

$photo = (string) $etudiants[$id]['photo'];
unset($etudiants[$id]);
sauver_etudiants($etudiants);
if ($photo !== '') {
    supprimer_photo($photo);
}

$_SESSION['flash'] = ['type' => 'success', 'texte' => 'Étudiant supprimé avec succès.'];
header('Location: liste.php');
exit;
