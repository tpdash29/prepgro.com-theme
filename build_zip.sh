#!/usr/bin/env bash
#
# build_zip.sh — bump the theme version and build an upload-ready zip.
#
#   bash build_zip.sh             # patch bump (3.4.0 -> 3.4.1), then zip
#   bash build_zip.sh --minor     # 3.4.1 -> 3.5.0
#   bash build_zip.sh --major     # 3.5.0 -> 4.0.0
#   bash build_zip.sh --no-bump   # zip the current version as-is
#
# The version lives in two places that must agree: the `Version:` header in
# style.css (what WordPress shows) and the `*_VERSION` constant in
# functions.php (cache-busts enqueued CSS/JS). Both are rewritten together;
# if they have drifted apart the higher one wins before bumping.
#
# Output: dist-zips/{theme-folder}-v{version}.zip with a top-level
# {theme-folder}/ directory (Appearance → Themes → Add New → Upload).
# Dev material (.git, .claude, tests/, dist-zips/, this script, .DS_Store,
# macOS resource forks) never ships. Every PHP file is linted on the staged
# tree first; a failure writes no zip. Older zips of this theme are pruned
# once the new one is written.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

SLUG="$(basename "$ROOT")"
OUT_DIR="$ROOT/dist-zips"
STYLE="$ROOT/style.css"
FUNCS="$ROOT/functions.php"

BUMP="patch"
for arg in "$@"; do
  case "$arg" in
    --no-bump) BUMP="none" ;;
    --patch)   BUMP="patch" ;;
    --minor)   BUMP="minor" ;;
    --major)   BUMP="major" ;;
    -h|--help) sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "ERROR: unknown option '$arg' (see --help)" >&2; exit 1 ;;
  esac
done

[ -f "$STYLE" ] || { echo "ERROR: style.css not found in $ROOT" >&2; exit 1; }
[ -f "$FUNCS" ] || { echo "ERROR: functions.php not found in $ROOT" >&2; exit 1; }

# ── Read versions ────────────────────────────────────────────────────────────
HEADER_VER="$(sed -n -E 's/^[[:space:]]*Version:[[:space:]]*([0-9]+(\.[0-9]+){0,2}).*/\1/p' "$STYLE" | head -1)"
CONST_NAME="$(sed -n -E "s/.*define\([[:space:]]*'([A-Z0-9_]+_VERSION)'.*/\1/p" "$FUNCS" | head -1)"
CONST_VER=""
[ -n "$CONST_NAME" ] && CONST_VER="$(sed -n -E "s/.*define\([[:space:]]*'${CONST_NAME}'[[:space:]]*,[[:space:]]*'([^']*)'.*/\1/p" "$FUNCS" | head -1)"

[ -n "$HEADER_VER" ] || { echo "ERROR: no 'Version:' header in style.css" >&2; exit 1; }

# Normalise to X.Y.Z and pick the higher of header / constant.
norm() { IFS=. read -r a b c <<< "$1"; echo "${a:-0}.${b:-0}.${c:-0}"; }
HEADER_VER="$(norm "$HEADER_VER")"
CURRENT="$HEADER_VER"
if [ -n "$CONST_VER" ]; then
  CONST_VER="$(norm "$CONST_VER")"
  CURRENT="$(printf '%s\n%s\n' "$HEADER_VER" "$CONST_VER" | sort -t. -k1,1n -k2,2n -k3,3n | tail -1)"
  [ "$HEADER_VER" = "$CONST_VER" ] || echo "▸ note: style.css ($HEADER_VER) and $CONST_NAME ($CONST_VER) disagreed — using $CURRENT"
fi

IFS=. read -r MAJOR MINOR PATCH <<< "$CURRENT"
case "$BUMP" in
  patch) PATCH=$((PATCH + 1)) ;;
  minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
  major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
esac
VERSION="${MAJOR}.${MINOR}.${PATCH}"

# Rewrites the header + constant inside a theme directory (BSD/macOS sed).
write_versions() {
  sed -i '' -E "s/^([[:space:]]*Version:[[:space:]]*)[0-9.]+/\1${VERSION}/" "$1/style.css"
  if [ -n "$CONST_NAME" ]; then
    sed -i '' -E "s/(define\([[:space:]]*'${CONST_NAME}'[[:space:]]*,[[:space:]]*')[^']*'/\1${VERSION}'/" "$1/functions.php"
  fi
}

# ── Stage ────────────────────────────────────────────────────────────────────
STAGE_ROOT="$OUT_DIR/stage"
STAGE="$STAGE_ROOT/$SLUG"
ZIP="$OUT_DIR/$SLUG-v$VERSION.zip"

rm -rf "$STAGE_ROOT"
mkdir -p "$STAGE"

rsync -a "$ROOT/" "$STAGE/" \
  --exclude '.git*' \
  --exclude '.claude/' \
  --exclude '.vscode/' \
  --exclude '.idea/' \
  --exclude '.DS_Store' \
  --exclude '._*' \
  --exclude '__MACOSX/' \
  --exclude 'node_modules/' \
  --exclude 'dist-zips/' \
  --exclude 'tests/' \
  --exclude '*.zip' \
  --exclude '*.log' \
  --exclude 'build_zip.sh' \
  --exclude 'PrepGro Space Landing.html'

# The staged copy gets the new version first; the source tree is only
# rewritten once everything below has passed, so a failed build changes nothing.
write_versions "$STAGE"

# ── Assertions ───────────────────────────────────────────────────────────────
fail=0
while IFS= read -r -d '' f; do
  if ! out="$(php -l "$f" 2>&1)"; then
    echo "  LINT: ${f#"$STAGE"/}"; echo "$out" | sed 's/^/    /'; fail=1
  fi
done < <(find "$STAGE" -type f -name '*.php' -print0)

for required in style.css functions.php; do
  [ -f "$STAGE/$required" ] || { echo "  FAIL: $required missing from stage"; fail=1; }
done
grep -q "Version:[[:space:]]*${VERSION}" "$STAGE/style.css" || { echo "  FAIL: staged style.css is not at $VERSION"; fail=1; }

if [ "$fail" -ne 0 ]; then
  rm -rf "$STAGE_ROOT"
  echo "ERROR: staging failed — zip NOT written, version NOT bumped." >&2
  exit 1
fi

write_versions "$ROOT"
if [ "$BUMP" = "none" ]; then
  echo "▸ version $VERSION (no bump)"
else
  echo "▸ version $CURRENT -> $VERSION (style.css${CONST_NAME:+ + $CONST_NAME})"
fi

# ── Zip ──────────────────────────────────────────────────────────────────────
rm -f "$ZIP"
( cd "$STAGE_ROOT" && COPYFILE_DISABLE=1 zip -rqX "$ZIP" "$SLUG" )
rm -rf "$STAGE_ROOT"

for old in "$OUT_DIR/$SLUG-v"*.zip; do
  [ -f "$old" ] && [ "$old" != "$ZIP" ] || continue
  rm -f "$old"
  echo "  pruned ${old##*/}"
done

FILES="$(unzip -Z1 "$ZIP" | grep -vc '/$')"
echo "✔ ${ZIP#"$ROOT"/} ($(du -h "$ZIP" | cut -f1 | tr -d ' '), $FILES files)"
