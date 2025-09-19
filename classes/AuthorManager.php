<?php
require_once 'Database.php';

class AuthorManager {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function addAuthor($nom, $prenom, $nationalite = null, $date_naissance = null) {
        $stmt = $this->pdo->prepare("INSERT INTO auteurs (nom, prenom, nationalite, date_naissance) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nom, $prenom, $nationalite, $date_naissance]);
    }

    public function updateAuthor($id_auteur, $nom, $prenom, $nationalite = null, $date_naissance = null) {
        $stmt = $this->pdo->prepare("UPDATE auteurs SET nom=?, prenom=?, nationalite=?, date_naissance=? WHERE id_auteur=?");
        return $stmt->execute([$nom, $prenom, $nationalite, $date_naissance, $id_auteur]);
    }

    public function deleteAuthor($id_auteur) {
        $stmt = $this->pdo->prepare("DELETE FROM auteurs WHERE id_auteur = ?");
        return $stmt->execute([$id_auteur]);
    }

    public function getAuthorById($id_auteur) {
        $stmt = $this->pdo->prepare("SELECT * FROM auteurs WHERE id_auteur = ?");
        $stmt->execute([$id_auteur]);
        return $stmt->fetch();
    }

    public function getAuthors($search = '', $limit = 10, $offset = 0) {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(nom LIKE ? OR prenom LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM auteurs
            $whereClause
            ORDER BY nom, prenom
            LIMIT $limit OFFSET $offset
        ");

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAuthors($search = '') {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(nom LIKE ? OR prenom LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM auteurs $whereClause");
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getAllAuthorsForSelect() {
        $stmt = $this->pdo->query("
            SELECT id_auteur, CONCAT(prenom, ' ', nom) as nom_complet
            FROM auteurs
            ORDER BY nom, prenom
        ");
        return $stmt->fetchAll();
    }

    public function hasBooks($id_auteur) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM livres WHERE id_auteur = ?");
        $stmt->execute([$id_auteur]);
        return $stmt->fetchColumn() > 0;
    }
}
?>