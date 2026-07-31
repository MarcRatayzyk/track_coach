import { registerPlugin } from '@capacitor/core';

/**
 * @typedef {Object} StreamTrimOptions
 * @property {string} path Absolute or file:// path to a readable video file
 * @property {number} startMs Trim start in milliseconds
 * @property {number} endMs Trim end in milliseconds
 */

/**
 * @typedef {Object} StreamTrimResult
 * @property {boolean} success
 * @property {string} [path]
 * @property {string} [name]
 * @property {string} [type]
 * @property {number} [size]
 * @property {string} [message]
 */

const VideoStreamTrim = registerPlugin('VideoStreamTrim', {
  web: () => ({
    async trim() {
      return {
        success: false,
        message: 'Stream-copy trim unavailable on web',
      };
    },
  }),
});

export { VideoStreamTrim };
