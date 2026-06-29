<?php
declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Realigns AI Gateway Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="ambient ambient-one"></div>
  <div class="ambient ambient-two"></div>

  <main class="app-shell">
    <aside class="sidebar">
      <div class="brand-block">
        <div class="brand-mark">R</div>
        <div>
          <p class="brand-kicker">Realigns</p>
          <h1>AI Gateway</h1>
        </div>
      </div>

      <nav class="nav-stack" aria-label="Workspace navigation">
        <button type="button" class="nav-item active"><span class="nav-icon">✦</span><span>Chat</span></button>
        <button type="button" class="nav-item"><span class="nav-icon">◈</span><span>Documents</span></button>
        <button type="button" class="nav-item"><span class="nav-icon">◌</span><span>Voice</span></button>
      </nav>

      <div class="side-card">
        <p class="side-label">Gateway status</p>
        <div class="status-pill" id="statusPill">Ready</div>
      </div>

      <div class="side-note">
        <strong>Private key setup</strong>
        <span>Keep the API key server-side in config.local.php or environment variables.</span>
      </div>
    </aside>

    <section class="main-workspace">
      <header class="topbar">
        <div>
          <p class="eyebrow">Realigns AI API Gateway</p>
          <h2>Private Workspace Chat</h2>
          <p class="subtitle">A clean browser workspace for chat, document context, local logs, and browser voice output.</p>
        </div>

        <div class="top-actions">
          <span class="secure-badge">No model name exposed</span>
          <button type="button" id="newChatBtn" class="ghost-btn">New chat</button>
        </div>
      </header>

      <section class="workspace-grid">
        <aside class="document-panel">
          <div class="panel-card">
            <div class="card-heading">
              <div>
                <p class="section-kicker">Context</p>
                <h3>Document reader</h3>
              </div>
              <span class="mini-badge">Text files</span>
            </div>

            <p class="card-copy">Upload or paste text. The content is attached as context with your next message.</p>

            <label class="file-drop">
              <input id="documentFile" type="file" accept=".txt,.md,.csv,.json,.log,.xml,.html,.css,.js,.php">
              <span class="file-icon">↑</span>
              <span><strong>Choose text document</strong><small>.txt, .md, .csv, .json and code/text files</small></span>
            </label>

            <textarea id="documentText" placeholder="Paste document text here..."></textarea>

            <div class="mini-actions">
              <button type="button" id="clearDocumentBtn" class="ghost-btn compact">Clear</button>
              <span id="documentMeta">No document loaded</span>
            </div>
          </div>

          <div class="panel-card small">
            <div class="card-heading">
              <div>
                <p class="section-kicker">Audio</p>
                <h3>Browser voice</h3>
              </div>
              <span class="mini-badge">TTS</span>
            </div>

            <p class="card-copy">Uses browser speech synthesis where supported by Safari, Chrome, or Firefox.</p>

            <label class="toggle-row">
              <input type="checkbox" id="ttsToggle">
              <span>Read assistant replies aloud</span>
            </label>

            <button type="button" id="stopTtsBtn" class="ghost-btn wide">Stop voice</button>
          </div>
        </aside>

        <section class="chat-panel">
          <div class="chat-card">
            <div class="chat-header">
              <div>
                <p class="section-kicker">Assistant</p>
                <h3>Gateway chat</h3>
              </div>
              <div class="chat-rules"><span>No web search</span><span>Server-side key</span></div>
            </div>

            <div class="messages" id="messages" aria-live="polite">
              <div class="message assistant">
                <div class="avatar">R</div>
                <div class="bubble">Hello. Add a document if needed, then ask your question.</div>
              </div>
            </div>

            <form id="chatForm" class="composer">
              <textarea id="messageInput" rows="2" placeholder="Ask Realigns Gateway..." autocomplete="off"></textarea>
              <button type="submit" id="sendBtn"><span>Send</span><span class="send-arrow">↗</span></button>
            </form>
          </div>
        </section>
      </section>
    </section>
  </main>

  <script src="assets/app.js"></script>
</body>
</html>
