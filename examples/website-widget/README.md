# Realigns AI Website Chatbot Widget

A lightweight website chatbot widget for Shopify, Hostinger, cPanel, and normal websites.

The widget is public frontend JavaScript, but the Realigns AI API key stays private on the server-side proxy.

## How it works

```text
Website page
→ realigns-chat-widget.js
→ private PHP proxy
→ Realigns AI API Gateway
```

## Features

- Floating chatbot button
- Mobile responsive chat window
- One script tag install
- Custom title and welcome message
- PHP proxy for cPanel/Hostinger/shared hosting
- Server-side API key only
- No web search
- No model name exposed
- No frontend API-key exposure

## Folder structure

```text
examples/website-widget/
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

## Install for cPanel / Hostinger

1. Upload this folder to your website, for example:

```text
/realigns-widget/
```

2. Copy:

```text
php-proxy/config.local.example.php
```

to:

```text
php-proxy/config.local.php
```

3. Paste your Realigns AI API key into `config.local.php`.

4. Add this snippet before `</body>` on your website:

```html
<link rel="stylesheet" href="/realigns-widget/widget/realigns-chat-widget.css">
<script
  src="/realigns-widget/widget/realigns-chat-widget.js"
  data-realigns-endpoint="/realigns-widget/php-proxy/chat.php"
  data-realigns-title="AI Assistant"
  data-realigns-welcome="Hi, how can I help you today?"
  defer></script>
```

## Shopify install

Use `install-snippets/shopify-theme-liquid.html` as a starting point.

Important: do not paste the Realigns API key into Shopify theme code. Keep the key in your server-side proxy only.

## Security notes

- The widget JavaScript is public.
- The PHP proxy is private server-side code.
- The Realigns API key must be stored only in `php-proxy/config.local.php` or a server environment variable.
- Do not commit `config.local.php`.
- Rotate any API key that was ever shared publicly.
