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
// ÉTAPE 3 : TRAITEMENT DES ACTIONS POST (FORMULAIRES)
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

// ---------- ACTION : MODIFIER UN LIVRE EXISTANT ----------
if ($_POST['action'] ?? '' == 'edit') {
    $stmt = $pdo->prepare("UPDATE livres SET titre=?, id_auteur=?, categorie=?, isbn=?, annee_publication=?, disponible=? WHERE id_livre=?");
    $stmt->execute([
        $_POST['titre'],
        $_POST['id_auteur'],
        $_POST['categorie'],
        $_POST['isbn'],
        $_POST['annee_publication'],
        $_POST['disponible'] ?? 1,
        $_POST['id']
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
            <h3>Ajouter un livre</h3>

            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="grid-2">
                    <div class="form-group">
                        <label>Titre :</label>
                        <input type="text" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur :</label>
                        <select name="id_auteur" required>
                            <option value="">Sélectionner un auteur</option>
                            <?php foreach ($auteurs as $auteur): ?>
                                <option value="<?= $auteur['id_auteur'] ?>">
                                    <?= htmlspecialchars($auteur['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Catégorie :</label>
                        <input type="text" name="categorie">
                    </div>
                    <div class="form-group">
                        <label>ISBN :</label>
                        <input type="text" name="isbn">
                    </div>
                    <div class="form-group">
                        <label>Année :</label>
                        <input type="number" name="annee_publication">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="disponible" value="1" checked>
                        Disponible
                    </label>
                </div>

                <button type="submit">Ajouter</button>
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
                        <a href="modifier_livre.php?id=<?= $livre['id_livre'] ?>" class="btn-edit">Modifier</a>
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

    <!-- Modal de modification -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Modifier le livre</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid-2">
                    <div class="form-group">
                        <label>Titre :</label>
                        <input type="text" name="titre" id="edit_titre" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur :</label>
                        <select name="id_auteur" id="edit_id_auteur" required>
                            <option value="">Sélectionner un auteur</option>
                            <?php foreach ($auteurs as $auteur): ?>
                                <option value="<?= $auteur['id_auteur'] ?>">
                                    <?= htmlspecialchars($auteur['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Catégorie :</label>
                        <input type="text" name="categorie" id="edit_categorie">
                    </div>
                    <div class="form-group">
                        <label>ISBN :</label>
                        <input type="text" name="isbn" id="edit_isbn">
                    </div>
                    <div class="form-group">
                        <label>Année :</label>
                        <input type="number" name="annee_publication" id="edit_annee_publication">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="disponible" value="1" id="edit_disponible">
                        Disponible
                    </label>
                </div>

                <button type="submit">Modifier</button>
                <button type="button" onclick="closeEditModal()">Annuler</button>
            </form>
        </div>
    </div>

    <style>
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 5px;
        position: relative;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        right: 15px;
        top: 10px;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
    }

    .btn-edit {
        background: none;
        border: none;
        color: #007bff;
        text-decoration: underline;
        cursor: pointer;
        font-size: inherit;
    }
    </style>

    <script>
    function openEditModal(id, titre, id_auteur, categorie, isbn, annee, disponible) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_titre').value = titre;
        document.getElementById('edit_id_auteur').value = id_auteur;
        document.getElementById('edit_categorie').value = categorie || '';
        document.getElementById('edit_isbn').value = isbn || '';
        document.getElementById('edit_annee_publication').value = annee || '';
        document.getElementById('edit_disponible').checked = disponible == 1;

        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Fermer le modal si on clique à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }
    </script>
</body>
</html>