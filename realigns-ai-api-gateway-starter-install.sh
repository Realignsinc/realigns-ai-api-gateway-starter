#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="realigns-ai-api-gateway-starter"

ZIP_FILE="${1:-}"
if [ -z "$ZIP_FILE" ]; then
  if [ -f "realigns-ai-api-gateway-starter-v4-public-clean-with-installer.zip" ]; then
    ZIP_FILE="realigns-ai-api-gateway-starter-v4-public-clean-with-installer.zip"
  elif [ -f "realigns-ai-api-gateway-starter-v3-public-clean.zip" ]; then
    ZIP_FILE="realigns-ai-api-gateway-starter-v3-public-clean.zip"
  elif [ -f "realigns-ai-api-gateway-starter.zip" ]; then
    ZIP_FILE="realigns-ai-api-gateway-starter.zip"
  else
    echo "ERROR: Could not find starter ZIP."
    echo "Place this script beside the starter ZIP or run:"
    echo "./realigns-ai-api-gateway-starter-install.sh path/to/starter.zip"
    exit 1
  fi
fi

mkdir -p "$PROJECT_DIR"
unzip -o "$ZIP_FILE" -d .

cd "$PROJECT_DIR"

if [ ! -f config.local.php ]; then
  cp config.local.example.php config.local.php
  echo "Created config.local.php. Paste your Realigns AI API key there."
else
  echo "config.local.php already exists. Existing private key config was not overwritten."
fi

echo "Ready. Open index.php through your PHP server."
echo "Local test command:"
echo "php -S 127.0.0.1:8080"
