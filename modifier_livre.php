<?php
require_once 'config.php';

// Vérifier qu'un ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: livres.php');
    exit;
}

$id_livre = $_GET['id'];

// Traitement de la modification
if ($_POST) {
    $stmt = $pdo->prepare("UPDATE livres SET titre=?, id_auteur=?, categorie=?, isbn=?, annee_publication=?, disponible=? WHERE id_livre=?");
    $result = $stmt->execute([
        $_POST['titre'],
        $_POST['id_auteur'],
        $_POST['categorie'],
        $_POST['isbn'],
        $_POST['annee_publication'],
        $_POST['disponible'] ?? 0,
        $id_livre
    ]);

    if ($result) {
        header('Location: livres.php?success=modified');
        exit;
    } else {
        $error = "Erreur lors de la modification";
    }
}

// Récupérer les données du livre
$stmt = $pdo->prepare("SELECT * FROM livres WHERE id_livre = ?");
$stmt->execute([$id_livre]);
$livre = $stmt->fetch();

if (!$livre) {
    header('Location: livres.php');
    exit;
}

// Récupérer les auteurs
$auteursStmt = $pdo->query("
    SELECT id_auteur, CONCAT(prenom, ' ', nom) as nom_complet
    FROM auteurs
    ORDER BY nom, prenom
");
$auteurs = $auteursStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le livre</title>
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
        <h1>Modifier le livre</h1>

        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Titre :</label>
                        <input type="text" name="titre" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur :</label>
                        <select name="id_auteur" required>
                            <option value="">Sélectionner un auteur</option>
                            <?php foreach ($auteurs as $auteur): ?>
                                <option value="<?= $auteur['id_auteur'] ?>"
                                        <?= $livre['id_auteur'] == $auteur['id_auteur'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($auteur['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Catégorie :</label>
                        <input type="text" name="categorie" value="<?= htmlspecialchars($livre['categorie']) ?>">
                    </div>
                    <div class="form-group">
                        <label>ISBN :</label>
                        <input type="text" name="isbn" value="<?= htmlspecialchars($livre['isbn']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Année :</label>
                        <input type="number" name="annee_publication" value="<?= $livre['annee_publication'] ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="disponible" value="1" <?= $livre['disponible'] ? 'checked' : '' ?>>
                        Disponible
                    </label>
                </div>

                <button type="submit">Modifier le livre</button>
                <a href="livres.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>