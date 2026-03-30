<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"] ?? 0);
    $statut = trim($_POST["statut"] ?? "");

    if ($id <= 0) {
        echo "ID invalide.";
        exit;
    }

    if ($statut !== "lu" && $statut !== "a lire") {
        echo "Statut invalide.";
        exit;
    }

    try {
        $sql = "UPDATE livres SET statut = :statut WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":statut" => $statut,
            ":id" => $id
        ]);

        echo "Statut modifié avec succès.";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification : " . $e->getMessage();
    }
} else {
    echo "Méthode non autorisée.";
}
?>