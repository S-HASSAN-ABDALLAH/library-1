-- ===============================================
-- SCRIPT DE CRÉATION DE LA BASE DE DONNÉES
-- Système de gestion de bibliothèque
-- ===============================================

-- Création de la base de données principale
CREATE DATABASE IF NOT EXISTS library;
USE library;

-- ===============================================
-- TABLE AUTEURS
-- Stocke les informations des auteurs de livres
-- ===============================================
CREATE TABLE auteurs (
    id_auteur INT PRIMARY KEY AUTO_INCREMENT,    -- Identifiant unique de l'auteur
    nom VARCHAR(100) NOT NULL,                   -- Nom de famille (obligatoire)
    prenom VARCHAR(100) NOT NULL,                -- Prénom (obligatoire)
    date_naissance DATE,                         -- Date de naissance (optionnel)
    nationalite VARCHAR(50)                      -- Nationalité (optionnel)
);

-- ===============================================
-- TABLE LIVRES
-- Catalogue des livres de la bibliothèque
-- ===============================================
CREATE TABLE livres (
    id_livre INT PRIMARY KEY AUTO_INCREMENT,     -- Identifiant unique du livre
    titre VARCHAR(200) NOT NULL,                 -- Titre du livre (obligatoire)
    id_auteur INT,                               -- Référence vers l'auteur
    categorie VARCHAR(50),                       -- Genre/catégorie du livre
    isbn VARCHAR(13),                            -- Code ISBN (optionnel)
    annee_publication INT,                       -- Année de publication
    disponible BOOLEAN DEFAULT TRUE,             -- Statut de disponibilité (TRUE = disponible)
    FOREIGN KEY (id_auteur) REFERENCES auteurs(id_auteur) -- Clé étrangère vers auteurs
);

-- ===============================================
-- TABLE EMPRUNTS
-- Historique des emprunts de livres
-- ===============================================
CREATE TABLE emprunts (
    id_emprunt INT PRIMARY KEY AUTO_INCREMENT,   -- Identifiant unique de l'emprunt
    id_livre INT,                                -- Référence vers le livre emprunté
    nom_emprunteur VARCHAR(100) NOT NULL,        -- Nom de la personne qui emprunte
    date_emprunt DATE NOT NULL,                  -- Date de début d'emprunt
    date_retour_prevue DATE NOT NULL,            -- Date de retour prévue
    date_retour_effective DATE,                  -- Date de retour réelle (NULL = pas encore rendu)
    FOREIGN KEY (id_livre) REFERENCES livres(id_livre) -- Clé étrangère vers livres
);

-- ===============================================
-- INSERTION DES DONNÉES D'EXEMPLE
-- Jeu de données de test pour la bibliothèque
-- ===============================================

-- Insertion des auteurs célèbres
INSERT INTO auteurs (nom, prenom, date_naissance, nationalite) VALUES
('Hugo', 'Victor', '1802-02-26', 'Française'),      -- Auteur français classique
('Rowling', 'J.K.', '1965-07-31', 'Britannique'),   -- Créatrice d'Harry Potter
('Camus', 'Albert', '1913-11-07', 'Française'),     -- Écrivain et philosophe
('Tolkien', 'J.R.R.', '1892-01-03', 'Britannique'), -- Créateur du Seigneur des Anneaux
('Zola', 'Émile', '1840-04-02', 'Française');       -- Écrivain naturaliste

-- Insertion du catalogue de livres
INSERT INTO livres (titre, id_auteur, categorie, isbn, annee_publication, disponible) VALUES
-- Œuvres de Victor Hugo
('Les Misérables', 1, 'Roman', '9782070409228', 1862, TRUE),
('Notre-Dame de Paris', 1, 'Roman', '9782070422524', 1831, FALSE),

-- Saga Harry Potter
('Harry Potter à l\'école des sorciers', 2, 'Fantasy', '9782070584628', 1997, FALSE),
('Harry Potter et la Chambre des secrets', 2, 'Fantasy', '9782070584642', 1998, TRUE),

-- Œuvres d'Albert Camus
('L\'Étranger', 3, 'Roman', '9782070360024', 1942, TRUE),
('La Peste', 3, 'Roman', '9782070360420', 1947, TRUE),

-- Œuvres de Tolkien
('Le Seigneur des anneaux', 4, 'Fantasy', '9782266154116', 1954, FALSE),
('Le Hobbit', 4, 'Fantasy', '9782253049418', 1937, TRUE),

-- Œuvres d'Émile Zola
('Germinal', 5, 'Roman', '9782070413003', 1885, TRUE),
('L\'Assommoir', 5, 'Roman', '9782070413027', 1877, TRUE);

-- Insertion d'emprunts d'exemple (certains en cours, d'autres terminés)
INSERT INTO emprunts (id_livre, nom_emprunteur, date_emprunt, date_retour_prevue, date_retour_effective) VALUES
-- Emprunt terminé (livre retourné)
(2, 'Martin Dupont', '2024-01-10', '2024-01-24', '2024-01-22'),

-- Emprunts en cours (non retournés)
(3, 'Sophie Bernard', '2024-01-15', '2024-01-29', NULL),    -- Harry Potter 1
(7, 'Lucas Moreau', '2024-01-18', '2024-02-01', NULL),     -- Seigneur des Anneaux
(2, 'Emma Petit', '2024-01-20', '2024-02-03', NULL);       -- Notre-Dame de Paris