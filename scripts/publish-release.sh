#!/usr/bin/env bash
#
# Build the MKSine release files and attach them to the GitHub release for the
# current package version (tag v{version}, which must already be pushed).
#
#   packages/mksine/scripts/publish-release.sh            # publish
#   packages/mksine/scripts/publish-release.sh --draft    # create a draft release
#
# Environment:
#   MKSINE_APP_DIR        Laravel app that contains this package (default: two levels up)
#   MKSINE_RELEASE_REPO   GitHub repository (default: MiranSalehi/mksine)
#
# The download page reads the newest manifest from
#   https://github.com/{repo}/releases/latest/download/release.json
# and links the quick installer at
#   https://github.com/{repo}/releases/latest/download/mksine-installer.php
set -euo pipefail

PACKAGE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
APP_DIR="${MKSINE_APP_DIR:-$(cd "$PACKAGE_DIR/../.." && pwd)}"
REPO="${MKSINE_RELEASE_REPO:-MiranSalehi/mksine}"
DRAFT_FLAG=""

if [[ "${1:-}" == "--draft" ]]; then
    DRAFT_FLAG="--draft"
fi

command -v gh >/dev/null || { echo "GitHub CLI (gh) is required: https://cli.github.com" >&2; exit 1; }
[[ -f "$APP_DIR/artisan" ]] || { echo "No artisan in $APP_DIR; set MKSINE_APP_DIR." >&2; exit 1; }

VERSION="$(grep -oE "'version' => '[^']+'" "$PACKAGE_DIR/config/mksine.php" | head -1 | cut -d"'" -f4)"
TAG="v$VERSION"
OUT="$APP_DIR/storage/app/releases/$VERSION"

if ! git -C "$PACKAGE_DIR" ls-remote --exit-code --tags origin "refs/tags/$TAG" >/dev/null; then
    echo "Tag $TAG is not on origin. Tag and push it first: git tag $TAG && git push origin $TAG" >&2
    exit 1
fi

echo "Building MKSine $VERSION release files…"
php -d memory_limit=1G "$APP_DIR/artisan" mks:release-build --output-dir="$OUT"

(cd "$OUT" && shasum -a 256 -c SHA256SUMS)

ASSETS=("$OUT"/mksine-"$VERSION"-*.zip "$OUT/mksine-installer.php" "$OUT/release.json" "$OUT/SHA256SUMS")

if gh release view "$TAG" --repo "$REPO" >/dev/null 2>&1; then
    gh release upload "$TAG" "${ASSETS[@]}" --repo "$REPO" --clobber
else
    gh release create "$TAG" "${ASSETS[@]}" --repo "$REPO" --title "MKSine $VERSION" --generate-notes --verify-tag $DRAFT_FLAG
fi

echo "Published: https://github.com/$REPO/releases/tag/$TAG"
echo "Manifest:  https://github.com/$REPO/releases/latest/download/release.json"
echo "Installer: https://github.com/$REPO/releases/latest/download/mksine-installer.php"
