-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 17 sep. 2025 à 08:01
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `library`
--

-- --------------------------------------------------------

--
-- Structure de la table `auteurs`
--

DROP TABLE IF EXISTS `auteurs`;
CREATE TABLE IF NOT EXISTS `auteurs` (
  `id_auteur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `nationalite` varchar(50) COLLATE utf8mb3_general_mysql500_ci DEFAULT NULL,
  PRIMARY KEY (`id_auteur`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `auteurs`
--

INSERT INTO `auteurs` (`id_auteur`, `nom`, `prenom`, `date_naissance`, `nationalite`) VALUES
(3, 'Camus', 'Albert', '1913-11-07', 'Française'),
(5, 'Zola', 'Émile', '1840-04-02', 'Française'),
(6, 'Hugo', 'Victor', '1802-02-26', 'Française'),
(9, 'Flaubert', 'Gustave', '1821-12-12', 'Française'),
(10, 'Maupassant', 'Guy de', '1850-08-05', 'Française'),
(11, 'Proust', 'Marcel', '1871-07-10', 'Française'),
(12, 'Dumas', 'Alexandre', '1802-07-24', 'Française'),
(13, 'Verne', 'Jules', '1828-02-08', 'Française'),
(14, 'Saint-Exupéry', 'Antoine de', '1900-06-29', 'Française'),
(15, 'Levy', 'Marc', '1961-10-16', 'Française'),
(16, 'Musso', 'Guillaume', '1974-06-06', 'Française'),
(17, 'Houellebecq', 'Michel', '1956-02-26', 'Française'),
(18, 'Nothomb', 'Amélie', '1966-07-09', 'Belge'),
(19, 'Rowling', 'J.K.', '1965-07-31', 'Britannique'),
(20, 'Tolkien', 'J.R.R.', '1892-01-03', 'Britannique'),
(21, 'Christie', 'Agatha', '1890-09-15', 'Britannique'),
(22, 'Orwell', 'George', '1903-06-25', 'Britannique'),
(23, 'Dickens', 'Charles', '1812-02-07', 'Britannique'),
(24, 'Conan Doyle', 'Arthur', '1859-05-22', 'Britannique'),
(25, 'King', 'Stephen', '1947-09-21', 'Américaine'),
(26, 'Hemingway', 'Ernest', '1899-07-21', 'Américaine'),
(27, 'Fitzgerald', 'F. Scott', '1896-09-24', 'Américaine'),
(28, 'Twain', 'Mark', '1835-11-30', 'Américaine'),
(29, 'Poe', 'Edgar Allan', '1809-01-19', 'Américaine'),
(30, 'García Márquez', 'Gabriel', '1927-03-06', 'Colombienne'),
(31, 'Murakami', 'Haruki', '1949-01-12', 'Japonaise'),
(32, 'Eco', 'Umberto', '1932-01-05', 'Italienne'),
(33, 'Kafka', 'Franz', '1883-07-03', 'Tchèque'),
(34, 'Dostoïevski', 'Fiodor', '1821-11-11', 'Russe');

-- --------------------------------------------------------

--
-- Structure de la table `emprunts`
--

DROP TABLE IF EXISTS `emprunts`;
CREATE TABLE IF NOT EXISTS `emprunts` (
  `id_emprunt` int NOT NULL AUTO_INCREMENT,
  `id_livre` int DEFAULT NULL,
  `nom_emprunteur` varchar(100) COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_emprunt` date NOT NULL,
  `date_retour_prevue` date NOT NULL,
  `date_retour_effective` date DEFAULT NULL,
  PRIMARY KEY (`id_emprunt`),
  KEY `id_livre` (`id_livre`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `emprunts`
--

INSERT INTO `emprunts` (`id_emprunt`, `id_livre`, `nom_emprunteur`, `date_emprunt`, `date_retour_prevue`, `date_retour_effective`) VALUES
(1, 2, 'Martin Dupont', '2024-01-10', '2024-01-24', '2024-01-22'),
(2, 3, 'Sophie Bernard', '2024-01-15', '2024-01-29', NULL),
(3, 7, 'Lucas Moreau', '2024-01-18', '2024-02-01', NULL),
(4, 2, 'Emma Petit', '2024-01-20', '2024-02-03', NULL),
(5, 2, 'Martin Dupont', '2023-11-10', '2023-11-24', '2023-11-22'),
(6, 5, 'Sophie Bernard', '2023-11-15', '2023-11-29', '2023-11-28'),
(7, 12, 'Lucas Moreau', '2023-12-01', '2023-12-15', '2023-12-14'),
(8, 18, 'Emma Petit', '2023-12-05', '2023-12-19', '2023-12-20'),
(9, 25, 'Thomas Martin', '2023-12-10', '2023-12-24', '2023-12-23'),
(10, 31, 'Julie Durand', '2023-12-15', '2023-12-29', '2023-12-28'),
(11, 1, 'Pierre Lefebvre', '2023-12-20', '2024-01-03', '2024-01-02'),
(12, 8, 'Marie Rousseau', '2023-12-22', '2024-01-05', '2024-01-04'),
(13, 15, 'Alexandre Moreau', '2024-01-02', '2024-01-16', '2024-01-15'),
(14, 22, 'Camille Blanc', '2024-01-05', '2024-01-19', '2024-01-18'),
(15, 28, 'Nicolas Girard', '2024-01-08', '2024-01-22', '2024-01-21'),
(16, 35, 'Léa Bonnet', '2024-01-10', '2024-01-24', '2024-01-23'),
(17, 2, 'Charlotte Dubois', '2024-01-12', '2024-01-26', NULL),
(18, 6, 'Maxime Lambert', '2024-01-14', '2024-01-28', NULL),
(19, 9, 'Océane Garnier', '2024-01-15', '2024-01-29', NULL),
(20, 13, 'Antoine Roux', '2024-01-16', '2024-01-30', NULL),
(21, 17, 'Clara Morel', '2024-01-17', '2024-01-31', NULL),
(22, 20, 'Hugo Faure', '2024-01-18', '2024-02-01', NULL),
(23, 24, 'Manon André', '2024-01-19', '2024-02-02', NULL),
(24, 27, 'Louis Mercier', '2024-01-20', '2024-02-03', NULL),
(25, 30, 'Inès Giraud', '2024-01-21', '2024-02-04', NULL),
(26, 34, 'Gabriel Fournier', '2024-01-22', '2024-02-05', NULL),
(27, 37, 'Zoé Lambert', '2024-01-23', '2024-02-06', NULL),
(28, 40, 'Nathan Dubois', '2024-01-24', '2024-02-07', NULL),
(29, 43, 'Chloé Vincent', '2024-01-25', '2024-02-08', NULL),
(30, 46, 'Raphaël Moreau', '2024-01-26', '2024-02-09', NULL),
(31, 49, 'Alice Blanc', '2024-01-26', '2024-02-09', NULL),
(32, 51, 'Théo Martin', '2024-01-27', '2024-02-10', NULL),
(33, 54, 'Sarah Petit', '2024-01-27', '2024-02-10', NULL),
(34, 56, 'Lucas Bernard', '2024-01-28', '2024-02-11', NULL),
(35, 58, 'Emma Rousseau', '2024-01-28', '2024-02-11', NULL),
(36, 60, 'Arthur Dupont', '2024-01-29', '2024-02-12', NULL),
(37, 62, 'Jade Leroy', '2024-01-29', '2024-02-12', NULL),
(38, 64, 'Léo Michel', '2024-01-30', '2024-02-13', NULL),
(39, 67, 'Louise Robert', '2024-01-30', '2024-02-13', NULL),
(40, 70, 'Jules Richard', '2024-01-31', '2024-02-14', NULL),
(41, 73, 'Rose Garnier', '2024-01-31', '2024-02-14', NULL),
(42, 75, 'Oscar Moreau', '2024-02-01', '2024-02-15', NULL),
(43, 78, 'Margot Dubois', '2024-02-01', '2024-02-15', NULL),
(44, 80, 'Tom Laurent', '2024-02-02', '2024-02-16', NULL),
(45, 83, 'Nina Simon', '2024-02-02', '2024-02-16', NULL),
(46, 85, 'Victor Blanc', '2024-02-03', '2024-02-17', NULL),
(47, 86, 'Lola Martin', '2024-02-03', '2024-02-17', NULL),
(48, 89, 'Adam Petit', '2024-02-04', '2024-02-18', NULL),
(49, 91, 'Eva Thomas', '2024-02-04', '2024-02-18', NULL),
(50, 93, 'Paul Garcia', '2024-02-05', '2024-02-19', NULL),
(51, 95, 'Léna Martinez', '2024-02-05', '2024-02-19', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

DROP TABLE IF EXISTS `livres`;
CREATE TABLE IF NOT EXISTS `livres` (
  `id_livre` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_auteur` int DEFAULT NULL,
  `categorie` varchar(50) COLLATE utf8mb3_general_mysql500_ci DEFAULT NULL,
  `isbn` varchar(13) COLLATE utf8mb3_general_mysql500_ci DEFAULT NULL,
  `annee_publication` int DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_livre`),
  KEY `id_auteur` (`id_auteur`)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id_livre`, `titre`, `id_auteur`, `categorie`, `isbn`, `annee_publication`, `disponible`) VALUES
(1, 'Les Misérables', 1, 'Roman', '9782070409228', 1862, 1),
(2, 'Notre-Dame de Paris', 1, 'Roman', '9782070422524', 1831, 0),
(3, 'Harry Potter à l\'école des sorciers', 2, 'Fantasy', '9782070584628', 1997, 0),
(4, 'Harry Potter et la Chambre des secrets', 2, 'Fantasy', '9782070584642', 1998, 1),
(5, 'L\'Étranger', 3, 'Roman', '9782070360024', 1942, 1),
(6, 'La Peste', 3, 'Roman', '9782070360420', 1947, 1),
(7, 'Le Seigneur des anneaux', 4, 'Fantasy', '9782266154116', 1954, 0),
(8, 'Le Hobbit', 4, 'Fantasy', '9782253049418', 1937, 1),
(9, 'Germinal', 5, 'Roman', '9782070413003', 1885, 1),
(10, 'L\'Assommoir', 5, 'Roman', '9782070413027', 1877, 1),
(11, 'Les Misérables', 1, 'Roman Classique', '9782070409228', 1862, 1),
(12, 'Notre-Dame de Paris', 1, 'Roman Classique', '9782070422524', 1831, 0),
(13, 'Les Contemplations', 1, 'Poésie', '9782070320257', 1856, 1),
(14, 'Quatrevingt-treize', 1, 'Roman Historique', '9782070382958', 1874, 1),
(15, 'Germinal', 2, 'Roman Naturaliste', '9782070413003', 1885, 1),
(16, 'L\'Assommoir', 2, 'Roman Naturaliste', '9782070413027', 1877, 0),
(17, 'Au Bonheur des Dames', 2, 'Roman Naturaliste', '9782070422182', 1883, 1),
(18, 'Nana', 2, 'Roman Naturaliste', '9782070413102', 1880, 1),
(21, 'La Chute', 3, 'Roman Philosophique', '9782070360028', 1956, 1),
(22, 'Le Mythe de Sisyphe', 3, 'Essai', '9782070322886', 1942, 1),
(23, 'Madame Bovary', 4, 'Roman Classique', '9782070413119', 1857, 0),
(24, 'L\'Éducation sentimentale', 4, 'Roman Classique', '9782070412396', 1869, 1),
(25, 'Salammbô', 4, 'Roman Historique', '9782070385737', 1862, 1),
(26, 'Bel-Ami', 5, 'Roman', '9782070409341', 1885, 1),
(27, 'Une Vie', 5, 'Roman', '9782070373420', 1883, 0),
(28, 'Pierre et Jean', 5, 'Roman', '9782070384877', 1888, 1),
(29, 'Du côté de chez Swann', 6, 'Roman', '9782070392407', 1913, 1),
(30, 'À l\'ombre des jeunes filles en fleurs', 6, 'Roman', '9782070394005', 1919, 0),
(31, 'Le Temps retrouvé', 6, 'Roman', '9782070394012', 1927, 1),
(32, 'Les Trois Mousquetaires', 7, 'Roman Historique', '9782070405374', 1844, 0),
(33, 'Le Comte de Monte-Cristo', 7, 'Roman Historique', '9782070424764', 1844, 1),
(34, 'La Reine Margot', 7, 'Roman Historique', '9782070385454', 1845, 1),
(35, 'Vingt mille lieues sous les mers', 8, 'Science-Fiction', '9782070424832', 1869, 1),
(36, 'Le Tour du monde en 80 jours', 8, 'Aventure', '9782070500628', 1872, 0),
(37, 'De la Terre à la Lune', 8, 'Science-Fiction', '9782070424023', 1865, 1),
(38, 'Voyage au centre de la Terre', 8, 'Science-Fiction', '9782070623648', 1864, 1),
(39, 'Le Petit Prince', 9, 'Conte', '9782070612758', 1943, 0),
(40, 'Vol de nuit', 9, 'Roman', '9782070361038', 1931, 1),
(41, 'Terre des hommes', 9, 'Roman', '9782070361213', 1939, 1),
(42, 'Et si c\'était vrai...', 10, 'Roman Contemporain', '9782266110211', 2000, 1),
(43, 'Où es-tu ?', 10, 'Roman Contemporain', '9782266112062', 2001, 0),
(44, 'L\'Horizon à l\'envers', 10, 'Roman Contemporain', '9782266268646', 2016, 1),
(45, 'Après...', 11, 'Thriller', '9782845632134', 2004, 1),
(46, 'Sauve-moi', 11, 'Thriller', '9782845633506', 2005, 0),
(47, 'La Fille de papier', 11, 'Roman', '9782845639560', 2010, 1),
(48, 'Les Particules élémentaires', 12, 'Roman Contemporain', '9782290028599', 1998, 1),
(49, 'La Carte et le Territoire', 12, 'Roman Contemporain', '9782082105866', 2010, 0),
(50, 'Soumission', 12, 'Roman Contemporain', '9782082139434', 2015, 1),
(51, 'Stupeur et Tremblements', 13, 'Roman Autobiographique', '9782253150718', 1999, 1),
(52, 'Hygiène de l\'assassin', 13, 'Roman', '9782253136491', 1992, 0),
(53, 'Métaphysique des tubes', 13, 'Roman Autobiographique', '9782253154938', 2000, 1),
(54, 'Harry Potter à l\'école des sorciers', 14, 'Fantasy', '9782070584628', 1997, 0),
(55, 'Harry Potter et la Chambre des secrets', 14, 'Fantasy', '9782070584642', 1998, 1),
(56, 'Harry Potter et le Prisonnier d\'Azkaban', 14, 'Fantasy', '9782070584925', 1999, 0),
(57, 'Harry Potter et la Coupe de feu', 14, 'Fantasy', '9782070585205', 2000, 1),
(58, 'Harry Potter et l\'Ordre du phénix', 14, 'Fantasy', '9782070585212', 2003, 0),
(59, 'Harry Potter et le Prince de sang-mêlé', 14, 'Fantasy', '9782070572670', 2005, 1),
(60, 'Harry Potter et les Reliques de la Mort', 14, 'Fantasy', '9782070615360', 2007, 0),
(61, 'Le Hobbit', 15, 'Fantasy', '9782253049418', 1937, 1),
(62, 'Le Seigneur des anneaux - La Communauté', 15, 'Fantasy', '9782266154116', 1954, 0),
(63, 'Le Seigneur des anneaux - Les Deux Tours', 15, 'Fantasy', '9782266154413', 1954, 1),
(64, 'Le Seigneur des anneaux - Le Retour du roi', 15, 'Fantasy', '9782266154710', 1955, 0),
(65, 'Le Silmarillion', 15, 'Fantasy', '9782266121002', 1977, 1),
(66, 'Le Crime de l\'Orient-Express', 16, 'Policier', '9782702424056', 1934, 0),
(67, 'Dix Petits Nègres', 16, 'Policier', '9782702421444', 1939, 1),
(68, 'Mort sur le Nil', 16, 'Policier', '9782702447369', 1937, 0),
(69, 'Le Meurtre de Roger Ackroyd', 16, 'Policier', '9782702448533', 1926, 1),
(70, '1984', 17, 'Science-Fiction', '9782070368228', 1949, 0),
(71, 'La Ferme des animaux', 17, 'Fable Politique', '9782070375165', 1945, 1),
(72, 'Oliver Twist', 18, 'Roman Social', '9782070409204', 1838, 1),
(73, 'David Copperfield', 18, 'Roman', '9782070411870', 1850, 0),
(74, 'Un conte de Noël', 18, 'Conte', '9782070454792', 1843, 1),
(75, 'Une étude en rouge', 19, 'Policier', '9782253098119', 1887, 1),
(76, 'Le Chien des Baskerville', 19, 'Policier', '9782253003977', 1902, 0),
(77, 'Les Aventures de Sherlock Holmes', 19, 'Policier', '9782253006329', 1892, 1),
(78, 'Carrie', 20, 'Horreur', '9782253151432', 1974, 1),
(79, 'Shining', 20, 'Horreur', '9782253151456', 1977, 0),
(80, 'Ça', 20, 'Horreur', '9782253147145', 1986, 1),
(81, 'Le Fléau', 20, 'Science-Fiction', '9782253147695', 1978, 0),
(82, 'Misery', 20, 'Thriller', '9782253058052', 1987, 1),
(83, 'Le Vieil Homme et la Mer', 21, 'Roman', '9782070360079', 1952, 1),
(84, 'Pour qui sonne le glas', 21, 'Roman de Guerre', '9782070360796', 1940, 0),
(85, 'L\'Adieu aux armes', 21, 'Roman de Guerre', '9782070365371', 1929, 1),
(86, 'Gatsby le Magnifique', 22, 'Roman', '9782070380640', 1925, 0),
(87, 'Tendre est la nuit', 22, 'Roman', '9782070384877', 1934, 1),
(88, 'Les Aventures de Tom Sawyer', 23, 'Aventure', '9782070629770', 1876, 1),
(89, 'Les Aventures d\'Huckleberry Finn', 23, 'Aventure', '9782070387199', 1884, 0),
(90, 'Histoires extraordinaires', 24, 'Nouvelles', '9782070383627', 1856, 1),
(91, 'Nouvelles histoires extraordinaires', 24, 'Nouvelles', '9782070409334', 1857, 0),
(92, 'Cent ans de solitude', 25, 'Réalisme Magique', '9782020238113', 1967, 0),
(93, 'L\'Amour aux temps du choléra', 25, 'Roman', '9782246337416', 1985, 1),
(94, 'Kafka sur le rivage', 26, 'Roman Contemporain', '9782714449283', 2002, 1),
(95, '1Q84', 26, 'Science-Fiction', '9782714449290', 2009, 0),
(96, 'Norwegian Wood', 26, 'Roman', '9782714438836', 1987, 1),
(97, 'Le Nom de la rose', 27, 'Roman Historique', '9782253033134', 1980, 0),
(98, 'Le Pendule de Foucault', 27, 'Roman', '9782253064435', 1988, 1),
(99, 'La Métamorphose', 28, 'Nouvelle', '9782070361973', 1915, 1),
(100, 'Le Procès', 28, 'Roman', '9782070365425', 1925, 0),
(101, 'Le Château', 28, 'Roman', '9782070365531', 1926, 1),
(102, 'Crime et Châtiment', 29, 'Roman Psychologique', '9782070369621', 1866, 0),
(103, 'Les Frères Karamazov', 29, 'Roman Philosophique', '9782070381333', 1880, 1),
(104, 'L\'Idiot', 29, 'Roman', '9782070383825', 1869, 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
