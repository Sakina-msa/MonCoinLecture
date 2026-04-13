<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];

// Livres déjà dans la BDD de l'utilisateur (pour afficher "Déjà ajouté")
$db = getDB();
$stmt = $db->prepare("SELECT titre, auteur FROM livres WHERE user_id = ?");
$stmt->execute([$uid]);
$dejaDansLib = [];
foreach ($stmt->fetchAll() as $row) {
    $dejaDansLib[] = strtolower(trim($row['titre']) . '|' . trim($row['auteur']));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recherche — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--ink:#1a1410;--ink-soft:#3d322a;--muted:#8a7b6e;--muted-light:#b5a89b;--bg:#faf7f2;--bg-warm:#f3ede3;--bg-card:#fffcf7;--border:#e8e0d4;--border-warm:#d4c8b8;--accent:#c4602a;--accent-light:#fdf0e8;--accent-deep:#9e3e14;--gold:#b8933f;--sage:#5a9e6a;--sage-light:#edf7ef;--lav:#8b6ec4;--lav-light:#f2edfb;--font-serif:'Playfair Display',Georgia,serif;--font-sans:'Instrument Sans',system-ui,sans-serif;--radius:12px;--radius-lg:18px;--shadow-sm:0 1px 4px rgba(26,20,16,0.06);--shadow-md:0 4px 20px rgba(26,20,16,0.09);--shadow-lg:0 12px 48px rgba(26,20,16,0.14)}
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased;min-height:100vh}

    /* HEADER */
    .site-header{position:sticky;top:0;z-index:100;background:rgba(250,247,242,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px}
    .logo{display:flex;align-items:baseline;gap:10px}.logo-mark{font-family:var(--font-serif);font-size:1.25rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}.logo-mark em{font-style:italic;color:var(--accent)}.logo-divider{width:1px;height:14px;background:var(--border-warm)}.logo-sub{font-size:0.75rem;color:var(--muted);letter-spacing:0.06em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}
    .header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:7px 18px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}
    .btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border-warm)}.btn-ghost:hover{background:var(--bg-warm)}.btn-accent{background:var(--ink);color:var(--bg)}.btn-accent:hover{background:var(--ink-soft)}

    /* NAV */
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}.nav-inner{max-width:1400px;margin:0 auto;padding:0 48px;display:flex;align-items:center;gap:4px;height:46px}
    .nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:6px;letter-spacing:0.03em;transition:all 0.15s}.nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}.nav-inner a.active{color:var(--accent);background:var(--accent-light)}
    .nav-dropdown{position:relative}.nav-dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);left:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;width:480px;flex-direction:row;z-index:200}.nav-dropdown:hover .nav-dropdown-menu{display:flex}
    .mega-left{flex:1;padding-right:20px;border-right:1px solid var(--border)}.mega-badge{display:inline-block;font-size:0.65rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--accent);background:var(--accent-light);padding:3px 10px;border-radius:99px;margin-bottom:10px}.mega-heading{font-family:var(--font-serif);font-size:1rem;font-weight:500;color:var(--ink);margin-bottom:8px}.mega-text{font-size:0.78rem;color:var(--muted);line-height:1.55}.mega-links{display:grid;grid-template-columns:1fr 1fr;gap:6px;flex:1.2}.mega-card{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;transition:background 0.15s}.mega-card:hover{background:var(--bg-warm)}.mega-icon{width:32px;height:32px;border-radius:8px;background:var(--bg-warm);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}.mega-icon svg{width:15px;height:15px}.mega-card-title{display:block;font-size:0.8rem;font-weight:600;color:var(--ink)}.mega-card-desc{display:block;font-size:0.7rem;color:var(--muted)}

    /* PAGE */
    .page-wrap{max-width:1320px;margin:0 auto;padding:44px 48px 80px}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    /* HERO SEARCH */
    .search-hero{text-align:center;padding:48px 0 40px;animation:fadeUp 0.5s ease both}
    .search-eyebrow{font-size:0.7rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--muted);margin-bottom:14px}
    .search-title{font-family:var(--font-serif);font-size:clamp(2rem,4vw,3.2rem);font-weight:700;color:var(--ink);letter-spacing:-0.025em;line-height:1.1;margin-bottom:28px}
    .search-title em{font-style:italic;font-weight:400;color:var(--accent)}
    .search-bar-big{display:flex;max-width:680px;margin:0 auto 20px;border:2px solid var(--border-warm);border-radius:99px;background:#fff;box-shadow:var(--shadow-md);overflow:hidden;transition:border-color 0.2s,box-shadow 0.2s}
    .search-bar-big:focus-within{border-color:var(--accent);box-shadow:0 0 0 4px rgba(196,96,42,0.12)}
    .search-bar-wrap{flex:1;position:relative}
    .search-bar-icon{position:absolute;left:20px;top:50%;transform:translateY(-50%);color:var(--muted-light);pointer-events:none}
    .search-bar-input{width:100%;height:58px;padding:0 20px 0 52px;border:none;background:transparent;font-family:var(--font-sans);font-size:1rem;color:var(--ink);outline:none}
    .search-bar-input::placeholder{color:var(--muted-light)}
    .search-bar-btn{height:58px;padding:0 32px;background:var(--accent);color:#fff;border:none;font-family:var(--font-sans);font-size:0.88rem;font-weight:600;cursor:pointer;transition:background 0.18s;letter-spacing:0.03em;border-radius:0 99px 99px 0;white-space:nowrap}
    .search-bar-btn:hover{background:var(--accent-deep)}
    .search-bar-btn:disabled{opacity:0.6;cursor:not-allowed}

    /* Tags suggérés */
    .search-tags{display:flex;flex-wrap:wrap;justify-content:center;gap:8px}
    .search-tag{font-size:0.75rem;font-weight:500;padding:6px 16px;border-radius:99px;border:1.5px solid var(--border-warm);color:var(--muted);cursor:pointer;transition:all 0.15s;background:transparent}
    .search-tag:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-light)}

    /* SOURCE OPEN LIBRARY */
    .source-band{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:32px;font-size:0.75rem;color:var(--muted)}
    .source-band a{color:var(--accent);text-decoration:none;font-weight:600}
    .source-dot{width:6px;height:6px;border-radius:50%;background:var(--sage);animation:pulse 2s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}

    /* RÉSULTATS */
    .results-header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:16px}
    .results-count{font-size:0.78rem;color:var(--muted);font-weight:500}
    .results-count strong{color:var(--ink);font-weight:700}

    /* GRILLE */
    .results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;animation:fadeUp 0.5s 0.1s ease both}
    .result-card{background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:transform 0.22s cubic-bezier(0.34,1.56,0.64,1),box-shadow 0.22s;position:relative}
    .result-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}

    /* Couverture */
    .result-cover{height:140px;position:relative;overflow:hidden;background:#e8e0d4}
    .result-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .result-cover-art{position:absolute;inset:0}
    .result-cover-art::after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(0,0,0,0.45) 0%,transparent 55%)}
    .result-cover::before{content:'';position:absolute;left:0;top:0;bottom:0;width:11px;background:rgba(0,0,0,0.22);z-index:1}
    .result-cover-init{position:absolute;bottom:10px;left:18px;font-family:var(--font-serif);font-size:1.3rem;font-weight:700;color:rgba(255,255,255,0.85);z-index:2;text-shadow:0 2px 6px rgba(0,0,0,0.3);letter-spacing:-0.02em}

    /* Badge "Déjà ajouté" */
    .badge-already{position:absolute;top:8px;right:8px;z-index:3;font-size:0.56rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;padding:3px 8px;border-radius:99px;background:var(--sage-light);color:var(--sage);border:1px solid #8dd49d}

    /* Corps carte */
    .result-body{padding:14px 16px 50px}
    .result-title{font-family:var(--font-serif);font-size:0.9rem;font-weight:500;color:var(--ink);line-height:1.3;margin-bottom:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .result-author{font-size:0.7rem;color:var(--muted);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .result-meta{display:flex;gap:6px;flex-wrap:wrap}
    .result-tag{font-size:0.6rem;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;padding:2px 7px;border-radius:99px;background:var(--bg-warm);border:1px solid var(--border);color:var(--muted)}
    .result-year{font-size:0.65rem;color:var(--muted-light)}

    /* Bouton ajouter */
    .result-add-btn{position:absolute;bottom:12px;left:16px;right:16px;padding:9px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-family:var(--font-sans);font-size:0.76rem;font-weight:600;cursor:pointer;transition:all 0.18s;display:flex;align-items:center;justify-content:center;gap:6px;letter-spacing:0.02em}
    .result-add-btn:hover{background:var(--accent-deep)}
    .result-add-btn:disabled{background:var(--sage);cursor:default}
    .result-add-btn svg{width:12px;height:12px;flex-shrink:0}

    /* Couleurs couvertures */
    .cv-roman{background:linear-gradient(145deg,#c84030,#8c1e20)}.cv-fantasy{background:linear-gradient(145deg,#3870c0,#1a4080)}.cv-manga{background:linear-gradient(145deg,#c8a020,#8a6808)}.cv-crime{background:linear-gradient(145deg,#283880,#101840)}.cv-romance{background:linear-gradient(145deg,#c83070,#880028)}.cv-scifi{background:linear-gradient(145deg,#28a8a0,#086860)}.cv-classique{background:linear-gradient(145deg,#9060c0,#481880)}.cv-default{background:linear-gradient(145deg,#8a7b6e,#4a3a2e)}

    /* Spinner */
    .spinner{display:none;flex-direction:column;align-items:center;gap:16px;padding:60px 0;grid-column:1/-1}
    .spinner.show{display:flex}
    .spin{width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.8s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
    .spinner-text{font-size:0.85rem;color:var(--muted)}

    /* Empty */
    .empty-state{text-align:center;padding:80px 32px;grid-column:1/-1}
    .empty-icon{width:72px;height:72px;background:var(--bg-warm);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px}
    .empty-icon svg{width:32px;height:32px;color:var(--muted-light)}
    .empty-title{font-family:var(--font-serif);font-size:1.3rem;font-weight:500;color:var(--ink);margin-bottom:8px}
    .empty-sub{font-size:0.85rem;color:var(--muted);line-height:1.65}

    /* Bouton détail dans les cartes de recherche */
    .result-detail-btn{position:absolute;top:8px;left:8px;z-index:3;padding:3px 9px;background:rgba(0,0,0,0.45);color:rgba(255,255,255,0.9);border:none;border-radius:99px;font-size:0.58rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:background 0.15s;backdrop-filter:blur(4px)}
    .result-detail-btn:hover{background:var(--accent)}
    /* Toast */
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:#f5f0e8;padding:12px 22px;border-radius:99px;font-size:0.82rem;font-weight:500;box-shadow:var(--shadow-lg);z-index:999;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s;opacity:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
    .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    .toast svg{width:14px;height:14px;color:var(--sage)}

    @media(max-width:768px){.page-wrap{padding:32px 24px 60px}.site-header,.nav-inner{padding-inline:24px}.results-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}}
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
        <div class="mega-left"><div class="mega-badge">Explorer</div><h3 class="mega-heading">Ma bibliothèque</h3><p class="mega-text">Retrouve tous tes livres, organise tes envies de lecture, garde tes favoris.</p></div>
        <div class="mega-links">
          <a href="mes-livres.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><span><span class="mega-card-title">Mes livres</span><span class="mega-card-desc">Toute ta bibliothèque</span></span></a>
          <a href="favoris.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><span><span class="mega-card-title">Favoris</span><span class="mega-card-desc">Tes livres préférés</span></span></a>
          <a href="decouvrir.php" class="mega-card"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Découvrir</span><span class="mega-card-desc">Nouveautés & catalogue</span></span></a>
          <a href="recherche.php" class="mega-card" style="background:var(--bg-warm)"><span class="mega-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span><span><span class="mega-card-title">Recherche</span><span class="mega-card-desc">Titre, auteur, genre</span></span></a>
        </div>
      </div>
    </div>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<div class="page-wrap">

  <!-- HERO -->
  <div class="search-hero">
    <div class="search-eyebrow">Rechercher</div>
    <h1 class="search-title">Retrouve n'importe quel <em>livre</em></h1>
    <div class="search-bar-big">
      <div class="search-bar-wrap">
        <svg class="search-bar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-bar-input" id="searchMain" type="text" placeholder="Titre, auteur, ISBN…" autocomplete="off"/>
      </div>
      <button class="search-bar-btn" id="searchBtn" onclick="doSearch()">Chercher</button>
    </div>
    <!-- Suggestions rapides -->
    <div class="search-tags">
      <button class="search-tag" onclick="quickSearch('Harry Potter')">Harry Potter</button>
      <button class="search-tag" onclick="quickSearch('Victor Hugo')">Victor Hugo</button>
      <button class="search-tag" onclick="quickSearch('Dune')">Dune</button>
      <button class="search-tag" onclick="quickSearch('Agatha Christie')">Agatha Christie</button>
      <button class="search-tag" onclick="quickSearch('One Piece')">One Piece</button>
      <button class="search-tag" onclick="quickSearch('Le Petit Prince')">Le Petit Prince</button>
    </div>
  </div>

  <!-- Source -->
  <div class="source-band">
    <div class="source-dot"></div>
    Résultats fournis par <a href="https://openlibrary.org" target="_blank">Open Library</a> — plus de 20 millions de livres
  </div>

  <!-- Compteur -->
  <div class="results-header">
    <div class="results-count" id="resultsCount"></div>
  </div>

  <!-- Grille -->
  <div class="results-grid" id="resultsGrid">
    <div class="empty-state">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </div>
      <div class="empty-title">Recherche un livre</div>
      <div class="empty-sub">Tape un titre, un auteur ou un ISBN dans la barre ci-dessus.</div>
    </div>
  </div>

</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
// ── Livres déjà dans la BDD (PHP → JS) ────────────
const dejaLib = new Set(<?= json_encode($dejaDansLib) ?>);

function estDejaAjoute(titre, auteur) {
  return dejaLib.has((titre + '|' + auteur).toLowerCase().trim());
}

// ── Couleur par sujet/genre ────────────────────────
function genreClass(subjects) {
  if (!subjects || !subjects.length) return 'cv-default';
  const s = subjects.join(' ').toLowerCase();
  if (s.includes('manga') || s.includes('comic') || s.includes('graphic')) return 'cv-manga';
  if (s.includes('fantasy') || s.includes('magic') || s.includes('dragon')) return 'cv-fantasy';
  if (s.includes('science fiction') || s.includes('space') || s.includes('robot')) return 'cv-scifi';
  if (s.includes('crime') || s.includes('detective') || s.includes('mystery') || s.includes('thriller')) return 'cv-crime';
  if (s.includes('romance') || s.includes('love')) return 'cv-romance';
  if (s.includes('classic') || s.includes('classique') || s.includes('19th')) return 'cv-classique';
  return 'cv-roman';
}

// ── Initiales depuis le titre ──────────────────────
function initials(titre) {
  const parts = titre.split(' ').filter(w => w.length > 2).slice(0, 2).map(w => w[0].toUpperCase());
  return parts.join('') || titre.slice(0, 2).toUpperCase();
}

// ── Recherche Open Library ─────────────────────────
async function doSearch() {
  const q = document.getElementById('searchMain').value.trim();
  if (!q) return;

  const btn  = document.getElementById('searchBtn');
  const grid = document.getElementById('resultsGrid');
  const count = document.getElementById('resultsCount');

  btn.disabled = true;
  btn.textContent = 'Recherche…';
  count.innerHTML = '';

  // Spinner
  grid.innerHTML = `<div class="spinner show"><div class="spin"></div><div class="spinner-text">Recherche dans Open Library…</div></div>`;

  try {
    const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=100`;
    const res  = await fetch(url);
    const data = await res.json();

    const docs = (data.docs || []).filter(d => d.title && (d.author_name || []).length > 0);

    if (!docs.length) {
      grid.innerHTML = `<div class="empty-state"><div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div><div class="empty-title">Aucun résultat pour "${escHtml(q)}"</div><div class="empty-sub">Essaie un autre terme ou vérifie l'orthographe.</div></div>`;
      count.innerHTML = '';
      return;
    }

    count.innerHTML = `<strong>${docs.length}</strong> livre${docs.length > 1 ? 's' : ''} trouvé${docs.length > 1 ? 's' : ''}`;

    grid.innerHTML = docs.map(doc => {
      const titre  = doc.title || '';
      const auteur = (doc.author_name || [])[0] || 'Auteur inconnu';
      const annee  = doc.first_publish_year || '';
      const init   = initials(titre);
      const cvCls  = genreClass(doc.subject);
      const coverId= doc.cover_i;
      const coverUrl = coverId ? `https://covers.openlibrary.org/b/id/${coverId}-M.jpg` : null;
      const deja   = estDejaAjoute(titre, auteur);

      // Encode les données pour le bouton
      const dataB64 = btoa(unescape(encodeURIComponent(JSON.stringify({
        titre, auteur,
        genre: guessGenre(doc.subject),
        description: (doc.subject || []).slice(0, 3).join(', ')
      }))));

      return `<div class="result-card">
        <div class="result-cover">
          ${coverUrl
            ? `<img class="result-cover-img" src="${coverUrl}" alt="${escHtml(titre)}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='block'" /><div class="result-cover-art ${cvCls}" style="display:none"></div>`
            : `<div class="result-cover-art ${cvCls}"></div>`
          }
          <div class="result-cover-init">${init}</div>
          ${deja ? `<span class="badge-already">✓ Dans ta bibli</span>` : ''}
          ${coverId ? `<a href="livre-detail.php?key=${encodeURIComponent(doc.key||'')}&titre=${encodeURIComponent(titre)}&auteur=${encodeURIComponent(auteur)}&cover=${encodeURIComponent(coverUrl||'')}&cv=${genreClass(doc.subject)}&rating=${doc.ratings_average||''}" class="result-detail-btn">Voir le livre</a>` : ''}
        </div>
        <div class="result-body">
          <div class="result-title">${escHtml(titre)}</div>
          <div class="result-author">${escHtml(auteur)}</div>
          <div class="result-meta">
            ${annee ? `<span class="result-year">${annee}</span>` : ''}
            ${doc.language && doc.language.includes('fre') ? `<span class="result-tag">Français</span>` : ''}
          </div>
        </div>
        <button class="result-add-btn" onclick="ajouterLivre(this, '${dataB64}')" ${deja ? 'disabled' : ''}>
          ${deja
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg> Déjà dans ta bibliothèque`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg> Ajouter à ma bibliothèque`
          }
        </button>
      </div>`;
    }).join('');

  } catch(e) {
    grid.innerHTML = `<div class="empty-state"><div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></div><div class="empty-title">Erreur de connexion</div><div class="empty-sub">Vérifie ta connexion internet et réessaie.</div></div>`;
  } finally {
    btn.disabled = false;
    btn.textContent = 'Chercher';
  }
}

// ── Deviner le genre depuis les sujets Open Library ──
function guessGenre(subjects) {
  if (!subjects || !subjects.length) return '';
  const s = subjects.join(' ').toLowerCase();
  if (s.includes('manga') || s.includes('comic')) return 'manga';
  if (s.includes('fantasy') || s.includes('magic')) return 'fantasy';
  if (s.includes('science fiction') || s.includes('space')) return 'scifi';
  if (s.includes('crime') || s.includes('detective') || s.includes('mystery')) return 'crime';
  if (s.includes('romance') || s.includes('love story')) return 'romance';
  if (s.includes('classic') || s.includes('19th century')) return 'classique';
  return 'roman';
}

// ── Ajouter en BDD via api_livres.php ─────────────
async function ajouterLivre(btn, dataB64) {
  let livre;
  try { livre = JSON.parse(decodeURIComponent(escape(atob(dataB64)))); }
  catch(e) { showToast('Erreur de données.', false); return; }

  btn.disabled = true;
  btn.innerHTML = '<div style="width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite"></div> Ajout…';

  const body = new FormData();
  body.append('action',      'add_livre');
  body.append('titre',       livre.titre);
  body.append('auteur',      livre.auteur);
  body.append('genre',       livre.genre || '');
  body.append('statut',      'alire');
  body.append('note',        '0');
  body.append('commentaire', '');

  try {
    const res  = await fetch('api_livres.php', { method: 'POST', body });
    const data = await res.json();

    if (data.error) { showToast(data.error, false); btn.disabled = false; return; }

    // Met à jour le bouton et le set local
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg> Déjà dans ta bibliothèque';
    dejaLib.add((livre.titre + '|' + livre.auteur).toLowerCase().trim());

    // Ajoute le badge sur la carte
    const card = btn.closest('.result-card');
    if (card && !card.querySelector('.badge-already')) {
      const badge = document.createElement('span');
      badge.className = 'badge-already';
      badge.textContent = '✓ Dans ta bibli';
      card.querySelector('.result-cover').appendChild(badge);
    }

    showToast(`"${livre.titre}" ajouté à ta bibliothèque !`, true);
  } catch(e) {
    showToast('Erreur réseau, réessaie.', false);
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg> Ajouter à ma bibliothèque';
  }
}

// ── Suggestion rapide ──────────────────────────────
function quickSearch(q) {
  document.getElementById('searchMain').value = q;
  doSearch();
}

// ── Touche Entrée ──────────────────────────────────
document.getElementById('searchMain').addEventListener('keydown', e => {
  if (e.key === 'Enter') doSearch();
});

// ── Échapper HTML ──────────────────────────────────
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Toast ──────────────────────────────────────────
function showToast(msg, ok = true) {
  const t = document.getElementById('toast');
  const icon = t.querySelector('svg');
  document.getElementById('toastMsg').textContent = msg;
  icon.style.color = ok ? 'var(--sage)' : 'var(--accent)';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ── URL param ?q= (depuis la page d'accueil) ───────
const urlQ = new URLSearchParams(location.search).get('q');
if (urlQ) {
  document.getElementById('searchMain').value = urlQ;
  doSearch();
}
</script>
</body>
</html>