#!/bin/bash
# chmod 755 filename (gia allagi dikeomaton)
CACHE_DIR="/home/..../www/image/cache/catalog"

if [ -d "$CACHE_DIR" ]; then
    cd "$CACHE_DIR" || exit

    rm -rf *

    echo "Cache in $CACHE_DIR have been cleared."
else
    echo "Error: Cache directory $CACHE_DIR not found."
fi
