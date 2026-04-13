<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];
$db  = getDB();

// ── Stats ─────────────────────────────────────────
$stmtStats = $db->prepare("
    SELECT
        SUM(statut='lu')      AS lus,
        SUM(statut='encours') AS en_cours,
        SUM(statut='alire')   AS a_lire
    FROM livres WHERE user_id = ?
");
$stmtStats->execute([$uid]);
$stats = $stmtStats->fetch();
$lus     = (int)($stats['lus']     ?? 0);
$enCours = (int)($stats['en_cours']?? 0);
$aLire   = (int)($stats['a_lire']  ?? 0);

// ── Livres en cours ───────────────────────────────
$stmtEnCours = $db->prepare("
    SELECT l.*, p.pourcentage
    FROM livres l
    LEFT JOIN progression p ON p.livre_id = l.id AND p.user_id = l.user_id
    WHERE l.user_id = ? AND l.statut = 'encours'
    ORDER BY l.created_at DESC
    LIMIT 3
");
$stmtEnCours->execute([$uid]);
$livresEnCours = $stmtEnCours->fetchAll();

// Fallback si pas de table progression
if (empty($livresEnCours)) {
    $stmtFallback = $db->prepare("SELECT * FROM livres WHERE user_id = ? AND statut = 'encours' ORDER BY created_at DESC LIMIT 3");
    $stmtFallback->execute([$uid]);
    $livresEnCours = $stmtFallback->fetchAll();
}

// ── Activité récente ──────────────────────────────
$stmtAct = $db->prepare("
    SELECT a.*, l.titre
    FROM activite a
    LEFT JOIN livres l ON l.id = a.livre_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 3
");
$stmtAct->execute([$uid]);
$activites = $stmtAct->fetchAll();

// ── Streak ────────────────────────────────────────
$stmtStreak = $db->prepare("SELECT jours FROM streak WHERE user_id = ?");
$stmtStreak->execute([$uid]);
$streak = $stmtStreak->fetch();
$streakJours = (int)($streak['jours'] ?? 0);

// ── Helpers ───────────────────────────────────────
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)   return 'il y a ' . max(1, round($diff/60)) . ' min';
    if ($diff < 86400)  return 'il y a ' . round($diff/3600) . ' h';
    if ($diff < 604800) return 'il y a ' . round($diff/86400) . ' jours';
    return 'il y a ' . round($diff/604800) . ' semaines';
}
function labelAct(string $action, string $titre): string {
    return match($action) {
        'ajout'      => "Ajouté <strong>" . htmlspecialchars($titre) . "</strong> à la liste",
        'suppression'=> "Supprimé <strong>" . htmlspecialchars($titre) . "</strong>",
        'favori'     => "<strong>" . htmlspecialchars($titre) . "</strong> en favori",
        'statut'     => "Statut mis à jour : <strong>" . htmlspecialchars($titre) . "</strong>",
        default      => "Action sur <strong>" . htmlspecialchars($titre) . "</strong>",
    };
}
function dotColor(string $action): string {
    return match($action) {
        'ajout'   => 'var(--gold)',
        'favori'  => 'var(--coral)',
        'statut'  => 'var(--teal)',
        default   => 'var(--sage)',
    };
}

// ── Initiales ─────────────────────────────────────
function getInit(string $titre): string {
    $w = array_filter(explode(' ', $titre), fn($w)=>strlen($w)>2);
    return substr(implode('', array_map(fn($w)=>strtoupper($w[0]), array_slice($w,0,2))),0,2) ?: strtoupper(substr($titre,0,2));
}

// Jours de la semaine pour le streak
$joursSemaine = ['L','M','M','J','V','S','D'];
$aujourdHui   = (int)date('N') - 1; // 0=lundi
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#fdf9f5;--bg-card:#ffffff;--bg-warm:#fef5ee;--bg-green:#f0f7f0;--bg-blue:#eef4fb;--bg-purple:#f5f0fb;--bg-pink:#fdf0f5;
      --ink:#1c1612;--ink-soft:#3a2e24;--muted:#8a7b70;--muted-lt:#b8a898;
      --border:#ede5dc;--border-w:#e0d4c6;
      --coral:#e8634a;--coral-lt:#fff0ec;
      --teal:#3aada8;--teal-lt:#e8f7f6;
      --sage:#5a9e6a;--sage-lt:#edf7ef;
      --lavender:#8b6ec4;--lav-lt:#f2edfb;
      --gold:#c49a38;--gold-lt:#fdf5e0;
      --blush:#e06090;--blush-lt:#fdeef5;
      --lav:#8b6ec4;
      --font-serif:'Playfair Display',Georgia,serif;
      --font-sans:'Instrument Sans',system-ui,sans-serif;
      --radius:14px;--radius-lg:20px;
      --shadow-sm:0 1px 6px rgba(28,22,18,0.06);
      --shadow-md:0 4px 24px rgba(28,22,18,0.09);
      --shadow-lg:0 12px 48px rgba(28,22,18,0.13);
    }
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased}
    /* HEADER */
    .site-header{position:sticky;top:0;z-index:100;background:rgba(253,249,245,0.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 60px;height:66px}
    .logo{display:flex;align-items:baseline;gap:10px}.logo-mark{font-family:var(--font-serif);font-size:1.3rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}.logo-mark em{font-style:italic;color:var(--coral)}.logo-divider{width:1px;height:14px;background:var(--border-w)}.logo-sub{font-size:0.72rem;color:var(--muted);letter-spacing:0.07em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}.header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:8px 20px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}.btn-ghost{background:transparent;color:var(--ink-soft);border:1.5px solid var(--border-w)}.btn-ghost:hover{background:var(--bg-warm)}.btn-solid{background:var(--coral);color:#fff}.btn-solid:hover{background:#d0522e}
    /* NAV */
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}.nav-inner{max-width:1320px;margin:0 auto;padding:0 60px;display:flex;align-items:center;gap:4px;height:46px}.nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:8px;letter-spacing:0.02em;transition:all 0.15s}.nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}.nav-inner a.active{color:var(--coral);background:var(--coral-lt)}
    .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:22px;width:480px;flex-direction:row;z-index:200}.nav-dropdown:hover .nav-dropdown-menu{display:flex}
    .mega-left{flex:1;padding-right:20px;border-right:1px solid var(--border)}.mega-badge{display:inline-block;font-size:0.62rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--coral);background:var(--coral-lt);padding:3px 10px;border-radius:99px;margin-bottom:10px}.mega-heading{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);margin-bottom:8px}.mega-text{font-size:0.77rem;color:var(--muted);line-height:1.55}.mega-links{display:grid;grid-template-columns:1fr 1fr;gap:6px;flex:1.2}.mega-card{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;text-decoration:none;transition:background 0.15s}.mega-card:hover{background:var(--bg-warm)}.mega-icon{width:32px;height:32px;border-radius:8px;background:var(--coral-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--coral)}.mega-icon svg{width:14px;height:14px}.mega-card-title{display:block;font-size:0.8rem;font-weight:600;color:var(--ink)}.mega-card-desc{display:block;font-size:0.68rem;color:var(--muted)}
    /* HERO */
    .hero{max-width:1320px;margin:0 auto;padding:72px 60px 64px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;position:relative}
    .hero-deco-circle-1{position:absolute;top:-60px;right:280px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(58,173,168,0.12),transparent 70%);pointer-events:none}
    .hero-deco-circle-2{position:absolute;bottom:-40px;left:40px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(139,110,196,0.1),transparent 70%);pointer-events:none}
    .hero-left{position:relative;z-index:1}
    .hero-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:0.7rem;font-weight:600;letter-spacing:0.15em;text-transform:uppercase;color:var(--coral);margin-bottom:20px}
    .hero-eyebrow-dot{width:6px;height:6px;background:var(--coral);border-radius:50%;animation:pulse 2.5s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(0.75)}}
    .hero-title{font-family:var(--font-serif);font-size:clamp(2.8rem,5vw,4.8rem);font-weight:700;line-height:1.02;color:var(--ink);letter-spacing:-0.03em;margin-bottom:36px}
    .hero-title em{font-style:italic;font-weight:400;color:var(--coral);display:block}
    .hero-search{display:flex;max-width:460px;margin-bottom:18px;border:2px solid var(--border-w);border-radius:99px;background:#fff;box-shadow:var(--shadow-sm);overflow:hidden;transition:border-color 0.2s,box-shadow 0.2s}
    .hero-search:focus-within{border-color:var(--coral);box-shadow:0 0 0 4px rgba(232,99,74,0.1)}
    .hero-search-wrap{flex:1;position:relative}.hero-search-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--muted-lt);pointer-events:none}
    .hero-search-input{width:100%;height:50px;padding:0 16px 0 46px;border:none;background:transparent;font-family:var(--font-sans);font-size:0.88rem;color:var(--ink);outline:none}.hero-search-input::placeholder{color:var(--muted-lt)}
    .hero-search-btn{height:50px;padding:0 26px;background:var(--coral);color:#fff;border:none;font-family:var(--font-sans);font-size:0.82rem;font-weight:600;cursor:pointer;transition:background 0.18s;letter-spacing:0.02em;border-radius:0 99px 99px 0}.hero-search-btn:hover{background:#d0522e}
    .hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:44px}.hero-tag{font-size:0.72rem;font-weight:500;padding:5px 14px;border-radius:99px;border:1.5px solid var(--border-w);color:var(--muted);cursor:pointer;transition:all 0.15s;background:transparent}.hero-tag:hover{border-color:var(--coral);color:var(--coral);background:var(--coral-lt)}
    .hero-stats{display:flex;gap:0}.hero-stat{padding:0 28px 0 0;margin:0 28px 0 0;border-right:1.5px solid var(--border)}.hero-stat:last-child{border-right:none;padding:0;margin:0}.hero-stat-num{font-family:var(--font-serif);font-size:2rem;font-weight:700;color:var(--ink);line-height:1;margin-bottom:3px}.hero-stat-label{font-size:0.62rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted)}
    .hero-right{position:relative;z-index:1;display:flex;align-items:flex-end;justify-content:center}
    .big-books{display:flex;align-items:flex-end;gap:10px}
    .big-book{border-radius:5px 9px 9px 5px;position:relative;overflow:hidden;flex-shrink:0;box-shadow:10px 14px 36px rgba(28,22,18,0.18),5px 5px 10px rgba(28,22,18,0.1),-3px 0 7px rgba(28,22,18,0.08),inset -5px 0 14px rgba(0,0,0,0.14),inset 2px 0 6px rgba(255,255,255,0.08);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);cursor:default}
    .big-book:hover{transform:translateY(-22px) rotate(-1.5deg) scale(1.03)}
    .big-book::before{content:'';position:absolute;left:0;top:0;bottom:0;width:13px;background:linear-gradient(90deg,rgba(0,0,0,0.28) 0%,rgba(0,0,0,0.08) 60%,transparent);border-radius:5px 0 0 5px;z-index:2}
    .big-book::after{content:'';position:absolute;right:6px;top:0;bottom:0;width:3px;background:rgba(255,255,255,0.07);z-index:2}
    .book-tex{position:absolute;inset:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.018) 2px,rgba(0,0,0,0.018) 3px);z-index:1}
    .book-band{position:absolute;left:0;right:0;height:2px;background:rgba(255,255,255,0.14);z-index:3}
    .book-lbl{writing-mode:vertical-rl;transform:rotate(180deg);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--font-sans);font-size:0.7rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.72);text-shadow:0 1px 5px rgba(0,0,0,0.3);padding:24px 0;z-index:4}
    .bb-1{background:linear-gradient(165deg,#d66050,#9c3220)}.bb-2{background:linear-gradient(165deg,#4898c0,#245a82)}.bb-3{background:linear-gradient(165deg,#c0a838,#7a6410)}.bb-4{background:linear-gradient(165deg,#58a870,#285e3a)}.bb-5{background:linear-gradient(165deg,#a868c8,#5c2880)}
    /* SECTIONS ÉDITORIALES */
    .ed-section{padding:72px 0;border-top:1px solid var(--border)}.ed-section-alt{background:var(--bg-warm)}
    .ed-inner{max-width:1100px;margin:0 auto;padding:0 60px}
    .ed-overline{font-size:0.65rem;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:10px;margin-bottom:28px}
    .ed-overline::before{content:'';width:20px;height:1.5px;background:var(--border-w)}
    /* EN COURS */
    .ed-reading-row{display:flex;align-items:center;gap:40px;flex-wrap:wrap}
    .ed-book-item{display:flex;align-items:center;gap:18px;text-decoration:none;transition:opacity 0.18s;flex:1;min-width:220px}.ed-book-item:hover{opacity:0.72}
    .ed-book-spine{width:44px;height:64px;border-radius:3px 6px 6px 3px;display:flex;align-items:center;justify-content:center;font-family:var(--font-serif);font-size:0.9rem;font-weight:700;color:rgba(255,255,255,0.85);flex-shrink:0;box-shadow:4px 6px 18px rgba(0,0,0,0.18),-2px 0 4px rgba(0,0,0,0.12)}
    .ed-book-meta{flex:1}.ed-book-title{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);line-height:1.2;margin-bottom:2px}.ed-book-author{font-size:0.72rem;color:var(--muted);margin-bottom:10px}
    .ed-book-bar{height:2px;background:var(--border);border-radius:99px;overflow:hidden;margin-bottom:5px}.ed-bar-fill{height:100%;border-radius:99px;transition:width 0.6s ease}
    .ed-book-pct{font-size:0.68rem;font-weight:600;color:var(--muted)}
    .ed-vline{width:1px;height:60px;background:var(--border);flex-shrink:0}
    .ed-see-all{font-size:0.78rem;font-weight:600;color:var(--coral);text-decoration:none;white-space:nowrap;margin-left:auto}.ed-see-all:hover{text-decoration:underline}
    /* STREAK */
    .ed-row-split{display:flex;gap:80px;align-items:flex-start}
    .ed-streak{flex-shrink:0}.ed-streak-num{font-family:var(--font-serif);font-size:5rem;font-weight:700;color:var(--gold);line-height:1;letter-spacing:-0.04em}.ed-streak-label{font-size:0.78rem;color:var(--muted);line-height:1.5;margin-bottom:18px}
    .ed-streak-days{display:flex;gap:6px}.sd{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;background:var(--border);color:var(--muted)}.sd.done{background:var(--gold);color:var(--ink)}.sd.today{background:var(--coral);color:#fff}
    .ed-mid-rule{width:1px;background:var(--border);align-self:stretch;flex-shrink:0}
    .ed-activity{flex:1;padding-top:4px}
    .ed-act-line{display:flex;align-items:baseline;gap:10px;font-size:0.88rem;color:var(--ink-soft);padding:14px 0;border-bottom:1px solid var(--border);line-height:1.4}.ed-act-line:first-of-type{padding-top:0}
    .ed-act-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:4px}
    .ed-act-when{font-size:0.72rem;color:var(--muted);margin-left:auto;white-space:nowrap}
    /* HUMEUR */
    .ed-mood-section{background:var(--bg)}.ed-mood-header{margin-bottom:36px}
    .ed-mood-title{font-family:var(--font-serif);font-size:clamp(2rem,4vw,3.2rem);font-weight:700;color:var(--ink);letter-spacing:-0.03em;line-height:1.1;margin-top:10px}
    .ed-mood-title em{font-style:italic;font-weight:400;color:var(--lav)}
    .ed-mood-pills{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:40px}
    .ed-pill{padding:10px 22px;border-radius:99px;border:1.5px solid var(--border-w);background:transparent;font-family:var(--font-sans);font-size:0.82rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all 0.2s}.ed-pill:hover{border-color:var(--lav);color:var(--lav)}.ed-pill.on{background:var(--lav);border-color:var(--lav);color:#fff}
    .ed-mood-result{display:none;align-items:flex-start;gap:28px;padding:32px 0;border-top:1px solid var(--border);animation:fadeUp 0.35s ease}.ed-mood-result.show{display:flex}
    .ed-mr-book{width:52px;height:76px;border-radius:3px 7px 7px 3px;background:linear-gradient(155deg,var(--lav),#481880);flex-shrink:0;box-shadow:4px 6px 18px rgba(0,0,0,0.15)}
    .ed-mr-genre{font-size:0.65rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--lav);margin-bottom:6px}
    .ed-mr-title{font-family:var(--font-serif);font-size:1.5rem;font-weight:500;color:var(--ink);letter-spacing:-0.02em;margin-bottom:4px}
    .ed-mr-author{font-size:0.78rem;color:var(--muted);margin-bottom:8px}
    .ed-mr-desc{font-size:0.84rem;color:var(--ink-soft);line-height:1.6;margin-bottom:14px;max-width:480px}
    .ed-mr-link{font-size:0.78rem;font-weight:600;color:var(--coral);text-decoration:none}.ed-mr-link:hover{text-decoration:underline}
    /* GENRES */
    .ed-genres-section{background:var(--ink);border-top:none}
    .ed-genres-section .ed-overline{color:rgba(245,240,232,0.35)}.ed-genres-section .ed-overline::before{background:rgba(245,240,232,0.15)}
    .ed-genres-scroll{display:flex;flex-wrap:wrap;gap:12px}
    .ed-genre-chip{padding:14px 28px;border-radius:99px;border:1.5px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.04);font-family:var(--font-sans);font-size:0.88rem;font-weight:600;color:rgba(245,240,232,0.75);text-decoration:none;transition:all 0.2s;position:relative;overflow:hidden}
    .ed-genre-chip::before{content:'';position:absolute;inset:0;background:var(--c);opacity:0;transition:opacity 0.2s;border-radius:99px}
    .ed-genre-chip:hover::before{opacity:0.15}.ed-genre-chip:hover{border-color:rgba(255,255,255,0.25);color:#fff;transform:translateY(-2px)}
    /* ANIMATIONS */
    @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .fade-in{opacity:0;transform:translateY(18px);transition:opacity 0.6s ease,transform 0.6s ease}.fade-in.visible{opacity:1;transform:translateY(0)}
    @media(max-width:900px){.hero{grid-template-columns:1fr;padding:48px 32px}.ed-inner{padding:0 28px}.ed-reading-row{gap:24px}.ed-vline{display:none}.ed-row-split{flex-direction:column;gap:40px}.ed-mid-rule{display:none}.site-header,.nav-inner{padding-inline:32px}}
  </style>
</head>
<body>

<?php include __DIR__ . '/header_nav.php'; ?>

<nav class="site-nav">
  <div class="nav-inner">
    <a href="index.php" class="active">Accueil</a>
    <div class="nav-dropdown">
      <a href="livres.php">Bibliothèque</a>
      <div class="nav-dropdown-menu">
        <div class="mega-left"><div class="mega-badge">Explorer</div><h3 class="mega-heading">Ma bibliothèque</h3><p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture, garde tes favoris.</p></div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<!-- HERO -->
<section>
  <div class="hero">
    <div class="hero-deco-circle-1"></div>
    <div class="hero-deco-circle-2"></div>
    <div class="hero-left">
      <div class="hero-eyebrow"><div class="hero-eyebrow-dot"></div>Ta bibliothèque personnelle</div>
      <h1 class="hero-title">Chaque livre<br>est une<br><em>nouvelle aventure</em></h1>
      <div class="hero-search">
        <div class="hero-search-wrap">
          <svg class="hero-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input class="hero-search-input" type="text" id="searchInput" placeholder="Titre, auteur, genre…"/>
        </div>
        <button class="hero-search-btn" onclick="doSearch()">Rechercher</button>
      </div>
      <div class="hero-tags">
        <span class="hero-tag" onclick="setSearch('Fantasy')">Fantasy</span>
        <span class="hero-tag" onclick="setSearch('Agatha Christie')">Agatha Christie</span>
        <span class="hero-tag" onclick="setSearch('Manga')">Manga</span>
        <span class="hero-tag" onclick="setSearch('Romance')">Romance</span>
        <span class="hero-tag" onclick="setSearch('Classiques')">Classiques</span>
        <span class="hero-tag" onclick="setSearch('Sci-Fi')">Sci-Fi</span>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><div class="hero-stat-num"><?= $lus ?></div><div class="hero-stat-label">Livres lus</div></div>
        <div class="hero-stat"><div class="hero-stat-num"><?= $enCours ?></div><div class="hero-stat-label">En cours</div></div>
        <div class="hero-stat"><div class="hero-stat-num"><?= $aLire ?></div><div class="hero-stat-label">À lire</div></div>
      </div>
    </div>
    <div class="hero-right">
      <div class="big-books">
        <div class="big-book bb-1" style="width:68px;height:66vh;max-height:400px;transform:rotate(2deg) translateY(16px)"><div class="book-tex"></div><div class="book-band" style="top:22%"></div><div class="book-band" style="bottom:22%"></div><span class="book-lbl">Roman</span></div>
        <div class="big-book bb-2" style="width:84px;height:80vh;max-height:490px;transform:rotate(-1deg)"><div class="book-tex"></div><div class="book-band" style="top:18%"></div><div class="book-band" style="bottom:18%"></div><span class="book-lbl">Fantasy</span></div>
        <div class="big-book bb-3" style="width:72px;height:72vh;max-height:440px;transform:rotate(1.5deg) translateY(8px)"><div class="book-tex"></div><div class="book-band" style="top:20%"></div><div class="book-band" style="bottom:20%"></div><span class="book-lbl">Classique</span></div>
        <div class="big-book bb-4" style="width:64px;height:64vh;max-height:390px;transform:rotate(-2deg) translateY(20px)"><div class="book-tex"></div><div class="book-band" style="top:25%"></div><div class="book-band" style="bottom:25%"></div><span class="book-lbl">Manga</span></div>
        <div class="big-book bb-5" style="width:76px;height:74vh;max-height:460px;transform:rotate(1deg) translateY(12px)"><div class="book-tex"></div><div class="book-band" style="top:19%"></div><div class="book-band" style="bottom:19%"></div><span class="book-lbl">Sci-Fi</span></div>
      </div>
    </div>
  </div>
</section>

<!-- EN COURS -->
<section class="ed-section">
  <div class="ed-inner">
    <div class="ed-overline fade-in">En cours de lecture</div>
    <div class="ed-reading-row fade-in">
      <?php if (empty($livresEnCours)): ?>
        <p style="color:var(--muted);font-size:0.85rem">Aucun livre en cours. <a href="mes-livres.php" style="color:var(--coral)">Ajoute-en un →</a></p>
      <?php else: foreach ($livresEnCours as $i => $livre):
        $pct = (int)($livre['pourcentage'] ?? $livre['progression'] ?? 0);
        $colors = ['var(--coral)','var(--teal)','var(--lavender)'];
        $color  = $colors[$i % 3];
        $bgColors = ['linear-gradient(160deg,#c84030,#8c1e20)','linear-gradient(160deg,#28a8a0,#086860)','linear-gradient(160deg,#8b6ec4,#481880)'];
        $bg = $bgColors[$i % 3];
      ?>
      <?php if ($i > 0): ?><div class="ed-vline"></div><?php endif ?>
      <a href="mes-livres.php" class="ed-book-item">
        <div class="ed-book-spine" style="background:<?= $bg ?>"><?= getInit($livre['titre']) ?></div>
        <div class="ed-book-meta">
          <div class="ed-book-title"><?= htmlspecialchars($livre['titre']) ?></div>
          <div class="ed-book-author"><?= htmlspecialchars($livre['auteur']) ?></div>
          <div class="ed-book-bar"><div class="ed-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
          <div class="ed-book-pct"><?= $pct ?>%</div>
        </div>
      </a>
      <?php endforeach; endif ?>
      <a href="mes-livres.php" class="ed-see-all">Voir tout →</a>
    </div>
  </div>
</section>

<!-- STREAK + ACTIVITÉ -->
<section class="ed-section ed-section-alt">
  <div class="ed-inner ed-row-split">
    <div class="ed-streak fade-in">
      <div class="ed-streak-num"><?= $streakJours ?></div>
      <div class="ed-streak-label">jours de lecture<br>consécutifs</div>
      <div class="ed-streak-days">
        <?php foreach ($joursSemaine as $idx => $jour):
          $cls = '';
          if ($streakJours >= 7) $cls = $idx === $aujourdHui ? 'today' : 'done';
          elseif ($idx < $streakJours && $idx < $aujourdHui) $cls = 'done';
          elseif ($idx === $aujourdHui) $cls = 'today';
        ?>
        <span class="sd <?= $cls ?>"><?= $jour ?></span>
        <?php endforeach ?>
      </div>
    </div>
    <div class="ed-mid-rule"></div>
    <div class="ed-activity fade-in">
      <div class="ed-overline" style="margin-bottom:20px">Activité récente</div>
      <?php if (empty($activites)): ?>
        <div class="ed-act-line"><span class="ed-act-dot" style="background:var(--muted)"></span>Aucune activité récente.</div>
      <?php else: foreach ($activites as $act): ?>
      <div class="ed-act-line">
        <span class="ed-act-dot" style="background:<?= dotColor($act['action']) ?>"></span>
        <?= labelAct($act['action'], $act['titre'] ?? '') ?>
        <span class="ed-act-when"><?= timeAgo($act['created_at']) ?></span>
      </div>
      <?php endforeach; endif ?>
    </div>
  </div>
</section>

<!-- HUMEUR -->
<section class="ed-section ed-mood-section fade-in">
  <div class="ed-inner">
    <div class="ed-mood-header">
      <div class="ed-overline">Suggestion du jour</div>
      <h2 class="ed-mood-title">Comment tu te <em>sens</em> aujourd'hui ?</h2>
    </div>
    <div class="ed-mood-pills">
      <button class="ed-pill" data-mood="calme">Calme</button>
      <button class="ed-pill" data-mood="aventure">Aventure</button>
      <button class="ed-pill" data-mood="emotion">Émotion</button>
      <button class="ed-pill" data-mood="legerete">Légèreté</button>
      <button class="ed-pill" data-mood="reflexion">Réfléchi</button>
      <button class="ed-pill" data-mood="frisson">Frisson</button>
    </div>
    <div class="ed-mood-result" id="moodResult">
      <div class="ed-mr-book" id="mrSpine"></div>
      <div class="ed-mr-text">
        <div class="ed-mr-genre" id="mrGenre"></div>
        <div class="ed-mr-title" id="mrTitle"></div>
        <div class="ed-mr-author" id="mrAuthor"></div>
        <div class="ed-mr-desc" id="mrDesc"></div>
        <a href="recommandations.php" class="ed-mr-link">Voir plus de suggestions →</a>
      </div>
    </div>
  </div>
</section>

<!-- GENRES -->
<section class="ed-section ed-genres-section fade-in">
  <div class="ed-inner">
    <div class="ed-overline">Explorer</div>
    <div class="ed-genres-scroll">
      <a href="recherche.php?q=Roman"      class="ed-genre-chip" style="--c:#c84030">Roman</a>
      <a href="recherche.php?q=Fantasy"    class="ed-genre-chip" style="--c:#3870c0">Fantasy</a>
      <a href="recherche.php?q=Manga"      class="ed-genre-chip" style="--c:#c8a020">Manga</a>
      <a href="recherche.php?q=Crime"      class="ed-genre-chip" style="--c:#283880">Crime</a>
      <a href="recherche.php?q=Romance"    class="ed-genre-chip" style="--c:#c83070">Romance</a>
      <a href="recherche.php?q=Sci-Fi"     class="ed-genre-chip" style="--c:#28a8a0">Sci-Fi</a>
      <a href="recherche.php?q=Classiques" class="ed-genre-chip" style="--c:#9060c0">Classiques</a>
      <a href="decouvrir.php"              class="ed-genre-chip" style="--c:#c49a38">Découvrir tout →</a>
    </div>
  </div>
</section>

<script>
function setSearch(v){document.getElementById("searchInput").value=v;document.getElementById("searchInput").focus()}
function doSearch(){const q=document.getElementById("searchInput").value.trim();if(q)window.location.href="recherche.php?q="+encodeURIComponent(q)}
document.getElementById("searchInput").addEventListener("keydown",e=>{if(e.key==="Enter")doSearch()});

const moodBooks={
  calme:{title:"Le Vieil Homme et la Mer",author:"Ernest Hemingway",desc:"Contemplatif, sur la solitude et la persévérance.",genre:"Roman"},
  aventure:{title:"Le Seigneur des Anneaux",author:"J.R.R. Tolkien",desc:"Une épopée grandiose dans un monde fantastique.",genre:"Fantasy"},
  emotion:{title:"Les Fleurs pour Algernon",author:"Daniel Keyes",desc:"Bouleversant, sur l'intelligence et l'amour.",genre:"Sci-Fi"},
  legerete:{title:"Le Petit Prince",author:"A. de Saint-Exupéry",desc:"Une fable douce pleine de sagesse.",genre:"Classique"},
  reflexion:{title:"L'Étranger",author:"Albert Camus",desc:"Court et profond, sur l'absurde.",genre:"Philosophie"},
  frisson:{title:"Shining",author:"Stephen King",desc:"Un huis clos terrifiant dans un hôtel hanté.",genre:"Thriller"}
};
document.querySelectorAll(".ed-pill").forEach(btn=>{
  btn.addEventListener("click",function(){
    document.querySelectorAll(".ed-pill").forEach(b=>b.classList.remove("on"));
    this.classList.add("on");
    const b=moodBooks[this.dataset.mood];if(!b)return;
    document.getElementById("mrTitle").textContent=b.title;
    document.getElementById("mrAuthor").textContent=b.author;
    document.getElementById("mrDesc").textContent=b.desc;
    document.getElementById("mrGenre").textContent=b.genre;
    document.getElementById("moodResult").classList.add("show");
  });
});
const obs=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target)}});},{threshold:0.06});
document.querySelectorAll('.fade-in').forEach(el=>obs.observe(el));
</script>
</body>
</html>