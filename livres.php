<?php
/**
 * GESTION DES LIVRES - Système de gestion de bibliothèque
 *
 * Cette page permet de :
 * - Afficher le catalogue des livres avec pagination
 * - Rechercher des livres par titre ou auteur
 * - Filtrer par catégorie et disponibilité
 * - Ajouter de nouveaux livres au catalogue
 * - Modifier les informations des livres existants
 * - Supprimer des livres du catalogue
 */

// Inclusion de la configuration de base de données
require_once 'config.php';

// === RÉCUPÉRATION DES PARAMÈTRES ===
$search = $_GET['search'] ?? '';           // Terme de recherche (titre/auteur)
$categorie = $_GET['categorie'] ?? '';     // Filtre par catégorie
$disponible = $_GET['disponible'] ?? '';   // Filtre par disponibilité
$page = max(1, $_GET['page'] ?? 1);        // Page courante (minimum 1)
$limit = 10;                               // Nombre d'éléments par page
$offset = ($page - 1) * $limit;            // Décalage pour la pagination

// === TRAITEMENT DES ACTIONS ===

// Action : Ajouter un nouveau livre
if ($_POST['action'] ?? '' == 'add') {
    $stmt = $pdo->prepare("INSERT INTO livres (titre, id_auteur, categorie, isbn, annee_publication, disponible) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['titre'], $_POST['id_auteur'], $_POST['categorie'], $_POST['isbn'], $_POST['annee_publication'], $_POST['disponible'] ?? 1]);
    header('Location: livres.php'); // Redirection pour éviter la re-soumission
    exit;
}

// Action : Modifier un livre existant
if ($_POST['action'] ?? '' == 'edit') {
    $stmt = $pdo->prepare("UPDATE livres SET titre=?, id_auteur=?, categorie=?, isbn=?, annee_publication=?, disponible=? WHERE id_livre=?");
    $stmt->execute([$_POST['titre'], $_POST['id_auteur'], $_POST['categorie'], $_POST['isbn'], $_POST['annee_publication'], $_POST['disponible'] ?? 1, $_POST['id']]);
    header('Location: livres.php'); // Redirection après modification
    exit;
}

// Action : Supprimer un livre
if ($_GET['delete'] ?? false) {
    $stmt = $pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: livres.php'); // Redirection après suppression
    exit;
}

// === CONSTRUCTION DES FILTRES DE RECHERCHE ===

$whereConditions = [];  // Conditions WHERE pour les filtres
$params = [];          // Paramètres pour les requêtes préparées

// Filtre de recherche par titre ou auteur
if ($search) {
    $whereConditions[] = "(l.titre LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtre par catégorie
if ($categorie) {
    $whereConditions[] = "l.categorie = ?";
    $params[] = $categorie;
}

// Filtre par disponibilité
if ($disponible !== '') {
    $whereConditions[] = "l.disponible = ?";
    $params[] = $disponible;
}

// Construction de la clause WHERE finale
$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// === REQUÊTES DE DONNÉES ===

// Compter le nombre total de livres (pour la pagination)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM livres l JOIN auteurs a ON l.id_auteur = a.id_auteur $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les livres de la page courante avec informations auteur
$stmt = $pdo->prepare("SELECT l.*, CONCAT(a.prenom, ' ', a.nom) as auteur FROM livres l JOIN auteurs a ON l.id_auteur = a.id_auteur $whereClause ORDER BY l.titre LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$livres = $stmt->fetchAll();

// === DONNÉES POUR LES FORMULAIRES ===

// Liste des auteurs pour le formulaire d'ajout/modification
$auteursStmt = $pdo->query("SELECT id_auteur, CONCAT(prenom, ' ', nom) as nom_complet FROM auteurs ORDER BY nom, prenom");
$auteurs = $auteursStmt->fetchAll();

// Liste des catégories existantes pour le filtre
$categoriesStmt = $pdo->query("SELECT DISTINCT categorie FROM livres WHERE categorie IS NOT NULL ORDER BY categorie");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

// === GESTION DE L'ÉDITION ===

// Si un livre est en cours d'édition, récupérer ses données
$editLivre = null;
if ($_GET['edit'] ?? false) {
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id_livre = ?");
    $stmt->execute([$_GET['edit']]);
    $editLivre = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation principale -->
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs.php">Auteurs</a>
        <a href="livres.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <!-- Conteneur principal -->
    <div class="container">
        <h1>Gestion des Livres</h1>

        <!-- Formulaire d'ajout/modification -->
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
                    <label class="checkbox-label">
                        <input type="checkbox" name="disponible" value="1" <?= (!$editLivre || $editLivre['disponible']) ? 'checked' : '' ?>>
                        Disponible
                    </label>
                </div>

                <button type="submit"><?= $editLivre ? 'Modifier' : 'Ajouter' ?></button>
                <?php if ($editLivre): ?>
                    <a href="livres.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Barre de recherche et filtres -->
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
                    <a href="livres.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Tableau des livres -->
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
                        <a href="?delete=<?= $livre['id_livre'] ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination (si nécessaire) -->
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
                <a href="?page=<?= $i ?><?= $queryString ?>" class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Informations de pagination -->
        <p>Total : <?= $total ?> livre(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>