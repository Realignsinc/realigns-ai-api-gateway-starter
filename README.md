# Realigns Inc. AI API Gateway Starter

Open-source PHP starter kit for using the Realigns AI API Gateway from a browser chat UI.

Users can upload the folder to shared hosting or cPanel, paste their Realigns AI API key server-side, and start chatting.

## Features

- Browser chat UI
- Server-side Realigns AI API key protection
- Chat logs
- Error logs
- Document context support
- Browser text-to-speech using Safari, Chrome, Firefox browser speech APIs
- No web search
- No model name shown to users
- No frontend exposure of API keys

## Requirements

- PHP 8.0+
- PHP cURL extension enabled
- HTTPS recommended

## Install

1. Upload all files to your hosting folder.
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

## Secure API key setup

Recommended production method:

```bash
REALIGNS_AI_API_KEY=rk_live_your_key_here
```

If your hosting panel does not support environment variables, use `config.local.php`.

Do not put your API key in JavaScript.

## Files

```text
index.php                  Browser UI
config.example.php          Example config file
config.local.example.php    Private config template
api/chat.php                Gateway bridge endpoint
api/logs.php                Protected local log viewer endpoint
assets/app.js               Chat UI, document context, TTS
assets/styles.css           Dark workspace UI styles
storage/.htaccess           Blocks direct web access on Apache
```

## Document support

This starter supports lightweight document context from browser-readable text files:

- `.txt`
- `.md`
- `.csv`
- `.json`
- text/code files

For PDF/DOCX parsing, copy and paste the document text into the document box or add a server-side parser later. This starter intentionally avoids heavy dependencies so it works on simple PHP/cPanel hosting.

## API endpoint

POST:

```text
/api/chat.php
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

- Chat logs are stored locally in `storage/chat_logs.jsonl`.
- Error logs are stored locally in `storage/error_logs.jsonl`.
- API key is used only server-side.
- The frontend never receives the API key.
- `config.local.php` and log files are ignored by Git.

## Optional installer script

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
