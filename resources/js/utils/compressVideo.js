/**
 * Préparation d'une vidéo avant envoi :
 * - natif Capacitor : compression hardware (MediaCodec / AVFoundation)
 *   directement à partir du chemin picker.
 * - web : FFmpeg.wasm via le même plugin (blob URL → MP4 ~720p).
 *
 * En cas d'échec / gain insuffisant, on renvoie la source d'origine.
 */

import { Capacitor } from '@capacitor/core';
import { Filesystem } from '@capacitor/filesystem';
import { NativeVideoCompressor } from 'capacitor-native-video-compressor';
import { isVirtualNativePath, materializeNativeVideoPath } from './nativeVideoFile';

const SKIP_UNDER_BYTES = 2 * 1024 * 1024;
const MIN_SAVINGS_RATIO = 0.12;
const COMPRESS_TIMEOUT_MS = 4 * 60 * 1000;
// HIGH = ~720p. Ne pas utiliser LOW : sur Android le plugin le mappe en VERY_LOW (qualité inutilisable).
const COMPRESS_QUALITY = 'HIGH';

let webCompressorReady = null;

/**
 * @typedef {Object} VideoSource
 * @property {string} name
 * @property {number} size
 * @property {string} type
 * @property {File} [file]      Fichier web (input HTML).
 * @property {string} [path]    Chemin natif / blob URL après compression.
 * @property {boolean} [isTemp] Fichier temporaire à supprimer après upload.
 */

/**
 * @param {VideoSource} source
 * @param {{ onProgress?: (ratio: number) => void }} [options]
 * @returns {Promise<{ source: VideoSource, compressed: boolean, originalBytes: number, outputBytes: number }>}
 */
export async function compressVideo(source, options = {}) {
  const onProgress = typeof options.onProgress === 'function' ? options.onProgress : () => {};
  const originalBytes = source.size ?? source.file?.size ?? 0;

  const passthrough = () => {
    onProgress(1);
    return { source, compressed: false, originalBytes, outputBytes: originalBytes };
  };

  // Petites vidéos : le gain ne vaut pas le temps de compression.
  if (originalBytes > 0 && originalBytes < SKIP_UNDER_BYTES) {
    return passthrough();
  }

  try {
    // Natif : compression hardware uniquement (chemin fichier).
    // Jamais FFmpeg.wasm dans le WebView Capacitor — OOM / crash app.
    if (Capacitor.isNativePlatform()) {
      if (source.path) {
        let nativeSource = source;
        if (isVirtualNativePath(source.path)) {
          const path = await materializeNativeVideoPath(source);
          nativeSource = { ...source, path, isTemp: true };
          source.path = path;
          source.isTemp = true;
        }
        return await compressWithPlugin(nativeSource, onProgress, { isWeb: false });
      }
      return passthrough();
    }

    if (source.file instanceof Blob) {
      return await compressWithPlugin(source, onProgress, { isWeb: true });
    }
  } catch (error) {
    console.warn('[compressVideo] fallback to original', error);
    return passthrough();
  }

  return passthrough();
}

/**
 * Initialise FFmpeg.wasm une seule fois (no-op sur natif).
 * @returns {Promise<void>}
 */
async function ensureWebCompressor() {
  if (Capacitor.isNativePlatform()) {
    return;
  }
  if (!webCompressorReady) {
    webCompressorReady = NativeVideoCompressor.initialize().then((result) => {
      if (!result?.success) {
        webCompressorReady = null;
        throw new Error(result?.message || 'FFmpeg.wasm indisponible');
      }
    });
  }
  await webCompressorReady;
}

/**
 * @param {VideoSource} source
 * @param {(ratio: number) => void} onProgress
 * @param {{ isWeb: boolean }} platform
 * @returns {Promise<{ source: VideoSource, compressed: boolean, originalBytes: number, outputBytes: number }>}
 */
async function compressWithPlugin(source, onProgress, { isWeb }) {
  const originalBytes = source.size ?? source.file?.size ?? 0;
  let listener = null;
  let timeoutId = 0;
  let inputBlobUrl = null;

  onProgress(0.02);

  if (isWeb) {
    await ensureWebCompressor();
    inputBlobUrl = URL.createObjectURL(source.file);
  }

  const sourcePath = isWeb ? inputBlobUrl : toNativeUri(source.path);

  listener = await NativeVideoCompressor.addListener('onProgress', (info) => {
    if (info?.status === 'progress' && typeof info.percent === 'number') {
      const ratio = info.percent > 1 ? info.percent / 100 : info.percent;
      onProgress(Math.min(0.98, Math.max(0.02, ratio)));
    } else if (info?.status === 'started' || info?.status === 'loading_core') {
      onProgress(0.05);
    }
  });

  try {
    const result = await Promise.race([
      NativeVideoCompressor.compressVideo({
        sourcePath,
        quality: COMPRESS_QUALITY,
      }),
      new Promise((_, reject) => {
        timeoutId = window.setTimeout(
          () => reject(new Error('Compression timeout')),
          COMPRESS_TIMEOUT_MS,
        );
      }),
    ]);

    if (!result?.success || !result.destPath) {
      throw new Error('Compression sans fichier de sortie');
    }

    const destPath = result.destPath;
    let outputBytes = 0;
    let outputFile = null;

    if (isWeb || destPath.startsWith('blob:')) {
      const response = await fetch(destPath);
      if (!response.ok) {
        throw new Error('Impossible de lire la vidéo compressée');
      }
      const blob = await response.blob();
      outputBytes = blob.size;
      const baseName = (source.name || 'video').replace(/\.[^.]+$/, '');
      outputFile = new File([blob], `${baseName}-720p.mp4`, { type: 'video/mp4' });
    } else {
      outputBytes = await nativeFileSize(destPath);
    }

    const savedEnough =
      outputBytes > 1024 &&
      (originalBytes <= 0 || outputBytes <= originalBytes * (1 - MIN_SAVINGS_RATIO));

    if (!savedEnough) {
      if (!isWeb && destPath) {
        await safeDeleteAbsolutePath(destPath);
      }
      if (destPath?.startsWith('blob:')) {
        URL.revokeObjectURL(destPath);
      }
      onProgress(1);
      return { source, compressed: false, originalBytes, outputBytes: originalBytes };
    }

    const baseName = (source.name || 'video').replace(/\.[^.]+$/, '');
    onProgress(1);

    if (outputFile) {
      if (destPath?.startsWith('blob:')) {
        URL.revokeObjectURL(destPath);
      }
      return {
        source: {
          name: outputFile.name,
          size: outputBytes,
          type: 'video/mp4',
          file: outputFile,
        },
        compressed: true,
        originalBytes,
        outputBytes,
      };
    }

    return {
      source: {
        name: `${baseName}-720p.mp4`,
        size: outputBytes,
        type: 'video/mp4',
        path: destPath,
        isTemp: true,
      },
      compressed: true,
      originalBytes,
      outputBytes,
    };
  } finally {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
    if (inputBlobUrl) {
      URL.revokeObjectURL(inputBlobUrl);
    }
    try {
      await listener?.remove();
    } catch {
      // ignore
    }
  }
}

/**
 * Charge une source vidéo en Blob prêt pour l'upload.
 * Web : le File est déjà en mémoire (ou blob URL après compression).
 * Natif : lecture via le pont HTTP local (convertFileSrc + fetch), SANS base64.
 * @param {VideoSource} source
 * @returns {Promise<Blob>}
 */
export async function resolveUploadBlob(source) {
  if (source.file instanceof Blob) {
    return source.file;
  }
  if (!source.path) {
    throw new Error('Vidéo introuvable pour l’envoi.');
  }

  if (source.path.startsWith('blob:') || source.path.startsWith('data:')) {
    const response = await fetch(source.path);
    if (!response.ok) {
      throw new Error('Vidéo compressée introuvable.');
    }
    return response.blob();
  }

  const uri = toNativeUri(source.path);
  try {
    const webUrl = Capacitor.convertFileSrc(uri);
    const response = await fetch(webUrl);
    if (response.ok) {
      const blob = await response.blob();
      if (blob.size > 0) {
        return blob;
      }
    }
  } catch (error) {
    console.warn('[compressVideo] convertFileSrc read failed, fallback to Filesystem', error);
  }

  const pathForFs = uri.replace(/^file:\/\//, '');
  const { data } = await Filesystem.readFile({ path: pathForFs });
  return base64ToBlob(String(data), source.type || 'video/mp4');
}

/**
 * Supprime un fichier temporaire natif (résultat de compression) après upload.
 * @param {VideoSource} source
 * @returns {Promise<void>}
 */
export async function cleanupSource(source) {
  if (source?.path?.startsWith('blob:')) {
    try {
      URL.revokeObjectURL(source.path);
    } catch {
      // ignore
    }
    return;
  }
  if (source?.isTemp && source.path) {
    await safeDeleteAbsolutePath(source.path);
  }
}

/**
 * @param {number} bytes
 * @returns {string}
 */
export function formatMb(bytes) {
  const mb = bytes / (1024 * 1024);
  if (mb < 10) {
    return `${mb.toFixed(1)} Mo`;
  }
  return `${Math.round(mb)} Mo`;
}

/**
 * @param {string|undefined} path
 * @returns {string|undefined}
 */
function toNativeUri(path) {
  if (!path) {
    return path;
  }
  if (path.startsWith('file://') || path.startsWith('content://')) {
    return path;
  }
  if (path.startsWith('/')) {
    return `file://${path}`;
  }
  return path;
}

/**
 * @param {string} absolutePath
 * @returns {Promise<number>}
 */
async function nativeFileSize(absolutePath) {
  const uri = toNativeUri(absolutePath);
  try {
    const stat = await Filesystem.stat({ path: uri });
    if (typeof stat.size === 'number' && stat.size > 0) {
      return stat.size;
    }
  } catch {
    // ignore, fallback ci-dessous
  }
  try {
    const webUrl = Capacitor.convertFileSrc(uri);
    const response = await fetch(webUrl);
    if (response.ok) {
      const blob = await response.blob();
      return blob.size;
    }
  } catch {
    // ignore
  }
  return 0;
}

/**
 * @param {string} base64
 * @param {string} mime
 * @returns {Blob}
 */
function base64ToBlob(base64, mime) {
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i += 1) {
    bytes[i] = binary.charCodeAt(i);
  }
  return new Blob([bytes], { type: mime });
}

/**
 * @param {string} absolutePath
 * @returns {Promise<void>}
 */
async function safeDeleteAbsolutePath(absolutePath) {
  try {
    const pathForFs = toNativeUri(absolutePath)?.replace(/^file:\/\//, '') ?? absolutePath;
    await Filesystem.deleteFile({ path: pathForFs });
  } catch {
    // ignore
  }
}
