import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const srcDir = join(root, 'node_modules', '@ffmpeg', 'core', 'dist', 'esm');
const destDir = join(root, 'public', 'ffmpeg');

const files = ['ffmpeg-core.js', 'ffmpeg-core.wasm'];

if (!existsSync(join(srcDir, 'ffmpeg-core.wasm'))) {
  console.warn('[sync-ffmpeg-core] @ffmpeg/core manquant — npm install @ffmpeg/core');
  process.exit(0);
}

mkdirSync(destDir, { recursive: true });
for (const file of files) {
  copyFileSync(join(srcDir, file), join(destDir, file));
  console.log(`[sync-ffmpeg-core] ${file}`);
}
