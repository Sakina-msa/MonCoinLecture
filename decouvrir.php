<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT titre, auteur FROM livres WHERE user_id = ?");
$stmt->execute([$uid]);
$dejaDansLib = [];
foreach ($stmt->fetchAll() as $row) {
    $dejaDansLib[] = strtolower(trim($row['titre']).'|'.trim($row['auteur']));
}
$genres = [
  ['id'=>'topnotes',   'label'=>'Top notés',   'dot'=>'#b8933f','query'=>'ratings_sortby=rating subject:fiction','cv'=>'cv-roman'],
  ['id'=>'populaires', 'label'=>'Populaires',  'dot'=>'#c4602a','query'=>'subject:bestseller popular fiction','cv'=>'cv-roman'],
  ['id'=>'fantasy',    'label'=>'Fantasy',     'dot'=>'#3870c0','query'=>'subject:fantasy magic dragons','cv'=>'cv-fantasy'],
  ['id'=>'thriller',   'label'=>'Thriller',    'dot'=>'#283880','query'=>'subject:thriller suspense','cv'=>'cv-crime'],
  ['id'=>'roman',      'label'=>'Roman',       'dot'=>'#c84030','query'=>'subject:fiction literary novel','cv'=>'cv-roman'],
  ['id'=>'scifi',      'label'=>'Sci-Fi',      'dot'=>'#28a8a0','query'=>'subject:science fiction space','cv'=>'cv-scifi'],
  ['id'=>'romance',    'label'=>'Romance',     'dot'=>'#c83070','query'=>'subject:romance love story','cv'=>'cv-romance'],
  ['id'=>'manga',      'label'=>'Manga',       'dot'=>'#c8a020','query'=>'subject:manga japanese comics','cv'=>'cv-manga'],
  ['id'=>'horreur',    'label'=>'Horreur',     'dot'=>'#5a1a1a','query'=>'subject:horror ghost supernatural','cv'=>'cv-crime'],
  ['id'=>'classique',  'label'=>'Classiques',  'dot'=>'#9060c0','query'=>'subject:classics literature 19th century','cv'=>'cv-classique'],
  ['id'=>'bd',         'label'=>'BD',          'dot'=>'#e06090','query'=>'subject:bande dessinee comics graphic novel','cv'=>'cv-autres'],
  ['id'=>'policier',   'label'=>'Policier',    'dot'=>'#1a3850','query'=>'subject:detective mystery crime investigation','cv'=>'cv-crime'],
  ['id'=>'biographie', 'label'=>'Biographie',  'dot'=>'#5a9e6a','query'=>'subject:biography autobiography memoir','cv'=>'cv-autres'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Découvrir — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--ink:#1a1410;--ink-soft:#3d322a;--muted:#8a7b6e;--muted-light:#b5a89b;--bg:#faf7f2;--bg-warm:#f3ede3;--bg-card:#fffcf7;--border:#e8e0d4;--border-warm:#d4c8b8;--accent:#c4602a;--accent-light:#fdf0e8;--accent-deep:#9e3e14;--gold:#b8933f;--sage:#5a9e6a;--sage-light:#edf7ef;--lav:#8b6ec4;--lav-light:#f2edfb;--font-serif:'Playfair Display',Georgia,serif;--font-sans:'Instrument Sans',system-ui,sans-serif;--radius:12px;--radius-lg:18px;--shadow-sm:0 1px 4px rgba(26,20,16,0.06);--shadow-md:0 4px 20px rgba(26,20,16,0.09);--shadow-lg:0 12px 48px rgba(26,20,16,0.14)}
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased;min-height:100vh}
    .site-header{position:sticky;top:0;z-index:100;background:rgba(250,247,242,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px}
    .logo{display:flex;align-items:baseline;gap:10px}.logo-mark{font-family:var(--font-serif);font-size:1.25rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}.logo-mark em{font-style:italic;color:var(--accent)}.logo-divider{width:1px;height:14px;background:var(--border-warm)}.logo-sub{font-size:0.75rem;color:var(--muted);letter-spacing:0.06em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}.header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:7px 18px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}.btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border-warm)}.btn-ghost:hover{background:var(--bg-warm)}.btn-accent{background:var(--ink);color:var(--bg)}.btn-accent:hover{background:var(--ink-soft)}
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}.nav-inner{max-width:1400px;margin:0 auto;padding:0 48px;display:flex;align-items:center;gap:4px;height:46px}.nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:6px;letter-spacing:0.03em;transition:all 0.15s}.nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}.nav-inner a.active{color:var(--accent);background:var(--accent-light)}
    .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;width:500px;flex-direction:row;z-index:200}.nav-dropdown:hover .nav-dropdown-menu{display:flex}
    .mega-left{flex:1;padding-right:20px;border-right:1px solid var(--border)}.mega-badge{display:inline-block;font-size:0.65rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--accent);background:var(--accent-light);padding:3px 10px;border-radius:99px;margin-bottom:10px}.mega-heading{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);margin-bottom:8px}.mega-text{font-size:0.78rem;color:var(--muted);line-height:1.55}.mega-links{display:grid;grid-template-columns:1fr 1fr;gap:6px;flex:1.2}.mega-card{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;transition:background 0.15s}.mega-card:hover{background:var(--bg-warm)}.mega-icon{width:32px;height:32px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}.mega-icon svg{width:15px;height:15px}.mega-card-title{display:block;font-size:0.8rem;font-weight:600;color:var(--ink)}.mega-card-desc{display:block;font-size:0.7rem;color:var(--muted)}
    /* PAGE */
    .page-wrap{max-width:1400px;margin:0 auto;padding:48px 0 80px}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes spin{to{transform:rotate(360deg)}}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    /* HERO SEARCH */
    .hero-search-section{padding:0 48px 44px;animation:fadeUp 0.5s ease both}
    .hero-label{font-size:0.7rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .hero-label::before{content:'';width:20px;height:1.5px;background:var(--border-warm)}
    .hero-title{font-family:var(--font-serif);font-size:clamp(2.2rem,4vw,3.2rem);font-weight:700;color:var(--ink);letter-spacing:-0.03em;line-height:1.05;margin-bottom:28px}
    .hero-title em{font-style:italic;font-weight:400;color:var(--accent)}
    .search-bar{display:flex;max-width:640px;border:2px solid var(--border-warm);border-radius:99px;background:#fff;box-shadow:var(--shadow-md);overflow:hidden;transition:border-color 0.2s,box-shadow 0.2s}
    .search-bar:focus-within{border-color:var(--accent);box-shadow:0 0 0 4px rgba(196,96,42,0.1)}
    .search-bar-wrap{flex:1;position:relative}
    .search-icon{position:absolute;left:20px;top:50%;transform:translateY(-50%);color:var(--muted-light);pointer-events:none}
    .search-input{width:100%;height:54px;padding:0 16px 0 50px;border:none;background:transparent;font-family:var(--font-sans);font-size:0.95rem;color:var(--ink);outline:none}
    .search-input::placeholder{color:var(--muted-light)}
    .search-btn{height:54px;padding:0 28px;background:var(--accent);color:#fff;border:none;font-family:var(--font-sans);font-size:0.84rem;font-weight:600;cursor:pointer;transition:background 0.18s;border-radius:0 99px 99px 0;white-space:nowrap}
    .search-btn:hover{background:var(--accent-deep)}
    /* GENRE SECTION */
    .genre-section{margin-bottom:52px;animation:fadeUp 0.5s ease both}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:0 48px;margin-bottom:18px}
    .section-title-wrap{display:flex;align-items:center;gap:14px}
    .section-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .section-title{font-family:var(--font-serif);font-size:1.3rem;font-weight:500;color:var(--ink);letter-spacing:-0.015em}
    .section-see-all{font-size:0.75rem;font-weight:600;color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:4px;transition:gap 0.15s;white-space:nowrap;border:none;background:none;cursor:pointer;font-family:var(--font-sans)}
    .section-see-all:hover{gap:8px}
    .section-see-all svg{width:12px;height:12px}
    /* SLIDE */
    .slide-wrap{position:relative}
    .slide-track{display:flex;gap:14px;padding:4px 48px 16px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none}
    .slide-track::-webkit-scrollbar{display:none}
    .slide-arrow{position:absolute;top:40%;transform:translateY(-50%);width:36px;height:36px;border-radius:50%;background:#fff;border:1.5px solid var(--border);box-shadow:var(--shadow-md);cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;transition:all 0.18s;color:var(--ink-soft)}
    .slide-arrow:hover{background:var(--ink);color:#fff;border-color:var(--ink)}
    .slide-arrow svg{width:14px;height:14px}
    .slide-arrow.left{left:10px}
    .slide-arrow.right{right:10px}
    /* CARTE */
    .book-card{width:160px;flex-shrink:0;cursor:pointer;transition:transform 0.22s cubic-bezier(0.34,1.56,0.64,1);position:relative}
    .book-card:hover{transform:translateY(-6px)}
    .book-card:hover .book-overlay{opacity:1}
    .book-cover{width:160px;height:220px;border-radius:var(--radius);overflow:hidden;position:relative;box-shadow:var(--shadow-md)}
    .book-cover-bg{position:absolute;inset:0}
    .book-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:1}
    .book-cover-spine{position:absolute;left:0;top:0;bottom:0;width:10px;background:rgba(0,0,0,0.25);z-index:2}
    .book-cover-gradient{position:absolute;inset:0;background:linear-gradient(0deg,rgba(0,0,0,0.5) 0%,transparent 50%);z-index:2}
    .book-cover-init{position:absolute;bottom:10px;left:14px;font-family:var(--font-serif);font-size:1.4rem;font-weight:700;color:rgba(255,255,255,0.85);z-index:3;text-shadow:0 2px 8px rgba(0,0,0,0.4)}
    .badge-deja{position:absolute;top:6px;right:6px;z-index:5;width:22px;height:22px;background:var(--sage);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.2)}
    .badge-deja svg{width:10px;height:10px;color:#fff}
    .book-overlay{position:absolute;inset:0;background:rgba(26,20,16,0.55);border-radius:var(--radius);opacity:0;transition:opacity 0.2s;z-index:4;display:flex;align-items:center;justify-content:center}
    .overlay-btn{padding:8px 16px;background:var(--accent);color:#fff;border:none;border-radius:99px;font-family:var(--font-sans);font-size:0.72rem;font-weight:600;cursor:pointer;transition:background 0.15s;white-space:nowrap}
    .overlay-btn:hover{background:var(--accent-deep)}
    .book-info{padding:8px 2px 0}
    .book-info-title{font-family:var(--font-serif);font-size:0.82rem;font-weight:500;color:var(--ink);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px}
    .book-info-author{font-size:0.68rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px}
    .book-info-stars{display:flex;align-items:center;gap:2px}
    .star{font-size:0.6rem}.star.lit{color:var(--gold)}.star.dim{color:var(--border-warm)}
    .star-score{font-size:0.62rem;color:var(--muted-light);margin-left:2px}
    /* SKELETON */
    .skeleton-card{width:160px;flex-shrink:0}
    .skeleton-cover{width:160px;height:220px;border-radius:var(--radius);background:linear-gradient(90deg,var(--border) 25%,var(--border-warm) 50%,var(--border) 75%);background-size:200% 100%;animation:shimmer 1.4s infinite}
    .skeleton-line{height:10px;border-radius:99px;background:linear-gradient(90deg,var(--border) 25%,var(--border-warm) 50%,var(--border) 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;margin-top:8px}
    .skeleton-line.short{width:60%}
    /* SEARCH RESULTS */
    .search-results-section{padding:0 48px;display:none}
    .search-results-section.show{display:block}
    .search-results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:20px;margin-top:20px}
    .search-back{display:inline-flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;color:var(--muted);cursor:pointer;border:none;background:none;font-family:var(--font-sans);padding:0;margin-bottom:16px;transition:color 0.15s}
    .search-back:hover{color:var(--ink)}
    .search-back svg{width:14px;height:14px}
    /* COUVERTURES */
    .cv-manga{background:linear-gradient(145deg,#c8a020,#8a6808)}.cv-romance{background:linear-gradient(145deg,#c83070,#880028)}.cv-crime{background:linear-gradient(145deg,#283880,#101840)}.cv-scifi{background:linear-gradient(145deg,#28a8a0,#086860)}.cv-fantasy{background:linear-gradient(145deg,#3870c0,#1a4080)}.cv-roman{background:linear-gradient(145deg,#c84030,#8c1e20)}.cv-autres{background:linear-gradient(145deg,#8a7b6e,#4a3a2e)}
    /* TOAST */
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:#f5f0e8;padding:12px 22px;border-radius:99px;font-size:0.82rem;font-weight:500;box-shadow:var(--shadow-lg);z-index:999;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s;opacity:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
    .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    .toast svg{width:14px;height:14px;color:var(--sage)}
    @media(max-width:768px){.page-wrap{padding:32px 0 60px}.site-header,.nav-inner{padding-inline:24px}.hero-search-section,.section-head,.search-results-section{padding-left:24px;padding-right:24px}.slide-track{padding-left:24px;padding-right:24px}.slide-arrow{display:none}}
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
        <div class="mega-left"><div class="mega-badge">Explorer</div><h3 class="mega-heading">Ma bibliothèque</h3><p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture, garde tes favoris.</p></div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card" style="background:var(--bg-warm)"><span class="mega-icon" style="background:var(--lav-light);color:var(--lav)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="decouvrir.php" class="active">Découvrir</a>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<div class="page-wrap">
  <div class="hero-search-section">
    <div class="hero-label">Catalogue mondial</div>
    <h1 class="hero-title">Découvrir de <em>nouveaux livres</em></h1>
    <div class="search-bar">
      <div class="search-bar-wrap">
        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-input" id="searchInput" type="text" placeholder="Titre, auteur, série…" autocomplete="off"/>
      </div>
      <button class="search-btn" onclick="doSearch()">Rechercher</button>
    </div>
  </div>

  <div class="search-results-section" id="searchResultsSection">
    <button class="search-back" onclick="hideSearch()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Retour aux genres
    </button>
    <div id="searchResultsCount" style="font-size:0.82rem;color:var(--muted);margin-bottom:8px"></div>
    <div class="search-results-grid" id="searchResultsGrid"></div>
  </div>

  <div id="genreSections">
    <?php foreach ($genres as $g): ?>
    <div class="genre-section">
      <div class="section-head">
        <div class="section-title-wrap">
          <div class="section-dot" style="background:<?= $g['dot'] ?>"></div>
          <div class="section-title"><?= $g['label'] ?></div>
        </div>
        <button class="section-see-all" onclick="searchGenre('<?= addslashes($g['query']) ?>','<?= $g['label'] ?>')">
          Voir plus <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>
      <div class="slide-wrap">
        <button class="slide-arrow left" onclick="slide('track-<?= $g['id'] ?>',-1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="slide-track" id="track-<?= $g['id'] ?>">
          <?php for($i=0;$i<8;$i++): ?>
          <div class="skeleton-card"><div class="skeleton-cover"></div><div class="skeleton-line"></div><div class="skeleton-line short"></div></div>
          <?php endfor ?>
        </div>
        <button class="slide-arrow right" onclick="slide('track-<?= $g['id'] ?>',1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
    </div>
    <?php endforeach ?>
  </div>
</div>

<div class="toast" id="toast">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
const dejaLib = new Set(<?= json_encode($dejaDansLib) ?>);
const genresList = <?= json_encode($genres) ?>;

function estDeja(t,a){return dejaLib.has((t+'|'+a).toLowerCase().trim())}
function initials(t){return t.split(' ').filter(w=>w.length>2).slice(0,2).map(w=>w[0].toUpperCase()).join('')||t.slice(0,2).toUpperCase()}
function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}
function starsHtml(r){
  if(!r)return '';
  const rounded=Math.round(r*2)/2;
  return Array.from({length:5},(_,i)=>`<span class="star ${i<Math.floor(rounded)?'lit':i<rounded?'lit':'dim'}">★</span>`).join('')+`<span class="star-score">${r.toFixed(1)}</span>`;
}
function showToast(msg){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3000)}
function slide(trackId,dir){const t=document.getElementById(trackId);if(t)t.scrollBy({left:dir*600,behavior:'smooth'})}

function makeCard(doc,defaultCv){
  const titre=(doc.title||'').trim();
  const auteur=((doc.author_name||[])[0]||'Auteur inconnu').trim();
  const coverId=doc.cover_i;
  const coverUrl=coverId?`https://covers.openlibrary.org/b/id/${coverId}-M.jpg`:null;
  const rating=doc.ratings_average?Math.round(doc.ratings_average*10)/10:null;
  const deja=estDeja(titre,auteur);
  const olKey=doc.key||'';
  const init=initials(titre);
  const params=new URLSearchParams({key:olKey,titre,auteur,cover:coverUrl||'',cv:defaultCv,rating:rating||''});

  const card=document.createElement('div');
  card.className='book-card';
  card.innerHTML=`
    <div class="book-cover">
      <div class="book-cover-bg ${defaultCv}"></div>
      ${coverUrl?`<img class="book-cover-img" src="${coverUrl}" alt="" loading="lazy" onerror="this.style.display='none'"/>`:''}
      <div class="book-cover-spine"></div>
      <div class="book-cover-gradient"></div>
      <div class="book-cover-init">${init}</div>
      ${deja?`<div class="badge-deja"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg></div>`:''}
      <div class="book-overlay"><button class="overlay-btn">Voir le livre</button></div>
    </div>
    <div class="book-info">
      <div class="book-info-title">${escHtml(titre)}</div>
      <div class="book-info-author">${escHtml(auteur)}</div>
      <div class="book-info-stars">${starsHtml(rating)}</div>
    </div>`;
  card.addEventListener('click',()=>window.location.href='livre-detail.php?'+params.toString());
  return card;
}

async function loadGenre(genreId,query,cv){
  const track=document.getElementById('track-'+genreId);
  if(!track)return;
  try{
    const url=`https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&limit=24&fields=title,author_name,cover_i,ratings_average,key`;
    const res=await fetch(url);
    const data=await res.json();
    const docs=(data.docs||[]).filter(d=>d.title&&d.author_name).sort(()=>Math.random()-0.5);
    track.innerHTML='';
    if(!docs.length){track.innerHTML='<div style="padding:20px;color:var(--muted);font-size:0.82rem">Aucun résultat</div>';return}
    docs.forEach(doc=>track.appendChild(makeCard(doc,cv)));
  }catch(e){track.innerHTML='<div style="padding:20px;color:var(--muted);font-size:0.82rem">Erreur de chargement</div>'}
}

async function searchGenre(query,label){
  const section=document.getElementById('searchResultsSection');
  const grid=document.getElementById('searchResultsGrid');
  const count=document.getElementById('searchResultsCount');
  section.classList.add('show');
  document.getElementById('genreSections').style.display='none';
  grid.innerHTML='<div style="grid-column:1/-1;padding:40px;text-align:center"><div style="width:32px;height:32px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 12px"></div><div style="font-size:0.82rem;color:var(--muted)">Chargement…</div></div>';
  count.textContent=label;
  try{
    const url=`https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&limit=60&fields=title,author_name,cover_i,ratings_average,key`;
    const res=await fetch(url);
    const data=await res.json();
    const docs=(data.docs||[]).filter(d=>d.title&&d.author_name);
    count.textContent=`${label} — ${docs.length} livres`;
    grid.innerHTML='';
    const cv=genresList.find(g=>g.label===label)?.cv||'cv-roman';
    docs.forEach(doc=>{
      const c=makeCard(doc,cv);
      c.style.width='100%';
      grid.appendChild(c);
    });
  }catch(e){grid.innerHTML='<div style="grid-column:1/-1;color:var(--muted);padding:20px">Erreur de connexion.</div>'}
}

function doSearch(){
  const q=document.getElementById('searchInput').value.trim();
  if(!q)return;
  searchGenre(q,`Résultats pour "${q}"`);
}

function hideSearch(){
  document.getElementById('searchResultsSection').classList.remove('show');
  document.getElementById('genreSections').style.display='';
  document.getElementById('searchInput').value='';
}

document.getElementById('searchInput').addEventListener('keydown',e=>{if(e.key==='Enter')doSearch()});

// Charger tous les genres
genresList.forEach(g=>loadGenre(g.id,g.query,g.cv));

// Si on arrive depuis recommandations.php avec un titre à chercher
const hashQ = decodeURIComponent(location.hash.replace('#search_',''));
if (hashQ && hashQ !== location.hash) {
  document.getElementById('searchInput').value = hashQ;
  setTimeout(() => searchGenre(hashQ, `"${hashQ}"`), 800);
}
</script>
</body>
</html>