import { spawnSync } from 'node:child_process';

const mode = process.argv[2];

const modeConfig = {
    spa: {
        label: 'Vue-only SPA',
        url: 'http://localhost/spa',
    },
    islands: {
        label: 'Blade + Vue islands',
        url: 'http://localhost/islands',
    },
};

if (!mode || !(mode in modeConfig)) {
    console.error('Usage: node scripts/open-mode.mjs <spa|islands>');
    process.exit(1);
}

const { label, url } = modeConfig[mode];

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

const [command, args] = getOpenCommand(url);

console.log(`${label}: ${url}`);

const result = spawnSync(command, args, {
    stdio: 'ignore',
});

if (result.error) {
    console.log('Could not open a browser automatically. Open the URL manually.');
}
