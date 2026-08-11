#!/usr/bin/env python3
"""
Capture a command's real terminal output into an asciicast v2 file.

termtosvg 1.1.0's own recorder writes an empty cast on Python 3.14, so we do the
pty capture here (which is reliable) and let `termtosvg render` turn the cast
into an animated SVG. This records the REAL output of the command, unmodified.

Usage:
    capture-cast.py OUTPUT.cast COLUMNS ROWS -- command args...
"""
import sys, os, pty, json, time, select, struct, fcntl, termios


def main():
    out_path = sys.argv[1]
    cols = int(sys.argv[2])
    rows = int(sys.argv[3])
    assert sys.argv[4] == "--", "expected -- before the command"
    cmd = sys.argv[5:]

    env = dict(os.environ)
    env["TERM"] = "xterm-256color"
    env["COLUMNS"] = str(cols)
    env["LINES"] = str(rows)

    pid, master_fd = pty.fork()
    if pid == 0:  # child
        os.execvpe(cmd[0], cmd, env)
        os._exit(127)

    # Set the pty window size so the program formats for our geometry.
    fcntl.ioctl(master_fd, termios.TIOCSWINSZ,
                struct.pack("HHHH", rows, cols, 0, 0))

    events = []
    start = time.time()
    while True:
        try:
            r, _, _ = select.select([master_fd], [], [], 0.05)
        except InterruptedError:
            continue
        if master_fd in r:
            try:
                data = os.read(master_fd, 65536)
            except OSError:
                break
            if not data:
                break
            events.append([round(time.time() - start, 4), "o",
                           data.decode("utf-8", "replace")])
        else:
            # Check whether the child has exited and the pipe is drained.
            try:
                done, _ = os.waitpid(pid, os.WNOHANG)
                if done:
                    # Drain any final output.
                    while True:
                        r, _, _ = select.select([master_fd], [], [], 0.05)
                        if master_fd not in r:
                            break
                        try:
                            data = os.read(master_fd, 65536)
                        except OSError:
                            data = b""
                        if not data:
                            break
                        events.append([round(time.time() - start, 4), "o",
                                       data.decode("utf-8", "replace")])
                    break
            except ChildProcessError:
                break

    with open(out_path, "w") as f:
        f.write(json.dumps({"version": 2, "width": cols, "height": rows,
                            "env": {"TERM": "xterm-256color"}}) + "\n")
        for e in events:
            f.write(json.dumps(e) + "\n")

    print(f"wrote {out_path}: {len(events)} events, "
          f"{events[-1][0] if events else 0:.1f}s")


if __name__ == "__main__":
    main()
