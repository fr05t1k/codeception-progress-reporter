#!/usr/bin/env python3
"""Strip Codeception's leading banner line from a captured asciicast.

Codeception's `run` command always prints a banner as the very first output
line (e.g. "Codeception PHP Testing Framework v5.3.5 https://stand-with-ukraine.pp.ua"),
gated only by --silent (which would also disable our progress bar). For the
demo recordings we don't want that line, so we remove the banner text from the
captured cast before rendering. The reporter's progress-bar block is
self-contained (it overwrites only its own lines), so dropping the banner event
does not affect the rest of the animation.

Usage:
    python3 scripts/strip-banner.py path/to/recording.cast
"""
from __future__ import annotations

import json
import re
import sys

BANNER = re.compile(r"Codeception PHP Testing Framework[^\r\n]*\r?\n")


def main(path: str) -> int:
    lines = open(path, encoding="utf-8").read().splitlines()
    if not lines:
        return 0

    out = [lines[0]]  # header
    for line in lines[1:]:
        event = json.loads(line)
        event[2] = BANNER.sub("", event[2])
        if event[2] == "":
            continue  # drop the now-empty banner event
        out.append(json.dumps(event))

    with open(path, "w", encoding="utf-8") as fh:
        fh.write("\n".join(out) + "\n")
    return 0


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("usage: strip-banner.py <recording.cast>", file=sys.stderr)
        raise SystemExit(2)
    raise SystemExit(main(sys.argv[1]))
