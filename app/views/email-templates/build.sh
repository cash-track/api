#!/bin/sh

OUT_DIR=$1

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd $SCRIPT_DIR

# Cleanup previous output
rm $OUT_DIR/*.dark.php

# Generate updated output
mjml ./*.mjml -o $OUT_DIR --config.useMjmlConfigOptions true

# Normalise file extension for framework templating engine
# *.html => *.dark.php
cd $OUT_DIR
for file in *.html
do
    mv -v "$file" "${file%.html}.dark.php"
done
