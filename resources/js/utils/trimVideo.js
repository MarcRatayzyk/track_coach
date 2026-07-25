/**
 * Rognage vidéo côté client (FFmpeg.wasm) avant compression / envoi.
 * - Essai rapide : stream copy (-c copy)
 * - Fallback : réencodage léger (libx264 ultrafast)
 */

import { FFmpeg } from '@ffmpeg/ffmpeg';
import { fetchFile, toBlobURL } from '@ffmpeg/util';
import { Capacitor } from '@capacitor/core';
import { resolveUploadBlob } from './compressVideo';

const FFMPEG_CORE_BASE = 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm';
const TRIM_TIMEOUT_MS = 4 * 60 * 1000;
const MIN_DURATION_SEC = 0.5;

/** @type {FFmpeg|null} */
let ffmpegInstance = null;
/** @type {Promise<FFmpeg>|null} */
let ffmpegReady = null;

/**
 * @returns {Promise<FFmpeg>}
 */
async function ensureFfmpeg() {
  if (ffmpegInstance) {
    return ffmpegInstance;
  }
  if (!ffmpegReady) {
    ffmpegReady = (async () => {
      const ffmpeg = new FFmpeg();
      await ffmpeg.load({
        coreURL: await toBlobURL(`${FFMPEG_CORE_BASE}/ffmpeg-core.js`, 'text/javascript'),
        wasmURL: await toBlobURL(`${FFMPEG_CORE_BASE}/ffmpeg-core.wasm`, 'application/wasm'),
      });
      ffmpegInstance = ffmpeg;
      return ffmpeg;
    })().catch((error) => {
      ffmpegReady = null;
      throw error;
    });
  }
  return ffmpegReady;
}

/**
 * URL lisible par un <video> pour prévisualiser une VideoSource.
 * @param {{ file?: Blob, path?: string }} source
 * @returns {string|null}
 */
export function getVideoPreviewUrl(source) {
  if (source?.file instanceof Blob) {
    return URL.createObjectURL(source.file);
  }
  if (!source?.path) {
    return null;
  }
  if (source.path.startsWith('blob:') || source.path.startsWith('data:') || source.path.startsWith('http')) {
    return source.path;
  }
  if (Capacitor.isNativePlatform()) {
    const uri = source.path.startsWith('file://') || source.path.startsWith('content://')
      ? source.path
      : source.path.startsWith('/')
        ? `file://${source.path}`
        : source.path;
    return Capacitor.convertFileSrc(uri);
  }
  return source.path;
}

/**
 * @param {number} startSec
 * @param {number} endSec
 * @param {number} [durationSec]
 * @returns {{ startSec: number, endSec: number }|null}
 */
export function normalizeTrimRange(startSec, endSec, durationSec = Infinity) {
  let start = Math.max(0, Number(startSec) || 0);
  let end = Math.max(0, Number(endSec) || 0);
  const max = Number.isFinite(durationSec) && durationSec > 0 ? durationSec : Infinity;

  if (end > max) {
    end = max;
  }
  if (start >= end) {
    return null;
  }
  if (end - start < MIN_DURATION_SEC) {
    return null;
  }
  return { startSec: start, endSec: end };
}

/**
 * @param {{ name?: string, size?: number, type?: string, file?: Blob, path?: string }} source
 * @param {{ startSec: number, endSec: number, onProgress?: (ratio: number) => void }} options
 * @returns {Promise<{ source: object, trimmed: boolean, originalBytes: number, outputBytes: number }>}
 */
export async function trimVideo(source, options = {}) {
  const onProgress = typeof options.onProgress === 'function' ? options.onProgress : () => {};
  const originalBytes = source.size ?? source.file?.size ?? 0;
  const range = normalizeTrimRange(options.startSec, options.endSec);
  if (!range) {
    throw new Error('Plage de rognage invalide.');
  }

  onProgress(0.02);

  let inputBlob;
  try {
    inputBlob = source.file instanceof Blob ? source.file : await resolveUploadBlob(source);
  } catch (error) {
    console.warn('[trimVideo] impossible de lire la source', error);
    throw new Error('Impossible de lire la vidéo à rogner.');
  }

  if (!(inputBlob instanceof Blob) || inputBlob.size < 256) {
    throw new Error('Vidéo source vide ou illisible.');
  }

  const inputUrl = URL.createObjectURL(inputBlob);
  const inputName = 'input' + guessExtension(source.name, source.type || inputBlob.type);
  const outputName = 'output.mp4';

  let timeoutId = 0;
  /** @type {((info: { progress: number }) => void)|null} */
  let progressHandler = null;

  try {
    const ffmpeg = await Promise.race([
      ensureFfmpeg(),
      new Promise((_, reject) => {
        timeoutId = window.setTimeout(() => reject(new Error('Chargement FFmpeg trop long')), 60_000);
      }),
    ]);
    clearTimeout(timeoutId);
    timeoutId = 0;

    onProgress(0.08);

    progressHandler = ({ progress }) => {
      if (typeof progress === 'number' && Number.isFinite(progress)) {
        onProgress(Math.min(0.95, Math.max(0.08, progress)));
      }
    };
    ffmpeg.on('progress', progressHandler);

    await ffmpeg.writeFile(inputName, await fetchFile(inputUrl));
    onProgress(0.12);

    const startStr = formatFfmpegTime(range.startSec);
    const endStr = formatFfmpegTime(range.endSec);

    let ok = await runTrim(ffmpeg, {
      inputName,
      outputName,
      startStr,
      endStr,
      reencode: false,
    });

    if (!ok) {
      await safeDeleteFfmpegFile(ffmpeg, outputName);
      ok = await runTrim(ffmpeg, {
        inputName,
        outputName,
        startStr,
        endStr,
        reencode: true,
      });
    }

    if (!ok) {
      throw new Error('Échec du rognage vidéo.');
    }

    const data = await ffmpeg.readFile(outputName);
    await safeDeleteFfmpegFile(ffmpeg, inputName);
    await safeDeleteFfmpegFile(ffmpeg, outputName);

    const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
    if (bytes.byteLength < 1024) {
      throw new Error('Clip rogné trop petit.');
    }

    const baseName = (source.name || 'video').replace(/\.[^.]+$/, '');
    const outputFile = new File([bytes], `${baseName}-trim.mp4`, { type: 'video/mp4' });
    onProgress(1);

    return {
      source: {
        name: outputFile.name,
        size: outputFile.size,
        type: 'video/mp4',
        file: outputFile,
      },
      trimmed: true,
      originalBytes: originalBytes || inputBlob.size,
      outputBytes: outputFile.size,
    };
  } catch (error) {
    console.warn('[trimVideo] échec', error);
    throw error instanceof Error ? error : new Error('Échec du rognage vidéo.');
  } finally {
    URL.revokeObjectURL(inputUrl);
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
    if (ffmpegInstance && progressHandler) {
      try {
        ffmpegInstance.off('progress', progressHandler);
      } catch {
        // ignore
      }
    }
  }
}

/**
 * @param {FFmpeg} ffmpeg
 * @param {{ inputName: string, outputName: string, startStr: string, endStr: string, reencode: boolean }} opts
 * @returns {Promise<boolean>}
 */
async function runTrim(ffmpeg, { inputName, outputName, startStr, endStr, reencode }) {
  const args = reencode
    ? [
        '-ss', startStr,
        '-to', endStr,
        '-i', inputName,
        '-c:v', 'libx264',
        '-preset', 'ultrafast',
        '-crf', '28',
        '-c:a', 'aac',
        '-b:a', '96k',
        '-movflags', '+faststart',
        '-y',
        outputName,
      ]
    : [
        '-ss', startStr,
        '-to', endStr,
        '-i', inputName,
        '-c', 'copy',
        '-movflags', '+faststart',
        '-avoid_negative_ts', 'make_zero',
        '-y',
        outputName,
      ];

  try {
    const code = await Promise.race([
      ffmpeg.exec(args),
      new Promise((_, reject) => {
        window.setTimeout(() => reject(new Error('Trim timeout')), TRIM_TIMEOUT_MS);
      }),
    ]);
    if (code !== 0) {
      return false;
    }
    const data = await ffmpeg.readFile(outputName);
    const size = data instanceof Uint8Array ? data.byteLength : data?.length ?? 0;
    return size >= 1024;
  } catch (error) {
    console.warn(`[trimVideo] ${reencode ? 'reencode' : 'copy'} failed`, error);
    return false;
  }
}

/**
 * @param {FFmpeg} ffmpeg
 * @param {string} name
 */
async function safeDeleteFfmpegFile(ffmpeg, name) {
  try {
    await ffmpeg.deleteFile(name);
  } catch {
    // ignore
  }
}

/**
 * @param {number} seconds
 * @returns {string}
 */
function formatFfmpegTime(seconds) {
  const s = Math.max(0, Number(seconds) || 0);
  const hours = Math.floor(s / 3600);
  const minutes = Math.floor((s % 3600) / 60);
  const secs = s % 60;
  const whole = Math.floor(secs);
  const ms = Math.round((secs - whole) * 1000);
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(whole).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
}

/**
 * @param {string|undefined} name
 * @param {string|undefined} mime
 * @returns {string}
 */
function guessExtension(name, mime) {
  const fromName = name?.match(/(\.[a-z0-9]+)$/i)?.[1];
  if (fromName) {
    return fromName.toLowerCase();
  }
  if (mime?.includes('webm')) {
    return '.webm';
  }
  if (mime?.includes('quicktime')) {
    return '.mov';
  }
  return '.mp4';
}
