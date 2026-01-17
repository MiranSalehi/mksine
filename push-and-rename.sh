#!/bin/bash

# Script to temporarily push to old repo, then rename

cd "$(dirname "$0")"

echo "Step 1: Temporarily changing remote to mks-cms..."
git remote set-url origin https://github.com/MiranSalehi/mks-cms.git

echo "Step 2: Pushing changes..."
git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Push successful!"
    echo ""
    echo "Step 3: Now rename the repository on GitHub:"
    echo "  1. Go to https://github.com/MiranSalehi/mks-cms"
    echo "  2. Settings → General → Repository name → Change to 'mksine'"
    echo ""
    echo "Step 4: After renaming, update remote URL:"
    echo "  git remote set-url origin https://github.com/MiranSalehi/mksine.git"
    echo "  git push -u origin main"
else
    echo ""
    echo "✗ Push failed. Please check your authentication."
fi
