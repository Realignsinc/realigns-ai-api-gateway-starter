(() => {
  "use strict";

  const $ = (selector) => document.querySelector(selector);

  const form = $("#chatForm");
  const input = $("#messageInput");
  const sendBtn = $("#sendBtn");
  const messages = $("#messages");
  const statusPill = $("#statusPill");
  const documentFile = $("#documentFile");
  const documentText = $("#documentText");
  const documentMeta = $("#documentMeta");
  const clearDocumentBtn = $("#clearDocumentBtn");
  const newChatBtn = $("#newChatBtn");
  const ttsToggle = $("#ttsToggle");
  const stopTtsBtn = $("#stopTtsBtn");

  const state = {
    busy: false,
    loadedFileName: "",
    maxBrowserDocumentChars: 6000
  };

  function setStatus(text, mode = "ok") {
    statusPill.textContent = text;
    statusPill.style.color = mode === "error" ? "#ff8a80" : mode === "busy" ? "#ffd166" : "#79f2c0";
  }

  function escapeText(text) {
    return String(text || "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "\"": "&quot;",
      "'": "&#039;"
    }[char]));
  }

  function addMessage(role, text, extraClass = "") {
    const row = document.createElement("div");
    row.className = `message ${role} ${extraClass}`.trim();

    if (role === "assistant") {
      const avatar = document.createElement("div");
      avatar.className = "avatar";
      avatar.textContent = "R";
      row.appendChild(avatar);
    }

    const bubble = document.createElement("div");
    bubble.className = "bubble";
    bubble.innerHTML = escapeText(text);

    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    return row;
  }

  function setBusy(isBusy) {
    state.busy = isBusy;
    sendBtn.disabled = isBusy;
    input.disabled = isBusy;
    setStatus(isBusy ? "Thinking" : "Ready", isBusy ? "busy" : "ok");
  }

  function getDocumentContext() {
    return documentText.value.trim().slice(0, state.maxBrowserDocumentChars);
  }

  function updateDocumentMeta() {
    const length = getDocumentContext().length;
    const filePart = state.loadedFileName ? `${state.loadedFileName} · ` : "";
    documentMeta.textContent = length > 0 ? `${filePart}${length.toLocaleString()} characters loaded` : "No document loaded";
  }

  function speak(text) {
    if (!ttsToggle.checked || !("speechSynthesis" in window)) {
      return;
    }

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(String(text || ""));
    utterance.rate = 1;
    utterance.pitch = 1;
    utterance.volume = 1;
    window.speechSynthesis.speak(utterance);
  }

  async function sendMessage(message) {
    const loadingRow = addMessage("assistant", "Thinking", "");
    loadingRow.querySelector(".bubble").classList.add("loading-dot");

    const response = await fetch("api/chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        message,
        document_context: getDocumentContext()
      })
    });

    const data = await response.json().catch(() => null);
    loadingRow.remove();

    if (!response.ok || !data || data.ok === false) {
      const errorText = data?.error || data?.status || "Request failed.";
      addMessage("assistant", errorText, "error");
      setStatus("Error", "error");
      return;
    }

    const reply = data.reply || data.message || "";
    addMessage("assistant", reply);
    speak(reply);
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const message = input.value.trim();

    if (!message || state.busy) {
      return;
    }

    input.value = "";
    addMessage("user", message);
    setBusy(true);

    try {
      await sendMessage(message);
      setStatus("Ready");
    } catch (error) {
      addMessage("assistant", "Network or server error. Please check the gateway configuration.", "error");
      setStatus("Error", "error");
    } finally {
      setBusy(false);
      input.focus();
    }
  });

  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  documentFile.addEventListener("change", async () => {
    const file = documentFile.files && documentFile.files[0];
    if (!file) {
      return;
    }

    state.loadedFileName = file.name;

    try {
      const text = await file.text();

      if (!text || /[\u0000-\u0008\u000E-\u001F]/.test(text.slice(0, 2000))) {
        documentText.value = "";
        updateDocumentMeta();
        setStatus("Unsupported document", "error");
        addMessage("assistant", "This starter reads text-based documents only. Please paste text from PDF/DOCX or upload .txt, .md, .csv, or .json.", "error");
        return;
      }

      documentText.value = text.slice(0, state.maxBrowserDocumentChars);
      updateDocumentMeta();
      setStatus("Document loaded");
    } catch (error) {
      setStatus("Document error", "error");
      documentMeta.textContent = "Could not read this file";
    }
  });

  documentText.addEventListener("input", () => {
    state.loadedFileName = "";
    updateDocumentMeta();
  });

  clearDocumentBtn.addEventListener("click", () => {
    documentFile.value = "";
    documentText.value = "";
    state.loadedFileName = "";
    updateDocumentMeta();
  });

  newChatBtn.addEventListener("click", () => {
    window.speechSynthesis?.cancel?.();
    messages.innerHTML = "";
    addMessage("assistant", "New chat started. Add a document if needed, then ask your question.");
    input.focus();
  });

  stopTtsBtn.addEventListener("click", () => {
    window.speechSynthesis?.cancel?.();
  });

  updateDocumentMeta();
})();
