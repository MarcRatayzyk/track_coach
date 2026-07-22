/**
 * Préparation d'une vidéo avant envoi :
 * - natif Capacitor : compression hardware (MediaCodec) DIRECTEMENT à partir du
 *   chemin de fichier fourni par le picker natif. Aucun passage par base64 :
 *   l'ancienne approche encodait tout le fichier en base64 en RAM (+33 %), ce qui
 *   était très lent et pouvait faire planter l'app sur les grosses vidéos.
 * - web : aucun ré-encodage (le ré-encodage MediaRecorder se faisait en temps
 *   réel, donc au moins aussi long que la durée de la vidéo). On uploade
 *   directement le fichier d'origine, borné par une limite de taille.
 *
 * En cas d'échec / gain insuffisant, on renvoie la source d'origine.
 */

import { Capacitor } from '@capacitor/core';
import { Filesystem } from '@capacitor/filesystem';
import { NativeVideoCompressor } from 'capacitor-native-video-compressor';

const SKIP_UNDER_BYTES = 20 * 1024 * 1024;
const MIN_SAVINGS_RATIO = 0.15;
const COMPRESS_TIMEOUT_MS = 4 * 60 * 1000;
const NATIVE_QUALITY = 'MEDIUM';

/**
 * @typedef {Object} VideoSource
 * @property {string} name
 * @property {number} size
 * @property {string} type
 * @property {File} [file]      Fichier web (input HTML).
 * @property {string} [path]    Chemin natif renvoyé par le picker / la compression.
 * @property {boolean} [isTemp] Fichier temporaire natif à supprimer après upload.
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

  // Web / PWA : pas de compression navigateur (trop lente en temps réel) -> upload direct.
  if (!Capacitor.isNativePlatform() || !source.path) {
    return passthrough();
  }

  // Petites vidéos : le gain ne vaut pas le temps de compression.
  if (originalBytes > 0 && originalBytes < SKIP_UNDER_BYTES) {
    return passthrough();
  }

  try {
    return await compressWithNativePlugin(source, onProgress);
  } catch (error) {
    console.warn('[compressVideo] native fallback to original', error);
    return passthrough();
  }
}

/**
 * @param {VideoSource} source
 * @param {(ratio: number) => void} onProgress
 * @returns {Promise<{ source: VideoSource, compressed: boolean, originalBytes: number, outputBytes: number }>}
 */
async function compressWithNativePlugin(source, onProgress) {
  const originalBytes = source.size ?? 0;
  let listener = null;
  let timeoutId = 0;

  onProgress(0.02);
  const sourceUri = toNativeUri(source.path);

  listener = await NativeVideoCompressor.addListener('onProgress', (info) => {
    if (info?.status === 'progress' && typeof info.percent === 'number') {
      const ratio = info.percent > 1 ? info.percent / 100 : info.percent;
      onProgress(Math.min(0.98, Math.max(0.02, ratio)));
    } else if (info?.status === 'started') {
      onProgress(0.05);
    }
  });

  try {
    const result = await Promise.race([
      NativeVideoCompressor.compressVideo({
        sourcePath: sourceUri,
        quality: NATIVE_QUALITY,
      }),
      new Promise((_, reject) => {
        timeoutId = window.setTimeout(
          () => reject(new Error('Compression timeout')),
          COMPRESS_TIMEOUT_MS,
        );
      }),
    ]);

    if (!result?.success || !result.destPath) {
      throw new Error('Compression native sans fichier de sortie');
    }

    const destPath = result.destPath;
    const outputBytes = await nativeFileSize(destPath);
    const savedEnough =
      outputBytes > 1024 &&
      (originalBytes <= 0 || outputBytes <= originalBytes * (1 - MIN_SAVINGS_RATIO));

    if (!savedEnough) {
      await safeDeleteAbsolutePath(destPath);
      onProgress(1);
      return { source, compressed: false, originalBytes, outputBytes: originalBytes };
    }

    const baseName = (source.name || 'video').replace(/\.[^.]+$/, '');
    onProgress(1);
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
    try {
      await listener?.remove();
    } catch {
      // ignore
    }
  }
}

/**
 * Charge une source vidéo en Blob prêt pour l'upload.
 * Web : le File est déjà en mémoire.
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
