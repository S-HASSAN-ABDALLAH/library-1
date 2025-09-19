<?php
require_once 'Database.php';

class BookManager {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function addBook($titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible = 1) {
        $stmt = $this->pdo->prepare("INSERT INTO livres (titre, id_auteur, categorie, isbn, annee_publication, disponible) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible]);
    }

    public function updateBook($id_livre, $titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible) {
        $stmt = $this->pdo->prepare("UPDATE livres SET titre=?, id_auteur=?, categorie=?, isbn=?, annee_publication=?, disponible=? WHERE id_livre=?");
        return $stmt->execute([$titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible, $id_livre]);
    }

    public function replaceBook($id_livre, $titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible) {
        $this->deleteBook($id_livre);
        return $this->addBook($titre, $id_auteur, $categorie, $isbn, $annee_publication, $disponible);
    }

    public function deleteBook($id_livre) {
        $stmt = $this->pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
        return $stmt->execute([$id_livre]);
    }

    public function getBookById($id_livre) {
        $stmt = $this->pdo->prepare("SELECT * FROM livres WHERE id_livre = ?");
        $stmt->execute([$id_livre]);
        return $stmt->fetch();
    }

    public function getBooks($search = '', $categorie = '', $disponible = '', $limit = 10, $offset = 0) {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(l.titre LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categorie) {
            $whereConditions[] = "l.categorie = ?";
            $params[] = $categorie;
        }

        if ($disponible !== '') {
            $whereConditions[] = "l.disponible = ?";
            $params[] = $disponible;
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("
            SELECT l.*, CONCAT(a.prenom, ' ', a.nom) as auteur
            FROM livres l
            JOIN auteurs a ON l.id_auteur = a.id_auteur
            $whereClause
            ORDER BY l.titre
            LIMIT $limit OFFSET $offset
        ");

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countBooks($search = '', $categorie = '', $disponible = '') {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(l.titre LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categorie) {
            $whereConditions[] = "l.categorie = ?";
            $params[] = $categorie;
        }

        if ($disponible !== '') {
            $whereConditions[] = "l.disponible = ?";
            $params[] = $disponible;
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM livres l JOIN auteurs a ON l.id_auteur = a.id_auteur $whereClause");
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getCategories() {
        $stmt = $this->pdo->query("
            SELECT DISTINCT categorie
            FROM livres
            WHERE categorie IS NOT NULL
            ORDER BY categorie
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>