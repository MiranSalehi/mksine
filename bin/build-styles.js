#!/usr/bin/env node
/**
 * Rebuild MKSine admin Tailwind (resources/dist/mksine.css).
 *
 * Invoke from a plugin directory, e.g.:
 *   node ../../vendor/miran/mksine/bin/build-styles.js
 *
 * Only rebuilds in the monorepo package tree (`packages/mksine`). Composer
 * installs live under `vendor/miran/mksine` where Filament import paths differ;
 * those installs use the pre-built `resources/dist/mksine.css` shipped in the
 * package archive.
 */
import { execSync } from 'child_process';
import { existsSync, realpathSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const packageRoot = join(dirname(fileURLToPath(import.meta.url)), '..');
const indexCss = join(packageRoot, 'resources/css/index.css');
const distCss = join(packageRoot, 'resources/dist/mksine.css');

let resolvedRoot = packageRoot;

try {
    resolvedRoot = realpathSync(packageRoot);
} catch {
    // Keep packageRoot when realpath fails (e.g. broken symlink).
}

const normalizedRoot = resolvedRoot.replace(/\\/g, '/');
const isMonorepoPackage = normalizedRoot.endsWith('/packages/mksine')
    || normalizedRoot.includes('/packages/mksine/');

if (! existsSync(indexCss)) {
    console.error('mksine: resources/css/index.css not found at', packageRoot);
    process.exit(1);
}

if (! isMonorepoPackage) {
    if (existsSync(distCss)) {
        console.warn(
            'mksine: skipping admin Tailwind rebuild (Composer vendor install); using pre-built CSS.',
        );
        console.warn('mksine:', distCss);
        process.exit(0);
    }

    console.error(
        'mksine: admin Tailwind rebuild is only supported from packages/mksine; pre-built mksine.css is missing at',
        distCss,
    );
    process.exit(1);
}

const packageJson = join(packageRoot, 'package.json');
const command = existsSync(join(packageRoot, 'node_modules', '@tailwindcss', 'cli'))
    ? 'npm run build:styles'
    : 'npx --yes @tailwindcss/cli@^4.0.0 -i resources/css/index.css -o resources/dist/mksine.css --minify';

if (! existsSync(packageJson)) {
    console.error('mksine: package.json not found at', packageRoot);
    process.exit(1);
}

try {
    execSync(command, {
        stdio: 'inherit',
        cwd: packageRoot,
        shell: true,
    });
} catch (error) {
    process.exitCode = error.status ?? 1;
}
