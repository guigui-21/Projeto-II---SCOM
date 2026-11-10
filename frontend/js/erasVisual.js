/**
 * erasVisual.js
 * Configuração VISUAL das 4 eras do Omnitrix (cores e imagem do relógio).
 *
 * Os DADOS dos aliens (nome, espécie, planeta, habilidades...) não ficam mais
 * no frontend: vêm da API REST em PHP/MySQL (veja api.js e app.js). Este
 * arquivo só guarda a apresentação de cada era, indexada pelo "slug" da era
 * (classico, alienForce, supremacia, omniverse) cadastrado no banco.
 */

const omnitrixEras = {
  classico: {
    id: "classico",
    nome: "Omnitrix Protótipo",
    ano: "2005",
    corPrimaria: "#00ff66",
    corSecundaria: "#e8e8e8",
    descricao:
      "O primeiro Omnitrix encontrado por Ben Tennyson: um relógio bruto, preto e verde, preso ao pulso após a queda de uma cápsula alienígena.",
    imagem: "assets/omnitrix/omnitrix-classico.png",
    imagemAlt: "Ilustração do Omnitrix Protótipo, usado na era Clássica (2005)",
  },
  alienForce: {
    id: "alienForce",
    nome: "Omnitrix Recalibrado",
    ano: "2008",
    corPrimaria: "#00ff66",
    corSecundaria: "#1c1c1c",
    descricao:
      "Uma década depois, Azmuth recalibra o Omnitrix: design mais elegante, novo elenco de transformações e maior controle sobre a metamorfose.",
    imagem: "assets/omnitrix/omnitrix-alienforce.png",
    imagemAlt: "Ilustração do Omnitrix Recalibrado, usado na era Força Alienígena (2008)",
  },
  supremacia: {
    id: "supremacia",
    nome: "Ultimatrix",
    ano: "2010",
    corPrimaria: "#39d353",
    corSecundaria: "#0f5132",
    descricao:
      "Um dispositivo experimental em formato de manopla, capaz de evoluir um alienígena já transformado para sua forma Suprema (Ultimate).",
    imagem: "assets/omnitrix/omnitrix-supremacia.png",
    imagemAlt: "Ilustração do Ultimatrix, usado na era Supremacia Alienígena (2010)",
  },
  omniverse: {
    id: "omniverse",
    nome: "Omnitrix Definitivo",
    ano: "2013",
    corPrimaria: "#7ee081",
    corSecundaria: "#d8d8d8",
    descricao:
      "Reconstruído por Azmuth com tecnologia refinada, reúne transformações inéditas e clássicas em um único mostrador circular.",
    imagem: "assets/omnitrix/omnitrix-omniverse.png",
    imagemAlt: "Ilustração do Omnitrix Definitivo, usado na era Omniverse (2013)",
  },
};