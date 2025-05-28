<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Selector de Dispositivos WebRTC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://jssip.net/download/releases/jssip-3.10.0.min.js"></script>
  <style>
    body {
      background-color: #0a2540;
      color: white;
      font-family: sans-serif;
      padding: 20px;
      text-align: center;
    }
    select {
      margin: 10px;
      padding: 5px;
      width: 80%;
    }
    button {
      margin-top: 15px;
      font-size: 20px;
    }
    audio {
      display: block;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <h1>Seleccionar dispositivos WebRTC</h1>

  <div>
    <label for="videoSource">Cámara:</label><br>
    <select id="videoSource"></select>
  </div>

  <div>
    <label for="audioInput">Micrófono:</label><br>
    <select id="audioInput"></select>
  </div>

  <div>
    <label for="audioOutput">Altavoz:</label><br>
    <select id="audioOutput"></select>
  </div>

  <button id="callBtn" class="btn btn-danger" disabled>Iniciar llamada</button>
  <button id="hangupBtn" class="btn btn-secondary" disabled>Colgar</button>

  <div id="status" style="margin-top:20px;">Cargando configuración...</div>

  <audio id="remoteAudio" autoplay></audio>

  <script>
    let ua, session;
    let config = {};

    async function obtenerConfig() {
      try {
        const resp = await fetch("https://zonapulsodevida.mx/llamada/sip-config.php?token=S3curity2025");
        if (!resp.ok) throw new Error("No autorizado");
        config = await resp.json();

        const socket = new JsSIP.WebSocketInterface(`wss://${config.servidorIP}:8089/ws`);
        ua = new JsSIP.UA({
          sockets: [socket],
          uri: `sip:${config.usuario}@${config.servidorIP}`,
          password: config.clave,
          session_timers: false,
          register: true
        });

        ua.on('connected', () => document.getElementById("status").textContent = "Conectado");
        ua.on('disconnected', () => document.getElementById("status").textContent = "Desconectado");
        ua.on('registered', () => {
          document.getElementById("status").textContent = "Registrado";
          document.getElementById("callBtn").disabled = false;
        });
        ua.on('registrationFailed', () => document.getElementById("status").textContent = "Fallo al registrar");

        ua.on('newRTCSession', function(e) {
          session = e.session;
          session.connection.addEventListener("addstream", (event) => {
            const remoteAudio = document.getElementById("remoteAudio");
            remoteAudio.srcObject = event.stream;
          });

          session.on('ended', finalizarLlamada);
          session.on('failed', finalizarLlamada);
        });

        ua.start();
      } catch (err) {
        console.error("Error al obtener configuración:", err);
        alert("No se pudo obtener la configuración SIP");
      }
    }

    async function cargarDispositivos() {
      const devices = await navigator.mediaDevices.enumerateDevices();

      const videoSelect = document.getElementById("videoSource");
      const audioInputSelect = document.getElementById("audioInput");
      const audioOutputSelect = document.getElementById("audioOutput");

      videoSelect.innerHTML = '';
      audioInputSelect.innerHTML = '';
      audioOutputSelect.innerHTML = '';

      for (const device of devices) {
        const option = document.createElement("option");
        option.value = device.deviceId;
        option.text = device.label || `${device.kind} sin nombre`;

        if (device.kind === "videoinput") videoSelect.appendChild(option);
        else if (device.kind === "audioinput") audioInputSelect.appendChild(option);
        else if (device.kind === "audiooutput") audioOutputSelect.appendChild(option);
      }
    }

    async function iniciarLlamada() {
      try {
        const videoId = document.getElementById("videoSource").value;
        const audioId = document.getElementById("audioInput").value;
        const outputId = document.getElementById("audioOutput").value;

        const stream = await navigator.mediaDevices.getUserMedia({
          video: { deviceId: { exact: videoId } },
          audio: { deviceId: { exact: audioId } }
        });

        const remoteAudio = document.getElementById("remoteAudio");
        if (typeof remoteAudio.setSinkId === "function" && outputId) {
          await remoteAudio.setSinkId(outputId);
          console.log("Salida configurada:", outputId);
        }

        session = ua.call(`sip:${config.destino}@${config.servidorIP}`, {
          mediaStream: stream,
          mediaConstraints: { audio: true, video: true },
          rtcOfferConstraints: { offerToReceiveAudio: true, offerToReceiveVideo: true }
        });

        document.getElementById("callBtn").disabled = true;
        document.getElementById("hangupBtn").disabled = false;
        document.getElementById("status").textContent = "Llamando...";
      } catch (err) {
        console.error("Error al iniciar llamada:", err);
        alert("Error accediendo a los dispositivos");
      }
    }

    function finalizarLlamada() {
      document.getElementById("callBtn").disabled = false;
      document.getElementById("hangupBtn").disabled = true;
      document.getElementById("status").textContent = "Llamada finalizada";
    }

    document.getElementById("callBtn").addEventListener("click", iniciarLlamada);
    document.getElementById("hangupBtn").addEventListener("click", () => {
      if (session) session.terminate();
    });

    // Pedir permisos y cargar dispositivos
    navigator.mediaDevices.getUserMedia({ audio: true, video: true }).then(() => {
      cargarDispositivos();
    }).catch(err => {
      alert("Se requieren permisos para acceder a cámara y micrófono");
    });

    obtenerConfig();
  </script>

</body>
</html>
