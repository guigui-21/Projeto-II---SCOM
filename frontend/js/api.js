/**
 * api.js
 * Cliente da API REST: encapsula fetch(), o envio do token CSRF e o
 * tratamento padronizado de erros (mensagens legíveis para a interface).
 */

class ApiError extends Error {
  constructor(mensagem, status, erros) {
    super(mensagem);
    this.name = "ApiError";
    this.status = status;
    this.erros = erros || null;
  }
}

const Api = (() => {
  let csrfToken = null;

  function setCsrf(token) {
    csrfToken = token || null;
  }

  async function request(metodo, caminho, corpo) {
    const headers = { Accept: "application/json" };
    const opcoes = { method: metodo, headers, credentials: "same-origin" };

    if (corpo !== undefined) {
      headers["Content-Type"] = "application/json";
      opcoes.body = JSON.stringify(corpo);
    }
    // O token CSRF é exigido apenas em requisições que alteram estado.
    if (metodo !== "GET" && csrfToken) {
      headers["X-CSRF-Token"] = csrfToken;
    }

    let resposta;
    try {
      resposta = await fetch(API_BASE + caminho, opcoes);
    } catch (erro) {
      throw new ApiError(
        "Não foi possível conectar ao servidor. Verifique se o Apache e o MySQL (XAMPP) estão ligados.",
        0
      );
    }

    let json = null;
    try {
      json = await resposta.json();
    } catch (erro) {
      json = null;
    }

    if (!resposta.ok) {
      const mensagem =
        json && json.message ? json.message : "Erro ao comunicar com o servidor (HTTP " + resposta.status + ").";
      throw new ApiError(mensagem, resposta.status, json && json.errors ? json.errors : null);
    }
    return json;
  }

  return {
    setCsrf,
    get: (caminho) => request("GET", caminho),
    post: (caminho, corpo) => request("POST", caminho, corpo === undefined ? {} : corpo),
    put: (caminho, corpo) => request("PUT", caminho, corpo),
    delete: (caminho) => request("DELETE", caminho),
  };
})();