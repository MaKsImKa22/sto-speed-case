"""Статична копія сторінки «до» (Elementor) для GitHub Pages — щоб її можна було заміряти в PageSpeed.

Зберігає головну сторінку і все, на що вона посилається (CSS, JS, фото, шрифти з CSS),
плюс усі скрипти Elementor, бо частину з них він довантажує на льоту.

Запуск: python tools/mirror_before.py http://localhost:8081 https://maksimka22.github.io/sto-speed-case/before D:/claude/vayb_code/wp-elementor
"""

import re
import shutil
import sys
import urllib.request
from pathlib import Path
from urllib.parse import urlsplit, unquote

TEXT_TYPES = (".html", ".css", ".js", ".json")


def fetch(url):
    with urllib.request.urlopen(url) as resp:
        return resp.read()


def find_urls(text, src):
    escaped = src.replace("/", "\\/")
    urls = set(re.findall(re.escape(src) + r"/[^\"'\s)<>,]+", text))
    urls |= {u.replace("\\/", "/") for u in re.findall(re.escape(escaped) + r"\\/[^\"'\s)<>,]+", text)}
    return {u.split("?")[0].split("#")[0].rstrip("\\") for u in urls}


def rewrite(text, src, dst):
    return text.replace(src, dst).replace(src.replace("/", "\\/"), dst.replace("/", "\\/"))


def local_path(out, url, src):
    path = unquote(urlsplit(url).path)
    if path.endswith("/") or "." not in path.rsplit("/", 1)[-1]:
        path = path.rstrip("/") + "/index.html"
    return out / path.lstrip("/")


def main(src, dst, wp_root):
    src, dst = src.rstrip("/"), dst.rstrip("/")
    out = Path(__file__).resolve().parent.parent / "before"
    if out.exists():
        shutil.rmtree(out)

    queue, seen = [src + "/"], set()
    while queue:
        url = queue.pop(0)
        if url in seen or "/wp-json" in url or url.endswith(("/feed", "/feed/", "xmlrpc.php")) or "/comments/" in url:
            continue
        seen.add(url)
        if url != src + "/" and local_path(out, url, src).suffix == ".html":
            continue  # інші сторінки сайту не потрібні
        try:
            body = fetch(url)
        except Exception as err:  # noqa: BLE001
            print(f"  ! {url}: {err}")
            continue
        target = local_path(out, url, src)
        target.parent.mkdir(parents=True, exist_ok=True)
        if target.suffix in TEXT_TYPES:
            text = body.decode("utf-8", errors="replace")
            queue.extend(sorted(find_urls(text, src)))
            # Відносні url() усередині CSS.
            if target.suffix == ".css":
                base = url.rsplit("/", 1)[0]
                for rel in re.findall(r"url\(['\"]?(?!data:|https?:|/)([^'\")]+)", text):
                    queue.append(base + "/" + rel.split("?")[0].split("#")[0])
            target.write_text(rewrite(text, src, dst), encoding="utf-8")
        else:
            target.write_bytes(body)

    # Скрипти Elementor, які він підвантажує динамічно.
    js_dir = Path(wp_root) / "wp-content/plugins/elementor/assets/js"
    dest = out / "wp-content/plugins/elementor/assets/js"
    for file in js_dir.glob("*.js"):
        target = dest / file.name
        if not target.exists():
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text(rewrite(file.read_text(encoding="utf-8"), src, dst), encoding="utf-8")

    (out / ".nojekyll").write_text("", encoding="utf-8")
    files = [p for p in out.rglob("*") if p.is_file()]
    print(f"Готово: {len(files)} файлів, {sum(p.stat().st_size for p in files) / 1024 / 1024:.1f} МБ")


if __name__ == "__main__":
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    main(*sys.argv[1:])
