<?php
/**
 * GESTION DES AUTEURS - Système de gestion de bibliothèque
 *
 * Cette page permet de :
 * - Afficher la liste des auteurs avec pagination
 * - Rechercher des auteurs par nom, prénom ou nationalité
 * - Ajouter de nouveaux auteurs
 * - Modifier les informations d'auteurs existants
 * - Supprimer des auteurs
 */

// Inclusion de la configuration de base de données
require_once 'config.php';

// === RÉCUPÉRATION DES PARAMÈTRES ===
$search = $_GET['search'] ?? '';           // Terme de recherche
$page = max(1, $_GET['page'] ?? 1);        // Page courante (minimum 1)
$limit = 10;                               // Nombre d'éléments par page
$offset = ($page - 1) * $limit;            // Décalage pour la pagination

// === TRAITEMENT DES ACTIONS ===

// Action : Ajouter un nouvel auteur
if ($_POST['action'] ?? '' == 'add') {
    $stmt = $pdo->prepare("INSERT INTO auteurs (nom, prenom, date_naissance, nationalite) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['nationalite']]);
    header('Location: auteurs.php'); // Redirection pour éviter la re-soumission
    exit;
}

// Action : Modifier un auteur existant
if ($_POST['action'] ?? '' == 'edit') {
    $stmt = $pdo->prepare("UPDATE auteurs SET nom=?, prenom=?, date_naissance=?, nationalite=? WHERE id_auteur=?");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['nationalite'], $_POST['id']]);
    header('Location: auteurs.php'); // Redirection après modification
    exit;
}

// Action : Supprimer un auteur
if ($_GET['delete'] ?? false) {
    $stmt = $pdo->prepare("DELETE FROM auteurs WHERE id_auteur = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: auteurs.php'); // Redirection après suppression
    exit;
}

// === CONSTRUCTION DES REQUÊTES DE RECHERCHE ===

$whereClause = "";  // Clause WHERE pour la recherche
$params = [];       // Paramètres pour les requêtes préparées

// Si une recherche est effectuée, construire la clause WHERE
if ($search) {
    $whereClause = "WHERE nom LIKE ? OR prenom LIKE ? OR nationalite LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// === REQUÊTES DE DONNÉES ===

// Compter le nombre total d'auteurs (pour la pagination)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM auteurs $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les auteurs de la page courante
$stmt = $pdo->prepare("SELECT * FROM auteurs $whereClause ORDER BY nom, prenom LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$auteurs = $stmt->fetchAll();

// === GESTION DE L'ÉDITION ===

// Si un auteur est en cours d'édition, récupérer ses données
$editAuteur = null;
if ($_GET['edit'] ?? false) {
    $stmt = $pdo->prepare("SELECT * FROM auteurs WHERE id_auteur = ?");
    $stmt->execute([$_GET['edit']]);
    $editAuteur = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Auteurs</title>
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
        <h1>Gestion des Auteurs</h1>

        <!-- Formulaire d'ajout/modification -->
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
                        <label>Date de naissance :</label>
                        <input type="date" name="date_naissance" value="<?= $editAuteur['date_naissance'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nationalité :</label>
                        <input type="text" name="nationalite" value="<?= $editAuteur['nationalite'] ?? '' ?>">
                    </div>
                </div>

                <button type="submit"><?= $editAuteur ? 'Modifier' : 'Ajouter' ?></button>
                <?php if ($editAuteur): ?>
                    <a href="auteurs.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Barre de recherche -->
        <div class="search">
            <form method="GET">
                <div class="grid-form">
                    <div style="flex: 1;">
                        <label>Rechercher :</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom ou nationalité">
                    </div>
                    <button type="submit">Rechercher</button>
                    <a href="auteurs.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Tableau des auteurs -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date de naissance</th>
                    <th>Nationalité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auteurs as $auteur): ?>
                <tr>
                    <td><?= $auteur['id_auteur'] ?></td>
                    <td><?= htmlspecialchars($auteur['nom']) ?></td>
                    <td><?= htmlspecialchars($auteur['prenom']) ?></td>
                    <td><?= $auteur['date_naissance'] ?></td>
                    <td><?= htmlspecialchars($auteur['nationalite']) ?></td>
                    <td class="actions">
                        <a href="?edit=<?= $auteur['id_auteur'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $auteur['id_auteur'] ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination (si nécessaire) -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Informations de pagination -->
        <p>Total : <?= $total ?> auteur(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>