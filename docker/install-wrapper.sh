#!/bin/sh
set -e

EXTENSIONS_FILE=$1

if [ ! -f "$EXTENSIONS_FILE" ]; then
  echo "Error: Extension file not found at $EXTENSIONS_FILE"
  exit 1
fi

echo ">>> Preparing to install extensions listed in ${EXTENSIONS_FILE} using mlocati/php-extension-installer..."

# Read the file, filter comments (#) and empty lines,
# then format names as space-separated arguments for the installer script.
EXT_ARGS=$(awk '!/^\s*#/ && !/^\s*$/ {printf "%s ", $0}' "$EXTENSIONS_FILE")

if [ -z "$EXT_ARGS" ]; then
    echo "No extensions listed in $EXTENSIONS_FILE to install."
    exit 0
fi

echo ">>> Installing extensions: ${EXT_ARGS}"

/usr/local/bin/install-php-extensions ${EXT_ARGS}

echo ">>> Extension installation complete."
