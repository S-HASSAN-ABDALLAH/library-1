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

// ========================================================================
// ÉTAPE 1 : INCLUSION ET CONNEXION À LA BASE DE DONNÉES
// ========================================================================

// require_once : inclut un fichier PHP une seule fois (évite les doublons)
// Si le fichier n'existe pas, arrête l'exécution avec une erreur fatale
// config.php contient la connexion PDO à la base de données MySQL
require_once 'config.php';

// ========================================================================
// ÉTAPE 2 : RÉCUPÉRATION ET VALIDATION DES PARAMÈTRES URL (GET)
// ========================================================================

// L'opérateur ?? (null coalescing) introduit en PHP 7
// Si $_GET['search'] existe, on prend sa valeur, sinon on met une chaîne vide ''
// Cela évite les erreurs "undefined index" si le paramètre n'est pas dans l'URL
$search = $_GET['search'] ?? '';

// Récupération du numéro de page pour la pagination
// max(1, ...) garantit que la page est au minimum 1
// Si $_GET['page'] n'existe pas, on utilise 1 par défaut
// Évite les valeurs négatives ou 0 qui casseraient la pagination
$page = max(1, $_GET['page'] ?? 1);

// Nombre d'auteurs à afficher par page (constante de pagination)
$limit = 10;

// Calcul de l'offset (décalage) pour la requête SQL
// Formule : (numéro_page - 1) × limite
// Page 1 : (1-1)×10 = 0 (affiche les lignes 0-9)
// Page 2 : (2-1)×10 = 10 (affiche les lignes 10-19)
// Page 3 : (3-1)×10 = 20 (affiche les lignes 20-29)
$offset = ($page - 1) * $limit;

// ========================================================================
// ÉTAPE 3 : TRAITEMENT DES ACTIONS POST (FORMULAIRES)
// ========================================================================

// ---------- ACTION : AJOUTER UN NOUVEL AUTEUR ----------
// Vérifie si le formulaire a été soumis avec action='add'
// $_POST['action'] ?? '' évite l'erreur si 'action' n'existe pas
if ($_POST['action'] ?? '' == 'add') {

    // prepare() : prépare une requête SQL avec des placeholders (?)
    // Protège contre les injections SQL en séparant le SQL des données
    $stmt = $pdo->prepare("INSERT INTO auteurs (nom, prenom, date_naissance, nationalite) VALUES (?, ?, ?, ?)");

    // execute() : exécute la requête en remplaçant les ? par les valeurs du tableau
    // Les données sont automatiquement échappées pour éviter les injections SQL
    $stmt->execute([
        $_POST['nom'],           // Remplace le 1er ?
        $_POST['prenom'],        // Remplace le 2e ?
        $_POST['date_naissance'], // Remplace le 3e ?
        $_POST['nationalite']    // Remplace le 4e ?
    ]);

    // header() : envoie un en-tête HTTP de redirection
    // Redirige vers la même page pour éviter la re-soumission du formulaire
    // si l'utilisateur actualise la page (pattern PRG : Post-Redirect-Get)
    header('Location: auteurs.php');

    // exit : arrête l'exécution du script après la redirection
    // Important car header() n'arrête pas le script automatiquement
    exit;
}

// ---------- ACTION : MODIFIER UN AUTEUR EXISTANT ----------
if ($_POST['action'] ?? '' == 'edit') {

    // UPDATE : modifie les enregistrements existants
    // SET : définit les nouvelles valeurs des colonnes
    // WHERE : condition pour identifier quel enregistrement modifier
    $stmt = $pdo->prepare("UPDATE auteurs SET nom=?, prenom=?, date_naissance=?, nationalite=? WHERE id_auteur=?");

    // Les 4 premiers ? sont les nouvelles valeurs
    // Le 5e ? est l'ID de l'auteur à modifier
    $stmt->execute([
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['date_naissance'],
        $_POST['nationalite'],
        $_POST['id']  // ID de l'auteur à modifier
    ]);

    // Redirection après modification pour éviter la re-soumission
    header('Location: auteurs.php');
    exit;
}

// ---------- ACTION : SUPPRIMER UN AUTEUR ----------
// Vérifie si un paramètre 'delete' existe dans l'URL (GET)
// Ex: auteurs.php?delete=5 supprimera l'auteur avec id_auteur=5
if ($_GET['delete'] ?? false) {

    // DELETE FROM : supprime des enregistrements de la table
    // WHERE : condition obligatoire pour éviter de supprimer toute la table
    $stmt = $pdo->prepare("DELETE FROM auteurs WHERE id_auteur = ?");

    // Execute avec l'ID de l'auteur à supprimer
    $stmt->execute([$_GET['delete']]);

    // Redirection après suppression
    header('Location: auteurs.php');
    exit;
}

// ========================================================================
// ÉTAPE 4 : CONSTRUCTION DE LA REQUÊTE DE RECHERCHE
// ========================================================================

// Variables pour construire dynamiquement la requête SQL
$whereClause = "";  // Contiendra la clause WHERE si une recherche est active
$params = [];       // Contiendra les paramètres pour les requêtes préparées

// Si une recherche est effectuée (le champ n'est pas vide)
if ($search) {
    // Construction de la clause WHERE avec LIKE pour recherche partielle
    // LIKE permet de rechercher des motifs dans les colonnes
    // OR : cherche dans n'importe laquelle des 3 colonnes
    $whereClause = "WHERE nom LIKE ? OR prenom LIKE ? OR nationalite LIKE ?";

    // % est le wildcard SQL : correspond à n'importe quelle suite de caractères
    // %hugo% trouvera : "Hugo", "Victor Hugo", "Hugoline", etc.
    $params = [
        "%$search%",  // Pour la colonne nom
        "%$search%",  // Pour la colonne prenom
        "%$search%"   // Pour la colonne nationalite
    ];
}

// ========================================================================
// ÉTAPE 5 : REQUÊTES DE RÉCUPÉRATION DES DONNÉES
// ========================================================================

// ---------- COMPTER LE NOMBRE TOTAL D'AUTEURS ----------
// COUNT(*) : compte toutes les lignes qui correspondent aux critères
// Utilise $whereClause qui est vide si pas de recherche, ou contient WHERE si recherche
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM auteurs $whereClause");

// Execute avec les paramètres de recherche (tableau vide si pas de recherche)
$countStmt->execute($params);

// fetchColumn() : récupère la valeur de la première colonne de la première ligne
// Parfait pour récupérer un COUNT qui retourne une seule valeur
$total = $countStmt->fetchColumn();

// ceil() : arrondit au nombre entier supérieur
// Ex: 25 auteurs / 10 par page = 2.5, ceil(2.5) = 3 pages
$totalPages = ceil($total / $limit);

// ---------- RÉCUPÉRER LES AUTEURS DE LA PAGE COURANTE ----------
// SELECT * : récupère toutes les colonnes
// ORDER BY : trie par nom puis prénom (ordre alphabétique)
// LIMIT : limite le nombre de résultats retournés
// OFFSET : ignore les X premiers résultats (pour la pagination)
$stmt = $pdo->prepare("SELECT * FROM auteurs $whereClause ORDER BY nom, prenom LIMIT $limit OFFSET $offset");

// Execute la requête avec les paramètres de recherche
$stmt->execute($params);

// fetchAll() : récupère tous les résultats sous forme de tableau associatif
// Chaque ligne devient un tableau avec les noms de colonnes comme clés
$auteurs = $stmt->fetchAll();

// ========================================================================
// ÉTAPE 6 : GESTION DU MODE ÉDITION
// ========================================================================

// Variable pour stocker les données de l'auteur en cours d'édition
$editAuteur = null;

// Si un paramètre 'edit' existe dans l'URL (ex: auteurs.php?edit=3)
if ($_GET['edit'] ?? false) {

    // Récupère les données de l'auteur à modifier
    $stmt = $pdo->prepare("SELECT * FROM auteurs WHERE id_auteur = ?");
    $stmt->execute([$_GET['edit']]);

    // fetch() : récupère une seule ligne (contrairement à fetchAll())
    // Stocke les données de l'auteur pour pré-remplir le formulaire
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

        <!-- ========================================================================
             FORMULAIRE D'AJOUT/MODIFICATION D'AUTEUR
             ======================================================================== -->
        <div class="form-container">
            <!-- Affiche "Modifier" si on édite, sinon "Ajouter" -->
            <!-- L'opérateur ternaire : condition ? si_vrai : si_faux -->
            <h3><?= $editAuteur ? 'Modifier' : 'Ajouter' ?> un auteur</h3>

            <!-- method="POST" : envoie les données sans les afficher dans l'URL -->
            <form method="POST">
                <!-- Champ caché pour indiquer l'action (add ou edit) -->
                <!-- value utilise l'opérateur ternaire pour choisir la bonne action -->
                <input type="hidden" name="action" value="<?= $editAuteur ? 'edit' : 'add' ?>">

                <!-- Si on est en mode édition, ajoute un champ caché avec l'ID -->
                <?php if ($editAuteur): ?>
                    <input type="hidden" name="id" value="<?= $editAuteur['id_auteur'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom :</label>
                        <!-- value : pré-remplit le champ avec la valeur existante si édition -->
                        <!-- ?? '' : si pas de valeur (mode ajout), met une chaîne vide -->
                        <!-- required : validation HTML5, champ obligatoire -->
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
                        <!-- type="date" : affiche un sélecteur de date HTML5 -->
                        <input type="date" name="date_naissance" value="<?= $editAuteur['date_naissance'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nationalité :</label>
                        <input type="text" name="nationalite" value="<?= $editAuteur['nationalite'] ?? '' ?>">
                    </div>
                </div>

                <!-- Bouton qui change de texte selon le mode (ajout/édition) -->
                <button type="submit"><?= $editAuteur ? 'Modifier' : 'Ajouter' ?></button>

                <!-- Lien d'annulation visible uniquement en mode édition -->
                <?php if ($editAuteur): ?>
                    <a href="auteurs.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========================================================================
             BARRE DE RECHERCHE
             ======================================================================== -->
        <div class="search">
            <!-- method="GET" : envoie les données dans l'URL pour pouvoir partager le lien -->
            <form method="GET">
                <div class="grid-form">
                    <div style="flex: 1;">
                        <label>Rechercher :</label>
                        <!-- htmlspecialchars() : convertit les caractères spéciaux en entités HTML -->
                        <!-- Évite les attaques XSS en empêchant l'injection de code HTML/JS -->
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom ou nationalité">
                    </div>
                    <button type="submit">Rechercher</button>
                    <!-- Lien pour réinitialiser la recherche (retour à la page normale) -->
                    <a href="auteurs.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- ========================================================================
             TABLEAU D'AFFICHAGE DES AUTEURS
             ======================================================================== -->
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
                <!-- foreach : boucle sur chaque auteur du tableau $auteurs -->
                <!-- $auteur contient les données d'un auteur à chaque itération -->
                <?php foreach ($auteurs as $auteur): ?>
                <tr>
                    <!-- Affichage simple de l'ID (pas besoin d'échapper, c'est un nombre) -->
                    <td><?= $auteur['id_auteur'] ?></td>

                    <!-- htmlspecialchars() sur toutes les données texte -->
                    <!-- Protège contre XSS en convertissant < > & " ' en entités HTML -->
                    <td><?= htmlspecialchars($auteur['nom']) ?></td>
                    <td><?= htmlspecialchars($auteur['prenom']) ?></td>

                    <!-- Date : pas de htmlspecialchars car format date non dangereux -->
                    <td><?= $auteur['date_naissance'] ?></td>

                    <td><?= htmlspecialchars($auteur['nationalite']) ?></td>

                    <td class="actions">
                        <!-- Lien pour éditer : redirige vers page de modification dédiée -->
                        <a href="modifier_auteur.php?id=<?= $auteur['id_auteur'] ?>" class="btn-edit">Modifier</a>

                        <!-- Lien pour supprimer : ajoute ?delete=ID à l'URL -->
                        <!-- onclick : demande confirmation JavaScript avant suppression -->
                        <!-- return false annule le clic si l'utilisateur clique sur Annuler -->
                        <a href="?delete=<?= $auteur['id_auteur'] ?>" class="btn-delete"
                           onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ========================================================================
             PAGINATION
             ======================================================================== -->
        <!-- N'affiche la pagination que s'il y a plus d'une page -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <!-- Boucle for pour créer les liens de pagination -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <!-- Construit l'URL avec le numéro de page -->
                <!-- urlencode() : encode les caractères spéciaux pour l'URL -->
                <!-- Si recherche active, ajoute &search=terme à l'URL -->
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                 
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================================================
             INFORMATIONS DE PAGINATION
             ======================================================================== -->
        <!-- Affiche le total et la position actuelle dans la pagination -->
        <p>Total : <?= $total ?> auteur(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>