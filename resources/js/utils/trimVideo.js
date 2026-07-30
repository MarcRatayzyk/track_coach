/**
 * Rognage vidéo côté client avant compression / envoi.
 * 1) MediaRecorder (rapide) si MP4 supporté
 * 2) FFmpeg.wasm : stream copy, puis réencodage léger
 */

import { FFmpeg } from '@ffmpeg/ffmpeg';
import { fetchFile, toBlobURL } from '@ffmpeg/util';
import { Capacitor } from '@capacitor/core';
import { resolveUploadBlob } from './compressVideo';

const FFMPEG_CORE_BASE = `${typeof window !== 'undefined' ? window.location.origin : ''}/ffmpeg`;
const TRIM_TIMEOUT_MS = 4 * 60 * 1000;
const MIN_DURATION_SEC = 0.5;

/** @type {FFmpeg|null} */
let ffmpegInstance = null;
/** @type {Promise<FFmpeg>|null} */
let ffmpegReady = null;

/**
 * Précharge FFmpeg pendant que l'athlète ajuste le clip (évite l'attente au confirm).
 * @returns {Promise<void>}
 */
export function preloadTrimEngine() {
  // FFmpeg.wasm dans Capacitor provoque souvent un OOM / crash.
  if (Capacitor.isNativePlatform()) {
    return Promise.resolve();
  }
  return ensureFfmpeg().then(() => undefined).catch(() => undefined);
}

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
 * @param {{ startSec: number, endSec: number, onProgress?: (ratio: number) => void, videoEl?: HTMLVideoElement|null }} options
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

  // Natif : ne pas passer par MediaRecorder (captureStream = images hachées / frames perdues
  // dans le WebView Capacitor). FFmpeg.wasm est aussi exclus (OOM).
  if (Capacitor.isNativePlatform()) {
    throw new Error(
      'Rognage indisponible sur mobile. Utilisez « Envoyer toute la vidéo », ou rognez avant l’import.',
    );
  }

  // Chemin rapide web : enregistrement de la sélection (MP4).
  if (options.videoEl instanceof HTMLVideoElement) {
    try {
      const recorded = await trimViaPlayback(options.videoEl, range, onProgress);
      if (recorded) {
        return {
          source: recorded,
          trimmed: true,
          originalBytes: originalBytes || recorded.size,
          outputBytes: recorded.size,
        };
      }
    } catch (error) {
      console.warn('[trimVideo] MediaRecorder indisponible', error);
    }
  }

  return trimWithFfmpeg(source, range, onProgress, originalBytes);
}

/**
 * @param {HTMLVideoElement} videoEl
 * @param {{ startSec: number, endSec: number }} range
 * @param {(ratio: number) => void} onProgress
 * @returns {Promise<{ name: string, size: number, type: string, file: File }|null>}
 */
async function trimViaPlayback(videoEl, range, onProgress) {
  const capture =
    typeof videoEl.captureStream === 'function'
      ? videoEl.captureStream.bind(videoEl)
      : typeof videoEl.mozCaptureStream === 'function'
        ? videoEl.mozCaptureStream.bind(videoEl)
        : null;

  if (!capture) {
    return null;
  }

  const mime = pickRecorderMime();
  if (!mime) {
    return null;
  }

  const duration = Math.max(0.5, range.endSec - range.startSec);
  const prevMuted = videoEl.muted;
  const prevVolume = videoEl.volume;
  const prevRate = videoEl.playbackRate;

  const chunks = [];
  let stream;
  /** @type {MediaRecorder|null} */
  let recorder = null;

  try {
    videoEl.pause();
    videoEl.playbackRate = 1;
    // Nécessaire pour capturer l'audio sur la plupart des navigateurs.
    videoEl.muted = false;
    videoEl.volume = 0.001;

    await seekVideo(videoEl, range.startSec);
    onProgress(0.08);

    stream = capture();
    if (!stream || stream.getVideoTracks().length === 0) {
      return null;
    }

    recorder = new MediaRecorder(stream, {
      mimeType: mime,
      videoBitsPerSecond: 8_000_000,
      audioBitsPerSecond: 128_000,
    });

    recorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        chunks.push(event.data);
      }
    };

    const stopped = new Promise((resolve, reject) => {
      recorder.onstop = () => resolve(undefined);
      recorder.onerror = () => reject(new Error('Échec enregistrement clip'));
    });

    // Sans timeslice : un seul segment bien formé (évite les coupures / GOP cassés).
    recorder.start();
    await videoEl.play();

    await waitUntilTime(videoEl, range.endSec, duration, (ratio) => {
      onProgress(0.08 + ratio * 0.85);
    });

    videoEl.pause();
    if (recorder.state !== 'inactive') {
      recorder.stop();
    }
    await stopped;

    const blob = new Blob(chunks, { type: mime.split(';')[0] });
    if (blob.size < 1024) {
      return null;
    }

    const ext = mime.includes('mp4') ? 'mp4' : 'webm';
    const type = mime.includes('mp4') ? 'video/mp4' : 'video/webm';
    const file = new File([blob], `clip-${Date.now()}.${ext}`, { type });
    onProgress(1);

    return {
      name: file.name,
      size: file.size,
      type,
      file,
    };
  } finally {
    try {
      videoEl.pause();
    } catch {
      // ignore
    }
    videoEl.muted = prevMuted;
    videoEl.volume = prevVolume;
    videoEl.playbackRate = prevRate;
    stream?.getTracks?.().forEach((track) => {
      try {
        track.stop();
      } catch {
        // ignore
      }
    });
  }
}

/**
 * @returns {string|null} mime MP4 uniquement (compat lecture coach iOS)
 */
function pickRecorderMime() {
  if (typeof MediaRecorder === 'undefined' || typeof MediaRecorder.isTypeSupported !== 'function') {
    return null;
  }
  const candidates = [
    'video/mp4;codecs=avc1.42E01E,mp4a.40.2',
    'video/mp4;codecs=avc1.4D401E,mp4a.40.2',
    'video/mp4',
  ];
  return candidates.find((type) => MediaRecorder.isTypeSupported(type)) || null;
}

/**
 * @param {HTMLVideoElement} video
 * @param {number} time
 * @returns {Promise<void>}
 */
function seekVideo(video, time) {
  return new Promise((resolve, reject) => {
    const onSeeked = () => {
      cleanup();
      resolve();
    };
    const onError = () => {
      cleanup();
      reject(new Error('Seek impossible'));
    };
    const cleanup = () => {
      video.removeEventListener('seeked', onSeeked);
      video.removeEventListener('error', onError);
    };
    video.addEventListener('seeked', onSeeked, { once: true });
    video.addEventListener('error', onError, { once: true });
    try {
      video.currentTime = Math.max(0, time);
    } catch (error) {
      cleanup();
      reject(error);
    }
    // Certains WebViews ne déclenchent pas seeked si déjà à la position.
    window.setTimeout(() => {
      if (Math.abs(video.currentTime - time) < 0.35) {
        cleanup();
        resolve();
      }
    }, 700);
  });
}

/**
 * @param {HTMLVideoElement} video
 * @param {number} endSec
 * @param {number} duration
 * @param {(ratio: number) => void} onProgress
 * @returns {Promise<void>}
 */
function waitUntilTime(video, endSec, duration, onProgress) {
  const started = performance.now();
  const hardStopAt = started + (duration + 2) * 1000;

  return new Promise((resolve) => {
    const tick = () => {
      const t = video.currentTime;
      const ratio = Math.min(1, Math.max(0, (t - (endSec - duration)) / duration));
      onProgress(ratio);

      if (t >= endSec - 0.04 || performance.now() >= hardStopAt || video.ended) {
        resolve();
        return;
      }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });
}

/**
 * @param {{ name?: string, size?: number, type?: string, file?: Blob, path?: string }} source
 * @param {{ startSec: number, endSec: number }} range
 * @param {(ratio: number) => void} onProgress
 * @param {number} originalBytes
 */
async function trimWithFfmpeg(source, range, onProgress, originalBytes) {
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
  const clipDuration = Math.max(MIN_DURATION_SEC, range.endSec - range.startSec);

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

    onProgress(0.1);

    progressHandler = ({ progress }) => {
      if (typeof progress === 'number' && Number.isFinite(progress)) {
        onProgress(Math.min(0.95, Math.max(0.1, 0.1 + progress * 0.85)));
      }
    };
    ffmpeg.on('progress', progressHandler);

    await ffmpeg.writeFile(inputName, await fetchFile(inputUrl));
    onProgress(0.15);

    const startStr = formatFfmpegTime(range.startSec);
    const durationStr = formatFfmpegTime(clipDuration);

    let ok = await runTrim(ffmpeg, {
      inputName,
      outputName,
      startStr,
      durationStr,
      reencode: false,
    });

    if (!ok) {
      await safeDeleteFfmpegFile(ffmpeg, outputName);
      ok = await runTrim(ffmpeg, {
        inputName,
        outputName,
        startStr,
        durationStr,
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
 * @param {{ inputName: string, outputName: string, startStr: string, durationStr: string, reencode: boolean }} opts
 * @returns {Promise<boolean>}
 */
async function runTrim(ffmpeg, { inputName, outputName, startStr, durationStr, reencode }) {
  // -ss avant -i = seek rapide ; -t = durée du clip.
  const args = reencode
    ? [
        '-ss', startStr,
        '-i', inputName,
        '-t', durationStr,
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
        '-i', inputName,
        '-t', durationStr,
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
