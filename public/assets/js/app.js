document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    if (window.I18n) {
        window.I18n.init();
    }

    const catalogRoot = document.querySelector('[data-food-catalog]');
    const restaurantCatalogRoot = document.querySelector('[data-restaurant-catalog]');
    const reviewRoot = document.querySelector('[data-review-root]');
    const mapContainer = document.getElementById('map-container');
    const detailMapContainer = document.getElementById('food-detail-map');
    const restaurantDetailMapContainer = document.getElementById('restaurant-detail-map');
    const restaurantMobileMapPanel = document.querySelector('[data-restaurant-mobile-map-panel]');
    const restaurantMobileMapOpenButtons = document.querySelectorAll('[data-restaurant-mobile-map-open]');
    const localeButtons = document.querySelectorAll('[data-locale-switch]');
    const mobileLocaleMenu = document.querySelector('[data-mobile-locale-menu]');
    const mobileLocaleToggle = document.querySelector('[data-mobile-locale-toggle]');
    const mobileLocalePopover = document.querySelector('[data-mobile-locale-popover]');
    const feedbackModal = document.querySelector('[data-feedback-modal]');
    const feedbackForm = document.querySelector('[data-feedback-form]');
    const feedbackStatus = document.querySelector('[data-feedback-status]');
    const detailModals = document.querySelectorAll('[data-detail-modal]');
    const dashboardFavoritesGrid = document.querySelector('[data-dashboard-favorites-grid]');
    const favoriteToggleSelector = '[data-favorite-toggle]';
    const restaurantDetailCompactQuery = window.matchMedia('(max-width: 1099px)');
    let catalog = null;

    const scheduleAfterFirstPaint = (callback) => {
        const run = () => {
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(callback, { timeout: 700 });
                return;
            }

            window.setTimeout(callback, 80);
        };

        if ('requestAnimationFrame' in window) {
            window.requestAnimationFrame(() => window.setTimeout(run, 0));
            return;
        }

        run();
    };

    localeButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const locale = button.getAttribute('data-locale');

            if (!locale || !window.I18n) {
                return;
            }

            setMobileLocaleOpen(false);

            localeButtons.forEach((item) => {
                item.disabled = true;
            });

            try {
                await window.I18n.setLocale(locale, { persist: true, reload: true });
            } catch (error) {
                console.error(error);
                localeButtons.forEach((item) => {
                    item.disabled = false;
                });
                setMobileLocaleOpen(false);
            }
        });
    });

    const setMobileLocaleOpen = (isOpen) => {
        if (!mobileLocaleToggle || !mobileLocalePopover) {
            return;
        }

        mobileLocaleToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileLocalePopover.hidden = !isOpen;
    };

    mobileLocaleToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        setMobileLocaleOpen(Boolean(mobileLocalePopover?.hidden));
    });

    document.addEventListener('click', (event) => {
        if (!mobileLocaleMenu || mobileLocaleMenu.contains(event.target)) {
            return;
        }

        setMobileLocaleOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMobileLocaleOpen(false);
            closeFeedbackModal();
            closeDetailModals();
            closeRestaurantMobileMap();
        }
    });

    function setFeedbackStatus(message, type = 'error') {
        if (!feedbackStatus) {
            return;
        }

        feedbackStatus.hidden = !message;
        feedbackStatus.textContent = message || '';
        feedbackStatus.dataset.state = type;
    }

    function closeFeedbackModal() {
        if (!feedbackModal) {
            return;
        }

        feedbackModal.hidden = true;
        document.body.classList.remove('is-feedback-open');
        setFeedbackStatus('');
    }

    function setRestaurantMobileMapOpen(isOpen) {
        if (!restaurantMobileMapPanel) {
            return;
        }

        restaurantMobileMapPanel.classList.toggle('is-mobile-map-open', isOpen);
        restaurantMobileMapPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.classList.toggle('is-restaurant-map-open', isOpen);

        if (!isOpen) {
            return;
        }

        window.setTimeout(() => {
            const map = window.restaurantDetailMap?.map;
            const restaurant = window.__RESTAURANT_DETAIL__?.restaurant;

            map?.invalidateSize?.();

            if (map && restaurant) {
                const lat = Number(restaurant.latitude) || 22.1745;
                const lng = Number(restaurant.longitude) || 113.55;
                map.setView([lat, lng], Math.max(map.getZoom(), 16), { animate: false });
            }
        }, 80);
    }

    function closeRestaurantMobileMap() {
        setRestaurantMobileMapOpen(false);
    }

    function openDetailModal(name) {
        const modal = document.querySelector(`[data-detail-modal="${CSS.escape(name)}"]`);

        if (!modal) {
            return;
        }

        closeDetailModals();
        modal.hidden = false;
        document.body.classList.add('is-detail-modal-open');
        window.setTimeout(() => modal.querySelector('[data-detail-modal-close], a, button')?.focus?.(), 30);
    }

    function closeDetailModals() {
        if (!detailModals.length) {
            return;
        }

        detailModals.forEach((modal) => {
            modal.hidden = true;
        });
        document.body.classList.remove('is-detail-modal-open');
    }

    function syncDetailMoreButtons() {
        const reviewsSection = document.querySelector('#restaurant-reviews');
        const reviewButton = document.querySelector('[data-detail-more-button="reviews"]');
        const dishButton = document.querySelector('[data-detail-more-button="dishes"]');
        const isCompactDetail = restaurantDetailCompactQuery.matches;

        if (dishButton) {
            const count = Number(dishButton.getAttribute('data-detail-more-count') || 0);
            const previewLimit = Number(dishButton.getAttribute('data-detail-preview-limit') || 2);
            dishButton.hidden = isCompactDetail && count <= previewLimit;
        }

        if (reviewButton && isCompactDetail) {
            const count = Number(reviewButton.getAttribute('data-detail-more-count') || 0);
            const previewLimit = Number(reviewButton.getAttribute('data-detail-preview-limit') || 3);
            reviewButton.hidden = count <= previewLimit;
            return;
        }

        if (reviewButton && reviewsSection) {
            window.requestAnimationFrame(() => {
                const overflows = reviewsSection.scrollHeight > reviewsSection.clientHeight + 8;
                reviewButton.hidden = !overflows;
            });
        }
    }

    window.addEventListener('restaurant-reviews-updated', syncDetailMoreButtons);

    function openFeedbackModal(trigger) {
        if (!feedbackModal || !feedbackForm) {
            return;
        }

        const label = trigger.getAttribute('data-feedback-context-label') || document.title;
        const contextType = trigger.getAttribute('data-feedback-context-type') || 'general';
        const restaurantId = trigger.getAttribute('data-feedback-restaurant-id') || '';
        const foodId = trigger.getAttribute('data-feedback-food-id') || '';

        feedbackForm.reset();
        const restaurantInput = feedbackForm.querySelector('[data-feedback-restaurant-id]');
        const foodInput = feedbackForm.querySelector('[data-feedback-food-id]');
        const contextInput = feedbackForm.querySelector('[data-feedback-context-type]');

        if (restaurantInput) {
            restaurantInput.value = restaurantId;
        }

        if (foodInput) {
            foodInput.value = foodId;
        }

        if (contextInput) {
            contextInput.value = contextType;
        }

        const contextLabel = feedbackForm.querySelector('[data-feedback-context]');
        if (contextLabel) {
            contextLabel.textContent = label;
        }

        setFeedbackStatus('');
        feedbackModal.hidden = false;
        document.body.classList.add('is-feedback-open');
        window.setTimeout(() => feedbackForm.querySelector('select, textarea, input, button')?.focus?.(), 30);
    }

    document.addEventListener('click', (event) => {
        const detailTrigger = event.target.closest('[data-detail-modal-open]');

        if (detailTrigger) {
            event.preventDefault();
            openDetailModal(detailTrigger.getAttribute('data-detail-modal-open') || '');
            return;
        }

        if (event.target.closest('[data-detail-modal-close]')) {
            event.preventDefault();
            closeDetailModals();
            return;
        }

        const trigger = event.target.closest('[data-feedback-open]');

        if (trigger) {
            event.preventDefault();
            openFeedbackModal(trigger);
            return;
        }

        if (event.target.closest('[data-feedback-close]')) {
            event.preventDefault();
            closeFeedbackModal();
        }
    });

    restaurantMobileMapOpenButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            setRestaurantMobileMapOpen(true);
        });
    });

    if (restaurantMobileMapPanel && restaurantDetailCompactQuery.matches) {
        setRestaurantMobileMapOpen(false);
    }

    const handleRestaurantDetailCompactChange = (event) => {
        if (!event.matches) {
            closeRestaurantMobileMap();
            return;
        }

        setRestaurantMobileMapOpen(false);
    };

    if (typeof restaurantDetailCompactQuery.addEventListener === 'function') {
        restaurantDetailCompactQuery.addEventListener('change', handleRestaurantDetailCompactChange);
    } else if (typeof restaurantDetailCompactQuery.addListener === 'function') {
        restaurantDetailCompactQuery.addListener(handleRestaurantDetailCompactChange);
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-restaurant-mobile-map-close]')) {
            return;
        }

        event.preventDefault();
        closeRestaurantMobileMap();
    });

    feedbackForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!window.api) {
            return;
        }

        const submitButton = feedbackForm.querySelector('button[type="submit"]');
        const formData = new FormData(feedbackForm);
        const message = String(formData.get('message') || '').trim();
        const issueType = String(formData.get('issue_type') || '').trim();

        if (!issueType || message.length < 10) {
            setFeedbackStatus(feedbackModal?.dataset.feedbackValidation || 'Please add more detail.', 'error');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            await window.api.post('/api/feedback', {
                issue_type: issueType,
                message,
                contact_email: String(formData.get('contact_email') || '').trim(),
                context_type: String(formData.get('context_type') || 'general'),
                restaurant_id: String(formData.get('restaurant_id') || ''),
                food_id: String(formData.get('food_id') || ''),
                page_url: window.location.href,
            });

            setFeedbackStatus(feedbackModal?.dataset.feedbackSuccess || 'Feedback submitted.', 'success');
            window.setTimeout(closeFeedbackModal, 900);
        } catch (error) {
            setFeedbackStatus(error?.payload?.error || feedbackModal?.dataset.feedbackError || 'Unable to submit feedback.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });

    const initMapViews = () => {
        if (mapContainer && window.MacauMap) {
            const restaurants = Array.isArray(window.restaurantCatalog?.visibleRestaurants)
                ? window.restaurantCatalog.visibleRestaurants
                : Array.isArray(window.__RESTAURANTS__)
                    ? window.__RESTAURANTS__
                    : Array.isArray(window.__TASTE_OF_MACAU__?.restaurants)
                        ? window.__TASTE_OF_MACAU__.restaurants
                        : [];
            const foods = catalog && typeof catalog.getVisibleFoods === 'function'
                ? catalog.getVisibleFoods()
                : Array.isArray(window.__FOODS__)
                    ? window.__FOODS__
                    : Array.isArray(window.__TASTE_OF_MACAU__?.foods)
                        ? window.__TASTE_OF_MACAU__.foods
                        : [];
            const useRestaurants = restaurants.length > 0;

            window.macauMap = new window.MacauMap();
            window.macauMap.init('map-container', useRestaurants ? restaurants : foods, {
                mode: useRestaurants ? 'restaurant' : 'food',
                center: [22.1745, 113.55],
                zoom: 13,
                singleMarkerZoom: 16,
            });
        }

        if (detailMapContainer && window.MacauMap && window.__FOOD_DETAIL__) {
            const detailMap = new window.MacauMap();
            detailMap.init('food-detail-map', [window.__FOOD_DETAIL__], {
                center: [Number(window.__FOOD_DETAIL__.latitude) || 22.1745, Number(window.__FOOD_DETAIL__.longitude) || 113.55],
                zoom: 16,
                singleMarkerZoom: 16,
            });
        }

        if (restaurantDetailMapContainer && window.MacauMap && window.__RESTAURANT_DETAIL__?.restaurant) {
            const restaurantDetailMap = new window.MacauMap();
            const restaurant = window.__RESTAURANT_DETAIL__.restaurant;

            restaurantDetailMap.init('restaurant-detail-map', [restaurant], {
                mode: 'restaurant',
                center: [Number(restaurant.latitude) || 22.1745, Number(restaurant.longitude) || 113.55],
                zoom: 16,
                singleMarkerZoom: 16,
            });
            window.restaurantDetailMap = restaurantDetailMap;
        }
    };

    const updateFavoriteButtonState = (button, isFavorited) => {
        const addLabel = button.getAttribute('data-favorite-label-add') || window.I18n?.t('actions.addToFavorites') || 'Add to favorites';
        const removeLabel = button.getAttribute('data-favorite-label-remove') || window.I18n?.t('actions.removeFromFavorites') || 'Remove favorite';
        const label = isFavorited ? removeLabel : addLabel;
        const card = button.closest('[data-food-card]');

        button.dataset.favorited = isFavorited ? 'true' : 'false';
        button.classList.toggle('is-active', isFavorited);
        button.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');

        if (button.classList.contains('food-card__favorite')) {
            button.textContent = isFavorited ? '♥' : '♡';
            button.setAttribute('title', label);
            button.setAttribute('aria-label', label);
        } else {
            button.textContent = label;
        }

        if (card) {
            card.dataset.favorited = isFavorited ? 'true' : 'false';
        }
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest(favoriteToggleSelector);

        if (
            !button
            || button.closest('[data-food-catalog]')
            || button.closest('[data-dashboard-favorites-grid]')
        ) {
            return;
        }

        const foodId = button.getAttribute('data-food-id');

        if (!foodId || (!window.api && !window.FavoriteStore)) {
            return;
        }

        event.preventDefault();
        button.disabled = true;

        try {
            const payload = window.FavoriteStore
                ? await window.FavoriteStore.toggle(foodId)
                : await window.api.post(`/api/favorites/${encodeURIComponent(foodId)}`);
            updateFavoriteButtonState(button, payload?.action === 'added');
        } catch (error) {
            console.error(error);
        } finally {
            button.disabled = false;
        }
    });

    if (dashboardFavoritesGrid && (window.api || window.FavoriteStore)) {
        dashboardFavoritesGrid.addEventListener('click', async (event) => {
            const button = event.target.closest(favoriteToggleSelector);

            if (!button) {
                return;
            }

            event.preventDefault();
            button.disabled = true;

            try {
                const foodId = button.getAttribute('data-food-id');
                const payload = window.FavoriteStore
                    ? await window.FavoriteStore.toggle(foodId || '')
                    : await window.api.post(`/api/favorites/${encodeURIComponent(foodId || '')}`);

                if (payload?.action === 'removed') {
                    const card = button.closest('[data-dashboard-favorite-card]');
                    card?.remove();

                    if (!dashboardFavoritesGrid.querySelector('[data-dashboard-favorite-card]')) {
                        const section = document.querySelector('.dashboard-favorites');

                        if (section) {
                            dashboardFavoritesGrid.remove();
                            const emptyState = document.createElement('div');
                            emptyState.className = 'dashboard-empty dashboard-empty--large';
                            emptyState.innerHTML = `
                                <div class="dashboard-favorites__empty-icon">🤍</div>
                                <h2 class="dashboard-favorites__empty-title" data-i18n="dashboard.favoritesEmptyTitle">No favorites yet</h2>
                                <p class="dashboard-favorites__empty-description" data-i18n="dashboard.favoritesEmptyLead">Start exploring the food catalog and tap the heart on dishes you want to revisit later.</p>
                            `;
                            section.appendChild(emptyState);
                            window.I18n?.applyTranslations(emptyState);
                        }
                    }
                }
            } catch (error) {
                console.error(error);
            } finally {
                button.disabled = false;
            }
        });
    }

    if (restaurantCatalogRoot && window.RestaurantCatalog) {
        window.restaurantCatalog = window.RestaurantCatalog.init({
            root: restaurantCatalogRoot,
            restaurants: Array.isArray(window.__RESTAURANTS__) ? window.__RESTAURANTS__ : [],
            foods: Array.isArray(window.__FOODS__) ? window.__FOODS__ : [],
            topRated: Array.isArray(window.__TOP_RATED__) ? window.__TOP_RATED__ : [],
            labels: window.__TASTE_OF_MACAU__?.labels || {},
        });
    }

    if (reviewRoot && window.ReviewManager) {
        window.reviewManager = window.ReviewManager.init({
            root: reviewRoot,
            restaurantId: window.__RESTAURANT_DETAIL__?.restaurant?.id,
            currentUserId: window.__RESTAURANT_DETAIL__?.currentUserId,
            labels: window.__RESTAURANT_DETAIL__?.labels || {},
        });
    }

    if (catalogRoot && window.FoodCatalog) {
        catalog = window.FoodCatalog.init({
            root: catalogRoot,
            gridSelector: '#food-cards-grid',
            countSelector: '[data-results-count]',
            noResultsSelector: '#catalog-no-results',
        });

        if (window.Search) {
            window.Search.init('#food-search', catalog);
        }
    }

    scheduleAfterFirstPaint(initMapViews);
    scheduleAfterFirstPaint(syncDetailMoreButtons);
    window.addEventListener('resize', syncDetailMoreButtons);
});
