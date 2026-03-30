<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"] ?? 0);

    if ($id <= 0) {
        echo "ID invalide.";
        exit;
    }

    try {
        $sql = "DELETE FROM livres WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":id" => $id
        ]);

        echo "Livre supprimé avec succès.";
    } catch (PDOException $e) {
        echo "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    echo "Méthode non autorisée.";
}
?>