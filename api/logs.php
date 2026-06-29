<?php
declare(strict_types=1);

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");

/*
  Protected local log viewer.

  Set REALIGNS_LOG_VIEW_TOKEN in your hosting environment,
  or add "log_view_token" in config.local.php.

  Request:
  /api/logs.php?token=YOUR_TOKEN&type=chat
  /api/logs.php?token=YOUR_TOKEN&type=error
*/

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function load_config(): array {
    $config = [];

    $localPath = dirname(__DIR__) . "/config.local.php";
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $config = $local;
        }
    }

    $envToken = getenv("REALIGNS_LOG_VIEW_TOKEN");
    if (is_string($envToken) && trim($envToken) !== "") {
        $config["log_view_token"] = trim($envToken);
    }

    return $config;
}

$config = load_config();
$requiredToken = trim((string)($config["log_view_token"] ?? ""));
$givenToken = trim((string)($_GET["token"] ?? ""));

if ($requiredToken === "") {
    json_response([
        "ok" => false,
        "error" => "Log viewer token is not configured."
    ], 403);
}

if (!hash_equals($requiredToken, $givenToken)) {
    json_response([
        "ok" => false,
        "error" => "Unauthorized."
    ], 401);
}

$type = $_GET["type"] ?? "chat";
$fileName = $type === "error" ? "error_logs.jsonl" : "chat_logs.jsonl";
$file = dirname(__DIR__) . "/storage/" . $fileName;

if (!is_file($file)) {
    json_response([
        "ok" => true,
        "type" => $type,
        "logs" => []
    ]);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!is_array($lines)) {
    $lines = [];
}

$lines = array_slice($lines, -100);
$logs = [];

foreach ($lines as $line) {
    $row = json_decode($line, true);
    if (is_array($row)) {
        $logs[] = $row;
    }
}

json_response([
    "ok" => true,
    "type" => $type,
    "logs" => $logs
]);
