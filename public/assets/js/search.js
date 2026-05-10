(() => {
    class SearchController {
        constructor(inputSelector, catalogInstance) {
            this.input = document.querySelector(inputSelector);
            this.catalog = catalogInstance;
            this.debounceTimer = null;

            if (!this.input || !this.catalog || !window.api) {
                return;
            }

            this.bindEvents();
        }

        static init(inputSelector, catalogInstance) {
            return new SearchController(inputSelector, catalogInstance);
        }

        bindEvents() {
            this.input.addEventListener('input', () => {
                window.clearTimeout(this.debounceTimer);
                this.debounceTimer = window.setTimeout(() => {
                    this.performSearch();
                }, 300);
            });

            document.addEventListener('catalog:category-change', () => {
                if (!this.input.value.trim()) {
                    this.catalog.filterByCategory(this.catalog.getActiveCategory());
                    return;
                }

                this.performSearch();
            });

            this.applyInitialQueryFromUrl();
        }

        applyInitialQueryFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const query = (params.get('q') || '').trim();
            const category = (params.get('category') || '').trim();

            if (category && typeof this.catalog.setActiveCategory === 'function') {
                this.catalog.setActiveCategory(category);
            }

            if (!query) {
                return;
            }

            this.input.value = query;
            this.performSearch();
        }

        async performSearch() {
            const query = this.input.value.trim();

            if (!query) {
                this.catalog.clearSearch();

                if (window.macauMap) {
                    window.macauMap.updateMarkers(this.catalog.getVisibleFoods());
                }

                return;
            }

            const params = new URLSearchParams({ q: query });
            const activeCategory = this.catalog.getActiveCategory();

            if (activeCategory && activeCategory !== 'all') {
                params.set('category', activeCategory);
            }

            try {
                const foods = await window.api.get(`/api/foods/search?${params.toString()}`);
                const results = Array.isArray(foods) ? foods : [];

                this.catalog.applySearchResults(results, query);

                if (window.macauMap) {
                    window.macauMap.updateMarkers(this.catalog.getVisibleFoods());
                }
            } catch (error) {
                this.catalog.applySearchResults([], query);

                if (window.macauMap) {
                    window.macauMap.updateMarkers([]);
                }

                console.error(error);
            }
        }
    }

    window.Search = {
        init(inputSelector, catalogInstance) {
            return SearchController.init(inputSelector, catalogInstance);
        },
    };
})();
