<?php
// ══════════════════════════════════════════════════
//  mes-livres.php
//  Chargement initial des livres côté serveur (PHP),
//  puis toutes les interactions en AJAX via api_livres.php
// ══════════════════════════════════════════════════
require_once __DIR__ . '/config.php';

// Pour tester sans login : user démo = 1
// Quand tu auras la connexion, décommente la ligne suivante :
// if (!isLoggedIn()) { header('Location: connexion.php'); exit; }
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];

$db = getDB();

// ── Livres au chargement (tous) ──────────────────
$stmtLivres = $db->prepare("SELECT * FROM livres WHERE user_id = ? ORDER BY created_at DESC");
$stmtLivres->execute([$uid]);
$livres = $stmtLivres->fetchAll();

// ── Stats ─────────────────────────────────────────
$stmtStats = $db->prepare("
    SELECT
        SUM(statut = 'lu')      AS lus,
        SUM(statut = 'encours') AS en_cours,
        SUM(statut = 'alire')   AS a_lire,
        ROUND(AVG(NULLIF(note, 0)), 1) AS note_moy
    FROM livres WHERE user_id = ?
");
$stmtStats->execute([$uid]);
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mes Livres — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --ink:#1a1410;--ink-soft:#3d322a;--muted:#8a7b6e;--muted-light:#b5a89b;
      --bg:#faf7f2;--bg-warm:#f3ede3;--bg-card:#fffcf7;
      --border:#e8e0d4;--border-warm:#d4c8b8;
      --accent:#c4602a;--accent-light:#fdf0e8;--accent-deep:#9e3e14;
      --gold:#b8933f;--gold-light:#fdf5e0;
      --sage:#5a9e6a;--sage-light:#edf7ef;
      --teal:#3aada8;--teal-light:#e8f7f6;
      --lav:#8b6ec4;--lav-light:#f2edfb;
      --font-serif:'Playfair Display',Georgia,serif;
      --font-sans:'Instrument Sans',system-ui,sans-serif;
      --radius:12px;--radius-lg:18px;
      --shadow-sm:0 1px 4px rgba(26,20,16,0.06);
      --shadow-md:0 4px 20px rgba(26,20,16,0.09);
      --shadow-lg:0 12px 48px rgba(26,20,16,0.14);
    }
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased;min-height:100vh}

    /* ── HEADER ── */
    .site-header{position:sticky;top:0;z-index:100;background:rgba(250,247,242,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px}
    .logo{display:flex;align-items:baseline;gap:10px}
    .logo-mark{font-family:var(--font-serif);font-size:1.25rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}
    .logo-mark em{font-style:italic;color:var(--accent)}
    .logo-divider{width:1px;height:14px;background:var(--border-warm)}
    .logo-sub{font-size:0.75rem;color:var(--muted);letter-spacing:0.06em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}
    .header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:7px 18px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}
    .btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border-warm)}
    .btn-ghost:hover{background:var(--bg-warm)}
    .btn-accent{background:var(--ink);color:var(--bg)}
    .btn-accent:hover{background:var(--ink-soft)}

    /* ── NAV ── */
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}
    .nav-inner{max-width:1400px;margin:0 auto;padding:0 48px;display:flex;align-items:center;gap:4px;height:46px}
    .nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:6px;letter-spacing:0.03em;transition:all 0.15s}
    .nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}
    .nav-inner a.active{color:var(--accent);background:var(--accent-light)}
    .nav-dropdown{position:relative}
    .nav-dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;width:480px;flex-direction:row;z-index:200}
    .nav-dropdown:hover .nav-dropdown-menu{display:flex}
    .mega-left{flex:1;padding-right:20px;border-right:1px solid var(--border)}
    .mega-badge{display:inline-block;font-size:0.65rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--accent);background:var(--accent-light);padding:3px 10px;border-radius:99px;margin-bottom:10px}
    .mega-heading{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);margin-bottom:8px}
    .mega-text{font-size:0.78rem;color:var(--muted);line-height:1.55}
    .mega-links{display:grid;grid-template-columns:1fr 1fr;gap:6px;flex:1.2}
    .mega-card{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;transition:background 0.15s}
    .mega-card:hover{background:var(--bg-warm)}
    .mega-icon{width:32px;height:32px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}
    .mega-icon svg{width:15px;height:15px}
    .mega-card-title{display:block;font-size:0.8rem;font-weight:600;color:var(--ink)}
    .mega-card-desc{display:block;font-size:0.7rem;color:var(--muted)}

    /* ══ PAGE LAYOUT ══ */
    .page-wrap{max-width:1400px;margin:0 auto;padding:44px 48px 80px}
    .page-header{margin-bottom:36px;animation:fadeUp 0.5s ease both}
    .page-eyebrow{font-size:0.7rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:8px}
    .page-eyebrow::before{content:'';width:22px;height:1.5px;background:var(--border-warm)}
    .page-title{font-family:var(--font-serif);font-size:clamp(2rem,3.5vw,2.8rem);font-weight:700;color:var(--ink);letter-spacing:-0.025em;line-height:1.1;margin-bottom:8px}
    .page-title em{font-style:italic;font-weight:400;color:var(--accent)}
    .page-sub{font-size:0.88rem;color:var(--muted);line-height:1.6}

    /* ── STATS ── */
    .stats-band{display:flex;gap:16px;margin-bottom:36px;animation:fadeUp 0.5s 0.06s ease both}
    .stat-pill{display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1px solid var(--border);border-radius:99px;padding:10px 20px;box-shadow:var(--shadow-sm)}
    .stat-pill-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .stat-pill-num{font-family:var(--font-serif);font-size:1.1rem;font-weight:700;color:var(--ink);line-height:1}
    .stat-pill-label{font-size:0.7rem;font-weight:600;letter-spacing:0.05em;color:var(--muted)}

    /* ── LAYOUT ── */
    .main-layout{display:grid;grid-template-columns:380px 1fr;gap:28px;align-items:start;animation:fadeUp 0.5s 0.1s ease both}

    /* ── FORMULAIRE ── */
    .add-form-card{background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-md);position:sticky;top:120px}
    .form-header{background:var(--ink);padding:22px 26px;display:flex;align-items:center;gap:12px}
    .form-header-icon{width:38px;height:38px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .form-header-icon svg{width:18px;height:18px;color:#f5f0e8}
    .form-header-title{font-family:var(--font-serif);font-size:1.05rem;font-weight:500;color:#f5f0e8}
    .form-header-sub{font-size:0.7rem;color:rgba(245,240,232,0.5);margin-top:1px}
    .form-body{padding:24px 26px;display:flex;flex-direction:column;gap:16px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-label{font-size:0.72rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted)}
    .form-label span{color:var(--accent);margin-left:2px}
    .form-input,.form-textarea,.form-select{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;background:var(--bg);font-family:var(--font-sans);font-size:0.88rem;color:var(--ink);outline:none;transition:border-color 0.18s,box-shadow 0.18s,background 0.18s}
    .form-input:focus,.form-textarea:focus,.form-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(196,96,42,0.1);background:#fff}
    .form-input::placeholder,.form-textarea::placeholder{color:var(--muted-light)}
    .form-textarea{resize:vertical;min-height:80px;line-height:1.55}
    .form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238a7b6e' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px;cursor:pointer}
    .stars-input{display:flex;gap:4px;align-items:center}
    .star-btn{background:none;border:none;cursor:pointer;padding:3px;transition:transform 0.15s;color:var(--border-warm);font-size:1.4rem;line-height:1}
    .star-btn:hover,.star-btn.lit{color:var(--gold);transform:scale(1.15)}
    .stars-label{font-size:0.72rem;color:var(--muted-light);margin-left:6px}
    .status-pills{display:flex;gap:8px;flex-wrap:wrap}
    .status-pill{padding:7px 14px;border-radius:99px;border:1.5px solid var(--border);font-size:0.75rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all 0.15s;background:transparent;font-family:var(--font-sans)}
    .status-pill:hover{border-color:var(--border-warm);background:var(--bg-warm)}
    .status-pill.active-lu{background:var(--sage-light);border-color:#8dd49d;color:var(--sage)}
    .status-pill.active-encours{background:var(--accent-light);border-color:#f0b080;color:var(--accent)}
    .status-pill.active-alire{background:var(--lav-light);border-color:#c0a8e0;color:var(--lav)}
    .btn-add{width:100%;padding:14px;background:var(--accent);color:#fff;border:none;border-radius:10px;font-family:var(--font-sans);font-size:0.88rem;font-weight:600;cursor:pointer;letter-spacing:0.03em;transition:background 0.18s,transform 0.15s,box-shadow 0.18s;display:flex;align-items:center;justify-content:center;gap:8px}
    .btn-add:hover{background:var(--accent-deep);transform:translateY(-1px);box-shadow:0 4px 16px rgba(196,96,42,0.3)}
    .btn-add:active{transform:translateY(0)}
    .btn-add svg{width:16px;height:16px}
    .btn-add:disabled{opacity:0.6;cursor:not-allowed;transform:none}
    .btn-decouvrir{width:100%;padding:11px;background:transparent;color:var(--lav);border:1.5px solid var(--lav);border-radius:10px;font-family:var(--font-sans);font-size:0.84rem;font-weight:600;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;display:flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;margin-top:-4px}
    .btn-decouvrir:hover{background:var(--lav-light);transform:translateY(-1px)}
    .btn-decouvrir svg{width:14px;height:14px}

    /* ── LISTE ── */
    .books-panel{display:flex;flex-direction:column;gap:20px}
    .filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .filter-search{flex:1;min-width:180px;position:relative}
    .filter-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted-light);pointer-events:none}
    .filter-search-input{width:100%;height:40px;padding:0 14px 0 38px;border:1.5px solid var(--border);border-radius:99px;background:var(--bg-card);font-family:var(--font-sans);font-size:0.82rem;color:var(--ink);outline:none;transition:border-color 0.18s}
    .filter-search-input:focus{border-color:var(--accent)}
    .filter-search-input::placeholder{color:var(--muted-light)}
    .filter-pill{padding:8px 16px;border-radius:99px;border:1.5px solid var(--border);font-size:0.75rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all 0.15s;background:var(--bg-card);white-space:nowrap}
    .filter-pill:hover{border-color:var(--border-warm);color:var(--ink)}
    .filter-pill.on{background:var(--ink);border-color:var(--ink);color:#fff}

    /* ── GRILLE CARTES ── */
    .books-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
    .book-card{background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);cursor:pointer;transition:transform 0.22s cubic-bezier(0.34,1.56,0.64,1),box-shadow 0.22s;position:relative;animation:pop 0.3s ease both}
    .book-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
    .book-cover-area{height:150px;position:relative;overflow:hidden;display:flex;align-items:flex-end}
    .book-cover-art{position:absolute;inset:0}
    .book-cover-art::after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(0,0,0,0.5) 0%,transparent 60%)}
    .book-cover-area::before{content:'';position:absolute;left:0;top:0;bottom:0;width:12px;background:rgba(0,0,0,0.25);z-index:1}
    .book-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0}
    .book-cover-initials{position:absolute;bottom:10px;left:18px;font-family:var(--font-serif);font-size:1.4rem;font-weight:700;color:rgba(255,255,255,0.85);line-height:1;z-index:2;text-shadow:0 2px 8px rgba(0,0,0,0.3);letter-spacing:-0.02em}
    .book-badge-status{position:absolute;top:10px;right:10px;z-index:2;font-size:0.58rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:3px 8px;border-radius:99px}
    .badge-lu{background:var(--sage-light);color:var(--sage);border:1px solid #8dd49d}
    .badge-encours{background:var(--accent-light);color:var(--accent);border:1px solid #f0b080}
    .badge-alire{background:var(--lav-light);color:var(--lav);border:1px solid #c0a8e0}

    /* Bouton favori sur la carte */
    .book-fav-btn{position:absolute;top:8px;left:16px;z-index:3;background:none;border:none;cursor:pointer;font-size:1.1rem;line-height:1;transition:transform 0.2s;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.4))}
    .book-fav-btn:hover{transform:scale(1.25)}
    .book-fav-btn.is-fav{color:#e06090}
    .book-fav-btn:not(.is-fav){color:rgba(255,255,255,0.5)}

    .book-delete{position:absolute;top:42px;right:8px;z-index:3;width:26px;height:26px;border-radius:50%;background:rgba(26,20,16,0.7);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.18s}
    .book-card:hover .book-delete{opacity:1}
    .book-delete svg{width:12px;height:12px;color:#fff}
    .book-card-body{padding:14px 16px}
    .book-card-title{font-family:var(--font-serif);font-size:0.9rem;font-weight:500;color:var(--ink);line-height:1.3;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .book-card-author{font-size:0.7rem;color:var(--muted);margin-bottom:8px}
    .book-card-genre{display:inline-block;font-size:0.6rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;padding:2px 8px;border-radius:99px;background:var(--bg-warm);border:1px solid var(--border);color:var(--muted);margin-bottom:8px}
    .book-card-stars{display:flex;gap:2px;margin-bottom:6px}
    .star-sm{font-size:0.75rem;color:var(--border-warm)}
    .star-sm.lit{color:var(--gold)}
    .book-card-note{font-size:0.72rem;color:var(--muted);font-style:italic;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .book-card-progress{margin-top:6px}
    .mini-pbar{height:3px;background:var(--border);border-radius:99px;overflow:hidden}
    .mini-pbar-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--gold));border-radius:99px}
    .mini-pbar-label{font-size:0.62rem;color:var(--muted-light);margin-top:3px}

    /* ── EMPTY ── */
    .empty-state{grid-column:1/-1;text-align:center;padding:60px 32px;background:var(--bg-card);border:2px dashed var(--border);border-radius:var(--radius-lg)}
    .empty-icon{width:64px;height:64px;background:var(--bg-warm);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
    .empty-icon svg{width:28px;height:28px;color:var(--muted-light)}
    .empty-title{font-family:var(--font-serif);font-size:1.2rem;font-weight:500;color:var(--ink);margin-bottom:6px}
    .empty-sub{font-size:0.82rem;color:var(--muted);line-height:1.6}

    /* ── TOAST ── */
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:#f5f0e8;padding:12px 22px;border-radius:99px;font-size:0.82rem;font-weight:500;box-shadow:var(--shadow-lg);z-index:999;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s;opacity:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
    .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    .toast svg{width:14px;height:14px}

    /* ── COULEURS COUVERTURES ── */
    .cv-roman    {background:linear-gradient(145deg,#c84030,#8c1e20)}
    .cv-fantasy  {background:linear-gradient(145deg,#3870c0,#1a4080)}
    .cv-manga    {background:linear-gradient(145deg,#c8a020,#8a6808)}
    .cv-crime    {background:linear-gradient(145deg,#283880,#101840)}
    .cv-romance  {background:linear-gradient(145deg,#c83070,#880028)}
    .cv-scifi    {background:linear-gradient(145deg,#28a8a0,#086860)}
    .cv-classique{background:linear-gradient(145deg,#9060c0,#481880)}
    .cv-default  {background:linear-gradient(145deg,#8a7b6e,#4a3a2e)}

    /* ── AUTOCOMPLETE TITRE ── */
    .autocomplete-wrap{position:relative}
    .autocomplete-list{
      position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:200;
      background:var(--bg-card);border:1.5px solid var(--border);
      border-radius:12px;box-shadow:var(--shadow-lg);
      overflow:hidden;display:none;
    }
    .autocomplete-list.open{display:block;animation:fadeUp 0.15s ease both}
    .ac-item{
      display:flex;align-items:center;gap:12px;
      padding:10px 14px;cursor:pointer;
      transition:background 0.12s;border-bottom:1px solid var(--border);
    }
    .ac-item:last-child{border-bottom:none}
    .ac-item:hover,.ac-item.focused{background:var(--bg-warm)}
    .ac-cover{
      width:30px;height:42px;border-radius:2px 4px 4px 2px;
      flex-shrink:0;background:var(--bg-warm);
      display:flex;align-items:center;justify-content:center;
      font-family:var(--font-serif);font-size:0.7rem;font-weight:700;
      color:rgba(255,255,255,0.85);overflow:hidden;position:relative;
    }
    .ac-cover::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:rgba(0,0,0,0.2)}
    .ac-cover img{width:100%;height:100%;object-fit:cover}
    .ac-info{flex:1;min-width:0}
    .ac-title{font-family:var(--font-serif);font-size:0.82rem;font-weight:500;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ac-author{font-size:0.68rem;color:var(--muted);margin-top:1px}
    .ac-year{font-size:0.65rem;color:var(--muted-light);flex-shrink:0}
    .ac-spinner{padding:14px;text-align:center;font-size:0.78rem;color:var(--muted)}
    .ac-empty{padding:12px 14px;font-size:0.78rem;color:var(--muted);text-align:center}

    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes pop{0%{transform:scale(0.8);opacity:0}100%{transform:scale(1);opacity:1}}

    @media(max-width:1100px){.main-layout{grid-template-columns:1fr}.add-form-card{position:static}}
    @media(max-width:900px){.page-wrap{padding:32px 24px 60px}.site-header,.nav-inner{padding-inline:24px}.books-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}}
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
          <a href="mes-livres.php" class="mega-card" style="background:var(--bg-warm)"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<!-- PAGE -->
<div class="page-wrap">

  <!-- EN-TÊTE -->
  <div class="page-header">
    <div class="page-eyebrow">Ma collection</div>
    <h1 class="page-title">Mes <em>livres</em></h1>
    <p class="page-sub">Ajoute, note et organise tous tes livres en un seul endroit.</p>
  </div>

  <!-- STATS (rendues PHP au chargement, mises à jour en JS après) -->
  <div class="stats-band" id="statsBand">
    <div class="stat-pill">
      <div class="stat-pill-dot" style="background:var(--sage)"></div>
      <div class="stat-pill-num" id="statLu"><?= (int)($stats['lus'] ?? 0) ?></div>
      <div class="stat-pill-label">Lus</div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-dot" style="background:var(--accent)"></div>
      <div class="stat-pill-num" id="statEnCours"><?= (int)($stats['en_cours'] ?? 0) ?></div>
      <div class="stat-pill-label">En cours</div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-dot" style="background:var(--lav)"></div>
      <div class="stat-pill-num" id="statAlire"><?= (int)($stats['a_lire'] ?? 0) ?></div>
      <div class="stat-pill-label">À lire</div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-dot" style="background:var(--gold)"></div>
      <div class="stat-pill-num" id="statNote"><?= $stats['note_moy'] ? $stats['note_moy'] . ' ★' : '—' ?></div>
      <div class="stat-pill-label">Note moy.</div>
    </div>
  </div>

  <!-- LAYOUT -->
  <div class="main-layout">

    <!-- FORMULAIRE -->
    <div class="add-form-card">
      <div class="form-header">
        <div class="form-header-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
        </div>
        <div>
          <div class="form-header-title">Ajouter un livre</div>
          <div class="form-header-sub">Remplis les champs ci-dessous</div>
        </div>
      </div>
      <div class="form-body">

        <div class="form-group">
          <label class="form-label">Titre <span>*</span></label>
          <div class="autocomplete-wrap">
            <input class="form-input" id="inputTitre" type="text"
                   placeholder="Ex : Les Misérables, Dune…"
                   autocomplete="off"
                   oninput="acOnInput()"
                   onkeydown="acOnKey(event)"
                   onfocus="if(this.value.trim().length>=2) acOnInput()"/>
            <div class="autocomplete-list" id="acList"></div>
          </div>
        </div>

        <!-- Aperçu couverture (apparaît après sélection depuis l'autocomplétion) -->
        <div id="coverPreview" style="display:none;align-items:center;gap:12px;padding:10px 14px;background:var(--bg-warm);border-radius:10px;border:1.5px solid var(--border)">
          <img id="coverPreviewImg" src="" alt="Couverture" style="height:72px;width:48px;object-fit:cover;border-radius:3px 6px 6px 3px;box-shadow:2px 3px 10px rgba(0,0,0,0.2)"/>
          <div style="flex:1">
            <div style="font-size:0.7rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:3px">Couverture trouvée</div>
            <div style="font-size:0.75rem;color:var(--ink-soft)">Depuis Open Library</div>
          </div>
          <button onclick="selectedCoverUrl='';document.getElementById('coverPreview').style.display='none'" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:1.1rem" title="Retirer">×</button>
        </div>

        <div class="form-group">
          <label class="form-label">Auteur <span>*</span></label>
          <input class="form-input" id="inputAuteur" type="text" placeholder="Ex : Victor Hugo, Frank Herbert…"/>
        </div>

        <div class="form-group">
          <label class="form-label">Genre</label>
          <select class="form-select" id="inputGenre">
            <option value="">Sélectionner un genre…</option>
            <option value="roman">Roman</option>
            <option value="fantasy">Fantasy</option>
            <option value="manga">Manga</option>
            <option value="crime">Thriller / Crime</option>
            <option value="romance">Romance</option>
            <option value="scifi">Science-Fiction</option>
            <option value="classique">Classique</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Statut</label>
          <div class="status-pills" id="statusPills">
            <button class="status-pill active-lu" data-val="lu"      onclick="setStatus(this,'lu')">Lu</button>
            <button class="status-pill"           data-val="encours" onclick="setStatus(this,'encours')">En cours</button>
            <button class="status-pill"           data-val="alire"   onclick="setStatus(this,'alire')">À lire</button>
          </div>
        </div>

        <div class="form-group" id="progressGroup" style="display:none">
          <label class="form-label">Progression (%)</label>
          <input class="form-input" id="inputProgress" type="number" min="0" max="100" placeholder="Ex : 45"/>
        </div>

        <div class="form-group">
          <label class="form-label">Ma note</label>
          <div class="stars-input" id="starInput">
            <button class="star-btn" data-val="1" onclick="setStar(1)">★</button>
            <button class="star-btn" data-val="2" onclick="setStar(2)">★</button>
            <button class="star-btn" data-val="3" onclick="setStar(3)">★</button>
            <button class="star-btn" data-val="4" onclick="setStar(4)">★</button>
            <button class="star-btn" data-val="5" onclick="setStar(5)">★</button>
            <span class="stars-label" id="starLabel">Non noté</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Commentaire <span style="color:var(--muted);font-weight:400">(facultatif)</span></label>
          <textarea class="form-textarea" id="inputNote" placeholder="Ton avis, tes impressions…"></textarea>
        </div>

        <button class="btn-add" id="btnAdd" onclick="addBook()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
          Ajouter à ma bibliothèque
        </button>

        <a href="decouvrir.php" id="btnDecouvrir" class="btn-decouvrir" style="display:none">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          Découvrir ce livre
        </a>

      </div>
    </div>

    <!-- LISTE LIVRES -->
    <div class="books-panel">
      <div class="filter-bar">
        <div class="filter-search">
          <svg class="filter-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input class="filter-search-input" id="filterInput" type="text" placeholder="Filtrer mes livres…" oninput="filterBooks()"/>
        </div>
        <button class="filter-pill on" data-filter="tous"    onclick="setFilter(this,'tous')">Tous</button>
        <button class="filter-pill"   data-filter="lu"       onclick="setFilter(this,'lu')">Lus</button>
        <button class="filter-pill"   data-filter="encours"  onclick="setFilter(this,'encours')">En cours</button>
        <button class="filter-pill"   data-filter="alire"    onclick="setFilter(this,'alire')">À lire</button>
      </div>

      <div class="books-grid" id="booksGrid">
        <?php if (empty($livres)): ?>
          <div class="empty-state">
            <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
            <div class="empty-title">Ta bibliothèque est vide</div>
            <div class="empty-sub">Ajoute ton premier livre avec le formulaire.</div>
          </div>
        <?php else: ?>
          <?php foreach ($livres as $i => $livre):
            $initiales = implode('', array_map(fn($w) => strtoupper($w[0]), array_filter(explode(' ', $livre['titre']), fn($w) => strlen($w) > 2)));
            $initiales = $initiales ?: strtoupper(substr($livre['titre'], 0, 2));
            $initiales = substr($initiales, 0, 2);
            $genreClass = 'cv-' . ($livre['genre'] ?: 'default');
            $badgeClass = 'badge-' . $livre['statut'];
            $statutLabel = ['lu' => 'Lu', 'encours' => 'En cours', 'alire' => 'À lire'][$livre['statut']] ?? '';
            $genreLabel  = ['roman' => 'Roman', 'fantasy' => 'Fantasy', 'manga' => 'Manga', 'crime' => 'Crime', 'romance' => 'Romance', 'scifi' => 'Sci-Fi', 'classique' => 'Classique'][$livre['genre']] ?? '';
          ?>
          <div class="book-card" data-id="<?= $livre['id'] ?>" data-statut="<?= htmlspecialchars($livre['statut']) ?>" style="animation-delay:<?= $i * 0.04 ?>s">
            <div class="book-cover-area">
              <div class="book-cover-art <?= $genreClass ?>"></div>
              <?php if (!empty($livre['cover_url'])): ?>
              <img class="book-cover-img"
                   src="<?= htmlspecialchars($livre['cover_url']) ?>"
                   alt="<?= htmlspecialchars($livre['titre']) ?>"
                   loading="lazy"
                   onerror="this.style.display='none'"/>
              <?php endif ?>
              <div class="book-cover-initials"><?= htmlspecialchars($initiales) ?></div>
              <span class="book-badge-status <?= $badgeClass ?>"><?= $statutLabel ?></span>
            </div>
            <button class="book-fav-btn <?= $livre['favori'] ? 'is-fav' : '' ?>"
                    onclick="event.stopPropagation(); toggleFavori(this, <?= $livre['id'] ?>)"
                    title="<?= $livre['favori'] ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">♥</button>
            <button class="book-delete" onclick="event.stopPropagation(); deleteBook(<?= $livre['id'] ?>, this)" title="Supprimer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <div class="book-card-body">
              <div class="book-card-title"><?= htmlspecialchars($livre['titre']) ?></div>
              <div class="book-card-author"><?= htmlspecialchars($livre['auteur']) ?></div>
              <?php if ($genreLabel): ?><span class="book-card-genre"><?= $genreLabel ?></span><?php endif ?>
              <div class="book-card-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <span class="star-sm <?= $s <= $livre['note'] ? 'lit' : '' ?>">★</span>
                <?php endfor ?>
              </div>
              <?php if ($livre['commentaire']): ?>
                <div class="book-card-note"><?= htmlspecialchars($livre['commentaire']) ?></div>
              <?php endif ?>
              <?php if ($livre['statut'] === 'encours' && $livre['progression'] !== null): ?>
                <div class="book-card-progress">
                  <div class="mini-pbar"><div class="mini-pbar-fill" style="width:<?= (int)$livre['progression'] ?>%"></div></div>
                  <div class="mini-pbar-label"><?= (int)$livre['progression'] ?>% lu</div>
                </div>
              <?php endif ?>
            </div>
          </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toastMsg">Livre ajouté !</span>
</div>

<script>
// ── État local ────────────────────────────────────
let currentStatus = 'lu';
let currentStar   = 0;
let currentFilter = 'tous';

const genreColors  = {roman:'cv-roman',fantasy:'cv-fantasy',manga:'cv-manga',crime:'cv-crime',romance:'cv-romance',scifi:'cv-scifi',classique:'cv-classique','':`cv-default`};
const genreNames   = {roman:'Roman',fantasy:'Fantasy',manga:'Manga',crime:'Crime',romance:'Romance',scifi:'Sci-Fi',classique:'Classique'};
const statutLabels = {lu:'Lu',encours:'En cours',alire:'À lire'};

// ── Helpers UI ────────────────────────────────────
function setStatus(el, val) {
  document.querySelectorAll('.status-pill').forEach(p => p.className = 'status-pill');
  el.classList.add(`active-${val}`);
  currentStatus = val;
  document.getElementById('progressGroup').style.display = val === 'encours' ? 'flex' : 'none';
}

function setStar(n) {
  currentStar = n;
  document.querySelectorAll('.star-btn').forEach((b, i) => b.classList.toggle('lit', i < n));
  document.getElementById('starLabel').textContent = ['','★','★★','★★★','★★★★','★★★★★'][n] + ' / 5';
}

function setFilter(el, val) {
  document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('on'));
  el.classList.add('on');
  currentFilter = val;
  filterBooks();
}

function filterBooks() {
  const q = document.getElementById('filterInput').value.toLowerCase();
  const cards = document.querySelectorAll('.book-card[data-id]');
  let visible = 0;
  cards.forEach(card => {
    const titre  = card.querySelector('.book-card-title')?.textContent.toLowerCase() || '';
    const auteur = card.querySelector('.book-card-author')?.textContent.toLowerCase() || '';
    const statut = card.dataset.statut || '';
    const matchFilter = currentFilter === 'tous' || statut === currentFilter;
    const matchSearch  = !q || titre.includes(q) || auteur.includes(q);
    const show = matchFilter && matchSearch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  // Gère l'état vide
  const grid = document.getElementById('booksGrid');
  let empty = grid.querySelector('.empty-dyn');
  if (visible === 0 && cards.length > 0) {
    if (!empty) {
      empty = document.createElement('div');
      empty.className = 'empty-state empty-dyn';
      empty.style.gridColumn = '1/-1';
      empty.innerHTML = `<div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div><div class="empty-title">Aucun résultat</div><div class="empty-sub">Essaie un autre filtre ou une autre recherche.</div>`;
      grid.appendChild(empty);
    }
  } else if (empty) {
    empty.remove();
  }
}

// ── Initiales ─────────────────────────────────────
function initials(titre) {
  const parts = titre.split(' ').filter(w => w.length > 2).slice(0, 2).map(w => w[0].toUpperCase());
  return parts.join('') || titre.slice(0, 2).toUpperCase();
}

// ── Ajouter un livre (AJAX → api_livres.php) ──────
async function addBook() {
  const titre  = document.getElementById('inputTitre').value.trim();
  const auteur = document.getElementById('inputAuteur').value.trim();
  if (!titre || !auteur) { showToast('Titre et auteur obligatoires !', false); return; }

  const btn = document.getElementById('btnAdd');
  btn.disabled = true;
  btn.textContent = 'Ajout en cours…';

  const body = new FormData();
  body.append('action',      'add_livre');
  body.append('titre',       titre);
  body.append('auteur',      auteur);
  body.append('genre',       document.getElementById('inputGenre').value);
  body.append('statut',      currentStatus);
  body.append('note',        currentStar);
  body.append('commentaire', document.getElementById('inputNote').value.trim());
  body.append('cover_url',   selectedCoverUrl || '');
  if (currentStatus === 'encours') {
    body.append('progression', document.getElementById('inputProgress').value || 0);
  }

  try {
    const res  = await fetch('api_livres.php', { method: 'POST', body });
    const data = await res.json();
    if (data.error) { showToast(data.error, false); return; }

    // Ajoute la carte en haut de la grille
    prependCard(data.livre);
    resetForm();
    refreshStats();
    showToast('Livre ajouté à ta bibliothèque !', true);
  } catch(e) {
    showToast('Erreur réseau, réessaie.', false);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg> Ajouter à ma bibliothèque';
  }
}

// ── Créer et insérer une carte ─────────────────────
function prependCard(livre) {
  const grid = document.getElementById('booksGrid');
  // Enlève l'état vide initial si présent
  const empty = grid.querySelector('.empty-state');
  if (empty) empty.remove();

  const card = document.createElement('div');
  card.className = 'book-card';
  card.dataset.id     = livre.id;
  card.dataset.statut = livre.statut;

  const init     = initials(livre.titre);
  const cvClass  = genreColors[livre.genre] || 'cv-default';
  const badgeCls = `badge-${livre.statut}`;
  const gnLabel  = genreNames[livre.genre] || '';
  const stars    = Array.from({length:5}, (_,i) => `<span class="star-sm ${i < livre.note ? 'lit' : ''}">★</span>`).join('');
  const progHtml = livre.statut === 'encours' && livre.progression != null
    ? `<div class="book-card-progress"><div class="mini-pbar"><div class="mini-pbar-fill" style="width:${livre.progression}%"></div></div><div class="mini-pbar-label">${livre.progression}% lu</div></div>`
    : '';
  const coverImgHtml = livre.cover_url
    ? `<img class="book-cover-img" src="${escHtml(livre.cover_url)}" alt="${escHtml(livre.titre)}" loading="lazy" onerror="this.style.display='none'"/>`
    : '';

  card.innerHTML = `
    <div class="book-cover-area">
      <div class="book-cover-art ${cvClass}"></div>
      ${coverImgHtml}
      <div class="book-cover-initials">${init}</div>
      <span class="book-badge-status ${badgeCls}">${statutLabels[livre.statut]}</span>
    </div>
    <button class="book-fav-btn" onclick="event.stopPropagation(); toggleFavori(this, ${livre.id})" title="Ajouter aux favoris">♥</button>
    <button class="book-delete" onclick="event.stopPropagation(); deleteBook(${livre.id}, this)" title="Supprimer">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="book-card-body">
      <div class="book-card-title">${escHtml(livre.titre)}</div>
      <div class="book-card-author">${escHtml(livre.auteur)}</div>
      ${gnLabel ? `<span class="book-card-genre">${gnLabel}</span>` : ''}
      <div class="book-card-stars">${stars}</div>
      ${livre.commentaire ? `<div class="book-card-note">${escHtml(livre.commentaire)}</div>` : ''}
      ${progHtml}
    </div>`;

  grid.insertBefore(card, grid.firstChild);
}

// ── Supprimer un livre ─────────────────────────────
async function deleteBook(id, btn) {
  if (!confirm('Supprimer ce livre définitivement ?')) return;
  const card = btn.closest('.book-card');

  const body = new FormData();
  body.append('action', 'delete_livre');
  body.append('id', id);

  try {
    const res  = await fetch('api_livres.php', { method: 'POST', body });
    const data = await res.json();
    if (data.error) { showToast(data.error, false); return; }
    card.style.transition = 'opacity 0.3s, transform 0.3s';
    card.style.opacity    = '0';
    card.style.transform  = 'scale(0.85)';
    setTimeout(() => { card.remove(); refreshStats(); }, 310);
    showToast('Livre supprimé.', false);
  } catch(e) {
    showToast('Erreur réseau.', false);
  }
}

// ── Toggler favori ─────────────────────────────────
async function toggleFavori(btn, id) {
  const body = new FormData();
  body.append('action', 'toggle_favori');
  body.append('id', id);
  try {
    const res  = await fetch('api_livres.php', { method: 'POST', body });
    const data = await res.json();
    if (data.error) { showToast(data.error, false); return; }
    btn.classList.toggle('is-fav', data.favori);
    btn.title = data.favori ? 'Retirer des favoris' : 'Ajouter aux favoris';
    showToast(data.favori ? 'Ajouté aux favoris !' : 'Retiré des favoris.', data.favori);
  } catch(e) {
    showToast('Erreur réseau.', false);
  }
}

// ── Rafraîchir les stats ───────────────────────────
async function refreshStats() {
  try {
    const res  = await fetch('api_livres.php?action=get_stats');
    const data = await res.json();
    document.getElementById('statLu').textContent      = data.lus      ?? 0;
    document.getElementById('statEnCours').textContent = data.en_cours ?? 0;
    document.getElementById('statAlire').textContent   = data.a_lire   ?? 0;
    document.getElementById('statNote').textContent    = data.note_moy ? data.note_moy + ' ★' : '—';
  } catch(e) {}
}

// ── Reset formulaire ──────────────────────────────
function resetForm() {
  ['inputTitre','inputAuteur','inputNote','inputProgress'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('inputGenre').value = '';
  selectedCoverUrl = '';
  const preview = document.getElementById('coverPreview');
  if (preview) preview.style.display = 'none';
  const btnD2 = document.getElementById('btnDecouvrir');
  if (btnD2) btnD2.style.display = 'none';
  currentStar = 0;
  document.querySelectorAll('.star-btn').forEach(b => b.classList.remove('lit'));
  document.getElementById('starLabel').textContent = 'Non noté';
  setStatus(document.querySelector('[data-val="lu"]'), 'lu');
}

// ── Toast ─────────────────────────────────────────
function showToast(msg, ok = true) {
  const t    = document.getElementById('toast');
  const icon = t.querySelector('svg');
  document.getElementById('toastMsg').textContent = msg;
  icon.style.color = ok ? 'var(--sage)' : 'var(--accent)';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// ── AUTOCOMPLETE TITRE ───────────────────────────
let acTimer    = null;
let acResults  = [];
let acFocused  = -1;
let acOpen     = false;

function acOnInput() {
  const q = document.getElementById('inputTitre').value.trim();
  clearTimeout(acTimer);
  if (q.length < 2) { acClose(); return; }
  acTimer = setTimeout(() => acFetch(q), 280);
}

async function acFetch(q) {
  const list = document.getElementById('acList');
  list.innerHTML = '<div class="ac-spinner">Recherche…</div>';
  list.classList.add('open');
  acOpen = true;

  try {
    const url  = `https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=6&fields=title,author_name,first_publish_year,cover_i,subject`;
    const res  = await fetch(url);
    const data = await res.json();
    acResults  = (data.docs || []).filter(d => d.title && d.author_name);

    if (!acResults.length) {
      list.innerHTML = '<div class="ac-empty">Aucun résultat</div>';
      return;
    }
    acFocused = -1;
    acRender();
  } catch(e) {
    list.innerHTML = '<div class="ac-empty">Erreur réseau</div>';
  }
}

function acRender() {
  const list = document.getElementById('acList');
  list.innerHTML = acResults.map((doc, i) => {
    const titre  = doc.title || '';
    const auteur = (doc.author_name || [])[0] || '';
    const annee  = doc.first_publish_year || '';
    const coverId= doc.cover_i;

    // Couleur de fond par défaut si pas de couverture
    const defColor = genreColors[guessGenreAc(doc.subject)] || 'cv-default';

    return `<div class="ac-item ${i === acFocused ? 'focused' : ''}" onclick="acSelect(${i})">
      <div class="ac-cover ${coverId ? '' : defColor}">
        ${coverId
          ? `<img src="https://covers.openlibrary.org/b/id/${coverId}-S.jpg" alt="" loading="lazy"/>`
          : `<span style="writing-mode:vertical-rl;font-size:0.5rem;letter-spacing:0.05em;color:rgba(255,255,255,0.7);transform:rotate(180deg)">${escHtml(titre.slice(0,4))}</span>`
        }
      </div>
      <div class="ac-info">
        <div class="ac-title">${escHtml(titre)}</div>
        <div class="ac-author">${escHtml(auteur)}</div>
      </div>
      ${annee ? `<div class="ac-year">${annee}</div>` : ''}
    </div>`;
  }).join('');
}

// Stocke la cover_url sélectionnée
let selectedCoverUrl = '';

function acSelect(i) {
  const doc    = acResults[i];
  if (!doc) return;
  const titre  = doc.title || '';
  const auteur = (doc.author_name || [])[0] || '';
  const genre  = guessGenreAc(doc.subject);
  const coverId= doc.cover_i;
  selectedCoverUrl = coverId ? `https://covers.openlibrary.org/b/id/${coverId}-L.jpg` : '';

  document.getElementById('inputTitre').value  = titre;
  document.getElementById('inputAuteur').value = auteur;
  if (genre) document.getElementById('inputGenre').value = genre;

  // Bouton Découvrir ce livre
  const btnD = document.getElementById('btnDecouvrir');
  if (btnD && doc.key) {
    const params = new URLSearchParams({
      key: doc.key,
      titre,
      auteur,
      cover: doc.cover_i ? `https://covers.openlibrary.org/b/id/${doc.cover_i}-L.jpg` : '',
      cv: 'cv-roman',
      rating: doc.ratings_average || ''
    });
    btnD.href = 'livre-detail.php?' + params.toString();
    btnD.style.display = 'flex';
  }

  // Aperçu de la couverture dans le formulaire
  const preview = document.getElementById('coverPreview');
  const previewImg = document.getElementById('coverPreviewImg');
  if (selectedCoverUrl && preview && previewImg) {
    previewImg.src = selectedCoverUrl;
    preview.style.display = 'flex';
  } else if (preview) {
    preview.style.display = 'none';
  }

  acClose();
}

function acOnKey(e) {
  if (!acOpen) return;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    acFocused = Math.min(acFocused + 1, acResults.length - 1);
    acRender();
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    acFocused = Math.max(acFocused - 1, -1);
    acRender();
  } else if (e.key === 'Enter' && acFocused >= 0) {
    e.preventDefault();
    acSelect(acFocused);
  } else if (e.key === 'Escape') {
    acClose();
  }
}

function acClose() {
  const list = document.getElementById('acList');
  list.classList.remove('open');
  acOpen    = false;
  acFocused = -1;
}

// Fermer si clic en dehors
document.addEventListener('click', e => {
  if (!e.target.closest('.autocomplete-wrap')) acClose();
});

// Deviner le genre depuis les sujets Open Library
function guessGenreAc(subjects) {
  if (!subjects || !subjects.length) return '';
  const s = subjects.join(' ').toLowerCase();
  if (s.includes('manga') || s.includes('comic')) return 'manga';
  if (s.includes('fantasy') || s.includes('magic') || s.includes('dragon')) return 'fantasy';
  if (s.includes('science fiction') || s.includes('space') || s.includes('robot')) return 'scifi';
  if (s.includes('crime') || s.includes('detective') || s.includes('mystery') || s.includes('thriller')) return 'crime';
  if (s.includes('romance') || s.includes('love story')) return 'romance';
  if (s.includes('classic') || s.includes('19th century')) return 'classique';
  return 'roman';
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>