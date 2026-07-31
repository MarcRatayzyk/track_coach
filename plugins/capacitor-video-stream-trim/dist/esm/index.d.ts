export interface StreamTrimOptions {
  path: string;
  startMs: number;
  endMs: number;
}

export interface StreamTrimResult {
  success: boolean;
  path?: string;
  name?: string;
  type?: string;
  size?: number;
  message?: string;
}

export interface VideoStreamTrimPlugin {
  trim(options: StreamTrimOptions): Promise<StreamTrimResult>;
}

export declare const VideoStreamTrim: VideoStreamTrimPlugin;
