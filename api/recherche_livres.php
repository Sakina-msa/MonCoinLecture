<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db.php";

$motCle = trim($_GET["q"] ?? "");

try {
    if ($motCle === "") {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT id, titre, auteur, genre, note, statut, image, description, isbn
            FROM livres
            WHERE titre LIKE :motcle
               OR auteur LIKE :motcle
               OR genre LIKE :motcle
               OR isbn LIKE :motcle
            ORDER BY titre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":motcle" => "%" . $motCle . "%"
    ]);

    $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($livres);

} catch (PDOException $e) {
    echo json_encode([
        "erreur" => "Erreur lors de la recherche : " . $e->getMessage()
    ]);
}
?>