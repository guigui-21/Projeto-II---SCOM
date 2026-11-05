/**
 * app.js
 * Camada de apresentacao/controle do Omnitrix Codex.
 * Le os dados de aliensData.js e manipula o DOM (padrao proximo de um
 * MVC simples: dados / logica de controle / marcacao HTML).
 */

document.addEventListener("DOMContentLoaded", () => {
  const state = {
    currentEra: "classico",
    currentIndex: 0,
  };

  // --- Referencias do DOM -------------------------------------------------
  const tabButtons = Array.from(document.querySelectorAll(".btn-era"));
  const omnitrixTitle = document.getElementById("omnitrix-nome");
  const omnitrixAno = document.getElementById("omnitrix-ano");
  const omnitrixDescricao = document.getElementById("omnitrix-descricao");
  const omnitrixImagem = document.getElementById("omnitrix-imagem");
  const dial = document.getElementById("omnitrix-dial");
  const dialRing = document.getElementById("dial-ring");
  const dialCurrentBtn = document.getElementById("dial-current-alien");
  const btnPrev = document.getElementById("btn-dial-prev");
  const btnNext = document.getElementById("btn-dial-next");
  const alienList = document.getElementById("alien-selector-list");
  const btnMute = document.getElementById("btn-mute");
  const painelOmnitrix = document.getElementById("painel-omnitrix");

  const modal = document.getElementById("alien-modal");
  const modalConteudo = document.getElementById("modal-conteudo");
  const btnModalClose = document.getElementById("btn-modal-close");

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  // --- Utilidades ----------------------------------------------------------
  function getCurrentAliens() {
    return aliensData[state.currentEra] || [];
  }

  // --- Renderizacao do painel do Omnitrix (dados da era) -------------------
  function renderOmnitrixInfo(eraId) {
    const era = omnitrixEras[eraId];
    omnitrixTitle.textContent = era.nome;
    omnitrixAno.textContent = era.ano;
    omnitrixDescricao.textContent = era.descricao;
    omnitrixImagem.src = era.imagem;
    omnitrixImagem.alt = era.imagemAlt;

    dial.style.setProperty("--dial-color-primary", era.corPrimaria);
    dial.style.setProperty("--dial-color-secondary", era.corSecundaria);
    dial.setAttribute("data-era", eraId);
  }

  // --- Renderizacao da lista acessivel de aliens ----------------------------
  function renderAlienButtons(eraId) {
    alienList.innerHTML = "";
    const aliens = aliensData[eraId] || [];

    aliens.forEach((alien, index) => {
      const li = document.createElement("li");
      const btn = document.createElement("button");
      btn.type = "button";
      btn.classList.add("btn-alien");
      btn.id = `btn-alien-${eraId}-${alien.id}`;
      btn.textContent = alien.nome;
      btn.setAttribute("aria-label", `Escolher ${alien.nome} e ver detalhes`);
      btn.setAttribute("aria-pressed", index === 0 ? "true" : "false");

      btn.addEventListener("click", () => {
        selectAlien(index, btn);
      });

      li.appendChild(btn);
      alienList.appendChild(li);
    });
  }

  function updateAlienButtonsState() {
    const buttons = alienList.querySelectorAll(".btn-alien");
    buttons.forEach((btn, index) => {
      const isActive = index === state.currentIndex;
      btn.classList.toggle("active", isActive);
      btn.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  }

  // Gera um glifo geometrico simples e original para representar o alien
  // no pop-up (evita depender de imagens externas/artes protegidas).
  function buildAlienGlyph(alienId) {
    let hash = 0;
    for (let i = 0; i < alienId.length; i++) {
      hash = (hash * 31 + alienId.charCodeAt(i)) % 360;
    }
    const hue = hash;
    return `
      <svg viewBox="0 0 120 120" width="120" height="120" focusable="false">
        <circle cx="60" cy="60" r="52" fill="hsl(${hue}, 45%, 18%)" stroke="hsl(${hue}, 70%, 55%)" stroke-width="3"/>
        <polygon points="60,20 92,60 60,100 28,60" fill="hsl(${hue}, 70%, 45%)" opacity="0.85"/>
        <circle cx="60" cy="60" r="14" fill="hsl(${hue}, 90%, 70%)"/>
      </svg>
    `;
  }

  // --- Pop-up de detalhes do alien --------------------------------------------
  let elementoQueAbriuModal = null;

  function abrirModalAlien(alien, elementoOrigem) {
    modalConteudo.innerHTML = `
      <div class="card-header">
        <h3 id="modal-alien-nome">${alien.nome}</h3>
        <span class="badge-especie">${alien.especie}</span>
      </div>
      <div class="card-body">
        <div class="alien-figure" aria-hidden="true">
          ${
            alien.imagem
              ? `<img src="${alien.imagem}" width="${alien.imagemW || 160}" height="${alien.imagemH || 160}" alt="${alien.imagemAlt || ""}" class="foto-alien" />`
              : buildAlienGlyph(alien.id)
          }
        </div>
        <div class="details-info">
          <p><strong>Planeta de origem:</strong> ${alien.planeta}</p>
          <p><strong>Feito marcante:</strong> ${alien.feito}</p>
          <h4>Habilidades principais</h4>
          <ul>
            ${alien.habilidades.map((h) => `<li>${h}</li>`).join("")}
          </ul>
        </div>
      </div>
    `;

    elementoQueAbriuModal = elementoOrigem || null;

    if (typeof modal.showModal === "function") {
      modal.showModal();
    } else {
      // Navegador sem suporte a <dialog>: mostra como painel simples.
      modal.setAttribute("open", "");
    }
  }

  function fecharModalAlien() {
    if (typeof modal.close === "function" && modal.open) {
      modal.close();
    } else {
      modal.removeAttribute("open");
      OmnitrixSound.playDescarregou();
    }
  }

  // O evento "close" cobre qualquer forma de fechamento (botao, tecla Esc
  // ou clique fora do conteudo), garantindo que o som toque sempre.
  modal.addEventListener("close", () => {
    OmnitrixSound.playDescarregou();
    if (elementoQueAbriuModal) {
      elementoQueAbriuModal.focus();
    }
  });

  btnModalClose.addEventListener("click", () => {
    fecharModalAlien();
  });

  // Fecha ao clicar fora da area de conteudo (no "backdrop" do <dialog>).
  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      fecharModalAlien();
    }
  });

  // --- Disco giratorio (visao de cima do Omnitrix) --------------------------
  function renderDialMarkers(eraId) {
    const aliens = aliensData[eraId] || [];
    const total = aliens.length;
    dialRing.innerHTML = "";

    aliens.forEach((alien, index) => {
      const marker = document.createElement("div");
      marker.className = "dial-marker";
      const angle = (360 / total) * index;
      marker.style.setProperty("--marker-angle", `${angle}deg`);
      marker.title = alien.nome;
      dialRing.appendChild(marker);
    });
  }

  function updateDialRotation(animate) {
    const aliens = getCurrentAliens();
    const step = 360 / aliens.length;
    const targetDeg = -state.currentIndex * step;

    dialRing.style.transition =
      animate && !prefersReducedMotion
        ? "transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)"
        : "none";
    dialRing.style.transform = `rotate(${targetDeg}deg)`;

    const alien = aliens[state.currentIndex];
    if (alien) {
      dialCurrentBtn.textContent = `Escolher: ${alien.nome}`;
      dialCurrentBtn.setAttribute("aria-label", `Escolher ${alien.nome} e ver detalhes`);
    }
  }

  // Apenas "passeia" pelo disco (visual + som de troca), sem abrir o pop-up.
  function moverDial(index) {
    const aliens = getCurrentAliens();
    if (!aliens.length) return;
    const total = aliens.length;
    state.currentIndex = ((index % total) + total) % total;
    updateDialRotation(true);
    updateAlienButtonsState();
  }

  function stepDial(direction) {
    moverDial(state.currentIndex + direction);
    OmnitrixSound.playTroca();
  }

  // Confirma a escolha de um alien especifico: som de escolha + pop-up.
  function selectAlien(index, elementoOrigem) {
    const aliens = getCurrentAliens();
    if (!aliens.length) return;
    const total = aliens.length;
    state.currentIndex = ((index % total) + total) % total;

    updateDialRotation(true);
    updateAlienButtonsState();

    const alien = aliens[state.currentIndex];
    OmnitrixSound.playEscolha();
    abrirModalAlien(alien, elementoOrigem || dialCurrentBtn);
  }

  dialCurrentBtn.addEventListener("click", () => {
    selectAlien(state.currentIndex, dialCurrentBtn);
  });

  // --- Troca de era ----------------------------------------------------------
  function activateEra(eraId, { focusTab = false, tocarSom = true } = {}) {
    state.currentEra = eraId;
    state.currentIndex = 0;

    tabButtons.forEach((btn) => {
      const isSelected = btn.dataset.era === eraId;
      btn.classList.toggle("active", isSelected);
      btn.setAttribute("aria-selected", isSelected ? "true" : "false");
      btn.tabIndex = isSelected ? 0 : -1;
      if (isSelected && focusTab) btn.focus();
    });

    painelOmnitrix.setAttribute("aria-labelledby", `tab-${eraId}`);

    renderOmnitrixInfo(eraId);
    renderAlienButtons(eraId);
    renderDialMarkers(eraId);
    updateDialRotation(false);
    updateAlienButtonsState();

    if (tocarSom) {
      OmnitrixSound.playAbertura();
    }
  }

  // --- Eventos: abas de era (padrao ARIA tabs, navegacao por setas) ---------
  tabButtons.forEach((btn, idx) => {
    btn.addEventListener("click", () => {
      activateEra(btn.dataset.era);
    });

    btn.addEventListener("keydown", (event) => {
      let newIndex = null;
      if (event.key === "ArrowRight") newIndex = (idx + 1) % tabButtons.length;
      if (event.key === "ArrowLeft")
        newIndex = (idx - 1 + tabButtons.length) % tabButtons.length;
      if (event.key === "Home") newIndex = 0;
      if (event.key === "End") newIndex = tabButtons.length - 1;

      if (newIndex !== null) {
        event.preventDefault();
        const targetBtn = tabButtons[newIndex];
        activateEra(targetBtn.dataset.era, { focusTab: true });
      }
    });
  });

  // --- Eventos: setas de navegacao do disco (apenas passeiam, nao escolhem) --
  btnPrev.addEventListener("click", () => stepDial(-1));
  btnNext.addEventListener("click", () => stepDial(1));

  dial.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight" || event.key === "ArrowDown") {
      event.preventDefault();
      stepDial(1);
    }
    if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
      event.preventDefault();
      stepDial(-1);
    }
    if (event.key === "Enter" || event.key === " ") {
      // Enter/espaco com o foco no grupo (fora do botao central) tambem confirma.
      if (document.activeElement === dial) {
        event.preventDefault();
        selectAlien(state.currentIndex, dial);
      }
    }
  });

  // --- Botao de mudo (respeita preferencia do usuario por som) ---------------
  btnMute.addEventListener("click", () => {
    const nowEnabled = !OmnitrixSound.isEnabled();
    OmnitrixSound.setEnabled(nowEnabled);
    btnMute.setAttribute("aria-pressed", nowEnabled ? "false" : "true");
    btnMute.textContent = nowEnabled ? "🔊 Som ligado" : "🔇 Som desligado";
  });

  // --- Inicializacao -----------------------------------------------------
  // tocarSom: false no carregamento inicial, pois navegadores bloqueiam
  // audio automatico sem uma interacao previa do usuario.
  activateEra(state.currentEra, { tocarSom: false });
});