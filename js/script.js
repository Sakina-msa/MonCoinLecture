document.addEventListener("DOMContentLoaded", () => {
  // Brancher les boutons
  const btnAjouter = document.getElementById("btnAjouter");
  const btnTrier = document.getElementById("btnTrier");
  const btnFiltrer = document.getElementById("btnFiltrer");
  const btnReset = document.getElementById("btnReset");

  if (btnAjouter) btnAjouter.addEventListener("click", ajouterLivre);
  if (btnTrier) btnTrier.addEventListener("click", trierLivres);
  if (btnFiltrer) btnFiltrer.addEventListener("click", filtrerLivres);
  if (btnReset) btnReset.addEventListener("click", resetFiltre);

  // Appuyer sur Entrée dans un champ => ajoute le livre
  ["titreLivre", "auteurLivre", "genreLivre"].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("keydown", (e) => {
      if (e.key === "Enter") ajouterLivre();
    });
  });

  afficherLivres();
  majCompteurLivres();
});

// ====== Stockage ======
function getLivres() {
  return JSON.parse(localStorage.getItem("livres") || "[]");
}
function saveLivres(livres) {
  localStorage.setItem("livres", JSON.stringify(livres));
}

// ====== UI ======
function majCompteurLivres() {
  const el = document.getElementById("compteurLivres");
  if (!el) return;
  el.textContent = getLivres().length;
}

function afficherLivres(liste = null) {
  const ul = document.getElementById("listeLivres");
  if (!ul) return;

  const livres = liste ?? getLivres();
  ul.innerHTML = "";

  if (livres.length === 0) {
    ul.innerHTML = `<li class="notice">Aucun livre pour le moment.</li>`;
    majCompteurLivres();
    return;
  }

  livres.forEach((l, index) => {
    const li = document.createElement("li");
    li.className = "book-item";

    li.innerHTML = `
      <div>
        <div class="book-title">${escapeHtml(l.titre)}</div>
        <div class="book-meta">${escapeHtml(l.auteur)} • ${escapeHtml(l.genre)}</div>
      </div>
      <button type="button" class="btn-danger" data-index="${index}">Supprimer</button>
    `;

    ul.appendChild(li);
  });

  // bouton supprimer (délégation)
  ul.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-index]");
    if (!btn) return;
    const index = Number(btn.dataset.index);
    supprimerLivre(index);
  }, { once: true });

  majCompteurLivres();
}

// ====== Actions ======
function ajouterLivre() {
  const titreEl = document.getElementById("titreLivre");
  const auteurEl = document.getElementById("auteurLivre");
  const genreEl = document.getElementById("genreLivre");
  const msg = document.getElementById("messageAjout");

  if (!titreEl || !auteurEl || !genreEl) return;

  const titre = titreEl.value.trim();
  const auteur = auteurEl.value.trim();
  const genre = genreEl.value.trim();

  if (!titre || !auteur || !genre) {
    if (msg) msg.innerHTML = `<div class="notice">Remplis Titre + Auteur + Genre.</div>`;
    return;
  }

  const livres = getLivres();
  livres.push({ titre, auteur, genre });
  saveLivres(livres);

  titreEl.value = "";
  auteurEl.value = "";
  genreEl.value = "";

  if (msg) {
    msg.innerHTML = `<div class="notice">Livre ajouté ✅</div>`;
    setTimeout(() => (msg.innerHTML = ""), 1200);
  }

  afficherLivres();
}

function supprimerLivre(index) {
  const livres = getLivres();
  livres.splice(index, 1);
  saveLivres(livres);
  afficherLivres();
}

function trierLivres() {
  const livres = getLivres();
  livres.sort((a, b) => a.titre.localeCompare(b.titre));
  saveLivres(livres);
  afficherLivres();
}

function filtrerLivres() {
  const input = document.getElementById("filtreGenre");
  if (!input) return;

  const q = input.value.trim().toLowerCase();
  const livres = getLivres();
  const res = livres.filter(l => (l.genre || "").toLowerCase().includes(q));
  afficherLivres(res);
}

function resetFiltre() {
  const input = document.getElementById("filtreGenre");
  if (input) input.value = "";
  afficherLivres();
}

// petite sécurité pour éviter injection HTML
function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}