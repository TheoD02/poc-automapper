#!/bin/bash
set -e

chmod +x /tools/bin/* || true
ln -s /tools/bin/* /usr/local/bin/ || true

# Add /tools/bin to PATH
export PATH=$PATH:/tools/bin

exec docker-php-entrypoint "$@"
