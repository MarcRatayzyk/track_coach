package com.trackcoach.videostreamtrim;

import android.media.MediaCodec;
import android.media.MediaExtractor;
import android.media.MediaFormat;
import android.media.MediaMetadataRetriever;
import android.media.MediaMuxer;

import java.io.File;
import java.io.IOException;
import java.nio.ByteBuffer;
import java.util.HashMap;
import java.util.Map;

/**
 * Trim MP4/MOV by remuxing sample data (stream copy) — no decode/encode.
 * Adapted from AOSP Gallery2 VideoUtils approach.
 */
final class VideoStreamCopy {
    private static final int DEFAULT_BUFFER_SIZE = 1024 * 1024;

    private VideoStreamCopy() {}

    static void trim(File src, File dst, int startMs, int endMs) throws IOException {
        MediaExtractor extractor = new MediaExtractor();
        MediaMuxer muxer = null;
        try {
            extractor.setDataSource(src.getAbsolutePath());
            int trackCount = extractor.getTrackCount();
            muxer = new MediaMuxer(dst.getAbsolutePath(), MediaMuxer.OutputFormat.MUXER_OUTPUT_MPEG_4);

            Map<Integer, Integer> indexMap = new HashMap<>(trackCount);
            int bufferSize = -1;

            for (int i = 0; i < trackCount; i++) {
                MediaFormat format = extractor.getTrackFormat(i);
                String mime = format.getString(MediaFormat.KEY_MIME);
                if (mime == null) {
                    continue;
                }
                boolean select =
                    mime.startsWith("video/") || mime.startsWith("audio/");
                if (!select) {
                    continue;
                }
                extractor.selectTrack(i);
                int dstIndex = muxer.addTrack(format);
                indexMap.put(i, dstIndex);
                if (format.containsKey(MediaFormat.KEY_MAX_INPUT_SIZE)) {
                    int newSize = format.getInteger(MediaFormat.KEY_MAX_INPUT_SIZE);
                    bufferSize = Math.max(bufferSize, newSize);
                }
            }

            if (indexMap.isEmpty()) {
                throw new IOException("No audio/video tracks to copy");
            }
            if (bufferSize <= 0) {
                bufferSize = DEFAULT_BUFFER_SIZE;
            }

            // Preserve rotation when available.
            MediaMetadataRetriever retriever = new MediaMetadataRetriever();
            try {
                retriever.setDataSource(src.getAbsolutePath());
                String degreesString =
                    retriever.extractMetadata(MediaMetadataRetriever.METADATA_KEY_VIDEO_ROTATION);
                if (degreesString != null) {
                    int degrees = Integer.parseInt(degreesString);
                    if (degrees >= 0) {
                        muxer.setOrientationHint(degrees);
                    }
                }
            } catch (Exception ignored) {
                // rotation optional
            } finally {
                try {
                    retriever.release();
                } catch (Exception ignored) {
                    // ignore
                }
            }

            if (startMs > 0) {
                extractor.seekTo((long) startMs * 1000L, MediaExtractor.SEEK_TO_CLOSEST_SYNC);
            }

            long startUs = (long) startMs * 1000L;
            long endUs = (long) endMs * 1000L;
            long firstSampleTime = -1L;
            ByteBuffer buffer = ByteBuffer.allocate(bufferSize);
            MediaCodec.BufferInfo info = new MediaCodec.BufferInfo();
            muxer.start();

            while (true) {
                info.offset = 0;
                info.size = extractor.readSampleData(buffer, 0);
                if (info.size < 0) {
                    break;
                }

                long sampleTime = extractor.getSampleTime();
                if (sampleTime < startUs) {
                    extractor.advance();
                    continue;
                }
                if (sampleTime > endUs) {
                    break;
                }

                if (firstSampleTime < 0L) {
                    firstSampleTime = sampleTime;
                }

                info.presentationTimeUs = Math.max(0L, sampleTime - firstSampleTime);
                info.flags = extractor.getSampleFlags();
                int trackIndex = extractor.getSampleTrackIndex();
                Integer dstTrack = indexMap.get(trackIndex);
                if (dstTrack != null) {
                    muxer.writeSampleData(dstTrack, buffer, info);
                }
                extractor.advance();
            }

            muxer.stop();
        } finally {
            try {
                extractor.release();
            } catch (Exception ignored) {
                // ignore
            }
            if (muxer != null) {
                try {
                    muxer.release();
                } catch (Exception ignored) {
                    // ignore
                }
            }
        }
    }
}
