(() => {
    const STORAGE_KEY = 'taste-of-macau:guest-favorites';

    const normalizeFoodId = (value) => {
        const id = Number.parseInt(String(value || ''), 10);
        return Number.isFinite(id) && id > 0 ? String(id) : '';
    };

    const readLocalIds = () => {
        try {
            const parsed = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(parsed)
                ? [...new Set(parsed.map(normalizeFoodId).filter(Boolean))]
                : [];
        } catch (error) {
            return [];
        }
    };

    const writeLocalIds = (ids) => {
        const normalized = [...new Set((Array.isArray(ids) ? ids : []).map(normalizeFoodId).filter(Boolean))];

        if (normalized.length === 0) {
            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch (error) {
                console.error(error);
            }
            return;
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
        } catch (error) {
            console.error(error);
        }
    };

    const isLoggedIn = () => (
        window.__APP_CONTEXT__?.isLoggedIn === true
        || window.__TASTE_OF_MACAU__?.isLoggedIn === true
    );

    let serverFavoriteIds = new Set(
        (Array.isArray(window.__TASTE_OF_MACAU__?.favoriteIds) ? window.__TASTE_OF_MACAU__.favoriteIds : [])
            .map(normalizeFoodId)
            .filter(Boolean)
    );

    const labelsFor = (button, isFavorited) => {
        const addLabel = button?.getAttribute('data-favorite-label-add') || window.I18n?.t('actions.addToFavorites') || 'Add to favorites';
        const removeLabel = button?.getAttribute('data-favorite-label-remove') || window.I18n?.t('actions.removeFromFavorites') || 'Remove favorite';

        return isFavorited ? removeLabel : addLabel;
    };

    const setButtonState = (button, isFavorited) => {
        if (!button) {
            return;
        }

        const label = labelsFor(button, isFavorited);

        button.dataset.favorited = isFavorited ? 'true' : 'false';
        button.classList.toggle('is-active', isFavorited);
        button.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');

        if (button.classList.contains('food-card__favorite')) {
            button.textContent = isFavorited ? '♥' : '♡';
            button.setAttribute('data-i18n-title', isFavorited ? 'actions.removeFromFavorites' : 'actions.addToFavorites');
            button.setAttribute('data-i18n-aria-label', isFavorited ? 'actions.removeFromFavorites' : 'actions.addToFavorites');
            button.setAttribute('title', label);
            button.setAttribute('aria-label', label);
            return;
        }

        button.textContent = label;
    };

    const applyFoodStateToBootstrap = (foodId, isFavorited) => {
        const id = normalizeFoodId(foodId);

        if (!id) {
            return;
        }

        const apply = (food) => (normalizeFoodId(food?.id) === id ? { ...food, is_favorited: isFavorited } : food);

        if (Array.isArray(window.__FOODS__)) {
            window.__FOODS__ = window.__FOODS__.map(apply);
        }

        if (Array.isArray(window.__TASTE_OF_MACAU__?.foods)) {
            window.__TASTE_OF_MACAU__.foods = window.__TASTE_OF_MACAU__.foods.map(apply);
        }

        if (window.__FOOD_DETAIL__ && normalizeFoodId(window.__FOOD_DETAIL__.id) === id) {
            window.__FOOD_DETAIL__.is_favorited = isFavorited;
        }

        if (Array.isArray(window.__RESTAURANT_DETAIL__?.restaurant?.foods)) {
            window.__RESTAURANT_DETAIL__.restaurant.foods = window.__RESTAURANT_DETAIL__.restaurant.foods.map(apply);
        }
    };

    const isFavorited = (foodId, fallback = false) => {
        const id = normalizeFoodId(foodId);

        if (!id) {
            return Boolean(fallback);
        }

        if (serverFavoriteIds.has(id)) {
            return true;
        }

        if (!isLoggedIn()) {
            return readLocalIds().includes(id);
        }

        return Boolean(fallback);
    };

    const hydrateDocument = (root = document) => {
        root.querySelectorAll('[data-food-card]').forEach((card) => {
            const foodId = normalizeFoodId(card.getAttribute('data-food-id'));

            if (!foodId) {
                return;
            }

            const active = isFavorited(foodId, card.getAttribute('data-favorited') === 'true');
            card.dataset.favorited = active ? 'true' : 'false';
            setButtonState(card.querySelector('[data-favorite-toggle]'), active);
        });

        root.querySelectorAll('[data-favorite-toggle]').forEach((button) => {
            const foodId = normalizeFoodId(button.getAttribute('data-food-id'));

            if (!foodId) {
                return;
            }

            setButtonState(button, isFavorited(foodId, button.getAttribute('data-favorited') === 'true'));
        });
    };

    const dispatchChange = () => {
        const favoriteIds = isLoggedIn() ? [...serverFavoriteIds] : readLocalIds();

        document.dispatchEvent(new CustomEvent('favorites:updated', {
            detail: { favoriteIds },
        }));
    };

    const setFavoriteState = (foodId, active) => {
        const id = normalizeFoodId(foodId);

        if (!id) {
            return;
        }

        if (isLoggedIn()) {
            if (active) {
                serverFavoriteIds.add(id);
            } else {
                serverFavoriteIds.delete(id);
            }
        } else {
            const localIds = readLocalIds();
            const nextIds = active
                ? [...localIds, id]
                : localIds.filter((item) => item !== id);
            writeLocalIds(nextIds);
        }

        applyFoodStateToBootstrap(id, active);
        hydrateDocument();
        dispatchChange();
    };

    const toggle = async (foodId) => {
        const id = normalizeFoodId(foodId);

        if (!id) {
            return { action: 'ignored', food_id: foodId };
        }

        if (isLoggedIn() && window.api) {
            const payload = await window.api.post(`/api/favorites/${encodeURIComponent(id)}`);
            const active = payload?.action === 'added';
            setFavoriteState(id, active);
            return payload;
        }

        const active = !readLocalIds().includes(id);
        setFavoriteState(id, active);

        return {
            action: active ? 'added' : 'removed',
            food_id: Number(id),
            local: true,
        };
    };

    const syncLocalToServer = async () => {
        if (!isLoggedIn() || !window.api) {
            return null;
        }

        const localIds = readLocalIds();

        if (localIds.length === 0) {
            return null;
        }

        const payload = await window.api.post('/api/favorites/sync', {
            food_ids: localIds.map((id) => Number(id)),
        });
        const syncedIds = Array.isArray(payload?.favorite_ids)
            ? payload.favorite_ids.map(normalizeFoodId).filter(Boolean)
            : localIds;

        serverFavoriteIds = new Set(syncedIds);
        window.localStorage.removeItem(STORAGE_KEY);
        syncedIds.forEach((id) => applyFoodStateToBootstrap(id, true));
        hydrateDocument();
        dispatchChange();

        return payload;
    };

    const applyToFoods = (foods) => (
        (Array.isArray(foods) ? foods : []).map((food) => ({
            ...food,
            is_favorited: isFavorited(food?.id, Boolean(food?.is_favorited)),
        }))
    );

    const getFavoriteIds = () => (isLoggedIn() ? [...serverFavoriteIds] : readLocalIds());

    window.FavoriteStore = {
        applyToFoods,
        getFavoriteIds,
        hydrateDocument,
        isFavorited,
        setButtonState,
        syncLocalToServer,
        toggle,
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (isLoggedIn()) {
            syncLocalToServer()
                .catch((error) => console.error(error))
                .finally(() => hydrateDocument());
            return;
        }

        hydrateDocument();
    });
})();
