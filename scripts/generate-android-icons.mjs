import { copyFileSync, mkdirSync, existsSync, unlinkSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = join(__dirname, '..');
const sourceIcon = join(rootDir, 'public', 'icons', 'icon-512.png');
const resDir = join(rootDir, 'android', 'app', 'src', 'main', 'res');

const mipmapDirs = [
    'mipmap-mdpi',
    'mipmap-hdpi',
    'mipmap-xhdpi',
    'mipmap-xxhdpi',
    'mipmap-xxxhdpi',
];

const iconNames = ['ic_launcher.png', 'ic_launcher_round.png', 'ic_launcher_foreground.png'];

for (const dir of mipmapDirs) {
    const targetDir = join(resDir, dir);

    if (!existsSync(targetDir)) {
        mkdirSync(targetDir, { recursive: true });
    }

    for (const name of iconNames) {
        copyFileSync(sourceIcon, join(targetDir, name));
    }
}

copyFileSync(sourceIcon, join(resDir, 'drawable', 'splash_icon.png'));

// Capacitor may regenerate splash.png which conflicts with our splash.xml.
const conflictingSplashPng = join(resDir, 'drawable', 'splash.png');

if (existsSync(conflictingSplashPng)) {
    unlinkSync(conflictingSplashPng);
}

console.log('Android icons copied from public/icons/icon-512.png');
