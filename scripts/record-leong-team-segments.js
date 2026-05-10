const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { createRequire } = require('node:module');

const ROOT = path.resolve(__dirname, '..');
const PROJECT_ROOT = path.resolve(ROOT, '..');
const OUTPUT_DIR = path.join(PROJECT_ROOT, 'Team Digital Story');
const TMP_VIDEO_DIR = path.join(ROOT, 'storage', 'recordings', 'team-leong');
const VIDEO_TOOLS_ROOT = path.join(PROJECT_ROOT, '.video-tools');

const BASE_URL = process.env.DEMO_BASE_URL || 'https://food.jackie-macau.top';
const EMAIL = process.env.DEMO_EMAIL || 'demo@example.com';
const PASSWORD = process.env.DEMO_PASSWORD || 'password123';
const FOOD_ID = process.env.DEMO_FOOD_ID || '8';
const RESTAURANT_ID = process.env.DEMO_RESTAURANT_ID || '3';
const VIEWPORT = { width: 1440, height: 900 };

const runtimeRequire = createRequire('C:/Users/Jackie_Laptop/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/package.json');
const { chromium } = runtimeRequire('playwright');

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function resolveBrowserExecutable() {
    const candidates = [
        process.env.CHROME_EXECUTABLE,
        'C:\\Tools\\chrome-win64\\chrome.exe',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ].filter(Boolean);

    return candidates.find((candidate) => fs.existsSync(candidate));
}

function resolveFfmpeg() {
    if (process.env.FFMPEG_PATH && fs.existsSync(process.env.FFMPEG_PATH)) {
        return process.env.FFMPEG_PATH;
    }

    try {
        const toolsRequire = createRequire(path.join(VIDEO_TOOLS_ROOT, 'package.json'));
        const ffmpeg = toolsRequire('@ffmpeg-installer/ffmpeg');
        return ffmpeg?.path && fs.existsSync(ffmpeg.path) ? ffmpeg.path : '';
    } catch {
        return '';
    }
}

function urlFor(route) {
    return new URL(route, BASE_URL).toString();
}

async function goto(page, route) {
    await page.goto(urlFor(route), { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForLoadState('networkidle', { timeout: 9000 }).catch(() => {});
    await wait(650);
}

async function clickIfVisible(page, selector, options = {}) {
    const locator = page.locator(selector).first();

    if (await locator.count() === 0) {
        return false;
    }

    try {
        await locator.click({ timeout: options.timeout || 3000 });
        await wait(options.after || 350);
        return true;
    } catch {
        return false;
    }
}

async function fillIfVisible(page, selector, value) {
    const locator = page.locator(selector).first();

    if (await locator.count() === 0) {
        return false;
    }

    await locator.fill(value, { timeout: 5000 });
    return true;
}

async function showCue(page, title, subtitle = '', seconds = 1.8) {
    await page.evaluate(({ title, subtitle }) => {
        let el = document.getElementById('team-demo-cue');

        if (!el) {
            el = document.createElement('div');
            el.id = 'team-demo-cue';
            el.style.cssText = [
                'position: fixed',
                'left: 28px',
                'bottom: 28px',
                'z-index: 999999',
                'max-width: min(620px, calc(100vw - 56px))',
                'padding: 14px 16px',
                'border-radius: 12px',
                'background: rgba(8, 22, 38, 0.88)',
                'color: #fff',
                'font-family: system-ui, -apple-system, Segoe UI, sans-serif',
                'box-shadow: 0 18px 44px rgba(0,0,0,.26)',
                'pointer-events: none',
            ].join(';');
            document.body.appendChild(el);
        }

        el.innerHTML = `
            <div style="font-size: 20px; font-weight: 760; line-height: 1.25;">${title}</div>
            ${subtitle ? `<div style="font-size: 14px; margin-top: 5px; opacity: .9; line-height: 1.35;">${subtitle}</div>` : ''}
        `;
    }, { title, subtitle });

    await wait(seconds * 1000);
}

async function hideCue(page) {
    await page.evaluate(() => document.getElementById('team-demo-cue')?.remove()).catch(() => {});
}

async function highlight(page, selector, seconds = 1.5) {
    await page.evaluate(({ selector }) => {
        const el = document.querySelector(selector);

        if (!el) {
            return;
        }

        el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        el.dataset.demoPreviousOutline = el.style.outline || '';
        el.dataset.demoPreviousShadow = el.style.boxShadow || '';
        el.style.outline = '4px solid #f2b544';
        el.style.boxShadow = '0 0 0 8px rgba(242,181,68,.24)';
    }, { selector }).catch(() => {});

    await wait(seconds * 1000);

    await page.evaluate(({ selector }) => {
        const el = document.querySelector(selector);

        if (!el) {
            return;
        }

        el.style.outline = el.dataset.demoPreviousOutline || '';
        el.style.boxShadow = el.dataset.demoPreviousShadow || '';
        delete el.dataset.demoPreviousOutline;
        delete el.dataset.demoPreviousShadow;
    }, { selector }).catch(() => {});
}

async function openTools(page) {
    await clickIfVisible(page, '[data-toggle-tools]', { after: 550 });
}

async function switchLocale(page, locale, label) {
    await clickIfVisible(page, '[data-mobile-locale-toggle]', { after: 250 });
    const button = page.locator(`[data-locale-switch][data-locale="${locale}"]`).first();

    if (await button.count() === 0) {
        return;
    }

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 12000 }).catch(() => {}),
        button.click({ timeout: 3000 }),
    ]);
    await page.waitForLoadState('networkidle', { timeout: 9000 }).catch(() => {});
    await wait(700);
    await showCue(page, label, 'The same Explore interface reloads with localized labels and content.', 1.25);
}

async function signIn(page) {
    await goto(page, '/login');

    if (await page.locator('input[name="email"]').count() === 0) {
        return;
    }

    await fillIfVisible(page, 'input[name="email"]', EMAIL);
    await fillIfVisible(page, 'input[name="password"]', PASSWORD);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {}),
        page.locator('button[type="submit"], input[type="submit"]').first().click({ timeout: 5000 }),
    ]);
    await page.waitForLoadState('networkidle', { timeout: 9000 }).catch(() => {});
    await wait(800);
}

async function removeFoodFavoriteIfNeeded(page) {
    await goto(page, `/food/${FOOD_ID}`);
    const button = page.locator('[data-favorite-toggle]').first();

    if (await button.count() === 0) {
        return;
    }

    const favorited = await button.getAttribute('data-favorited');

    if (favorited === 'true') {
        await button.click();
        await wait(1200);
    }
}

async function prepareLoggedInState(browser) {
    const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1 });
    await context.addInitScript(() => {
        window.localStorage.removeItem('taste-of-macau:guest-favorites');
    });

    const page = await context.newPage();
    page.setDefaultTimeout(10000);
    await signIn(page);
    await removeFoodFavoriteIfNeeded(page);
    const state = await context.storageState();
    await context.close();
    return state;
}

async function segment4Catalogues(page) {
    await goto(page, '/explore');
    await showCue(page, 'Segment 4: Food and restaurant catalogues', 'Logged-in Explore page: 16 restaurants are rendered from 49 food records.', 3.0);
    await highlight(page, '[data-results-count]', 1.8);
    await highlight(page, '[data-restaurant-card]', 2.0);

    await openTools(page);
    await showCue(page, 'Category filtering', 'Open the filter panel, choose Desserts, then apply.', 2.0);
    await clickIfVisible(page, '[data-open-filters]', { after: 450 });
    await clickIfVisible(page, '[data-filter-category="desserts"]', { after: 500 });
    await clickIfVisible(page, '[data-apply-filters]', { after: 950 });
    await showCue(page, 'Desserts filter applied', 'The result count changes from 16 to 7 matching restaurant spots.', 3.0);
    await highlight(page, '[data-results-count]', 2.0);

    await showCue(page, 'Trilingual display', 'Switch the same Explore workflow across Chinese, English and Portuguese.', 2.0);
    await switchLocale(page, 'zh', 'Chinese interface');
    await switchLocale(page, 'en', 'English interface');
    await switchLocale(page, 'pt', 'Portuguese interface');
    await switchLocale(page, 'en', 'Back to English for the next segment');
    await wait(1800);
    await hideCue(page);
}

async function segment5SearchAndHistory(page) {
    await goto(page, '/explore');
    await showCue(page, 'Segment 5: Search and history functions', 'Search is live while typing, then Enter records the query in Dashboard history.', 3.0);

    const search = page.locator('#restaurant-search').first();
    await search.fill('');
    await search.type('Lord', { delay: 110 });
    await wait(1600);
    await highlight(page, '#restaurant-search', 1.2);
    await search.press('Enter');
    await wait(1600);
    await showCue(page, 'Search committed', 'The query "Lord" returns Lord Stow\'s Bakery and is logged after Enter.', 2.6);

    await goto(page, '/dashboard/search-history');
    await showCue(page, 'Dashboard Search History', 'The confirmed query appears with its result count and rerun link.', 3.0);
    await highlight(page, '.dashboard-table', 2.0);

    const lordLink = page.locator('.dashboard-table__strong-link', { hasText: 'Lord' }).first();
    if (await lordLink.count()) {
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 12000 }).catch(() => {}),
            lordLink.click({ timeout: 3000 }),
        ]);
        await page.waitForLoadState('networkidle', { timeout: 9000 }).catch(() => {});
        await wait(900);
        await showCue(page, 'Rerun previous search', 'Clicking a past query opens Explore with the same search again.', 2.5);
    }

    await goto(page, `/food/${FOOD_ID}`);
    await showCue(page, 'Food browse history', 'A logged-in food detail visit is recorded for the dashboard.', 2.2);
    await goto(page, `/restaurant/${RESTAURANT_ID}`);
    await showCue(page, 'Restaurant browse history', 'A restaurant detail visit is recorded in the same history area.', 2.2);
    await goto(page, '/dashboard/browse-history');
    await showCue(page, 'Dashboard Browse History', 'Food and restaurant visits appear together with deduplication.', 3.0);
    await highlight(page, '.dashboard-browse-history__grid, .dashboard-empty', 2.0);
    await hideCue(page);
}

async function logout(page) {
    await goto(page, '/explore');

    const loggedIn = await page.evaluate(() => (
        window.__APP_CONTEXT__?.isLoggedIn === true
        || window.__TASTE_OF_MACAU__?.isLoggedIn === true
    )).catch(() => false);

    if (loggedIn) {
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 12000 }).catch(() => {}),
            page.evaluate(() => {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.getAttribute('value')
                    || '';
                const form = document.createElement('form');
                const input = document.createElement('input');

                form.method = 'post';
                form.action = '/logout';
                input.type = 'hidden';
                input.name = '_token';
                input.value = token;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }),
        ]);
        await page.waitForLoadState('networkidle', { timeout: 9000 }).catch(() => {});
        await wait(700);
    }

    await page.evaluate(() => window.localStorage.removeItem('taste-of-macau:guest-favorites')).catch(() => {});
}

async function segment9FavoritesMerge(page) {
    await logout(page);
    await showCue(page, 'Segment 9: Favourites and guest merge', 'Start as a guest, save a food item locally, then log in to sync it.', 3.0);

    await goto(page, `/food/${FOOD_ID}`);
    await showCue(page, 'Guest favorite on food detail', 'Portuguese Egg Tart is a food-level favorite, so the detail page is the correct test surface.', 2.5);
    await clickIfVisible(page, '[data-favorite-toggle]', { after: 850 });

    const localState = await page.evaluate(() => window.localStorage.getItem('taste-of-macau:guest-favorites') || '[]');
    await showCue(page, 'LocalStorage proof', `taste-of-macau:guest-favorites = ${localState}`, 3.0);

    await goto(page, '/explore?favorites=1');
    await showCue(page, 'Guest favorites view', 'The saved food affects discovery by showing related restaurant cards before login.', 3.0);
    await highlight(page, '[data-restaurant-card]', 2.0);

    await signIn(page);
    await showCue(page, 'Login merge starts', 'After login, the app posts guest favorite IDs to the sync API.', 2.8);
    await goto(page, '/dashboard/favorites');
    await showCue(page, 'Dashboard Favorites after sync', 'Portuguese Egg Tart is now stored as an account favorite without duplicates.', 3.4);
    await highlight(page, '[data-dashboard-favorite-card], .dashboard-favorites__grid, .dashboard-empty', 2.0);
    await hideCue(page);
}

async function recordOne(browser, key, task) {
    const storageState = task.loggedIn ? await prepareLoggedInState(browser) : undefined;
    const context = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 1,
        ...(storageState ? { storageState } : {}),
        recordVideo: {
            dir: TMP_VIDEO_DIR,
            size: VIEWPORT,
        },
    });

    await context.addInitScript(() => {
        window.localStorage.removeItem('taste-of-macau:guest-favorites');
    });

    const page = await context.newPage();
    page.setDefaultTimeout(12000);
    const video = page.video();

    try {
        await task.run(page);
    } finally {
        await page.close();
        await context.close();
    }

    const webmPath = await video.path();
    const finalWebm = path.join(OUTPUT_DIR, `${task.outputBase}.webm`);
    fs.copyFileSync(webmPath, finalWebm);

    const ffmpeg = resolveFfmpeg();
    const finalMp4 = path.join(OUTPUT_DIR, `${task.outputBase}.mp4`);

    if (ffmpeg) {
        execFileSync(ffmpeg, [
            '-y',
            '-i', finalWebm,
            '-an',
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '22',
            '-pix_fmt', 'yuv420p',
            finalMp4,
        ], { stdio: 'inherit' });
        console.log(`${key}: ${finalMp4}`);
        return finalMp4;
    }

    console.log(`${key}: ${finalWebm}`);
    return finalWebm;
}

async function main() {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    fs.mkdirSync(TMP_VIDEO_DIR, { recursive: true });

    const executablePath = resolveBrowserExecutable();
    const browser = await chromium.launch({
        headless: true,
        ...(executablePath ? { executablePath } : {}),
    });

    const tasks = {
        segment4: {
            outputBase: 'Leong_Team_Segment04_Catalogues_Silent',
            loggedIn: true,
            run: segment4Catalogues,
        },
        segment5: {
            outputBase: 'Leong_Team_Segment05_Search_History_Silent',
            loggedIn: true,
            run: segment5SearchAndHistory,
        },
        segment9: {
            outputBase: 'Leong_Team_Segment09_Favourites_Guest_Merge_Silent',
            loggedIn: true,
            run: segment9FavoritesMerge,
        },
    };

    try {
        const requested = process.argv.slice(2).map((item) => item.toLowerCase());
        const keys = requested.length === 0 || requested.includes('all')
            ? Object.keys(tasks)
            : requested;

        for (const key of keys) {
            const task = tasks[key];

            if (!task) {
                throw new Error(`Unknown segment "${key}". Use segment4, segment5, segment9, or all.`);
            }

            await recordOne(browser, key, task);
        }
    } finally {
        await browser.close();
    }
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
