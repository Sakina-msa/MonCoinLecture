<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db.php";

try {
    $sql = "SELECT id, titre, auteur, genre, note, statut
            FROM livres
            ORDER BY id DESC";

    $stmt = $pdo->query($sql);
    $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($livres);
} catch (PDOException $e) {
    echo json_encode([
        "erreur" => $e->getMessage()
    ]);
}
?>