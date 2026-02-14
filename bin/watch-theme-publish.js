#!/usr/bin/env node
/**
 * Watch package resources and run theme-publish on change (for npm run dev).
 */
import { spawn } from 'child_process';
import { watch } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const packageRoot = join(__dirname, '..');
const resourcesDir = join(packageRoot, 'resources');

let debounceTimer;
const run = () => {
    debounceTimer = null;
    spawn('node', [join(__dirname, 'theme-publish.js')], {
        stdio: 'inherit',
        cwd: packageRoot,
    });
};

const debouncedRun = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(run, 600);
};

run();

watch(resourcesDir, { recursive: true }, () => {
    debouncedRun();
});
