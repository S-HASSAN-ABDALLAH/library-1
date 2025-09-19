<?php
require_once 'autoload.php';

$bookManager = new BookManager();
$authorManager = new AuthorManager();

$search = $_GET['search'] ?? '';
$categorie = $_GET['categorie'] ?? '';
$disponible = $_GET['disponible'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($_POST['action'] ?? '' == 'add') {
    $bookManager->addBook(
        $_POST['titre'],
        $_POST['id_auteur'],
        $_POST['categorie'],
        $_POST['isbn'],
        $_POST['annee_publication'],
        $_POST['disponible'] ?? 1
    );
    header('Location: livres_modular.php');
    exit;
}

// Récupérer l'ID du livre à éditer AVANT le traitement POST
$editLivre = null;
if ($_GET['edit'] ?? false) {
    $editLivre = $bookManager->getBookById($_GET['edit']);
}

if ($_POST['action'] ?? '' == 'edit') {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $bookManager->updateBook(
            $_POST['id'],
            $_POST['titre'],
            $_POST['id_auteur'],
            $_POST['categorie'],
            $_POST['isbn'],
            $_POST['annee_publication'],
            $_POST['disponible'] ?? 1
        );
    } else {
        // Si pas d'ID, c'est un ajout
        $bookManager->addBook(
            $_POST['titre'],
            $_POST['id_auteur'],
            $_POST['categorie'],
            $_POST['isbn'],
            $_POST['annee_publication'],
            $_POST['disponible'] ?? 1
        );
    }
    header('Location: livres_modular.php');
    exit;
}

if ($_GET['delete'] ?? false) {
    $bookManager->deleteBook($_GET['delete']);
    header('Location: livres_modular.php');
    exit;
}

$total = $bookManager->countBooks($search, $categorie, $disponible);
$totalPages = ceil($total / $limit);
$livres = $bookManager->getBooks($search, $categorie, $disponible, $limit, $offset);
$auteurs = $authorManager->getAllAuthorsForSelect();
$categories = $bookManager->getCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres - Version Modulaire</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs.php">Auteurs</a>
        <a href="livres_modular.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <div class="container">
        <h1>Gestion des Livres (Version Modulaire)</h1>

        <div class="form-container">
            <h3><?= $editLivre ? 'Modifier' : 'Ajouter' ?> un livre</h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editLivre ? 'edit' : 'add' ?>">
                <?php if ($editLivre): ?>
                    <input type="hidden" name="id" value="<?= $editLivre['id_livre'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Titre :</label>
                        <input type="text" name="titre" value="<?= $editLivre['titre'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur :</label>
                        <select name="id_auteur" required>
                            <option value="">Sélectionner un auteur</option>
                            <?php foreach ($auteurs as $auteur): ?>
                                <option value="<?= $auteur['id_auteur'] ?>"
                                        <?= ($editLivre && $editLivre['id_auteur'] == $auteur['id_auteur']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($auteur['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Catégorie :</label>
                        <input type="text" name="categorie" value="<?= $editLivre['categorie'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>ISBN :</label>
                        <input type="text" name="isbn" value="<?= $editLivre['isbn'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Année :</label>
                        <input type="number" name="annee_publication" value="<?= $editLivre['annee_publication'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="disponible" value="1"
                               <?= (!$editLivre || $editLivre['disponible']) ? 'checked' : '' ?>>
                        Disponible
                    </label>
                </div>

                <button type="submit"><?= $editLivre ? 'Modifier' : 'Ajouter' ?></button>
                <?php if ($editLivre): ?>
                    <a href="livres_modular.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="search">
            <form method="GET">
                <div class="grid-2-1-1">
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Titre ou auteur">
                    </div>
                    <div>
                        <label>Catégorie :</label>
                        <select name="categorie">
                            <option value="">Toutes</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= $categorie == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Statut :</label>
                        <select name="disponible">
                            <option value="">Tous</option>
                            <option value="1" <?= $disponible === '1' ? 'selected' : '' ?>>Disponible</option>
                            <option value="0" <?= $disponible === '0' ? 'selected' : '' ?>>Emprunté</option>
                        </select>
                    </div>
                    <button type="submit">Filtrer</button>
                    <a href="livres_modular.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Auteur</th>
                    <th>Catégorie</th>
                    <th>Année</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($livres as $livre): ?>
                <tr>
                    <td><?= $livre['id_livre'] ?></td>
                    <td><?= htmlspecialchars($livre['titre']) ?></td>
                    <td><?= htmlspecialchars($livre['auteur']) ?></td>
                    <td><?= htmlspecialchars($livre['categorie']) ?></td>
                    <td><?= $livre['annee_publication'] ?></td>
                    <td>
                        <span class="<?= $livre['disponible'] ? 'status-disponible' : 'status-indisponible' ?>">
                            <?= $livre['disponible'] ? 'Disponible' : 'Emprunté' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="?edit=<?= $livre['id_livre'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $livre['id_livre'] ?>" class="btn-delete"
                           onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryParams = [];
            if ($search) $queryParams[] = 'search=' . urlencode($search);
            if ($categorie) $queryParams[] = 'categorie=' . urlencode($categorie);
            if ($disponible !== '') $queryParams[] = 'disponible=' . urlencode($disponible);
            $queryString = $queryParams ? '&' . implode('&', $queryParams) : '';
            ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <p>Total : <?= $total ?> livre(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>