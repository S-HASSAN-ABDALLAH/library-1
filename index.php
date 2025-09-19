<?php
/**
 * PAGE D'ACCUEIL - Système de gestion de bibliothèque
 *
 * Cette page affiche l'interface principale avec les liens
 * vers les différentes sections de l'application
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Bibliothèque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation principale -->
    <div class="nav">
        <a href="index.php">Accueil</a>
        <a href="auteurs_modular.php">Auteurs</a>
        <a href="livres.php">Livres</a>
        <a href="emprunts.php">Emprunts</a>
    </div>

    <!-- Conteneur principal -->
    <div class="container">
        <h1>Gestion de Bibliothèque</h1>

        <!-- Grille des modules principaux -->
        <div class="home-grid">
            <!-- Module Auteurs -->
            <div class="home-card">
                <h3>Auteurs</h3>
                <p>Gérer les auteurs de la bibliothèque</p>
                <a href="auteurs.php">
                    <button>Accèder aux auteurs</button>
                </a>
            </div>

            <!-- Module Livres -->
            <div class="home-card">
                <h3>Livres</h3>
                <p>Gérer le catalogue de livres</p>
                <a href="livres.php">
                    <button>Accéder aux livres</button>
                </a>
            </div>

            <!-- Module Emprunts -->
            <div class="home-card">
                <h3>Emprunts</h3>
                <p>Gérer les emprunts de livres</p>
                <a href="emprunts.php">
                    <button>Accéder aux emprunts</button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>