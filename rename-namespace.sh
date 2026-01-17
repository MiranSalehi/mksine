#!/bin/bash

# Script to rename namespace from MiranSalehi\MksCms to Miran\Mksine

cd "$(dirname "$0")"

echo "Renaming namespace from MiranSalehi\\MksCms to Miran\\Mksine..."

# Find all PHP files and replace namespace
find src tests -type f -name "*.php" | while read file; do
    if [ -f "$file" ]; then
        # Replace namespace declaration
        sed -i '' 's/namespace MiranSalehi\\MksCms/namespace Miran\\Mksine/g' "$file"
        
        # Replace use statements
        sed -i '' 's/use MiranSalehi\\MksCms/use Miran\\Mksine/g' "$file"
        
        # Replace in strings (escaped backslashes)
        sed -i '' 's/MiranSalehi\\\\MksCms/Miran\\\\Mksine/g' "$file"
        
        # Replace single backslash in strings
        sed -i '' 's/\\MiranSalehi\\MksCms/\\Miran\\Mksine/g' "$file"
    fi
done

# Update stub files
find stubs -type f -name "*.stub" 2>/dev/null | while read file; do
    if [ -f "$file" ]; then
        sed -i '' 's/MiranSalehi\\MksCms/Miran\\Mksine/g' "$file"
        sed -i '' 's/MiranSalehi\\\\MksCms/Miran\\\\Mksine/g' "$file"
    fi
done

# Update other files (routes, config, etc.)
find routes config -type f -name "*.php" 2>/dev/null | while read file; do
    if [ -f "$file" ]; then
        sed -i '' 's/MiranSalehi\\MksCms/Miran\\Mksine/g' "$file"
        sed -i '' 's/MiranSalehi\\\\MksCms/Miran\\\\Mksine/g' "$file"
    fi
done

echo "Done! Please review the changes and test your application."
