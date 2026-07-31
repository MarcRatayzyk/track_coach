package com.trackcoach.videostreamtrim;

import android.net.Uri;
import android.util.Log;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

import java.io.File;

@CapacitorPlugin(name = "VideoStreamTrim")
public class VideoStreamTrimPlugin extends Plugin {
    private static final String TAG = "VideoStreamTrim";

    @PluginMethod
    public void trim(PluginCall call) {
        String path = call.getString("path");
        Integer startMs = call.getInt("startMs", 0);
        Integer endMs = call.getInt("endMs", 0);

        if (path == null || path.isEmpty()) {
            call.reject("path is required");
            return;
        }
        if (endMs == null || startMs == null || endMs <= startMs) {
            call.reject("Invalid trim range");
            return;
        }
        if (endMs - startMs < 500) {
            call.reject("Clip too short");
            return;
        }

        File inputFile = resolveInputFile(path);
        if (inputFile == null || !inputFile.canRead()) {
            call.reject("Cannot read input file: " + path);
            return;
        }

        final int start = Math.max(0, startMs);
        final int end = endMs;
        final File src = inputFile;

        getBridge()
            .execute(
                () -> {
                    try {
                        File outFile = File.createTempFile("stream_trim_", ".mp4", getContext().getCacheDir());
                        if (outFile.exists()) {
                            //noinspection ResultOfMethodCallIgnored
                            outFile.delete();
                        }

                        VideoStreamCopy.trim(src, outFile, start, end);

                        if (!outFile.exists() || outFile.length() < 1024) {
                            call.reject("Stream-copy output too small");
                            return;
                        }

                        JSObject file = new JSObject();
                        file.put("name", outFile.getName());
                        file.put("path", Uri.fromFile(outFile).toString());
                        file.put("type", "video/mp4");
                        file.put("size", outFile.length());

                        JSObject ret = new JSObject();
                        ret.put("success", true);
                        ret.put("path", file.getString("path"));
                        ret.put("name", file.getString("name"));
                        ret.put("type", file.getString("type"));
                        ret.put("size", file.getLong("size"));
                        call.resolve(ret);
                    } catch (Exception e) {
                        Log.w(TAG, "stream-copy trim failed", e);
                        call.reject(e.getMessage() != null ? e.getMessage() : "Stream-copy trim failed");
                    }
                }
            );
    }

    private File resolveInputFile(String path) {
        try {
            Uri uri = Uri.parse(path);
            String scheme = uri.getScheme();
            if (scheme == null) {
                return new File(path);
            }
            if ("file".equalsIgnoreCase(scheme)) {
                String p = uri.getPath();
                return p != null ? new File(p) : null;
            }
            // content:// not supported here — JS materializes to file:// first
            return new File(path);
        } catch (Exception e) {
            return new File(path);
        }
    }
}
