<?php
require_once __DIR__ . '/config.php';
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$uid = $_SESSION['user_id'];

// Paramètres passés depuis decouvrir.php
$olKey   = $_GET['key']    ?? '';
$titre   = htmlspecialchars($_GET['titre']  ?? '');
$auteur  = htmlspecialchars($_GET['auteur'] ?? '');
$cover   = htmlspecialchars(filter_var($_GET['cover'] ?? '', FILTER_SANITIZE_URL));
$cv      = preg_replace('/[^a-z-]/', '', $_GET['cv'] ?? 'cv-roman');
$rating  = (float)($_GET['rating'] ?? 0);

// Livres déjà en BDD
$db   = getDB();
$stmt = $db->prepare("SELECT titre, auteur FROM livres WHERE user_id = ?");
$stmt->execute([$uid]);
$dejaDansLib = [];
foreach ($stmt->fetchAll() as $row) {
    $dejaDansLib[] = strtolower(trim($row['titre']).'|'.trim($row['auteur']));
}
$deja = in_array(strtolower($titre.'|'.$auteur), $dejaDansLib);

function initiales(string $t): string {
    $w = array_filter(explode(' ', $t), fn($w) => strlen($w) > 2);
    return substr(implode('', array_map(fn($w) => strtoupper($w[0]), array_slice($w,0,2))), 0, 2) ?: strtoupper(substr($t,0,2));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $titre ?> — Mon Coin Lecture</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,700&family=Instrument+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--ink:#1a1410;--ink-soft:#3d322a;--muted:#8a7b6e;--muted-light:#b5a89b;--bg:#faf7f2;--bg-warm:#f3ede3;--bg-card:#fffcf7;--border:#e8e0d4;--border-warm:#d4c8b8;--accent:#c4602a;--accent-light:#fdf0e8;--accent-deep:#9e3e14;--gold:#b8933f;--sage:#5a9e6a;--sage-light:#edf7ef;--lav:#8b6ec4;--font-serif:'Playfair Display',Georgia,serif;--font-sans:'Instrument Sans',system-ui,sans-serif;--radius:12px;--radius-lg:18px;--shadow-sm:0 1px 4px rgba(26,20,16,0.06);--shadow-md:0 4px 20px rgba(26,20,16,0.09);--shadow-lg:0 12px 48px rgba(26,20,16,0.14)}
    html{font-size:15px;scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);-webkit-font-smoothing:antialiased;min-height:100vh}
    .site-header{position:sticky;top:0;z-index:100;background:rgba(250,247,242,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px}
    .logo{display:flex;align-items:baseline;gap:10px}.logo-mark{font-family:var(--font-serif);font-size:1.25rem;font-weight:500;color:var(--ink);letter-spacing:-0.01em}.logo-mark em{font-style:italic;color:var(--accent)}.logo-divider{width:1px;height:14px;background:var(--border-warm)}.logo-sub{font-size:0.75rem;color:var(--muted);letter-spacing:0.06em;text-transform:uppercase;font-weight:500}
    .header-actions{display:flex;align-items:center;gap:10px}.header-btn{font-family:var(--font-sans);font-size:0.8rem;font-weight:500;padding:7px 18px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none}.btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border-warm)}.btn-ghost:hover{background:var(--bg-warm)}.btn-accent{background:var(--ink);color:var(--bg)}.btn-accent:hover{background:var(--ink-soft)}
    .site-nav{border-bottom:1px solid var(--border);background:var(--bg-card)}.nav-inner{max-width:1400px;margin:0 auto;padding:0 48px;display:flex;align-items:center;gap:4px;height:46px}.nav-inner a{font-size:0.8rem;font-weight:500;color:var(--muted);text-decoration:none;padding:5px 14px;border-radius:6px;letter-spacing:0.03em;transition:all 0.15s}.nav-inner a:hover{color:var(--ink);background:var(--bg-warm)}.nav-inner a.active{color:var(--accent);background:var(--accent-light)}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes spin{to{transform:rotate(360deg)}}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    /* PAGE */
    .page-wrap{max-width:1200px;margin:0 auto;padding:48px 48px 80px}
    /* RETOUR */
    .back-btn{display:inline-flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;color:var(--muted);text-decoration:none;margin-bottom:36px;transition:color 0.15s;border:none;background:none;cursor:pointer;font-family:var(--font-sans);padding:0}
    .back-btn:hover{color:var(--ink)}
    .back-btn svg{width:14px;height:14px}
    /* HERO LIVRE */
    .book-hero{display:grid;grid-template-columns:280px 1fr;gap:52px;align-items:start;margin-bottom:60px;animation:fadeUp 0.5s ease both}
    /* COUVERTURE */
    .cover-wrap{position:relative}
    .cover-img-big{width:280px;height:400px;border-radius:var(--radius-lg);overflow:hidden;position:relative;box-shadow:12px 16px 48px rgba(26,20,16,0.22),-3px 0 10px rgba(26,20,16,0.1)}
    .cover-bg{position:absolute;inset:0}
    .cover-bg::before{content:'';position:absolute;left:0;top:0;bottom:0;width:16px;background:rgba(0,0,0,0.28);z-index:1}
    .cover-real-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:2}
    .cover-init{position:absolute;bottom:20px;left:26px;font-family:var(--font-serif);font-size:2.4rem;font-weight:700;color:rgba(255,255,255,0.85);z-index:3;text-shadow:0 2px 12px rgba(0,0,0,0.4);letter-spacing:-0.03em}
    /* INFOS */
    .book-info{padding-top:8px}
    .book-genre-badge{display:inline-block;font-size:0.62rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;padding:4px 12px;border-radius:99px;background:var(--accent-light);color:var(--accent);border:1px solid #f0b080;margin-bottom:16px}
    .book-title-big{font-family:var(--font-serif);font-size:clamp(2rem,4vw,3rem);font-weight:700;color:var(--ink);letter-spacing:-0.03em;line-height:1.05;margin-bottom:8px}
    .book-author-big{font-size:1rem;color:var(--muted);margin-bottom:20px}
    /* Rating */
    .rating-row{display:flex;align-items:center;gap:12px;margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid var(--border)}
    .stars-big{display:flex;gap:3px}
    .star-big{font-size:1.1rem}.star-big.lit{color:var(--gold)}.star-big.dim{color:var(--border-warm)}
    .rating-score-big{font-family:var(--font-serif);font-size:1.3rem;font-weight:700;color:var(--ink)}
    .rating-count-big{font-size:0.78rem;color:var(--muted-light)}
    /* Description */
    .desc-section{margin-bottom:28px}
    .desc-label{font-size:0.65rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:8px}
    .desc-label::before{content:'';width:16px;height:1.5px;background:var(--border-warm)}
    .desc-text{font-size:0.9rem;color:var(--ink-soft);line-height:1.75}
    .desc-text.loading{color:var(--muted);font-style:italic}
    /* Boutons action */
    .action-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px}
    .btn-add-big{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:var(--accent);color:#fff;border:none;border-radius:99px;font-family:var(--font-sans);font-size:0.88rem;font-weight:600;cursor:pointer;transition:all 0.18s;letter-spacing:0.02em}
    .btn-add-big:hover{background:var(--accent-deep);transform:translateY(-1px);box-shadow:0 4px 16px rgba(196,96,42,0.3)}
    .btn-add-big:disabled{background:var(--sage);cursor:default;transform:none;box-shadow:none}
    .btn-add-big svg{width:16px;height:16px}
    .btn-ol{display:inline-flex;align-items:center;gap:6px;padding:13px 20px;background:transparent;color:var(--ink-soft);border:1.5px solid var(--border);border-radius:99px;font-family:var(--font-sans);font-size:0.82rem;font-weight:600;cursor:pointer;transition:all 0.18s;text-decoration:none}
    .btn-ol:hover{background:var(--bg-warm);border-color:var(--border-warm)}
    .btn-ol svg{width:14px;height:14px}
    /* Sujets */
    .tags-row{display:flex;flex-wrap:wrap;gap:6px}
    .tag-chip{font-size:0.62rem;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;padding:4px 10px;border-radius:99px;background:var(--bg-warm);border:1px solid var(--border);color:var(--muted)}
    /* SECTION SIMILAIRES */
    .similar-section{animation:fadeUp 0.5s 0.2s ease both}
    .similar-head{display:flex;align-items:center;gap:12px;margin-bottom:24px}
    .similar-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0}
    .similar-title{font-family:var(--font-serif);font-size:1.4rem;font-weight:500;color:var(--ink)}
    /* Grid similaires */
    .similar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:20px}
    .sim-card{cursor:pointer;transition:transform 0.22s cubic-bezier(0.34,1.56,0.64,1)}
    .sim-card:hover{transform:translateY(-5px)}
    .sim-card:hover .sim-overlay{opacity:1}
    .sim-cover{width:100%;aspect-ratio:2/3;border-radius:var(--radius);overflow:hidden;position:relative;box-shadow:var(--shadow-md);margin-bottom:8px}
    .sim-cover-bg{position:absolute;inset:0}
    .sim-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:1}
    .sim-cover-spine{position:absolute;left:0;top:0;bottom:0;width:8px;background:rgba(0,0,0,0.25);z-index:2}
    .sim-cover-init{position:absolute;bottom:8px;left:12px;font-family:var(--font-serif);font-size:1.1rem;font-weight:700;color:rgba(255,255,255,0.85);z-index:3;text-shadow:0 2px 6px rgba(0,0,0,0.4)}
    .sim-overlay{position:absolute;inset:0;background:rgba(26,20,16,0.5);opacity:0;transition:opacity 0.2s;z-index:4;display:flex;align-items:center;justify-content:center;border-radius:var(--radius)}
    .sim-overlay-btn{padding:6px 14px;background:var(--accent);color:#fff;border:none;border-radius:99px;font-size:0.68rem;font-weight:600;cursor:pointer;font-family:var(--font-sans)}
    .sim-title{font-family:var(--font-serif);font-size:0.78rem;font-weight:500;color:var(--ink);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px}
    .sim-author{font-size:0.65rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sim-stars{display:flex;align-items:center;gap:1px;margin-top:3px}
    .sim-star{font-size:0.55rem}.sim-star.lit{color:var(--gold)}.sim-star.dim{color:var(--border-warm)}
    /* Skeleton */
    .skeleton-sim{border-radius:var(--radius);background:linear-gradient(90deg,var(--border) 25%,var(--border-warm) 50%,var(--border) 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;aspect-ratio:2/3;margin-bottom:8px}
    .skeleton-line{height:10px;border-radius:99px;background:linear-gradient(90deg,var(--border) 25%,var(--border-warm) 50%,var(--border) 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;margin-top:6px}
    .skeleton-line.short{width:60%}
    /* COUVERTURES */
    .cv-manga{background:linear-gradient(145deg,#c8a020,#8a6808)}.cv-romance{background:linear-gradient(145deg,#c83070,#880028)}.cv-crime{background:linear-gradient(145deg,#283880,#101840)}.cv-scifi{background:linear-gradient(145deg,#28a8a0,#086860)}.cv-fantasy{background:linear-gradient(145deg,#3870c0,#1a4080)}.cv-roman{background:linear-gradient(145deg,#c84030,#8c1e20)}.cv-autres{background:linear-gradient(145deg,#8a7b6e,#4a3a2e)}
    /* TOAST */
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--ink);color:#f5f0e8;padding:12px 22px;border-radius:99px;font-size:0.82rem;font-weight:500;box-shadow:var(--shadow-lg);z-index:999;transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s;opacity:0;white-space:nowrap;display:flex;align-items:center;gap:8px}
    .toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    .toast svg{width:14px;height:14px;color:var(--sage)}
    @media(max-width:900px){.book-hero{grid-template-columns:1fr}.cover-img-big{width:100%;height:280px}.page-wrap{padding:32px 24px 60px}.site-header,.nav-inner{padding-inline:24px}}
  </style>
</head>
<body>
<?php include __DIR__ . '/header_nav.php'; ?>

<nav class="site-nav">
  <div class="nav-inner">
    <a href="index.php">Accueil</a>
    <a href="livres.php">Bibliothèque</a>
    <a href="decouvrir.php" class="active">Découvrir</a>
    <a href="recommandations.php">Recommandations</a>
  </div>
</nav>

<div class="page-wrap">

  <button class="back-btn" onclick="history.back()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Retour
  </button>

  <!-- HERO LIVRE -->
  <div class="book-hero">

    <!-- COUVERTURE -->
    <div class="cover-wrap">
      <div class="cover-img-big">
        <div class="cover-bg <?= $cv ?>"></div>
        <?php if ($cover): ?>
        <img class="cover-real-img" src="<?= $cover ?>" alt="<?= $titre ?>" onerror="this.style.display='none'"/>
        <?php endif ?>
        <div class="cover-init"><?= initiales($titre) ?></div>
      </div>
    </div>

    <!-- INFOS -->
    <div class="book-info">
      <span class="book-genre-badge" id="genreBadge">Chargement…</span>
      <h1 class="book-title-big"><?= $titre ?></h1>
      <p class="book-author-big"><?= $auteur ?></p>

      <?php if ($rating > 0): ?>
      <div class="rating-row">
        <div class="stars-big">
          <?php
          $r = round($rating * 2) / 2;
          for ($i = 1; $i <= 5; $i++):
            $cls = $i <= floor($r) ? 'lit' : ($i <= $r ? 'lit' : 'dim');
          ?>
          <span class="star-big <?= $cls ?>">★</span>
          <?php endfor ?>
        </div>
        <span class="rating-score-big"><?= number_format($rating, 1) ?></span>
        <span class="rating-count-big">/ 5 sur Open Library</span>
      </div>
      <?php endif ?>

      <div class="desc-section">
        <div class="desc-label">Résumé</div>
        <p class="desc-text loading" id="descText">Chargement du résumé…</p>
      </div>

      <div class="action-row">
        <button class="btn-add-big" id="btnAdd" onclick="ajouterLivre()" <?= $deja ? 'disabled' : '' ?>>
          <?php if ($deja): ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
          Déjà dans ta bibliothèque
          <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
          Ajouter à ma bibliothèque
          <?php endif ?>
        </button>
        <?php if ($olKey): ?>
        <a href="https://openlibrary.org<?= $olKey ?>" target="_blank" class="btn-ol">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Open Library
        </a>
        <?php endif ?>
      </div>

      <div class="tags-row" id="tagsList"></div>
    </div>
  </div>

  <!-- LIVRES SIMILAIRES -->
  <div class="similar-section">
    <div class="similar-head">
      <div class="similar-dot"></div>
      <div class="similar-title">Livres similaires</div>
    </div>
    <div class="similar-grid" id="similarGrid">
      <?php for($i=0;$i<8;$i++): ?>
      <div>
        <div class="skeleton-sim"></div>
        <div class="skeleton-line"></div>
        <div class="skeleton-line short"></div>
      </div>
      <?php endfor ?>
    </div>
  </div>

</div>

<div class="toast" id="toast">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
const TITRE  = <?= json_encode($titre) ?>;
const AUTEUR = <?= json_encode($auteur) ?>;
const OL_KEY = <?= json_encode($olKey) ?>;
const CV     = <?= json_encode($cv) ?>;
const COVER  = <?= json_encode($cover) ?>;

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function initials(t){return t.split(' ').filter(w=>w.length>2).slice(0,2).map(w=>w[0].toUpperCase()).join('')||t.slice(0,2).toUpperCase()}
function showToast(msg){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3000)}

function starsHtml(r,cls='sim-star'){
  if(!r)return '';
  const rounded=Math.round(r*2)/2;
  return Array.from({length:5},(_,i)=>`<span class="${cls} ${i<Math.floor(rounded)?'lit':i<rounded?'lit':'dim'}">★</span>`).join('');
}

// ── Charger les détails depuis Open Library ────────
async function loadDetails(){
  if(!OL_KEY)return;
  try{
    const res=await fetch(`https://openlibrary.org${OL_KEY}.json`);
    const data=await res.json();

    // Description
    const descEl=document.getElementById('descText');
    let desc=data.description;
    if(desc&&typeof desc==='object')desc=desc.value;
    if(desc){
      descEl.textContent=desc.length>600?desc.slice(0,600)+'…':desc;
      descEl.classList.remove('loading');
    } else {
      descEl.textContent='Aucun résumé disponible pour ce livre.';
      descEl.classList.remove('loading');
    }

    // Sujets
    const subjects=(data.subjects||[]).slice(0,8);
    const tagsList=document.getElementById('tagsList');
    tagsList.innerHTML=subjects.map(s=>`<span class="tag-chip">${escHtml(s)}</span>`).join('');

    // Genre badge
    const badge=document.getElementById('genreBadge');
    if(subjects.length){
      const s=subjects.join(' ').toLowerCase();
      let genre='Roman';
      if(s.includes('manga')||s.includes('comic'))genre='Manga';
      else if(s.includes('fantasy')||s.includes('magic'))genre='Fantasy';
      else if(s.includes('science fiction')||s.includes('space'))genre='Sci-Fi';
      else if(s.includes('crime')||s.includes('detective')||s.includes('mystery'))genre='Policier';
      else if(s.includes('romance')||s.includes('love'))genre='Romance';
      badge.textContent=genre;
    } else badge.textContent='Littérature';

    // Livres similaires
    loadSimilar(subjects[0]||AUTEUR);
  }catch(e){
    document.getElementById('descText').textContent='Aucun résumé disponible.';
    document.getElementById('descText').classList.remove('loading');
    document.getElementById('genreBadge').textContent='Livre';
    loadSimilar(AUTEUR);
  }
}

// ── Charger livres similaires ──────────────────────
async function loadSimilar(subject){
  const grid=document.getElementById('similarGrid');
  try{
    const q=subject||AUTEUR;
    const url=`https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=20&fields=title,author_name,cover_i,ratings_average,key`;
    const res=await fetch(url);
    const data=await res.json();
    const docs=(data.docs||[])
      .filter(d=>d.title&&d.author_name&&d.title!==TITRE)
      .sort(()=>Math.random()-0.5)
      .slice(0,12);

    grid.innerHTML='';
    if(!docs.length){grid.innerHTML='<div style="color:var(--muted);font-size:0.82rem;grid-column:1/-1">Aucun livre similaire trouvé.</div>';return}

    docs.forEach(doc=>{
      const titre=(doc.title||'').trim();
      const auteur=((doc.author_name||[])[0]||'').trim();
      const coverId=doc.cover_i;
      const coverUrl=coverId?`https://covers.openlibrary.org/b/id/${coverId}-M.jpg`:null;
      const rating=doc.ratings_average?Math.round(doc.ratings_average*10)/10:null;
      const olKey=doc.key||'';
      const init=initials(titre);
      const params=new URLSearchParams({key:olKey,titre,auteur,cover:coverUrl||'',cv:CV,rating:rating||''});

      const card=document.createElement('div');
      card.className='sim-card';
      card.innerHTML=`
        <div class="sim-cover">
          <div class="sim-cover-bg ${CV}"></div>
          ${coverUrl?`<img class="sim-cover-img" src="${coverUrl}" alt="" loading="lazy" onerror="this.style.display='none'"/>`:''}
          <div class="sim-cover-spine"></div>
          <div class="sim-cover-init">${init}</div>
          <div class="sim-overlay"><button class="sim-overlay-btn">Voir</button></div>
        </div>
        <div class="sim-title">${escHtml(titre)}</div>
        <div class="sim-author">${escHtml(auteur)}</div>
        ${rating?`<div class="sim-stars">${starsHtml(rating,'sim-star')}</div>`:''}`;
      card.addEventListener('click',()=>window.location.href='livre-detail.php?'+params.toString());
      grid.appendChild(card);
    });
  }catch(e){grid.innerHTML='<div style="color:var(--muted);font-size:0.82rem;grid-column:1/-1">Erreur de chargement.</div>'}
}

// ── Ajouter à la bibliothèque ──────────────────────
async function ajouterLivre(){
  const btn=document.getElementById('btnAdd');
  btn.disabled=true;
  btn.innerHTML='<div style="width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;flex-shrink:0"></div> Ajout…';

  const body=new FormData();
  body.append('action','add_livre');
  body.append('titre',TITRE);
  body.append('auteur',AUTEUR);
  body.append('genre','');
  body.append('statut','alire');
  body.append('note','0');
  body.append('commentaire','');
  body.append('cover_url',COVER||'');

  try{
    const res=await fetch('api_livres.php',{method:'POST',body});
    const data=await res.json();
    if(data.error){showToast(data.error);btn.disabled=false;return}
    btn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg> Déjà dans ta bibliothèque';
    showToast(`"${TITRE}" ajouté à ta bibliothèque !`);
  }catch(e){
    showToast('Erreur réseau.');
    btn.disabled=false;
    btn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg> Ajouter à ma bibliothèque';
  }
}

// Init
loadDetails();
</script>
</body>
</html>