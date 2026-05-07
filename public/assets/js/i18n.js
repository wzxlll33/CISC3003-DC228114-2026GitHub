(() => {
    const translations = {
        en: {
            nav: { home: 'Home', login: 'Login', register: 'Register', logout: 'Logout', dashboard: 'My', backHome: 'Back to Home', getStarted: 'Get Started', explore: 'Explore', languages: 'Language switcher' },
            auth: {
                asideTitle: 'Taste of Macau',
                email: 'Email', password: 'Password', username: 'Username',
                login: { eyebrow: 'Welcome back', title: 'Sign In', subtitle: 'Access your Taste of Macau account', submit: 'Sign In', forgot: 'Forgot password?', registerPrompt: 'Don\'t have an account? Register', registerPromptText: 'Don\'t have an account?', registerLink: 'Register' },
                register: { eyebrow: 'Join us', title: 'Create Account', subtitle: 'Start your Macau food discovery journey', submit: 'Create Account', confirmPassword: 'Confirm Password', loginPrompt: 'Already have an account? Sign In', loginPromptText: 'Already have an account?', loginLink: 'Sign In' },
                forgot: { eyebrow: 'Account recovery', title: 'Reset Your Password', subtitle: 'Enter your email and we\'ll send a reset link', submit: 'Send Reset Link', back: 'Back to Sign In', success: 'If that email exists, a reset link has been sent.' },
                reset: { eyebrow: 'Almost there', title: 'Set New Password', subtitle: 'Choose a strong password for your account', newPassword: 'New Password', confirmPassword: 'Confirm Password', submit: 'Reset Password' },
            },
            landing: {
                hero: {
                    eyebrow: 'Macau food discovery platform',
                    title: 'Discover Macau\'s Culinary Heritage',
                    subtitle: 'Your guide to 20+ iconic dishes across the city\'s most beloved neighbourhoods',
                    getStarted: 'Get Started',
                    signIn: 'Sign In',
                    guestAccess: 'Guest access',
                    visualLabel: 'Neighbourhood dish collage',
                    tile1: { title: 'Portuguese Egg Tart', text: 'Warm pastry classics from the streets of Coloane.' },
                    tile2: { title: 'Street Skewers', text: 'Late-night snacks near every bustling avenue.' },
                    tile3: { title: 'Macanese Classics', text: 'Blended flavors shaped by centuries of heritage.' },
                    tile4: { title: 'Dessert Trails', text: 'Sweet stops for almond cookies and pudding cups.' },
                    tile5: { title: 'Tea & Cafés', text: 'Hidden neighbourhood spots for slow afternoon breaks.' },
                    tile6: { title: 'Live Map Links', text: 'Jump from discovery cards to the exact place to visit.' },
                },
                features: {
                    eyebrow: 'Platform features',
                    title: 'Everything You Need to Explore',
                    subtitle: 'Taste of Macau combines curated discovery, multilingual search, and personal tracking in one service-driven experience.',
                    map: { title: 'Interactive Map', text: 'Find dishes on a real-time map of Macau with bi-directional card linking' },
                    search: { title: 'Smart Search', text: 'Search by name, category, or location in Chinese, English, or Portuguese' },
                    collection: { title: 'Personal Collection', text: 'Save your favorites and track your food exploration journey' },
                },
                stats: {
                    eyebrow: 'Platform snapshot',
                    dishes: { value: '20+', label: 'Iconic Dishes' },
                    categories: { value: '4', label: 'Food Categories' },
                    languages: { value: '3', label: 'Languages Supported' },
                },
                categories: {
                    eyebrow: 'Curated browsing',
                    title: 'Explore by Category',
                    subtitle: 'Move from street stalls to heritage cafés with themed collections built for first-time visitors and returning locals alike.',
                    street: { title: 'Street Snacks', text: 'Discover pepper buns, skewers, and grab-and-go favorites from Macau\'s busiest pedestrian streets.' },
                    portuguese: { title: 'Portuguese', text: 'Browse dishes inspired by Lusophone heritage, from bacalhau plates to comforting Macanese fusion meals.' },
                    desserts: { title: 'Desserts', text: 'Follow a sweet route through egg tarts, almond cookies, puddings, and café treats across the city.' },
                    main: { title: 'Main Dishes', text: 'Plan full meals with rice dishes, noodles, curries, and richly layered specialities worth crossing town for.' },
                },
                cta: {
                    eyebrow: 'Start your food trail',
                    title: 'Ready to Explore Macau\'s Flavors?',
                    subtitle: 'Join thousands of food lovers discovering the best of Macau',
                    button: 'Create Free Account',
                    secondary: 'Sign In',
                },
                footer: {
                    aboutTitle: 'About',
                    aboutText: 'Taste of Macau helps visitors and locals explore iconic dishes, neighbourhood stories, and multilingual food discovery in one place.',
                    quickLinksTitle: 'Quick Links',
                    quickLinks: { register: 'Create Account', login: 'Sign In', explore: 'Explore Platform' },
                    languagesTitle: 'Languages',
                    languages: { zh: '中文', en: 'English', pt: 'Português' },
                    course: 'CISC3003 Web Programming — Team 04',
                },
            },
            categories: { all: 'All', streetSnacks: 'Street Snacks', portuguese: 'Portuguese', desserts: 'Desserts', mainDishes: 'Main Dishes' },
            actions: {
                search: 'Search',
                searchPlaceholder: 'Search foods, descriptions, or areas...',
                filter: 'Filter',
                viewDetails: 'View Details',
                addToFavorites: 'Add to Favorites',
                removeFromFavorites: 'Remove from Favorites',
                signInToSave: 'Sign in to save',
                nearMe: 'Near Me',
                locating: 'Locating...',
                clearAll: 'Clear All',
                confirmClear: 'Confirm clear?',
            },
            dashboard: {
                overview: 'Overview',
                profile: 'Profile',
                searchHistory: 'Search History',
                browseHistory: 'Browse History',
                favorites: 'Favorites',
                favoritesEmptyTitle: 'No favorites yet',
                favoritesEmptyLead: 'Start exploring the food catalog and tap the heart on dishes you want to revisit later.',
                member: 'Taste of Macau Member',
            },
            messages: {
                noResultsFound: 'No matching dishes found for your current search and category.',
                loading: 'Loading...',
                geolocationFailed: 'Unable to get your location. Please allow location access and try again.',
            },
            footer: { tagline: 'Taste of Macau brings together iconic dishes, local neighbourhoods, and food stories across three languages.' },
            home: {
                heroLead: 'Discover Macau\'s iconic food spots across trilingual stories, neighbourhood highlights, and category-focused browsing. Filter by culinary style, search by food or area, and jump into details for each signature dish.',
                catalogTitle: 'Food Catalog',
                mapTitle: 'Food Map',
                mapHint: 'Explore dishes by location',
            },
            favorites: {
                title: 'Your saved Macau favorites',
                description: 'Keep your must-try dishes close at hand, revisit the neighborhoods they belong to, and jump back into the details anytime.',
                emptyTitle: 'No favorites yet',
                emptyDescription: 'Start exploring the food catalog and tap the heart on dishes you want to revisit later.',
            },
        },
        zh: {
            nav: { home: '首頁', login: '登入', register: '註冊', logout: '登出', dashboard: '我的', backHome: '返回首頁', getStarted: '立即開始', explore: '探索', languages: '語言切換' },
            auth: {
                asideTitle: '味遊澳門',
                email: '電子郵件', password: '密碼', username: '用戶名',
                login: { eyebrow: '歡迎回來', title: '登入', subtitle: '登入你的味遊澳門帳號', submit: '登入', forgot: '忘記密碼？', registerPrompt: '還沒有帳號？立即註冊', registerPromptText: '還沒有帳號？', registerLink: '立即註冊' },
                register: { eyebrow: '加入我們', title: '建立帳號', subtitle: '開始你的澳門美食探索之旅', submit: '建立帳號', confirmPassword: '確認密碼', loginPrompt: '已有帳號？立即登入', loginPromptText: '已有帳號？', loginLink: '立即登入' },
                forgot: { eyebrow: '帳號恢復', title: '重設密碼', subtitle: '輸入你的電子郵件，我們將發送重設連結', submit: '發送重設連結', back: '返回登入', success: '如果該電子郵件已註冊，我們已發送重設連結。' },
                reset: { eyebrow: '即將完成', title: '設定新密碼', subtitle: '為你的帳號選擇一個強密碼', newPassword: '新密碼', confirmPassword: '確認密碼', submit: '重設密碼' },
            },
            landing: {
                hero: {
                    eyebrow: '澳門美食探索平台',
                    title: '探索澳門的飲食文化遺產',
                    subtitle: '帶你走訪全城最受歡迎的社區，品嚐 20+ 道經典美食',
                    getStarted: '立即開始',
                    signIn: '登入',
                    guestAccess: '游客访问',
                    visualLabel: '社區美食拼貼',
                    tile1: { title: '葡式蛋撻', text: '路環街頭的溫暖酥皮經典。' },
                    tile2: { title: '街頭串燒', text: '每條熱鬧大街旁的深夜小吃。' },
                    tile3: { title: '澳門經典', text: '數百年文化交融孕育的獨特風味。' },
                    tile4: { title: '甜品之旅', text: '杏仁餅、布甸杯的甜蜜驛站。' },
                    tile5: { title: '茶室與咖啡館', text: '隱藏在社區裡的悠閒午後角落。' },
                    tile6: { title: '即時地圖連結', text: '從探索卡片直接跳轉到實際地點。' },
                },
                features: {
                    eyebrow: '平台功能',
                    title: '探索所需的一切',
                    subtitle: '味遊澳門結合精選探索、多語搜尋與個人追蹤，打造一站式服務體驗。',
                    map: { title: '互動地圖', text: '在澳門即時地圖上尋找美食，卡片與標記雙向聯動' },
                    search: { title: '智能搜尋', text: '以中文、英文或葡文按名稱、分類或地點搜尋' },
                    collection: { title: '個人收藏', text: '收藏你的最愛，追蹤你的美食探索旅程' },
                },
                stats: {
                    eyebrow: '平台概覽',
                    dishes: { value: '20+', label: '經典美食' },
                    categories: { value: '4', label: '美食分類' },
                    languages: { value: '3', label: '支援語言' },
                },
                categories: {
                    eyebrow: '精選瀏覽',
                    title: '按分類探索',
                    subtitle: '從街頭攤檔到傳統咖啡館，為初訪遊客和回頭客打造的主題合集。',
                    street: { title: '街頭小吃', text: '探索澳門最繁忙步行街上的胡椒餅、串燒和即買即食美食。' },
                    portuguese: { title: '葡式風味', text: '瀏覽受葡語文化啟發的菜式，從馬介休到溫暖的澳門融合餐。' },
                    desserts: { title: '甜品', text: '沿著甜蜜路線品嚐蛋撻、杏仁餅、布甸和全城咖啡館小食。' },
                    main: { title: '主菜', text: '用飯類、麵食、咖喱和值得跨區品嚐的豐富特色菜規劃正餐。' },
                },
                cta: {
                    eyebrow: '開始你的美食之旅',
                    title: '準備好探索澳門的風味了嗎？',
                    subtitle: '加入數千名美食愛好者，一起發現澳門的精彩',
                    button: '免費建立帳號',
                    secondary: '登入',
                },
                footer: {
                    aboutTitle: '關於',
                    aboutText: '味遊澳門幫助遊客和本地人在同一平台探索經典美食、社區故事和多語美食發現。',
                    quickLinksTitle: '快速連結',
                    quickLinks: { register: '建立帳號', login: '登入', explore: '探索平台' },
                    languagesTitle: '語言',
                    languages: { zh: '中文', en: 'English', pt: 'Português' },
                    course: 'CISC3003 網頁程式設計 — 第四組',
                },
            },
            categories: { all: '全部', streetSnacks: '街頭小吃', portuguese: '葡式風味', desserts: '甜品', mainDishes: '主菜' },
            actions: {
                search: '搜尋',
                searchPlaceholder: '搜尋美食、描述或地區...',
                filter: '篩選',
                viewDetails: '查看詳情',
                addToFavorites: '加入最愛',
                removeFromFavorites: '移除最愛',
                signInToSave: '登入後收藏',
                nearMe: '附近美食',
                locating: '正在定位...',
                clearAll: '清除全部',
                confirmClear: '確認清除？',
            },
            dashboard: {
                overview: '總覽',
                profile: '個人資料',
                searchHistory: '搜尋紀錄',
                browseHistory: '瀏覽紀錄',
                favorites: '我的最愛',
                favoritesEmptyTitle: '尚未收藏任何美食',
                favoritesEmptyLead: '開始瀏覽美食目錄，點擊愛心即可把想再回味的菜式加入最愛。',
                member: '味遊澳門會員',
            },
            messages: {
                noResultsFound: '目前搜尋與分類下找不到相符的美食。',
                loading: '載入中...',
                geolocationFailed: '無法取得你的位置，請允許定位權限後再試。',
            },
            footer: { tagline: '味遊澳門以三種語言帶你探索澳門經典美食、社區風味與飲食故事。' },
            home: {
                heroLead: '以三語故事、社區亮點與分類瀏覽方式探索澳門代表性美食。依飲食風格篩選、按美食或地區搜尋，並快速查看每道招牌菜的詳情。',
                catalogTitle: '美食目錄',
                mapTitle: '美食地圖',
                mapHint: '按地點探索美食',
            },
            favorites: {
                title: '你收藏的澳門美食',
                description: '把最想再吃的菜式留在身邊，快速重溫它們所在的社區，隨時返回詳情頁。',
                emptyTitle: '尚未收藏任何美食',
                emptyDescription: '開始瀏覽美食目錄，點擊愛心即可把想再回味的菜式加入最愛。',
            },
        },
        pt: {
            nav: { home: 'Início', login: 'Entrar', register: 'Registar', logout: 'Sair', dashboard: 'Meu', backHome: 'Voltar ao início', getStarted: 'Começar', explore: 'Explorar', languages: 'Seletor de língua' },
            auth: {
                asideTitle: 'Sabores de Macau',
                email: 'Email', password: 'Palavra-passe', username: 'Nome de utilizador',
                login: { eyebrow: 'Bem-vindo de volta', title: 'Entrar', subtitle: 'Acede à tua conta Taste of Macau', submit: 'Entrar', forgot: 'Esqueceste a palavra-passe?', registerPrompt: 'Não tens conta? Regista-te', registerPromptText: 'Não tens conta?', registerLink: 'Regista-te' },
                register: { eyebrow: 'Junta-te a nós', title: 'Criar Conta', subtitle: 'Começa a tua jornada de descoberta gastronómica em Macau', submit: 'Criar Conta', confirmPassword: 'Confirmar Palavra-passe', loginPrompt: 'Já tens conta? Entra', loginPromptText: 'Já tens conta?', loginLink: 'Entra' },
                forgot: { eyebrow: 'Recuperação de conta', title: 'Repor Palavra-passe', subtitle: 'Introduz o teu email e enviaremos um link de reposição', submit: 'Enviar Link', back: 'Voltar ao login', success: 'Se esse email existir, foi enviado um link de reposição.' },
                reset: { eyebrow: 'Quase lá', title: 'Definir Nova Palavra-passe', subtitle: 'Escolhe uma palavra-passe forte para a tua conta', newPassword: 'Nova Palavra-passe', confirmPassword: 'Confirmar Palavra-passe', submit: 'Repor Palavra-passe' },
            },
            landing: {
                hero: {
                    eyebrow: 'Plataforma de descoberta gastronómica de Macau',
                    title: 'Descobre o Património Culinário de Macau',
                    subtitle: 'O teu guia para mais de 20 pratos icónicos nos bairros mais queridos da cidade',
                    getStarted: 'Começar',
                    signIn: 'Entrar',
                    guestAccess: 'Acesso de visitante',
                    visualLabel: 'Colagem de pratos por bairro',
                    tile1: { title: 'Pastel de Nata', text: 'Clássicos de pastelaria quente das ruas de Coloane.' },
                    tile2: { title: 'Espetadas de Rua', text: 'Petiscos noturnos junto a cada avenida movimentada.' },
                    tile3: { title: 'Clássicos Macaenses', text: 'Sabores mesclados moldados por séculos de herança.' },
                    tile4: { title: 'Trilhos de Sobremesas', text: 'Paragens doces para biscoitos de amêndoa e pudins.' },
                    tile5: { title: 'Chá e Cafés', text: 'Recantos escondidos nos bairros para tardes calmas.' },
                    tile6: { title: 'Links no Mapa', text: 'Salta dos cartões de descoberta para o local exato a visitar.' },
                },
                features: {
                    eyebrow: 'Funcionalidades da plataforma',
                    title: 'Tudo o Que Precisas para Explorar',
                    subtitle: 'O Taste of Macau combina descoberta curada, pesquisa multilingue e acompanhamento pessoal numa experiência orientada ao serviço.',
                    map: { title: 'Mapa Interativo', text: 'Encontra pratos num mapa em tempo real de Macau com ligação bidirecional de cartões' },
                    search: { title: 'Pesquisa Inteligente', text: 'Pesquisa por nome, categoria ou localização em chinês, inglês ou português' },
                    collection: { title: 'Coleção Pessoal', text: 'Guarda os teus favoritos e acompanha a tua jornada de exploração gastronómica' },
                },
                stats: {
                    eyebrow: 'Panorama da plataforma',
                    dishes: { value: '20+', label: 'Pratos Icónicos' },
                    categories: { value: '4', label: 'Categorias' },
                    languages: { value: '3', label: 'Línguas Suportadas' },
                },
                categories: {
                    eyebrow: 'Navegação curada',
                    title: 'Explorar por Categoria',
                    subtitle: 'Das bancas de rua aos cafés patrimoniais, com coleções temáticas para visitantes de primeira vez e locais que regressam.',
                    street: { title: 'Petiscos de Rua', text: 'Descobre pãezinhos de pimenta, espetadas e favoritos para levar das ruas pedonais mais movimentadas de Macau.' },
                    portuguese: { title: 'Portuguesa', text: 'Explora pratos inspirados na herança lusófona, de pratos de bacalhau a reconfortantes refeições de fusão macaense.' },
                    desserts: { title: 'Sobremesas', text: 'Segue uma rota doce por pastéis de nata, biscoitos de amêndoa, pudins e petiscos de café pela cidade.' },
                    main: { title: 'Pratos Principais', text: 'Planeia refeições completas com pratos de arroz, massas, caril e especialidades que valem a travessia da cidade.' },
                },
                cta: {
                    eyebrow: 'Começa o teu trilho gastronómico',
                    title: 'Pronto para Explorar os Sabores de Macau?',
                    subtitle: 'Junta-te a milhares de amantes de comida a descobrir o melhor de Macau',
                    button: 'Criar Conta Gratuita',
                    secondary: 'Entrar',
                },
                footer: {
                    aboutTitle: 'Sobre',
                    aboutText: 'O Taste of Macau ajuda visitantes e locais a explorar pratos icónicos, histórias de bairro e descoberta gastronómica multilingue num só lugar.',
                    quickLinksTitle: 'Links Rápidos',
                    quickLinks: { register: 'Criar Conta', login: 'Entrar', explore: 'Explorar Plataforma' },
                    languagesTitle: 'Línguas',
                    languages: { zh: '中文', en: 'English', pt: 'Português' },
                    course: 'CISC3003 Programação Web — Equipa 04',
                },
            },
            categories: { all: 'Todos', streetSnacks: 'Petiscos de Rua', portuguese: 'Portuguesa', desserts: 'Sobremesas', mainDishes: 'Pratos Principais' },
            actions: {
                search: 'Pesquisar',
                searchPlaceholder: 'Pesquisar pratos, descrições ou zonas...',
                filter: 'Filtrar',
                viewDetails: 'Ver detalhes',
                addToFavorites: 'Adicionar aos favoritos',
                removeFromFavorites: 'Remover dos favoritos',
                signInToSave: 'Entrar para guardar',
                nearMe: 'Perto de mim',
                locating: 'A localizar...',
                clearAll: 'Limpar tudo',
                confirmClear: 'Confirmar limpeza?',
            },
            dashboard: {
                overview: 'Visão Geral',
                profile: 'Perfil',
                searchHistory: 'Histórico de Pesquisa',
                browseHistory: 'Histórico de Navegação',
                favorites: 'Favoritos',
                favoritesEmptyTitle: 'Ainda não tens favoritos',
                favoritesEmptyLead: 'Explora o catálogo gastronómico e toca no coração dos pratos que queres rever mais tarde.',
                member: 'Membro do Taste of Macau',
            },
            messages: {
                noResultsFound: 'Não foram encontrados pratos correspondentes à pesquisa e categoria atuais.',
                loading: 'A carregar...',
                geolocationFailed: 'Não foi possível obter a tua localização. Permite o acesso à localização e tenta novamente.',
            },
            footer: { tagline: 'Taste of Macau reúne pratos icónicos, bairros locais e histórias gastronómicas em três línguas.' },
            home: {
                heroLead: 'Descobre os lugares gastronómicos icónicos de Macau com histórias em três línguas, destaques por bairro e navegação por categorias. Filtra por estilo culinário, pesquisa por prato ou zona e entra nos detalhes de cada especialidade.',
                catalogTitle: 'Catálogo Gastronómico',
                mapTitle: 'Mapa Gastronómico',
                mapHint: 'Explora pratos por localização',
            },
            favorites: {
                title: 'Os teus favoritos de Macau',
                description: 'Mantém os pratos imperdíveis por perto, revisita os bairros a que pertencem e volta aos detalhes sempre que quiseres.',
                emptyTitle: 'Ainda não tens favoritos',
                emptyDescription: 'Explora o catálogo gastronómico e toca no coração dos pratos que queres rever mais tarde.',
            },
        },
    };

    class I18nManager {
        constructor() {
            this.supportedLocales = ['en', 'zh', 'pt'];
            this.currentLocale = this.resolveInitialLocale();
        }

        resolveInitialLocale() {
            const stored = window.localStorage.getItem('taste-of-macau-locale');
            const metaLocale = document.querySelector('meta[name="app-locale"]')?.getAttribute('content') || 'zh';
            const candidate = this.supportedLocales.includes(metaLocale) ? metaLocale : stored;

            return this.supportedLocales.includes(candidate) ? candidate : 'zh';
        }

        init() {
            this.applyLocale(this.currentLocale);
            return this;
        }

        resolveKey(source, key) {
            return String(key || '')
                .split('.')
                .reduce((value, segment) => (value && typeof value === 'object' ? value[segment] : undefined), source);
        }

        t(key) {
            const value = this.resolveKey(translations[this.currentLocale], key);

            if (typeof value === 'string') {
                return value;
            }

            const fallback = this.resolveKey(translations.zh, key);
            return typeof fallback === 'string' ? fallback : key;
        }

        applyTranslations(root = document) {
            root.querySelectorAll('[data-i18n]').forEach((element) => {
                const key = element.getAttribute('data-i18n');

                if (key) {
                    element.textContent = this.t(key);
                }
            });

            root.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
                const key = element.getAttribute('data-i18n-placeholder');

                if (key) {
                    element.setAttribute('placeholder', this.t(key));
                }
            });

            root.querySelectorAll('[data-i18n-title]').forEach((element) => {
                const key = element.getAttribute('data-i18n-title');

                if (key) {
                    element.setAttribute('title', this.t(key));
                }
            });

            root.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
                const key = element.getAttribute('data-i18n-aria-label');

                if (key) {
                    element.setAttribute('aria-label', this.t(key));
                }
            });
        }

        syncLocaleButtons() {
            document.querySelectorAll('[data-locale-switch]').forEach((button) => {
                const isActive = button.getAttribute('data-locale') === this.currentLocale;

                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        applyLocale(locale) {
            this.currentLocale = this.supportedLocales.includes(locale) ? locale : 'zh';
            document.documentElement.lang = this.currentLocale;
            document.querySelector('meta[name="app-locale"]')?.setAttribute('content', this.currentLocale);
            window.localStorage.setItem('taste-of-macau-locale', this.currentLocale);
            this.applyTranslations(document);
            this.syncLocaleButtons();
        }

        async setLocale(locale, options = {}) {
            const resolvedLocale = this.supportedLocales.includes(locale) ? locale : 'zh';
            const { persist = true, reload = false } = options;

            this.applyLocale(resolvedLocale);

            if (persist && window.api) {
                await window.api.post('/api/locale', { locale: resolvedLocale });
            }

            if (reload) {
                window.location.reload();
            }

            return resolvedLocale;
        }

        formatDishesCount(count) {
            const safeCount = Number(count || 0);

            if (this.currentLocale === 'zh') {
                return `${safeCount} 道美食`;
            }

            if (this.currentLocale === 'pt') {
                return `${safeCount} ${safeCount === 1 ? 'prato' : 'pratos'}`;
            }

            return `${safeCount} dish${safeCount === 1 ? '' : 'es'}`;
        }

        formatDistance(distanceKm) {
            const safeDistance = Number(distanceKm || 0);

            if (this.currentLocale === 'zh') {
                return `${safeDistance.toFixed(1)} 公里`;
            }

            return `${safeDistance.toFixed(1)} km`;
        }
    }

    window.I18n = new I18nManager();
})();
