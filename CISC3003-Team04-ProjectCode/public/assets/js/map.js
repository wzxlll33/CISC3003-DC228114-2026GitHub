(() => {
    const MACAU_CENTER = [22.1745, 113.55];
    const CATEGORY_COLORS = {
        'street-snacks': '#1B67AD',
        portuguese: '#0F4E96',
        desserts: '#B45A3E',
        'main-dishes': '#0A3A73',
    };
    const DEFAULT_MARKER_COLOR = '#244563';

    class MacauMap {
        constructor(options = {}) {
            this.map = null;
            this.markersLayer = null;
            this.markersByFoodId = new Map();
            this.markersByRestaurantId = new Map();
            this.currentFoods = [];
            this.currentRestaurants = [];
            this.userMarker = null;
            this.searchLocationMarker = null;
            this.selectedRestaurantId = null;
            this.selectionControl = null;
            this.containerId = null;
            this.options = options;
            this.mode = options.mode || 'food';
            this.clusterRefreshQueued = false;
            this.pendingRestaurantPopupTimer = null;
        }

        init(containerId, items = [], options = {}) {
            if (!window.L) {
                return null;
            }

            this.ensureStyles();

            const container = document.getElementById(containerId);

            if (!container) {
                return null;
            }

            this.containerId = containerId;
            this.options = { ...this.options, ...options };
            this.mode = this.options.mode || 'food';

            if (this.map) {
                this.map.remove();
            }

            this.userMarker = null;
            this.map = L.map(containerId, {
                scrollWheelZoom: true,
                zoomControl: true,
                preferCanvas: true,
            }).setView(this.options.center || MACAU_CENTER, this.options.zoom || 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                updateWhenIdle: true,
                updateWhenZooming: false,
                keepBuffer: 1,
                crossOrigin: true,
            }).addTo(this.map);

            this.markersLayer = L.layerGroup().addTo(this.map);
            this.bindMapEvents();

            if (this.mode === 'restaurant') {
                this.loadRestaurantMarkers(items);
            } else {
                this.loadMarkers(items);
            }

            window.setTimeout(() => {
                this.map?.invalidateSize();
            }, 150);

            return this;
        }

        loadMarkers(foods = []) {
            if (!this.map || !this.markersLayer) {
                return;
            }

            this.mode = 'food';
            this.clearMarkers();
            this.currentFoods = this.normalizeFoods(foods);

            this.currentFoods.forEach((food) => {
                const marker = this.createFoodMarker(food);

                if (!marker) {
                    return;
                }

                marker.foodId = food.id;
                marker.addTo(this.markersLayer);
                this.markersByFoodId.set(String(food.id), marker);
            });

            this.fitBounds();
        }

        loadRestaurantMarkers(restaurants = []) {
            if (!this.map || !this.markersLayer) {
                return;
            }

            this.mode = 'restaurant';
            this.clearMarkers();
            this.currentRestaurants = this.normalizeRestaurants(restaurants);

            this.renderRestaurantMarkers();

            this.fitBounds();
        }

        updateMarkers(foods = []) {
            this.loadMarkers(foods);
        }

        updateRestaurantMarkers(restaurants = []) {
            this.clearRestaurantSelection({ preserveMarkers: true });
            this.loadRestaurantMarkers(restaurants);
        }

        flyToFood(foodId) {
            const marker = this.markersByFoodId.get(String(foodId));
            this.flyToMarker(marker, String(foodId), 'food');
        }

        flyToRestaurant(restaurantId, options = {}) {
            const marker = this.markersByRestaurantId.get(String(restaurantId));

            if (marker) {
                this.flyToMarker(marker, String(restaurantId), 'restaurant', options);
                return;
            }

            const restaurant = this.currentRestaurants.find((item) => String(item.id) === String(restaurantId));

            if (!this.map || !restaurant || !this.hasCoordinates(restaurant)) {
                return;
            }

            const position = [Number(restaurant.latitude), Number(restaurant.longitude)];
            const zoom = Math.max(this.map.getZoom() + 2, this.options.clusterExpandZoom || 17);
            const center = this.getFramedCenter(L.latLng(position), zoom);

            this.map.flyTo(center, zoom, { duration: 0.6 });

            window.clearTimeout(this.pendingRestaurantPopupTimer);
            this.pendingRestaurantPopupTimer = window.setTimeout(() => {
                this.renderRestaurantMarkers();
                this.openRestaurantPopup(restaurantId);
            }, 650);
        }

        selectRestaurant(restaurantId) {
            const id = String(restaurantId || '');

            if (!id || !this.map || this.mode !== 'restaurant') {
                return;
            }

            const restaurant = this.currentRestaurants.find((item) => String(item.id) === id);

            if (!restaurant || !this.hasCoordinates(restaurant)) {
                return;
            }

            const isSameSelection = this.selectedRestaurantId === id;
            this.selectedRestaurantId = id;
            if (!isSameSelection) {
                this.renderRestaurantMarkers();
            }
            this.showSelectionControl();
            this.flyToRestaurant(id, { stable: isSameSelection });
        }

        clearRestaurantSelection(options = {}) {
            if (!this.selectedRestaurantId && !this.selectionControl) {
                return;
            }

            this.selectedRestaurantId = null;
            this.removeSelectionControl();

            if (options.preserveMarkers) {
                return;
            }

            this.renderRestaurantMarkers();
            this.fitBounds();
        }

        flyToMarker(marker, id, type, options = {}) {
            if (!this.map || !marker) {
                return;
            }

            const position = marker.getLatLng();
            const targetZoom = this.options.singleMarkerZoom || 16;
            const targetCenter = this.getFramedCenter(position, targetZoom, options);
            const currentCenter = this.map.getCenter();
            const isAlreadyFramed = this.map.getZoom() >= targetZoom && currentCenter.distanceTo(targetCenter) < 18;

            if (!isAlreadyFramed || !options.stable) {
                this.map.flyTo(targetCenter, targetZoom, { duration: 0.6 });
            }

            if (type === 'restaurant') {
                if (isAlreadyFramed && options.stable) {
                    this.openRestaurantPopup(id, marker);
                    return;
                }

                window.clearTimeout(this.pendingRestaurantPopupTimer);
                this.pendingRestaurantPopupTimer = window.setTimeout(() => {
                    this.renderRestaurantMarkers();
                    this.openRestaurantPopup(id);
                }, 650);
                return;
            }

            marker.openPopup();
            this.highlightMarkerByType(type, id, 1800);
        }

        getFramedCenter(latLng, zoom, options = {}) {
            const position = L.latLng(latLng);

            if (!this.map || options.rawCenter || !this.isMobileViewport()) {
                return position;
            }

            const container = this.map.getContainer();
            const containerRect = container?.getBoundingClientRect?.();

            if (!containerRect?.height) {
                return position;
            }

            const page = container.closest?.('.explore-page--map-first') || document.querySelector('.explore-page--map-first');

            if (!page) {
                return position;
            }

            const searchShell = page.classList.contains('is-search-hidden')
                ? null
                : page.querySelector('.explore-search-shell');
            const resultsPanel = page.querySelector('.explore-results-panel:not([hidden])');
            let topInset = 0;
            let bottomInset = 0;

            if (searchShell) {
                const rect = searchShell.getBoundingClientRect();
                topInset = Math.max(0, rect.bottom - containerRect.top + 8);
            }

            if (resultsPanel) {
                const rect = resultsPanel.getBoundingClientRect();
                if (rect.top < containerRect.bottom) {
                    bottomInset = Math.max(0, containerRect.bottom - Math.max(containerRect.top, rect.top) + 8);
                }
            }

            topInset = Math.min(topInset, containerRect.height * 0.42);
            bottomInset = Math.min(bottomInset, containerRect.height * 0.62);

            const visibleHeight = Math.max(160, containerRect.height - topInset - bottomInset);
            const popupAllowance = 62;
            const desiredMarkerY = topInset + visibleHeight / 2 + popupAllowance;
            const yOffset = Math.max(
                -containerRect.height * 0.34,
                Math.min(containerRect.height * 0.30, desiredMarkerY - containerRect.height / 2)
            );

            if (Math.abs(yOffset) < 4) {
                return position;
            }

            const targetPoint = this.map.project(position, zoom).subtract(L.point(0, yOffset));
            return this.map.unproject(targetPoint, zoom);
        }

        isMobileViewport() {
            return typeof window !== 'undefined'
                && typeof window.matchMedia === 'function'
                && window.matchMedia('(max-width: 640px)').matches;
        }

        openRestaurantPopup(restaurantId, marker = null) {
            const focusedMarker = marker || this.markersByRestaurantId.get(String(restaurantId));

            if (!focusedMarker) {
                return;
            }

            focusedMarker.openPopup();
            this.nudgePopupIntoMobileView();
            this.highlightRestaurantMarker(restaurantId, 1800);
        }

        nudgePopupIntoMobileView() {
            if (!this.map || !this.isMobileViewport()) {
                return;
            }

            window.requestAnimationFrame(() => {
                const container = this.map?.getContainer();
                const popup = container?.querySelector?.('.leaflet-popup');

                if (!container || !popup) {
                    return;
                }

                const containerRect = container.getBoundingClientRect();
                const popupRect = popup.getBoundingClientRect();
                const page = container.closest?.('.explore-page--map-first') || document.querySelector('.explore-page--map-first');
                let minTop = containerRect.top + 8;
                let maxBottom = containerRect.bottom - 8;

                if (page && !page.classList.contains('is-search-hidden')) {
                    const searchShell = page.querySelector('.explore-search-shell');
                    const searchRect = searchShell?.getBoundingClientRect?.();

                    if (searchRect) {
                        minTop = Math.max(minTop, searchRect.bottom + 8);
                    }
                }

                if (page) {
                    const resultsPanel = page.querySelector('.explore-results-panel:not([hidden])');
                    const resultsRect = resultsPanel?.getBoundingClientRect?.();

                    if (resultsRect && resultsRect.top < containerRect.bottom) {
                        maxBottom = Math.min(maxBottom, resultsRect.top - 8);
                    }
                }

                if (maxBottom - minTop < popupRect.height + 8) {
                    maxBottom = containerRect.bottom - 8;
                }

                let visualDeltaY = 0;

                if (popupRect.top < minTop) {
                    visualDeltaY = minTop - popupRect.top;
                } else if (popupRect.bottom > maxBottom) {
                    visualDeltaY = maxBottom - popupRect.bottom;
                }

                if (Math.abs(visualDeltaY) < 4) {
                    return;
                }

                const zoom = this.map.getZoom();
                const centerPoint = this.map.project(this.map.getCenter(), zoom).subtract(L.point(0, visualDeltaY));
                this.map.panTo(this.map.unproject(centerPoint, zoom), { animate: true, duration: 0.25 });
            });
        }

        highlightCard(foodId) {
            const card = document.querySelector(`[data-food-card][data-food-id="${String(foodId)}"]`);
            this.highlightDomCard(card, 'food-card--highlighted');
        }

        highlightRestaurantCard(restaurantId) {
            const card = document.querySelector(`[data-restaurant-card][data-restaurant-id="${String(restaurantId)}"]`);
            this.highlightDomCard(card, 'restaurant-card--highlighted');
        }

        highlightDomCard(card, className) {
            if (!card) {
                return;
            }

            card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            card.classList.remove(className);
            void card.offsetWidth;
            card.classList.add(className);

            window.setTimeout(() => {
                card.classList.remove(className);
            }, 2000);
        }

        highlightMarker(foodId, duration = 1200) {
            this.highlightMarkerByType('food', String(foodId), duration);
        }

        highlightRestaurantMarker(restaurantId, duration = 1200) {
            this.highlightMarkerByType('restaurant', String(restaurantId), duration);
        }

        highlightMarkerByType(type, id, duration = 1200) {
            const marker = type === 'restaurant'
                ? this.markersByRestaurantId.get(String(id))
                : this.markersByFoodId.get(String(id));

            if (!marker) {
                return;
            }

            const element = marker.getElement();

            if (!element) {
                return;
            }

            element.classList.remove('map-marker--active');
            void element.offsetWidth;
            element.classList.add('map-marker--active');

            window.setTimeout(() => {
                element.classList.remove('map-marker--active');
            }, duration);
        }

        showUserLocation(lat, lng) {
            if (!this.map || !Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) {
                return;
            }

            const position = [Number(lat), Number(lng)];

            if (this.userMarker) {
                this.userMarker.setLatLng(position);
            } else {
                this.userMarker = L.marker(position, {
                    icon: this.createUserLocationIcon(),
                    keyboard: false,
                    title: 'Your location',
                }).addTo(this.map);
            }

            this.map.flyTo(position, 14, { duration: 0.7 });
            this.fitBounds();
        }

        showSearchLocation(lat, lng, label = '') {
            if (!this.map || !Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) {
                return;
            }

            const position = [Number(lat), Number(lng)];
            const safeLabel = String(label || '').trim() || 'Selected location';

            if (this.searchLocationMarker) {
                this.searchLocationMarker.setLatLng(position);
                this.searchLocationMarker.setPopupContent(this.buildSearchLocationPopupContent(safeLabel));
            } else {
                this.searchLocationMarker = L.marker(position, {
                    icon: this.createSearchLocationIcon(),
                    keyboard: false,
                    title: safeLabel,
                }).addTo(this.map);
                this.searchLocationMarker.bindPopup(this.buildSearchLocationPopupContent(safeLabel), {
                    className: 'map-popup-wrapper map-popup-wrapper--location',
                    closeButton: false,
                    closeOnClick: false,
                    autoPan: false,
                    offset: [0, -20],
                });
            }

            this.map.flyTo(position, Math.max(this.map.getZoom(), 15), { duration: 0.6 });
            this.searchLocationMarker.openPopup();
        }

        removeUserLocation() {
            if (!this.map || !this.userMarker) {
                return;
            }

            this.map.removeLayer(this.userMarker);
            this.userMarker = null;
            this.fitBounds();
        }

        removeSearchLocation() {
            if (!this.map || !this.searchLocationMarker) {
                return;
            }

            this.map.removeLayer(this.searchLocationMarker);
            this.searchLocationMarker = null;
        }

        showSelectionControl() {
            if (!this.map || this.selectionControl) {
                return;
            }

            const labels = window.__TASTE_OF_MACAU__?.labels || {};
            const buttonLabel = labels.clearMapSelection || 'Clear selection';
            const control = L.control({ position: 'topright' });

            control.onAdd = () => {
                const button = L.DomUtil.create('button', 'map-selection-clear');
                button.type = 'button';
                button.textContent = buttonLabel;
                button.setAttribute('aria-label', buttonLabel);
                L.DomEvent.disableClickPropagation(button);
                L.DomEvent.disableScrollPropagation(button);
                L.DomEvent.on(button, 'click', (event) => {
                    L.DomEvent.preventDefault(event);
                    this.clearRestaurantSelection();
                });

                return button;
            };

            control.addTo(this.map);
            this.selectionControl = control;
        }

        removeSelectionControl() {
            if (!this.map || !this.selectionControl) {
                this.selectionControl = null;
                return;
            }

            this.map.removeControl(this.selectionControl);
            this.selectionControl = null;
        }

        fitBounds() {
            if (!this.map || !this.markersLayer) {
                return;
            }

            const activeItems = this.mode === 'restaurant'
                ? this.currentRestaurants
                : this.currentFoods;
            const locations = activeItems
                .filter((item) => this.hasCoordinates(item))
                .map((item) => L.latLng(Number(item.latitude), Number(item.longitude)));

            if (this.userMarker) {
                locations.push(this.userMarker.getLatLng());
            }

            if (this.searchLocationMarker) {
                locations.push(this.searchLocationMarker.getLatLng());
            }

            if (locations.length === 0) {
                this.map.setView(this.options.center || MACAU_CENTER, this.options.zoom || 13);
                return;
            }

            if (locations.length === 1) {
                this.map.setView(locations[0], this.options.singleMarkerZoom || 15);
                return;
            }

            this.map.fitBounds(L.latLngBounds(locations), { padding: [32, 32] });
        }

        bindMapEvents() {
            if (!this.map) {
                return;
            }

            this.map.on('zoomend', () => {
                if (this.mode !== 'restaurant') {
                    return;
                }

                this.queueRestaurantMarkerRender();
            });
        }

        queueRestaurantMarkerRender() {
            if (this.clusterRefreshQueued) {
                return;
            }

            this.clusterRefreshQueued = true;
            window.requestAnimationFrame(() => {
                this.clusterRefreshQueued = false;
                this.renderRestaurantMarkers();
            });
        }

        renderRestaurantMarkers() {
            if (!this.map || !this.markersLayer || this.mode !== 'restaurant') {
                return;
            }

            this.markersLayer.clearLayers();
            this.markersByRestaurantId.clear();

            const sourceRestaurants = this.selectedRestaurantId
                ? this.currentRestaurants.filter((restaurant) => String(restaurant.id) === this.selectedRestaurantId)
                : this.currentRestaurants;
            const groups = this.selectedRestaurantId
                ? sourceRestaurants.map((restaurant) => this.createClusterGroup([restaurant]))
                : this.clusterRestaurants(sourceRestaurants);
            const hasVisibleClusters = groups.some((group) => group.items.length > 1);

            groups.forEach((group) => {
                if (group.items.length > 1) {
                    this.createRestaurantClusterMarker(group).addTo(this.markersLayer);
                    return;
                }

                const restaurant = group.items[0];
                const marker = this.createRestaurantMarker(restaurant, {
                    showRating: !hasVisibleClusters,
                });

                if (!marker) {
                    return;
                }

                marker.restaurantId = restaurant.id;
                marker.addTo(this.markersLayer);
                this.markersByRestaurantId.set(String(restaurant.id), marker);
            });
        }

        clusterRestaurants(restaurants) {
            const list = this.normalizeRestaurants(restaurants);
            const zoom = this.map?.getZoom() || this.options.zoom || 13;
            const maxClusterZoom = Number(this.options.clusterMaxZoom || 16);

            if (!this.map || zoom >= maxClusterZoom) {
                return list.map((restaurant) => this.createClusterGroup([restaurant]));
            }

            const radius = Number(this.options.clusterPixelRadius || 54);
            const groups = [];

            list.forEach((restaurant) => {
                const latLng = L.latLng(Number(restaurant.latitude), Number(restaurant.longitude));
                const point = this.map.project(latLng, zoom);
                const targetGroup = groups.find((group) => point.distanceTo(group.centerPoint) <= radius);

                if (targetGroup) {
                    targetGroup.items.push(restaurant);
                    targetGroup.centerPoint = L.point(
                        (targetGroup.centerPoint.x * (targetGroup.items.length - 1) + point.x) / targetGroup.items.length,
                        (targetGroup.centerPoint.y * (targetGroup.items.length - 1) + point.y) / targetGroup.items.length
                    );
                    targetGroup.latLng = this.averageLatLng(targetGroup.items);
                    return;
                }

                groups.push({
                    items: [restaurant],
                    centerPoint: point,
                    latLng,
                });
            });

            return groups;
        }

        createClusterGroup(items) {
            return {
                items,
                centerPoint: this.map.project(L.latLng(Number(items[0].latitude), Number(items[0].longitude)), this.map.getZoom()),
                latLng: this.averageLatLng(items),
            };
        }

        averageLatLng(items) {
            const totals = items.reduce((sum, item) => ({
                lat: sum.lat + Number(item.latitude),
                lng: sum.lng + Number(item.longitude),
            }), { lat: 0, lng: 0 });

            return L.latLng(totals.lat / items.length, totals.lng / items.length);
        }

        createRestaurantClusterMarker(group) {
            const count = group.items.length;
            const marker = L.marker(group.latLng, {
                icon: this.createClusterIcon(count),
                keyboard: true,
                title: `${count} restaurants in this area`,
            });

            marker.on('click', () => {
                this.expandCluster(group);
            });

            return marker;
        }

        createClusterIcon(count) {
            return L.divIcon({
                className: 'map-marker-wrapper map-cluster-wrapper',
                html: `
                    <span class="map-marker map-marker--cluster map-cluster" style="--marker-color: #0A3A73" aria-hidden="true">
                        <span class="map-marker__core">${this.escapeHtml(String(count))}</span>
                    </span>
                `,
                iconSize: [70, 70],
                iconAnchor: [35, 35],
            });
        }

        expandCluster(group) {
            if (!this.map || !Array.isArray(group.items) || group.items.length === 0) {
                return;
            }

            const locations = group.items.map((restaurant) => L.latLng(Number(restaurant.latitude), Number(restaurant.longitude)));
            const bounds = L.latLngBounds(locations);
            const expandZoom = this.options.clusterExpandZoom || 17;

            if (bounds.getNorthEast().equals(bounds.getSouthWest())) {
                this.map.flyTo(group.latLng, expandZoom, { duration: 0.7 });
                return;
            }

            this.map.fitBounds(bounds, {
                padding: [56, 56],
                maxZoom: expandZoom,
            });
        }

        clearMarkers() {
            if (this.markersLayer) {
                this.markersLayer.clearLayers();
            }

            this.markersByFoodId.clear();
            this.markersByRestaurantId.clear();
        }

        createFoodMarker(food) {
            if (!this.hasCoordinates(food)) {
                return null;
            }

            const marker = L.marker([Number(food.latitude), Number(food.longitude)], {
                icon: this.createFoodMarkerIcon(food),
                keyboard: true,
                title: food.name || '',
            });

            marker.bindPopup(this.buildFoodPopupContent(food), {
                className: 'map-popup-wrapper',
                closeButton: false,
                closeOnClick: false,
                offset: [0, -26],
            });

            marker.on('click', () => {
                this.highlightCard(food.id);
                this.highlightMarker(food.id, 1800);
            });

            return marker;
        }

        createRestaurantMarker(restaurant, options = {}) {
            if (!this.hasCoordinates(restaurant)) {
                return null;
            }

            const marker = L.marker([Number(restaurant.latitude), Number(restaurant.longitude)], {
                icon: this.createRestaurantMarkerIcon(restaurant, options),
                keyboard: true,
                title: restaurant.name || '',
            });

            marker.bindPopup(this.buildRestaurantPopupContent(restaurant), {
                className: 'map-popup-wrapper',
                closeButton: false,
                closeOnClick: false,
                autoPan: false,
                keepInView: false,
                offset: [0, -26],
            });

            marker.on('click', () => {
                this.highlightRestaurantCard(restaurant.id);
                this.highlightRestaurantMarker(restaurant.id, 1800);
            });

            return marker;
        }

        createFoodMarkerIcon(food) {
            const color = CATEGORY_COLORS[food.category_slug] || DEFAULT_MARKER_COLOR;
            const label = this.escapeHtml(this.getFoodMarkerLabel(food));

            return this.buildMarkerIcon(color, label);
        }

        createRestaurantMarkerIcon(restaurant, options = {}) {
            const color = this.getRestaurantMarkerColor(restaurant);
            const showRating = Boolean(options.showRating);

            if (showRating) {
                return this.buildMarkerIcon(color, this.getRestaurantMarkerLabel(restaurant), 'map-marker--rating');
            }

            return this.buildMarkerIcon(color, '<span class="map-marker__dot"></span>', 'map-marker--restaurant');
        }

        buildMarkerIcon(color, label, modifierClass = '') {
            const markerClass = `map-marker ${modifierClass}`.trim();

            return L.divIcon({
                className: 'map-marker-wrapper',
                html: `
                    <span class="${markerClass}" style="--marker-color: ${color}" aria-hidden="true">
                        <span class="map-marker__core">${label}</span>
                    </span>
                `,
                iconSize: [70, 70],
                iconAnchor: [35, 35],
                popupAnchor: [0, -32],
            });
        }

        getRestaurantMarkerColor(restaurant) {
            const rating = Number(restaurant.avg_rating || 0);
            const price = String(restaurant.price_range || '');

            if (rating >= 4.5) {
                return '#0F4E96';
            }

            if (price.includes('$$$') || price.includes('MOP 300')) {
                return '#0A3A73';
            }

            if (price.includes('$')) {
                return '#1B67AD';
            }

            return '#B45A3E';
        }

        getFoodMarkerLabel(food) {
            const category = String(food.category_slug || '').trim();
            const labels = {
                'street-snacks': 'S',
                portuguese: 'P',
                desserts: 'D',
                'main-dishes': 'M',
            };

            if (labels[category]) {
                return labels[category];
            }

            const source = String(food.category_name || food.name || 'M').trim();
            const first = Array.from(source).find((char) => /[\p{L}\p{N}]/u.test(char));

            return first ? first.toUpperCase() : 'M';
        }

        getRestaurantMarkerLabel(restaurant) {
            const rating = Number(restaurant.avg_rating || 0);

            if (Number.isFinite(rating) && rating > 0) {
                const [whole, decimal = '0'] = this.formatRating(rating).split('.');

                return `${this.escapeHtml(whole)}<em>.${this.escapeHtml(decimal)}</em>`;
            }

            return '<em>R</em>';
        }

        createUserLocationIcon() {
            return L.divIcon({
                className: 'map-user-marker-wrapper',
                html: '<span class="map-user-marker" aria-hidden="true"></span>',
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            });
        }

        createSearchLocationIcon() {
            return L.divIcon({
                className: 'map-search-location-wrapper',
                html: '<span class="map-search-location-marker" aria-hidden="true"></span>',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
            });
        }

        buildFoodPopupContent(food) {
            const safeImage = this.escapeAttribute(food.image_url || '');
            const safeName = this.escapeHtml(food.name || '');
            const safeCategory = this.escapeHtml(`${food.category_icon || ''} ${food.category_name || ''}`.trim());
            const safeArea = this.escapeHtml(food.area || '');
            const detailUrl = `/food/${encodeURIComponent(String(food.id || ''))}`;
            const detailsLabel = window.I18n?.t('actions.viewDetails') || 'View Details';

            return `
                <div class="map-popup">
                    <img src="${safeImage}" alt="${this.escapeAttribute(food.name || '')}" class="map-popup__image" loading="lazy" decoding="async">
                    <h4 class="map-popup__title">${safeName}</h4>
                    <p class="map-popup__category">${safeCategory}</p>
                    <p class="map-popup__area">${safeArea}</p>
                    <a href="${detailUrl}" class="map-popup__link">${this.escapeHtml(detailsLabel)} →</a>
                </div>
            `;
        }

        buildRestaurantPopupContent(restaurant) {
            const safeImage = this.escapeAttribute(restaurant.image_url || '');
            const safeName = this.escapeHtml(restaurant.name || '');
            const safeArea = this.escapeHtml(restaurant.area || '');
            const safePrice = this.escapeHtml(restaurant.price_range || '');
            const safeRating = this.escapeHtml(this.formatRating(restaurant.avg_rating));
            const reviewCount = Number(restaurant.review_count || 0);
            const detailUrl = `/restaurant/${encodeURIComponent(String(restaurant.id || ''))}`;
            const labels = window.__TASTE_OF_MACAU__?.labels || {};
            const reviewsLabel = labels.reviews || 'reviews';
            const detailsLabel = labels.viewDetails || window.I18n?.t('actions.viewDetails') || 'View Details';
            const tags = Array.isArray(restaurant.tags)
                ? restaurant.tags.slice(0, 3).map((tag) => `<span class="map-popup__tag">${this.escapeHtml(tag.name || '')}</span>`).join('')
                : '';

            return `
                <div class="map-popup">
                    <img src="${safeImage}" alt="${this.escapeAttribute(restaurant.name || '')}" class="map-popup__image" loading="lazy" decoding="async">
                    <h4 class="map-popup__title">${safeName}</h4>
                    <p class="map-popup__category">★ ${safeRating} · ${reviewCount} ${this.escapeHtml(reviewsLabel)}</p>
                    <p class="map-popup__area">📍 ${safeArea} · ${safePrice}</p>
                    <div class="map-popup__tags">${tags}</div>
                    <a href="${detailUrl}" class="map-popup__link">${this.escapeHtml(detailsLabel)} →</a>
                </div>
            `;
        }

        buildSearchLocationPopupContent(label) {
            return `
                <div class="map-popup map-popup--location">
                    <h4 class="map-popup__title">${this.escapeHtml(label)}</h4>
                </div>
            `;
        }

        normalizeFoods(foods) {
            return (Array.isArray(foods) ? foods : []).filter((food) => this.hasCoordinates(food));
        }

        normalizeRestaurants(restaurants) {
            return (Array.isArray(restaurants) ? restaurants : []).filter((restaurant) => this.hasCoordinates(restaurant));
        }

        hasCoordinates(entity) {
            return entity && Number.isFinite(Number(entity.latitude)) && Number.isFinite(Number(entity.longitude));
        }

        formatRating(value) {
            const rating = Number(value || 0);

            return rating.toFixed(2).replace(/\.?0+$/, '');
        }

        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        escapeAttribute(value) {
            return this.escapeHtml(value);
        }

        ensureStyles() {
            if (document.getElementById('macau-map-runtime-styles')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'macau-map-runtime-styles';
            style.textContent = `
                .map-marker-wrapper {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    border: 0;
                    overflow: visible;
                }

                .map-marker {
                    --marker-color: #0A3A73;
                    position: relative;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 42px;
                    height: 42px;
                    border: 0;
                    border-radius: 999px;
                    background: var(--marker-color);
                    color: var(--marker-color);
                    font-size: 1.05rem;
                    font-weight: 800;
                    letter-spacing: 0;
                    box-shadow:
                        0 0 0 5px rgba(255, 255, 255, 0.94),
                        0 0 18px 10px rgba(255, 255, 255, 0.42),
                        0 7px 14px rgba(8, 35, 74, 0.18);
                    isolation: isolate;
                    transform-origin: center;
                }

                .map-marker::before {
                    content: '';
                    position: absolute;
                    inset: -18px;
                    z-index: -1;
                    border-radius: inherit;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0) 0 46%, rgba(255, 255, 255, 0.82) 58%, rgba(255, 255, 255, 0.42) 70%, rgba(255, 255, 255, 0.16) 82%, rgba(255, 255, 255, 0) 94%);
                    filter: blur(6px);
                    pointer-events: none;
                }

                .map-marker::after {
                    content: '';
                    position: absolute;
                    inset: 5px;
                    z-index: 0;
                    border-radius: inherit;
                    background: #FFFDF8;
                    box-shadow: inset 0 0 0 1px rgba(10, 58, 115, 0.10);
                    pointer-events: none;
                }

                .map-marker__core {
                    position: relative;
                    z-index: 2;
                    line-height: 1;
                    color: inherit;
                    font-variant-numeric: tabular-nums;
                }

                .map-marker__core em {
                    color: #0A3A73;
                    font-style: normal;
                }

                .map-marker--rating .map-marker__core {
                    font-size: 0.82rem;
                    font-weight: 800;
                }

                .map-marker--rating .map-marker__core em {
                    font-size: 0.72em;
                }

                .map-marker--restaurant .map-marker__core {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .map-marker--cluster .map-marker__core {
                    color: #0A3A73;
                    font-size: 1rem;
                    font-weight: 800;
                }

                .map-marker__dot {
                    display: inline-block;
                    width: 8px;
                    height: 8px;
                    border-radius: 999px;
                    background: #0A3A73;
                    box-shadow: 0 0 0 2px rgba(10, 58, 115, 0.12);
                }

                .map-marker--active .map-marker {
                    animation: map-marker-pulse 0.9s ease-in-out 2;
                }

                .map-cluster-wrapper {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    border: 0;
                    color: #0A3A73 !important;
                    overflow: visible;
                }

                .map-cluster {
                    --marker-color: #0A3A73;
                }

                .map-popup {
                    width: 220px;
                }

                .map-popup__image {
                    width: 100%;
                    height: 112px;
                    object-fit: cover;
                    border-radius: 12px;
                    margin-bottom: 12px;
                }

                .map-popup__title {
                    margin: 0 0 8px;
                    color: #0A3A73;
                    font-size: 1rem;
                }

                .map-popup__category,
                .map-popup__area {
                    margin: 0 0 6px;
                    color: #244563;
                    font-size: 0.9rem;
                }

                .map-popup__tags {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.35rem;
                    margin-top: 0.5rem;
                }

                .map-popup__tag {
                    display: inline-flex;
                    padding: 0.25rem 0.55rem;
                    border-radius: 999px;
                    background: rgba(15, 78, 150, 0.12);
                    color: #0A3A73;
                    font-size: 0.75rem;
                }

                .map-popup__link {
                    display: inline-flex;
                    margin-top: 8px;
                    color: #0F4E96;
                    font-weight: 700;
                    text-decoration: none;
                }

                .leaflet-popup-content-wrapper {
                    border-radius: 18px;
                    padding: 4px;
                }

                .leaflet-popup-content {
                    margin: 12px;
                }

                .map-user-marker-wrapper {
                    background: transparent;
                    border: 0;
                }

                .map-user-marker {
                    display: inline-flex;
                    width: 22px;
                    height: 22px;
                    border-radius: 999px;
                    background: #2563eb;
                    border: 4px solid rgba(255, 255, 255, 0.96);
                    box-shadow: 0 0 0 7px rgba(37, 99, 235, 0.18), 0 10px 22px rgba(37, 99, 235, 0.22);
                }

                .map-selection-clear {
                    min-height: 36px;
                    padding: 0 14px;
                    border: 1px solid rgba(15, 78, 150, 0.24);
                    border-radius: 10px;
                    background: rgba(250, 248, 240, 0.96);
                    color: #0A3A73;
                    font: inherit;
                    font-size: 0.84rem;
                    font-weight: 800;
                    line-height: 1;
                    box-shadow: 0 12px 28px rgba(8, 35, 74, 0.14);
                    cursor: pointer;
                }

                .map-selection-clear:hover,
                .map-selection-clear:focus-visible {
                    border-color: rgba(15, 78, 150, 0.46);
                    background: rgba(15, 78, 150, 0.10);
                    outline: 0;
                }

                @media (max-width: 640px) {
                    .map-popup {
                        width: min(158px, calc(100vw - 148px));
                    }

                    .map-popup__image {
                        height: 46px;
                        border-radius: 9px;
                        margin-bottom: 5px;
                    }

                    .map-popup__title {
                        margin-bottom: 3px;
                        overflow: hidden;
                        font-size: 0.82rem;
                        line-height: 1.15;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .map-popup__category,
                    .map-popup__area {
                        margin-bottom: 2px;
                        overflow: hidden;
                        font-size: 0.7rem;
                        line-height: 1.25;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .map-popup__tags {
                        gap: 0.2rem;
                        margin-top: 0.25rem;
                        max-height: 1.2rem;
                        overflow: hidden;
                        flex-wrap: nowrap;
                    }

                    .map-popup__tag {
                        flex: 0 1 auto;
                        max-width: 100%;
                        padding: 0.12rem 0.34rem;
                        border-radius: 8px;
                        overflow: hidden;
                        font-size: 0.6rem;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .map-popup__link {
                        margin-top: 4px;
                        font-size: 0.68rem;
                        line-height: 1.1;
                    }

                    .map-popup-wrapper .leaflet-popup-content-wrapper {
                        border-radius: 14px;
                        padding: 2px;
                    }

                    .map-popup-wrapper .leaflet-popup-content {
                        margin: 6px;
                    }
                }

                @keyframes map-marker-pulse {
                    0% {
                        transform: scale(1);
                        box-shadow:
                            0 0 0 5px rgba(255, 255, 255, 0.94),
                            0 0 18px 10px rgba(255, 255, 255, 0.42),
                            0 8px 18px rgba(8, 35, 74, 0.22);
                    }
                    50% {
                        transform: scale(1.14);
                        box-shadow:
                            0 0 0 7px rgba(255, 255, 255, 0.92),
                            0 0 24px 14px rgba(255, 255, 255, 0.48),
                            0 10px 24px rgba(8, 35, 74, 0.28);
                    }
                    100% {
                        transform: scale(1);
                        box-shadow:
                            0 0 0 5px rgba(255, 255, 255, 0.94),
                            0 0 18px 10px rgba(255, 255, 255, 0.42),
                            0 8px 18px rgba(8, 35, 74, 0.22);
                    }
                }
            `;

            document.head.appendChild(style);
        }
    }

    window.MacauMap = MacauMap;
})();
