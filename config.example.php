<?php
declare(strict_types=1);

/*
  Realigns AI API Gateway Starter
  Copy config.local.example.php to config.local.php and paste your server-side API key.

  Do NOT expose this key in frontend JavaScript.
  Do NOT commit config.local.php to public GitHub.
*/

return [
    "api_key" => "rk_live_your_key_here",
    "gateway_url" => "https://gpt-api.realignsinc.com/ai/chat",
    "max_message_chars" => 3000,
    "max_document_chars" => 6000,
    "chat_log_limit" => 200,
    "max_gateway_chars" => 8500
];
