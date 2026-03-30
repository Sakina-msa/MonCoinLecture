<?php
require_once "db.php";

$sql = "SELECT * FROM livres WHERE favori = 1";
$stmt = $pdo->query($sql);

$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($livres);
?>