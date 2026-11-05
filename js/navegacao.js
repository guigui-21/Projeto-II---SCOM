/**
 * navegacao.js
 * Controla a troca de secoes do Codex ao estilo "abas de pagina":
 * em vez de rolar a pagina inteira, apenas a secao escolhida no menu
 * fica visivel dentro da area de conteudo.
 *
 * Continua funcionando por links de ancora (#id), entao tambem serve
 * como reforco de acessibilidade/fallback caso o JavaScript falhe
 * (nesse caso o navegador apenas rola ate a secao, como antes).
 */

document.addEventListener("DOMContentLoaded", () => {
  const secoes = Array.from(document.querySelectorAll(".page-section"));
  const linksNav = Array.from(document.querySelectorAll(".nav-link"));
  // Qualquer link interno (#id) na pagina, incluindo o que fica dentro
  // do texto de introducao ("...na secao Omnitrix...").
  const linksInternos = Array.from(
    document.querySelectorAll('a[href^="#"]')
  );

  if (!secoes.length) return;

  const idsValidos = new Set(secoes.map((secao) => secao.id));

  function mostrarSecao(id, { moverFoco = true, atualizarHistorico = true } = {}) {
    if (!idsValidos.has(id)) return;

    secoes.forEach((secao) => {
      const ativa = secao.id === id;
      secao.hidden = !ativa;
    });

    linksNav.forEach((link) => {
      const alvo = link.getAttribute("href").replace("#", "");
      const ativo = alvo === id;
      link.classList.toggle("active", ativo);
      if (ativo) {
        link.setAttribute("aria-current", "page");
      } else {
        link.removeAttribute("aria-current");
      }
    });

    if (atualizarHistorico && window.location.hash !== `#${id}`) {
      history.pushState({ secao: id }, "", `#${id}`);
    }

    // Leva o foco para o inicio da nova secao (bom para teclado/leitor de tela)
    // sem depender do salto padrao do navegador para a ancora.
    if (moverFoco) {
      const secaoAtiva = document.getElementById(id);
      if (secaoAtiva) {
        secaoAtiva.focus({ preventScroll: false });
      }
    }

    const conteudo = document.getElementById("conteudo");
    if (conteudo) {
      conteudo.scrollTo({ top: 0, behavior: "auto" });
    }
    window.scrollTo({ top: 0, behavior: "auto" });
  }

  linksInternos.forEach((link) => {
    const alvo = link.getAttribute("href").slice(1);
    if (!idsValidos.has(alvo)) return; // ex.: algum link que nao aponta pra secao

    link.addEventListener("click", (event) => {
      event.preventDefault();
      mostrarSecao(alvo);
    });
  });

  window.addEventListener("popstate", () => {
    const alvo = window.location.hash.replace("#", "") || secoes[0].id;
    mostrarSecao(alvo, { atualizarHistorico: false });
  });

  // --- Inicializacao: respeita um link direto (#viloes, por exemplo) -------
  const idInicial = window.location.hash.replace("#", "");
  mostrarSecao(idsValidos.has(idInicial) ? idInicial : secoes[0].id, {
    moverFoco: false,
    atualizarHistorico: false,
  });
});