export function productCatalog(config) {
    return {
        options: config.products,
        retained: [],
        selectedIds: (config.selectedIds ?? []).map(String),
        searchTerm: '',
        currentPage: 1,
        hasMore: config.products.length >= 50,
        searching: false,
        searchError: false,
        searchSequence: 0,
        get products() {
            return [...new Map([...this.retained, ...this.options].map(product => [String(product.id), product])).values()];
        },
        async searchProducts(page = 1) {
            const sequence = ++this.searchSequence;
            const selected = new Set((this.items ? this.items.map(item => item.product_id) : this.selectedIds).map(String));
            this.retained = this.products.filter(product => selected.has(String(product.id)));
            this.searching = true;
            this.searchError = false;
            try {
                const url = new URL(config.searchUrl, window.location.origin);
                url.searchParams.set('q', this.searchTerm);
                url.searchParams.set('page', page);
                url.searchParams.set('active', config.activeOnly ? '1' : '0');
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Product search failed');
                const data = await response.json();
                if (sequence !== this.searchSequence) return;
                const currentSelected = new Set((this.items ? this.items.map(item => item.product_id) : this.selectedIds).map(String));
                this.retained = this.products.filter(product => currentSelected.has(String(product.id)));
                this.options = data.products;
                this.currentPage = page;
                this.hasMore = data.has_more;
            } catch {
                if (sequence === this.searchSequence) this.searchError = true;
            } finally {
                if (sequence === this.searchSequence) this.searching = false;
            }
        },
    };
}

export function packageEditor(config) {
    return Object.assign(productCatalog(config), {
        items: config.items,
        total: '—',
        lineTotals: [],
        calculationError: false,
        calculating: false,
        calculationSequence: 0,
        calculationTimer: null,
        init() {
            this.$watch('items', () => this.recalculate());
            this.recalculate();
        },
        destroy() {
            clearTimeout(this.calculationTimer);
            ++this.calculationSequence;
        },
        add() {
            if (this.items.length < config.maxItems) this.items.push({ product_id: '', quantity: 1 });
        },
        remove(index) { this.items.splice(index, 1); },
        recalculate() {
            clearTimeout(this.calculationTimer);
            const sequence = ++this.calculationSequence;
            this.lineTotals = [];
            this.total = '—';
            this.calculationError = false;
            this.calculating = false;
            this.calculationTimer = setTimeout(() => this.calculate(sequence), 200);
        },
        async calculate(sequence) {
            const payload = this.items.filter(row => row.product_id)
                .map(row => ({ product_id: Number(row.product_id), quantity: Number(row.quantity) }));
            if (!payload.length) { this.calculating = false; return; }
            this.calculating = true;
            try {
                const response = await fetch(config.calculateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ items: payload }),
                });
                if (!response.ok) throw new Error('Package calculation failed');
                const data = await response.json();
                if (sequence !== this.calculationSequence) return;
                this.total = data.formatted;
                let line = 0;
                this.lineTotals = this.items.map(row => row.product_id ? data.line_totals[line++] : '');
            } catch {
                if (sequence === this.calculationSequence) this.calculationError = true;
            } finally {
                if (sequence === this.calculationSequence) this.calculating = false;
            }
        },
    });
}
