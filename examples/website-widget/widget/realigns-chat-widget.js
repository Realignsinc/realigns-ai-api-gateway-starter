(() => {
  'use strict';

  const currentScript = document.currentScript;
  const endpoint = currentScript?.dataset?.realignsEndpoint || '/realigns-widget/php-proxy/chat.php';
  const title = currentScript?.dataset?.realignsTitle || 'AI Assistant';
  const welcome = currentScript?.dataset?.realignsWelcome || 'Hi, how can I help you today?';
  const placeholder = currentScript?.dataset?.realignsPlaceholder || 'Type your message...';

  if (window.__realignsChatWidgetLoaded) {
    return;
  }
  window.__realignsChatWidgetLoaded = true;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
  }

  const launcher = el('button', 'realigns-chat-launcher', 'R');
  launcher.type = 'button';
  launcher.setAttribute('aria-label', 'Open chat');

  const panel = el('section', 'realigns-chat-window');
  panel.setAttribute('data-open', 'false');
  panel.setAttribute('aria-label', title);

  const header = el('div', 'realigns-chat-header');
  const titleWrap = el('div', 'realigns-chat-title');
  const mark = el('div', 'realigns-chat-mark', 'R');
  const titleText = el('div');
  const strong = el('strong', '', title);
  const sub = el('span', '', 'Online');
  const close = el('button', 'realigns-chat-close', '×');
  close.type = 'button';
  close.setAttribute('aria-label', 'Close chat');

  titleText.append(strong, sub);
  titleWrap.append(mark, titleText);
  header.append(titleWrap, close);

  const messages = el('div', 'realigns-chat-messages');
  const form = el('form', 'realigns-chat-form');
  const input = el('textarea', 'realigns-chat-input');
  input.rows = 1;
  input.placeholder = placeholder;
  const send = el('button', 'realigns-chat-send', '↗');
  send.type = 'submit';
  form.append(input, send);

  panel.append(header, messages, form);
  document.body.append(launcher, panel);

  function addMessage(role, text, isError = false) {
    const row = el('div', 'realigns-chat-row');
    row.setAttribute('data-role', role);
    if (isError) row.setAttribute('data-error', 'true');
    const bubble = el('div', 'realigns-chat-bubble', text);
    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    return row;
  }

  function setOpen(open) {
    panel.setAttribute('data-open', open ? 'true' : 'false');
    launcher.setAttribute('aria-label', open ? 'Close chat' : 'Open chat');
    if (open) input.focus();
  }

  function setBusy(busy) {
    input.disabled = busy;
    send.disabled = busy;
  }

  launcher.addEventListener('click', () => {
    const isOpen = panel.getAttribute('data-open') === 'true';
    setOpen(!isOpen);
  });

  close.addEventListener('click', () => setOpen(false));

  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    addMessage('user', message);
    setBusy(true);
    const loading = addMessage('assistant', 'Thinking...');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
      });

      const data = await response.json().catch(() => null);
      loading.remove();

      if (!response.ok || !data || data.ok === false) {
        addMessage('assistant', data?.error || 'Chat request failed.', true);
        return;
      }

      addMessage('assistant', data.reply || data.message || '');
    } catch {
      loading.remove();
      addMessage('assistant', 'Network or server error.', true);
    } finally {
      setBusy(false);
      input.focus();
    }
  });

  addMessage('assistant', welcome);
})();
