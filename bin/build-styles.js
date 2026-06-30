#!/usr/bin/env node
/**
 * Rebuild MKSine admin Tailwind (resources/dist/mksine.css).
 *
 * Invoke from a plugin directory, e.g.:
 *   node ../../vendor/miran/mksine/bin/build-styles.js
 *
 * Works for Composer installs (vendor/miran/mksine) and monorepos where that
 * path symlinks to packages/mksine.
 */
import { execSync } from 'child_process';
import { existsSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const packageRoot = join(dirname(fileURLToPath(import.meta.url)), '..');

if (! existsSync(join(packageRoot, 'package.json'))) {
    console.error('mksine: package.json not found at', packageRoot);
    process.exit(1);
}

try {
    execSync('npm run build:styles', {
        stdio: 'inherit',
        cwd: packageRoot,
        shell: true,
    });
} catch (error) {
    process.exitCode = error.status ?? 1;
}
