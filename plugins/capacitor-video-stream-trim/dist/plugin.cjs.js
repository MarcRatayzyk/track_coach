'use strict';
Object.defineProperty(exports, '__esModule', { value: true });
const core = require('@capacitor/core');

const VideoStreamTrim = core.registerPlugin('VideoStreamTrim', {
  web: () => ({
    async trim() {
      return {
        success: false,
        message: 'Stream-copy trim unavailable on web',
      };
    },
  }),
});

exports.VideoStreamTrim = VideoStreamTrim;
