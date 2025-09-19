<?php
require_once 'config.php';

// Vérifier qu'un ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: auteurs.php');
    exit;
}

$id_auteur = $_GET['id'];

// Traitement de la modification
if ($_POST) {
    $stmt = $pdo->prepare("UPDATE auteurs SET nom=?, prenom=?, nationalite=?, date_naissance=? WHERE id_auteur=?");
    $result = $stmt->execute([
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['nationalite'] ?: null,
        $_POST['date_naissance'] ?: null,
        $id_auteur
    ]);

    if ($result) {
        header('Location: auteurs.php?success=modified');
        exit;
    } else {
        $error = "Erreur lors de la modification";
    }
}

// Récupérer les données de l'auteur
$stmt = $pdo->prepare("SELECT * FROM auteurs WHERE id_auteur = ?");
$stmt->execute([$id_auteur]);
$auteur = $stmt->fetch();

if (!$auteur) {
    header('Location: auteurs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'auteur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs.php">Auteurs</a>
        <a href="livres.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <div class="container">
        <h1>Modifier l'auteur</h1>

        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom :</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($auteur['nom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom :</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($auteur['prenom']) ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nationalité :</label>
                        <input type="text" name="nationalite" value="<?= htmlspecialchars($auteur['nationalite']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Date de naissance :</label>
                        <input type="date" name="date_naissance" value="<?= $auteur['date_naissance'] ?>">
                    </div>
                </div>

                <button type="submit">Modifier l'auteur</button>
                <a href="auteurs.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>