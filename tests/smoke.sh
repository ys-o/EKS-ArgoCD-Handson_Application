#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# Each run owns an isolated Compose project and removes only its own resources.
export COMPOSE_PROJECT_NAME="three-layer-test-$$"
export WEB_IMAGE="$COMPOSE_PROJECT_NAME-web" AP_IMAGE="$COMPOSE_PROJECT_NAME-php"
export WEB_PORT="${TEST_WEB_PORT:-18080}"
export DB_NAME=handson DB_USER=handson APP_VERSION='smoke-<script>test</script>'
export DB_PASSWORD="test-$(od -An -N16 -tx1 /dev/urandom | tr -d ' \n')"
export MYSQL_ROOT_PASSWORD="root-$(od -An -N16 -tx1 /dev/urandom | tr -d ' \n')"
dc() { docker compose --env-file /dev/null "$@"; }
cleanup() { dc down --volumes --remove-orphans >/dev/null; }
trap cleanup EXIT
for service in web php; do
    tar -cf - app docker .dockerignore | docker build -f "docker/$service/Dockerfile" -t "$COMPOSE_PROJECT_NAME-$service" -
done
dc up --no-build --wait --wait-timeout 240
dc exec -T php php -l public/index.php
dc exec -T php php -l src/status.php
dc exec -T php php < tests/config.php
dc exec -T web nginx -t
base="http://127.0.0.1:$WEB_PORT"
check() {
    local path="$1" expected="$2" actual
    actual=$(curl --silent --show-error --max-time 15 --output /dev/null --write-out '%{http_code}' "$base$path")
    test "$actual" = "$expected" || { echo "FAIL $path: expected $expected, got $actual"; exit 1; }
    echo "PASS $path -> $actual"
}
check / 200
check /health 200
check /ready 200
check /style.css 200
check /missing 404
check /.env 404
test "$(curl -s -o /dev/null -w '%{http_code}' -X POST "$base/")" = 405
body=$(curl -fsS "$base/")
[[ "$body" == *'smoke-&lt;script&gt;test&lt;/script&gt;'* ]]
[[ "$body" != *"$DB_PASSWORD"* ]]
curl -fsSI "$base/" | grep -qi '^Content-Security-Policy:'
dc stop db
check /health 200
check /ready 503
check / 503
body=$(curl -sS "$base/")
[[ "$body" != *"$DB_PASSWORD"* && "$body" != *SQLSTATE* ]]
dc start --wait db
check /ready 200
echo 'PASS: healthy response, escaping, routing, DB failure, and recovery'
