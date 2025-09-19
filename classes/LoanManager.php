<?php
require_once 'Database.php';

class LoanManager {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function addLoan($id_livre, $nom_emprunteur, $email_emprunteur, $date_emprunt = null, $date_retour_prevue = null) {
        if ($date_emprunt === null) {
            $date_emprunt = date('Y-m-d');
        }
        if ($date_retour_prevue === null) {
            $date_retour_prevue = date('Y-m-d', strtotime('+30 days'));
        }

        $this->pdo->beginTransaction();
        try {
            // Ajouter l'emprunt
            $stmt = $this->pdo->prepare("INSERT INTO emprunts (id_livre, nom_emprunteur, email_emprunteur, date_emprunt, date_retour_prevue) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_livre, $nom_emprunteur, $email_emprunteur, $date_emprunt, $date_retour_prevue]);

            // Marquer le livre comme non disponible
            $stmt = $this->pdo->prepare("UPDATE livres SET disponible = 0 WHERE id_livre = ?");
            $stmt->execute([$id_livre]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function returnBook($id_emprunt, $date_retour_effective = null) {
        if ($date_retour_effective === null) {
            $date_retour_effective = date('Y-m-d');
        }

        $this->pdo->beginTransaction();
        try {
            // Récupérer l'ID du livre
            $stmt = $this->pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
            $stmt->execute([$id_emprunt]);
            $id_livre = $stmt->fetchColumn();

            // Mettre à jour l'emprunt
            $stmt = $this->pdo->prepare("UPDATE emprunts SET date_retour_effective = ? WHERE id_emprunt = ?");
            $stmt->execute([$date_retour_effective, $id_emprunt]);

            // Marquer le livre comme disponible
            $stmt = $this->pdo->prepare("UPDATE livres SET disponible = 1 WHERE id_livre = ?");
            $stmt->execute([$id_livre]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteLoan($id_emprunt) {
        $this->pdo->beginTransaction();
        try {
            // Récupérer l'ID du livre
            $stmt = $this->pdo->prepare("SELECT id_livre FROM emprunts WHERE id_emprunt = ?");
            $stmt->execute([$id_emprunt]);
            $id_livre = $stmt->fetchColumn();

            // Supprimer l'emprunt
            $stmt = $this->pdo->prepare("DELETE FROM emprunts WHERE id_emprunt = ?");
            $stmt->execute([$id_emprunt]);

            // Marquer le livre comme disponible
            $stmt = $this->pdo->prepare("UPDATE livres SET disponible = 1 WHERE id_livre = ?");
            $stmt->execute([$id_livre]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getLoanById($id_emprunt) {
        $stmt = $this->pdo->prepare("SELECT * FROM emprunts WHERE id_emprunt = ?");
        $stmt->execute([$id_emprunt]);
        return $stmt->fetch();
    }

    public function getLoans($search = '', $statut = '', $limit = 10, $offset = 0) {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(e.nom_emprunteur LIKE ? OR e.email_emprunteur LIKE ? OR l.titre LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statut === 'en_cours') {
            $whereConditions[] = "e.date_retour_effective IS NULL";
        } elseif ($statut === 'retourne') {
            $whereConditions[] = "e.date_retour_effective IS NOT NULL";
        } elseif ($statut === 'retard') {
            $whereConditions[] = "e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()";
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("
            SELECT e.*, l.titre as livre_titre, CONCAT(a.prenom, ' ', a.nom) as livre_auteur
            FROM emprunts e
            JOIN livres l ON e.id_livre = l.id_livre
            JOIN auteurs a ON l.id_auteur = a.id_auteur
            $whereClause
            ORDER BY e.date_emprunt DESC
            LIMIT $limit OFFSET $offset
        ");

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countLoans($search = '', $statut = '') {
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(e.nom_emprunteur LIKE ? OR e.email_emprunteur LIKE ? OR l.titre LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statut === 'en_cours') {
            $whereConditions[] = "e.date_retour_effective IS NULL";
        } elseif ($statut === 'retourne') {
            $whereConditions[] = "e.date_retour_effective IS NOT NULL";
        } elseif ($statut === 'retard') {
            $whereConditions[] = "e.date_retour_effective IS NULL AND e.date_retour_prevue < CURDATE()";
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM emprunts e
            JOIN livres l ON e.id_livre = l.id_livre
            JOIN auteurs a ON l.id_auteur = a.id_auteur
            $whereClause
        ");
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getOverdueLoans() {
        $stmt = $this->pdo->query("
            SELECT e.*, l.titre as livre_titre, CONCAT(a.prenom, ' ', a.nom) as livre_auteur
            FROM emprunts e
            JOIN livres l ON e.id_livre = l.id_livre
            JOIN auteurs a ON l.id_auteur = a.id_auteur
            WHERE e.date_retour_effective IS NULL
            AND e.date_retour_prevue < CURDATE()
            ORDER BY e.date_retour_prevue ASC
        ");
        return $stmt->fetchAll();
    }
}
?>