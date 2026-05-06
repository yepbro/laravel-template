import { spawnSync } from 'node:child_process';

const spaUrl = 'http://localhost/spa';
const mode = process.argv[2] ?? 'spa';

if (mode !== 'spa') {
    console.error('Usage: node scripts/open-mode.mjs [spa]');
    process.exit(1);
}

function getOpenCommand(targetUrl) {
    switch (process.platform) {
        case 'darwin':
            return ['open', [targetUrl]];
        case 'win32':
            return ['cmd', ['/c', 'start', '', targetUrl]];
        default:
            return ['xdg-open', [targetUrl]];
    }
}

const [command, args] = getOpenCommand(spaUrl);

console.log(`Vue-only SPA: ${spaUrl}`);

const result = spawnSync(command, args, {
    stdio: 'ignore',
});

if (result.error) {
    console.log('Could not open a browser automatically. Open the URL manually.');
}
