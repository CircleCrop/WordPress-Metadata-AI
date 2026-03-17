#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PLUGIN_SLUG="wordpress-metadata-aigen"
PLUGIN_FILE="${ROOT_DIR}/${PLUGIN_SLUG}.php"
DIST_DIR="${ROOT_DIR}/dist"

if [[ ! -f "${PLUGIN_FILE}" ]]; then
	echo "Plugin bootstrap file not found: ${PLUGIN_FILE}" >&2
	exit 1
fi

if ! command -v php >/dev/null 2>&1; then
	echo "php is required to lint the plugin before packaging." >&2
	exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
	echo "zip is required to build the plugin archive." >&2
	exit 1
fi

VERSION="$(
	sed -nE 's/^[[:space:]]*\*[[:space:]]Version:[[:space:]]*([^[:space:]]+).*$/\1/p' "${PLUGIN_FILE}" \
		| head -n 1
)"

if [[ -z "${VERSION}" ]]; then
	echo "Could not determine plugin version from ${PLUGIN_FILE}." >&2
	exit 1
fi

TMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${PLUGIN_SLUG}.XXXXXX")"
STAGE_DIR="${TMP_DIR}/${PLUGIN_SLUG}"

cleanup() {
	rm -rf "${TMP_DIR}"
}

trap cleanup EXIT

echo "Linting PHP files..."
while IFS= read -r -d '' file; do
	php -l "${file}" >/dev/null
done < <(
	find "${ROOT_DIR}" \
		-path "${ROOT_DIR}/.git" -prune -o \
		-path "${ROOT_DIR}/dist" -prune -o \
		-type f -name '*.php' -print0
)

mkdir -p "${STAGE_DIR}" "${DIST_DIR}"

copy_path() {
	local relative_path="$1"
	local source_path="${ROOT_DIR}/${relative_path}"

	if [[ ! -e "${source_path}" ]]; then
		return 0
	fi

	cp -R "${source_path}" "${STAGE_DIR}/"
}

copy_matching_paths() {
	local pattern="$1"
	local matched=0

	shopt -s nullglob

	for source_path in "${ROOT_DIR}"/${pattern}; do
		matched=1
		cp -R "${source_path}" "${STAGE_DIR}/"
	done

	shopt -u nullglob

	if [[ ${matched} -eq 0 ]]; then
		return 0
	fi
}

copy_path "${PLUGIN_SLUG}.php"
copy_path "src"
copy_path "views"
copy_path "languages"
copy_path "assets"
copy_path "vendor"
copy_path "readme.txt"
copy_matching_paths "README*.md"
copy_path "LICENSE"
copy_path "license.txt"

VERSIONED_ZIP="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
LATEST_ZIP="${DIST_DIR}/${PLUGIN_SLUG}.zip"

rm -f "${VERSIONED_ZIP}" "${LATEST_ZIP}"

echo "Building ${VERSIONED_ZIP}..."
(
	cd "${TMP_DIR}"
	zip -rq "${VERSIONED_ZIP}" "${PLUGIN_SLUG}"
)

cp "${VERSIONED_ZIP}" "${LATEST_ZIP}"

echo "Package created:"
echo "  ${VERSIONED_ZIP}"
echo "  ${LATEST_ZIP}"
