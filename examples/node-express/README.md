# Realigns AI API Gateway — Node.js Express Example

Clean Node.js + Express starter for calling the Realigns AI API Gateway with the API key kept server-side.

## Features

- Express backend
- Browser chat UI
- Optional document context
- Local chat/error logs
- API key stored in `.env`
- No web search
- No model name exposed
- No frontend API-key exposure

## Requirements

- Node.js 18+
- npm

## Install

```bash
cd examples/node-express
npm install
cp .env.example .env
```

Edit `.env`:

```bash
REALIGNS_AI_API_KEY=rk_live_your_key_here
```

Start:

```bash
npm start
```

Open:

```text
http://127.0.0.1:3000
```

## API

POST:

```text
/api/chat
```

JSON body:

```json
{
  "message": "Summarize this document",
  "document_context": "Optional document text"
}
```

Response:

```json
{
  "ok": true,
  "reply": "Assistant reply here"
}
```

## Security notes

- Do not commit `.env`.
- Do not place the API key in frontend JavaScript.
- Keep the key only in the server environment.
- Rotate any key that was ever pasted in public.

## Logs

Runtime logs are written locally to:

```text
logs/chat_logs.jsonl
logs/error_logs.jsonl
```

These log files are ignored by Git.
