<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];

$db = getDB();

// ── Récupérer tous les favoris ────────────────────
$stmt = $db->prepare("
    SELECT * FROM livres
    WHERE user_id = ? AND favori = 1
    ORDER BY created_at DESC
");
$stmt->execute([$uid]);
$favoris = $stmt->fetchAll();

// ── Grouper par genre ─────────────────────────────
$etageres = [
    'classique' => ['label' => 'Classiques',       'livres' => []],
    'fantasy'   => ['label' => 'Fantasy',           'livres' => []],
    'scifi'     => ['label' => 'Sci-Fi',            'livres' => []],
    'crime'     => ['label' => 'Thriller & Crime',  'livres' => []],
    'roman'     => ['label' => 'Romans',            'livres' => []],
    'romance'   => ['label' => 'Romance',           'livres' => []],
    'manga'     => ['label' => 'Manga',             'livres' => []],
    ''          => ['label' => 'Autres',            'livres' => []],
];
foreach ($favoris as $livre) {
    $genre = $livre['genre'] ?? '';
    if (!isset($etageres[$genre])) $genre = '';
    $etageres[$genre]['livres'][] = $livre;
}
// Supprimer les étagères vides
$etageres = array_filter($etageres, fn($e) => count($e['livres']) > 0);

// ── Stats ─────────────────────────────────────────
$totalFavoris = count($favoris);
$genres       = count(array_unique(array_column($favoris, 'genre')));
$notedBooks   = array_filter($favoris, fn($b) => $b['note'] > 0);
$noteMoy      = $notedBooks ? round(array_sum(array_column(array_values($notedBooks), 'note')) / count($notedBooks), 1) : null;
$dernierAjout = $favoris ? date('Y', strtotime($favoris[0]['created_at'])) : '—';

// ── Couleur par genre ─────────────────────────────
$genreColors = [
    'roman'     => 'linear-gradient(160deg,#c84030,#8c1e20)',
    'fantasy'   => 'linear-gradient(160deg,#3870c0,#1a4080)',
    'manga'     => 'linear-gradient(160deg,#c8a020,#8a6808)',
    'crime'     => 'linear-gradient(160deg,#283880,#101840)',
    'romance'   => 'linear-gradient(160deg,#c83070,#880028)',
    'scifi'     => 'linear-gradient(160deg,#28a8a0,#086860)',
    'classique' => 'linear-gradient(160deg,#9060c0,#481880)',
    ''          => 'linear-gradient(160deg,#8a7b6e,#4a3a2e)',
];
$spineColors = [
    'roman'     => '#c84030', 'fantasy'   => '#3870c0', 'manga'  => '#c8a020',
    'crime'     => '#283880', 'romance'   => '#c83070', 'scifi'  => '#28a8a0',
    'classique' => '#9060c0', ''          => '#8a7b6e',
];

// ── Initiales depuis le titre ─────────────────────
function getInitiales(string $titre): string {
    $words = array_filter(explode(' ', $titre), fn($w) => strlen($w) > 2);
    $init  = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice($words, 0, 2)));
    return $init ?: strtoupper(substr($titre, 0, 2));
}

// ── Hauteur de dos proportionnelle au nombre de pages ─
function getSpineHeight(array $livre): int {
    // On simule une hauteur entre 110px et 175px
    $hash = crc32($livre['titre'] . $livre['auteur']);
    return 110 + abs($hash % 65);
}
function getSpineWidth(array $livre): int {
    $hash = crc32($livre['auteur'] . $livre['titre']);
    return 24 + abs($hash % 24);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mes Favoris — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --ink:#1a1410; --ink-soft:#3d322a; --muted:#8a7b6e; --muted-light:#b5a89b;
      --bg:#faf7f2; --bg-warm:#f3ede3; --bg-card:#fffcf7;
      --border:#e8e0d4; --border-warm:#d4c8b8;
      --accent:#c4602a; --accent-light:#fdf0e8; --accent-deep:#9e3e14;
      --gold:#b8933f;
      --love:#e05c7a; --love-light:#fff0f3;
      --font-serif:'Playfair Display',Georgia,serif;
      --font-sans:'Instrument Sans',system-ui,sans-serif;
      --radius:12px;
      --shadow-sm:0 1px 4px rgba(26,20,16,0.06);
      --shadow-md:0 4px 20px rgba(26,20,16,0.09);
      --shadow-lg:0 12px 48px rgba(26,20,16,0.12);
      --shelf-top:#c8a97a; --shelf-face:#a67c52;
    }
    html { font-size:15px; }
    body { background:var(--bg); color:var(--ink); font-family:var(--font-sans); -webkit-font-smoothing:antialiased; min-height:100vh; }

    /* HEADER */
    .site-header { position:sticky; top:0; z-index:100; background:rgba(250,247,242,0.88); backdrop-filter:blur(12px); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; padding:0 48px; height:64px; }
    .logo { display:flex; align-items:baseline; gap:10px; }
    .logo-mark { font-family:var(--font-serif); font-size:1.25rem; font-weight:500; color:var(--ink); letter-spacing:-0.01em; }
    .logo-mark em { font-style:italic; color:var(--accent); }
    .logo-divider { width:1px; height:14px; background:var(--border-warm); }
    .logo-sub { font-size:0.75rem; color:var(--muted); letter-spacing:0.06em; text-transform:uppercase; font-weight:500; }
    .header-actions { display:flex; align-items:center; gap:10px; }
    .header-btn { font-family:var(--font-sans); font-size:0.8rem; font-weight:500; padding:7px 18px; border-radius:99px; cursor:pointer; letter-spacing:0.02em; transition:all 0.18s; border:none; }
    .header-btn-ghost { background:transparent; color:var(--ink-soft); border:1px solid var(--border-warm); }
    .header-btn-ghost:hover { background:var(--bg-warm); }
    .header-btn-accent { background:var(--ink); color:var(--bg); }
    .header-btn-accent:hover { background:var(--ink-soft); }

    /* NAV */
    .site-nav { border-bottom:1px solid var(--border); background:var(--bg-card); }
    .nav-inner { max-width:1400px; margin:0 auto; padding:0 48px; display:flex; align-items:center; gap:4px; height:46px; }
    .nav-inner a { font-size:0.8rem; font-weight:500; color:var(--muted); text-decoration:none; padding:5px 14px; border-radius:6px; letter-spacing:0.03em; transition:all 0.15s; }
    .nav-inner a:hover { color:var(--ink); background:var(--bg-warm); }
    .nav-inner a.active { color:var(--accent); background:var(--accent-light); }
    .nav-dropdown { position:relative; }
    .nav-dropdown-menu { display:none; position:absolute; top:calc(100% + 8px); left:0; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-lg); padding:20px; width:480px; flex-direction:row; z-index:200; }
    .nav-dropdown:hover .nav-dropdown-menu { display:flex; }
    .mega-left { flex:1; padding-right:20px; border-right:1px solid var(--border); }
    .mega-badge { display:inline-block; font-size:0.65rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); background:var(--accent-light); padding:3px 10px; border-radius:99px; margin-bottom:10px; }
    .mega-heading { font-family:var(--font-serif); font-size:1rem; font-weight:500; color:var(--ink); margin-bottom:8px; }
    .mega-text { font-size:0.78rem; color:var(--muted); line-height:1.55; }
    .mega-links { display:grid; grid-template-columns:1fr 1fr; gap:6px; flex:1.2; }
    .mega-card { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; text-decoration:none; transition:background 0.15s; }
    .mega-card:hover { background:var(--bg-warm); }
    .mega-icon { width:32px; height:32px; border-radius:8px; background:var(--bg-warm); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--accent); }
    .mega-icon svg { width:15px; height:15px; }
    .mega-card-title { display:block; font-size:0.8rem; font-weight:600; color:var(--ink); }
    .mega-card-desc { display:block; font-size:0.7rem; color:var(--muted); }

    /* PAGE */
    .page-wrap { max-width:1400px; margin:0 auto; padding:44px 48px 80px; }
    .page-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:36px; animation:fadeUp 0.5s ease both; }
    .page-eyebrow { font-size:0.72rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted); margin-bottom:10px; }
    .page-title { font-family:var(--font-serif); font-size:clamp(2rem,3vw,2.6rem); font-weight:400; color:var(--ink); letter-spacing:-0.02em; line-height:1.15; }
    .page-title em { font-style:italic; color:var(--accent); }
    .page-subtitle { font-size:0.88rem; color:var(--muted); margin-top:8px; line-height:1.6; }
    .page-header-right { display:flex; align-items:center; gap:10px; padding-bottom:4px; }
    .sort-select { font-family:var(--font-sans); font-size:0.8rem; color:var(--ink-soft); border:1.5px solid var(--border); border-radius:99px; padding:7px 32px 7px 16px; background:var(--bg-card); cursor:pointer; outline:none; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%238a7b6e' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; }
    .view-toggle { display:flex; border:1.5px solid var(--border); border-radius:99px; overflow:hidden; background:var(--bg-card); }
    .view-btn { width:36px; height:34px; display:flex; align-items:center; justify-content:center; border:none; background:transparent; cursor:pointer; color:var(--muted); transition:all 0.15s; }
    .view-btn.active { background:var(--ink); color:#fff; }
    .view-btn svg { width:14px; height:14px; }

    /* STATS */
    .stats-row { display:flex; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:20px 32px; margin-bottom:36px; box-shadow:var(--shadow-sm); animation:fadeUp 0.5s 0.07s ease both; }
    .stat-item { flex:1; text-align:center; padding:0 20px; border-right:1px solid var(--border); }
    .stat-item:last-child { border-right:none; }
    .stat-num { font-family:var(--font-serif); font-size:1.8rem; font-weight:700; color:var(--ink); line-height:1; margin-bottom:4px; }
    .stat-label { font-size:0.7rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }

    /* FILTERS */
    .filter-row { display:flex; align-items:center; gap:8px; margin-bottom:32px; flex-wrap:wrap; animation:fadeUp 0.5s 0.1s ease both; }
    .filter-label { font-size:0.72rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); margin-right:4px; }
    .filter-pill { font-family:var(--font-sans); font-size:0.75rem; font-weight:500; padding:6px 16px; border-radius:99px; border:1.5px solid var(--border); background:var(--bg-card); color:var(--muted); cursor:pointer; transition:all 0.15s; }
    .filter-pill:hover { border-color:var(--border-warm); background:var(--bg-warm); color:var(--ink-soft); }
    .filter-pill.active { background:var(--accent-light); border-color:#e8b490; color:var(--accent); }

    /* SHELF */
    .shelf-section { margin-bottom:52px; animation:fadeUp 0.5s ease both; }
    .shelf-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
    .shelf-label { font-size:0.72rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; color:var(--muted); }
    .shelf-count { font-size:0.75rem; color:var(--muted-light); margin-left:10px; }
    .shelf-link { font-size:0.78rem; font-weight:600; color:var(--accent); text-decoration:none; display:flex; align-items:center; gap:4px; transition:opacity 0.15s; }
    .shelf-link:hover { opacity:0.7; }
    .shelf-container { position:relative; border-radius:10px; overflow:hidden; padding:20px 0 0; background:linear-gradient(180deg,#f0e8dc,#e8ddd0); box-shadow:var(--shadow-md); border:1px solid var(--border-warm); }
    .shelf-container::before { content:''; position:absolute; inset:0; background-image:repeating-linear-gradient(0deg,transparent,transparent 24px,rgba(180,160,130,0.06) 24px,rgba(180,160,130,0.06) 25px),repeating-linear-gradient(90deg,transparent,transparent 24px,rgba(180,160,130,0.04) 24px,rgba(180,160,130,0.04) 25px); pointer-events:none; z-index:0; }
    .books-row { display:flex; align-items:flex-end; padding:0 24px; min-height:190px; position:relative; z-index:1; flex-wrap:wrap; gap:0; }
    .shelf-plank { height:22px; background:linear-gradient(180deg,var(--shelf-top) 0%,var(--shelf-top) 55%,var(--shelf-face) 55%); box-shadow:0 6px 20px rgba(100,60,20,0.25),inset 0 1px 0 rgba(255,255,255,0.35); position:relative; z-index:2; }
    .shelf-plank::before { content:''; position:absolute; left:0; right:0; top:0; height:3px; background:rgba(255,255,255,0.25); }

    /* BOOK ITEM */
    .book-item { display:flex; flex-direction:column; align-items:center; cursor:default; position:relative; padding:0 3px; transition:transform 0.28s cubic-bezier(0.34,1.56,0.64,1); transform-origin:bottom center; }
    .book-item:hover { transform:translateY(-18px); z-index:10; }
    .book-item:hover .book-tooltip { opacity:1; transform:translateX(-50%) translateY(0); pointer-events:auto; }
    .book-item:hover .book-remove-btn { opacity:1; transform:scale(1); }

    /* BOUTON RETIRER */
    .book-remove-btn { position:absolute; top:-8px; right:-4px; width:24px; height:24px; background:var(--love); border:2.5px solid var(--bg); border-radius:50%; display:flex; align-items:center; justify-content:center; opacity:0; transform:scale(0.5); transition:opacity 0.2s, transform 0.25s cubic-bezier(0.34,1.56,0.64,1); z-index:20; cursor:pointer; box-shadow:0 2px 10px rgba(224,92,122,0.45); }
    .book-remove-btn:hover { background:#c0304e; transform:scale(1.2) !important; }
    .book-remove-btn svg { width:10px; height:10px; color:#fff; }

    /* BOOK SPINE */
    .book-spine { border-radius:2px 4px 4px 2px; position:relative; box-shadow:-2px 0 4px rgba(0,0,0,0.15),2px 0 4px rgba(0,0,0,0.08),inset -3px 0 8px rgba(0,0,0,0.12),inset 2px 0 4px rgba(255,255,255,0.18); overflow:hidden; flex-shrink:0; }
    .book-spine::before { content:''; position:absolute; left:0; top:0; bottom:0; width:5px; background:rgba(0,0,0,0.18); }
    .book-spine-title { writing-mode:vertical-rl; text-orientation:mixed; transform:rotate(180deg); position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:600; letter-spacing:0.06em; padding:8px 4px; text-align:center; line-height:1.2; color:rgba(255,255,255,0.85); }

    /* TOOLTIP */
    .book-tooltip { position:absolute; bottom:calc(100% + 18px); left:50%; transform:translateX(-50%) translateY(8px); background:var(--ink); border-radius:12px; padding:14px 16px; min-width:170px; max-width:210px; pointer-events:none; opacity:0; transition:all 0.22s ease; z-index:30; box-shadow:var(--shadow-lg); }
    .book-tooltip::after { content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:7px solid transparent; border-top-color:var(--ink); }
    .tt-title { font-family:var(--font-serif); font-size:0.88rem; font-weight:500; color:#f5f0e8; margin-bottom:3px; line-height:1.3; }
    .tt-author { font-size:0.72rem; color:var(--muted-light); margin-bottom:8px; }
    .tt-genre { display:inline-block; font-size:0.62rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; padding:3px 9px; border-radius:99px; background:rgba(196,96,42,0.25); color:#e8a070; }
    .tt-stars { display:flex; gap:3px; margin-top:8px; font-size:0.75rem; color:var(--gold); }
    .tt-hint { font-size:0.64rem; color:rgba(224,92,122,0.75); margin-top:9px; display:flex; align-items:center; gap:4px; line-height:1.3; }
    .tt-hint svg { width:9px; height:9px; flex-shrink:0; }

    /* ADD SLOT → redirige vers mes-livres */
    .book-add { display:flex; flex-direction:column; align-items:center; justify-content:flex-end; padding:0 4px; cursor:pointer; text-decoration:none; }
    .book-add-spine { border-radius:2px 4px 4px 2px; border:2px dashed var(--border-warm); background:rgba(244,237,228,0.6); display:flex; align-items:center; justify-content:center; transition:all 0.2s; width:28px; height:120px; }
    .book-add-spine:hover { border-color:var(--accent); background:var(--accent-light); }
    .book-add-spine svg { width:16px; height:16px; color:var(--muted-light); transition:color 0.2s; }
    .book-add-spine:hover svg { color:var(--accent); }
    .book-add-label { font-size:0.6rem; color:var(--muted-light); margin-top:5px; text-align:center; }

    /* ANIMATION CHUTE */
    @keyframes bookFall { 0%{transform:translateY(-18px) rotate(0deg);opacity:1} 35%{transform:translateY(-18px) rotate(-10deg);opacity:1} 100%{transform:translateY(70px) rotate(-18deg) scaleY(0.2);opacity:0} }
    .book-item.removing { animation:bookFall 0.42s cubic-bezier(0.55,0,1,0.45) forwards; pointer-events:none; }

    /* EMPTY STATE */
    .empty-shelf { display:none; text-align:center; padding:40px 20px; color:var(--muted); }
    .empty-shelf.show { display:block; }
    .empty-icon { width:48px; height:48px; border-radius:50%; background:var(--bg-warm); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:var(--muted-light); }
    .empty-icon svg { width:22px; height:22px; }
    .empty-title { font-family:var(--font-serif); font-size:1rem; font-weight:500; color:var(--ink-soft); margin-bottom:5px; }
    .empty-text { font-size:0.8rem; line-height:1.6; }

    /* EMPTY PAGE */
    .empty-page { text-align:center; padding:80px 32px; }
    .empty-page-icon { width:80px; height:80px; border-radius:50%; background:var(--bg-warm); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; color:var(--muted-light); }
    .empty-page-icon svg { width:36px; height:36px; }
    .empty-page-title { font-family:var(--font-serif); font-size:1.6rem; font-weight:400; color:var(--ink); margin-bottom:10px; }
    .empty-page-sub { font-size:0.88rem; color:var(--muted); line-height:1.65; margin-bottom:24px; }
    .btn-goto { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:var(--accent); color:#fff; text-decoration:none; border-radius:99px; font-family:var(--font-sans); font-size:0.85rem; font-weight:600; transition:background 0.18s; }
    .btn-goto:hover { background:var(--accent-deep); }

    /* MODAL */
    .modal-overlay { position:fixed; inset:0; background:rgba(26,20,16,0.55); backdrop-filter:blur(4px); z-index:500; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity 0.2s; }
    .modal-overlay.open { opacity:1; pointer-events:auto; }
    .modal-card { background:var(--bg-card); border:1px solid var(--border); border-radius:18px; padding:32px 36px; max-width:380px; width:90%; box-shadow:var(--shadow-lg); transform:scale(0.9) translateY(16px); transition:transform 0.32s cubic-bezier(0.34,1.56,0.64,1); text-align:center; }
    .modal-overlay.open .modal-card { transform:scale(1) translateY(0); }
    .modal-icon { width:52px; height:52px; background:var(--love-light); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; color:var(--love); }
    .modal-icon svg { width:22px; height:22px; }
    .modal-title { font-family:var(--font-serif); font-size:1.2rem; font-weight:500; color:var(--ink); margin-bottom:8px; }
    .modal-text { font-size:0.82rem; color:var(--muted); line-height:1.65; margin-bottom:26px; }
    .modal-book-name { font-weight:600; color:var(--ink-soft); font-style:italic; }
    .modal-actions { display:flex; gap:10px; justify-content:center; }
    .modal-btn { font-family:var(--font-sans); font-size:0.82rem; font-weight:600; padding:10px 26px; border-radius:99px; cursor:pointer; letter-spacing:0.02em; transition:all 0.18s; border:none; }
    .modal-btn-cancel { background:var(--bg-warm); color:var(--ink-soft); border:1.5px solid var(--border); }
    .modal-btn-cancel:hover { background:var(--border); }
    .modal-btn-confirm { background:var(--love); color:#fff; }
    .modal-btn-confirm:hover { background:#c0304e; }

    /* TOAST */
    .toast { position:fixed; bottom:32px; left:50%; transform:translateX(-50%) translateY(90px); background:var(--ink); color:#f5f0e8; padding:13px 22px; border-radius:99px; font-size:0.82rem; font-weight:500; display:flex; align-items:center; gap:12px; box-shadow:var(--shadow-lg); z-index:999; transition:transform 0.38s cubic-bezier(0.34,1.56,0.64,1); white-space:nowrap; }
    .toast.show { transform:translateX(-50%) translateY(0); }
    .toast-dot { width:7px; height:7px; border-radius:50%; background:var(--love); flex-shrink:0; }
    .toast-undo { font-size:0.78rem; font-weight:700; color:var(--gold); cursor:pointer; background:none; border:none; font-family:var(--font-sans); letter-spacing:0.04em; text-transform:uppercase; padding:0; }
    .toast-undo:hover { text-decoration:underline; }
    .toast-sep { width:1px; height:14px; background:rgba(255,255,255,0.15); }

    /* LIST VIEW */
    .list-view { display:none; }
    .list-view.active { display:flex; flex-direction:column; }
    .list-item { display:flex; align-items:center; gap:20px; padding:18px 24px; background:var(--bg-card); border:1px solid var(--border); border-bottom:none; transition:background 0.15s; }
    .list-item:first-child { border-radius:var(--radius) var(--radius) 0 0; }
    .list-item:last-child { border-bottom:1px solid var(--border); border-radius:0 0 var(--radius) var(--radius); }
    .list-item:only-child { border-radius:var(--radius); border-bottom:1px solid var(--border); }
    .list-item:hover { background:var(--bg-warm); }
    .list-cover { width:44px; height:60px; border-radius:3px 6px 6px 3px; flex-shrink:0; box-shadow:-2px 0 4px rgba(0,0,0,0.12),2px 2px 8px rgba(0,0,0,0.1); }
    .list-info { flex:1; min-width:0; }
    .list-title { font-family:var(--font-serif); font-size:1rem; font-weight:500; color:var(--ink); margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .list-author { font-size:0.78rem; color:var(--muted); margin-bottom:8px; }
    .list-tags { display:flex; gap:6px; }
    .list-tag { font-size:0.65rem; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; padding:3px 9px; border-radius:99px; border:1px solid var(--border); color:var(--muted); background:var(--bg); }
    .list-meta { display:flex; flex-direction:column; align-items:flex-end; gap:6px; }
    .list-stars { display:flex; gap:2px; font-size:0.75rem; color:var(--gold); }
    .list-date { font-size:0.7rem; color:var(--muted-light); }
    .unfav-btn { width:34px; height:34px; border-radius:50%; border:1.5px solid var(--border); background:transparent; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--love); transition:all 0.18s; flex-shrink:0; }
    .unfav-btn:hover { border-color:var(--love); background:var(--love-light); transform:scale(1.1); }
    .unfav-btn svg { width:14px; height:14px; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    @media(max-width:1024px){.page-wrap{padding:28px 24px 60px} .site-header,.nav-inner{padding-inline:24px}}
    @media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start;gap:16px}}
  </style>
</head>
<body>

<!-- HEADER -->
<?php include __DIR__ . '/header_nav.php'; ?>


<!-- NAV -->
<nav class="site-nav">
  <div class="nav-inner">
    <a href="index.php">Accueil</a>
    <div class="nav-dropdown">
      <a href="livres.php">Bibliothèque</a>
      <div class="nav-dropdown-menu">
        <div class="mega-left"><div class="mega-badge">Explorer</div><h3 class="mega-heading">Ma bibliothèque</h3><p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture, garde tes favoris.</p></div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card" style="background:var(--bg-warm)"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="favoris.php" class="active">Favoris</a>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-card">
    <div class="modal-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        <line x1="18" y1="6" x2="6" y2="18" stroke-width="2"/>
      </svg>
    </div>
    <div class="modal-title">Retirer des favoris ?</div>
    <p class="modal-text">Tu vas retirer <span class="modal-book-name" id="modalBookName"></span> de tes favoris. Tu pourras le rajouter depuis ta bibliothèque.</p>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="modal-btn modal-btn-confirm" id="modalConfirm">Oui, retirer</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
  <div class="toast-dot"></div>
  <span id="toastMsg"></span>
  <div class="toast-sep"></div>
  <button class="toast-undo" id="toastUndo">Annuler</button>
</div>

<!-- MAIN -->
<main class="page-wrap">

  <div class="page-header">
    <div>
      <div class="page-eyebrow">Ma collection</div>
      <h1 class="page-title">Mes livres <em>favoris</em></h1>
      <p class="page-subtitle">Les œuvres qui t'ont marqué, rangées avec soin sur tes étagères.</p>
    </div>
    <div class="page-header-right">
      <div class="view-toggle">
        <button class="view-btn active" id="btnShelf" onclick="setView('shelf')" title="Étagère">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="4" rx="1"/><rect x="2" y="10" width="20" height="4" rx="1"/><rect x="2" y="17" width="20" height="4" rx="1"/></svg>
        </button>
        <button class="view-btn" id="btnList" onclick="setView('list')" title="Liste">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-item"><div class="stat-num" id="statTotal"><?= $totalFavoris ?></div><div class="stat-label">Favoris</div></div>
    <div class="stat-item"><div class="stat-num"><?= $genres ?></div><div class="stat-label">Genres</div></div>
    <div class="stat-item"><div class="stat-num"><?= $noteMoy ?? '—' ?></div><div class="stat-label">Note moy.</div></div>
    <div class="stat-item"><div class="stat-num"><?= $dernierAjout ?></div><div class="stat-label">Dernier ajout</div></div>
  </div>

  <!-- FILTRES -->
  <?php if (!empty($etageres)): ?>
  <div class="filter-row">
    <span class="filter-label">Genre :</span>
    <button class="filter-pill active" onclick="filterGenre('all',this)">Tous</button>
    <?php foreach ($etageres as $genre => $data): ?>
      <button class="filter-pill" onclick="filterGenre('<?= htmlspecialchars($genre) ?>',this)"><?= htmlspecialchars($data['label']) ?></button>
    <?php endforeach ?>
  </div>
  <?php endif ?>

  <?php if (empty($favoris)): ?>
  <!-- ── PAGE VIDE ── -->
  <div class="empty-page">
    <div class="empty-page-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </div>
    <div class="empty-page-title">Pas encore de favoris</div>
    <p class="empty-page-sub">Clique sur le cœur ♥ d'un livre dans ta bibliothèque<br>pour l'ajouter ici.</p>
    <a href="mes-livres.php" class="btn-goto">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Aller à ma bibliothèque
    </a>
  </div>

  <?php else: ?>

  <!-- ══ VUE ÉTAGÈRES ══ -->
  <div id="shelfView">
    <?php foreach ($etageres as $genre => $data): ?>
    <div class="shelf-section" data-genre-section="<?= htmlspecialchars($genre) ?>">
      <div class="shelf-header">
        <div>
          <span class="shelf-label"><?= htmlspecialchars($data['label']) ?></span>
          <span class="shelf-count"> · <?= count($data['livres']) ?> livre<?= count($data['livres']) > 1 ? 's' : '' ?></span>
        </div>
        <a href="mes-livres.php" class="shelf-link">
          Ajouter un livre
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        </a>
      </div>
      <div class="shelf-container">
        <div class="books-row">
          <?php foreach ($data['livres'] as $livre):
            $h     = getSpineHeight($livre);
            $w     = getSpineWidth($livre);
            $color = $spineColors[$livre['genre']] ?? $spineColors[''];
            $stars = str_repeat('★', (int)$livre['note']) . str_repeat('☆', 5 - (int)$livre['note']);
            $genreLabels = ['roman'=>'Roman','fantasy'=>'Fantasy','manga'=>'Manga','crime'=>'Crime','romance'=>'Romance','scifi'=>'Sci-Fi','classique'=>'Classique'];
            $genreLabel  = $genreLabels[$livre['genre']] ?? 'Autre';
          ?>
          <div class="book-item"
               data-id="<?= $livre['id'] ?>"
               data-genre="<?= htmlspecialchars($livre['genre']) ?>"
               data-title="<?= htmlspecialchars($livre['titre']) ?>">

            <!-- Tooltip -->
            <div class="book-tooltip">
              <div class="tt-title"><?= htmlspecialchars($livre['titre']) ?></div>
              <div class="tt-author"><?= htmlspecialchars($livre['auteur']) ?></div>
              <span class="tt-genre"><?= $genreLabel ?></span>
              <?php if ($livre['note'] > 0): ?>
              <div class="tt-stars"><?= $stars ?></div>
              <?php endif ?>
              <div class="tt-hint">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/><line x1="18" y1="6" x2="6" y2="18" stroke-width="2"/></svg>
                Le bouton rouge retire le livre
              </div>
            </div>

            <!-- Bouton retirer -->
            <button class="book-remove-btn" onclick="openModal(this.closest('.book-item'))">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <!-- Dos du livre -->
            <div class="book-spine" style="width:<?= $w ?>px;height:<?= $h ?>px;background:<?= $color ?>">
              <span class="book-spine-title"><?= htmlspecialchars($livre['titre']) ?></span>
            </div>
          </div>
          <?php endforeach ?>

          <!-- Slot + pour ajouter → redirige vers mes-livres -->
          <a href="mes-livres.php" class="book-add" title="Ajouter un livre à tes favoris">
            <div class="book-add-spine">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </a>
        </div>
        <div class="shelf-plank"></div>
      </div>
    </div>
    <?php endforeach ?>
  </div>

  <!-- ══ VUE LISTE ══ -->
  <div id="listView" class="list-view">
    <?php foreach ($favoris as $livre):
      $color = $spineColors[$livre['genre']] ?? $spineColors[''];
      $stars = str_repeat('★', (int)$livre['note']) . str_repeat('☆', 5 - (int)$livre['note']);
      $genreLabels = ['roman'=>'Roman','fantasy'=>'Fantasy','manga'=>'Manga','crime'=>'Crime','romance'=>'Romance','scifi'=>'Sci-Fi','classique'=>'Classique'];
      $genreLabel  = $genreLabels[$livre['genre']] ?? 'Autre';
      $dateLabel   = date('d/m/Y', strtotime($livre['created_at']));
    ?>
    <div class="list-item" data-id="<?= $livre['id'] ?>" data-genre="<?= htmlspecialchars($livre['genre']) ?>">
      <div class="list-cover" style="background:<?= $color ?>"></div>
      <div class="list-info">
        <div class="list-title"><?= htmlspecialchars($livre['titre']) ?></div>
        <div class="list-author"><?= htmlspecialchars($livre['auteur']) ?></div>
        <div class="list-tags">
          <?php if ($genreLabel): ?><span class="list-tag"><?= $genreLabel ?></span><?php endif ?>
        </div>
      </div>
      <div class="list-meta">
        <?php if ($livre['note'] > 0): ?>
        <div class="list-stars"><?= $stars ?></div>
        <?php endif ?>
        <div class="list-date">Ajouté le <?= $dateLabel ?></div>
      </div>
      <button class="unfav-btn" onclick="retirerFavoriListe(this, <?= $livre['id'] ?>, '<?= htmlspecialchars($livre['titre'], ENT_QUOTES) ?>')" title="Retirer des favoris">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </button>
    </div>
    <?php endforeach ?>
  </div>

  <?php endif ?>

</main>

<script>
// ── État ──────────────────────────────────────────
let currentBookItem = null;
let toastTimeout    = null;
let undoData        = null;

// ── MODAL ─────────────────────────────────────────
function openModal(bookItem) {
  currentBookItem = bookItem;
  document.getElementById('modalBookName').textContent = '"' + bookItem.dataset.title + '"';
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  currentBookItem = null;
}
document.getElementById('modalConfirm').onclick = () => {
  if (!currentBookItem) return;
  const item = currentBookItem;
  closeModal();
  doRetirer(item);
};
document.getElementById('modalOverlay').onclick = e => { if (e.target.id === 'modalOverlay') closeModal(); };
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── RETIRER (étagère) ─────────────────────────────
async function doRetirer(bookItem) {
  const id    = bookItem.dataset.id;
  const title = bookItem.dataset.title;
  const parent = bookItem.parentNode;
  const before = bookItem.nextSibling;

  // Animation chute
  bookItem.classList.add('removing');

  // Appel API
  const body = new FormData();
  body.append('action', 'toggle_favori');
  body.append('id', id);
  try {
    await fetch('api_livres.php', { method: 'POST', body });
  } catch(e) {}

  setTimeout(() => {
    undoData = { id, title, html: bookItem.outerHTML, parent, before };
    bookItem.remove();
    updateStatTotal(-1);
    showToast(`« ${title} » retiré des favoris`);
  }, 430);
}

// ── RETIRER (vue liste) ───────────────────────────
async function retirerFavoriListe(btn, id, title) {
  const li = btn.closest('.list-item');
  const body = new FormData();
  body.append('action', 'toggle_favori');
  body.append('id', id);
  try { await fetch('api_livres.php', { method: 'POST', body }); } catch(e) {}
  li.style.transition = 'opacity 0.3s, transform 0.3s';
  li.style.opacity    = '0';
  li.style.transform  = 'translateX(20px)';
  setTimeout(() => { li.remove(); updateStatTotal(-1); }, 310);
  showToast(`« ${title} » retiré des favoris`);
}

// ── UNDO ──────────────────────────────────────────
document.getElementById('toastUndo').onclick = async () => {
  if (!undoData) return;
  const { id, html, parent, before } = undoData;

  // Remettre en favori en BDD
  const body = new FormData();
  body.append('action', 'toggle_favori');
  body.append('id', id);
  try { await fetch('api_livres.php', { method: 'POST', body }); } catch(e) {}

  // Remettre le livre sur l'étagère
  const temp = document.createElement('div');
  temp.innerHTML = html;
  const restored = temp.firstElementChild;
  restored.classList.remove('removing');
  restored.style.animation = 'none';
  const addSlot = parent.querySelector('.book-add');
  if (before && before.parentNode === parent) {
    parent.insertBefore(restored, before);
  } else if (addSlot) {
    parent.insertBefore(restored, addSlot);
  } else {
    parent.appendChild(restored);
  }

  undoData = null;
  document.getElementById('toast').classList.remove('show');
  updateStatTotal(+1);
};

// ── STATS ─────────────────────────────────────────
function updateStatTotal(delta) {
  const el  = document.getElementById('statTotal');
  const cur = parseInt(el.textContent) || 0;
  el.textContent = Math.max(0, cur + delta);
}

// ── TOAST ─────────────────────────────────────────
function showToast(msg) {
  const toast = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  toast.classList.add('show');
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => { toast.classList.remove('show'); undoData = null; }, 5000);
}

// ── FILTRE PAR GENRE ──────────────────────────────
function filterGenre(genre, el) {
  document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  // Étagères
  document.querySelectorAll('.shelf-section').forEach(s => {
    s.style.display = (genre === 'all' || s.dataset.genreSection === genre) ? '' : 'none';
  });
  // Liste
  document.querySelectorAll('.list-item').forEach(i => {
    i.style.display = (genre === 'all' || i.dataset.genre === genre) ? '' : 'none';
  });
}

// ── VUE ───────────────────────────────────────────
function setView(mode) {
  const shelf = document.getElementById('shelfView');
  const list  = document.getElementById('listView');
  if (!shelf || !list) return;
  if (mode === 'shelf') {
    shelf.style.display = '';
    list.classList.remove('active');
    document.getElementById('btnShelf').classList.add('active');
    document.getElementById('btnList').classList.remove('active');
  } else {
    shelf.style.display = 'none';
    list.classList.add('active');
    document.getElementById('btnList').classList.add('active');
    document.getElementById('btnShelf').classList.remove('active');
  }
}
</script>
</body>
</html>