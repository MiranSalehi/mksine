#!/usr/bin/env node
/**
 * Run php artisan filament:assets from the Laravel app root.
 * Finds app root by walking up from package dir until a directory containing "artisan" is found.
 */
import { execSync } from 'child_process';
import { existsSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const packageRoot = join(__dirname, '..');

let dir = packageRoot;
while (dir !== join(dir, '..')) {
    if (existsSync(join(dir, 'artisan'))) {
        break;
    }
    dir = join(dir, '..');
}

if (!existsSync(join(dir, 'artisan'))) {
    console.warn('mksine: Laravel app root (artisan) not found; skipping filament:assets.');
    process.exit(0);
}

try {
    execSync('php artisan filament:assets', {
        stdio: 'inherit',
        cwd: dir,
    });
} catch (e) {
    process.exitCode = e.status ?? 1;
}
