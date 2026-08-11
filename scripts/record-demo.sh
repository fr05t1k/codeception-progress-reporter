#!/usr/bin/env bash
#
# Regenerates the animated terminal demo used in the README.
#
# It records the REAL Codeception progress bar produced by this extension
# (src/ProgressReporter.php) into a self-contained animated SVG, so the demo
# stays accurate whenever the UI changes. Just re-run this script after any
# UI update and commit the refreshed preview.svg.
#
# Requirements:
#   - php (cli) + composer
#   - termtosvg  (pipx install termtosvg)
#
# Usage:
#   ./scripts/record-demo.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."

# Number of demo tests and the artificial per-test delay (seconds) used ONLY
# for the recording, so the progress bar visibly fills instead of finishing
# instantly. This does not affect the real test suite.
TESTS="${DEMO_TESTS:-40}"
DELAY="${DEMO_DELAY:-0.06}"
OUT="${DEMO_OUT:-preview.svg}"

PHP="php -d register_argc_argv=On -d memory_limit=512M"

command -v termtosvg >/dev/null 2>&1 || {
  echo "error: termtosvg not found (pipx install termtosvg)" >&2; exit 1;
}

echo "==> Installing dependencies"
composer update --prefer-dist --no-interaction >/dev/null

echo "==> Generating ${TESTS} demo tests (with a ${DELAY}s delay each)"
rm -rf tests/unit/Stub
for i in $(seq 1 "${TESTS}"); do
  $PHP vendor/bin/codecept g:test unit "Stub/Demo${i}" >/dev/null
  # Insert a small delay so the recording shows the bar advancing.
  file="tests/unit/Stub/Demo${i}Test.php"
  perl -0pi -e "s/public function testSomeFeature\(\)\s*\{/public function testSomeFeature()\n    {\n        usleep((int)(${DELAY} * 1000000));/" "$file"
done

echo "==> Recording real progress bar to ${OUT}"
CAST="$(mktemp --suffix=.cast)"
python3 scripts/capture-cast.py "${CAST}" 100 14 -- \
  php -d register_argc_argv=On -d memory_limit=512M vendor/bin/codecept run unit
# Drop Codeception's leading banner line so the demo starts at the progress bar.
python3 scripts/strip-banner.py "${CAST}"
# termtosvg's recorder is broken on Python 3.14, but its renderer works, so we
# render the cast captured above into a self-contained animated SVG.
termtosvg render "${CAST}" "${OUT}" --template window_frame
rm -f "${CAST}"

echo "==> Cleaning up demo tests"
rm -rf tests/unit/Stub

echo "==> Done: ${OUT}"
