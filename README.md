# Realigns Inc. AI API Gateway Starter

Open-source starter kits for using the Realigns AI API Gateway from browser, server, and website chatbot integrations.

This repository includes a PHP web tool, a Node.js Express example, and a website chatbot widget for Shopify, cPanel, WordPress, Hostinger, and normal websites. In every option, the Realigns AI API key stays server-side and is never exposed in frontend JavaScript.

## Available starters

```text
Root PHP web tool             PHP/cPanel browser chat tool
examples/node-express/        Node.js + Express backend example
examples/website-widget/      Website chatbot widget for Shopify, cPanel, WordPress, Hostinger
```

## Shared features

- Browser chat UI
- Server-side Realigns AI API key protection
- Chat logs where supported
- Error logs where supported
- Document context support where supported
- No web search
- No model name shown to users
- No frontend exposure of API keys

## Test your Realigns AI API key with cURL

Before installing any starter, you can test your API key directly from Terminal:

```bash
curl -X POST https://gpt-api.realignsinc.com/ai/chat \
-H "Content-Type: application/json" \
-H "x-api-key: rk_live_YOUR_REALIGNS_API_KEY_HERE" \
-d '{"prompt":"Say hello."}'
```

Replace `rk_live_YOUR_REALIGNS_API_KEY_HERE` with your own Realigns AI API key. Do not commit or expose your real key publicly.

## PHP web tool / cPanel starter

The root project is a simple PHP web tool for shared hosting, cPanel, Hostinger PHP hosting, and normal PHP websites.

### Requirements

- PHP 8.0+
- PHP cURL extension enabled
- HTTPS recommended

### Install

1. Upload all root files to your hosting folder.
2. Copy `config.local.example.php` to `config.local.php`.
3. Paste your Realigns AI API key in `config.local.php`.
4. Open `index.php` in your browser.

Example:

```php
<?php
return [
    "api_key" => "rk_live_your_key_here"
];
```

### Secure API key setup

Recommended production method:

```bash
REALIGNS_AI_API_KEY=rk_live_your_key_here
```

If your hosting panel does not support environment variables, use `config.local.php`.

Do not put your API key in JavaScript.

## Node.js Express example

A clean Node.js + Express example is available in:

```text
examples/node-express/
```

### Requirements

- Node.js 18+
- npm

### Install

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

The Node example includes:

- Express backend
- Browser chat UI
- Optional document context
- Local chat/error logs
- `.env` server-side API key
- No frontend API-key exposure

## Website chatbot widget

A lightweight website chatbot widget is available in:

```text
examples/website-widget/
```

It is designed for:

- Shopify websites and Shopify theme/custom liquid snippets
- cPanel websites
- WordPress websites where custom HTML/footer scripts are allowed
- Hostinger websites
- Normal HTML/PHP websites

### Install idea

Upload `examples/website-widget/` as:

```text
/realigns-widget/
```

Copy:

```text
php-proxy/config.local.example.php
```

to:

```text
php-proxy/config.local.php
```

Paste your private Realigns AI API key in `config.local.php`, then add this snippet before `</body>`:

```html
<link rel="stylesheet" href="/realigns-widget/widget/realigns-chat-widget.css">
<script
  src="/realigns-widget/widget/realigns-chat-widget.js"
  data-realigns-endpoint="/realigns-widget/php-proxy/chat.php"
  data-realigns-title="AI Assistant"
  data-realigns-welcome="Hi, how can I help you today?"
  defer></script>
```

Shopify users should host the PHP proxy on their own domain/server and paste only the widget script snippet into Shopify theme/custom liquid code. Do not paste the API key into Shopify.

WordPress users can paste the widget snippet into a footer/custom HTML area or a safe header-footer script plugin, while keeping the PHP proxy and API key on their own server.

## Examples folder

```text
examples/
├── node-express/
│   ├── server.js
│   ├── package.json
│   ├── .env.example
│   ├── public/index.html
│   └── README.md
└── website-widget/
    ├── README.md
    ├── widget/
    │   ├── realigns-chat-widget.js
    │   └── realigns-chat-widget.css
    ├── php-proxy/
    │   ├── chat.php
    │   ├── config.local.example.php
    │   └── .htaccess
    └── install-snippets/
        ├── cpanel.html
        ├── hostinger.html
        └── shopify-theme-liquid.html
```

## Files

```text
index.php                                           PHP browser UI
config.example.php                                  Example PHP config
config.local.example.php                            Private PHP config template
api/chat.php                                        PHP gateway bridge endpoint
api/logs.php                                        Protected PHP local log viewer endpoint
assets/app.js                                       PHP starter chat UI logic
assets/styles.css                                   PHP starter workspace UI styles
storage/.htaccess                                   Blocks direct storage access on Apache
examples/node-express/server.js                     Node Express gateway bridge
examples/node-express/public/index.html             Node example browser UI
examples/node-express/.env.example                  Node private config template
examples/website-widget/widget/realigns-chat-widget.js
examples/website-widget/widget/realigns-chat-widget.css
examples/website-widget/php-proxy/chat.php
examples/website-widget/php-proxy/config.local.example.php
examples/website-widget/install-snippets/
```

## Document support

The PHP and Node starters support lightweight document context from browser-readable text files or pasted text:

- `.txt`
- `.md`
- `.csv`
- `.json`
- text/code files

For PDF/DOCX parsing, copy and paste the document text into the document box or add a server-side parser later. These starters intentionally avoid heavy dependencies so they can run on simple hosting or basic Node servers.

## API endpoints

PHP web tool:

```text
/api/chat.php
```

Node Express example:

```text
/api/chat
```

Website widget PHP proxy:

```text
/realigns-widget/php-proxy/chat.php
```

JSON body:

```json
{
  "message": "Summarize this document",
  "document_context": "Optional pasted/uploaded document text"
}
```

Response:

```json
{
  "ok": true,
  "reply": "Assistant reply here"
}
```

## Privacy notes

- API keys are used only server-side.
- The frontend never receives the API key.
- PHP chat logs are stored locally in `storage/chat_logs.jsonl`.
- PHP error logs are stored locally in `storage/error_logs.jsonl`.
- Node logs are stored locally in `examples/node-express/logs/`.
- `config.local.php`, `.env`, and log files are ignored by Git.
- Website widget JavaScript is public, but the PHP proxy key config is private.

## Optional PHP installer script

```bash
chmod +x realigns-ai-api-gateway-starter-install.sh
./realigns-ai-api-gateway-starter-install.sh
```

You can also pass a ZIP path manually:

```bash
./realigns-ai-api-gateway-starter-install.sh path/to/realigns-ai-api-gateway-starter.zip
```

The script creates `config.local.php` from `config.local.example.php` if it does not already exist.

## Important

If an API key was ever posted publicly, rotate it before publishing or using this project.
