#!/usr/bin/env sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)
IMAGE_NAME="${IMAGE_NAME:-epsicube-framework-test}"
FORCE_BUILD="${FORCE_BUILD:-0}"

if [ "${FORCE_BUILD}" = "1" ] || ! docker image inspect "${IMAGE_NAME}" >/dev/null 2>&1; then
  docker build -t "${IMAGE_NAME}" -f "${REPO_ROOT}/Dockerfile" "${REPO_ROOT}"
fi

cd "${REPO_ROOT}"

COMMAND='
set -eu

STAMP_FILE=vendor/.composer-install-checksum
CURRENT_CHECKSUM="$(sha256sum composer.json composer.lock | sha256sum | cut -d" " -f1)"
INSTALLED_CHECKSUM=""

if [ -f "${STAMP_FILE}" ]; then
  INSTALLED_CHECKSUM="$(cat "${STAMP_FILE}")"
fi

if [ ! -f vendor/autoload.php ] || [ ! -x vendor/bin/pint ] || [ "${CURRENT_CHECKSUM}" != "${INSTALLED_CHECKSUM}" ]; then
  composer install --no-interaction --prefer-dist --no-scripts
  printf "%s" "${CURRENT_CHECKSUM}" > "${STAMP_FILE}"
fi

php vendor/bin/pint "$@"
'

exec docker run --rm \
  --user "$(id -u):$(id -g)" -e HOME=/tmp -e XDG_CACHE_HOME=/tmp/.cache \
  -v "${REPO_ROOT}:/app" -w /app "${IMAGE_NAME}" \
  sh -c "${COMMAND}" -- "$@"
