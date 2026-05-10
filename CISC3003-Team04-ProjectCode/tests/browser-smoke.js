const fs = require('fs');
const path = require('path');
const assert = require('assert');
const { chromium } = require('playwright');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const CHROME_PATH = process.env.CHROME_PATH || 'C:/Tools/chrome-win64/chrome.exe';
const OUT_DIR = process.env.SCREENSHOTS_DIR || path.join(__dirname, 'screenshots');

fs.mkdirSync(OUT_DIR, { recursive: true });

async function login(page) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'demo@example.com');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  assert(page.url().includes('/explore'), `expected login redirect to /explore, got ${page.url()}`);
}

function hasHorizontalOverflow(metrics) {
  return metrics.scrollWidth > metrics.clientWidth + 1;
}

async function collectExploreMetrics(page) {
  return page.evaluate(() => {
    const rect = (selector) => {
      const element = document.querySelector(selector);
      if (!element) {
        return null;
      }

      const box = element.getBoundingClientRect();

      return {
        x: Math.round(box.x),
        y: Math.round(box.y),
        width: Math.round(box.width),
        height: Math.round(box.height),
      };
    };

    const visibleHiddenElements = [...document.querySelectorAll('[hidden]')]
      .filter((element) => getComputedStyle(element).display !== 'none')
      .map((element) => element.className || element.tagName);

    const languageButtons = [...document.querySelectorAll('.site-nav__locale')].map((button) => rectFor(button));

    function rectFor(element) {
      const box = element.getBoundingClientRect();

      return {
        text: (element.textContent || '').trim(),
        right: Math.round(box.right),
        left: Math.round(box.left),
        width: Math.round(box.width),
      };
    }

    return {
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      hasLeaflet: Boolean(window.L),
      hasMapInstance: Boolean(window.macauMap),
      mapChildren: document.querySelector('#map-container')?.children.length || 0,
      markerCount: document.querySelectorAll('.map-marker-wrapper, .leaflet-marker-icon').length,
      restaurantCards: document.querySelectorAll('[data-restaurant-card]').length,
      hiddenStillVisible: visibleHiddenElements,
      exploreLayout: rect('.explore-page__layout'),
      map: rect('#map-container'),
      panel: rect('.explore-page__panel'),
      languageButtons,
    };
  });
}

async function collectRestaurantDetailMetrics(page) {
  return page.evaluate(() => {
    const visibleHiddenElements = [...document.querySelectorAll('[hidden]')]
      .filter((element) => getComputedStyle(element).display !== 'none')
      .map((element) => element.className || element.tagName);
    const map = document.querySelector('#restaurant-detail-map');
    const mapBox = map?.getBoundingClientRect();

    return {
      title: document.querySelector('.restaurant-detail h1')?.textContent?.trim() || '',
      hasLeaflet: Boolean(window.L),
      mapChildren: map?.children.length || 0,
      mapHeight: Math.round(mapBox?.height || 0),
      markerCount: document.querySelectorAll('#restaurant-detail-map .map-marker-wrapper, #restaurant-detail-map .leaflet-marker-icon').length,
      reviewCards: document.querySelectorAll('[data-review-list] .review-card').length,
      ratingRows: document.querySelectorAll('[data-rating-row]').length,
      starButtons: document.querySelectorAll('[data-star-value]').length,
      reviewFormExists: Boolean(document.querySelector('[data-review-form]')),
      hiddenStillVisible: visibleHiddenElements,
    };
  });
}

async function main() {
  const browser = await chromium.launch({
    headless: true,
    executablePath: CHROME_PATH,
  });

  const desktopContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const desktopPage = await desktopContext.newPage();
  const failedLocalAssets = [];

  desktopPage.on('response', (response) => {
    const url = response.url();

    if (url.startsWith(BASE_URL) && response.status() >= 400 && !url.includes('/favicon.ico')) {
      failedLocalAssets.push(`${response.status()} ${url}`);
    }
  });

  await login(desktopPage);
  await desktopPage.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await desktopPage.waitForTimeout(1200);
  await desktopPage.screenshot({ path: path.join(OUT_DIR, 'smoke-desktop-explore.png'), fullPage: false });
  const desktop = await collectExploreMetrics(desktopPage);

  assert.strictEqual(hasHorizontalOverflow(desktop), false, `desktop has horizontal overflow: ${JSON.stringify(desktop)}`);
  assert.strictEqual(desktop.hasLeaflet, true, 'Leaflet should be loaded on explore');
  assert.strictEqual(desktop.hasMapInstance, true, 'Macau map instance should be initialized on explore');
  assert(desktop.mapChildren > 0, `map should render child layers, got ${desktop.mapChildren}`);
  assert(desktop.markerCount > 0, `map should render restaurant markers, got ${desktop.markerCount}`);
  assert(desktop.restaurantCards >= 10, `expected restaurant cards to render, got ${desktop.restaurantCards}`);
  assert.deepStrictEqual(desktop.hiddenStillVisible, [], `hidden elements are visible: ${desktop.hiddenStillVisible.join(', ')}`);
  assert.deepStrictEqual(failedLocalAssets, [], `local asset failures:\n${failedLocalAssets.join('\n')}`);

  const detailHref = await desktopPage.locator('[data-detail-link]').first().getAttribute('href');
  assert(detailHref, 'expected the first restaurant card to expose a detail link');
  await desktopPage.goto(`${BASE_URL}${detailHref}`, { waitUntil: 'networkidle' });
  await desktopPage.waitForTimeout(1200);
  await desktopPage.screenshot({ path: path.join(OUT_DIR, 'smoke-desktop-restaurant-detail.png'), fullPage: false });
  const detail = await collectRestaurantDetailMetrics(desktopPage);

  assert(detail.title.length > 0, 'restaurant detail page should render a title');
  assert.strictEqual(detail.hasLeaflet, true, 'Leaflet should be loaded on restaurant detail');
  assert(detail.mapHeight >= 260, `restaurant detail map should be usable, got ${detail.mapHeight}px`);
  assert(detail.mapChildren > 0, `restaurant detail map should render child layers, got ${detail.mapChildren}`);
  assert(detail.markerCount > 0, `restaurant detail map should render a marker, got ${detail.markerCount}`);
  assert(detail.reviewCards > 0, `restaurant detail should render seeded reviews, got ${detail.reviewCards}`);
  assert.strictEqual(detail.ratingRows, 5, `rating summary should render five rows, got ${detail.ratingRows}`);
  assert.strictEqual(detail.starButtons, 5, `review form should expose five star buttons, got ${detail.starButtons}`);
  assert.strictEqual(detail.reviewFormExists, true, 'review form should exist for the rating flow');
  assert.deepStrictEqual(detail.hiddenStillVisible, [], `hidden elements are visible on detail: ${detail.hiddenStillVisible.join(', ')}`);
  assert.deepStrictEqual(failedLocalAssets, [], `local asset failures:\n${failedLocalAssets.join('\n')}`);

  const mobileContext = await browser.newContext({
    viewport: { width: 375, height: 812 },
    isMobile: true,
  });
  const mobilePage = await mobileContext.newPage();

  await login(mobilePage);
  await mobilePage.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await mobilePage.waitForTimeout(1200);
  await mobilePage.screenshot({ path: path.join(OUT_DIR, 'smoke-mobile-explore.png'), fullPage: false });
  const mobile = await collectExploreMetrics(mobilePage);

  assert.strictEqual(hasHorizontalOverflow(mobile), false, `mobile has horizontal overflow: ${JSON.stringify(mobile)}`);
  assert.strictEqual(mobile.hasLeaflet, true, 'Leaflet should load on mobile explore');
  assert.strictEqual(mobile.hasMapInstance, true, 'Macau map should initialize on mobile explore');
  assert(mobile.map.height >= 320, `mobile map should stay usable, got ${mobile.map.height}px`);
  assert(mobile.languageButtons.every((button) => button.right <= mobile.clientWidth), `language buttons overflow: ${JSON.stringify(mobile.languageButtons)}`);

  await browser.close();
}

main().catch(async (error) => {
  console.error(error);
  process.exit(1);
});
