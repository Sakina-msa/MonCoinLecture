<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = trim($_POST["titre"] ?? "");
    $auteur = trim($_POST["auteur"] ?? "");
    $genre = trim($_POST["genre"] ?? "");

    if ($titre === "" || $auteur === "" || $genre === "") {
        echo "Tous les champs sont obligatoires.";
        exit;
    }

    try {
        $sql = "INSERT INTO livres (titre, auteur, genre, statut, note)
                VALUES (:titre, :auteur, :genre, 'a lire', NULL)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":titre" => $titre,
            ":auteur" => $auteur,
            ":genre" => $genre
        ]);

        echo "Livre ajouté avec succès.";
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout : " . $e->getMessage();
    }
} else {
    echo "Méthode non autorisée.";
}
?>