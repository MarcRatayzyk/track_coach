import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { deflateSync } from 'node:zlib';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '..', 'public', 'icons');

function crc32(buffer) {
    let crc = 0xffffffff;

    for (let index = 0; index < buffer.length; index += 1) {
        crc ^= buffer[index];

        for (let bit = 0; bit < 8; bit += 1) {
            crc = crc & 1 ? (crc >>> 1) ^ 0xedb88320 : crc >>> 1;
        }
    }

    return (crc ^ 0xffffffff) >>> 0;
}

function pngChunk(type, data) {
    const typeBuffer = Buffer.from(type, 'ascii');
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length, 0);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(Buffer.concat([typeBuffer, data])), 0);

    return Buffer.concat([length, typeBuffer, data, crc]);
}

function createPng(size) {
    const background = { r: 15, g: 23, b: 42 };
    const panel = { r: 30, g: 41, b: 59 };
    const bolt = { r: 96, g: 165, b: 250 };
    const pixels = Buffer.alloc(size * size * 4);

    const center = (size - 1) / 2;
    const panelRadius = size * 0.34;

    for (let y = 0; y < size; y += 1) {
        for (let x = 0; x < size; x += 1) {
            const offset = (y * size + x) * 4;
            const dx = x - center;
            const dy = y - center;
            const distance = Math.hypot(dx, dy);
            let color = background;

            if (distance <= panelRadius) {
                color = panel;
            }

            const boltPoints = [
                [0.02, -0.24],
                [-0.08, 0.02],
                [0.0, 0.02],
                [-0.12, 0.28],
                [0.1, -0.02],
                [0.02, -0.02],
            ];
            const scale = size * 0.42;
            const px = dx / scale;
            const py = dy / scale;
            let insideBolt = false;

            for (let index = 0, j = boltPoints.length - 1; index < boltPoints.length; j = index, index += 1) {
                const [xi, yi] = boltPoints[index];
                const [xj, yj] = boltPoints[j];
                const intersects =
                    yi > py !== yj > py && px < ((xj - xi) * (py - yi)) / (yj - yi + Number.EPSILON) + xi;

                if (intersects) {
                    insideBolt = !insideBolt;
                }
            }

            if (insideBolt) {
                color = bolt;
            }

            pixels[offset] = color.r;
            pixels[offset + 1] = color.g;
            pixels[offset + 2] = color.b;
            pixels[offset + 3] = 255;
        }
    }

    const stride = size * 4 + size;
    const raw = Buffer.alloc(stride * size);

    for (let y = 0; y < size; y += 1) {
        const rowStart = y * stride;
        raw[rowStart] = 0;
        pixels.copy(raw, rowStart + 1, y * size * 4, (y + 1) * size * 4);
    }

    const signature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(size, 0);
    ihdr.writeUInt32BE(size, 4);
    ihdr[8] = 8;
    ihdr[9] = 6;
    ihdr[10] = 0;
    ihdr[11] = 0;
    ihdr[12] = 0;

    return Buffer.concat([
        signature,
        pngChunk('IHDR', ihdr),
        pngChunk('IDAT', deflateSync(raw)),
        pngChunk('IEND', Buffer.alloc(0)),
    ]);
}

mkdirSync(outDir, { recursive: true });

writeFileSync(join(outDir, 'icon-144.png'), createPng(144));
writeFileSync(join(outDir, 'icon-192.png'), createPng(192));
writeFileSync(join(outDir, 'icon-512.png'), createPng(512));
writeFileSync(join(outDir, 'apple-touch-icon.png'), createPng(180));

console.log('PWA icons generated in public/icons/');
