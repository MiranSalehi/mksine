#!/usr/bin/env node
/**
 * Rebuild MKSine admin Tailwind (resources/dist/mksine.css).
 *
 * Invoke from a plugin directory, e.g.:
 *   node ../../vendor/miran/mksine/bin/build-styles.js
 *
 * Composer dist archives omit package.json (export-ignore). When only the
 * pre-built CSS is shipped, this script exits successfully so plugin builds
 * can continue. Run `npm install` in vendor/miran/mksine when you need a
 * full Tailwind rebuild on a consumer app.
 */
import { execSync } from 'child_process';
import { existsSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const packageRoot = join(dirname(fileURLToPath(import.meta.url)), '..');
const indexCss = join(packageRoot, 'resources/css/index.css');
const distCss = join(packageRoot, 'resources/dist/mksine.css');
const packageJson = join(packageRoot, 'package.json');

if (! existsSync(indexCss)) {
    console.error('mksine: resources/css/index.css not found at', packageRoot);
    process.exit(1);
}

if (! existsSync(packageJson)) {
    if (existsSync(distCss)) {
        console.warn(
            'mksine: package.json is not included in the Composer package; skipping Tailwind rebuild.',
        );
        console.warn('mksine: using pre-built', distCss);
        process.exit(0);
    }

    console.error(
        'mksine: cannot rebuild styles (no package.json) and pre-built mksine.css is missing at',
        distCss,
    );
    process.exit(1);
}

const command = existsSync(join(packageRoot, 'node_modules', '@tailwindcss', 'cli'))
    ? 'npm run build:styles'
    : 'npx --yes @tailwindcss/cli@^4.0.0 -i resources/css/index.css -o resources/dist/mksine.css --minify';

try {
    execSync(command, {
        stdio: 'inherit',
        cwd: packageRoot,
        shell: true,
    });
} catch (error) {
    process.exitCode = error.status ?? 1;
}
