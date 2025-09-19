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

// ========================================================================
// ÉTAPE 1 : INCLUSION ET CONNEXION À LA BASE DE DONNÉES
// ========================================================================

// Inclusion du fichier de configuration contenant la connexion PDO
require_once 'config.php';

// ========================================================================
// ÉTAPE 2 : RÉCUPÉRATION ET VALIDATION DES PARAMÈTRES URL (GET)
// ========================================================================

// Récupération du terme de recherche (emprunteur, titre ou auteur)
$search = $_GET['search'] ?? '';

// Récupération du filtre par statut d'emprunt
// Valeurs possibles : 'en_cours', 'retourne', 'retard'
$statut = $_GET['statut'] ?? '';

// Pagination : numéro de page actuelle (minimum 1)
$page = max(1, $_GET['page'] ?? 1);

// Nombre d'emprunts à afficher par page
$limit = 10;

// Calcul de l'offset pour la clause SQL LIMIT/OFFSET
$offset = ($page - 1) * $limit;

// ========================================================================
// ÉTAPE 3 : TRAITEMENT DES ACTIONS POST/GET (CRUD COMPLET)
// ========================================================================

// ---------- ACTION : CRÉER UN NOUVEL EMPRUNT ----------
if ($_POST['action'] ?? '' == 'add') {

    // ÉTAPE 1 : Insertion de l'emprunt dans la table emprunts
    $stmt = $pdo->prepare("INSERT INTO emprunts (id_livre, nom_emprunteur, date_emprunt, date_retour_prevue) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['id_livre'],
        $_POST['nom_emprunteur'],
        $_POST['date_emprunt'],
        $_POST['date_retour_prevue']
    ]);

    // ÉTAPE 2 : Mise à jour automatique de la disponibilité du livre
    // FALSE (ou 0) : marque le livre comme emprunté/indisponible
    // Importante cohérence des données : un livre emprunté ne peut pas être disponible
    $pdo->prepare("UPDATE livres SET disponible = FALSE WHERE id_livre = ?")
        ->execute([$_POST['id_livre']]);

    // Redirection pour éviter la re-soumission (pattern PRG)
    header('Location: emprunts.php');
    exit;
}

// ---------- ACTION : MODIFIER UN EMPRUNT EXISTANT ----------
if ($_POST['action'] ?? '' == 'edit') {

    // UPDATE de tous les champs de l'emprunt
    $stmt = $pdo->prepare("UPDATE emprunts SET id_livre=?, nom_emprunteur=?, date_emprunt=?, date_retour_prevue=?, date_retour_effective=? WHERE id_emprunt=?");

    // Exécution avec gestion de la date de retour effective
    // ?: null → opérateur ternaire court : si vide, met NULL dans la base
    $stmt->execute([
        $_POST['id_livre'],
        $_POST['nom_emprunteur'],
        $_POST['date_emprunt'],
        $_POST['date_retour_prevue'],
        $_POST['date_retour_effective'] ?: null,  // NULL si vide (livre non retourné)
        $_POST['id']  // ID de l'emprunt à modifier
    ]);

    header('Location: emprunts.php');
    exit;
}

// ---------- ACTION : SUPPRIMER UN EMPRUNT ----------
if ($_GET['delete'] ?? false) {

    // IMPORTANT : Avant de supprimer, récupérer l'ID du livre
    // pour pouvoir le rendre disponible après suppression
    $stmt = $pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['delete']]);

    // fetchColumn() : récupère directement la valeur de la première colonne
    $id_livre = $stmt->fetchColumn();

    // ÉTAPE 1 : Suppression de l'emprunt
    $pdo->prepare("DELETE FROM emprunts WHERE id_emprunt = ?")
        ->execute([$_GET['delete']]);

    // ÉTAPE 2 : Rendre le livre disponible (TRUE ou 1)
    // Cohérence : si on supprime l'emprunt, le livre redevient disponible
    $pdo->prepare("UPDATE livres SET disponible = TRUE WHERE id_livre = ?")
        ->execute([$id_livre]);

    header('Location: emprunts.php');
    exit;
}

// ---------- ACTION : MARQUER UN LIVRE COMME RETOURNÉ ----------
// Action spécifique aux emprunts : gestion du retour de livre
if ($_GET['retour'] ?? false) {

    // ÉTAPE 1 : Mettre à jour la date de retour effective
    // CURDATE() : fonction MySQL qui retourne la date du jour (YYYY-MM-DD)
    $stmt = $pdo->prepare("UPDATE emprunts SET date_retour_effective = CURDATE() WHERE id_emprunt = ?");
    $stmt->execute([$_GET['retour']]);

    // ÉTAPE 2 : Récupérer l'ID du livre pour le rendre disponible
    $stmt = $pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['retour']]);
    $id_livre = $stmt->fetchColumn();

    // ÉTAPE 3 : Marquer le livre comme disponible
    $pdo->prepare("UPDATE livres SET disponible = TRUE WHERE id_livre = ?")
        ->execute([$id_livre]);

    header('Location: emprunts.php');
    exit;
}

// ========================================================================
// ÉTAPE 4 : CONSTRUCTION DYNAMIQUE DES FILTRES DE RECHERCHE
// ========================================================================

// Tableaux pour construire une requête SQL dynamique
$whereConditions = [];  // Contiendra chaque condition WHERE
$params = [];          // Contiendra les valeurs pour les placeholders

// ---------- FILTRE 1 : RECHERCHE TEXTUELLE ----------
if ($search) {
    // Recherche dans : nom emprunteur, titre livre, nom complet auteur
    // CONCAT() : concatène prénom et nom de l'auteur pour la recherche
    $whereConditions[] = "(e.nom_emprunteur LIKE ? OR l.titre LIKE ? OR CONCAT(a.prenom, ' ', a.nom) LIKE ?)";

    // Ajout de 3 paramètres identiques pour les 3 LIKE
    $params[] = "%$search%";  // Pour e.nom_emprunteur
    $params[] = "%$search%";  // Pour l.titre
    $params[] = "%$search%";  // Pour CONCAT(a.prenom, ' ', a.nom)
}

// ---------- FILTRE 2 : STATUT DE L'EMPRUNT ----------
// Logique complexe basée sur les dates
if ($statut !== '') {
    if ($statut == 'en_cours') {
        // En cours = pas de date de retour effective (livre non retourné)
        // IS NULL : teste si une colonne est NULL
        $whereConditions[] = "e.date_retour_effective IS NULL";
        // Pas de paramètre supplémentaire (condition statique)

    } elseif ($statut == 'retourne') {
        // Retourné = date de retour effective renseignée
        // IS NOT NULL : teste si une colonne n'est pas NULL
        $whereConditions[] = "e.date_retour_effective IS NOT NULL";

    } elseif ($statut == 'retard') {
        // En retard = pas retourné ET date prévue dépassée
        // CURDATE() : date actuelle MySQL
        // Combinaison de deux conditions avec AND
        $whereConditions[] = "e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()";
    }
}

// Construction de la clause WHERE finale
$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// ========================================================================
// ÉTAPE 5 : REQUÊTES DE RÉCUPÉRATION DES DONNÉES
// ========================================================================

// ---------- COMPTER LE NOMBRE TOTAL D'EMPRUNTS (AVEC FILTRES) ----------
// Requête avec jointures multiples pour accéder aux infos livre et auteur
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id_livre      -- Jointure avec les livres
    JOIN auteurs a ON l.id_auteur = a.id_auteur   -- Jointure avec les auteurs
    $whereClause
");

$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// ---------- RÉCUPÉRER LES EMPRUNTS AVEC CALCULS DYNAMIQUES ----------
// Requête complexe avec CASE WHEN pour calculer le statut et le retard
$stmt = $pdo->prepare("
    SELECT e.*,                                    -- Toutes les colonnes de emprunts
           l.titre,                                -- Titre du livre
           CONCAT(a.prenom, ' ', a.nom) as auteur, -- Nom complet de l'auteur

           -- CASE WHEN : équivalent SQL du if/else
           -- Calcul du statut d'affichage
           CASE
               WHEN e.date_retour_effective IS NULL THEN 'En cours'
               ELSE 'Retourné'
           END as statut_emprunt,

           -- Calcul du nombre de jours de retard (si applicable)
           CASE
               -- Si non retourné ET date prévue dépassée
               WHEN e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()
               -- DATEDIFF : calcule la différence en jours entre deux dates
               THEN CONCAT(DATEDIFF(CURDATE(), e.date_retour_prevue), ' jours')
               ELSE NULL  -- Pas de retard
           END as retard

    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id_livre
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    $whereClause
    ORDER BY e.date_emprunt DESC  -- Les emprunts les plus récents en premier
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$emprunts = $stmt->fetchAll();

// ========================================================================
// ÉTAPE 6 : RÉCUPÉRATION DES DONNÉES POUR LES FORMULAIRES
// ========================================================================

// ---------- LISTE DES LIVRES DISPONIBLES POUR NOUVEAUX EMPRUNTS ----------
// Important : ne montrer QUE les livres disponibles (disponible = TRUE)
$livresDisponiblesStmt = $pdo->query("
    SELECT l.id_livre,
           l.titre,
           CONCAT(a.prenom, ' ', a.nom) as auteur
    FROM livres l
    JOIN auteurs a ON l.id_auteur = a.id_auteur
    WHERE l.disponible = TRUE      -- Filtre crucial : seulement les disponibles
    ORDER BY l.titre
");
$livresDisponibles = $livresDisponiblesStmt->fetchAll();

// ========================================================================
// ÉTAPE 7 : GESTION DU MODE ÉDITION
// ========================================================================

// Variable pour stocker les données de l'emprunt en cours d'édition
$editEmprunt = null;

if ($_GET['edit'] ?? false) {

    // Récupère les données de l'emprunt à modifier
    $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id_emprunt = ?");
    $stmt->execute([$_GET['edit']]);
    $editEmprunt = $stmt->fetch();

    // SPÉCIFICITÉ IMPORTANTE pour l'édition :
    // En mode édition, on veut pouvoir sélectionner N'IMPORTE QUEL livre
    // (pas seulement les disponibles) car on modifie un emprunt existant
    if ($editEmprunt) {
        $tousLivresStmt = $pdo->query("
            SELECT l.id_livre,
                   l.titre,
                   CONCAT(a.prenom, ' ', a.nom) as auteur
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
<!-- Classe CSS spécifique pour la page emprunts -->
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

        <!-- ========================================================================
             FORMULAIRE D'AJOUT/MODIFICATION D'EMPRUNT
             ======================================================================== -->
        <div class="form-container">
            <h3><?= $editEmprunt ? 'Modifier' : 'Nouvel' ?> emprunt</h3>

            <form method="POST">
                <!-- Champ caché pour identifier l'action -->
                <input type="hidden" name="action" value="<?= $editEmprunt ? 'edit' : 'add' ?>">

                <!-- Si mode édition, ajoute l'ID de l'emprunt -->
                <?php if ($editEmprunt): ?>
                    <input type="hidden" name="id" value="<?= $editEmprunt['id_emprunt'] ?>">
                <?php endif; ?>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Livre :</label>
                        <select name="id_livre" required>
                            <option value="">Sélectionner un livre</option>

                            <?php
                            // LOGIQUE IMPORTANTE :
                            // - Mode ajout : affiche seulement les livres disponibles
                            // - Mode édition : affiche tous les livres
                            $livresOptions = $editEmprunt ? $tousLivres : $livresDisponibles;

                            foreach ($livresOptions as $livre):
                            ?>
                                <option value="<?= $livre['id_livre'] ?>"
                                        <!-- Pré-sélectionne le livre actuel si édition -->
                                        <?= ($editEmprunt && $editEmprunt['id_livre'] == $livre['id_livre']) ? 'selected' : '' ?>>
                                    <!-- Affichage : "Titre - Auteur" -->
                                    <?= htmlspecialchars($livre['titre']) ?> - <?= htmlspecialchars($livre['auteur']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Emprunteur :</label>
                        <input type="text" name="nom_emprunteur"
                               value="<?= $editEmprunt['nom_emprunteur'] ?? '' ?>"
                               required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Date d'emprunt :</label>
                        <!-- date('Y-m-d') : format date PHP pour input date HTML5 -->
                        <!-- Par défaut : date du jour pour nouvel emprunt -->
                        <input type="date" name="date_emprunt"
                               value="<?= $editEmprunt['date_emprunt'] ?? date('Y-m-d') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Date de retour prévue :</label>
                        <!-- strtotime('+2 weeks') : ajoute 2 semaines à la date actuelle -->
                        <!-- Par défaut : 2 semaines après aujourd'hui -->
                        <input type="date" name="date_retour_prevue"
                               value="<?= $editEmprunt['date_retour_prevue'] ?? date('Y-m-d', strtotime('+2 weeks')) ?>"
                               required>
                    </div>

                    <!-- Champ visible uniquement en mode édition -->
                    <?php if ($editEmprunt): ?>
                    <div class="form-group">
                        <label>Date de retour effective :</label>
                        <!-- Pas de required : peut rester vide si livre non retourné -->
                        <input type="date" name="date_retour_effective"
                               value="<?= $editEmprunt['date_retour_effective'] ?? '' ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit"><?= $editEmprunt ? 'Modifier' : 'Créer' ?> l'emprunt</button>

                <?php if ($editEmprunt): ?>
                    <a href="emprunts.php" style="margin-left: 10px; text-decoration: none; color: #666;">Annuler</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========================================================================
             BARRE DE RECHERCHE ET FILTRES
             ======================================================================== -->
        <div class="search">
            <form method="GET">
                <div class="grid-form">
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search"
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Emprunteur, titre ou auteur">
                    </div>

                    <div>
                        <label>Statut :</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <!-- Maintient la sélection après filtrage -->
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

        <!-- ========================================================================
             TABLEAU D'AFFICHAGE DES EMPRUNTS
             ======================================================================== -->
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

                    <!-- Formatage des dates : conversion du format SQL au format français -->
                    <!-- strtotime() : convertit une date texte en timestamp Unix -->
                    <!-- date('d/m/Y', ...) : formate en jour/mois/année -->
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>

                    <td>
                        <!-- Opérateur ternaire : affiche la date ou un tiret si NULL -->
                        <?= $emprunt['date_retour_effective']
                            ? date('d/m/Y', strtotime($emprunt['date_retour_effective']))
                            : '-'  // Tiret si pas encore retourné
                        ?>
                    </td>

                    <td>
                        <!-- Affichage conditionnel du statut avec classes CSS différentes -->
                        <?php if ($emprunt['retard']): ?>
                            <!-- En retard : affichage prioritaire avec nombre de jours -->
                            <span class="status-retard">Retard: <?= $emprunt['retard'] ?></span>

                        <?php elseif ($emprunt['statut_emprunt'] == 'En cours'): ?>
                            <!-- En cours normal (pas en retard) -->
                            <span class="status-en-cours"><?= $emprunt['statut_emprunt'] ?></span>

                        <?php else: ?>
                            <!-- Retourné -->
                            <span class="status-retourne"><?= $emprunt['statut_emprunt'] ?></span>
                        <?php endif; ?>
                    </td>

                    <td class="actions">
                        <!-- Bouton "Retour" visible seulement si livre non retourné -->
                        <?php if (!$emprunt['date_retour_effective']): ?>
                            <a href="?retour=<?= $emprunt['id_emprunt'] ?>"
                               class="btn-retour"
                               onclick="return confirm('Marquer comme retourné ?')">Retour</a>
                        <?php endif; ?>

                        <!-- Actions toujours disponibles -->
                        <a href="modifier_emprunt.php?id=<?= $emprunt['id_emprunt'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $emprunt['id_emprunt'] ?>"
                           class="btn-delete"
                           onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ========================================================================
             PAGINATION (CONSERVE LES FILTRES)
             ======================================================================== -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            // Construction des paramètres à conserver dans l'URL
            $queryParams = [];

            if ($search) $queryParams[] = 'search=' . urlencode($search);
            if ($statut) $queryParams[] = 'statut=' . urlencode($statut);

            // Assemblage de la chaîne de requête
            $queryString = $queryParams ? '&' . implode('&', $queryParams) : '';
            ?>

            <!-- Génération des liens de pagination -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- ========================================================================
             INFORMATIONS DE PAGINATION
             ======================================================================== -->
        <p>Total : <?= $total ?> emprunt(s) - Page <?= $page ?> sur <?= $totalPages ?></p>
    </div>
</body>
</html>