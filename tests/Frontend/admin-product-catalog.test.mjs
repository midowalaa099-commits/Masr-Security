import { test, afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { productCatalog, packageEditor } from '../../resources/js/admin-product-catalog.js';

const originalFetch = globalThis.fetch;
const originalWindow = globalThis.window;
globalThis.window = { location: { origin: 'http://localhost' } };
afterEach(() => { globalThis.fetch = originalFetch; });

const camera = { id: 1, name: 'Camera', sku: 'CAM-1', price: '0.10' };
const config = { products: [camera], searchUrl: '/admin/product-options', activeOnly: true,
    calculateUrl: '/admin/packages/calculate', csrf: 'local-test-token', maxItems: 100, items: [] };
const response = data => ({ ok: true, json: async () => data });

test('search retains selections and applies only the newest response', async () => {
    const pending = [];
    globalThis.fetch = () => new Promise(resolve => pending.push(resolve));
    const catalog = productCatalog({ ...config, selectedIds: [1] });
    const oldSearch = catalog.searchProducts();
    const newSearch = catalog.searchProducts(2);
    pending[1](response({ products: [{ ...camera, id: 2 }], has_more: false }));
    await newSearch;
    pending[0](response({ products: [{ ...camera, id: 3 }], has_more: true }));
    await oldSearch;
    assert.deepEqual(catalog.products.map(product => product.id), [1, 2]);
    assert.equal(catalog.currentPage, 2);
    assert.equal(catalog.hasMore, false);
    assert.equal(catalog.searching, false);
});

test('package editor preserves its live product getter while searching', async () => {
    globalThis.fetch = async () => response({ products: [{ ...camera, id: 2 }], has_more: false });
    const editor = packageEditor({ ...config, items: [{ product_id: '1', quantity: 3 }] });
    await editor.searchProducts();
    assert.deepEqual(editor.products.map(product => product.id), [1, 2]);
    assert.equal(editor.items[0].quantity, 3);
});

test('search failure leaves the current choices available and shows an error', async () => {
    globalThis.fetch = async () => ({ ok: false });
    const catalog = productCatalog(config);
    await catalog.searchProducts();
    assert.equal(catalog.searchError, true);
    assert.deepEqual(catalog.products, [camera]);
    assert.equal(catalog.searching, false);
});

test('calculator uses server decimal totals and maps them past empty rows', async () => {
    const editor = packageEditor({ ...config, items: [{ product_id: '', quantity: 1 }, { product_id: '1', quantity: 3 }] });
    globalThis.fetch = async (url, options) => {
        assert.equal(url, config.calculateUrl);
        assert.deepEqual(JSON.parse(options.body), { items: [{ product_id: 1, quantity: 3 }] });
        return response({ formatted: '0.30 EGP', line_totals: ['0.30'] });
    };
    await editor.calculate(0);
    assert.equal(editor.total, '0.30 EGP');
    assert.deepEqual(editor.lineTotals, ['', '0.30']);
});

test('stale calculations cannot overwrite a newer total', async () => {
    const pending = [];
    globalThis.fetch = () => new Promise(resolve => pending.push(resolve));
    const editor = packageEditor({ ...config, items: [{ product_id: '1', quantity: 1 }] });
    const oldCalculation = editor.calculate(0);
    editor.calculationSequence = 1;
    editor.items[0].quantity = 3;
    const newCalculation = editor.calculate(1);
    pending[1](response({ formatted: '0.30 EGP', line_totals: ['0.30'] }));
    await newCalculation;
    pending[0](response({ formatted: '0.10 EGP', line_totals: ['0.10'] }));
    await oldCalculation;
    assert.equal(editor.total, '0.30 EGP');
});

test('rapid changes debounce to a single calculation and failure is visible', async context => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    let requests = 0;
    globalThis.fetch = async () => { requests++; return { ok: false }; };
    const editor = packageEditor({ ...config, items: [{ product_id: '1', quantity: 1 }] });
    editor.recalculate();
    editor.items[0].quantity = 3;
    editor.recalculate();
    context.mock.timers.tick(200);
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(requests, 1);
    assert.equal(editor.calculationError, true);
    assert.equal(editor.calculating, false);
    editor.destroy();
});

test.after(() => { globalThis.window = originalWindow; });
