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

// ========================================================================
// ÉTAPE 1 : INCLUSION ET CONNEXION À LA BASE DE DONNÉES
// ========================================================================

// Inclusion du fichier de configuration contenant la connexion PDO
require_once 'config.php';

// ========================================================================
// ÉTAPE 2 : RÉCUPÉRATION ET VALIDATION DES PARAMÈTRES URL (GET)
// ========================================================================

// Récupération du terme de recherche (titre ou auteur)
// ?? '' : si le paramètre n'existe pas, utilise une chaîne vide
$search = $_GET['search'] ?? '';

// Récupération du filtre par catégorie (Roman, Science-fiction, etc.)
$categorie = $_GET['categorie'] ?? '';

// Récupération du filtre par disponibilité (1 = disponible, 0 = emprunté)
// Attention : on garde une chaîne vide par défaut, pas 0 ou false
// Car 0 est une valeur valide (livre emprunté)
$disponible = $_GET['disponible'] ?? '';

// Pagination : numéro de page actuelle (minimum 1)
$page = max(1, $_GET['page'] ?? 1);

// Nombre de livres à afficher par page
$limit = 10;

// Calcul de l'offset pour la clause SQL LIMIT/OFFSET
// Page 1 → offset 0, Page 2 → offset 10, Page 3 → offset 20
$offset = ($page - 1) * $limit;

// ========================================================================
// ÉTAPE 3 : GESTION DU MODE ÉDITION (AVANT LES ACTIONS POST)
// ========================================================================

// Variable pour stocker les données du livre en cours d'édition
$editLivre = null;

// Si un paramètre 'edit' existe dans l'URL (ex: livres.php?edit=5)
if ($_GET['edit'] ?? false) {
    // Récupère toutes les données du livre à modifier
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id_livre = ?");
    $stmt->execute([$_GET['edit']]);
    // fetch() : récupère une seule ligne (le livre à éditer)
    $editLivre = $stmt->fetch();
}

// ========================================================================
// ÉTAPE 4 : TRAITEMENT DES ACTIONS POST (FORMULAIRES)
// ========================================================================

// ---------- ACTION : AJOUTER UN NOUVEAU LIVRE ----------
if ($_POST['action'] ?? '' == 'add') {

    // Préparation de la requête INSERT avec 6 placeholders
    $stmt = $pdo->prepare("INSERT INTO livres (titre, id_auteur, categorie, isbn, annee_publication, disponible) VALUES (?, ?, ?, ?, ?, ?)");

    // Exécution avec les données du formulaire
    // Note : $_POST['disponible'] ?? 1 → si la checkbox n'est pas cochée,
    // le champ n'existe pas dans $_POST, donc on met 1 (disponible) par défaut
    $stmt->execute([
        $_POST['titre'],
        $_POST['id_auteur'],        // ID de l'auteur sélectionné dans le select
        $_POST['categorie'],
        $_POST['isbn'],
        $_POST['annee_publication'],
        $_POST['disponible'] ?? 1    // 1 si checkbox cochée ou absente, sinon sa valeur
    ]);

    // Redirection pour éviter la re-soumission (pattern PRG)
    header('Location: livres.php');
    exit;
}

// ---------- ACTION : REMPLACER UN LIVRE (SUPPRIMER PUIS AJOUTER) ----------
if ($_POST['action'] ?? '' == 'edit') {

    // Vérifier si l'ID existe
    if (isset($_POST['id']) && $_POST['id']) {
        // Supprimer l'ancien livre
        $deleteStmt = $pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
        $deleteStmt->execute([$_POST['id']]);
    }

    // Ajouter le nouveau livre avec les modifications
    $insertStmt = $pdo->prepare("INSERT INTO livres (titre, id_auteur, categorie, isbn, annee_publication, disponible) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->execute([
        $_POST['titre'],
        $_POST['id_auteur'],
        $_POST['categorie'],
        $_POST['isbn'],
        $_POST['annee_publication'],
        $_POST['disponible'] ?? 1
    ]);

    header('Location: livres.php');
    exit;
}

// ---------- ACTION : SUPPRIMER UN LIVRE ----------
if ($_GET['delete'] ?? false) {

    // Suppression basée sur l'ID passé en GET
    $stmt = $pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
    $stmt->execute([$_GET['delete']]);

    header('Location: livres.php');
    exit;
}

// ========================================================================
// ÉTAPE 4 : CONSTRUCTION DYNAMIQUE DES FILTRES DE RECHERCHE
// ========================================================================

// Tableaux pour construire une requête SQL dynamique avec plusieurs filtres
$whereConditions = [];  // Contiendra chaque condition WHERE
$params = [];          // Contiendra les valeurs pour les placeholders

// ---------- FILTRE 1 : RECHERCHE PAR TITRE OU AUTEUR ----------
if ($search) {
    // Parenthèses importantes pour grouper les OR ensemble
    // Recherche dans le titre du livre OU le nom/prénom de l'auteur
    $whereConditions[] = "(l.titre LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ?)";

    // Ajout de 3 paramètres (un pour chaque LIKE)
    $params[] = "%$search%";  // Pour l.titre
    $params[] = "%$search%";  // Pour a.nom
    $params[] = "%$search%";  // Pour a.prenom
}

// ---------- FILTRE 2 : CATÉGORIE ----------
if ($categorie) {
    // Filtre exact sur la catégorie (pas de LIKE ici)
    $whereConditions[] = "l.categorie = ?";
    $params[] = $categorie;
}

// ---------- FILTRE 3 : DISPONIBILITÉ ----------
// Attention : on teste !== '' car 0 est une valeur valide
// Si on testait juste if ($disponible), 0 serait considéré comme false
if ($disponible !== '') {
    $whereConditions[] = "l.disponible = ?";
    $params[] = $disponible;
}

// ---------- CONSTRUCTION DE LA CLAUSE WHERE FINALE ----------
// implode(" AND ", ...) : joint toutes les conditions avec AND
// Ex: "WHERE (l.titre LIKE ?) AND l.categorie = ? AND l.disponible = ?"
// Si aucun filtre : $whereClause reste vide
$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// ========================================================================
// ÉTAPE 5 : REQUÊTES DE RÉCUPÉRATION DES DONNÉES
// ========================================================================

// ---------- COMPTER LE NOMBRE TOTAL DE LIVRES (AVEC FILTRES) ----------
// JOIN : nécessaire car on filtre potentiellement sur le nom de l'auteur
// La jointure permet de lier chaque livre à son auteur
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM livres l JOIN auteurs a ON l.id_auteur = a.id_auteur $whereClause");

// Execute avec tous les paramètres des filtres
$countStmt->execute($params);

// Récupère le nombre total pour calculer la pagination
$total = $countStmt->fetchColumn();

// Calcul du nombre de pages nécessaires
$totalPages = ceil($total / $limit);

// ---------- RÉCUPÉRER LES LIVRES DE LA PAGE COURANTE ----------
// SELECT l.* : toutes les colonnes de la table livres
// CONCAT() : fonction SQL qui concatène le prénom et nom de l'auteur
// AS auteur : crée un alias pour utiliser facilement dans l'affichage
$stmt = $pdo->prepare("
    SELECT l.*, CONCAT(a.prenom, ' ', a.nom) as auteur
    FROM livres l
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    $whereClause
    ORDER BY l.titre
    LIMIT $limit OFFSET $offset
");

// Execute avec les mêmes paramètres de filtres
$stmt->execute($params);

// Récupère tous les livres sous forme de tableau associatif
$livres = $stmt->fetchAll();

// ========================================================================
// ÉTAPE 6 : RÉCUPÉRATION DES DONNÉES POUR LES FORMULAIRES
// ========================================================================

// ---------- LISTE DES AUTEURS POUR LE SELECT ----------
// query() : utilisé ici car pas de paramètres (requête statique)
// CONCAT() dans le SELECT pour avoir "Prénom Nom" directement
$auteursStmt = $pdo->query("
    SELECT id_auteur, CONCAT(prenom, ' ', nom) as nom_complet
    FROM auteurs
    ORDER BY nom, prenom
");
$auteurs = $auteursStmt->fetchAll();

// ---------- LISTE DES CATÉGORIES EXISTANTES ----------
// DISTINCT : récupère chaque catégorie une seule fois
// WHERE categorie IS NOT NULL : ignore les livres sans catégorie
// PDO::FETCH_COLUMN : récupère uniquement la première colonne (categorie)
// Résultat : tableau simple ['Roman', 'Science-fiction', ...]
$categoriesStmt = $pdo->query("
    SELECT DISTINCT categorie
    FROM livres
    WHERE categorie IS NOT NULL
    ORDER BY categorie
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

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

        <!-- ========================================================================
             FORMULAIRE D'AJOUT/MODIFICATION DE LIVRE
             ======================================================================== -->
        <div class="form-container">
            <!-- Titre dynamique selon le mode (ajout ou édition) -->
            <h3><?= $editLivre ? 'Modifier' : 'Ajouter' ?> un livre</h3>

            <form method="POST">
                <!-- Champ caché pour identifier l'action (add ou edit) -->
                <input type="hidden" name="action" value="<?= $editLivre ? 'edit' : 'add' ?>">

                <!-- Si mode édition, ajoute l'ID du livre -->
                <?php if ($editLivre): ?>
                    <input type="hidden" name="id" value="<?= $editLivre['id_livre'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Titre :</label>
                        <!-- Pré-remplit avec les données existantes si édition -->
                        <input type="text" name="titre" value="<?= $editLivre['titre'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur :</label>
                        <!-- SELECT : liste déroulante des auteurs -->
                        <select name="id_auteur" required>
                            <option value="">Sélectionner un auteur</option>

                            <!-- Boucle sur tous les auteurs de la base -->
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
                        <!-- Input texte libre pour la catégorie -->
                        <input type="text" name="categorie" value="<?= $editLivre['categorie'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>ISBN :</label>
                        <input type="text" name="isbn" value="<?= $editLivre['isbn'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Année :</label>
                        <!-- type="number" : champ numérique avec flèches -->
                        <input type="number" name="annee_publication" value="<?= $editLivre['annee_publication'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <!-- CHECKBOX : case à cocher pour la disponibilité -->
                        <!-- value="1" : si cochée, envoie 1, sinon le champ n'existe pas dans $_POST -->
                        <!-- checked : coche par défaut si nouveau livre OU si livre disponible -->
                        <input type="checkbox" name="disponible" value="1"
                               <?= (!$editLivre || $editLivre['disponible']) ? 'checked' : '' ?>>
                        Disponible
                    </label>
                </div>

                <button type="submit"><?= $editLivre ? 'Modifier' : 'Ajouter' ?></button>

                <!-- Lien d'annulation visible uniquement en mode édition -->
                <?php if ($editLivre): ?>
                    <a href="livres.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========================================================================
             BARRE DE RECHERCHE ET FILTRES AVANCÉS
             ======================================================================== -->
        <div class="search">
            <form method="GET">
                <div class="grid-2-1-1">
                    <!-- CHAMP 1 : Recherche textuelle -->
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search"
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Titre ou auteur">
                    </div>

                    <!-- CHAMP 2 : Filtre par catégorie -->
                    <div>
                        <label>Catégorie :</label>
                        <select name="categorie">
                            <option value="">Toutes</option>

                            <!-- Liste dynamique des catégories existantes -->
                            <?php foreach ($categories as $cat): ?>
                                <!-- selected : maintient la sélection après filtrage -->
                                <option value="<?= $cat ?>" <?= $categorie == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- CHAMP 3 : Filtre par disponibilité -->
                    <div>
                        <label>Statut :</label>
                        <select name="disponible">
                            <option value="">Tous</option>
                            <!-- === : comparaison stricte car '0' !== 0 en PHP -->
                            <option value="1" <?= $disponible === '1' ? 'selected' : '' ?>>Disponible</option>
                            <option value="0" <?= $disponible === '0' ? 'selected' : '' ?>>Emprunté</option>
                        </select>
                    </div>

                    <button type="submit">Filtrer</button>
                    <a href="livres.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- ========================================================================
             TABLEAU D'AFFICHAGE DES LIVRES
             ======================================================================== -->
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
                <!-- Boucle sur tous les livres récupérés -->
                <?php foreach ($livres as $livre): ?>
                <tr>
                    <td><?= $livre['id_livre'] ?></td>

                    <!-- htmlspecialchars() sur toutes les données utilisateur -->
                    <td><?= htmlspecialchars($livre['titre']) ?></td>

                    <!-- 'auteur' vient du CONCAT() dans la requête SQL -->
                    <td><?= htmlspecialchars($livre['auteur']) ?></td>

                    <td><?= htmlspecialchars($livre['categorie']) ?></td>

                    <td><?= $livre['annee_publication'] ?></td>

                    <td>
                        <!-- Classe CSS dynamique selon la disponibilité -->
                        <span class="<?= $livre['disponible'] ? 'status-disponible' : 'status-indisponible' ?>">
                            <?= $livre['disponible'] ? 'Disponible' : 'Emprunté' ?>
                        </span>
                    </td>

                    <td class="actions">
                        <a href="?edit=<?= $livre['id_livre'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $livre['id_livre'] ?>"
                           class="btn-delete"
                           onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ========================================================================
             PAGINATION AVANCÉE (CONSERVE LES FILTRES)
             ======================================================================== -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            // Construction des paramètres GET à conserver lors du changement de page
            $queryParams = [];

            // Ajoute chaque filtre actif aux paramètres
            if ($search) $queryParams[] = 'search=' . urlencode($search);
            if ($categorie) $queryParams[] = 'categorie=' . urlencode($categorie);
            if ($disponible !== '') $queryParams[] = 'disponible=' . urlencode($disponible);

            // Construit la chaîne de requête complète
            // Ex: "&search=hugo&categorie=Roman&disponible=1"
            $queryString = $queryParams ? '&' . implode('&', $queryParams) : '';
            ?>

            <!-- Génération des liens de pagination -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <!-- Chaque lien conserve les filtres grâce à $queryString -->
                <a href="?page=<?= $i ?><?= $queryString ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================================================
             INFORMATIONS DE PAGINATION
             ======================================================================== -->
        <p>Total : <?= $total ?> livre(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>