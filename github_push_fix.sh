#!/bin/bash
# Run this script on your LOCAL machine inside the NullNet folder
# It fixes the pack corruption error and removes large/unnecessary files from git tracking

echo "=== NullNet GitHub Push Fix ==="
echo ""

# Step 1: Untrack files that should not be in version control
echo "[1/4] Removing large/unnecessary files from git tracking..."
git rm --cached composer.phar 2>/dev/null && echo "  Removed: composer.phar" || echo "  Skipped: composer.phar (not tracked)"
git rm --cached felux.sql 2>/dev/null && echo "  Removed: felux.sql" || echo "  Skipped: felux.sql (not tracked)"
git rm --cached seller/files/shell_new2.zip 2>/dev/null && echo "  Removed: seller/files/shell_new2.zip" || echo "  Skipped"
git rm --cached admin/vendors/summernote/dist/summernote-0.8.8-dist.zip 2>/dev/null && echo "  Removed summernote zip (admin)" || echo "  Skipped"
git rm --cached support/vendors/summernote/dist/summernote-0.8.8-dist.zip 2>/dev/null && echo "  Removed summernote zip (support)" || echo "  Skipped"

# Step 2: Rebuild pack files to fix the "did not receive expected object" error
echo ""
echo "[2/4] Rebuilding git pack files (fixes the push error)..."
git repack -a -d --aggressive 2>&1
git gc --aggressive --prune=now 2>&1

# Step 3: Verify no large files remain tracked
echo ""
echo "[3/4] Checking for large tracked files (should be empty)..."
git ls-files | xargs -I{} sh -c 'size=$(du -b "{}" 2>/dev/null | cut -f1); [ "$size" -gt 10000000 ] && echo "  WARNING large file: {} ($size bytes)"' 2>/dev/null
echo "  Done."

# Step 4: Commit the .gitignore changes and untracked removals
echo ""
echo "[4/4] Committing cleanup..."
git add .gitignore
git status --short

echo ""
echo "=== Ready to push ==="
echo "Run the following to push to GitHub:"
echo "  git commit -m 'Clean up: update gitignore, remove large binaries from tracking'"
echo "  git push -u origin main --force"
