<?php
// header_nav.php — inclus sur toutes les pages
// Requiert que config.php soit déjà inclus
$isLoggedIn  = isset($_SESSION['user_id']);
$userName    = $_SESSION['user_name'] ?? '';
$userInitial = strtoupper(mb_substr($userName, 0, 1)) ?: '?';

// Stats rapides si connecté
$userStats = ['lus' => 0, 'en_cours' => 0, 'favoris' => 0];
if ($isLoggedIn) {
    try {
        $db = getDB();
        $st = $db->prepare("SELECT SUM(statut='lu') AS lus, SUM(statut='encours') AS en_cours, SUM(favori=1) AS favoris FROM livres WHERE user_id = ?");
        $st->execute([$_SESSION['user_id']]);
        $row = $st->fetch();
        $userStats = [
            'lus'      => (int)($row['lus']      ?? 0),
            'en_cours' => (int)($row['en_cours']  ?? 0),
            'favoris'  => (int)($row['favoris']   ?? 0),
        ];
    } catch (Exception $e) {}
}
?>
<header class="site-header">
  <div class="logo">
    <a href="index.php" style="text-decoration:none">
      <span class="logo-mark">Mon <em>Coin</em> Lecture</span>
    </a>
    <div class="logo-divider"></div>
    <span class="logo-sub">Ta bibliothèque</span>
  </div>

  <div class="header-actions">
    <?php if ($isLoggedIn): ?>
      <!-- UTILISATEUR CONNECTÉ -->
      <div class="user-menu-wrap">
        <button class="user-avatar-btn" onclick="toggleUserMenu()" id="userAvatarBtn">
          <div class="user-avatar"><?= htmlspecialchars($userInitial) ?></div>
          <span class="user-name-label"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        <div class="user-dropdown" id="userDropdown">
          <!-- Carte profil -->
          <div class="ud-profile">
            <div class="ud-avatar"><?= htmlspecialchars($userInitial) ?></div>
            <div>
              <div class="ud-name"><?= htmlspecialchars($userName) ?></div>
              <div class="ud-label">Lecteur passionné</div>
            </div>
          </div>
          <!-- Mini stats -->
          <div class="ud-stats">
            <div class="ud-stat"><div class="ud-stat-num"><?= $userStats['lus'] ?></div><div class="ud-stat-label">Lus</div></div>
            <div class="ud-stat-sep"></div>
            <div class="ud-stat"><div class="ud-stat-num"><?= $userStats['en_cours'] ?></div><div class="ud-stat-label">En cours</div></div>
            <div class="ud-stat-sep"></div>
            <div class="ud-stat"><div class="ud-stat-num"><?= $userStats['favoris'] ?></div><div class="ud-stat-label">Favoris</div></div>
          </div>
          <!-- Liens -->
          <div class="ud-links">
            <a href="mes-livres.php" class="ud-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Ma bibliothèque
            </a>
            <a href="favoris.php" class="ud-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              Mes favoris
            </a>
            <a href="decouvrir.php" class="ud-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              Découvrir
            </a>
          </div>
          <div class="ud-sep"></div>
          <a href="deconnexion.php" class="ud-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Se déconnecter
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- NON CONNECTÉ -->
      <a href="connexion.php" class="header-btn header-btn-ghost" style="text-decoration:none">Connexion</a>
      <a href="inscrire.php"  class="header-btn header-btn-accent" style="text-decoration:none">Commencer</a>
    <?php endif ?>
  </div>
</header>

<style>
/* ── BOUTONS HEADER ── */
.header-btn{font-family:var(--font-sans,sans-serif);font-size:0.8rem;font-weight:500;padding:8px 20px;border-radius:99px;cursor:pointer;letter-spacing:0.02em;transition:all 0.18s;border:none;display:inline-block}
.header-btn-ghost{background:transparent;color:#3d322a;border:1.5px solid #d4c8b8}.header-btn-ghost:hover{background:#f3ede3}
.header-btn-accent{background:#1a1410;color:#faf7f2}.header-btn-accent:hover{background:#3d322a}

/* ── MENU UTILISATEUR ── */
.user-menu-wrap{position:relative}
.user-avatar-btn{display:flex;align-items:center;gap:8px;background:transparent;border:1.5px solid #e8e0d4;border-radius:99px;padding:6px 14px 6px 6px;cursor:pointer;font-family:inherit;transition:all 0.18s}
.user-avatar-btn:hover{background:#f3ede3;border-color:#d4c8b8}
.user-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#c4602a,#b8933f);color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--font-serif,'Playfair Display',Georgia,serif);font-size:0.9rem;font-weight:700;flex-shrink:0}
.user-name-label{font-size:0.82rem;font-weight:600;color:#1a1410}
.chevron{width:14px;height:14px;color:#8a7b6e;transition:transform 0.2s}
.user-avatar-btn.open .chevron{transform:rotate(180deg)}

/* Dropdown */
.user-dropdown{display:none;position:absolute;top:calc(100% + 10px);right:0;background:#fff;border:1.5px solid #e8e0d4;border-radius:18px;box-shadow:0 12px 48px rgba(26,20,16,0.14);width:260px;z-index:300;overflow:hidden;animation:dropIn 0.2s ease}
.user-dropdown.show{display:block}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

/* Profil */
.ud-profile{display:flex;align-items:center;gap:12px;padding:18px 18px 14px}
.ud-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#c4602a,#b8933f);color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--font-serif,'Playfair Display',Georgia,serif);font-size:1.1rem;font-weight:700;flex-shrink:0}
.ud-name{font-size:0.9rem;font-weight:600;color:#1a1410;margin-bottom:2px}
.ud-label{font-size:0.68rem;color:#8a7b6e;font-style:italic}

/* Mini stats */
.ud-stats{display:flex;align-items:center;justify-content:space-around;padding:10px 18px 14px;border-top:1px solid #e8e0d4;border-bottom:1px solid #e8e0d4;background:#faf7f2}
.ud-stat{text-align:center;flex:1}
.ud-stat-num{font-family:var(--font-serif,'Playfair Display',Georgia,serif);font-size:1.2rem;font-weight:700;color:#1a1410;line-height:1}
.ud-stat-label{font-size:0.58rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#8a7b6e;margin-top:2px}
.ud-stat-sep{width:1px;height:32px;background:#e8e0d4;flex-shrink:0}

/* Liens */
.ud-links{padding:8px 10px}
.ud-link{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;text-decoration:none;font-size:0.82rem;font-weight:500;color:#3d322a;transition:background 0.15s}
.ud-link:hover{background:#faf7f2;color:#1a1410}
.ud-link svg{width:15px;height:15px;color:#8a7b6e;flex-shrink:0}
.ud-sep{height:1px;background:#e8e0d4;margin:4px 10px}
.ud-logout{display:flex;align-items:center;gap:10px;padding:10px 20px 14px;font-size:0.8rem;font-weight:600;color:#c4602a;text-decoration:none;transition:color 0.15s}
.ud-logout:hover{color:#9e3e14}
.ud-logout svg{width:15px;height:15px;flex-shrink:0}
</style>

<script>
function toggleUserMenu() {
  const btn  = document.getElementById('userAvatarBtn');
  const menu = document.getElementById('userDropdown');
  if (!btn || !menu) return;
  const isOpen = menu.classList.toggle('show');
  btn.classList.toggle('open', isOpen);
}
document.addEventListener('click', e => {
  const wrap = document.querySelector('.user-menu-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('userDropdown')?.classList.remove('show');
    document.getElementById('userAvatarBtn')?.classList.remove('open');
  }
});
</script>
