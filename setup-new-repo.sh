#!/bin/bash

# Script to setup fresh repository for mksine

cd "$(dirname "$0")"

echo "🚀 Setting up fresh repository for mksine..."
echo ""

# Step 1: Remove old .git directory
if [ -d ".git" ]; then
    echo "Step 1: Removing old .git directory..."
    rm -rf .git
    echo "✓ Old git history removed"
    echo ""
fi

# Step 2: Initialize new git repository
echo "Step 2: Initializing new git repository..."
git init
git branch -M main
echo "✓ New repository initialized"
echo ""

# Step 3: Add remote (user needs to create repo on GitHub first)
echo "Step 3: Setting up remote..."
echo "⚠️  Please create a new repository 'mksine' on GitHub first!"
echo "   Go to: https://github.com/new"
echo "   Repository name: mksine"
echo "   Don't initialize with README, .gitignore, or license"
echo ""
read -p "Press Enter after creating the repository on GitHub..."

git remote add origin https://github.com/MiranSalehi/mksine.git
echo "✓ Remote added"
echo ""

# Step 4: Add all files
echo "Step 4: Adding all files..."
git add -A
echo "✓ Files added"
echo ""

# Step 5: Create initial commit
echo "Step 5: Creating initial commit..."
git commit -m "Initial commit: MKSine package

A headless CMS core package built with Filament for the MKS project.
Renamed from mks-cms to mksine."
echo "✓ Initial commit created"
echo ""

# Step 6: Push to GitHub
echo "Step 6: Pushing to GitHub..."
git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Success! Repository is now on GitHub at:"
    echo "   https://github.com/MiranSalehi/mksine"
else
    echo ""
    echo "❌ Push failed. Please check:"
    echo "   1. Repository 'mksine' exists on GitHub"
    echo "   2. You have push access"
    echo "   3. Your authentication is set up"
fi
