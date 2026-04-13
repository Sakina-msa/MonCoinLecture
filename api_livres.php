<?php
// ══════════════════════════════════════════════════
//  api_livres.php — Endpoints AJAX pour Mes Livres
//  Appelé par fetch() depuis mes-livres.php
// ══════════════════════════════════════════════════

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Pour tester sans login : user démo = 1 ─────────
// Quand tu auras la connexion, remplace par :
// if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Non connecté']); exit; }
// $uid = getUserId();
$uid = $_SESSION['user_id'] ?? 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ────────────────────────────────────────────────
    // Récupérer tous les livres (avec filtre optionnel)
    // ────────────────────────────────────────────────
    case 'get_livres':
        $statut = $_GET['statut'] ?? 'tous';
        $q      = trim($_GET['q'] ?? '');
        $db     = getDB();

        $sql    = "SELECT * FROM livres WHERE user_id = ?";
        $params = [$uid];

        if ($statut !== 'tous') {
            $sql    .= " AND statut = ?";
            $params[] = $statut;
        }
        if ($q !== '') {
            $sql    .= " AND (titre LIKE ? OR auteur LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    // ────────────────────────────────────────────────
    // Récupérer les stats (compteurs + note moyenne)
    // ────────────────────────────────────────────────
    case 'get_stats':
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT
                SUM(statut = 'lu')      AS lus,
                SUM(statut = 'encours') AS en_cours,
                SUM(statut = 'alire')   AS a_lire,
                ROUND(AVG(NULLIF(note, 0)), 1) AS note_moy
            FROM livres WHERE user_id = ?
        ");
        $stmt->execute([$uid]);
        echo json_encode($stmt->fetch());
        break;

    // ────────────────────────────────────────────────
    // Ajouter un livre
    // ────────────────────────────────────────────────
    case 'add_livre':
        $titre      = trim($_POST['titre']      ?? '');
        $auteur     = trim($_POST['auteur']     ?? '');
        $genre      = trim($_POST['genre']      ?? '');
        $statut     = $_POST['statut']          ?? 'lu';
        $note       = (int)($_POST['note']      ?? 0);
        $commentaire= trim($_POST['commentaire']?? '');
        $progression= isset($_POST['progression']) && $_POST['progression'] !== ''
                      ? (int)$_POST['progression'] : null;
        $cover_url  = trim($_POST['cover_url'] ?? '');
        $cover_url  = strlen($cover_url) > 0 && filter_var($cover_url, FILTER_VALIDATE_URL) ? $cover_url : null;

        // Validations
        if ($titre === '' || $auteur === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Titre et auteur obligatoires.']);
            break;
        }
        $statutsValides = ['lu', 'encours', 'alire'];
        if (!in_array($statut, $statutsValides)) $statut = 'lu';
        $note = max(0, min(5, $note));
        if ($progression !== null) $progression = max(0, min(100, $progression));

        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO livres (user_id, titre, auteur, genre, statut, note, commentaire, progression, cover_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$uid, $titre, $auteur, $genre, $statut, $note, $commentaire ?: null, $progression, $cover_url]);
        $newId = $db->lastInsertId();

        // Log activité
        logActivite($db, $uid, 'ajout', $newId, $titre);

        // Retourne le livre fraîchement inséré
        $stmt2 = $db->prepare("SELECT * FROM livres WHERE id = ?");
        $stmt2->execute([$newId]);
        echo json_encode(['ok' => true, 'livre' => $stmt2->fetch()]);
        break;

    // ────────────────────────────────────────────────
    // Supprimer un livre
    // ────────────────────────────────────────────────
    case 'delete_livre':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        $db = getDB();
        // Vérifie que le livre appartient bien à ce user
        $check = $db->prepare("SELECT titre FROM livres WHERE id = ? AND user_id = ?");
        $check->execute([$id, $uid]);
        $livre = $check->fetch();
        if (!$livre) { http_response_code(403); echo json_encode(['error' => 'Livre introuvable']); break; }

        $db->prepare("DELETE FROM livres WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
        logActivite($db, $uid, 'suppression', null, $livre['titre']);
        echo json_encode(['ok' => true]);
        break;

    // ────────────────────────────────────────────────
    // Changer le statut d'un livre
    // ────────────────────────────────────────────────
    case 'update_statut':
        $id     = (int)($_POST['id']     ?? 0);
        $statut = $_POST['statut']       ?? '';
        if (!$id || !in_array($statut, ['lu','encours','alire'])) {
            http_response_code(400); echo json_encode(['error' => 'Paramètres invalides']); break;
        }
        $db = getDB();
        $db->prepare("UPDATE livres SET statut = ? WHERE id = ? AND user_id = ?")
           ->execute([$statut, $id, $uid]);
        logActivite($db, $uid, 'statut', $id, $statut);
        echo json_encode(['ok' => true]);
        break;

    // ────────────────────────────────────────────────
    // Mettre à jour la progression
    // ────────────────────────────────────────────────
    case 'update_progression':
        $id  = (int)($_POST['id']  ?? 0);
        $pct = max(0, min(100, (int)($_POST['pct'] ?? 0)));
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }
        getDB()->prepare("UPDATE livres SET progression = ? WHERE id = ? AND user_id = ?")
               ->execute([$pct, $id, $uid]);
        echo json_encode(['ok' => true]);
        break;

    // ────────────────────────────────────────────────
    // Toggler favori
    // ────────────────────────────────────────────────
    case 'toggle_favori':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }
        $db   = getDB();
        $stmt = $db->prepare("SELECT favori, titre FROM livres WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $row  = $stmt->fetch();
        if (!$row) { http_response_code(403); echo json_encode(['error' => 'Introuvable']); break; }
        $nouveau = $row['favori'] ? 0 : 1;
        $db->prepare("UPDATE livres SET favori = ? WHERE id = ? AND user_id = ?")
           ->execute([$nouveau, $id, $uid]);
        if ($nouveau) logActivite($db, $uid, 'favori', $id, $row['titre']);
        echo json_encode(['ok' => true, 'favori' => (bool)$nouveau]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => "Action inconnue : $action"]);
}

// ── Helper : log activité ──────────────────────────
function logActivite(PDO $db, int $uid, string $action, ?int $livreId, ?string $detail): void {
    try {
        $db->prepare("INSERT INTO activite (user_id, action, livre_id, detail) VALUES (?,?,?,?)")
           ->execute([$uid, $action, $livreId, $detail]);
    } catch (Exception $e) {
        // Non bloquant
    }
}