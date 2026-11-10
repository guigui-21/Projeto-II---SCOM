/**
 * administrador.js
 * CRUD de aliens para o perfil "administrador": formulario de cadastro/edicao e
 * exclusao. Conversa com a API (POST/PUT/DELETE) e avisa o app.js, por
 * evento, para recarregar os dados.
 *
 * O backend e quem decide a permissao (HTTP 403 para quem nao e administrador);
 * esconder os botoes aqui e apenas conveniencia de interface.
 */

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("alien-form-modal");
  const form = document.getElementById("alien-form");
  const titulo = document.getElementById("alien-form-titulo");
  const erroEl = document.getElementById("alien-form-erro");
  const btnEnviar = document.getElementById("alien-form-enviar");
  const btnFechar = document.getElementById("btn-alien-form-close");
  const btnNovo = document.getElementById("btn-novo-alien");

  const campos = {
    nome: document.getElementById("af-nome"),
    especie: document.getElementById("af-especie"),
    planeta: document.getElementById("af-planeta"),
    era: document.getElementById("af-era"),
    habilidades: document.getElementById("af-habilidades"),
    descricao: document.getElementById("af-descricao"),
    imagemUrl: document.getElementById("af-imagem-url"),
    imagemAlt: document.getElementById("af-imagem-alt"),
  };

  let editandoId = null;
  let erasCarregadas = false;

  async function carregarEras() {
    if (erasCarregadas) return;
    const resposta = await Api.get("/api/eras");
    campos.era.innerHTML = "";
    resposta.data.forEach((era) => {
      const opcao = document.createElement("option");
      opcao.value = String(era.id);
      opcao.textContent = era.nome;
      campos.era.appendChild(opcao);
    });
    erasCarregadas = true;
  }

  async function abrirFormulario(alien) {
    try {
      await carregarEras();
    } catch (erro) {
      Aviso.mostrar(erro.message, "erro");
      return;
    }

    erroEl.hidden = true;
    form.reset();
    editandoId = alien ? alien.id : null;
    titulo.textContent = alien ? "Editar alien" : "Adicionar novo alien";
    btnEnviar.textContent = alien ? "Salvar alterações" : "Adicionar";

    if (alien) {
      campos.nome.value = alien.nome;
      campos.especie.value = alien.especie;
      campos.planeta.value = alien.planeta_origem;
      campos.era.value = String(alien.era_id);
      campos.habilidades.value = (alien.habilidades || []).join("\n");
      campos.descricao.value = alien.descricao;
      campos.imagemUrl.value = alien.imagem_url || "";
      campos.imagemAlt.value = alien.imagem_alt || "";
    }

    modal.showModal();
    campos.nome.focus();
  }

  function lerFormulario() {
    return {
      nome: campos.nome.value,
      especie: campos.especie.value,
      planeta_origem: campos.planeta.value,
      era_id: Number(campos.era.value),
      habilidades: campos.habilidades.value
        .split("\n")
        .map((linha) => linha.trim())
        .filter((linha) => linha !== ""),
      descricao: campos.descricao.value,
      imagem_url: campos.imagemUrl.value.trim() || null,
      imagem_alt: campos.imagemAlt.value.trim() || null,
    };
  }

  btnNovo.addEventListener("click", () => abrirFormulario(null));
  btnFechar.addEventListener("click", () => modal.close());

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    erroEl.hidden = true;
    btnEnviar.disabled = true;

    const dados = lerFormulario();
    try {
      if (editandoId === null) {
        await Api.post("/api/aliens", dados);
        Aviso.mostrar("Alien adicionado com sucesso.");
      } else {
        await Api.put(`/api/aliens/${editandoId}`, dados);
        Aviso.mostrar("Alien atualizado com sucesso.");
      }
      modal.close();
      document.dispatchEvent(new CustomEvent("aliens:alterados"));
    } catch (erro) {
      mostrarErroApi(erroEl, erro);
    } finally {
      btnEnviar.disabled = false;
    }
  });

  document.addEventListener("alien:editar", (evento) => {
    abrirFormulario(evento.detail);
  });

  document.addEventListener("alien:excluir", async (evento) => {
    const alien = evento.detail;
    if (!window.confirm(`Excluir o alien "${alien.nome}"? Esta ação não pode ser desfeita.`)) {
      return;
    }
    try {
      await Api.delete(`/api/aliens/${alien.id}`);
      Aviso.mostrar(`Alien "${alien.nome}" excluído.`);
      document.dispatchEvent(new CustomEvent("aliens:alterados"));
    } catch (erro) {
      Aviso.mostrar(erro.message, "erro");
    }
  });
});