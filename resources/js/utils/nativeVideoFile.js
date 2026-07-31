/**
 * Copie une vidéo picker (souvent content:// / photopicker) vers le cache app,
 * pour que VideoEditor / LiTr puisse lire un vrai fichier.
 */

import { Capacitor } from '@capacitor/core';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { FilePicker } from '@capawesome/capacitor-file-picker';

/** @type {Map<string, Promise<string>>} */
const materializeInflight = new Map();

/**
 * @param {string|undefined} path
 * @returns {boolean}
 */
export function isVirtualNativePath(path) {
  if (!path || typeof path !== 'string') {
    return true;
  }
  if (path.startsWith('content://')) {
    return true;
  }
  if (path.includes('photopicker') || path.includes('/picker/')) {
    return true;
  }
  return false;
}

/**
 * @param {string|undefined} path
 * @returns {string|null}
 */
export function toFileUri(path) {
  if (!path) {
    return null;
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
 * Démarre la copie cache sans bloquer l’UI (réutilise le même Promise si déjà en cours).
 * Met à jour `source.path` une fois prêt (sans changer l’identité de l’objet).
 *
 * @param {{ path?: string, name?: string, type?: string, isTemp?: boolean }} source
 * @returns {Promise<string|null>}
 */
export function warmMaterializeNativeVideoPath(source) {
  if (!source?.path) {
    return Promise.resolve(null);
  }

  return materializeNativeVideoPath(source)
    .then((uri) => {
      if (uri && source.path !== uri) {
        source.path = uri;
        source.isTemp = true;
      }
      return uri;
    })
    .catch((error) => {
      console.warn('[warmMaterializeNativeVideoPath]', error);
      return null;
    });
}

/**
 * @param {{ path?: string, name?: string, type?: string }} source
 * @returns {Promise<string>} URI file:// lisible par VideoEditor
 */
export async function materializeNativeVideoPath(source) {
  const original = source?.path;
  if (!original) {
    throw new Error('Chemin vidéo introuvable.');
  }

  if (!isVirtualNativePath(original) && (original.startsWith('/') || original.startsWith('file://'))) {
    return toFileUri(original);
  }

  const existing = materializeInflight.get(original);
  if (existing) {
    return existing;
  }

  const pending = copyVirtualVideoToCache(source, original).finally(() => {
    materializeInflight.delete(original);
  });

  materializeInflight.set(original, pending);
  return pending;
}

/**
 * @param {{ path?: string, name?: string, type?: string }} source
 * @param {string} original
 * @returns {Promise<string>}
 */
async function copyVirtualVideoToCache(source, original) {
  const safeName = String(source.name || 'video.mp4')
    .replace(/[^a-zA-Z0-9._-]/g, '_')
    .slice(0, 80);
  const fileName = `picked-${Date.now()}-${safeName}`;

  // Crée le fichier cible dans le cache app (copie ensuite via FilePicker).
  await Filesystem.writeFile({
    path: fileName,
    directory: Directory.Cache,
    data: 'AA==',
    recursive: true,
  });

  const { uri: destUri } = await Filesystem.getUri({
    path: fileName,
    directory: Directory.Cache,
  });

  const from = original.startsWith('content://') || original.startsWith('file://')
    ? original
    : toFileUri(original);

  try {
    await FilePicker.copyFile({
      from,
      to: destUri,
      overwrite: true,
    });
  } catch (copyError) {
    console.warn('[materializeNativeVideoPath] copyFile failed, fetch fallback', copyError);
    const webUrl = Capacitor.convertFileSrc(from);
    const response = await fetch(webUrl);
    if (!response.ok) {
      throw new Error('Impossible de lire la vidéo sélectionnée.');
    }
    const blob = await response.blob();
    if (blob.size < 256) {
      throw new Error('Vidéo sélectionnée vide ou inaccessible.');
    }
    const base64 = await blobToBase64(blob);
    await Filesystem.writeFile({
      path: fileName,
      directory: Directory.Cache,
      data: base64,
      recursive: true,
    });
  }

  return destUri;
}

/**
 * @param {Blob} blob
 * @returns {Promise<string>}
 */
function blobToBase64(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      const result = String(reader.result || '');
      const comma = result.indexOf(',');
      resolve(comma >= 0 ? result.slice(comma + 1) : result);
    };
    reader.onerror = () => reject(new Error('Lecture vidéo impossible.'));
    reader.readAsDataURL(blob);
  });
}
