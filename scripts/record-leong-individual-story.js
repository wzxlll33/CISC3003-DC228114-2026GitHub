const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { createRequire } = require('node:module');
const { pathToFileURL } = require('node:url');

const ROOT = path.resolve(__dirname, '..');
const PROJECT_ROOT = path.resolve(ROOT, '..');
const RECORDINGS_DIR = path.join(ROOT, 'storage', 'recordings');
const PPT_PREVIEW_DIR = path.join(PROJECT_ROOT, 'deck-previews', 'individual-digital-story');
const VIDEO_TOOLS_ROOT = path.join(PROJECT_ROOT, '.video-tools');
const BASE_URL = process.env.DEMO_BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.DEMO_EMAIL || 'leong.demo@example.com';
const PASSWORD = process.env.DEMO_PASSWORD || 'password123';
const VIEWPORT = { width: 1440, height: 900 };

const runtimeRequire = createRequire('C:/Users/Jackie_Laptop/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/package.json');
let chromium;
try {
  ({ chromium } = require('playwright'));
} catch (error) {
  ({ chromium } = runtimeRequire('playwright'));
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function imageDataUrl(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const mime = ext === '.jpg' || ext === '.jpeg' ? 'image/jpeg' : 'image/png';
  return `data:${mime};base64,${fs.readFileSync(filePath).toString('base64')}`;
}

function getFfmpegPath() {
  if (process.env.FFMPEG_PATH && fs.existsSync(process.env.FFMPEG_PATH)) {
    return process.env.FFMPEG_PATH;
  }

  try {
    const toolsRequire = createRequire(path.join(VIDEO_TOOLS_ROOT, 'package.json'));
    const ffmpeg = toolsRequire('@ffmpeg-installer/ffmpeg');
    return ffmpeg?.path && fs.existsSync(ffmpeg.path) ? ffmpeg.path : '';
  } catch (error) {
    return '';
  }
}

function readSnippet(relativePath, start, end) {
  const filePath = path.join(ROOT, relativePath);
  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  const selected = lines.slice(start - 1, end);
  return selected
    .map((line, index) => `${String(start + index).padStart(4, ' ')}  ${line}`)
    .join('\n');
}

function prepareDemoData() {
  const phpCandidates = [
    process.env.PHP_EXECUTABLE,
    'C:\\xampp\\php\\php.exe',
  ].filter(Boolean);
  const php = phpCandidates.find((candidate) => fs.existsSync(candidate));

  if (!php) {
    console.warn('PHP executable not found; demo data cleanup skipped.');
    return;
  }

  const dbPath = path.join(ROOT, 'storage', 'database', 'app.sqlite').replace(/\\/g, '\\\\');
  const code = `
    $db = new PDO('sqlite:${dbPath}');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $demoHash = $db->query("SELECT password_hash FROM users WHERE email = 'demo@example.com' LIMIT 1")->fetchColumn();
    if (!$demoHash) {
        $demoHash = password_hash('password123', PASSWORD_BCRYPT);
    }
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => 'leong.demo@example.com']);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        $insert = $db->prepare("INSERT INTO users (username, email, password_hash, is_verified, locale, created_at, updated_at) VALUES (:username, :email, :hash, 1, 'en', datetime('now'), datetime('now'))");
        $insert->execute([':username' => 'LeongDemo', ':email' => 'leong.demo@example.com', ':hash' => $demoHash]);
        $userId = $db->lastInsertId();
    } else {
        $update = $db->prepare("UPDATE users SET username = 'LeongDemo', password_hash = :hash, is_verified = 1, locale = 'en', updated_at = datetime('now') WHERE id = :id");
        $update->execute([':hash' => $demoHash, ':id' => $userId]);
    }
    $deleteFavorites = $db->prepare('DELETE FROM favorites WHERE user_id = :id');
    $deleteFavorites->execute([':id' => $userId]);
    $deleteSearch = $db->prepare('DELETE FROM search_history WHERE user_id = :id');
    $deleteSearch->execute([':id' => $userId]);
    echo "Prepared demo user {$userId}\\n";
  `;

  execFileSync(php, ['-r', code], { stdio: 'inherit' });
}

function buildStoryHtml() {
  const snippets = {
    state: readSnippet('public/assets/js/restaurant-catalog.js', 3, 45),
    filter: readSnippet('public/assets/js/restaurant-catalog.js', 359, 388),
    render: readSnippet('public/assets/js/restaurant-catalog.js', 1005, 1054),
    search: readSnippet('public/assets/js/restaurant-catalog.js', 127, 162),
    apply: readSnippet('public/assets/js/restaurant-catalog.js', 879, 914),
    history: readSnippet('public/assets/js/restaurant-catalog.js', 924, 948),
    favorites: readSnippet('public/assets/js/favorites.js', 1, 37),
    toggle: readSnippet('public/assets/js/favorites.js', 156, 204),
    sync: readSnippet('public/assets/js/favorites.js', 206, 255),
    api: readSnippet('app/Controllers/Api/FavoriteApiController.php', 75, 106),
  };

  const slides = [
    {
      kicker: 'CISC3003 Individual Digital Story',
      title: 'Taste of Macau',
      subtitle: 'Leong Chi Long · DC227153 · Team 04',
      body: [
        'My contribution focuses on catalogue rendering, category filtering, debounced search, search history, guest favorites, and login synchronization.',
        'This silent recording is structured for later voice narration and keeps the demo in one planned flow.',
      ],
      badge: '00:00-00:25',
    },
    {
      kicker: 'Personal Scope',
      title: 'What I Did',
      subtitle: 'Discovery experience and saved-food state',
      body: [
        'Built the restaurant-first Explore catalogue and category-filter behavior.',
        'Connected search input to local matching, API-backed results, and dashboard search history.',
        'Implemented guest favorites with LocalStorage and server merge after login.',
        'Prepared dashboard evidence so the demo can prove persistence instead of only showing button states.',
      ],
      badge: '00:25-01:10',
    },
    {
      kicker: 'Demo Roadmap',
      title: 'One Linear Flow',
      subtitle: 'Each step proves one feature once',
      body: [
        'Guest catalogue baseline, then category filtering.',
        'Trilingual UI switch: Chinese, English, Portuguese, then back to English.',
        'Guest adds Portuguese Egg Tart to favorites.',
        'Login sync moves the guest favorite into the user account.',
        'Logged-in search for Lord, press Enter, then verify Search History.',
        'Code walkthrough, challenges, solutions and learning close the story.',
      ],
      badge: '01:10-01:55',
    },
    {
      kicker: 'System Context',
      title: 'Catalogue Architecture',
      subtitle: 'Restaurant cards are filtered by food-category data',
      body: [
        'Explore renders 16 restaurant cards while using food categories such as Desserts and Portuguese as filters.',
        'The UI keeps the map, result count, active filter summary and cards synchronized.',
        'Favorites are food-level data, so guest favorites are demonstrated from a food detail page before being synced to the account.',
      ],
      badge: '01:55-02:35',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Catalogue State Model',
      subtitle: 'public/assets/js/restaurant-catalog.js',
      code: snippets.state,
      callout: 'The controller owns all Explore UI state: active filters, draft filters, search term, loaded restaurants, food data and categoryIndex. I explain this first because the later demo results all come from this state model.',
      badge: '09:15-09:45',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Draft Filter to Applied Filter',
      subtitle: 'public/assets/js/restaurant-catalog.js',
      code: snippets.filter,
      callout: 'A category click only changes draftCategory and the selected button style. The real catalogue does not update until Apply copies draft state into active state and calls applyFilters.',
      badge: '09:45-10:15',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'One Render List for Cards, Count and Map',
      subtitle: 'filterRestaurantsByActiveCategory() and renderCards()',
      code: snippets.render,
      callout: 'Filtering is data-first. The code builds one restaurant list, then uses that same list for cards, result counts, no-results state and map markers. This avoids stale pins or wrong counts.',
      badge: '10:15-10:45',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Debounced Input and Enter Commit',
      subtitle: 'public/assets/js/restaurant-catalog.js',
      code: snippets.search,
      callout: 'Typing starts a 220 ms preview search with commitHistory false. Pressing Enter clears the timer and reruns search with commitHistory true, which is why the dashboard history appears only after Enter.',
      badge: '10:45-11:15',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Local Match, API Fallback and History Guard',
      subtitle: 'applyFilters() and logSearchHistory()',
      code: snippets.apply + '\n\n' + snippets.history,
      callout: 'applyFilters first respects the current category and service filter, then checks local matches. If needed it calls the backend search API. logSearchHistory ignores empty, guest and duplicate searches.',
      badge: '11:15-11:50',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Guest Favorites State',
      subtitle: 'public/assets/js/favorites.js',
      code: snippets.favorites + '\n\n' + snippets.toggle,
      callout: 'Guest mode stores normalized food IDs in LocalStorage; account mode calls the API. Both paths call setFavoriteState, so the favorite button behaves consistently before and after login.',
      badge: '11:50-12:25',
    },
    {
      kicker: 'Code Walkthrough',
      title: 'Login Merge Flow',
      subtitle: 'FavoriteStore.syncLocalToServer()',
      code: snippets.sync,
      callout: 'After login, syncLocalToServer reads guest IDs, posts them once to /api/favorites/sync, updates serverFavoriteIds, clears LocalStorage, hydrates buttons and dispatches an update event.',
      badge: '12:25-13:00',
    },
    {
      kicker: 'Challenge and Solution',
      title: 'Keeping State Consistent',
      subtitle: 'The hardest part was not the button; it was the data contract',
      body: [
        'Challenge: guest favorites and logged-in favorites lived in different storage layers.',
        'Solution: normalize food IDs and make syncLocalToServer the single merge point.',
        'Challenge: search should feel live, but history should not log every keystroke.',
        'Solution: debounce typing for preview results and require Enter for committed history.',
        'Challenge: filters had to update cards, result count and map without contradictory UI states.',
      ],
      badge: '13:00-13:35',
    },
    {
      kicker: 'Learning',
      title: 'What I Learned',
      subtitle: 'Front-end state is part of the full-stack contract',
      body: [
        'A good demo must prove the actual state transition, not just point at a feature.',
        'LocalStorage, session state, CSRF-protected APIs and dashboard pages need to agree.',
        'Stable data attributes made the interface easier to test and record.',
        'Planning the journey first avoided repeated navigation and made the story easier to follow.',
      ],
      badge: '13:35-14:15',
    },
    {
      kicker: 'Closing',
      title: 'Contribution Summary',
      subtitle: 'Leong Chi Long · Catalogue, Search and Favorites',
      body: [
        'The final demo proves category filtering, i18n, guest favorites, login sync, search results and search history.',
        'The code walkthrough connects those behaviors to the implementation.',
        'This completes my Individual Digital Story flow for post-production narration.',
      ],
      badge: '14:15-14:35',
    },
  ];

  const slideHtml = slides.map((slide, index) => {
    const body = slide.code
      ? `<pre><code>${escapeHtml(slide.code)}</code></pre><p class="callout">${escapeHtml(slide.callout)}</p>`
      : `<ul>${slide.body.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
    return `
      <section class="slide" data-slide="${index}" ${index === 0 ? '' : 'hidden'}>
        <div class="slide__chrome">
          <span>${escapeHtml(slide.kicker)}</span>
          <strong>${escapeHtml(slide.badge)}</strong>
        </div>
        <div class="slide__body ${slide.code ? 'slide__body--code' : ''}">
          <p class="kicker">${escapeHtml(slide.kicker)}</p>
          <h1>${escapeHtml(slide.title)}</h1>
          <h2>${escapeHtml(slide.subtitle)}</h2>
          ${body}
        </div>
      </section>`;
  }).join('\n');

  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Leong Chi Long Individual Digital Story</title>
<style>
  :root {
    --blue: #0f4e96;
    --gold: #d4a843;
    --ink: #172026;
    --paper: #faf7ef;
    --muted: #657070;
    --red: #b9614a;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; width: 100%; height: 100%; overflow: hidden; background: var(--paper); color: var(--ink); font-family: Aptos, Segoe UI, Arial, sans-serif; }
  .slide { position: absolute; inset: 0; display: grid; grid-template-rows: 72px 1fr; padding: 34px 54px 42px 72px; background: linear-gradient(120deg, rgba(255,255,255,.82), rgba(250,247,239,.9)); }
  .slide::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 18px; background: var(--blue); }
  .slide:nth-child(4n+2)::before { background: var(--red); }
  .slide:nth-child(4n+3)::before { background: var(--gold); }
  .slide:nth-child(4n+4)::before { background: #2a745b; }
  .slide[hidden] { display: none; }
  .slide__chrome { display: flex; align-items: center; justify-content: space-between; gap: 24px; color: var(--blue); font-size: 16px; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
  .slide__chrome strong { min-width: 126px; padding: 8px 12px; border-radius: 999px; background: var(--blue); color: #fff; font-size: 13px; text-align: center; letter-spacing: .02em; }
  .slide__body { width: min(1040px, 100%); align-self: center; }
  .slide__body--code { width: min(1300px, 100%); display: grid; grid-template-columns: minmax(0, 1fr) 430px; gap: 28px; align-items: start; }
  .kicker { margin: 0 0 14px; color: var(--blue); font-size: 17px; font-weight: 850; text-transform: uppercase; letter-spacing: .08em; }
  h1 { margin: 0; font-size: 66px; line-height: .95; letter-spacing: 0; }
  h2 { margin: 18px 0 30px; max-width: 980px; color: #795e24; font-size: 29px; line-height: 1.2; font-weight: 720; }
  ul { margin: 0; padding: 0; display: grid; gap: 18px; list-style: none; }
  li { position: relative; padding-left: 30px; font-size: 27px; line-height: 1.22; font-weight: 560; }
  li::before { content: ""; position: absolute; left: 0; top: .5em; width: 10px; height: 10px; border-radius: 50%; background: var(--gold); }
  pre { grid-column: 1; margin: 0; height: 540px; overflow: hidden; padding: 22px 24px; border: 1px solid rgba(15,78,150,.18); border-radius: 18px; background: #10202e; color: #f4f0e6; box-shadow: 0 20px 46px rgba(8,35,74,.14); font: 16px/1.4 Consolas, Cascadia Mono, monospace; white-space: pre-wrap; }
  .slide__body--code .kicker, .slide__body--code h1, .slide__body--code h2 { grid-column: 1 / -1; }
  .callout { grid-column: 2; margin: 0; padding: 24px; border-left: 8px solid var(--gold); background: rgba(255,255,255,.76); color: #223032; font-size: 21px; line-height: 1.3; font-weight: 720; }
  .section-note { position: fixed; right: 28px; bottom: 24px; z-index: 999999; width: 410px; padding: 15px 18px; border-radius: 16px; background: rgba(15,32,46,.92); color: #fff; box-shadow: 0 16px 38px rgba(0,0,0,.28); font: 750 20px/1.3 Aptos, Segoe UI, sans-serif; pointer-events: none; }
</style>
</head>
<body>
${slideHtml}
<script>
  window.showSlide = (index) => {
    document.querySelectorAll('.slide').forEach((slide) => {
      slide.hidden = Number(slide.dataset.slide) !== Number(index);
    });
  };
</script>
</body>
</html>`;
}

function buildPptStoryHtml() {
  const slides = Array.from({ length: 20 }, (_, index) => {
    const slideNumber = String(index + 1).padStart(2, '0');
    const filePath = path.join(PPT_PREVIEW_DIR, `slide-${slideNumber}.png`);
    if (!fs.existsSync(filePath)) {
      throw new Error(`Missing PPT preview image: ${filePath}`);
    }

    return {
      number: index + 1,
      dataUrl: imageDataUrl(filePath),
    };
  });

  const slideHtml = slides.map((slide, index) => `
      <section class="slide" data-slide="${index}" ${index === 0 ? '' : 'hidden'}>
        <img src="${slide.dataUrl}" alt="Individual Digital Story slide ${slide.number}">
      </section>`).join('\n');

  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Leong Chi Long Individual Digital Story PPT Recording</title>
<style>
  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #faf7ef;
  }
  .slide {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: #faf7ef;
  }
  .slide[hidden] { display: none; }
  .slide img {
    display: block;
    width: 100vw;
    height: 100vh;
    object-fit: contain;
    background: #faf7ef;
  }
</style>
</head>
<body>
${slideHtml}
<script>
  window.showSlide = (index) => {
    document.querySelectorAll('.slide').forEach((slide) => {
      slide.hidden = Number(slide.dataset.slide) !== Number(index);
    });
  };
</script>
</body>
</html>`;
}

function writeStoryHtml() {
  fs.mkdirSync(RECORDINGS_DIR, { recursive: true });
  const filePath = path.join(RECORDINGS_DIR, 'leong-individual-story.html');
  fs.writeFileSync(filePath, buildPptStoryHtml(), 'utf8');
  return filePath;
}

async function installOverlay(page) {
  await page.evaluate(() => {
    if (document.getElementById('recording-overlay-style')) {
      return;
    }
    const style = document.createElement('style');
    style.id = 'recording-overlay-style';
    style.textContent = `
      #demo-caption {
        position: fixed;
        left: 26px;
        bottom: 24px;
        z-index: 999999;
        max-width: min(760px, calc(100vw - 52px));
        padding: 15px 18px;
        border-radius: 16px;
        background: rgba(15, 32, 46, 0.94);
        color: #fff;
        font: 750 21px/1.34 Aptos, Segoe UI, Arial, sans-serif;
        box-shadow: 0 16px 38px rgba(0,0,0,.28);
        pointer-events: none;
      }
      .demo-click-ring {
        position: fixed;
        z-index: 1000000;
        border: 4px solid #d4a843;
        border-radius: 16px;
        background: rgba(212, 168, 67, 0.14);
        box-shadow: 0 0 0 7px rgba(15,78,150,.20);
        pointer-events: none;
        transition: opacity .35s ease, transform .35s ease;
      }
      .demo-click-label {
        position: fixed;
        z-index: 1000001;
        padding: 8px 11px;
        border-radius: 999px;
        background: #0f4e96;
        color: #fff;
        font: 850 15px/1 Aptos, Segoe UI, Arial, sans-serif;
        pointer-events: none;
        box-shadow: 0 9px 24px rgba(8,35,74,.24);
      }
      .demo-status-badge {
        position: fixed;
        right: 26px;
        top: 84px;
        z-index: 999999;
        max-width: 430px;
        padding: 14px 16px;
        border-left: 7px solid #d4a843;
        border-radius: 12px;
        background: rgba(250,248,240,.96);
        color: #172026;
        font: 850 20px/1.28 Aptos, Segoe UI, Arial, sans-serif;
        box-shadow: 0 16px 35px rgba(8,35,74,.16);
        pointer-events: none;
      }
    `;
    document.head.appendChild(style);
  });
}

async function caption(page, text, seconds = 2.5) {
  await installOverlay(page);
  await page.evaluate((value) => {
    let el = document.getElementById('demo-caption');
    if (!el) {
      el = document.createElement('div');
      el.id = 'demo-caption';
      document.body.appendChild(el);
    }
    el.textContent = value;
  }, text);
  await wait(seconds * 1000);
}

async function statusBadge(page, text, seconds = 2) {
  await installOverlay(page);
  await page.evaluate((value) => {
    document.querySelectorAll('.demo-status-badge').forEach((item) => item.remove());
    const el = document.createElement('div');
    el.className = 'demo-status-badge';
    el.textContent = value;
    document.body.appendChild(el);
    window.setTimeout(() => {
      el.style.opacity = '0';
      window.setTimeout(() => el.remove(), 420);
    }, 2400);
  }, text);
  await wait(seconds * 1000);
}

async function markBox(page, box, label) {
  await installOverlay(page);
  await page.evaluate(({ box, label }) => {
    document.querySelectorAll('.demo-click-ring, .demo-click-label').forEach((item) => item.remove());
    const pad = 7;
    const ring = document.createElement('div');
    ring.className = 'demo-click-ring';
    ring.style.left = `${Math.max(0, box.x - pad)}px`;
    ring.style.top = `${Math.max(0, box.y - pad)}px`;
    ring.style.width = `${box.width + pad * 2}px`;
    ring.style.height = `${box.height + pad * 2}px`;
    document.body.appendChild(ring);
    if (label) {
      const tag = document.createElement('div');
      tag.className = 'demo-click-label';
      tag.textContent = label;
      tag.style.left = `${Math.max(8, box.x)}px`;
      tag.style.top = `${Math.max(8, box.y - 36)}px`;
      document.body.appendChild(tag);
    }
    window.setTimeout(() => {
      ring.style.opacity = '0';
      ring.style.transform = 'scale(1.03)';
      document.querySelectorAll('.demo-click-label').forEach((item) => item.style.opacity = '0');
    }, 900);
    window.setTimeout(() => {
      ring.remove();
      document.querySelectorAll('.demo-click-label').forEach((item) => item.remove());
    }, 1350);
  }, { box, label });
}

async function clickMarked(page, locator, label) {
  await locator.scrollIntoViewIfNeeded().catch(() => {});
  const box = await locator.boundingBox();
  if (box) {
    await markBox(page, box, label);
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 8 });
    await wait(220);
  }
  await locator.click({ timeout: 10000 });
  await wait(520);
}

async function showSlide(page, index, seconds) {
  await page.evaluate((slideIndex) => window.showSlide(slideIndex), index);
  await wait(seconds * 1000);
}

async function goApp(page, url) {
  await page.goto(`${BASE_URL}${url}`, { waitUntil: 'networkidle' });
  await installOverlay(page);
}

async function ensureToolsOpen(page) {
  const shell = page.locator('[data-search-shell]');
  const isOpen = await shell.evaluate((element) => element.classList.contains('is-tools-open')).catch(() => false);
  if (!isOpen) {
    await clickMarked(page, page.locator('[data-toggle-tools]').first(), 'Open tools');
  }

  await page.locator('#explore-tools-panel').waitFor({ state: 'visible', timeout: 6000 });
  await page.waitForFunction(() => {
    const shellElement = document.querySelector('[data-search-shell]');
    const tools = document.querySelector('#explore-tools-panel');
    const toggle = document.querySelector('[data-toggle-tools]');
    return shellElement?.classList.contains('is-tools-open')
      && tools
      && !tools.hidden
      && toggle?.getAttribute('aria-expanded') === 'true';
  });
}

async function openFilterPanel(page) {
  await ensureToolsOpen(page);
  const alreadyOpen = await page.locator('#explore-filter-panel').evaluate((element) => !element.hidden).catch(() => false);
  if (!alreadyOpen) {
    await clickMarked(page, page.locator('[data-open-filters]').first(), 'Filter');
  }
  await page.locator('#explore-filter-panel').waitFor({ state: 'visible', timeout: 6000 });
}

async function closeFilterPanel(page) {
  const closeButton = page.locator('[data-close-filters]').first();
  if (await closeButton.isVisible().catch(() => false)) {
    await clickMarked(page, closeButton, 'Close filter');
    await wait(500);
  }
}

async function waitForResultCount(page, expected) {
  await page.waitForFunction((value) => {
    const count = document.querySelector('[data-results-count]')?.textContent?.trim();
    return count === String(value);
  }, expected, { timeout: 7000 });
}

async function waitForRestaurantText(page, text) {
  await page.waitForFunction((needle) => {
    return Array.from(document.querySelectorAll('[data-restaurant-card]'))
      .some((card) => card.textContent.includes(needle));
  }, text, { timeout: 8000 });
}

async function chooseLocale(page, locale, label) {
  await clickMarked(page, page.locator('[data-mobile-locale-toggle]'), 'Language');
  const target = page.locator(`[data-locale-switch][data-locale="${locale}"]`);
  await target.waitFor({ state: 'visible', timeout: 6000 });
  await clickMarked(page, target, label);
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForFunction((expectedLocale) => (
    document.documentElement.lang === expectedLocale
    || document.querySelector('meta[name="app-locale"]')?.getAttribute('content') === expectedLocale
  ), locale).catch(() => {});
  await wait(900);
  await installOverlay(page);
}

async function login(page) {
  await goApp(page, '/login');
  await caption(page, 'Login as LeongDemo. This is the point where guest LocalStorage is merged into the account.', 4);
  await page.locator('input[name="email"]').fill(EMAIL);
  await wait(400);
  await page.locator('input[name="password"]').fill(PASSWORD);
  await wait(500);
  await clickMarked(page, page.locator('button[type="submit"], input[type="submit"]').first(), 'Sign in');
  await page.waitForLoadState('networkidle').catch(() => {});
  await wait(1600);
  await installOverlay(page);
}

async function liveDemo(page) {
  await goApp(page, '/explore');
  await caption(page, 'Live demo starts as a guest. The Explore page renders the restaurant catalogue with map and result sheet.', 5);
  await statusBadge(page, 'Baseline: 16 restaurant cards', 3);
  await caption(page, 'Each card shows rank, restaurant name, rating, area, category, signature dish cue, detail link and issue-report action.', 6);

  await clickMarked(page, page.locator('[data-toggle-tools]'), 'Open tools');
  await page.locator('#explore-tools-panel').waitFor({ state: 'visible', timeout: 6000 });
  await page.waitForFunction(() => document.querySelector('[data-search-shell]')?.classList.contains('is-tools-open'));
  await statusBadge(page, 'Tools expanded: List, Filter, Must-eat and Favorites are visible', 4);
  await caption(page, 'Open the tools menu first. The category filter button lives inside this expanded panel.', 5);
  await clickMarked(page, page.locator('[data-open-filters]'), 'Filter');
  await caption(page, 'Open the filter panel, then choose a food category. This makes the filtering test visible.', 4);
  await clickMarked(page, page.locator('[data-filter-category="desserts"]'), 'Desserts');
  await statusBadge(page, 'Draft filter selected: Desserts', 3);
  await clickMarked(page, page.locator('[data-apply-filters]'), 'Apply filter');
  await wait(900);
  const filteredCount = await page.locator('[data-results-count]').first().textContent().catch(() => '7');
  await statusBadge(page, `Filter success: ${String(filteredCount).trim()} restaurants shown`, 4);
  await caption(page, 'The visible count changes from 16 to 7, proving that category filtering is actually applied.', 7);

  await caption(page, 'Next, I test i18n. The UI starts in Chinese, then changes language through the global language menu.', 5);
  await chooseLocale(page, 'en', 'English');
  await statusBadge(page, 'English: Search restaurants, areas, signature dishes...', 4);
  await chooseLocale(page, 'pt', 'Portuguese');
  const ptPlaceholder = await page.locator('#restaurant-search').getAttribute('placeholder').catch(() => 'Portuguese UI');
  await statusBadge(page, `Portuguese UI active: ${ptPlaceholder}`, 4);
  await chooseLocale(page, 'en', 'Back to English');
  await statusBadge(page, 'Back to English for the remaining demo', 3);
  await caption(page, 'This verifies that navigation labels, search placeholder and page copy reload in different languages.', 6);

  await goApp(page, '/food/8');
  await caption(page, 'Now I test guest favorites from a food detail page. Favorites are food-level data, so this is the correct page for the button.', 6);
  await clickMarked(page, page.locator('[data-favorite-toggle][data-food-id="8"]'), 'Add favorite');
  await wait(900);
  await statusBadge(page, 'Guest favorite saved: food id 8 in LocalStorage', 4);
  await caption(page, 'The button changes to Remove from Favorites, confirming the guest action succeeded before login.', 5);

  await goApp(page, '/explore?favorites=1');
  await caption(page, 'The guest favorites view now shows restaurants related to the saved Portuguese Egg Tart, before any account login.', 6);
  await statusBadge(page, 'Guest favorites view: 2 matching restaurants', 4);

  await login(page);
  await caption(page, 'After login, FavoriteStore syncs the guest favorite to the server and clears the guest LocalStorage key.', 6);
  await goApp(page, '/dashboard/favorites');
  await caption(page, 'Dashboard evidence: Portuguese Egg Tart appears as a persisted account favorite.', 6);
  await statusBadge(page, 'Login sync success: favorite is now in the user dashboard', 4);

  await goApp(page, '/explore');
  await caption(page, "Now I test search and history. I use the keyword Lord because it returns Lord Stow's Bakery.", 5);
  const search = page.locator('#restaurant-search');
  await clickMarked(page, search, 'Search input');
  await search.fill('');
  await search.type('Lord', { delay: 120 });
  await wait(900);
  await caption(page, 'Typing updates results, but history is only committed when I press Enter.', 4);
  await search.press('Enter');
  await wait(1400);
  await statusBadge(page, 'Search result: Lord Stow\'s Bakery, 1 result', 4);
  await caption(page, "The result is not empty: Lord Stow's Bakery is shown in the result sheet.", 5);

  await goApp(page, '/dashboard/search-history');
  await caption(page, 'Dashboard evidence: the query Lord is recorded with one result because Enter confirmed the search.', 6);
  await statusBadge(page, 'Search history success: Lord · 1 result', 4);
}

async function liveDemoFixed(page) {
  await goApp(page, '/explore');
  await page.evaluate(() => {
    window.localStorage.removeItem('taste-of-macau:guest-favorites');
  });
  await caption(page, 'Planned demo route: guest catalogue, category filter, i18n, guest favorite, login sync, search result, then search history.', 5);
  await chooseLocale(page, 'zh', 'Chinese');
  await goApp(page, '/explore');
  await caption(page, 'The live demo starts as a guest in Chinese. The Explore page renders the restaurant catalogue with map and result sheet.', 5);
  await waitForResultCount(page, 16);
  await statusBadge(page, 'Baseline: 16 restaurant cards', 3);
  await caption(page, 'Each card shows rank, restaurant name, rating, area, category, signature dish cue, detail link and issue-report action.', 6);

  await ensureToolsOpen(page);
  await statusBadge(page, 'Tools expanded: List, Filter, Must-eat and Favorites are visible', 4);
  await caption(page, 'The tools toggle is clicked first, so the filter, list, must-eat and favorites actions are fully visible.', 5);
  await openFilterPanel(page);
  await caption(page, 'Open the filter panel, then choose a food category. This makes the filtering test visible.', 4);
  await clickMarked(page, page.locator('[data-filter-category="desserts"]').first(), 'Desserts');
  await statusBadge(page, 'Draft filter selected: Desserts', 3);
  await clickMarked(page, page.locator('[data-apply-filters]').first(), 'Apply filter');
  await wait(900);
  await waitForResultCount(page, 7);
  const filteredCount = await page.locator('[data-results-count]').first().textContent().catch(() => '7');
  await statusBadge(page, `Filter success: ${String(filteredCount).trim()} restaurants shown`, 4);
  await caption(page, 'The visible count changes from 16 to 7 after Apply is clicked, proving that category filtering is actually tested.', 7);

  await caption(page, 'Next, I test i18n using the same Explore workflow. I switch languages and reopen the tools plus filter panel each time.', 5);
  await chooseLocale(page, 'en', 'English');
  await ensureToolsOpen(page);
  await openFilterPanel(page);
  await page.waitForFunction(() => document.querySelector('#restaurant-search')?.getAttribute('placeholder')?.toLowerCase().includes('search'));
  await statusBadge(page, 'English UI shown: search placeholder and filter panel are translated', 4);
  await caption(page, 'In English, the search placeholder and the filter panel labels are different from the Chinese interface.', 5);
  await closeFilterPanel(page);
  await chooseLocale(page, 'pt', 'Portuguese');
  await ensureToolsOpen(page);
  await openFilterPanel(page);
  const ptPlaceholder = await page.locator('#restaurant-search').getAttribute('placeholder').catch(() => 'Portuguese UI');
  await statusBadge(page, `Portuguese UI active: ${ptPlaceholder}`, 4);
  await caption(page, 'In Portuguese, the search placeholder and filter controls change again, so the i18n effect is visible on screen.', 5);
  await closeFilterPanel(page);
  await chooseLocale(page, 'en', 'Back to English');
  await statusBadge(page, 'Back to English for the remaining demo', 3);
  await caption(page, 'The language switch is now verified in context, not only mentioned in narration.', 5);

  await goApp(page, '/food/8');
  await caption(page, 'Now I test guest favorites from a food detail page. Favorites are food-level data, so this is the correct page for the button.', 6);
  await clickMarked(page, page.locator('[data-favorite-toggle][data-food-id="8"]').first(), 'Add favorite');
  await wait(900);
  await page.waitForFunction(() => {
    const button = document.querySelector('[data-favorite-toggle][data-food-id="8"]');
    const localIds = JSON.parse(window.localStorage.getItem('taste-of-macau:guest-favorites') || '[]');
    return button?.getAttribute('data-favorited') === 'true' && localIds.includes('8');
  });
  await statusBadge(page, 'Guest favorite saved: food id 8 in LocalStorage', 4);
  await caption(page, 'The button changes to Remove from Favorites and LocalStorage contains food id 8, confirming the guest action succeeded before login.', 6);

  await goApp(page, '/explore?favorites=1');
  await waitForRestaurantText(page, 'Lord Stow');
  await caption(page, 'The guest favorites view now shows restaurants related to the saved Portuguese Egg Tart before any account login.', 6);
  await statusBadge(page, 'Guest favorites view: matching restaurants are visible', 4);

  await login(page);
  await caption(page, 'After login, FavoriteStore syncs the guest favorite to the server and clears the guest LocalStorage key.', 6);
  await goApp(page, '/dashboard/favorites');
  await page.waitForFunction(() => document.body.textContent.includes('Portuguese Egg Tart'), null, { timeout: 8000 });
  await caption(page, 'Dashboard evidence: Portuguese Egg Tart appears as a persisted account favorite.', 6);
  await statusBadge(page, 'Login sync success: favorite is now in the user dashboard', 4);

  await goApp(page, '/explore');
  await caption(page, "Now I test search and history. I use the keyword Lord because it returns Lord Stow's Bakery.", 5);
  const search = page.locator('#restaurant-search');
  await clickMarked(page, search, 'Search input');
  await search.fill('');
  await search.type('Lord', { delay: 120 });
  await wait(900);
  await caption(page, 'Typing updates results, but history is only committed when I press Enter.', 4);
  await search.press('Enter');
  await wait(1400);
  await waitForRestaurantText(page, 'Lord Stow');
  await waitForResultCount(page, 1);
  await statusBadge(page, 'Search result: Lord Stow\'s Bakery, 1 result', 4);
  await caption(page, "The result is not empty: Lord Stow's Bakery is shown in the result sheet.", 5);

  await goApp(page, '/dashboard/search-history');
  await page.waitForFunction(() => document.body.textContent.includes('Lord') && document.body.textContent.includes('1'), null, { timeout: 8000 });
  await caption(page, 'Dashboard evidence: the query Lord is recorded with one result because Enter confirmed the search.', 6);
  await statusBadge(page, 'Search history success: Lord - 1 result', 4);
}

function convertWebmToMp4(webmPath) {
  const ffmpegPath = getFfmpegPath();
  if (!ffmpegPath) {
    console.warn('ffmpeg not found; MP4 conversion skipped.');
    return '';
  }

  const mp4Path = webmPath.replace(/\.webm$/i, '.mp4');
  execFileSync(ffmpegPath, [
    '-y',
    '-i',
    webmPath,
    '-c:v',
    'libx264',
    '-preset',
    'veryfast',
    '-crf',
    '23',
    '-pix_fmt',
    'yuv420p',
    '-an',
    mp4Path,
  ], { stdio: 'inherit' });
  return mp4Path;
}

async function main() {
  fs.mkdirSync(RECORDINGS_DIR, { recursive: true });
  prepareDemoData();
  const storyHtmlPath = writeStoryHtml();

  const chromeCandidates = [
    process.env.CHROME_EXECUTABLE,
    'C:\\Tools\\chrome-win64\\chrome.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  ].filter(Boolean);
  const executablePath = chromeCandidates.find((candidate) => fs.existsSync(candidate));
  const browser = await chromium.launch({
    headless: false,
    executablePath,
    args: ['--disable-notifications'],
  });
  const context = await browser.newContext({
    viewport: VIEWPORT,
    deviceScaleFactor: 1,
    recordVideo: {
      dir: RECORDINGS_DIR,
      size: VIEWPORT,
    },
  });
  const page = await context.newPage();
  page.setDefaultTimeout(12000);

  try {
    await page.goto(pathToFileURL(storyHtmlPath).href);
    await showSlide(page, 0, 22);
    await showSlide(page, 1, 38);
    await showSlide(page, 2, 36);
    await showSlide(page, 3, 34);

    await liveDemoFixed(page);

    await page.goto(pathToFileURL(storyHtmlPath).href);
    await showSlide(page, 9, 22);
    await showSlide(page, 10, 32);
    await showSlide(page, 11, 32);
    await showSlide(page, 12, 32);
    await showSlide(page, 13, 32);
    await showSlide(page, 14, 34);
    await showSlide(page, 15, 34);
    await showSlide(page, 16, 34);
    await showSlide(page, 17, 38);
    await showSlide(page, 18, 34);
    await showSlide(page, 19, 22);
  } finally {
    await page.close();
    await context.close();
    await browser.close();
  }

  const videos = fs.readdirSync(RECORDINGS_DIR)
    .filter((name) => name.endsWith('.webm'))
    .map((name) => ({
      name,
      path: path.join(RECORDINGS_DIR, name),
      mtime: fs.statSync(path.join(RECORDINGS_DIR, name)).mtimeMs,
    }))
    .sort((a, b) => b.mtime - a.mtime);

  if (videos[0]) {
    const finalWebm = path.join(RECORDINGS_DIR, `leong-individual-story-${Date.now()}.webm`);
    fs.renameSync(videos[0].path, finalWebm);
    console.log(`Recording saved: ${finalWebm}`);
    const finalMp4 = convertWebmToMp4(finalWebm);
    if (finalMp4) {
      console.log(`MP4 saved: ${finalMp4}`);
    }
  }
  console.log(`Story HTML: ${storyHtmlPath}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
