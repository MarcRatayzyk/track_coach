import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

const androidDir = join(process.cwd(), 'android');
const gradlew = join(androidDir, process.platform === 'win32' ? 'gradlew.bat' : 'gradlew');

const javaHomeCandidates = [
    process.env.JAVA_HOME,
    'C:\\Program Files\\Android\\Android Studio\\jbr',
    'C:\\Program Files\\Android\\Android Studio\\jbr\\Contents\\Home',
].filter(Boolean);

const env = { ...process.env };

if (!env.JAVA_HOME) {
    for (const candidate of javaHomeCandidates) {
        if (existsSync(join(candidate, 'bin', process.platform === 'win32' ? 'java.exe' : 'java'))) {
            env.JAVA_HOME = candidate;
            break;
        }
    }
}

if (!env.JAVA_HOME) {
    console.error(
        'JAVA_HOME is not set and Android Studio JBR was not found. Install JDK 21 or set JAVA_HOME.',
    );
    process.exit(1);
}

console.log(`Using JAVA_HOME=${env.JAVA_HOME}`);

const stop = spawnSync(gradlew, ['--stop'], { cwd: androidDir, env, shell: false });
const build = spawnSync(gradlew, ['assembleDebug'], { cwd: androidDir, env, stdio: 'inherit', shell: false });

if (build.status !== 0) {
    process.exit(build.status ?? 1);
}

console.log('\nAPK debug generated at:');
console.log('android/app/build/outputs/apk/debug/app-debug.apk');
