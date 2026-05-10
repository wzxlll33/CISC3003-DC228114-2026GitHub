"""Focused Playwright smoke checks for the map-first explore experience."""
from pathlib import Path
import os
import re

from playwright.sync_api import expect, sync_playwright


BASE_URL = os.environ.get("BASE_URL", "http://localhost:8000")
SCREENSHOTS_DIR = Path(__file__).resolve().parent / "screenshots"
SCREENSHOTS_DIR.mkdir(parents=True, exist_ok=True)


def login(page):
    page.goto(f"{BASE_URL}/login", wait_until="domcontentloaded")
    page.fill('input[name="email"]', "demo@example.com")
    page.fill('input[name="password"]', "password123")
    page.click('button[type="submit"]')
    page.wait_for_url("**/explore", wait_until="domcontentloaded")


def assert_no_horizontal_overflow(page, label):
    metrics = page.evaluate(
        """() => ({
            clientWidth: document.documentElement.clientWidth,
            scrollWidth: document.documentElement.scrollWidth,
        })"""
    )
    assert metrics["scrollWidth"] <= metrics["clientWidth"] + 1, f"{label} has horizontal overflow: {metrics}"


def assert_initial_explore_state(page, label):
    expect(page.locator(".explore-search-shell")).to_be_visible()
    expect(page.locator("[data-results-panel]")).to_be_visible()
    expect(page.locator("[data-filter-panel]")).to_be_hidden()
    expect(page.locator("[data-must-eat-panel]")).to_be_hidden()
    expect(page.locator("[data-panel-backdrop]")).to_be_hidden()
    expect(page.locator("[data-explore-tools]")).to_be_hidden()
    expect(page.locator("[data-toggle-tools]")).to_have_attribute("aria-expanded", "false")
    page.wait_for_function("() => window.RestaurantCatalog && window.macauMap?.map", timeout=10000)

    map_metrics = page.evaluate(
        """() => ({
            hasLeaflet: Boolean(window.L),
            hasMap: Boolean(window.macauMap?.map),
            mapChildren: document.querySelector("#map-container")?.children.length || 0,
            visibleCards: [...document.querySelectorAll("[data-restaurant-card]")]
                .filter((card) => card.offsetParent !== null).length,
            visibleActions: [...document.querySelectorAll(".explore-action")]
                .filter((button) => button.offsetParent !== null).length,
            searchWidth: Math.round(document.querySelector(".explore-search-shell")?.getBoundingClientRect().width || 0),
            resultsWidth: Math.round(document.querySelector("[data-results-panel]")?.getBoundingClientRect().width || 0),
        })"""
    )
    assert map_metrics["hasLeaflet"], f"{label} should load Leaflet"
    assert map_metrics["hasMap"], f"{label} should initialize the Macau map"
    assert map_metrics["mapChildren"] > 0, f"{label} map should render layers: {map_metrics}"
    assert map_metrics["visibleCards"] > 0, f"{label} should show restaurant cards on first load: {map_metrics}"
    assert map_metrics["visibleActions"] == 0, f"{label} should keep explore actions hidden on first load: {map_metrics}"
    assert abs(map_metrics["searchWidth"] - map_metrics["resultsWidth"]) <= 1, f"{label} search/results widths should match: {map_metrics}"
    assert_no_horizontal_overflow(page, label)


def exercise_desktop(page):
    page.goto(f"{BASE_URL}/explore", wait_until="domcontentloaded")
    page.wait_for_timeout(1200)
    assert_initial_explore_state(page, "desktop initial")

    page.evaluate(
        """() => {
            window.macauMap.map.setZoom(12);
            window.macauMap.renderRestaurantMarkers();
        }"""
    )
    page.wait_for_timeout(350)
    cluster_count = page.locator(".map-cluster-wrapper").count()
    assert cluster_count > 0, "low zoom should combine nearby restaurants into cluster labels"

    page.click("[data-toggle-tools]")
    expect(page.locator("[data-explore-tools]")).to_be_visible()
    expect(page.locator("[data-toggle-tools]")).to_have_attribute("aria-expanded", "true")
    page.click("[data-open-results]")
    expect(page.locator("[data-results-panel]")).to_be_visible()
    expect(page.locator("[data-open-results]").first).to_have_attribute("aria-expanded", "true")
    expect(page.locator("[data-explore-tools]")).to_be_hidden()
    assert page.locator("[data-restaurant-card]").count() > 0, "results panel should render restaurant cards"

    page.click("[data-close-results]")
    expect(page.locator("[data-results-panel]")).to_be_hidden()
    expect(page.locator("[data-open-results]").first).to_have_attribute("aria-expanded", "false")

    page.click("[data-toggle-tools]")
    page.click("[data-open-filters]")
    expect(page.locator("[data-filter-panel]")).to_be_visible()
    expect(page.locator("[data-open-filters]").first).to_have_attribute("aria-expanded", "true")
    page.click('[data-filter-service="high-rated"]')
    expect(page.locator('[data-filter-service="high-rated"]')).to_have_attribute("aria-pressed", "true")
    page.click("[data-apply-filters]")
    expect(page.locator("[data-filter-panel]")).to_be_hidden()
    expect(page.locator("[data-results-panel]")).to_be_visible()
    expect(page.locator("[data-active-filter-summary]")).to_contain_text(re.compile(r"(High rated|高評分|Bem avaliados)"))
    assert page.locator("[data-restaurant-card]").count() > 0, "high-rated filter should keep visible results"

    page.click("[data-toggle-tools]")
    page.click("[data-open-must-eat]")
    expect(page.locator("[data-must-eat-panel]")).to_be_visible()
    assert page.locator("[data-top-rated-id]").count() > 0, "must-eat drawer should render ranked restaurants"
    page.click("[data-top-rated-id]")
    page.wait_for_timeout(1100)
    expect(page.locator("[data-results-panel]")).to_be_visible()
    zoom = page.evaluate("() => window.macauMap?.map?.getZoom?.() || 0")
    assert zoom >= 15, f"must-eat item should zoom into a restaurant, got zoom {zoom}"

    page.screenshot(path=str(SCREENSHOTS_DIR / "explore-ui-smoke-desktop.png"), full_page=False)
    assert_no_horizontal_overflow(page, "desktop after interactions")


def exercise_mobile(page):
    page.goto(f"{BASE_URL}/explore", wait_until="domcontentloaded")
    page.wait_for_timeout(1200)
    assert_initial_explore_state(page, "mobile initial")

    page.click("[data-toggle-tools]")
    expect(page.locator("[data-explore-tools]")).to_be_visible()
    page.click("[data-open-filters]")
    expect(page.locator("[data-filter-panel]")).to_be_visible()
    filter_box = page.locator("[data-filter-panel]").bounding_box()
    viewport = page.viewport_size
    assert filter_box and viewport, "mobile filter panel should have a measurable box"
    assert filter_box["x"] >= -1, f"mobile filter panel starts off-screen: {filter_box}"
    assert filter_box["x"] + filter_box["width"] <= viewport["width"] + 1, f"mobile filter panel overflows: {filter_box}"

    page.click("[data-close-filters]")
    expect(page.locator("[data-filter-panel]")).to_be_hidden()
    page.screenshot(path=str(SCREENSHOTS_DIR / "explore-ui-smoke-mobile.png"), full_page=False)
    assert_no_horizontal_overflow(page, "mobile after interactions")


def main():
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(headless=True)

        desktop = browser.new_context(viewport={"width": 1440, "height": 900})
        desktop_page = desktop.new_page()
        failed_local_assets = []
        page_errors = []
        desktop_page.on("pageerror", lambda error: page_errors.append(str(error)))
        desktop_page.on(
            "response",
            lambda response: failed_local_assets.append(f"{response.status} {response.url}")
            if response.url.startswith(BASE_URL) and response.status >= 400 and "/favicon.ico" not in response.url
            else None,
        )
        login(desktop_page)
        exercise_desktop(desktop_page)
        desktop.close()

        mobile = browser.new_context(viewport={"width": 375, "height": 812}, is_mobile=True)
        mobile_page = mobile.new_page()
        mobile_page.on("pageerror", lambda error: page_errors.append(str(error)))
        login(mobile_page)
        exercise_mobile(mobile_page)
        mobile.close()

        browser.close()

    assert not page_errors, "browser page errors:\n" + "\n".join(page_errors)
    assert not failed_local_assets, "local asset failures:\n" + "\n".join(failed_local_assets)
    print("Explore UI smoke checks passed")


if __name__ == "__main__":
    main()
