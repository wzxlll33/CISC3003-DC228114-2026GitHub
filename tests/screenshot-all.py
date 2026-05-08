"""Screenshot all key pages of Taste of Macau for visual inspection."""
from playwright.sync_api import sync_playwright
import os, time

SCREENSHOTS_DIR = r'C:\Users\Jackie_Laptop\Desktop\Repository\Study\UM\Y4\S2\CISC3003\Project\taste-of-macau\tests\screenshots'
os.makedirs(SCREENSHOTS_DIR, exist_ok=True)

BASE = os.environ.get('BASE_URL', 'http://localhost:8000')

def screenshot(page, name, url=None, full_page=True):
    if url:
        page.goto(url, wait_until='domcontentloaded')
        page.wait_for_timeout(500)
    path = os.path.join(SCREENSHOTS_DIR, f'{name}.png')
    page.screenshot(path=path, full_page=full_page)
    print(f'  [{name}] saved')

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1440, 'height': 900})
    page = context.new_page()

    print('=== Public Pages (Guest) ===')
    screenshot(page, '01-landing', f'{BASE}/')
    screenshot(page, '02-login', f'{BASE}/login')
    screenshot(page, '03-register', f'{BASE}/register')
    screenshot(page, '04-forgot-password', f'{BASE}/forgot-password')

    print('\n=== Login as demo ===')
    page.goto(f'{BASE}/login', wait_until='domcontentloaded')
    page.fill('input[name="email"]', 'demo@example.com')
    page.fill('input[name="password"]', 'password123')
    page.click('button[type="submit"]')
    page.wait_for_url('**/explore', wait_until='domcontentloaded')
    page.wait_for_timeout(1000)
    print(f'  Logged in, URL: {page.url}')

    print('\n=== Authenticated Pages ===')
    screenshot(page, '05-explore', f'{BASE}/explore')
    # Wait extra for map tiles to load
    page.wait_for_timeout(2000)
    screenshot(page, '05b-explore-map-loaded', full_page=False)

    screenshot(page, '06-restaurant-detail', f'{BASE}/restaurant/1')
    screenshot(page, '07-food-detail', f'{BASE}/food/1')
    screenshot(page, '08-dashboard', f'{BASE}/dashboard')
    screenshot(page, '09-dashboard-profile', f'{BASE}/dashboard/profile')
    screenshot(page, '10-dashboard-favorites', f'{BASE}/dashboard/favorites')
    screenshot(page, '11-dashboard-search-history', f'{BASE}/dashboard/search-history')
    screenshot(page, '12-dashboard-browse-history', f'{BASE}/dashboard/browse-history')

    print('\n=== Mobile View (375px) ===')
    mobile_context = browser.new_context(viewport={'width': 375, 'height': 812})
    mobile_page = mobile_context.new_page()
    # Login on mobile
    mobile_page.goto(f'{BASE}/login', wait_until='domcontentloaded')
    mobile_page.fill('input[name="email"]', 'demo@example.com')
    mobile_page.fill('input[name="password"]', 'password123')
    mobile_page.click('button[type="submit"]')
    mobile_page.wait_for_url('**/explore', wait_until='domcontentloaded')
    mobile_page.wait_for_timeout(500)

    screenshot(mobile_page, '13-mobile-landing', f'{BASE}/')
    screenshot(mobile_page, '14-mobile-explore', f'{BASE}/explore')
    screenshot(mobile_page, '15-mobile-restaurant', f'{BASE}/restaurant/1')

    mobile_context.close()
    browser.close()
    print(f'\nAll screenshots saved to: {SCREENSHOTS_DIR}')
