<?php
declare(strict_types=1);

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");

/*
  Realigns AI Website Widget PHP Proxy

  Website widget -> this PHP proxy -> Realigns AI API Gateway

  Keep the API key server-side only.
  Do not place the API key in frontend JavaScript.
*/

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function clean_text(string $text, int $max): string {
    $text = trim(strip_tags($text));
    $text = preg_replace("/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u", "", $text);
    $text = preg_replace("/\s+/u", " ", (string)$text);

    if (function_exists("mb_substr")) {
        return mb_substr((string)$text, 0, $max);
    }

    return substr((string)$text, 0, $max);
}

function load_config(): array {
    $config = [
        "api_key" => "",
        "gateway_url" => "https://gpt-api.realignsinc.com/ai/chat",
        "max_message_chars" => 2000
    ];

    $localPath = __DIR__ . "/config.local.php";
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $config = array_merge($config, $local);
        }
    }

    $envKey = getenv("REALIGNS_AI_API_KEY");
    if (is_string($envKey) && trim($envKey) !== "") {
        $config["api_key"] = trim($envKey);
    }

    return $config;
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

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    json_response(["ok" => false, "error" => "Method not allowed."], 405);
}

$config = load_config();
$apiKey = trim((string)($config["api_key"] ?? ""));
$gatewayUrl = trim((string)($config["gateway_url"] ?? ""));
$maxMessageChars = max(300, (int)($config["max_message_chars"] ?? 2000));

if ($apiKey === "" || $apiKey === "rk_live_your_key_here" || $apiKey === "rk_live_") {
    json_response([
        "ok" => false,
        "error" => "Gateway API key is not configured on the server.",
        "status" => "missing_api_key"
    ], 500);
}

$input = json_decode((string)file_get_contents("php://input"), true);
if (!is_array($input)) {
    json_response(["ok" => false, "error" => "Invalid JSON input."], 400);
}

$userMessage = clean_text((string)($input["message"] ?? $input["prompt"] ?? ""), $maxMessageChars);
if ($userMessage === "") {
    json_response(["ok" => false, "error" => "Invalid prompt."], 400);
}

$payload = json_encode([
    "message" => $userMessage,
    "prompt" => $userMessage
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($gatewayUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-key: " . $apiKey
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 35,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || trim((string)$response) === "") {
    json_response([
        "ok" => false,
        "error" => "Gateway unavailable.",
        "status" => "gateway_unreachable"
    ], 502);
}

$data = json_decode((string)$response, true);
if (!is_array($data)) {
    json_response([
        "ok" => false,
        "error" => "Invalid gateway response.",
        "status" => "invalid_gateway_json"
    ], 502);
}

if ($httpCode >= 400) {
    json_response([
        "ok" => false,
        "error" => "Gateway rejected the request.",
        "status" => "gateway_rejected",
        "http_code" => $httpCode
    ], 502);
}

$reply = extract_gateway_reply($data);
if ($reply === "") {
    json_response([
        "ok" => false,
        "error" => "Empty gateway reply.",
        "status" => "empty_gateway_reply"
    ], 502);
}

json_response([
    "ok" => true,
    "reply" => $reply,
    "message" => $reply,
    "http_code" => $httpCode
]);
