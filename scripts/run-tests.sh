#!/usr/bin/env sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)
FORCE_BUILD="${FORCE_BUILD:-0}"

cd "${REPO_ROOT}"

BUILD_OPTS=""
if [ "${FORCE_BUILD}" = "1" ]; then
    BUILD_OPTS="--build"
fi

COMMAND='
set -eu

STAMP_FILE=vendor/.composer-install-checksum
CURRENT_CHECKSUM="$(sha256sum composer.json composer.lock | sha256sum | cut -d" " -f1)"
INSTALLED_CHECKSUM=""

if [ -f "${STAMP_FILE}" ]; then
  INSTALLED_CHECKSUM="$(cat "${STAMP_FILE}")"
fi

if [ ! -f vendor/autoload.php ] || [ ! -x vendor/bin/pest ] || [ "${CURRENT_CHECKSUM}" != "${INSTALLED_CHECKSUM}" ]; then
  composer install --no-interaction --prefer-dist --no-scripts
  printf "%s" "${CURRENT_CHECKSUM}" > "${STAMP_FILE}"
fi

php vendor/bin/pest "$@"
'

exec docker compose run --rm $BUILD_OPTS --user "$(id -u):$(id -g)" dev sh -c "${COMMAND}" -- "$@"
