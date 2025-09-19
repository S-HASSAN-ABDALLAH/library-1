<?php
require_once 'autoload.php';

$loanManager = new LoanManager();
$bookManager = new BookManager();

$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($_POST['action'] ?? '' == 'add') {
    $loanManager->addLoan(
        $_POST['id_livre'],
        $_POST['nom_emprunteur'],
        $_POST['email_emprunteur'],
        $_POST['date_emprunt'] ?: null,
        $_POST['date_retour_prevue'] ?: null
    );
    header('Location: emprunts_modular.php');
    exit;
}

if ($_POST['action'] ?? '' == 'return') {
    $loanManager->returnBook(
        $_POST['id_emprunt'],
        $_POST['date_retour_effective'] ?: null
    );
    header('Location: emprunts_modular.php');
    exit;
}

if ($_GET['delete'] ?? false) {
    $loanManager->deleteLoan($_GET['delete']);
    header('Location: emprunts_modular.php');
    exit;
}

$total = $loanManager->countLoans($search, $statut);
$totalPages = ceil($total / $limit);
$emprunts = $loanManager->getLoans($search, $statut, $limit, $offset);

// Récupérer les livres disponibles pour le formulaire d'ajout
$livresDisponibles = $bookManager->getBooks('', '', '1', 100, 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emprunts - Version Modulaire</title>
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
        <h1>Gestion des Emprunts (Version Modulaire)</h1>

        <div class="form-container">
            <h3>Nouvel emprunt</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Livre :</label>
                    <select name="id_livre" required>
                        <option value="">Sélectionner un livre</option>
                        <?php foreach ($livresDisponibles as $livre): ?>
                            <option value="<?= $livre['id_livre'] ?>">
                                <?= htmlspecialchars($livre['titre']) ?> - <?= htmlspecialchars($livre['auteur']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nom de l'emprunteur :</label>
                        <input type="text" name="nom_emprunteur" required>
                    </div>
                    <div class="form-group">
                        <label>Email :</label>
                        <input type="email" name="email_emprunteur" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Date d'emprunt :</label>
                        <input type="date" name="date_emprunt" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Date de retour prévue :</label>
                        <input type="date" name="date_retour_prevue" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                </div>

                <button type="submit">Créer l'emprunt</button>
            </form>
        </div>

        <div class="search">
            <form method="GET">
                <div class="grid-2-1-1">
                    <div>
                        <label>Rechercher :</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Emprunteur ou livre">
                    </div>
                    <div>
                        <label>Statut :</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="retourne" <?= $statut === 'retourne' ? 'selected' : '' ?>>Retourné</option>
                            <option value="retard" <?= $statut === 'retard' ? 'selected' : '' ?>>En retard</option>
                        </select>
                    </div>
                    <button type="submit">Filtrer</button>
                    <a href="emprunts_modular.php" style="text-decoration: none; color: #666;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Livre</th>
                    <th>Emprunteur</th>
                    <th>Email</th>
                    <th>Date emprunt</th>
                    <th>Retour prévu</th>
                    <th>Retour effectif</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emprunts as $emprunt): ?>
                <tr>
                    <td><?= $emprunt['id_emprunt'] ?></td>
                    <td><?= htmlspecialchars($emprunt['livre_titre']) ?><br>
                        <small><?= htmlspecialchars($emprunt['livre_auteur']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($emprunt['nom_emprunteur']) ?></td>
                    <td><?= htmlspecialchars($emprunt['email_emprunteur']) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                    <td><?= $emprunt['date_retour_effective'] ? date('d/m/Y', strtotime($emprunt['date_retour_effective'])) : '-' ?></td>
                    <td>
                        <?php if ($emprunt['date_retour_effective']): ?>
                            <span class="status-disponible">Retourné</span>
                        <?php elseif (strtotime($emprunt['date_retour_prevue']) < time()): ?>
                            <span class="status-retard">En retard</span>
                        <?php else: ?>
                            <span class="status-indisponible">En cours</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if (!$emprunt['date_retour_effective']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                                <input type="hidden" name="date_retour_effective" value="<?= date('Y-m-d') ?>">
                                <button type="submit" class="btn-edit" onclick="return confirm('Marquer comme retourné ?')">Retourner</button>
                            </form>
                        <?php endif; ?>
                        <a href="?delete=<?= $emprunt['id_emprunt'] ?>" class="btn-delete"
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
            if ($statut) $queryParams[] = 'statut=' . urlencode($statut);
            $queryString = $queryParams ? '&' . implode('&', $queryParams) : '';
            ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ?>"
                   class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <p>Total : <?= $total ?> emprunt(s) - Page <?= $page ?> sur <?= $totalPages ?></p>

        <?php
        // Afficher les emprunts en retard
        $empruntsEnRetard = $loanManager->getOverdueLoans();
        if (!empty($empruntsEnRetard)):
        ?>
        <div style="margin-top: 30px; padding: 15px; background-color: #ffeaa7; border-left: 4px solid #fdcb6e;">
            <h3>Emprunts en retard (<?= count($empruntsEnRetard) ?>)</h3>
            <table style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Emprunteur</th>
                        <th>Livre</th>
                        <th>Retour prévu</th>
                        <th>Jours de retard</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empruntsEnRetard as $retard): ?>
                    <tr>
                        <td><?= htmlspecialchars($retard['nom_emprunteur']) ?></td>
                        <td><?= htmlspecialchars($retard['livre_titre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($retard['date_retour_prevue'])) ?></td>
                        <td><?= floor((time() - strtotime($retard['date_retour_prevue'])) / (60*60*24)) ?> jours</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .status-retard {
        background-color: #e74c3c;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
    }
    </style>
</body>
</html>