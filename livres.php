<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];

$db = getDB();

// ── Stats depuis la BDD ───────────────────────────
$stmt = $db->prepare("
    SELECT
        SUM(statut = 'lu')      AS lus,
        SUM(statut = 'encours') AS en_cours,
        SUM(statut = 'alire')   AS a_lire,
        SUM(favori = 1)         AS favoris
    FROM livres WHERE user_id = ?
");
$stmt->execute([$uid]);
$stats = $stmt->fetch();

$lus      = (int)($stats['lus']      ?? 0);
$enCours  = (int)($stats['en_cours'] ?? 0);
$aLire    = (int)($stats['a_lire']   ?? 0);
$favoris  = (int)($stats['favoris']  ?? 0);
$total    = $lus + $enCours + $aLire;

// Barres de progression (% relatif au total)
$barLus     = $total ? min(100, round($lus     / $total * 100)) : 0;
$barFav     = $total ? min(100, round($favoris / $total * 100)) : 0;
$barALire   = $total ? min(100, round($aLire   / $total * 100)) : 0;
$barEnCours = $total ? min(100, round($enCours / $total * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bibliothèque — Mon Coin Lecture</title>
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
      --font-serif:'Playfair Display',Georgia,serif;
      --font-sans:'Instrument Sans',system-ui,sans-serif;
      --radius:12px;
      --shadow-sm:0 1px 4px rgba(26,20,16,0.06);
      --shadow-md:0 4px 20px rgba(26,20,16,0.09);
      --shadow-lg:0 12px 48px rgba(26,20,16,0.12);
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
    .page-wrap { max-width:1400px; margin:0 auto; padding:52px 48px 80px; }
    .page-hero { margin-bottom:52px; animation:fadeUp 0.5s ease both; }
    .page-eyebrow { font-size:0.72rem; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .page-eyebrow::before { content:''; width:24px; height:1px; background:var(--border-warm); }
    .page-title { font-family:var(--font-serif); font-size:clamp(2.2rem,4vw,3.2rem); font-weight:400; color:var(--ink); letter-spacing:-0.025em; line-height:1.1; margin-bottom:14px; }
    .page-title em { font-style:italic; color:var(--accent); }
    .page-subtitle { font-size:0.9rem; color:var(--muted); line-height:1.65; max-width:480px; }

    /* INTERCALAIRES */
    .tabs-wrapper { position:relative; display:grid; grid-template-columns:repeat(4,1fr); gap:0; align-items:end; margin-bottom:-1px; animation:fadeUp 0.5s 0.1s ease both; }
    .tab-trigger { position:relative; cursor:pointer; text-decoration:none; display:flex; flex-direction:column; align-items:flex-start; }
    .tab-trigger:nth-child(1){z-index:4} .tab-trigger:nth-child(2){z-index:3} .tab-trigger:nth-child(3){z-index:2} .tab-trigger:nth-child(4){z-index:1}
    .tab-ear { display:inline-flex; align-items:center; gap:7px; padding:8px 20px 10px; border-radius:10px 10px 0 0; font-size:0.72rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; margin-left:16px; position:relative; transition:padding-bottom 0.2s; white-space:nowrap; }
    .tab-body { width:100%; min-height:340px; border-radius:0 12px 0 0; padding:32px 30px 28px; position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; transition:min-height 0.25s ease; border-top:2px solid rgba(255,255,255,0.15); }
    .tab-trigger:first-child .tab-body{border-radius:12px 12px 0 0}
    .tab-trigger:last-child .tab-body{border-radius:0 12px 0 0}
    .tab-trigger:hover .tab-body{min-height:360px}
    .tab-trigger:hover .tab-ear{padding-bottom:14px}
    .tab-body::after { content:''; position:absolute; top:0; left:0; right:0; height:1px; background:rgba(255,255,255,0.25); }
    .tab-big-label { font-size:0.62rem; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; opacity:0.5; margin-bottom:10px; position:relative; z-index:1; }
    .tab-name { font-family:var(--font-serif); font-size:clamp(1.6rem,2.5vw,2.1rem); font-weight:400; line-height:1.15; letter-spacing:-0.02em; position:relative; z-index:1; margin-bottom:14px; }
    .tab-name em{font-style:italic}
    .tab-desc { font-size:0.78rem; line-height:1.6; opacity:0.65; max-width:220px; position:relative; z-index:1; }
    .tab-cta { display:inline-flex; align-items:center; gap:6px; font-size:0.72rem; font-weight:600; letter-spacing:0.04em; opacity:0.7; position:relative; z-index:1; margin-top:20px; transition:opacity 0.18s, gap 0.18s; }
    .tab-trigger:hover .tab-cta{opacity:1;gap:10px}
    .tab-cta svg{width:14px;height:14px}
    .tab-watermark { position:absolute; bottom:-20px; right:-10px; font-family:var(--font-serif); font-size:8rem; font-weight:700; line-height:1; opacity:0.06; pointer-events:none; user-select:none; z-index:0; }
    .tab-1 .tab-ear{background:#c4602a;color:#fff} .tab-1 .tab-body{background:linear-gradient(145deg,#c4602a 0%,#a03818 100%);color:#fff}
    .tab-2 .tab-ear{background:#b8933f;color:#fff} .tab-2 .tab-body{background:linear-gradient(145deg,#b8933f 0%,#8a6820 100%);color:#fff}
    .tab-3 .tab-ear{background:#3a6e50;color:#fff} .tab-3 .tab-body{background:linear-gradient(145deg,#3a6e50 0%,#1e4232 100%);color:#fff}
    .tab-4 .tab-ear{background:#1a1410;color:rgba(245,240,232,0.85)} .tab-4 .tab-body{background:linear-gradient(145deg,#2a2018 0%,#0e0c0a 100%);color:#f5f0e8}

    /* SOL */
    .tabs-floor { height:20px; background:var(--border-warm); border-radius:0 0 16px 16px; box-shadow:0 8px 24px rgba(26,20,16,0.1); animation:fadeUp 0.5s 0.1s ease both; margin-bottom:48px; }

    /* STATS */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; animation:fadeUp 0.5s 0.22s ease both; }
    .stat-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:20px 22px; box-shadow:var(--shadow-sm); text-align:center; transition:transform 0.2s, box-shadow 0.2s; text-decoration:none; display:block; }
    .stat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
    .stat-num { font-family:var(--font-serif); font-size:2rem; font-weight:700; line-height:1; margin-bottom:4px; }
    .stat-label { font-size:0.7rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }
    .stat-bar { height:3px; border-radius:99px; margin:10px auto 0; width:60%; overflow:hidden; }
    .stat-bar-fill { height:100%; border-radius:99px; transition:width 1s ease; }

    /* CITATION */
    .quote-band { margin-top:48px; padding:36px 48px; background:var(--ink); border-radius:16px; display:flex; align-items:center; gap:32px; animation:fadeUp 0.5s 0.3s ease both; }
    .quote-mark { font-family:var(--font-serif); font-size:5rem; color:var(--accent); line-height:0.7; flex-shrink:0; opacity:0.7; }
    .quote-text { font-family:var(--font-serif); font-size:clamp(1rem,1.8vw,1.3rem); font-style:italic; color:#f5f0e8; line-height:1.6; letter-spacing:-0.01em; }
    .quote-author { font-size:0.75rem; color:var(--muted-light); margin-top:10px; font-weight:500; letter-spacing:0.04em; }

    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    @media(max-width:1024px){.page-wrap{padding:32px 24px 60px}.site-header,.nav-inner{padding-inline:24px}.tabs-wrapper{grid-template-columns:1fr 1fr;gap:12px;margin-bottom:0}.tab-body{border-radius:0 12px 12px 12px!important;min-height:260px}.stats-row{grid-template-columns:1fr 1fr}.quote-band{flex-direction:column;gap:16px;padding:28px 24px}}
    @media(max-width:640px){.tabs-wrapper{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr 1fr}}
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
      <a href="livres.php" class="active">Bibliothèque</a>
      <div class="nav-dropdown-menu">
        <div class="mega-left">
          <div class="mega-badge">Explorer</div>
          <h3 class="mega-heading">Ma bibliothèque</h3>
          <p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture, garde tes favoris.</p>
        </div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Catalogue mondial</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<div class="page-wrap">

  <!-- TITRE -->
  <div class="page-hero">
    <div class="page-eyebrow">Ma collection</div>
    <h1 class="page-title">Ma <em>bibliothèque</em></h1>
    <p class="page-subtitle">Tous tes livres en un seul endroit — explore, retrouve et organise tes lectures avec soin.</p>
  </div>

  <!-- 4 INTERCALAIRES -->
  <div class="tabs-wrapper">

    <a href="mes-livres.php" class="tab-trigger tab-1">
      <div class="tab-ear">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Mes livres
      </div>
      <div class="tab-body">
        <div class="tab-watermark">1</div>
        <div>
          <div class="tab-big-label">Collection</div>
          <div class="tab-name">Ma<br><em>bibliothèque</em></div>
          <div class="tab-desc">Tous les livres lus, en cours ou à venir.</div>
        </div>
        <div class="tab-cta">Ouvrir <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
      </div>
    </a>

    <a href="favoris.php" class="tab-trigger tab-2">
      <div class="tab-ear">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        Favoris
      </div>
      <div class="tab-body">
        <div class="tab-watermark">2</div>
        <div>
          <div class="tab-big-label">Coups de cœur</div>
          <div class="tab-name">Mes<br><em>favoris</em></div>
          <div class="tab-desc">Les œuvres qui t'ont le plus marqué.</div>
        </div>
        <div class="tab-cta">Ouvrir <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
      </div>
    </a>

    <a href="decouvrir.php" class="tab-trigger tab-3">
      <div class="tab-ear">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        Découvrir
      </div>
      <div class="tab-body">
        <div class="tab-watermark">3</div>
        <div>
          <div class="tab-big-label">Catalogue mondial</div>
          <div class="tab-name">Découvrir<br><em>des livres</em></div>
          <div class="tab-desc">Explore des millions de livres par genre.</div>
        </div>
        <div class="tab-cta">Ouvrir <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
      </div>
    </a>

    <a href="recherche.php" class="tab-trigger tab-4">
      <div class="tab-ear">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        Recherche
      </div>
      <div class="tab-body">
        <div class="tab-watermark">4</div>
        <div>
          <div class="tab-big-label">Trouver</div>
          <div class="tab-name">Rechercher<br><em>un titre</em></div>
          <div class="tab-desc">Cherche par titre, auteur ou genre.</div>
        </div>
        <div class="tab-cta">Ouvrir <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
      </div>
    </a>

  </div>

  <div class="tabs-floor"></div>

  <!-- STATS dynamiques depuis la BDD -->
  <div class="stats-row">
    <a href="mes-livres.php?filter=lu" class="stat-card">
      <div class="stat-num" style="color:var(--accent)"><?= $lus ?></div>
      <div class="stat-label">Livres lus</div>
      <div class="stat-bar" style="background:var(--accent-light)">
        <div class="stat-bar-fill" style="width:<?= $barLus ?>%;background:var(--accent)"></div>
      </div>
    </a>
    <a href="favoris.php" class="stat-card">
      <div class="stat-num" style="color:var(--gold)"><?= $favoris ?></div>
      <div class="stat-label">Favoris</div>
      <div class="stat-bar" style="background:#faf3e0">
        <div class="stat-bar-fill" style="width:<?= $barFav ?>%;background:var(--gold)"></div>
      </div>
    </a>
    <a href="decouvrir.php" class="stat-card">
      <div class="stat-num" style="color:#3a6e50"><?= $aLire ?></div>
      <div class="stat-label">À lire</div>
      <div class="stat-bar" style="background:#eaf4ee">
        <div class="stat-bar-fill" style="width:<?= $barALire ?>%;background:#3a6e50"></div>
      </div>
    </a>
    <a href="mes-livres.php?filter=encours" class="stat-card">
      <div class="stat-num" style="color:var(--ink)"><?= $enCours ?></div>
      <div class="stat-label">En cours</div>
      <div class="stat-bar" style="background:var(--bg-warm)">
        <div class="stat-bar-fill" style="width:<?= $barEnCours ?>%;background:var(--ink)"></div>
      </div>
    </a>
  </div>

  <!-- CITATION -->
  <div class="quote-band">
    <div class="quote-mark">"</div>
    <div>
      <div class="quote-text">Un lecteur vit mille vies avant de mourir.<br>Celui qui ne lit jamais n'en vit qu'une.</div>
      <div class="quote-author">— George R.R. Martin</div>
    </div>
  </div>

</div>
</body>
</html>