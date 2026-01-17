#!/bin/bash

# Script to rename files and classes from MksCms to Mksine

cd "$(dirname "$0")"

echo "Renaming files and classes from MksCms to Mksine..."

# 1. Rename MksCms.php to Mksine.php
if [ -f "src/MksCms.php" ]; then
    mv "src/MksCms.php" "src/Mksine.php"
    sed -i '' 's/class MksCms/class Mksine/g' "src/Mksine.php"
    echo "✓ Renamed MksCms.php to Mksine.php"
fi

# 2. Rename MksCmsServiceProvider.php to MksineServiceProvider.php
if [ -f "src/MksCmsServiceProvider.php" ]; then
    mv "src/MksCmsServiceProvider.php" "src/MksineServiceProvider.php"
    sed -i '' 's/class MksCmsServiceProvider/class MksineServiceProvider/g' "src/MksineServiceProvider.php"
    echo "✓ Renamed MksCmsServiceProvider.php to MksineServiceProvider.php"
fi

# 3. Rename MksCmsPlugin.php to MksinePlugin.php
if [ -f "src/MksCmsPlugin.php" ]; then
    mv "src/MksCmsPlugin.php" "src/MksinePlugin.php"
    sed -i '' 's/class MksCmsPlugin/class MksinePlugin/g' "src/MksinePlugin.php"
    echo "✓ Renamed MksCmsPlugin.php to MksinePlugin.php"
fi

# 4. Rename MksCmsCommand.php to MksineCommand.php
if [ -f "src/Commands/MksCmsCommand.php" ]; then
    mv "src/Commands/MksCmsCommand.php" "src/Commands/MksineCommand.php"
    sed -i '' 's/class MksCmsCommand/class MksineCommand/g' "src/Commands/MksineCommand.php"
    echo "✓ Renamed MksCmsCommand.php to MksineCommand.php"
fi

# 5. Rename MksCmsInstallCommand.php to MksineInstallCommand.php
if [ -f "src/Commands/MksCmsInstallCommand.php" ]; then
    mv "src/Commands/MksCmsInstallCommand.php" "src/Commands/MksineInstallCommand.php"
    sed -i '' 's/class MksCmsInstallCommand/class MksineInstallCommand/g' "src/Commands/MksineInstallCommand.php"
    echo "✓ Renamed MksCmsInstallCommand.php to MksineInstallCommand.php"
fi

# 6. Rename Facades/MksCms.php to Facades/Mksine.php
if [ -f "src/Facades/MksCms.php" ]; then
    mv "src/Facades/MksCms.php" "src/Facades/Mksine.php"
    sed -i '' 's/class MksCms/class Mksine/g' "src/Facades/Mksine.php"
    sed -i '' 's/\\Miran\\Mksine\\MksCms/\\Miran\\Mksine\\Mksine/g' "src/Facades/Mksine.php"
    echo "✓ Renamed Facades/MksCms.php to Facades/Mksine.php"
fi

# 7. Rename Testing/TestsMksCms.php to Testing/TestsMksine.php
if [ -f "src/Testing/TestsMksCms.php" ]; then
    mv "src/Testing/TestsMksCms.php" "src/Testing/TestsMksine.php"
    sed -i '' 's/class TestsMksCms/class TestsMksine/g' "src/Testing/TestsMksine.php"
    echo "✓ Renamed TestsMksCms.php to TestsMksine.php"
fi

# 8. Rename Core/Events/MksCmsEvent.php to Core/Events/MksineEvent.php
if [ -f "src/Core/Events/MksCmsEvent.php" ]; then
    mv "src/Core/Events/MksCmsEvent.php" "src/Core/Events/MksineEvent.php"
    sed -i '' 's/abstract class MksCmsEvent/abstract class MksineEvent/g' "src/Core/Events/MksineEvent.php"
    echo "✓ Renamed MksCmsEvent.php to MksineEvent.php"
fi

# 9. Rename Core/Hooks/MksCmsListenerInterface.php to Core/Hooks/MksineListenerInterface.php
if [ -f "src/Core/Hooks/MksCmsListenerInterface.php" ]; then
    mv "src/Core/Hooks/MksCmsListenerInterface.php" "src/Core/Hooks/MksineListenerInterface.php"
    sed -i '' 's/interface MksCmsListenerInterface/interface MksineListenerInterface/g' "src/Core/Hooks/MksineListenerInterface.php"
    echo "✓ Renamed MksCmsListenerInterface.php to MksineListenerInterface.php"
fi

# 10. Rename test file
if [ -f "tests/Unit/Hooks/MksCmsEventTest.php" ]; then
    mv "tests/Unit/Hooks/MksCmsEventTest.php" "tests/Unit/Hooks/MksineEventTest.php"
    sed -i '' 's/MksCmsEvent/MksineEvent/g' "tests/Unit/Hooks/MksineEventTest.php"
    echo "✓ Renamed MksCmsEventTest.php to MksineEventTest.php"
fi

echo ""
echo "Updating references in all files..."

# Update all references to renamed classes
find src tests -type f -name "*.php" | while read file; do
    if [ -f "$file" ]; then
        # Update class references
        sed -i '' 's/MksCmsServiceProvider/MksineServiceProvider/g' "$file"
        sed -i '' 's/MksCmsPlugin/MksinePlugin/g' "$file"
        sed -i '' 's/MksCmsCommand/MksineCommand/g' "$file"
        sed -i '' 's/MksCmsInstallCommand/MksineInstallCommand/g' "$file"
        sed -i '' 's/TestsMksCms/TestsMksine/g' "$file"
        sed -i '' 's/MksCmsEvent/MksineEvent/g' "$file"
        sed -i '' 's/MksCmsListenerInterface/MksineListenerInterface/g' "$file"
        
        # Update MksCms class references (but be careful with Facade)
        sed -i '' 's/\\Miran\\Mksine\\MksCms[^F]/\\Miran\\Mksine\\Mksine/g' "$file"
        sed -i '' 's/use Miran\\Mksine\\MksCms;/use Miran\\Mksine\\Mksine;/g' "$file"
        sed -i '' 's/use Miran\\Mksine\\MksCms\\/use Miran\\Mksine\\Mksine\\/g' "$file"
    fi
done

# Update Facade reference separately
find src tests -type f -name "*.php" | while read file; do
    if [ -f "$file" ]; then
        sed -i '' 's/\\Miran\\Mksine\\Facades\\MksCms/\\Miran\\Mksine\\Facades\\Mksine/g' "$file"
        sed -i '' 's/use Miran\\Mksine\\Facades\\MksCms/use Miran\\Mksine\\Facades\\Mksine/g' "$file"
    fi
done

echo "Done! Please review the changes and test your application."
