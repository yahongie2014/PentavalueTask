#!/usr/bin/env bash
set -e

# Default port if $PORT not provided by platform
PORT="${PORT:-8080}"

# Optional: run migrations or other bootstrap tasks
# Example: php artisan migrate --force || true

# Start PHP built-in server using server.php as router
exec php server.php
