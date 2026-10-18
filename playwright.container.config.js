import { defineConfig, devices } from '@playwright/test';
export default defineConfig({
    testDir: './tests/e2e',
    testMatch: /.*\.spec\.(js|ts|mjs)/,
    fullyParallel: true,
    workers: 2,
    retries: 0,
    reporter: [['list']],
    outputDir: '/tmp/pw-results',
    use: {
        baseURL: 'http://localhost:3300',
        trace: 'off', screenshot: 'off', video: 'off',
        launchOptions: { args: ['--no-sandbox', '--disable-dev-shm-usage'] },
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
