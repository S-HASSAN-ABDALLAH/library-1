<?php
require_once 'autoload.php';

$authorManager = new AuthorManager();

$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($_POST['action'] ?? '' == 'add') {
    $authorManager->addAuthor(
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['nationalite'] ?: null,
        $_POST['date_naissance'] ?: null
    );
    header('Location: auteurs_modular.php');
    exit;
}

if ($_POST['action'] ?? '' == 'edit') {
    $authorManager->updateAuthor(
        $_POST['id'],
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['nationalite'] ?: null,
        $_POST['date_naissance'] ?: null
    );
    header('Location: auteurs_modular.php');
    exit;
}

if ($_GET['delete'] ?? false) {
    if (!$authorManager->hasBooks($_GET['delete'])) {
        $authorManager->deleteAuthor($_GET['delete']);
    }
    header('Location: auteurs_modular.php');
    exit;
}

$editAuteur = null;
if ($_GET['edit'] ?? false) {
    $editAuteur = $authorManager->getAuthorById($_GET['edit']);
}

$total = $authorManager->countAuthors($search);
$totalPages = ceil($total / $limit);
$auteurs = $authorManager->getAuthors($search, $limit, $offset);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Auteurs - Version Modulaire</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs_modular.php">Auteurs</a>
        <a href="livres_modular.php">Livres</a>
        <a href="emprunts_modular.php">Emprunts</a>
    </div>

    <div class="container">
        <h1>Gestion des Auteurs (Version Modulaire)</h1>

        <div class="form-container">
            <h3><?= $editAuteur ? 'Modifier' : 'Ajouter' ?> un auteur</h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editAuteur ? 'edit' : 'add' ?>">
                <?php if ($editAuteur): ?>
                    <input type="hidden" name="id" value="<?= $editAuteur['id_auteur'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom :</label>
                        <input type="text" name="nom" value="<?= $editAuteur['nom'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom :</label>
                        <input type="text" name="prenom" value="<?= $editAuteur['prenom'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nationalité :</label>
                        <input type="text" name="nationalite" value="<?= $editAuteur['nationalite'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Date de naissance :</label>
                        <input type="date" name="date_naissance" value="<?= $editAuteur['date_naissance'] ?? '' ?>">
                    </div>
                </div>

                <button type="submit"><?= $editAuteur ? 'Modifier' : 'Ajouter' ?></button>
                <?php if ($editAuteur): ?>
                    <a href="auteurs_modular.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="search">
            <form method="GET">
                <div class="grid-2-1-1">
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom ou prénom">
                    </div>
                    <button type="submit">Rechercher</button>
                    <a href="auteurs_modular.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Nationalité</th>
                    <th>Date de naissance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auteurs as $auteur): ?>
                <tr>
                    <td><?= $auteur['id_auteur'] ?></td>
                    <td><?= htmlspecialchars($auteur['nom']) ?></td>
                    <td><?= htmlspecialchars($auteur['prenom']) ?></td>
                    <td><?= htmlspecialchars($auteur['nationalite'] ?? '') ?></td>
                    <td><?= $auteur['date_naissance'] ? date('d/m/Y', strtotime($auteur['date_naissance'])) : '' ?></td>
                    <td class="actions">
                        <a href="?edit=<?= $auteur['id_auteur'] ?>" class="btn-edit">Modifier</a>
                        <?php if (!$authorManager->hasBooks($auteur['id_auteur'])): ?>
                            <a href="?delete=<?= $auteur['id_auteur'] ?>" class="btn-delete"
                               onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Auteur avec livres</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryString = $search ? '&search=' . urlencode($search) : '';
            ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <p>Total : <?= $total ?> auteur(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>