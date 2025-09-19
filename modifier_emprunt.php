<?php
require_once 'config.php';

// Vérifier qu'un ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: emprunts.php');
    exit;
}

$id_emprunt = $_GET['id'];

// Traitement de la modification
if ($_POST) {
    $stmt = $pdo->prepare("UPDATE emprunts SET nom_emprunteur=?, email_emprunteur=?, date_emprunt=?, date_retour_prevue=? WHERE id_emprunt=?");
    $result = $stmt->execute([
        $_POST['nom_emprunteur'],
        $_POST['email_emprunteur'],
        $_POST['date_emprunt'],
        $_POST['date_retour_prevue'],
        $id_emprunt
    ]);

    if ($result) {
        header('Location: emprunts.php?success=modified');
        exit;
    } else {
        $error = "Erreur lors de la modification";
    }
}

// Récupérer les données de l'emprunt avec les infos du livre
$stmt = $pdo->prepare("
    SELECT e.*, l.titre, CONCAT(a.prenom, ' ', a.nom) as auteur_nom
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id_livre
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    WHERE e.id_emprunt = ?
");
$stmt->execute([$id_emprunt]);
$emprunt = $stmt->fetch();

if (!$emprunt) {
    header('Location: emprunts.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'emprunt</title>
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
        <h1>Modifier l'emprunt</h1>

        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <div style="background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <h3>Livre emprunté</h3>
                <p><strong><?= htmlspecialchars($emprunt['titre']) ?></strong> par <?= htmlspecialchars($emprunt['auteur_nom']) ?></p>
                <small>ID de l'emprunt: <?= $emprunt['id_emprunt'] ?></small>
            </div>

            <form method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom de l'emprunteur :</label>
                        <input type="text" name="nom_emprunteur" value="<?= htmlspecialchars($emprunt['nom_emprunteur']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email :</label>
                        <input type="email" name="email_emprunteur" value="<?= htmlspecialchars($emprunt['email_emprunteur']) ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Date d'emprunt :</label>
                        <input type="date" name="date_emprunt" value="<?= $emprunt['date_emprunt'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date de retour prévue :</label>
                        <input type="date" name="date_retour_prevue" value="<?= $emprunt['date_retour_prevue'] ?>" required>
                    </div>
                </div>

                <?php if ($emprunt['date_retour_effective']): ?>
                <div style="background-color: #d4edda; padding: 10px; margin: 15px 0; border-radius: 5px;">
                    <strong>Livre retourné le :</strong> <?= date('d/m/Y', strtotime($emprunt['date_retour_effective'])) ?>
                </div>
                <?php endif; ?>

                <button type="submit">Modifier l'emprunt</button>
                <a href="emprunts.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>