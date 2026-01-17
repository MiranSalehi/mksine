#!/bin/bash

# Script to update git remote URL after renaming repository

cd "$(dirname "$0")"

echo "Updating git remote URL..."

# Update remote URL to new repository name
git remote set-url origin https://github.com/MiranSalehi/mksine.git

echo "✓ Remote URL updated to: https://github.com/MiranSalehi/mksine.git"
echo ""
echo "To verify, run: git remote -v"
echo ""
echo "After renaming the repository on GitHub, you can push with:"
echo "  git push -u origin main"
