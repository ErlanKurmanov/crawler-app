<template>
    <div class="app">
        <header class="header">
            <div class="header__inner">
                <div class="header__brand">
                    <span class="header__dot">●</span>
                    <span class="header__title">НОВОСТИ</span>
                    <span class="header__source">kaktus.media</span>
                </div>
            </div>
        </header>

        <main class="main">
            <!-- Controls bar -->
            <div class="controls">
                <div class="controls__inner">
                    <!-- Date picker -->
                    <div class="controls__group">
                        <label class="controls__label">ДАТА</label>
                        <div class="controls__date-wrap">
                            <input
                                class="controls__date"
                                type="date"
                                v-model="selectedDate"
                                :max="today"
                                @change="onDateChange"
                            />
                            <span class="controls__date-icon">◈</span>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="controls__group controls__group--search">
                        <label class="controls__label">ПОИСК ПО ЗАГОЛОВКАМ</label>
                        <div class="controls__search-wrap">
                            <input
                                class="controls__search"
                                type="text"
                                v-model="searchQuery"
                                placeholder="Введите ключевое слово..."
                                @input="onSearchInput"
                            />
                            <button
                                v-if="searchQuery"
                                class="controls__clear"
                                @click="clearSearch"
                                title="Очистить"
                            >✕</button>
                            <span v-else class="controls__search-icon">⌕</span>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="controls__meta" v-if="!loading">
                        <span class="controls__count">{{ news.length }}</span>
                        <span class="controls__count-label">{{ pluralize(news.length) }}</span>
                    </div>
                </div>
            </div>

            <!-- Error state -->
            <div class="state state--error" v-if="error">
                <div class="state__icon">✕</div>
                <div class="state__text">{{ error }}</div>
                <button class="state__retry" @click="fetchNews">Повторить</button>
            </div>

            <!-- Loading skeleton -->
            <div class="grid" v-else-if="loading">
                <div class="card card--skeleton" v-for="i in 12" :key="i">
                    <div class="card__img-skeleton"></div>
                    <div class="card__body">
                        <div class="card__skeleton-line card__skeleton-line--wide"></div>
                        <div class="card__skeleton-line"></div>
                        <div class="card__skeleton-line card__skeleton-line--narrow"></div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div class="state state--empty" v-else-if="news.length === 0">
                <div class="state__icon">◌</div>
                <div class="state__text">
                    {{ searchQuery ? `По запросу «${searchQuery}» ничего не найдено` : 'Нет новостей за выбранную дату' }}
                </div>
                <button v-if="searchQuery" class="state__retry" @click="clearSearch">
                    Сбросить поиск
                </button>
            </div>

            <!-- News list -->
            <div class="list" v-else>
                <a
                    class="card"
                    v-for="(item, index) in news"
                    :key="item.url"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :style="{ '--i': index }"
                >
                    <!-- Image -->
                    <div class="card__img-wrap">
                        <img
                            v-if="item.image"
                            class="card__img"
                            :src="item.image"
                            :alt="item.title"
                            loading="lazy"
                            @error="onImgError"
                        />
                        <div v-else class="card__img-placeholder">
                            <span>◈</span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card__body">
                        <p class="card__title" v-html="highlight(item.title)"></p>
                        <div class="card__meta">
                            <span class="card__date">{{ formatDate(item.date) }}</span>
                            <span class="card__arrow">→</span>
                        </div>
                    </div>
                </a>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'App',

    data() {
        const today = new Date().toISOString().split('T')[0]
        return {
            today,
            selectedDate: today,
            searchQuery: '',
            news: [],
            loading: false,
            error: null,
            searchTimeout: null,
        }
    },

    mounted() {
        this.fetchNews()
    },

    methods: {
        /**
         * Fetch news from the backend for the selected date.
         * Resets search query on date change.
         */
        async fetchNews() {
            this.loading = true
            this.error = null

            try {
                const params = new URLSearchParams({ date: this.selectedDate })
                const res = await fetch(`/api/news?${params}`)
                if (!res.ok) throw new Error(`HTTP ${res.status}`)
                const data = await res.json()
                this.news = data.data ?? []
            } catch (e) {
                this.error = 'Не удалось загрузить новости. Проверьте соединение.'
                this.news = []
            } finally {
                this.loading = false
            }
        },

        /**
         * Fetch backend-filtered news by search keyword.
         * Debounced by 400ms to avoid excessive requests.
         */
        async fetchSearch() {
            this.loading = true
            this.error = null

            try {
                const params = new URLSearchParams({
                    date: this.selectedDate,
                    search: this.searchQuery,
                })
                const res = await fetch(`/api/news/search?${params}`)
                if (!res.ok) throw new Error(`HTTP ${res.status}`)
                const data = await res.json()
                this.news = data.data ?? []
            } catch (e) {
                this.error = 'Ошибка при поиске. Попробуйте ещё раз.'
                this.news = []
            } finally {
                this.loading = false
            }
        },

        /** Trigger full reload when date changes, clearing search. */
        onDateChange() {
            this.searchQuery = ''
            clearTimeout(this.searchTimeout)
            this.fetchNews()
        },

        /** Debounce search input to avoid hammering the backend. */
        onSearchInput() {
            clearTimeout(this.searchTimeout)
            if (!this.searchQuery.trim()) {
                this.fetchNews()
                return
            }
            this.searchTimeout = setTimeout(() => {
                this.fetchSearch()
            }, 400)
        },

        clearSearch() {
            this.searchQuery = ''
            clearTimeout(this.searchTimeout)
            this.fetchNews()
        },

        /** Highlight matching search term in title HTML. */
        highlight(title) {
            if (!this.searchQuery.trim()) return title
            const escaped = this.searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            return title.replace(
                new RegExp(`(${escaped})`, 'gi'),
                '<mark class="highlight">$1</mark>'
            )
        },

        formatDate(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-')
            const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн',
                'июл', 'авг', 'сен', 'окт', 'ноя', 'дек']
            return `${parseInt(d)} ${months[parseInt(m) - 1]} ${y}`
        },

        pluralize(n) {
            if (n % 100 >= 11 && n % 100 <= 19) return 'материалов'
            const r = n % 10
            if (r === 1) return 'материал'
            if (r >= 2 && r <= 4) return 'материала'
            return 'материалов'
        },

        onImgError(e) {
            // Replace broken image with placeholder parent
            const wrap = e.target.closest('.card__img-wrap')
            if (wrap) {
                e.target.style.display = 'none'
                const ph = document.createElement('div')
                ph.className = 'card__img-placeholder'
                ph.innerHTML = '<span>◈</span>'
                wrap.prepend(ph)
            }
        },
    },
}
</script>


