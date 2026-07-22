/**
 * Compresse une vidéo côté navigateur (~720p) via MediaRecorder + canvas.
 * En cas d’échec / gain insuffisant, renvoie le fichier d’origine.
 */

const SKIP_UNDER_BYTES = 20 * 1024 * 1024;
const MIN_SAVINGS_RATIO = 0.15;
const MAX_WIDTH = 1280;
const MAX_HEIGHT = 720;
const VIDEO_BITS_PER_SECOND = 2_500_000;
const COMPRESS_TIMEOUT_MS = 8 * 60 * 1000;

/**
 * @param {File} file
 * @param {{ onProgress?: (ratio: number) => void }} [options]
 * @returns {Promise<{ file: File, compressed: boolean, originalBytes: number, outputBytes: number }>}
 */
export async function compressVideo(file, options = {}) {
  const onProgress = typeof options.onProgress === 'function' ? options.onProgress : () => {};
  const originalBytes = file.size;

  if (!(file instanceof Blob) || originalBytes < SKIP_UNDER_BYTES) {
    onProgress(1);
    return {
      file,
      compressed: false,
      originalBytes,
      outputBytes: originalBytes,
    };
  }

  if (typeof MediaRecorder === 'undefined' || typeof HTMLCanvasElement === 'undefined') {
    onProgress(1);
    return {
      file,
      compressed: false,
      originalBytes,
      outputBytes: originalBytes,
    };
  }

  try {
    const result = await compressWithMediaRecorder(file, onProgress);
    const savedEnough = result.size <= originalBytes * (1 - MIN_SAVINGS_RATIO);

    if (!savedEnough) {
      onProgress(1);
      return {
        file,
        compressed: false,
        originalBytes,
        outputBytes: originalBytes,
      };
    }

    const extension = extensionForMime(result.type);
    const baseName = (file.name || 'video').replace(/\.[^.]+$/, '');
    const compressedFile = new File([result], `${baseName}-720p.${extension}`, {
      type: result.type || 'video/webm',
      lastModified: Date.now(),
    });

    onProgress(1);
    return {
      file: compressedFile,
      compressed: true,
      originalBytes,
      outputBytes: compressedFile.size,
    };
  } catch (error) {
    console.warn('[compressVideo] fallback to original', error);
    onProgress(1);
    return {
      file,
      compressed: false,
      originalBytes,
      outputBytes: originalBytes,
    };
  }
}

/**
 * @param {File[]} files
 * @param {{ onFileProgress?: (index: number, ratio: number) => void, onStatus?: (message: string) => void }} [options]
 * @returns {Promise<File[]>}
 */
export async function compressVideos(files, options = {}) {
  const list = Array.from(files || []);
  const output = [];

  for (let index = 0; index < list.length; index += 1) {
    const file = list[index];
    options.onStatus?.(`Compression ${index + 1}/${list.length}…`);
    const result = await compressVideo(file, {
      onProgress: (ratio) => options.onFileProgress?.(index, ratio),
    });
    output.push(result.file);
    if (result.compressed) {
      options.onStatus?.(
        `Vidéo ${index + 1} : ${formatMb(result.originalBytes)} → ${formatMb(result.outputBytes)}`,
      );
    }
  }

  return output;
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
 * @param {File} file
 * @param {(ratio: number) => void} onProgress
 * @returns {Promise<Blob>}
 */
function compressWithMediaRecorder(file, onProgress) {
  return new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const video = document.createElement('video');
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.src = objectUrl;

    let settled = false;
    let rafId = 0;
    let recorder = null;
    let timeoutId = 0;

    const cleanup = () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      if (rafId) {
        cancelAnimationFrame(rafId);
      }
      try {
        video.pause();
      } catch {
        // ignore
      }
      video.removeAttribute('src');
      video.load();
      URL.revokeObjectURL(objectUrl);
    };

    const fail = (error) => {
      if (settled) {
        return;
      }
      settled = true;
      cleanup();
      reject(error instanceof Error ? error : new Error(String(error)));
    };

    const succeed = (blob) => {
      if (settled) {
        return;
      }
      settled = true;
      cleanup();
      resolve(blob);
    };

    timeoutId = window.setTimeout(() => {
      try {
        recorder?.stop();
      } catch {
        // ignore
      }
      fail(new Error('Compression timeout'));
    }, COMPRESS_TIMEOUT_MS);

    video.onerror = () => fail(new Error('Impossible de lire la vidéo pour compression'));

    video.onloadedmetadata = async () => {
      try {
        const srcW = video.videoWidth || MAX_WIDTH;
        const srcH = video.videoHeight || MAX_HEIGHT;
        const scale = Math.min(1, MAX_WIDTH / srcW, MAX_HEIGHT / srcH);
        const width = Math.max(2, Math.round((srcW * scale) / 2) * 2);
        const height = Math.max(2, Math.round((srcH * scale) / 2) * 2);

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { alpha: false });
        if (!ctx) {
          fail(new Error('Canvas indisponible'));
          return;
        }

        const stream = canvas.captureStream(30);
        const mimeType = pickRecorderMimeType();
        if (!mimeType) {
          fail(new Error('MediaRecorder non supporté pour la compression'));
          return;
        }

        const chunks = [];
        recorder = new MediaRecorder(stream, {
          mimeType,
          videoBitsPerSecond: VIDEO_BITS_PER_SECOND,
        });

        recorder.ondataavailable = (event) => {
          if (event.data && event.data.size > 0) {
            chunks.push(event.data);
          }
        };

        recorder.onerror = () => fail(new Error('Erreur MediaRecorder'));

        recorder.onstop = () => {
          stream.getTracks().forEach((track) => track.stop());
          const blob = new Blob(chunks, { type: mimeType.split(';')[0] });
          if (blob.size < 1024) {
            fail(new Error('Résultat de compression vide'));
            return;
          }
          succeed(blob);
        };

        const draw = () => {
          if (settled) {
            return;
          }
          ctx.drawImage(video, 0, 0, width, height);
          const duration = video.duration;
          if (Number.isFinite(duration) && duration > 0) {
            onProgress(Math.min(0.99, video.currentTime / duration));
          }
          rafId = requestAnimationFrame(draw);
        };

        video.onended = () => {
          if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
          }
          ctx.drawImage(video, 0, 0, width, height);
          onProgress(0.99);
          if (recorder && recorder.state !== 'inactive') {
            recorder.stop();
          }
        };

        recorder.start(250);
        draw();

        try {
          await video.play();
        } catch (error) {
          fail(error);
        }
      } catch (error) {
        fail(error);
      }
    };
  });
}

/**
 * @returns {string|null}
 */
function pickRecorderMimeType() {
  const candidates = [
    'video/webm;codecs=vp9,opus',
    'video/webm;codecs=vp8,opus',
    'video/webm;codecs=vp9',
    'video/webm;codecs=vp8',
    'video/webm',
    'video/mp4;codecs=h264,aac',
    'video/mp4',
  ];

  for (const type of candidates) {
    if (MediaRecorder.isTypeSupported(type)) {
      return type;
    }
  }

  return null;
}

/**
 * @param {string} mime
 * @returns {string}
 */
function extensionForMime(mime) {
  if ((mime || '').includes('mp4')) {
    return 'mp4';
  }
  return 'webm';
}
