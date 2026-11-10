/**
 * auth.js
 * Autenticacao no frontend: login/logout via API (sessao PHP + token CSRF),
 * atualizacao do cabecalho e aviso de status para leitores de tela.
 *
 * Perfis: "administrador" (UMA conta, criada no banco: adiciona, edita e exclui aliens) e
 * "visitante" (qualquer conta criada pelo cadastro do site: somente leitura).
 */

// Aviso curto (regiao aria-live) usado por varios modulos.
const Aviso = (() => {
  let temporizador = null;

  function mostrar(texto, tipo) {
    const el = document.getElementById("status-msg");
    if (!el) return;
    el.textContent = texto;
    el.classList.toggle("erro", tipo === "erro");
    el.hidden = false;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
      el.hidden = true;
      el.textContent = "";
    }, 6000);
  }

  return { mostrar };
})();

// Exibe a mensagem de erro da API (e os erros por campo) dentro de um elemento.
function mostrarErroApi(elemento, erro) {
  elemento.textContent = "";
  const titulo = document.createElement("strong");
  titulo.textContent = erro && erro.message ? erro.message : "Ocorreu um erro inesperado.";
  elemento.appendChild(titulo);

  if (erro && erro.erros) {
    const lista = document.createElement("ul");
    Object.keys(erro.erros).forEach((campo) => {
      const item = document.createElement("li");
      item.textContent = erro.erros[campo];
      lista.appendChild(item);
    });
    elemento.appendChild(lista);
  }
  elemento.hidden = false;
}

const Auth = (() => {
  let usuario = null;

  function isAdministrador() {
    return !!usuario && usuario.perfil === "administrador";
  }

  function notificar() {
    document.dispatchEvent(new CustomEvent("auth:alterada", { detail: usuario }));
  }

  // Descobre se ja existe sessao e obtem o token CSRF.
  async function iniciar() {
    try {
      const resposta = await Api.get("/api/me");
      Api.setCsrf(resposta.data.csrf_token);
      usuario = resposta.data.user;
    } catch (erro) {
      usuario = null;
    }
    notificar();
  }

  async function entrar(email, senha) {
    const resposta = await Api.post("/api/login", { email, senha });
    Api.setCsrf(resposta.data.csrf_token);
    usuario = resposta.data.user;
    notificar();
    return usuario;
  }

  // Cria uma conta de visitante e ja inicia a sessao.
  async function cadastrar(nome, email, senha) {
    const resposta = await Api.post("/api/register", { nome, email, senha });
    Api.setCsrf(resposta.data.csrf_token);
    usuario = resposta.data.user;
    notificar();
    return usuario;
  }

  async function sair() {
    const resposta = await Api.post("/api/logout");
    Api.setCsrf(resposta.data.csrf_token);
    usuario = null;
    notificar();
  }

  return { isAdministrador, iniciar, entrar, cadastrar, sair };
})();

document.addEventListener("DOMContentLoaded", () => {
  const btnAbrir = document.getElementById("btn-login-open");
  const btnSair = document.getElementById("btn-logout");
  const textoUsuario = document.getElementById("auth-user");
  const toolbar = document.getElementById("administrador-toolbar");

  const modal = document.getElementById("login-modal");
  const form = document.getElementById("login-form");
  const campoEmail = document.getElementById("login-email");
  const campoSenha = document.getElementById("login-senha");
  const erroEl = document.getElementById("login-erro");
  const btnEnviar = document.getElementById("login-enviar");
  const btnFechar = document.getElementById("btn-login-close");

  const btnCadastroAbrir = document.getElementById("btn-cadastro-open");
  const modalCadastro = document.getElementById("cadastro-modal");
  const formCadastro = document.getElementById("cadastro-form");
  const cadNome = document.getElementById("cad-nome");
  const cadEmail = document.getElementById("cad-email");
  const cadSenha = document.getElementById("cad-senha");
  const cadErro = document.getElementById("cadastro-erro");
  const cadEnviar = document.getElementById("cadastro-enviar");
  const btnCadastroFechar = document.getElementById("btn-cadastro-close");
  const loginParaCadastro = document.getElementById("login-para-cadastro");
  const cadastroParaLogin = document.getElementById("cadastro-para-login");

  // Botoes "Mostrar/Ocultar" senha: alternam o campo entre type="password" e type="text".
  function definirSenhaVisivel(botao, visivel) {
    const campo = document.getElementById(botao.dataset.alvo);
    campo.type = visivel ? "text" : "password";
    botao.textContent = visivel ? "Ocultar" : "Mostrar";
    botao.setAttribute("aria-pressed", visivel ? "true" : "false");
    botao.setAttribute("aria-label", visivel ? "Ocultar senha" : "Mostrar senha");
  }

  function ocultarTodasAsSenhas() {
    document.querySelectorAll(".btn-ver-senha").forEach((botao) => definirSenhaVisivel(botao, false));
  }

  document.querySelectorAll(".btn-ver-senha").forEach((botao) => {
    botao.addEventListener("click", () => {
      const campo = document.getElementById(botao.dataset.alvo);
      definirSenhaVisivel(botao, campo.type === "password");
      campo.focus();
    });
  });

  // Ao fechar qualquer janela, a senha volta a ficar oculta.
  modal.addEventListener("close", ocultarTodasAsSenhas);
  modalCadastro.addEventListener("close", ocultarTodasAsSenhas);

  function atualizarInterface(usuario) {
    const logado = !!usuario;
    btnAbrir.hidden = logado;
    btnCadastroAbrir.hidden = logado;
    btnSair.hidden = !logado;
    textoUsuario.hidden = !logado;
    textoUsuario.textContent = logado ? `${usuario.nome} (${usuario.perfil})` : "";
    toolbar.hidden = !Auth.isAdministrador();
  }

  document.addEventListener("auth:alterada", (evento) => atualizarInterface(evento.detail));

  function abrirLogin() {
    erroEl.hidden = true;
    form.reset();
    ocultarTodasAsSenhas();
    modal.showModal();
    campoEmail.focus();
  }

  function abrirCadastro() {
    cadErro.hidden = true;
    formCadastro.reset();
    ocultarTodasAsSenhas();
    modalCadastro.showModal();
    cadNome.focus();
  }

  btnAbrir.addEventListener("click", abrirLogin);
  btnCadastroAbrir.addEventListener("click", abrirCadastro);

  loginParaCadastro.addEventListener("click", () => {
    modal.close();
    abrirCadastro();
  });
  cadastroParaLogin.addEventListener("click", () => {
    modalCadastro.close();
    abrirLogin();
  });

  btnCadastroFechar.addEventListener("click", () => modalCadastro.close());

  formCadastro.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    cadErro.hidden = true;
    cadEnviar.disabled = true;

    try {
      const usuario = await Auth.cadastrar(cadNome.value, cadEmail.value.trim(), cadSenha.value);
      modalCadastro.close();
      formCadastro.reset();
      Aviso.mostrar(`Conta criada com sucesso! Você entrou como ${usuario.nome} (${usuario.perfil}).`);
    } catch (erro) {
      mostrarErroApi(cadErro, erro);
    } finally {
      cadEnviar.disabled = false;
    }
  });

  btnFechar.addEventListener("click", () => modal.close());

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    erroEl.hidden = true;
    btnEnviar.disabled = true;

    try {
      const usuario = await Auth.entrar(campoEmail.value.trim(), campoSenha.value);
      modal.close();
      form.reset();
      Aviso.mostrar(`Bem-vindo(a), ${usuario.nome}! Perfil: ${usuario.perfil}.`);
    } catch (erro) {
      mostrarErroApi(erroEl, erro);
    } finally {
      btnEnviar.disabled = false;
    }
  });

  btnSair.addEventListener("click", async () => {
    try {
      await Auth.sair();
      Aviso.mostrar("Você saiu da sessão.");
    } catch (erro) {
      Aviso.mostrar(erro.message, "erro");
    }
  });

  Auth.iniciar();
});