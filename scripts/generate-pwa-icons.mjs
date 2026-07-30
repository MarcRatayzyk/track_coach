/**
 * Génère les icônes PWA / favicon à partir de resources/brand/logo.png
 */
import { spawnSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = join(__dirname, '..');
const script = join(__dirname, 'generate-icons-from-logo.py');

const candidates = [
    process.env.PYTHON,
    'C:\\Users\\marcr\\AppData\\Local\\Programs\\Python\\Python314\\python.exe',
    'py',
    'python',
    'python3',
].filter(Boolean);

let lastError = null;

for (const python of candidates) {
    const args = python === 'py' ? ['-3', script] : [script];
    const result = spawnSync(python, args, {
        cwd: rootDir,
        encoding: 'utf8',
        shell: false,
    });

    if (result.status === 0) {
        if (result.stdout) {
            process.stdout.write(result.stdout);
        }
        process.exit(0);
    }

    lastError = result.stderr || result.stdout || `exit ${result.status}`;
}

console.error('Failed to generate icons. Install Pillow (pip install pillow).');
if (lastError) {
    console.error(lastError);
}
process.exit(1);
