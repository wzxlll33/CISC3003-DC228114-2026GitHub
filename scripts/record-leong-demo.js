const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const ROOT = path.resolve(__dirname, '..');
const RECORDINGS_DIR = path.join(ROOT, 'storage', 'recordings');
const BASE_URL = process.env.DEMO_BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.DEMO_EMAIL || 'leong.demo@example.com';
const PASSWORD = process.env.DEMO_PASSWORD || 'password123';

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function clickIfVisible(page, selector, options = {}) {
  const locator = page.locator(selector).first();
  if (await locator.count() === 0) {
    return false;
  }
  try {
    await locator.click({ timeout: options.timeout || 2500 });
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
  try {
    await locator.fill(value, { timeout: 2500 });
    return true;
  } catch {
    return false;
  }
}

async function caption(page, text, seconds = 2.2) {
  await page.evaluate(({ text }) => {
    let el = document.getElementById('demo-caption');
    if (!el) {
      el = document.createElement('div');
      el.id = 'demo-caption';
      el.style.cssText = [
        'position: fixed',
        'left: 32px',
        'bottom: 28px',
        'z-index: 999999',
        'max-width: min(680px, calc(100vw - 64px))',
        'padding: 16px 18px',
        'border-radius: 16px',
        'background: rgba(10, 28, 47, 0.90)',
        'color: #fff',
        'font: 600 22px/1.35 system-ui, -apple-system, Segoe UI, sans-serif',
        'box-shadow: 0 18px 45px rgba(0,0,0,.28)',
        'pointer-events: none',
      ].join(';');
      document.body.appendChild(el);
    }
    el.textContent = text;
  }, { text });
  await wait(seconds * 1000);
}

async function smoothScroll(page, y, duration = 900) {
  await page.evaluate(({ y, duration }) => {
    const start = window.scrollY;
    const distance = y - start;
    const startTime = performance.now();
    const step = (now) => {
      const t = Math.min(1, (now - startTime) / duration);
      const eased = 1 - Math.pow(1 - t, 3);
      window.scrollTo(0, start + distance * eased);
      if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }, { y, duration });
  await wait(duration + 250);
}

async function chooseLanguage(page, locale) {
  const selectors = [
    `button[data-locale="${locale}"]`,
    `a[data-locale="${locale}"]`,
    `button[data-lang="${locale}"]`,
    `a[data-lang="${locale}"]`,
    `[data-locale-switch="${locale}"]`,
  ];

  for (const selector of selectors) {
    if (await clickIfVisible(page, selector, { timeout: 1200 })) {
      await wait(900);
      return true;
    }
  }

  const select = page.locator('select[name="locale"], select[data-locale-select], select[aria-label*="Language"]').first();
  if (await select.count()) {
    try {
      await select.selectOption(locale, { timeout: 1200 });
      await wait(900);
      return true;
    } catch {
      return false;
    }
  }

  return false;
}

async function login(page) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await caption(page, 'Login as a verified demo user');
  await fillIfVisible(page, 'input[name="email"]', EMAIL);
  await fillIfVisible(page, 'input[name="password"]', PASSWORD);
  await wait(600);
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    clickIfVisible(page, 'button[type="submit"], input[type="submit"]'),
  ]);
  await wait(1200);
}

async function logoutIfNeeded(page) {
  await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle' });
  await clickIfVisible(page, 'button:has-text("Logout"), button:has-text("登出"), button:has-text("Sair")', { timeout: 1500 });
  await wait(500);
}

async function recordTeamSegment(page) {
  await logoutIfNeeded(page);
  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await caption(page, 'Food and restaurant catalogues: browsing Macau dishes');
  await smoothScroll(page, 520, 900);
  await wait(900);

  await caption(page, 'Category filtering: narrow results by food type');
  const categoryClicked =
    await clickIfVisible(page, '[data-category-filter="portuguese"]') ||
    await clickIfVisible(page, '[data-category="portuguese"]') ||
    await clickIfVisible(page, 'button:has-text("Portuguese")') ||
    await clickIfVisible(page, 'button:has-text("葡")');
  if (categoryClicked) {
    await wait(1500);
  }

  await caption(page, 'Trilingual display: switch between Chinese, English and Portuguese');
  await chooseLanguage(page, 'en');
  await wait(800);
  await chooseLanguage(page, 'pt');
  await wait(800);
  await chooseLanguage(page, 'zh');

  await caption(page, 'Real-time search: keyword results update as the user types');
  const search = page.locator('#food-search, input[type="search"], input[name="q"], input[placeholder*="Search"], input[placeholder*="搜索"]').first();
  if (await search.count()) {
    await search.fill('');
    await search.type('egg tart', { delay: 95 });
    await wait(1800);
  }

  await caption(page, 'Guest favorites: save dishes before login');
  const hearts = page.locator('[data-favorite-toggle], .food-card__favorite');
  const count = await hearts.count();
  for (let i = 0; i < Math.min(2, count); i += 1) {
    await hearts.nth(i).click().catch(() => {});
    await wait(650);
  }

  await login(page);
  await page.goto(`${BASE_URL}/dashboard/favorites`, { waitUntil: 'networkidle' });
  await caption(page, 'After login, guest favorites sync to the user account');
  await wait(1800);

  await page.goto(`${BASE_URL}/explore?q=egg%20tart`, { waitUntil: 'networkidle' });
  await caption(page, 'Logged-in search history is recorded in the dashboard');
  await wait(1400);
  await page.goto(`${BASE_URL}/dashboard/search-history`, { waitUntil: 'networkidle' });
  await wait(1800);
}

async function recordIndividualSegment(page) {
  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await caption(page, 'Leong Chi Long: catalogue, search and favorites contribution', 2.8);
  await smoothScroll(page, 500, 850);

  await caption(page, 'Catalogue rendering and category filtering');
  await clickIfVisible(page, 'button:has-text("Dessert"), button:has-text("甜"), [data-category-filter="desserts"]');
  await wait(1300);
  await clickIfVisible(page, 'button:has-text("All"), button:has-text("全部"), [data-category-filter="all"]');
  await wait(900);

  await caption(page, 'Search uses debounced input and API-backed results');
  const search = page.locator('#food-search, input[type="search"], input[name="q"], input[placeholder*="Search"], input[placeholder*="搜索"]').first();
  if (await search.count()) {
    await search.fill('');
    await search.type('pork chop bun', { delay: 90 });
    await wait(1700);
    await search.fill('');
    await search.type('egg tart', { delay: 90 });
    await wait(1700);
  }

  await caption(page, 'Favorites are available from the same food card interaction');
  const hearts = page.locator('[data-favorite-toggle], .food-card__favorite');
  if (await hearts.count()) {
    await hearts.first().click().catch(() => {});
    await wait(700);
    await hearts.first().click().catch(() => {});
    await wait(700);
  }

  await page.goto(`${BASE_URL}/dashboard/search-history`, { waitUntil: 'networkidle' });
  await caption(page, 'Dashboard evidence: logged-in search history');
  await wait(1700);

  await page.goto(`${BASE_URL}/dashboard/favorites`, { waitUntil: 'networkidle' });
  await caption(page, 'Dashboard evidence: saved favorites');
  await wait(1700);
}

async function main() {
  fs.mkdirSync(RECORDINGS_DIR, { recursive: true });
  const chromeCandidates = [
    process.env.CHROME_EXECUTABLE,
    'C:\\Tools\\chrome-win64\\chrome.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  ].filter(Boolean);
  const executablePath = chromeCandidates.find((candidate) => fs.existsSync(candidate));
  const launchOptions = executablePath
    ? { headless: false, executablePath }
    : { headless: false, channel: 'chrome' };
  const browser = await chromium.launch(launchOptions);
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
    recordVideo: {
      dir: RECORDINGS_DIR,
      size: { width: 1440, height: 900 },
    },
  });
  const page = await context.newPage();
  page.setDefaultTimeout(8000);

  try {
    await recordTeamSegment(page);
    await recordIndividualSegment(page);
    await caption(page, 'End of silent recording. Add narration in post-production.', 2);
  } finally {
    await page.close();
    await context.close();
    await browser.close();
  }

  const videos = fs.readdirSync(RECORDINGS_DIR)
    .filter((name) => name.endsWith('.webm'))
    .map((name) => path.join(RECORDINGS_DIR, name))
    .sort((a, b) => fs.statSync(b).mtimeMs - fs.statSync(a).mtimeMs);

  if (videos[0]) {
    const target = path.join(RECORDINGS_DIR, `leong-demo-${Date.now()}.webm`);
    fs.renameSync(videos[0], target);
    console.log(target);
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
