#!/usr/bin/env bash
set -Eeuo pipefail

app_dir="${1:?Usage: $0 /path/to/app}"
release_id="${2:-$(date +%Y%m%d%H%M%S)-$$}"
runtime_root="${app_dir}/.runtime"
runtime_dir="${runtime_root}/node_modules"
release_dir="${runtime_dir}/releases/${release_id}"
npm_cache_dir="${runtime_root}/npm-cache"
puppeteer_cache_dir="${runtime_root}/puppeteer-cache"
npmrc="${NPM_CONFIG_USERCONFIG:-}"

if [[ -z "$npmrc" || ! -f "$npmrc" ]]; then
    echo "NPM_CONFIG_USERCONFIG must point to a readable npmrc file." >&2
    exit 1
fi

command -v node >/dev/null 2>&1 || { echo "node is required." >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "npm is required." >&2; exit 1; }
command -v flock >/dev/null 2>&1 || { echo "flock is required." >&2; exit 1; }

[[ -f "${app_dir}/package.json" ]] || { echo "package.json is missing." >&2; exit 1; }
[[ -f "${app_dir}/package-lock.json" ]] || { echo "package-lock.json is missing." >&2; exit 1; }

mkdir -p "${runtime_dir}/releases" "${npm_cache_dir}" "${puppeteer_cache_dir}"
exec 9>"${runtime_dir}/deploy.lock"
flock 9

rm -rf "${release_dir}"
mkdir -p "${release_dir}"
cp "${app_dir}/package.json" "${app_dir}/package-lock.json" "${release_dir}/"

(
    cd "${release_dir}"
    NPM_CONFIG_USERCONFIG="${npmrc}" \
    NPM_CONFIG_CACHE="${npm_cache_dir}" \
    PUPPETEER_CACHE_DIR="${puppeteer_cache_dir}" \
    npm ci --omit=dev --no-audit --no-fund
)

node_modules_path="${release_dir}/node_modules"
NODE_PATH="${node_modules_path}" \
PUPPETEER_CACHE_DIR="${puppeteer_cache_dir}" \
node -e 'const path = require.resolve("puppeteer"); if (!path) process.exit(1); console.log(`Puppeteer resolved at ${path}`);'
[[ -f "${node_modules_path}/puppeteer/package.json" ]] || { echo "puppeteer was not installed." >&2; exit 1; }

current_link="${app_dir}/node_modules"
next_link="${app_dir}/.node_modules.next"
rm -f "${next_link}"
ln -s "${node_modules_path}" "${next_link}"

if [[ -L "${current_link}" ]]; then
    mv -Tf "${next_link}" "${current_link}"
else
    legacy_dir="${runtime_dir}/legacy-${release_id}"
    if [[ -e "${current_link}" ]]; then
        mv "${current_link}" "${legacy_dir}"
    fi
    mv "${next_link}" "${current_link}"
    rm -rf "${legacy_dir:-}"
fi

mapfile -t old_releases < <(
    find "${runtime_dir}/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
        | sort -nr \
        | tail -n +3 \
        | cut -d' ' -f2-
)
for old_release in "${old_releases[@]}"; do
    rm -rf -- "${old_release}"
done

echo "Production node dependencies installed: ${node_modules_path}"
