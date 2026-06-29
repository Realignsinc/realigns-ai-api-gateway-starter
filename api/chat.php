<?php
declare(strict_types=1);

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

/*
  Realigns AI API Gateway Starter
  Browser UI -> chat.php -> Realigns AI API Gateway -> JSON reply

  No web search.
  No model name exposure.
  No API key exposure in frontend.
*/

function storage_path(string $file): string {
    $dir = dirname(__DIR__) . "/storage";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . "/" . $file;
}

function write_jsonl(string $file, array $payload): void {
    $payload["time"] = gmdate("c");
    $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        @file_put_contents(storage_path($file), $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function log_error_event(string $message, array $context = []): void {
    write_jsonl("error_logs.jsonl", [
        "type" => "error",
        "message" => $message,
        "context" => $context,
        "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown"
    ]);
}

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    log_error_event("PHP warning", [
        "severity" => $severity,
        "message" => $message,
        "file" => basename($file),
        "line" => $line
    ]);
    return true;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array((int)$error["type"], $fatalTypes, true)) {
        return;
    }

    log_error_event("PHP fatal error", [
        "message" => $error["message"] ?? "unknown",
        "file" => isset($error["file"]) ? basename((string)$error["file"]) : "unknown",
        "line" => $error["line"] ?? 0
    ]);

    if (!headers_sent()) {
        http_response_code(500);
        header("Content-Type: application/json; charset=utf-8");
    }

    echo json_encode([
        "ok" => false,
        "error" => "Server error.",
        "status" => "php_fatal_error"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function text_length(string $text): int {
    return function_exists("mb_strlen") ? mb_strlen($text) : strlen($text);
}

function text_slice(string $text, int $start, int $length): string {
    return function_exists("mb_substr") ? mb_substr($text, $start, $length) : substr($text, $start, $length);
}

function clean_text(string $text, int $max): string {
    $text = trim(strip_tags($text));
    $text = preg_replace("/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/u", "", $text);
    $text = preg_replace("/\\r\\n|\\r/u", "\n", (string)$text);
    $text = preg_replace("/[ \\t]+/u", " ", (string)$text);
    $text = preg_replace("/\\n{4,}/u", "\n\n\n", (string)$text);
    return text_slice((string)$text, 0, $max);
}

function compact_document_context(string $text, int $max): string {
    $text = clean_text($text, max($max * 2, $max));
    $lines = preg_split("/\n/u", $text);
    $cleanLines = [];

    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === "") {
                $cleanLines[] = "";
                continue;
            }
            if (strlen($line) > 900 && preg_match("/^[A-Za-z0-9+\/=._-]+$/", $line)) {
                continue;
            }
            $cleanLines[] = clean_text($line, 900);
        }
    }

    $text = trim(implode("\n", $cleanLines));
    $text = preg_replace("/\n{4,}/u", "\n\n\n", (string)$text);

    if (text_length($text) <= $max) {
        return $text;
    }

    $headLen = (int)floor($max * 0.72);
    $tailLen = max(600, $max - $headLen - 160);

    return trim(text_slice($text, 0, $headLen)) .
        "\n\n[Document shortened to fit gateway request. Middle content omitted.]\n\n" .
        trim(text_slice($text, max(0, text_length($text) - $tailLen), $tailLen));
}

function compact_gateway_message(string $message, int $max): string {
    if (text_length($message) <= $max) {
        return $message;
    }

    $headLen = (int)floor($max * 0.8);
    $tailLen = max(500, $max - $headLen - 120);

    return trim(text_slice($message, 0, $headLen)) .
        "\n\n[Request shortened to fit gateway limit.]\n\n" .
        trim(text_slice($message, max(0, text_length($message) - $tailLen), $tailLen));
}

function load_config(): array {
    $defaults = [
        "api_key" => "",
        "gateway_url" => "https://gpt-api.realignsinc.com/ai/chat",
        "max_message_chars" => 3000,
        "max_document_chars" => 6000,
        "chat_log_limit" => 200,
        "max_gateway_chars" => 8500
    ];

    $examplePath = dirname(__DIR__) . "/config.example.php";
    if (is_file($examplePath)) {
        $example = require $examplePath;
        if (is_array($example)) {
            $defaults = array_merge($defaults, $example);
        }
    }

    $localPath = dirname(__DIR__) . "/config.local.php";
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $defaults = array_merge($defaults, $local);
        }
    }

    $envKey = getenv("REALIGNS_AI_API_KEY");
    if (is_string($envKey) && trim($envKey) !== "") {
        $defaults["api_key"] = trim($envKey);
    }

    return $defaults;
}

function extract_gateway_reply(array $data): string {
    $paths = [
        ["reply"],
        ["message"],
        ["answer"],
        ["output"],
        ["text"],
        ["data", "reply"],
        ["data", "message"],
        ["choices", 0, "message", "content"],
        ["choices", 0, "text"]
    ];

    foreach ($paths as $path) {
        $value = $data;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                $value = null;
                break;
            }
            $value = $value[$key];
        }
        if (is_string($value) && trim($value) !== "") {
            return trim($value);
        }
    }

    return "";
}

function save_chat_log(string $prompt, string $reply, bool $hasDocument): void {
    write_jsonl("chat_logs.jsonl", [
        "type" => "chat",
        "ip" => $_SERVER["REMOTE_ADDR"] ?? "unknown",
        "has_document" => $hasDocument,
        "prompt" => clean_text($prompt, 700),
        "reply" => clean_text($reply, 1200)
    ]);
}

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    json_response(["ok" => false, "error" => "Method not allowed."], 405);
}

$config = load_config();
$apiKey = trim((string)($config["api_key"] ?? ""));
$gatewayUrl = trim((string)($config["gateway_url"] ?? ""));
$maxMessageChars = max(500, (int)($config["max_message_chars"] ?? 3000));
$maxDocumentChars = max(1000, (int)($config["max_document_chars"] ?? 6000));
$maxGatewayChars = max(3000, (int)($config["max_gateway_chars"] ?? 8500));

if ($apiKey === "" || $apiKey === "rk_live_your_key_here" || $apiKey === "rk_live_") {
    log_error_event("Missing API key");
    json_response([
        "ok" => false,
        "error" => "Gateway API key is not configured on the server.",
        "status" => "missing_api_key"
    ], 500);
}

if ($gatewayUrl === "" || !preg_match("/^https:\\/\\//i", $gatewayUrl)) {
    log_error_event("Invalid gateway URL");
    json_response([
        "ok" => false,
        "error" => "Gateway URL is not configured correctly.",
        "status" => "invalid_gateway_url"
    ], 500);
}

$inputRaw = file_get_contents("php://input");
$input = json_decode((string)$inputRaw, true);

if (!is_array($input)) {
    json_response(["ok" => false, "error" => "Invalid JSON input."], 400);
}

$userMessage = clean_text((string)($input["message"] ?? $input["prompt"] ?? ""), $maxMessageChars);
$documentContext = compact_document_context((string)($input["document_context"] ?? ""), $maxDocumentChars);

if ($userMessage === "") {
    json_response(["ok" => false, "error" => "Invalid prompt."], 400);
}

$messageToGateway = $userMessage;

if ($documentContext !== "") {
    $messageToGateway =
        "You are reading a user-provided document. Answer from the document context when relevant. " .
        "If the answer is not present in the document, say that the document does not contain enough information.\n\n" .
        "Document context:\n" .
        $documentContext .
        "\n\nUser question:\n" .
        $userMessage;
}

$messageToGateway = compact_gateway_message($messageToGateway, $maxGatewayChars);

$payload = json_encode([
    "message" => $messageToGateway,
    "prompt" => $messageToGateway
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($payload === false) {
    log_error_event("Failed to encode gateway payload");
    json_response(["ok" => false, "error" => "Request encoding failed."], 500);
}

$ch = curl_init($gatewayUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-key: " . $apiKey
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 12
]);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || trim((string)$response) === "") {
    log_error_event("Gateway unavailable", ["http_code" => $httpCode, "curl_error" => $curlErr]);
    json_response([
        "ok" => false,
        "error" => "Gateway unavailable.",
        "status" => "gateway_unreachable",
        "http_code" => $httpCode
    ], 502);
}

$data = json_decode((string)$response, true);
if (!is_array($data)) {
    log_error_event("Invalid gateway JSON", [
        "http_code" => $httpCode,
        "response_preview" => clean_text((string)$response, 500)
    ]);
    json_response([
        "ok" => false,
        "error" => "Invalid gateway response.",
        "status" => "invalid_gateway_json",
        "http_code" => $httpCode
    ], 502);
}

if ($httpCode >= 400) {
    log_error_event("Gateway rejected request", [
        "http_code" => $httpCode,
        "response_preview" => clean_text((string)$response, 700),
        "has_document" => $documentContext !== "",
        "message_chars" => text_length($messageToGateway)
    ]);
    json_response([
        "ok" => false,
        "error" => "Gateway rejected the request. Try a shorter document or paste only the relevant section.",
        "status" => "gateway_rejected",
        "http_code" => $httpCode
    ], 502);
}

$reply = extract_gateway_reply($data);
if ($reply === "") {
    log_error_event("Empty gateway reply", ["http_code" => $httpCode, "keys" => array_keys($data)]);
    json_response([
        "ok" => false,
        "error" => "Empty gateway reply.",
        "status" => "empty_gateway_reply",
        "http_code" => $httpCode
    ], 502);
}

$reply = trim((string)preg_replace("/[ \\t]+/u", " ", $reply));
save_chat_log($userMessage, $reply, $documentContext !== "");

json_response([
    "ok" => true,
    "reply" => $reply,
    "message" => $reply,
    "http_code" => $httpCode
]);
