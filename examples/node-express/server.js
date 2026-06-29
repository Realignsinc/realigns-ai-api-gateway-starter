import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = Number(process.env.PORT || 3000);
const GATEWAY_URL = String(process.env.REALIGNS_GATEWAY_URL || 'https://gpt-api.realignsinc.com/ai/chat').trim();
const API_KEY = String(process.env.REALIGNS_AI_API_KEY || '').trim();
const MAX_MESSAGE_CHARS = Number(process.env.MAX_MESSAGE_CHARS || 3000);
const MAX_DOCUMENT_CHARS = Number(process.env.MAX_DOCUMENT_CHARS || 6000);
const MAX_GATEWAY_CHARS = Number(process.env.MAX_GATEWAY_CHARS || 8500);

app.use(cors({ origin: true }));
app.use(express.json({ limit: '1mb' }));
app.use(express.static(path.join(__dirname, 'public')));

function cleanText(value, maxChars) {
  return String(value || '')
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/[ \t]+/g, ' ')
    .replace(/\r\n|\r/g, '\n')
    .replace(/\n{4,}/g, '\n\n\n')
    .trim()
    .slice(0, maxChars);
}

function compactDocumentContext(value, maxChars) {
  const text = cleanText(value, maxChars * 2);
  const lines = text
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => !(line.length > 900 && /^[A-Za-z0-9+/=._-]+$/.test(line)))
    .map((line) => cleanText(line, 900));

  const cleaned = lines.join('\n').replace(/\n{4,}/g, '\n\n\n').trim();

  if (cleaned.length <= maxChars) {
    return cleaned;
  }

  const headLength = Math.floor(maxChars * 0.72);
  const tailLength = Math.max(600, maxChars - headLength - 160);

  return `${cleaned.slice(0, headLength).trim()}\n\n[Document shortened to fit gateway request. Middle content omitted.]\n\n${cleaned.slice(-tailLength).trim()}`;
}

function compactGatewayMessage(message, maxChars) {
  if (message.length <= maxChars) {
    return message;
  }

  const headLength = Math.floor(maxChars * 0.8);
  const tailLength = Math.max(500, maxChars - headLength - 120);

  return `${message.slice(0, headLength).trim()}\n\n[Request shortened to fit gateway limit.]\n\n${message.slice(-tailLength).trim()}`;
}

function extractGatewayReply(data) {
  const candidates = [
    data?.reply,
    data?.message,
    data?.answer,
    data?.output,
    data?.text,
    data?.data?.reply,
    data?.data?.message,
    data?.choices?.[0]?.message?.content,
    data?.choices?.[0]?.text
  ];

  for (const candidate of candidates) {
    if (typeof candidate === 'string' && candidate.trim()) {
      return candidate.trim();
    }
  }

  return '';
}

async function writeJsonl(fileName, payload) {
  try {
    const logsDir = path.join(__dirname, 'logs');
    await fs.mkdir(logsDir, { recursive: true });
    const line = JSON.stringify({ ...payload, time: new Date().toISOString() });
    await fs.appendFile(path.join(logsDir, fileName), `${line}\n`, 'utf8');
  } catch {
    // Logging must never break chat responses.
  }
}

async function logError(message, context = {}) {
  await writeJsonl('error_logs.jsonl', {
    type: 'error',
    message,
    context
  });
}

app.get('/api/health', (req, res) => {
  res.json({
    ok: true,
    service: 'realigns-ai-api-gateway-node-express-example'
  });
});

app.post('/api/chat', async (req, res) => {
  try {
    if (!API_KEY || API_KEY === 'rk_live_your_key_here' || API_KEY === 'rk_live_') {
      await logError('Missing API key');
      return res.status(500).json({
        ok: false,
        error: 'Gateway API key is not configured on the server.',
        status: 'missing_api_key'
      });
    }

    const userMessage = cleanText(req.body?.message || req.body?.prompt || '', MAX_MESSAGE_CHARS);
    const documentContext = compactDocumentContext(req.body?.document_context || '', MAX_DOCUMENT_CHARS);

    if (!userMessage) {
      return res.status(400).json({
        ok: false,
        error: 'Invalid prompt.'
      });
    }

    let messageToGateway = userMessage;

    if (documentContext) {
      messageToGateway = [
        'You are reading a user-provided document. Answer from the document context when relevant.',
        'If the answer is not present in the document, say that the document does not contain enough information.',
        '',
        'Document context:',
        documentContext,
        '',
        'User question:',
        userMessage
      ].join('\n');
    }

    messageToGateway = compactGatewayMessage(messageToGateway, MAX_GATEWAY_CHARS);

    const gatewayResponse = await fetch(GATEWAY_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': API_KEY
      },
      body: JSON.stringify({
        message: messageToGateway,
        prompt: messageToGateway
      })
    });

    const rawText = await gatewayResponse.text();
    let data;

    try {
      data = JSON.parse(rawText);
    } catch {
      await logError('Invalid gateway JSON', {
        http_code: gatewayResponse.status,
        response_preview: cleanText(rawText, 500)
      });
      return res.status(502).json({
        ok: false,
        error: 'Invalid gateway response.',
        status: 'invalid_gateway_json',
        http_code: gatewayResponse.status
      });
    }

    if (!gatewayResponse.ok) {
      await logError('Gateway rejected request', {
        http_code: gatewayResponse.status,
        has_document: Boolean(documentContext),
        message_chars: messageToGateway.length
      });
      return res.status(502).json({
        ok: false,
        error: 'Gateway rejected the request. Try a shorter document or paste only the relevant section.',
        status: 'gateway_rejected',
        http_code: gatewayResponse.status
      });
    }

    const reply = extractGatewayReply(data);

    if (!reply) {
      await logError('Empty gateway reply', {
        http_code: gatewayResponse.status,
        keys: Object.keys(data || {})
      });
      return res.status(502).json({
        ok: false,
        error: 'Empty gateway reply.',
        status: 'empty_gateway_reply',
        http_code: gatewayResponse.status
      });
    }

    await writeJsonl('chat_logs.jsonl', {
      type: 'chat',
      has_document: Boolean(documentContext),
      prompt: cleanText(userMessage, 700),
      reply: cleanText(reply, 1200)
    });

    return res.json({
      ok: true,
      reply,
      message: reply,
      http_code: gatewayResponse.status
    });
  } catch (error) {
    await logError('Server error', { message: error?.message || 'unknown' });
    return res.status(500).json({
      ok: false,
      error: 'Server error.',
      status: 'server_error'
    });
  }
});

app.listen(PORT, '127.0.0.1', () => {
  console.log(`Realigns AI API Gateway Node Express example running on http://127.0.0.1:${PORT}`);
});
