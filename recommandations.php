<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recommandations — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&family=Caveat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --ink:#1a1410;--ink-soft:#3d322a;--muted:#8a7b6e;--muted-light:#b5a89b;
      --bg:#faf7f2;--bg-warm:#f3ede3;--bg-card:#fffcf7;
      --border:#e8e0d4;--border-warm:#d4c8b8;
      --accent:#c4602a;--accent-light:#fdf0e8;--accent-deep:#9e3e14;
      --gold:#b8933f;--sage:#5a9e6a;--lav:#8b6ec4;--teal:#3aada8;--blush:#e06090;
      --font-serif:'Playfair Display',Georgia,serif;
      --font-sans:'Instrument Sans',system-ui,sans-serif;
      --font-hand:'Caveat',cursive;
      --radius:12px;--radius-lg:18px;
      --shadow-sm:0 1px 4px rgba(26,20,16,0.06);
      --shadow-md:0 4px 20px rgba(26,20,16,0.09);
      --shadow-lg:0 12px 48px rgba(26,20,16,0.14);
      --shadow-card:0 8px 32px rgba(26,20,16,0.15),0 2px 8px rgba(26,20,16,0.08);
    }
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased;min-height:100vh}

    /* HEADER */
    .site-header{position:sticky;top:0;z-index:100;background:rgba(250,247,242,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px}
    .logo{display:flex;align-items:baseline;gap:10px}.logo-mark{font-family:var(--font-serif);font-size:1.25rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}.logo-mark em{font-style:italic;color:var(--accent)}.logo-divider{width:1px;height:14px;background:var(--border-warm)}.logo-sub{font-size:0.75rem;color:var(--muted);letter-spacing:0.06em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}.header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:7px 18px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}.btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border-warm)}.btn-ghost:hover{background:var(--bg-warm)}.btn-accent{background:var(--ink);color:var(--bg)}.btn-accent:hover{background:var(--ink-soft)}

    /* NAV */
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}.nav-inner{max-width:1400px;margin:0 auto;padding:0 48px;display:flex;align-items:center;gap:4px;height:46px}.nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:6px;letter-spacing:0.03em;transition:all 0.15s}.nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}.nav-inner a.active{color:var(--accent);background:var(--accent-light)}
    .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;width:480px;flex-direction:row;z-index:200}.nav-dropdown:hover .nav-dropdown-menu{display:flex}
    .mega-left{flex:1;padding-right:20px;border-right:1px solid var(--border)}.mega-badge{display:inline-block;font-size:0.65rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--accent);background:var(--accent-light);padding:3px 10px;border-radius:99px;margin-bottom:10px}.mega-heading{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);margin-bottom:8px}.mega-text{font-size:0.78rem;color:var(--muted);line-height:1.55}.mega-links{display:grid;grid-template-columns:1fr 1fr;gap:6px;flex:1.2}.mega-card{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;transition:background 0.15s}.mega-card:hover{background:var(--bg-warm)}.mega-icon{width:32px;height:32px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}.mega-icon svg{width:15px;height:15px}.mega-card-title{display:block;font-size:0.8rem;font-weight:600;color:var(--ink)}.mega-card-desc{display:block;font-size:0.7rem;color:var(--muted)}

    /* PAGE */
    .page-wrap{max-width:1400px;margin:0 auto;padding:0 48px 80px}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes floatPin{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}

    /* HERO */
    .reco-hero{background:var(--ink);border-radius:0 0 28px 28px;padding:52px 60px 44px;margin-bottom:56px;position:relative;overflow:hidden;animation:fadeUp 0.5s ease both}
    .reco-hero::before{content:'"';position:absolute;font-family:var(--font-serif);font-size:28rem;line-height:1;color:rgba(255,255,255,0.022);top:-60px;right:-10px;pointer-events:none}
    .reco-hero::after{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 80% 50%,rgba(196,96,42,0.14),transparent 55%);pointer-events:none}
    .reco-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr auto;align-items:center;gap:40px}
    .reco-eyebrow{font-size:0.7rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:rgba(245,240,232,0.4);margin-bottom:12px}
    .reco-title{font-family:var(--font-serif);font-size:clamp(2rem,4vw,3rem);font-weight:700;color:#f5f0e8;letter-spacing:-0.025em;line-height:1.1;margin-bottom:8px}
    .reco-title em{font-style:italic;font-weight:400;color:var(--accent)}
    .reco-sub{font-size:0.86rem;color:rgba(245,240,232,0.5);line-height:1.65;max-width:460px}
    .reco-stats{display:flex;gap:28px;flex-shrink:0}
    .reco-stat-num{font-family:var(--font-serif);font-size:2rem;font-weight:700;color:#f5f0e8;line-height:1;margin-bottom:3px}
    .reco-stat-label{font-size:0.62rem;font-weight:600;letter-spacing:0.09em;text-transform:uppercase;color:rgba(181,168,155,0.55)}

    /* HUMEUR */
    .mood-section{margin-bottom:52px;animation:fadeUp 0.4s 0.1s ease both}
    .mood-section-head{margin-bottom:20px}
    .section-eyebrow{font-size:0.7rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;display:flex;align-items:center;gap:8px}
    .section-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--border-warm)}
    .section-title{font-family:var(--font-serif);font-size:1.6rem;font-weight:500;color:var(--ink);letter-spacing:-0.02em}
    .section-title em{font-style:italic;color:var(--accent)}
    .mood-row{display:flex;gap:10px;flex-wrap:wrap}
    .mood-chip{padding:10px 20px;border-radius:99px;border:2px solid var(--border);background:var(--bg-card);font-family:var(--font-sans);font-size:0.8rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:7px}
    .mood-chip:hover{border-color:var(--border-warm);color:var(--ink);background:var(--bg-warm);transform:translateY(-2px)}
    .mood-chip.on{background:var(--ink);border-color:var(--ink);color:#fff;box-shadow:var(--shadow-md)}
    .mood-chip-icon{width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center}
    .mood-chip-icon svg{width:12px;height:12px}
    .mood-chip.on .mood-chip-icon{background:rgba(255,255,255,0.15)}

    /* MOOD BOARD */
    .board-section{margin-bottom:60px;animation:fadeUp 0.5s 0.15s ease both}
    .mood-board{background:#f5efe4;border-radius:24px;padding:52px 44px 48px;position:relative;overflow:hidden;min-height:620px;background-image:radial-gradient(circle at 1px 1px,rgba(196,150,80,0.06) 1px,transparent 0),linear-gradient(135deg,#f5efe4,#ede5d5);background-size:20px 20px,100% 100%;border:1.5px solid var(--border-warm);box-shadow:inset 0 2px 12px rgba(80,40,10,0.06),var(--shadow-md)}
    .board-threads{position:absolute;inset:0;pointer-events:none;z-index:0}
    .board-cards{position:relative;z-index:1;min-height:500px}

    /* POLAROÏD */
    .pcard{position:absolute;width:170px;cursor:pointer;transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1),box-shadow 0.35s,z-index 0s;z-index:2}
    .pcard:hover{z-index:20;transform:var(--pc-rotate) translateY(-14px) scale(1.06) !important}
    .pcard:hover .pcard-inner{box-shadow:0 20px 60px rgba(26,20,16,0.28)}
    .pcard-inner{background:#fff;border-radius:4px;padding:8px 8px 32px;box-shadow:var(--shadow-card);position:relative}
    .pcard-img{width:100%;aspect-ratio:2/2.8;border-radius:2px;overflow:hidden;position:relative}
    .pcard-cover{position:absolute;inset:0;display:flex;align-items:center;justify-content:center}
    .pcard-cover::before{content:'';position:absolute;left:0;top:0;bottom:0;width:12px;background:rgba(0,0,0,0.22)}
    .pcard-cover::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0.02) 0%,rgba(0,0,0,0.25) 100%)}
    .pcard-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0}
    .pcard-init{font-family:var(--font-serif);font-size:2rem;font-weight:700;color:rgba(255,255,255,0.88);text-shadow:0 2px 8px rgba(0,0,0,0.4);position:relative;z-index:1;letter-spacing:-0.03em}
    .pcard-label{position:absolute;bottom:6px;left:0;right:0;text-align:center;font-family:var(--font-hand);font-size:0.82rem;color:var(--ink-soft);line-height:1.2;padding:0 6px}
    .pcard-pin{position:absolute;top:-16px;left:50%;transform:translateX(-50%);width:18px;height:18px;border-radius:50%;z-index:3;box-shadow:0 2px 6px rgba(0,0,0,0.35);animation:floatPin 3s ease-in-out infinite}
    .pcard-tape{position:absolute;width:48px;height:18px;border-radius:2px;opacity:0.7;z-index:3}
    .tape-top{top:-9px;left:50%;transform:translateX(-50%) rotate(-2deg)}
    .tape-tl{top:-9px;left:10px;transform:rotate(-8deg)}
    .tape-tr{top:-9px;right:10px;transform:rotate(6deg)}
    .pcard-clip{position:absolute;z-index:4}
    .pcard-clip svg{width:28px;height:28px;filter:drop-shadow(1px 2px 3px rgba(0,0,0,0.25))}
    .board-note{position:absolute;background:#fef9d4;border-radius:2px;padding:10px 14px 12px;box-shadow:2px 3px 12px rgba(0,0,0,0.12);font-family:var(--font-hand);font-size:1rem;color:#4a3a1a;line-height:1.4;z-index:2;transform:rotate(2deg);max-width:140px}

    /* Tooltip adaptatif — en dessous (défaut) */
    .pcard-tooltip{position:absolute;top:calc(100% + 10px);left:50%;transform:translateX(-50%) translateY(6px);background:var(--ink);color:#f5f0e8;border-radius:10px;padding:12px 14px;width:210px;box-shadow:var(--shadow-lg);opacity:0;pointer-events:none;transition:opacity 0.2s,transform 0.2s;z-index:50}
    .pcard:hover .pcard-tooltip{opacity:1;transform:translateX(-50%) translateY(0);pointer-events:auto}
    .pcard-tooltip::before{content:'';position:absolute;bottom:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-bottom-color:var(--ink)}
    /* Tooltip en haut (pour les cartes du bas) */
    .pcard.tip-up .pcard-tooltip{top:auto;bottom:calc(100% + 10px);transform:translateX(-50%) translateY(-6px)}
    .pcard.tip-up:hover .pcard-tooltip{transform:translateX(-50%) translateY(0)}
    .pcard.tip-up .pcard-tooltip::before{display:none}
    .pcard.tip-up .pcard-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:var(--ink)}
    .tt-title{font-family:var(--font-serif);font-size:0.88rem;font-weight:500;color:#f5f0e8;margin-bottom:3px}
    .tt-author{font-size:0.7rem;color:var(--muted-light);margin-bottom:6px}
    .tt-desc{font-size:0.72rem;color:rgba(245,240,232,0.6);line-height:1.5;margin-bottom:8px}
    .tt-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;background:var(--accent);color:#fff;border-radius:99px;font-size:0.68rem;font-weight:600;cursor:pointer;border:none;font-family:var(--font-sans);transition:background 0.15s}
    .tt-btn:hover{background:var(--accent-deep)}
    .tt-btn.added{background:var(--sage);cursor:default}
    .tt-detail{display:inline-flex;align-items:center;gap:4px;font-size:0.65rem;color:rgba(245,240,232,0.45);text-decoration:none;margin-top:5px;transition:color 0.15s}
    .tt-detail:hover{color:rgba(245,240,232,0.8)}
    .tt-detail svg{width:10px;height:10px}

    /* GENRES */
    .genres-section{margin-bottom:52px;animation:fadeUp 0.5s 0.2s ease both}
    .genre-row{display:grid;grid-template-columns:repeat(7,1fr);gap:12px}
    .genre-tile{border-radius:16px;padding:20px 14px 18px;cursor:pointer;transition:transform 0.22s,box-shadow 0.22s;text-decoration:none;display:flex;flex-direction:column;gap:10px;position:relative;overflow:hidden}
    .genre-tile:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
    .genre-tile::before{content:'';position:absolute;bottom:-10px;right:-10px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.08);pointer-events:none}
    .genre-tile-icon{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center}
    .genre-tile-icon svg{width:18px;height:18px;color:#fff}
    .genre-tile-name{font-family:var(--font-serif);font-size:0.95rem;font-weight:500;color:#fff}
    .genre-tile-count{font-size:0.66rem;color:rgba(255,255,255,0.55);font-weight:500}
    .gt-roman{background:linear-gradient(145deg,#c84030,#8c1e20)}.gt-fantasy{background:linear-gradient(145deg,#3870c0,#1a4080)}.gt-manga{background:linear-gradient(145deg,#c8a020,#8a6808)}.gt-crime{background:linear-gradient(145deg,#283880,#101840)}.gt-romance{background:linear-gradient(145deg,#c83070,#880028)}.gt-scifi{background:linear-gradient(145deg,#28a8a0,#086860)}.gt-classique{background:linear-gradient(145deg,#9060c0,#481880)}

    /* TOAST */
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:#f5f0e8;padding:12px 22px;border-radius:99px;font-size:0.82rem;font-weight:500;box-shadow:var(--shadow-lg);z-index:999;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s;opacity:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
    .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    .toast svg{width:14px;height:14px;color:var(--sage)}

    /* Couleurs couvertures */
    .cv-roman{background:linear-gradient(155deg,#c84030,#8c1e20)}.cv-fantasy{background:linear-gradient(155deg,#3870c0,#1a4080)}.cv-manga{background:linear-gradient(155deg,#c8a020,#8a6808)}.cv-crime{background:linear-gradient(155deg,#283880,#101840)}.cv-romance{background:linear-gradient(155deg,#c83070,#880028)}.cv-scifi{background:linear-gradient(155deg,#28a8a0,#086860)}.cv-classique{background:linear-gradient(155deg,#9060c0,#481880)}

    @media(max-width:1100px){.genre-row{grid-template-columns:repeat(4,1fr)}.reco-hero-inner{grid-template-columns:1fr}}
    @media(max-width:768px){.page-wrap{padding:0 24px 60px}.site-header,.nav-inner{padding-inline:24px}.reco-hero{padding:40px 28px}.genre-row{grid-template-columns:repeat(3,1fr)}.mood-board{padding:32px 20px}.pcard{width:140px}}
  </style>
</head>
<body>

<?php include __DIR__ . '/header_nav.php'; ?>


<nav class="site-nav">
  <div class="nav-inner">
    <a href="index.php">Accueil</a>
    <div class="nav-dropdown">
      <a href="livres.php">Bibliothèque</a>
      <div class="nav-dropdown-menu">
        <div class="mega-left"><div class="mega-badge">Explorer</div><h3 class="mega-heading">Ma bibliothèque</h3><p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture.</p></div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="recommandations.php" class="active">Recommandations</a>
  </div>
</nav>

<div class="page-wrap">

  <!-- HERO -->
  <div class="reco-hero">
    <div class="reco-hero-inner">
      <div>
        <div class="reco-eyebrow">Suggestions personnalisées</div>
        <h1 class="reco-title">Tes prochaines <em>grandes lectures</em></h1>
        <p class="reco-sub">Des suggestions adaptées à ton humeur du moment. Survole les livres pour en savoir plus.</p>
      </div>
      <div class="reco-stats">
        <div><div class="reco-stat-num">6</div><div class="reco-stat-label">Humeurs</div></div>
        <div><div class="reco-stat-num" style="font-size:1rem;line-height:1.3" id="heroDate">—</div><div class="reco-stat-label">Sélection du jour</div></div>
      </div>
    </div>
  </div>

  <!-- HUMEUR -->
  <div class="mood-section">
    <div class="mood-section-head">
      <div class="section-eyebrow">Personnalisé</div>
      <div class="section-title">Selon ton <em>humeur</em></div>
    </div>
    <div class="mood-row">
      <button class="mood-chip on" data-m="calme" onclick="setMood(this,'calme')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></span>Calme</button>
      <button class="mood-chip" data-m="aventure" onclick="setMood(this,'aventure')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>Aventure</button>
      <button class="mood-chip" data-m="emotion" onclick="setMood(this,'emotion')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></span>Émotion</button>
      <button class="mood-chip" data-m="legerete" onclick="setMood(this,'legerete')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 3 4 3 4-3 4-3"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></span>Légèreté</button>
      <button class="mood-chip" data-m="reflexion" onclick="setMood(this,'reflexion')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/><circle cx="12" cy="12" r="10"/></svg></span>Réfléchi</button>
      <button class="mood-chip" data-m="frisson" onclick="setMood(this,'frisson')"><span class="mood-chip-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg></span>Frisson</button>
    </div>
  </div>

  <!-- MOOD BOARD -->
  <div class="board-section">
    <div class="mood-board" id="moodBoard">
      <svg class="board-threads" id="boardThreads" viewBox="0 0 900 620" preserveAspectRatio="none"></svg>
      <div class="board-cards" id="boardCards"></div>
    </div>
  </div>

  <!-- GENRES -->
  <div class="genres-section">
    <div style="margin-bottom:20px">
      <div class="section-eyebrow">Explorer</div>
      <div class="section-title">Par <em>genre</em></div>
    </div>
    <div class="genre-row">
      <a href="recherche.php?q=Roman"     class="genre-tile gt-roman"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div><div class="genre-tile-name">Roman</div><div class="genre-tile-count">12 suggestions</div></a>
      <a href="recherche.php?q=Fantasy"   class="genre-tile gt-fantasy"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div><div class="genre-tile-name">Fantasy</div><div class="genre-tile-count">8 suggestions</div></a>
      <a href="recherche.php?q=Manga"     class="genre-tile gt-manga"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg></div><div class="genre-tile-name">Manga</div><div class="genre-tile-count">6 suggestions</div></a>
      <a href="recherche.php?q=Crime"     class="genre-tile gt-crime"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="genre-tile-name">Crime</div><div class="genre-tile-count">5 suggestions</div></a>
      <a href="recherche.php?q=Romance"   class="genre-tile gt-romance"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="genre-tile-name">Romance</div><div class="genre-tile-count">4 suggestions</div></a>
      <a href="recherche.php?q=Sci-Fi"    class="genre-tile gt-scifi"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg></div><div class="genre-tile-name">Sci-Fi</div><div class="genre-tile-count">7 suggestions</div></a>
      <a href="recherche.php?q=Classiques" class="genre-tile gt-classique"><div class="genre-tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div><div class="genre-tile-name">Classiques</div><div class="genre-tile-count">9 suggestions</div></a>
    </div>
  </div>

</div>

<div class="toast" id="toast">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
const moodData = {
  calme:[
    {titre:"Le Vieil Homme et la Mer",auteur:"Ernest Hemingway",genre:"roman",desc:"Contemplatif, sur la solitude et la mer."},
    {titre:"L'Elegance du herisson",auteur:"Muriel Barbery",genre:"roman",desc:"Doux et philosophique."},
    {titre:"Lettres a un jeune poete",auteur:"Rainer Maria Rilke",genre:"classique",desc:"Des lettres lumineuses sur la creation."},
    {titre:"Les Heures",auteur:"Michael Cunningham",genre:"roman",desc:"Trois femmes, trois epoques, une melodie."},
    {titre:"Siddhartha",auteur:"Herman Hesse",genre:"classique",desc:"Un voyage spirituel au coeur de l'Inde."},
  ],
  aventure:[
    {titre:"Le Seigneur des Anneaux",auteur:"J.R.R. Tolkien",genre:"fantasy",desc:"Une epopee grandiose et inoubliable."},
    {titre:"Dune",auteur:"Frank Herbert",genre:"scifi",desc:"L'univers de SF le plus riche jamais construit."},
    {titre:"L'Appel de la foret",auteur:"Jack London",genre:"roman",desc:"Un recit d'aventure sauvage."},
    {titre:"Eragon",auteur:"Christopher Paolini",genre:"fantasy",desc:"Dragons et magie dans un monde epique."},
    {titre:"L'Ile au tresor",auteur:"Robert Louis Stevenson",genre:"classique",desc:"Pirates, cartes et tresors caches."},
  ],
  emotion:[
    {titre:"Les Fleurs pour Algernon",auteur:"Daniel Keyes",genre:"scifi",desc:"Bouleversant, sur l'intelligence et l'amour."},
    {titre:"La Voleuse de livres",auteur:"Markus Zusak",genre:"roman",desc:"L'Allemagne nazie vue par la Mort."},
    {titre:"Norwegian Wood",auteur:"Haruki Murakami",genre:"roman",desc:"Melancolie, amour et perte de l'innocence."},
    {titre:"Memoires d'une geisha",auteur:"Arthur Golden",genre:"roman",desc:"Poetique et sensible, un chef-d'oeuvre."},
    {titre:"Un monstre me appelle",auteur:"Patrick Ness",genre:"roman",desc:"La perte et le deuil vus par un enfant."},
  ],
  legerete:[
    {titre:"Le Petit Prince",auteur:"Antoine de Saint-Exupery",genre:"classique",desc:"Une fable douce pleine de sagesse."},
    {titre:"Good Omens",auteur:"Terry Pratchett",genre:"fantasy",desc:"Drole, absurde et brillant."},
    {titre:"Bridget Jones",auteur:"Helen Fielding",genre:"romance",desc:"Hilarant et touchant a la fois."},
    {titre:"Le Monde de Sophie",auteur:"Jostein Gaarder",genre:"roman",desc:"La philosophie expliquee de facon captivante."},
    {titre:"La Princesse de Cleves",auteur:"Madame de Lafayette",genre:"classique",desc:"Court et elegant, un bijou."},
  ],
  reflexion:[
    {titre:"L'Etranger",auteur:"Albert Camus",genre:"classique",desc:"Court et profond, sur l'absurde."},
    {titre:"1984",auteur:"George Orwell",genre:"scifi",desc:"Visionnaire et terriblement actuel."},
    {titre:"Le Meilleur des mondes",auteur:"Aldous Huxley",genre:"scifi",desc:"Dystopie troublante."},
    {titre:"Sapiens",auteur:"Yuval Noah Harari",genre:"roman",desc:"L'histoire de l'humanite revisitee."},
    {titre:"Crime et Chatiment",auteur:"Fiodor Dostoievski",genre:"classique",desc:"La culpabilite et la redemption."},
  ],
  frisson:[
    {titre:"Shining",auteur:"Stephen King",genre:"crime",desc:"Un huis clos terrifiant."},
    {titre:"Millenium T.1",auteur:"Stieg Larsson",genre:"crime",desc:"Thriller nordique haletant."},
    {titre:"Le Nom de la rose",auteur:"Umberto Eco",genre:"crime",desc:"Mystere medieval fascinant."},
    {titre:"Rebecca",auteur:"Daphne du Maurier",genre:"romance",desc:"Gothique, mysterieux, captivant."},
    {titre:"L'Exorciste",auteur:"William Peter Blatty",genre:"crime",desc:"Le roman d'horreur par excellence."},
  ]
};

const genreColors = {roman:'cv-roman',fantasy:'cv-fantasy',manga:'cv-manga',crime:'cv-crime',romance:'cv-romance',scifi:'cv-scifi',classique:'cv-classique'};
const pinColors   = ['#8b6ec4','#c4602a','#3aada8','#e06090','#5a9e6a','#b8933f'];
const tapeColors  = ['rgba(200,180,255,0.6)','rgba(196,96,42,0.35)','rgba(58,173,168,0.4)','rgba(224,96,144,0.35)','rgba(90,158,106,0.4)'];
const clipColors  = ['#8b6ec4','#c4602a','#3aada8','#e06090'];

const positions = [
  {x:5,  y:4,  rot:-6, pin:0, tape:'tape-top', clip:'tr', note:null},
  {x:58, y:2,  rot:5,  pin:1, tape:'tape-tl',  clip:'tl', note:"un coup de coeur"},
  {x:30, y:42, rot:-3, pin:2, tape:'tape-top', clip:'tr', note:null},
  {x:62, y:52, rot:7,  pin:3, tape:'tape-tl',  clip:null,  note:null},
  {x:8,  y:68, rot:-5, pin:0, tape:'tape-tr',  clip:'tl', note:"must read !"},
];

const threads = [
  {from:0, to:1, color:'#8b6ec4', width:1.5},
  {from:1, to:3, color:'#c4602a', width:1.5},
  {from:2, to:3, color:'#3aada8', width:1.5},
  {from:2, to:4, color:'#8b6ec4', width:1.5},
];

const dejaDansLib = new Set();
let currentMood   = 'calme';
let currentBooks  = [];

// ── Helpers ──────────────────────────────────────
function initials(t) {
  var w = t.split(' ').filter(function(x){return x.length>2;});
  return w.slice(0,2).map(function(x){return x[0].toUpperCase();}).join('') || t.slice(0,2).toUpperCase();
}

function getDailySeed() {
  var d = new Date();
  return d.getFullYear()*10000 + (d.getMonth()+1)*100 + d.getDate();
}
function seededRand(seed, i) {
  var x = Math.sin(seed+i)*10000;
  return x - Math.floor(x);
}
function dailyShuffle(arr, mood) {
  var seed = getDailySeed() + mood.split('').reduce(function(a,c){return a+c.charCodeAt(0);},0);
  var copy = arr.slice();
  for (var i=copy.length-1;i>0;i--) {
    var j = Math.floor(seededRand(seed,i)*(i+1));
    var tmp=copy[i]; copy[i]=copy[j]; copy[j]=tmp;
  }
  return copy.slice(0,5);
}

function clipSVG(color) {
  return '<svg viewBox="0 0 32 32" fill="none"><path d="M16 4C12 4 9 7 9 11C9 15 12 18 16 18L20 18C22 18 24 20 24 22C24 24 22 26 20 26C18 26 17 25 16 24L10 18C7 15 7 11 10 8" stroke="'+color+'" stroke-width="2.2" stroke-linecap="round" fill="none"/><path d="M20 4C22 4 24 6 24 8L24 22" stroke="'+color+'" stroke-width="2.2" stroke-linecap="round" fill="none" opacity="0.5"/></svg>';
}

async function loadDejaAjoutes() {
  try {
    var res  = await fetch('api_livres.php?action=get_livres');
    var data = await res.json();
    data.forEach(function(l){ dejaDansLib.add((l.titre+'|'+l.auteur).toLowerCase().trim()); });
  } catch(e) {}
}

// ── Build Card ───────────────────────────────────
function buildCard(b, i, pos) {
  var boardEl = document.getElementById('moodBoard');
  var W   = boardEl.offsetWidth || 900;
  var H   = Math.max(boardEl.offsetHeight, 620);
  var cW  = 170;
  var lft = Math.min(pos.x/100*(W-40), W-cW-20);
  var tp  = pos.y/100*(H-60);
  var pin = pinColors[pos.pin % pinColors.length];
  var tape= tapeColors[i % tapeColors.length];
  var clipC= clipColors[i % clipColors.length];
  var init = initials(b.titre);
  var cvCls= genreColors[b.genre] || 'cv-roman';
  var deja = dejaDansLib.has((b.titre+'|'+b.auteur).toLowerCase().trim());

  var clipStyle = '';
  if (pos.clip==='tr') clipStyle='top:-14px;right:-8px;transform:rotate(20deg)';
  else if (pos.clip==='tl') clipStyle='top:-14px;left:-8px;transform:rotate(-15deg)';

  var noteHTML = pos.note ? '<div class="board-note" style="right:-105px;top:28px">'+pos.note+'</div>' : '';
  var clipHTML = pos.clip ? '<div class="pcard-clip" style="'+clipStyle+'">'+clipSVG(clipC)+'</div>' : '';
  var btnHTML  = deja
    ? '<button class="tt-btn added" disabled>&#10003; Deja dans ta liste</button>'
    : '<button class="tt-btn" onclick="event.stopPropagation();ajouterLivre('+i+',this)">+ Ajouter a ma liste</button>';

  var card = document.createElement('div');
  card.className = 'pcard';
  card.setAttribute('data-idx', i);
  card.style.cssText = 'left:'+lft+'px;top:'+tp+'px;transform:rotate('+pos.rot+'deg);--pc-rotate:rotate('+pos.rot+'deg)';

  card.innerHTML =
    '<div class="pcard-pin" style="background:'+pin+'"></div>'+
    '<div class="pcard-tape '+pos.tape+'" style="background:'+tape+'"></div>'+
    clipHTML+
    '<div class="pcard-inner">'+
      '<div class="pcard-img">'+
        '<div class="pcard-cover '+cvCls+'" id="cover-'+i+'">'+
          '<span class="pcard-init" id="init-'+i+'">'+init+'</span>'+
        '</div>'+
      '</div>'+
      '<div class="pcard-label">'+(b.titre.length>18?b.titre.slice(0,16)+'...':b.titre)+'<br><span style="font-size:0.72rem;opacity:0.6">'+b.auteur.split(' ').pop()+'</span></div>'+
    '</div>'+
    noteHTML+
    '<div class="pcard-tooltip">'+
      '<div class="tt-title">'+b.titre+'</div>'+
      '<div class="tt-author">'+b.auteur+'</div>'+
      '<div class="tt-desc">'+b.desc+'</div>'+
      btnHTML+
    '</div>';

  card.addEventListener('click', function() {
    var titre  = currentBooks[i].titre;
    var auteur = currentBooks[i].auteur;
    var genre  = currentBooks[i].genre;
    fetch('https://openlibrary.org/search.json?q='+encodeURIComponent(titre+' '+auteur)+'&limit=1&fields=key,cover_i,ratings_average')
      .then(function(r){return r.json();})
      .then(function(d){
        var doc = d.docs && d.docs[0];
        if (doc && doc.key) {
          window.location.href = 'livre-detail.php?key='+encodeURIComponent(doc.key)+'&titre='+encodeURIComponent(titre)+'&auteur='+encodeURIComponent(auteur)+'&cover='+encodeURIComponent(doc.cover_i?'https://covers.openlibrary.org/b/id/'+doc.cover_i+'-L.jpg':'')+'&cv='+(genreColors[genre]||'cv-roman')+'&rating='+(doc.ratings_average||'');
        } else {
          window.location.href = 'decouvrir.php#search_'+encodeURIComponent(titre);
        }
      })
      .catch(function(){ window.location.href = 'decouvrir.php#search_'+encodeURIComponent(titre); });
  });

  return card;
}

// ── Render Board ─────────────────────────────────
async function renderBoard() {
  var allBooks = moodData[currentMood] || [];
  currentBooks = dailyShuffle(allBooks, currentMood);
  var board    = document.getElementById('boardCards');
  board.innerHTML = '';

  currentBooks.forEach(function(b, i) {
    var card = buildCard(b, i, positions[i]);
    if (positions[i].y > 45) card.classList.add('tip-up');
    board.appendChild(card);
  });
  requestAnimationFrame(function(){ drawThreads(); });

  // Charger couvertures
  currentBooks.forEach(function(b, i) {
    fetch('https://openlibrary.org/search.json?q='+encodeURIComponent(b.titre+' '+b.auteur)+'&limit=1&fields=cover_i')
      .then(function(r){return r.json();})
      .then(function(d){
        var id = d.docs && d.docs[0] && d.docs[0].cover_i;
        if (!id) return;
        var coverEl = document.getElementById('cover-'+i);
        var initEl  = document.getElementById('init-'+i);
        if (!coverEl) return;
        var img = document.createElement('img');
        img.className = 'pcard-cover-img';
        img.src = 'https://covers.openlibrary.org/b/id/'+id+'-M.jpg';
        img.loading = 'lazy';
        img.onerror = function(){img.remove();};
        img.onload  = function(){ if(initEl) initEl.style.display='none'; };
        coverEl.prepend(img);
      })
      .catch(function(){});
  });
}

// ── Draw Threads ─────────────────────────────────
function drawThreads() {
  var svg     = document.getElementById('boardThreads');
  var boardEl = document.getElementById('moodBoard');
  var W       = boardEl.offsetWidth || 900;
  var H       = Math.max(boardEl.offsetHeight, 620);
  var cW      = 170;
  var pad     = {top:52, left:44};

  var pts = positions.slice(0, currentBooks.length).map(function(pos){
    var l = Math.min(pos.x/100*(W-40), W-cW-20);
    var t = pos.y/100*(H-60);
    return {x: pad.left+l+cW/2, y: pad.top+t-7};
  });

  svg.setAttribute('viewBox','0 0 '+W+' '+H);
  var offsets=[{mx:30,my:-30},{mx:-40,my:20},{mx:20,my:30},{mx:-25,my:-20}];
  var paths='';
  threads.forEach(function(t,i){
    if (t.from<pts.length && t.to<pts.length){
      var a=pts[t.from], b=pts[t.to], off=offsets[i%offsets.length];
      var mx=(a.x+b.x)/2+off.mx, my=(a.y+b.y)/2+off.my;
      paths+='<path d="M'+a.x.toFixed(1)+','+a.y.toFixed(1)+' Q'+mx.toFixed(1)+','+my.toFixed(1)+' '+b.x.toFixed(1)+','+b.y.toFixed(1)+'" stroke="'+t.color+'" stroke-width="'+t.width+'" fill="none" opacity="0.7"/>';
      paths+='<circle cx="'+a.x.toFixed(1)+'" cy="'+a.y.toFixed(1)+'" r="5" fill="'+t.color+'" opacity="0.85"/>';
      paths+='<circle cx="'+b.x.toFixed(1)+'" cy="'+b.y.toFixed(1)+'" r="5" fill="'+t.color+'" opacity="0.85"/>';
    }
  });
  svg.innerHTML = paths;
}

// ── Mood ─────────────────────────────────────────
function setMood(el, m) {
  document.querySelectorAll('.mood-chip').forEach(function(c){c.classList.remove('on');});
  el.classList.add('on');
  currentMood = m;
  renderBoard();
}

// ── Ajouter livre ─────────────────────────────────
async function ajouterLivre(idx, btn) {
  var b = currentBooks[idx];
  if (!b) return;
  if (btn) { btn.disabled=true; btn.textContent='Ajout...'; }
  var body = new FormData();
  body.append('action','add_livre');
  body.append('titre', b.titre);
  body.append('auteur',b.auteur);
  body.append('genre', b.genre);
  body.append('statut','alire');
  body.append('note','0');
  body.append('commentaire','');
  body.append('cover_url','');
  try {
    var res  = await fetch('api_livres.php',{method:'POST',body:body});
    var data = await res.json();
    if (data.error){ showToast(data.error); if(btn){btn.disabled=false;btn.textContent='+ Ajouter a ma liste';} return; }
    dejaDansLib.add((b.titre+'|'+b.auteur).toLowerCase().trim());
    if (btn){ btn.className='tt-btn added'; btn.textContent='&#10003; Deja dans ta liste'; }
    showToast(b.titre+' ajoute a ta liste !');
  } catch(e) {
    showToast('Erreur reseau.');
    if(btn){btn.disabled=false;btn.textContent='+ Ajouter a ma liste';}
  }
}

function showToast(msg) {
  var t=document.getElementById('toast');
  document.getElementById('toastMsg').textContent=msg;
  t.classList.add('show');
  setTimeout(function(){t.classList.remove('show');},2800);
}

// ── Init ─────────────────────────────────────────
loadDejaAjoutes().then(function(){ renderBoard(); });
window.addEventListener('resize', function(){ renderBoard(); });
var heroDateEl = document.getElementById('heroDate');
if (heroDateEl) heroDateEl.textContent = new Date().toLocaleDateString('fr-FR',{day:'numeric',month:'long'});

</script>
</body>
</html>