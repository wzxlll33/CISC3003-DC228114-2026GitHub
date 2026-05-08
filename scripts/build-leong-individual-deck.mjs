import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { fileURLToPath, pathToFileURL } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const requireFromRuntime = createRequire('C:/Users/Jackie_Laptop/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/package.json');
const artifactToolPath = requireFromRuntime.resolve('@oai/artifact-tool');
const { chromium } = requireFromRuntime('playwright');

const {
  Presentation,
  PresentationFile,
  row,
  column,
  grid,
  layers,
  panel,
  text,
  image,
  shape,
  rule,
  fill,
  hug,
  fixed,
  wrap,
  grow,
  fr,
  auto,
} = await import(pathToFileURL(artifactToolPath).href);

const APP_ROOT = path.resolve(__dirname, '..');
const PROJECT_ROOT = path.resolve(APP_ROOT, '..');
const OUTPUT_PPTX = path.join(PROJECT_ROOT, 'Individual Digital Story.pptx');
const OUTPUT_COPY = path.join(PROJECT_ROOT, 'Individual Digital Story - Leong Chi Long.pptx');
const ASSET_DIR = path.join(APP_ROOT, 'storage', 'recordings', 'individual-deck-assets');
const PREVIEW_DIR = path.join(PROJECT_ROOT, 'deck-previews', 'individual-digital-story');
const BASE_URL = process.env.DEMO_BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.DEMO_EMAIL || 'leong.demo@example.com';
const PASSWORD = process.env.DEMO_PASSWORD || 'password123';

const COLORS = {
  ink: '#172026',
  blue: '#0F4E96',
  gold: '#D4A843',
  red: '#B9614A',
  green: '#2A745B',
  paper: '#FAF7EF',
  surface: '#FFFDF7',
  muted: '#66706F',
  paleBlue: '#E7F0F7',
  paleGold: '#F4E8C8',
  paleRed: '#F5DED7',
  code: '#10202E',
};

const SLIDE_W = 1920;
const SLIDE_H = 1080;

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

async function saveBlob(blob, filePath) {
  ensureDir(path.dirname(filePath));
  if (typeof blob.save === 'function') {
    await blob.save(filePath);
    return;
  }
  const bytes = Buffer.from(await blob.arrayBuffer());
  fs.writeFileSync(filePath, bytes);
}

async function assertServer() {
  const response = await fetch(`${BASE_URL}/api/health`);
  if (!response.ok) {
    throw new Error(`App server is not healthy at ${BASE_URL}/api/health`);
  }
}

function prepareDemoData() {
  const phpCandidates = [
    process.env.PHP_EXECUTABLE,
    'C:\\xampp\\php\\php.exe',
  ].filter(Boolean);
  const php = phpCandidates.find((candidate) => fs.existsSync(candidate));

  if (!php) {
    console.warn('PHP executable not found; screenshot data cleanup skipped.');
    return;
  }

  const dbPath = path.join(APP_ROOT, 'storage', 'database', 'app.sqlite').replace(/\\/g, '\\\\');
  const code = `
    $db = new PDO('sqlite:${dbPath}');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $hash = $db->query("SELECT password_hash FROM users WHERE email = 'demo@example.com' LIMIT 1")->fetchColumn();
    if (!$hash) { $hash = password_hash('password123', PASSWORD_BCRYPT); }
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => 'leong.demo@example.com']);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
      $insert = $db->prepare("INSERT INTO users (username, email, password_hash, is_verified, locale, created_at, updated_at) VALUES ('LeongDemo', 'leong.demo@example.com', :hash, 1, 'en', datetime('now'), datetime('now'))");
      $insert->execute([':hash' => $hash]);
      $userId = $db->lastInsertId();
    } else {
      $update = $db->prepare("UPDATE users SET username = 'LeongDemo', password_hash = :hash, is_verified = 1, locale = 'en', updated_at = datetime('now') WHERE id = :id");
      $update->execute([':hash' => $hash, ':id' => $userId]);
    }
    $db->prepare('DELETE FROM favorites WHERE user_id = :id')->execute([':id' => $userId]);
    $db->prepare('DELETE FROM search_history WHERE user_id = :id')->execute([':id' => $userId]);
    echo "Prepared Leong demo account {$userId}\\n";
  `;
  execFileSync(php, ['-r', code], { stdio: 'inherit' });
}

async function chooseLocale(page, locale) {
  await page.locator('[data-mobile-locale-toggle]').click();
  const button = page.locator(`[data-locale-switch][data-locale="${locale}"]`);
  await button.waitFor({ state: 'visible', timeout: 5000 });
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
    button.click(),
  ]);
  await page.waitForLoadState('networkidle').catch(() => {});
}

async function openExploreTools(page) {
  const toggle = page.locator('[data-toggle-tools]');
  if (await toggle.getAttribute('aria-expanded') !== 'true') {
    await toggle.click();
  }
  await page.locator('#explore-tools-panel').waitFor({ state: 'visible', timeout: 5000 });
  await page.waitForFunction(() => document.querySelector('[data-search-shell]')?.classList.contains('is-tools-open'));
}

async function openFilterPanel(page) {
  await openExploreTools(page);
  const filterButton = page.locator('[data-open-filters]');
  if (await filterButton.getAttribute('aria-expanded') !== 'true') {
    await filterButton.click();
  }
  await page.locator('[data-filter-panel]').waitFor({ state: 'visible', timeout: 5000 });
}

async function captureLocaleFilterPanel(page, locale, filePath) {
  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await chooseLocale(page, locale);
  await page.waitForTimeout(500);
  await openFilterPanel(page);
  await page.waitForTimeout(400);
  await page.screenshot({ path: filePath, clip: { x: 0, y: 65, width: 800, height: 610 } });
}

async function captureLanguageMenu(page, filePath) {
  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await chooseLocale(page, 'zh');
  await page.waitForTimeout(500);
  await page.locator('[data-mobile-locale-toggle]').click();
  await page.locator('[data-mobile-locale-popover]').waitFor({ state: 'visible', timeout: 5000 });
  await page.waitForTimeout(300);
  await page.screenshot({ path: filePath, clip: { x: 1060, y: 0, width: 280, height: 240 } });
}

async function captureScreenshots() {
  ensureDir(ASSET_DIR);
  await assertServer();
  prepareDemoData();

  const chromeCandidates = [
    process.env.CHROME_EXECUTABLE,
    'C:\\Tools\\chrome-win64\\chrome.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  ].filter(Boolean);
  const executablePath = chromeCandidates.find((candidate) => fs.existsSync(candidate));
  const browser = await chromium.launch({ headless: true, executablePath });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
  const page = await context.newPage();
  page.setDefaultTimeout(10000);

  const shots = {
    explore: path.join(ASSET_DIR, '01-explore-baseline.png'),
    filter: path.join(ASSET_DIR, '02-dessert-filter.png'),
    i18nEn: path.join(ASSET_DIR, '03-i18n-en-filter-panel.png'),
    i18nZh: path.join(ASSET_DIR, '04-i18n-zh-filter-panel.png'),
    i18nPt: path.join(ASSET_DIR, '05-i18n-pt-filter-panel.png'),
    languageMenu: path.join(ASSET_DIR, '06-language-menu.png'),
    favorites: path.join(ASSET_DIR, '07-dashboard-favorites.png'),
    history: path.join(ASSET_DIR, '08-search-history.png'),
  };

  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await chooseLocale(page, 'en');
  await page.waitForTimeout(800);
  await openExploreTools(page);
  await page.screenshot({ path: shots.explore, fullPage: false });

  await page.locator('[data-open-filters]').click();
  await page.locator('[data-filter-category="desserts"]').click();
  await page.locator('[data-apply-filters]').click();
  await page.waitForTimeout(900);
  await openFilterPanel(page);
  await page.waitForTimeout(500);
  await page.screenshot({ path: shots.filter, fullPage: false });

  await captureLocaleFilterPanel(page, 'en', shots.i18nEn);
  await captureLocaleFilterPanel(page, 'zh', shots.i18nZh);
  await captureLocaleFilterPanel(page, 'pt', shots.i18nPt);
  await captureLanguageMenu(page, shots.languageMenu);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
    page.locator('[data-locale-switch][data-locale="en"]').click(),
  ]);
  await page.waitForLoadState('networkidle').catch(() => {});

  await page.goto(`${BASE_URL}/food/8`, { waitUntil: 'networkidle' });
  await page.locator('[data-favorite-toggle][data-food-id="8"]').click();
  await page.waitForTimeout(600);
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await page.locator('input[name="email"]').fill(EMAIL);
  await page.locator('input[name="password"]').fill(PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
    page.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  await page.waitForTimeout(1000);
  await page.goto(`${BASE_URL}/dashboard/favorites`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: shots.favorites, fullPage: false });

  await page.goto(`${BASE_URL}/explore`, { waitUntil: 'networkidle' });
  await page.locator('#restaurant-search').fill('Lord');
  await page.locator('#restaurant-search').press('Enter');
  await page.waitForTimeout(1000);
  await page.goto(`${BASE_URL}/dashboard/search-history`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: shots.history, fullPage: false });

  await context.close();
  await browser.close();
  return shots;
}

function t(value, options = {}) {
  return text(value, {
    width: options.width ?? fill,
    height: options.height ?? hug,
    style: {
      fontSize: options.size ?? 28,
      color: options.color ?? COLORS.ink,
      bold: Boolean(options.bold),
      italic: Boolean(options.italic),
    },
    name: options.name,
  });
}

function smallLabel(value, color = COLORS.blue) {
  return t(value.toUpperCase(), {
    size: 18,
    bold: true,
    color,
    width: wrap(760),
  });
}

function titleBlock(kicker, title, subtitle, options = {}) {
  return column(
    { name: 'title-stack', width: fill, height: hug, gap: 18 },
    [
      smallLabel(kicker, options.kickerColor ?? COLORS.blue),
      t(title, { name: 'slide-title', size: options.titleSize ?? 62, bold: true, color: options.titleColor ?? COLORS.ink }),
      t(subtitle, { name: 'slide-subtitle', size: options.subtitleSize ?? 27, color: options.subtitleColor ?? '#735E28', width: wrap(options.subtitleWidth ?? 1220) }),
    ],
  );
}

function bullet(value, options = {}) {
  return row(
    { width: fill, height: hug, gap: 16, align: 'start' },
    [
      shape({
        geometry: 'ellipse',
        width: fixed(12),
        height: fixed(12),
        fill: options.dotColor ?? COLORS.gold,
      }),
      t(value, { size: options.size ?? 25, color: options.color ?? COLORS.ink }),
    ],
  );
}

function bulletList(items, options = {}) {
  return column(
    { width: fill, height: hug, gap: options.gap ?? 16 },
    items.map((item) => bullet(item, options)),
  );
}

function screenshotFrame(imagePath, label) {
  const dataUrl = `data:image/png;base64,${fs.readFileSync(imagePath).toString('base64')}`;
  return panel(
    {
      name: 'screenshot-frame',
      width: fill,
      height: fill,
      padding: 14,
      fill: '#FFFFFF',
      borderRadius: 22,
    },
    column(
      { width: fill, height: fill, gap: 10 },
      [
        image({ dataUrl, width: fill, height: grow(1), fit: 'cover', alt: label }),
        t(label, { size: 17, bold: true, color: COLORS.muted }),
      ],
    ),
  );
}

function miniScreenshotFrame(imagePath, label, options = {}) {
  const dataUrl = `data:image/png;base64,${fs.readFileSync(imagePath).toString('base64')}`;
  return panel(
    {
      name: 'mini-screenshot-frame',
      width: fill,
      height: fill,
      padding: 10,
      fill: '#FFFFFF',
      borderRadius: 18,
    },
    column(
      { width: fill, height: fill, gap: 6 },
      [
        image({ dataUrl, width: fill, height: grow(1), fit: options.fit ?? 'contain', alt: label }),
        t(label, { size: options.labelSize ?? 14, bold: true, color: COLORS.muted }),
      ],
    ),
  );
}

function metric(value, label, color) {
  return column(
    { width: fill, height: hug, gap: 4 },
    [
      t(value, { size: 56, bold: true, color }),
      t(label, { size: 19, color: COLORS.muted }),
    ],
  );
}

function codePanel(lines, callout, options = {}) {
  return grid(
    { width: fill, height: fill, columns: [fr(options.codeFr ?? 1.35), fr(options.noteFr ?? 0.65)], columnGap: 30 },
    [
      panel(
        {
          width: fill,
          height: fill,
          padding: 28,
          fill: COLORS.code,
          borderRadius: 20,
        },
        t(lines.join('\n'), { size: options.codeSize ?? 19, color: '#F6F1E7' }),
      ),
      panel(
        {
          width: fill,
          height: hug,
          padding: { x: 28, y: 26 },
          fill: COLORS.paleGold,
          borderRadius: 20,
        },
        bulletList(callout, { size: options.noteSize ?? 23, dotColor: COLORS.blue, gap: options.noteGap ?? 16 }),
      ),
    ],
  );
}

function chrome(slide, accent, child) {
  slide.compose(
    layers(
      { name: 'slide-root', width: fill, height: fill, alignItems: 'stretch', justifyItems: 'stretch' },
      [
        shape({ name: 'background', width: fill, height: fill, fill: COLORS.paper }),
        shape({ name: 'left-accent', width: fixed(18), height: fill, fill: accent }),
        child,
      ],
    ),
    { frame: { left: 0, top: 0, width: SLIDE_W, height: SLIDE_H }, baseUnit: 8 },
  );
}

function slideLayout(accent, children, options = {}) {
  return grid(
    {
      width: fill,
      height: fill,
      columns: [fr(1)],
      padding: { x: options.xPad ?? 104, y: options.yPad ?? 72 },
      rows: options.rows ?? [auto, fr(1), auto],
      rowGap: options.rowGap ?? 36,
    },
    [
      ...children,
      t('Taste of Macau - Individual Digital Story - Leong Chi Long', {
        size: 15,
        color: COLORS.muted,
        name: 'footer',
      }),
    ],
  );
}

function addCover(presentation) {
  const slide = presentation.slides.add();
  chrome(
    slide,
    COLORS.blue,
    slideLayout(
      COLORS.blue,
      [
        column(
          { width: fill, height: fill, justify: 'center', gap: 26 },
          [
            smallLabel('CISC3003 Individual Digital Story', COLORS.red),
            t('Taste of Macau', { size: 80, bold: true, color: COLORS.ink, width: wrap(1100) }),
            t('Catalogue, search, favorites and the account sync flow', { size: 32, color: '#735E28', width: wrap(1180) }),
            rule({ width: fixed(340), stroke: COLORS.gold, weight: 6 }),
            t('Leong Chi Long - DC227153 - Team 04', { size: 30, bold: true, color: COLORS.blue, width: wrap(900) }),
          ],
        ),
      ],
      { rows: [fr(1), auto], rowGap: 0 },
    ),
  );
}

function addSlides(presentation, shots) {
  const specs = [
    () => [
      COLORS.red,
      [
        titleBlock('Personal Scope', 'My contribution is the discovery state layer', 'I handled the catalogue, category filtering, debounced search, guest favorites, login merge and dashboard proof points.'),
        grid(
          { width: fill, height: fill, columns: [fr(1), fr(1), fr(1)], columnGap: 34 },
          [
            metric('Catalogue', 'restaurant-first Explore UI', COLORS.blue),
            metric('Search', 'debounce + Enter to log', COLORS.red),
            metric('Favorites', 'guest LocalStorage to account sync', COLORS.green),
          ],
        ),
      ],
    ],
    () => [
      COLORS.green,
      [
        titleBlock('Story Flow', 'One route through the demo', 'The recording should prove each feature once, in the same order a user would naturally discover it.'),
        grid(
          { width: fill, height: fill, columns: [fr(0.8), fr(1.2)], columnGap: 46 },
          [
            column(
              { width: fill, height: hug, gap: 20 },
              [
                metric('01', 'Guest catalogue baseline', COLORS.blue),
                metric('02', 'Filter + i18n', COLORS.red),
                metric('03', 'Favorite + login sync', COLORS.green),
              ],
            ),
            bulletList([
              'Start from Explore and show the initial 16 restaurant cards.',
              'Apply Desserts so the count visibly changes to 7.',
              'Switch Chinese, English and Portuguese in the live UI.',
              'Add Portuguese Egg Tart as a guest, then log in.',
              'Search Lord, press Enter, and verify Search History.',
            ]),
          ],
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Architecture', 'Restaurant-first catalogue, food-level favorites', 'The Explore screen shows restaurants, while filters and favorites are driven by food data.'),
        grid(
          { width: fill, height: fill, columns: [fr(1), fr(1.05)], columnGap: 44 },
          [
            bulletList([
              '16 restaurants are rendered in the Explore result sheet.',
              '49 foods power category matching and detail pages.',
              'Favorites use food IDs, then connect back to restaurants.',
              'Dashboard pages provide persistence evidence after login.',
            ]),
            panel(
              { width: fill, height: fill, padding: 34, fill: '#FFFFFF', borderRadius: 24 },
              column(
                { width: fill, height: fill, justify: 'center', gap: 28 },
                [
                  metric('Explore UI', 'cards, map, count, filters', COLORS.blue),
                  rule({ width: fill, stroke: COLORS.gold, weight: 4 }),
                  metric('State Layer', 'search term, draft filters, favorite IDs', COLORS.red),
                  rule({ width: fill, stroke: COLORS.gold, weight: 4 }),
                  metric('Backend APIs', 'favorites sync, history logging, search', COLORS.green),
                ],
              ),
            ),
          ],
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Live Proof 01', 'Explore tools fully expanded', 'The starting point is the real Explore page with the tools menu opened by the toggle button.'),
        grid(
          { width: fill, height: fill, columns: [fr(0.62), fr(1.38)], columnGap: 34 },
          [
            bulletList([
              'Open /explore as a guest.',
              'Click the menu button to expand the tool row.',
              'Show list, filters, must-eat and favorites actions.',
              'Use this as the baseline before filtering.',
            ]),
            screenshotFrame(shots.explore, 'Explore tools expanded: list, filter, must-eat and favorites are visible'),
          ],
        ),
      ],
    ],
    () => [
      COLORS.red,
      [
        titleBlock('Live Proof 02', 'Category filtering is visible', 'The important part is the click path: tools, filter, Desserts, Apply.'),
        grid(
          { width: fill, height: fill, columns: [fr(1.35), fr(0.65)], columnGap: 34 },
          [
            screenshotFrame(shots.filter, 'Desserts filter applied: result count changes from 16 to 7'),
            bulletList([
              'The filter is selected in the panel.',
              'Apply commits the draft category.',
              'Cards, count and map update together.',
              'This fixes the earlier unclear filtering demo.',
            ]),
          ],
        ),
      ],
    ],
    () => [
      COLORS.gold,
      [
        titleBlock('Live Proof 03', 'i18n shown through the same filter workflow', 'Each language opens the same Explore tools and filter panel, so the screenshot proves translated controls in context.'),
        grid(
          { width: fill, height: fill, columns: [fr(1), fr(1), fr(1)], rows: [fr(1), fixed(170)], columnGap: 18, rowGap: 16 },
          [
            miniScreenshotFrame(shots.i18nEn, 'English: Filter restaurants / All categories', { labelSize: 13 }),
            miniScreenshotFrame(shots.i18nZh, '中文：篩選店家 / 全部分類', { labelSize: 13 }),
            miniScreenshotFrame(shots.i18nPt, 'Português: Filtrar restaurantes / Todas as categorias', { labelSize: 13 }),
            row(
              { width: fill, height: fill, gap: 24, columnSpan: 3 },
              [
                miniScreenshotFrame(shots.languageMenu, 'Language menu: 中文, English, Português', { labelSize: 13 }),
                bulletList([
                  'Same Explore screen, same filter workflow.',
                  'Search placeholder, result heading, buttons and category labels all change.',
                  'Language menu shows the available three locales.',
                ], { size: 20, gap: 12 }),
              ],
            ),
          ],
        ),
      ],
    ],
    () => [
      COLORS.green,
      [
        titleBlock('Live Proof 04', 'Guest favorite becomes account data', 'The favorite button is tested on a food detail page because favorites are food-level records.'),
        grid(
          { width: fill, height: fill, columns: [fr(0.7), fr(1.3)], columnGap: 34 },
          [
            bulletList([
              'Open Portuguese Egg Tart detail.',
              'Click Add to Favorites as a guest.',
              'LocalStorage stores food id 8.',
              'After login, the dashboard shows the synced favorite.',
            ]),
            screenshotFrame(shots.favorites, 'Dashboard Favorites after login sync: Portuguese Egg Tart is persisted'),
          ],
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Live Proof 05', 'Search only becomes history after Enter', 'Typing is responsive, but pressing Enter is the deliberate commit action.'),
        grid(
          { width: fill, height: fill, columns: [fr(1.25), fr(0.75)], columnGap: 34 },
          [
            screenshotFrame(shots.history, 'Search History: Lord is logged with one result'),
            bulletList([
              'Use Lord because it returns Lord Stow\'s Bakery.',
              'Press Enter after typing.',
              'History stores query, filters and result count.',
              'Dashboard proves the backend received the committed search.',
            ]),
          ],
        ),
      ],
    ],
    () => [
      COLORS.gold,
      [
        titleBlock('JS Walkthrough', 'The JavaScript job is state orchestration', 'In my part, every click is translated into state, storage, API calls and visible proof in the UI.'),
        grid(
          { width: fill, height: fill, columns: [fr(0.9), fr(1.1)], columnGap: 44 },
          [
            column(
              { width: fill, height: hug, gap: 20 },
              [
                metric('01', 'user action: click, type, Enter, login', COLORS.blue),
                metric('02', 'state change: draft, active, search, favorites', COLORS.red),
                metric('03', 'side effect: LocalStorage or API call', COLORS.green),
                metric('04', 'visible proof: cards, counts, map, dashboard', COLORS.gold),
              ],
            ),
            bulletList([
              'I explain the code as a flow, not as isolated functions.',
              'The catalogue controller keeps UI state predictable across cards, map and filters.',
              'Search uses two modes: preview while typing and committed history after Enter.',
              'Favorites use the same button behavior while switching storage based on login state.',
              'The dashboard screens prove the JavaScript state became persisted account data.',
            ], { size: 24, gap: 14 }),
          ],
        ),
      ],
    ],
    () => [
      COLORS.red,
      [
        titleBlock('Code Walkthrough 01', 'The catalogue controller owns the UI state', 'restaurant-catalog.js starts by collecting DOM targets and separating active state from draft state.'),
        codePanel(
          [
            'constructor(options = {}) {',
            '  this.cardsContainer = root.querySelector("[data-restaurant-cards]");',
            '  this.countElements = root.querySelectorAll("[data-results-count]");',
            '  this.searchInput = root.querySelector("#restaurant-search");',
            '  this.filterPanel = root.querySelector("[data-filter-panel]");',
            '',
            '  this.activeCategory = "all";',
            '  this.activeServiceFilter = "all";',
            '  this.draftCategory = "all";',
            '  this.draftServiceFilter = "all";',
            '  this.searchTerm = "";',
            '  this.debounceTimer = null;',
            '',
            '  this.allRestaurants = options.restaurants || [];',
            '  this.foods = options.foods || [];',
            '  this.visibleRestaurants = [...this.allRestaurants];',
            '  this.categoryIndex = this.buildCategoryIndex(this.foods);',
            '}',
          ],
          [
            'This constructor is the ownership point for the Explore page state.',
            'DOM references let the controller update cards, counts, filters and panels together.',
            'Draft state records what the user is choosing; active state records what is applied.',
            'categoryIndex bridges food-category data back to restaurant cards.',
            'visibleRestaurants becomes the current truth for result UI and map updates.',
          ],
          { codeSize: 15, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Code Walkthrough 02', 'Filter clicks are staged, then committed', 'A filter button does not immediately mutate the catalogue; Apply commits the draft state.'),
        codePanel(
          [
            'bindFilterPanel() {',
            '  [data-filter-service].click -> {',
            '    this.draftServiceFilter = button.dataset.filterService;',
            '    this.syncDraftFilters();',
            '  }',
            '',
            '  [data-filter-category].click -> {',
            '    this.draftCategory = button.dataset.filterCategory;',
            '    this.syncDraftFilters();',
            '  }',
            '',
            '  [data-apply-filters].click -> this.applyDraftFilters();',
            '}',
            '',
            'async applyDraftFilters() {',
            '  this.activeCategory = this.draftCategory || "all";',
            '  this.activeServiceFilter = this.draftServiceFilter || "all";',
            '  await this.applyFilters({ openResults: true });',
            '  this.closeFilters();',
            '}',
          ],
          [
            'Clicking a filter changes only the draft value and selected-button styling.',
            'Apply copies draft state into active state, then runs the real filtering pipeline.',
            'Nearby/location state is cleared when that service mode is no longer active.',
            'The result sheet opens after commit, so the viewer can see the updated count.',
            'Reset also uses the same path, keeping filter behavior consistent.',
          ],
          { codeSize: 15, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.green,
      [
        titleBlock('Code Walkthrough 03', 'Rendering updates cards, counts and map together', 'The catalogue avoids split-brain UI by rerendering every visible surface from the same list.'),
        codePanel(
          [
            'filterRestaurantsByActiveCategory(restaurants) {',
            '  if (this.activeCategory === "all") return restaurants;',
            '  const allowed = this.categoryIndex[this.activeCategory];',
            '  return restaurants.filter((r) => allowed.has(String(r.id)));',
            '}',
            '',
            'renderCards(restaurants) {',
            '  const list = Array.isArray(restaurants) ? restaurants : [];',
            '  this.cardsContainer.innerHTML =',
            '    list.map((r, i) => this.renderCardMarkup(r, i)).join("");',
            '  this.visibleRestaurants = list;',
            '',
            '  this.countElements.forEach((el) => el.textContent = list.length);',
            '  this.noResultsElement.hidden = list.length !== 0;',
            '  window.macauMap?.updateRestaurantMarkers(list);',
            '}',
          ],
          [
            'Filtering is data-first: it builds a restaurant list instead of hiding random DOM cards.',
            'Food category selection becomes a Set of allowed restaurant IDs.',
            'renderCards is the single render point for card HTML, visibleRestaurants and counts.',
            'The no-results state and Macau map markers are refreshed from the same list.',
            'This prevents stale map pins or a result count that disagrees with the cards.',
          ],
          { codeSize: 15, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Code Walkthrough 04', 'Search separates preview from commitment', 'Typing is debounced for responsiveness, while Enter is the signal that creates history.'),
        codePanel(
          [
            'searchInput.addEventListener("input", () => {',
            '  clearTimeout(this.debounceTimer);',
            '  this.debounceTimer = setTimeout(() => {',
            '    this.search(searchInput.value, {',
            '      openResults: true,',
            '      preserveFocus: true,',
            '      commitHistory: false',
            '    });',
            '  }, 220);',
            '});',
            '',
            'searchInput.addEventListener("keydown", (event) => {',
            '  if (event.key === "Enter") {',
            '    clearTimeout(this.debounceTimer);',
            '    this.search(searchInput.value, { commitHistory: true });',
            '  }',
            '});',
          ],
          [
            'Input events clear the old timer, then wait 220 ms before preview search.',
            'Preview search keeps results responsive but uses commitHistory: false.',
            'Enter prevents the default form behavior, clears the timer and commits the query.',
            'allowLocationFallback lets map/location search handle non-restaurant place names.',
            'This explains why the demo must press Enter before checking Search History.',
          ],
          { codeSize: 15, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.red,
      [
        titleBlock('Code Walkthrough 05', 'Search results use local match, API fallback and history guard', 'The search flow tries fast local matching first, then falls back to the backend search API.'),
        codePanel(
          [
            'async applyFilters(options = {}) {',
            '  const categoryFiltered =',
            '    this.filterRestaurantsByActiveCategory(sourceRestaurants);',
            '  const serviceFiltered =',
            '    this.filterRestaurantsByActiveService(categoryFiltered);',
            '',
            '  const localMatches = serviceFiltered.filter((restaurant) =>',
            '    this.matchesQuery(restaurant, this.searchTerm));',
            '  const shouldCommitHistory = Boolean(options.commitHistory);',
            '',
            '  if (localMatches.length > 0) {',
            '    this.renderCards(localMatches);',
            '    if (shouldCommitHistory) this.logSearchHistory(this.searchTerm, localMatches.length);',
            '    return;',
            '  }',
            '',
            '  const response = await window.api.get(',
            '    `/api/restaurants/search?q=${encodeURIComponent(this.searchTerm)}`);',
            '  this.renderCards(remoteMatches);',
            '}',
          ],
          [
            'The pipeline is source list -> category filter -> service filter -> search query.',
            'Local matching returns quickly and respects the active filter state.',
            'The API fallback covers results that are not already in the bootstrapped list.',
            'History logging is guarded by commitHistory, so typing alone does not pollute it.',
            'The backend receives query, active filters and result count for dashboard evidence.',
          ],
          { codeSize: 14, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.green,
      [
        titleBlock('Code Walkthrough 06', 'Favorites support guest mode and account mode', 'favorites.js keeps the button behavior stable while storage changes based on login state.'),
        codePanel(
          [
            'const STORAGE_KEY = "taste-of-macau:guest-favorites";',
            '',
            'readLocalIds() -> JSON.parse(localStorage[STORAGE_KEY])',
            'writeLocalIds(ids) -> normalize, de-duplicate, save',
            '',
            'async toggle(foodId) {',
            '  const id = normalizeFoodId(foodId);',
            '',
            '  if (isLoggedIn() && window.api) {',
            '    const payload = await api.post(`/api/favorites/${id}`);',
            '    setFavoriteState(id, payload.action === "added");',
            '    return payload;',
            '  }',
            '',
            '  const active = !readLocalIds().includes(id);',
            '  setFavoriteState(id, active);',
            '  return { action: active ? "added" : "removed", local: true };',
            '}',
          ],
          [
            'normalizeFoodId protects the feature from empty, invalid or duplicate values.',
            'Guest mode writes to LocalStorage so saving works before registration or login.',
            'Account mode calls the API endpoint, then updates the same front-end state.',
            'setFavoriteState hydrates buttons and food bootstrap data after either path.',
            'This is why the same favorite button feels stable across guest and logged-in mode.',
          ],
          { codeSize: 14, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Code Walkthrough 07', 'Login sync turns guest choices into server data', 'On DOMContentLoaded, guest favorites are merged into the account and the local copy is cleared.'),
        codePanel(
          [
            'async syncLocalToServer() {',
            '  if (!isLoggedIn() || !window.api) return null;',
            '',
            '  const localIds = readLocalIds();',
            '  if (localIds.length === 0) return null;',
            '',
            '  const payload = await api.post("/api/favorites/sync", {',
            '    food_ids: localIds.map((id) => Number(id))',
            '  });',
            '',
            '  serverFavoriteIds = new Set(payload.favorite_ids);',
            '  localStorage.removeItem(STORAGE_KEY);',
            '  hydrateDocument();',
            '  dispatchChange();',
            '}',
            '',
            'DOMContentLoaded -> syncLocalToServer().finally(hydrateDocument)',
          ],
          [
            'The sync runs only when the browser knows the user is logged in and API is available.',
            'If there are no guest IDs, it exits without unnecessary network work.',
            'Guest IDs are posted once to /api/favorites/sync and converted to server favorites.',
            'LocalStorage is removed after success, preventing repeated merge attempts.',
            'hydrateDocument and dispatchChange refresh buttons, cards and dashboard proof.',
          ],
          { codeSize: 14, noteSize: 18, noteGap: 10 },
        ),
      ],
    ],
    () => [
      COLORS.red,
      [
        titleBlock('Challenge and Solution', 'The hard part was state consistency', 'Each feature looked simple in the UI, but the real work was keeping multiple states aligned.'),
        grid(
          { width: fill, height: fill, columns: [fr(1), fr(1)], columnGap: 38 },
          [
            bulletList([
              'Challenge: guest and logged-in favorites use different storage.',
              'Challenge: live search should not create noisy history records.',
              'Challenge: filters must update result count, cards and map together.',
            ], { dotColor: COLORS.red }),
            bulletList([
              'Solution: normalize food IDs and centralize sync.',
              'Solution: debounce preview search and log only after Enter.',
              'Solution: render all visible catalogue surfaces from one active filter state.',
            ], { dotColor: COLORS.green }),
          ],
        ),
      ],
    ],
    () => [
      COLORS.gold,
      [
        titleBlock('Learning', 'A demo should prove the state transition', 'For my part of the project, the best evidence is seeing UI state become stored account data.'),
        grid(
          { width: fill, height: fill, columns: [fr(0.9), fr(1.1)], columnGap: 44 },
          [
            column(
              { width: fill, height: fill, justify: 'center', gap: 30 },
              [
                metric('UI', 'visible behavior', COLORS.blue),
                metric('Storage', 'LocalStorage/session/API', COLORS.red),
                metric('Evidence', 'dashboard confirmation', COLORS.green),
              ],
            ),
            bulletList([
              'Stable data attributes made the interface testable.',
              'Small details like pressing Enter affect whether history is recorded.',
              'A linear story makes the technical contribution easier to assess.',
              'Front-end state is part of the full-stack contract.',
            ]),
          ],
        ),
      ],
    ],
    () => [
      COLORS.blue,
      [
        titleBlock('Closing', 'Contribution Summary', 'Leong Chi Long - DC227153 - Team 04'),
        grid(
          { width: fill, height: fill, columns: [fr(0.9), fr(1.1)], columnGap: 44 },
          [
            column(
              { width: fill, height: fill, justify: 'center', gap: 18 },
              [
                metric('Catalogue', 'rendering and filtering', COLORS.blue),
                metric('Search', 'API results and history', COLORS.red),
                metric('Favorites', 'guest sync and dashboard proof', COLORS.green),
              ],
            ),
            bulletList([
              'Catalogue rendering and category filtering.',
              'Debounced search with API-backed results and search history.',
              'Guest favorites, login sync and dashboard evidence.',
              'Challenges, solutions and learning explained through code.',
            ], { size: 28 }),
          ],
        ),
      ],
    ],
  ];

  specs.forEach((makeSpec) => {
    const [accent, children] = makeSpec();
    const slide = presentation.slides.add();
    chrome(slide, accent, slideLayout(accent, children));
  });
}

async function buildDeck() {
  const shots = await captureScreenshots();
  ensureDir(PREVIEW_DIR);

  const presentation = Presentation.create({ slideSize: { width: SLIDE_W, height: SLIDE_H } });
  addCover(presentation);
  addSlides(presentation, shots);

  const pptx = await PresentationFile.exportPptx(presentation);
  await saveBlob(pptx, OUTPUT_PPTX);
  let copySaved = true;
  try {
    await saveBlob(pptx, OUTPUT_COPY);
  } catch (error) {
    copySaved = false;
    console.warn(`Skipped locked deck copy: ${OUTPUT_COPY}`);
    console.warn(error?.message || error);
  }

  const previews = [];
  for (let index = 0; index < presentation.slides.count; index += 1) {
    const slide = presentation.slides.getItem(index);
    const png = await slide.export({ format: 'png' });
    const previewPath = path.join(PREVIEW_DIR, `slide-${String(index + 1).padStart(2, '0')}.png`);
    await saveBlob(png, previewPath);
    previews.push(previewPath);
  }

  console.log(JSON.stringify({
    pptx: OUTPUT_PPTX,
    copy: OUTPUT_COPY,
    copySaved,
    previews,
    screenshots: shots,
  }, null, 2));
}

buildDeck()
  .then(() => {
    process.reallyExit(0);
  })
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  });
