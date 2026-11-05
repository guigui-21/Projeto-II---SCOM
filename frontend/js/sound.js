/**
 * sound.js
 * Controla os efeitos sonoros do Omnitrix Codex.
 *
 * Os sons reais (arquivos do proprio estudante, em assets/audio/) sao
 * tocados por elementos <audio> ja presentes no HTML. Caso o navegador nao
 * consiga reproduzir nenhum dos formatos oferecidos (mp3/m4a) - por exemplo,
 * se os arquivos nao forem encontrados -, um bipe sintetizado via Web Audio
 * API e usado como alternativa, para que a interacao nunca fique "muda".
 */

const OmnitrixSound = (() => {
  let enabled = true;
  let audioCtx = null;

  const elementos = {
    abertura: document.getElementById("audio-abertura"),
    troca: document.getElementById("audio-troca"),
    escolha: document.getElementById("audio-escolha"),
    descarregou: document.getElementById("audio-descarregou"),
  };

  function getContext() {
    if (!audioCtx) {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      audioCtx = new AudioContextClass();
    }
    return audioCtx;
  }

  // Bipe sintetizado de reserva (usado apenas se o <audio> real falhar).
  function beepFallback(freqStart, freqEnd, duracao) {
    try {
      const ctx = getContext();
      const now = ctx.currentTime;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = "square";
      osc.frequency.setValueAtTime(freqStart, now);
      osc.frequency.exponentialRampToValueAtTime(freqEnd, now + duracao * 0.7);

      gain.gain.setValueAtTime(0.0001, now);
      gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + duracao);

      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(now);
      osc.stop(now + duracao + 0.02);
    } catch (err) {
      console.warn("Nao foi possivel reproduzir o som de reserva.", err);
    }
  }

  function playReal(chave, fallback) {
    if (!enabled) return;
    const el = elementos[chave];

    if (!el) {
      fallback();
      return;
    }

    try {
      el.pause();
      el.currentTime = 0;
      const promessa = el.play();
      if (promessa && typeof promessa.catch === "function") {
        promessa.catch(() => {
          fallback();
        });
      }
    } catch (err) {
      fallback();
    }
  }

  function playAbertura() {
    playReal("abertura", () => beepFallback(200, 500, 0.3));
  }

  function playTroca() {
    playReal("troca", () => beepFallback(320, 720, 0.16));
  }

  function playEscolha() {
    playReal("escolha", () => beepFallback(440, 880, 0.22));
  }

  function playDescarregou() {
    playReal("descarregou", () => beepFallback(500, 150, 0.35));
  }

  function isEnabled() {
    return enabled;
  }

  function setEnabled(value) {
    enabled = value;
    if (!value) {
      Object.values(elementos).forEach((el) => {
        if (el) el.pause();
      });
    }
  }

  return {
    playAbertura,
    playTroca,
    playEscolha,
    playDescarregou,
    isEnabled,
    setEnabled,
  };
})();