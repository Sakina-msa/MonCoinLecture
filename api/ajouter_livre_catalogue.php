<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = $_POST["titre"] ?? "";
    $auteur = $_POST["auteur"] ?? "";
    $genre = $_POST["genre"] ?? "";
    $source_api = $_POST["source_api"] ?? null;
    $source_id = $_POST["source_id"] ?? null;
    $date_publication = $_POST["date_publication"] ?? null;
    $isbn = $_POST["isbn"] ?? null;
    $image = $_POST["image"] ?? null;

    if (empty($titre)) {
        echo "Titre manquant.";
        exit;
    }

    $sql = "INSERT INTO livres 
            (titre, auteur, genre, source_api, source_id, date_publication, isbn, image, statut, favori)
            VALUES
            (:titre, :auteur, :genre, :source_api, :source_id, :date_publication, :isbn, :image, 'a lire', 0)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":titre" => $titre,
        ":auteur" => $auteur,
        ":genre" => $genre,
        ":source_api" => $source_api,
        ":source_id" => $source_id,
        ":date_publication" => $date_publication,
        ":isbn" => $isbn,
        ":image" => $image
    ]);

    echo "Livre ajouté à ta bibliothèque.";
} else {
    echo "Méthode non autorisée.";
}
?>
