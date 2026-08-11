#!/usr/bin/env bash
#
# Records the REAL Codeception progress bar (src/ProgressReporter.php) into an
# animated GIF used for visual verification on pull requests.
#
# Unlike scripts/record-demo.sh (which produces the README's happy-path SVG),
# this run deliberately includes a failing and an erroring test so the GIF also
# shows the end-of-run failure report the reporter delegates to Codeception.
#
# GitHub does not render SVG inside PR/issue comments, so we emit a GIF here.
#
# Requirements:
#   - php (cli) + composer
#   - python3            (scripts/capture-cast.py, pty capture)
#   - agg                (asciinema GIF generator; path overridable via $AGG)
#
# Usage:
#   ./scripts/record-pr-demo.sh [output.gif]
set -euo pipefail

cd "$(dirname "$0")/.."

OUT="${1:-${DEMO_OUT:-demo.gif}}"
PASS_TESTS="${DEMO_TESTS:-18}"
DELAY="${DEMO_DELAY:-0.05}"
COLS="${DEMO_COLS:-100}"
ROWS="${DEMO_ROWS:-20}"
AGG="${AGG:-agg}"

PHP="php -d register_argc_argv=On -d memory_limit=512M"

command -v python3 >/dev/null 2>&1 || { echo "error: python3 not found" >&2; exit 1; }
command -v "${AGG}" >/dev/null 2>&1 || { echo "error: agg not found (set \$AGG)" >&2; exit 1; }

echo "==> Generating ${PASS_TESTS} passing demo tests (+1 fail, +1 error)"
rm -rf tests/unit/Stub
mkdir -p tests/unit/Stub

for i in $(seq 1 "${PASS_TESTS}"); do
  $PHP vendor/bin/codecept g:test unit "Stub/Demo${i}" >/dev/null
  file="tests/unit/Stub/Demo${i}Test.php"
  perl -0pi -e "s/public function testSomeFeature\(\)\s*\{/public function testSomeFeature()\n    {\n        usleep((int)(${DELAY} * 1000000));/" "$file"
done

# A failing and an erroring test so the GIF shows the failure report too.
cat > tests/unit/Stub/DemoFailTest.php <<'PHP'
<?php

namespace Codeception\ProgressReporter\Tests\Unit\Stub;

use Codeception\Test\Unit;

class DemoFailTest extends Unit
{
    public function testFailingExample(): void
    {
        usleep(50000);
        $this->assertSame('expected', 'actual', 'demo failure');
    }
}
PHP

cat > tests/unit/Stub/DemoErrorTest.php <<'PHP'
<?php

namespace Codeception\ProgressReporter\Tests\Unit\Stub;

use Codeception\Test\Unit;

class DemoErrorTest extends Unit
{
    public function testErroringExample(): void
    {
        usleep(50000);
        throw new \RuntimeException('demo error');
    }
}
PHP

CAST="$(mktemp --suffix=.cast)"
echo "==> Recording real progress bar (${COLS}x${ROWS})"
# The pty makes Codeception treat the output as a real color terminal, so the
# progress bar overwrites in place (as a user sees it) instead of stacking.
# codecept exits non-zero because of the demo failures; that is expected.
python3 scripts/capture-cast.py "${CAST}" "${COLS}" "${ROWS}" -- \
  ${PHP} vendor/bin/codecept run unit Stub || true

echo "==> Rendering GIF -> ${OUT}"
"${AGG}" --theme monokai --font-size 16 --speed 1 "${CAST}" "${OUT}"
rm -f "${CAST}"

echo "==> Cleaning up demo tests"
rm -rf tests/unit/Stub

echo "==> Done: ${OUT}"
