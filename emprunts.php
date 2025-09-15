<?php
/**
 * GESTION DES EMPRUNTS - Système de gestion de bibliothèque
 *
 * Cette page permet de :
 * - Afficher la liste des emprunts avec pagination
 * - Rechercher par emprunteur, titre ou auteur
 * - Filtrer par statut (en cours, retournés, en retard)
 * - Créer de nouveaux emprunts
 * - Modifier les emprunts existants
 * - Marquer des livres comme retournés
 * - Supprimer des emprunts
 * - Gérer automatiquement la disponibilité des livres
 */

// Inclusion de la configuration de base de données
require_once 'config.php';

// === RÉCUPÉRATION DES PARAMÈTRES ===
$search = $_GET['search'] ?? '';           // Terme de recherche (emprunteur/titre/auteur)
$statut = $_GET['statut'] ?? '';           // Filtre par statut d'emprunt
$page = max(1, $_GET['page'] ?? 1);        // Page courante (minimum 1)
$limit = 10;                               // Nombre d'éléments par page
$offset = ($page - 1) * $limit;            // Décalage pour la pagination

// === TRAITEMENT DES ACTIONS ===

// Action : Créer un nouvel emprunt
if ($_POST['action'] ?? '' == 'add') {
    // Insertion de l'emprunt
    $stmt = $pdo->prepare("INSERT INTO emprunts (id_livre, nom_emprunteur, date_emprunt, date_retour_prevue) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['id_livre'], $_POST['nom_emprunteur'], $_POST['date_emprunt'], $_POST['date_retour_prevue']]);

    // Marquer le livre comme indisponible
    $pdo->prepare("UPDATE livres SET disponible = FALSE WHERE id_livre = ?")->execute([$_POST['id_livre']]);
    header('Location: emprunts.php'); // Redirection pour éviter la re-soumission
    exit;
}

// Action : Modifier un emprunt existant
if ($_POST['action'] ?? '' == 'edit') {
    $stmt = $pdo->prepare("UPDATE emprunts SET id_livre=?, nom_emprunteur=?, date_emprunt=?, date_retour_prevue=?, date_retour_effective=? WHERE id_emprunt=?");
    $stmt->execute([$_POST['id_livre'], $_POST['nom_emprunteur'], $_POST['date_emprunt'], $_POST['date_retour_prevue'], $_POST['date_retour_effective'] ?: null, $_POST['id']]);
    header('Location: emprunts.php'); // Redirection après modification
    exit;
}

// Action : Supprimer un emprunt
if ($_GET['delete'] ?? false) {
    // Récupérer l'ID du livre avant suppression
    $stmt = $pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['delete']]);
    $id_livre = $stmt->fetchColumn();

    // Supprimer l'emprunt et rendre le livre disponible
    $pdo->prepare("DELETE FROM emprunts WHERE id_emprunt = ?")->execute([$_GET['delete']]);
    $pdo->prepare("UPDATE livres SET disponible = TRUE WHERE id_livre = ?")->execute([$id_livre]);
    header('Location: emprunts.php'); // Redirection après suppression
    exit;
}

// Action : Marquer un livre comme retourné
if ($_GET['retour'] ?? false) {
    // Mettre à jour la date de retour effective
    $stmt = $pdo->prepare("UPDATE emprunts SET date_retour_effective = CURDATE() WHERE id_emprunt = ?");
    $stmt->execute([$_GET['retour']]);

    // Récupérer l'ID du livre et le rendre disponible
    $stmt = $pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['retour']]);
    $id_livre = $stmt->fetchColumn();

    $pdo->prepare("UPDATE livres SET disponible = TRUE WHERE id_livre = ?")->execute([$id_livre]);
    header('Location: emprunts.php'); // Redirection après retour
    exit;
}

// === CONSTRUCTION DES FILTRES DE RECHERCHE ===

$whereConditions = [];  // Conditions WHERE pour les filtres
$params = [];          // Paramètres pour les requêtes préparées

// Filtre de recherche par emprunteur, titre ou auteur
if ($search) {
    $whereConditions[] = "(e.nom_emprunteur LIKE ? OR l.titre LIKE ? OR CONCAT(a.prenom, ' ', a.nom) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filtre par statut d'emprunt
if ($statut !== '') {
    if ($statut == 'en_cours') {
        $whereConditions[] = "e.date_retour_effective IS NULL";  // Pas encore retourné
    } elseif ($statut == 'retourne') {
        $whereConditions[] = "e.date_retour_effective IS NOT NULL";  // Déjà retourné
    } elseif ($statut == 'retard') {
        $whereConditions[] = "e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()";  // En retard
    }
}

// Construction de la clause WHERE finale
$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// === REQUÊTES DE DONNÉES ===

// Compter le nombre total d'emprunts (pour la pagination)
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id_livre
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    $whereClause
");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupérer les emprunts de la page courante avec informations complètes
$stmt = $pdo->prepare("
    SELECT e.*, l.titre, CONCAT(a.prenom, ' ', a.nom) as auteur,
           CASE
               WHEN e.date_retour_effective IS NULL THEN 'En cours'
               ELSE 'Retourné'
           END as statut_emprunt,
           CASE
               WHEN e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()
               THEN CONCAT(DATEDIFF(CURDATE(), e.date_retour_prevue), ' jours')
               ELSE NULL
           END as retard
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id_livre
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    $whereClause
    ORDER BY e.date_emprunt DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$emprunts = $stmt->fetchAll();

// === DONNÉES POUR LES FORMULAIRES ===

// Liste des livres disponibles pour nouveaux emprunts
$livresDisponiblesStmt = $pdo->query("
    SELECT l.id_livre, l.titre, CONCAT(a.prenom, ' ', a.nom) as auteur
    FROM livres l
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    WHERE l.disponible = TRUE
    ORDER BY l.titre
");
$livresDisponibles = $livresDisponiblesStmt->fetchAll();

// === GESTION DE L'ÉDITION ===

// Si un emprunt est en cours d'édition, récupérer ses données
$editEmprunt = null;
if ($_GET['edit'] ?? false) {
    $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['edit']]);
    $editEmprunt = $stmt->fetch();

    // Pour l'édition, récupérer TOUS les livres (pas seulement les disponibles)
    if ($editEmprunt) {
        $tousLivresStmt = $pdo->query("
            SELECT l.id_livre, l.titre, CONCAT(a.prenom, ' ', a.nom) as auteur
            FROM livres l
            JOIN auteurs a ON l.id_auteur = a.id_auteur
            ORDER BY l.titre
        ");
        $tousLivres = $tousLivresStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emprunts</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="emprunts">
    <!-- Navigation principale -->
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs.php">Auteurs</a>
        <a href="livres.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <!-- Conteneur principal -->
    <div class="container">
        <h1>Gestion des Emprunts</h1>

        <!-- Formulaire d'ajout/modification -->
        <div class="form-container">
            <h3><?= $editEmprunt ? 'Modifier' : 'Nouvel' ?> emprunt</h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editEmprunt ? 'edit' : 'add' ?>">
                <?php if ($editEmprunt): ?>
                    <input type="hidden" name="id" value="<?= $editEmprunt['id_emprunt'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Livre :</label>
                        <select name="id_livre" required>
                            <option value="">Sélectionner un livre</option>
                            <?php
                            $livresOptions = $editEmprunt ? $tousLivres : $livresDisponibles;
                            foreach ($livresOptions as $livre):
                            ?>
                                <option value="<?= $livre['id_livre'] ?>"
                                        <?= ($editEmprunt && $editEmprunt['id_livre'] == $livre['id_livre']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($livre['titre']) ?> - <?= htmlspecialchars($livre['auteur']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Emprunteur :</label>
                        <input type="text" name="nom_emprunteur" value="<?= $editEmprunt['nom_emprunteur'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Date d'emprunt :</label>
                        <input type="date" name="date_emprunt" value="<?= $editEmprunt['date_emprunt'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date de retour prévue :</label>
                        <input type="date" name="date_retour_prevue" value="<?= $editEmprunt['date_retour_prevue'] ?? date('Y-m-d', strtotime('+2 weeks')) ?>" required>
                    </div>
                    <?php if ($editEmprunt): ?>
                    <div class="form-group">
                        <label>Date de retour effective :</label>
                        <input type="date" name="date_retour_effective" value="<?= $editEmprunt['date_retour_effective'] ?? '' ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit"><?= $editEmprunt ? 'Modifier' : 'Créer' ?> l'emprunt</button>
                <?php if ($editEmprunt): ?>
                    <a href="emprunts.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="search">
            <form method="GET">
                <div class="grid-form">
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Emprunteur, titre ou auteur">
                    </div>
                    <div>
                        <label>Statut :</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <option value="en_cours" <?= $statut == 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="retourne" <?= $statut == 'retourne' ? 'selected' : '' ?>>Retournés</option>
                            <option value="retard" <?= $statut == 'retard' ? 'selected' : '' ?>>En retard</option>
                        </select>
                    </div>
                    <button type="submit">Filtrer</button>
                    <a href="emprunts.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Tableau des emprunts -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Livre</th>
                    <th>Auteur</th>
                    <th>Emprunteur</th>
                    <th>Date emprunt</th>
                    <th>Date retour prévue</th>
                    <th>Date retour effective</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emprunts as $emprunt): ?>
                <tr>
                    <td><?= $emprunt['id_emprunt'] ?></td>
                    <td><?= htmlspecialchars($emprunt['titre']) ?></td>
                    <td><?= htmlspecialchars($emprunt['auteur']) ?></td>
                    <td><?= htmlspecialchars($emprunt['nom_emprunteur']) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                    <td>
                        <?= $emprunt['date_retour_effective'] ? date('d/m/Y', strtotime($emprunt['date_retour_effective'])) : '-' ?>
                    </td>
                    <td>
                        <?php if ($emprunt['retard']): ?>
                            <span class="status-retard">Retard: <?= $emprunt['retard'] ?></span>
                        <?php elseif ($emprunt['statut_emprunt'] == 'En cours'): ?>
                            <span class="status-en-cours"><?= $emprunt['statut_emprunt'] ?></span>
                        <?php else: ?>
                            <span class="status-retourne"><?= $emprunt['statut_emprunt'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if (!$emprunt['date_retour_effective']): ?>
                            <a href="?retour=<?= $emprunt['id_emprunt'] ?>" class="btn-retour" onclick="return confirm('Marquer comme retourné ?')">Retour</a>
                        <?php endif; ?>
                        <a href="?edit=<?= $emprunt['id_emprunt'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $emprunt['id_emprunt'] ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
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
            if ($statut) $queryParams[] = 'statut=' . urlencode($statut);
            $queryString = $queryParams ? '&' . implode('&', $queryParams) : '';
            ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ?>" class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Informations de pagination -->
        <p>Total : <?= $total ?> emprunt(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>