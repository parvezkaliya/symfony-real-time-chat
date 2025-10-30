// public/js/chat.js
let ws;
let reconnectTimer;

function appendMessage(user, text, server, time) {
  const messages = document.getElementById('messages');
  const el = document.createElement('div');
  el.innerHTML = `<b>${user}</b> <small>(${server} @ ${time})</small>: ${text}`;
  messages.appendChild(el);
  messages.scrollTop = messages.scrollHeight;
}

function connect() {
  if (ws && ws.readyState === WebSocket.OPEN) ws.close();

  const port = document.getElementById('server').value;
  const url = `ws://localhost:${port}`;
  document.getElementById('status').innerText = `Connecting to ${url}...`;

  ws = new WebSocket(url);

  ws.onopen = () => {
    document.getElementById('status').innerText = `Connected to ${url}`;
    clearTimeout(reconnectTimer);
  };

  ws.onmessage = (evt) => {
    try {
      const data = JSON.parse(evt.data);
      appendMessage(data.user, data.text, data.server, data.time);
    } catch (e) {
      console.error('Invalid message', evt.data);
    }
  };

  ws.onclose = () => {
    document.getElementById('status').innerText = `Disconnected from ${url}, retrying...`;
    reconnectTimer = setTimeout(connect, 2000);
  };

  ws.onerror = (e) => {
    console.error('Socket error', e);
    ws.close();
  };
}

function send() {
  const text = document.getElementById('msg').value.trim();
  if (!text || !ws || ws.readyState !== WebSocket.OPEN) return;
  const user = document.getElementById('username').value || 'Anon';
  ws.send(JSON.stringify({ user, text }));
  document.getElementById('msg').value = '';
}
