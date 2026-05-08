"""One-off: merge assessment view <style> blocks into assets/css/assessments.css"""
import re
import pathlib

ROOT = pathlib.Path(__file__).resolve().parents[1]
VIEWS = ROOT / "application" / "views" / "assessments"
OUT = ROOT / "assets" / "css" / "assessments.css"

files = [
    "index.php",
    "take.php",
    "create.php",
    "edit.php",
    "grade.php",
    "result.php",
    "review.php",
]
parts = []
for name in files:
    p = VIEWS / name
    if not p.exists():
        continue
    t = p.read_text(encoding="utf-8", errors="replace")
    for m in re.finditer(r"<style>(.*?)</style>", t, re.S):
        parts.append(f"/* --- {name} --- */\n{m.group(1).strip()}\n")

OUT.parent.mkdir(parents=True, exist_ok=True)
merged = "\n".join(parts)
if len(merged.strip()) < 500:
    raise SystemExit(
        "Refusing to write empty assessments.css — views may already use external CSS. "
        "Restore <style> blocks in git history or maintain assets/css/assessments.css manually."
    )
OUT.write_text(merged, encoding="utf-8")
print("Wrote", OUT.relative_to(ROOT), OUT.stat().st_size, "bytes")
