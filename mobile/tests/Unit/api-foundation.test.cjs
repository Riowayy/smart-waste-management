const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { test } = require('node:test');
const vm = require('node:vm');
const ts = require('typescript');

// Jalankan source TypeScript tanpa build artefak atau dependency test tambahan.
function setup({ fetch, platform = 'web', browser = true, baseUrl = 'https://example.test/api/v1/' } = {}) {
  const values = new Map();
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: 7,
    async getItemAsync(key) { return values.get(key) ?? null; },
    async setItemAsync(key, value) { values.set(key, value); },
    async deleteItemAsync(key) { values.delete(key); },
  };
  const cache = new Map();
  const context = vm.createContext({
    URL, Headers, FormData, AbortController, setTimeout, clearTimeout,
    fetch, process: { env: { EXPO_PUBLIC_API_URL: baseUrl } },
    ...(browser ? { window: {} } : {}),
  });
  function load(filename) {
    filename = path.resolve(__dirname, '../../src/services', filename);
    if (cache.has(filename)) return cache.get(filename).exports;
    const module = { exports: {} };
    cache.set(filename, module);
    const source = ts.transpileModule(readFileSync(filename, 'utf8'), {
      compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    }).outputText;
    const requireModule = (name) => {
      if (name === 'react-native') return { Platform: { OS: platform } };
      if (name === 'expo-secure-store') return secureStore;
      return load(path.resolve(path.dirname(filename), `${name}.ts`));
    };
    vm.runInContext(`(function(require, module, exports) {${source}\n})`, context)(requireModule, module, module.exports);
    return module.exports;
  }
  return { client: load('api/client.ts').apiClient, storage: load('token-storage.ts').tokenStorage, values };
}

const success = (data = null, extra = {}) => new Response(JSON.stringify({
  success: true, message: 'Berhasil.', data, ...extra,
}));

test('Bearer, JSON, query, dan pagination mengikuti kontrak Laravel', async () => {
  let sent;
  const { client, storage } = setup({ fetch: async (url, init) => {
    sent = { url, ...init };
    return success([], { pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1 } });
  } });
  await storage.setToken('1|dummy');
  const result = await client.request('/reports', { method: 'POST', body: { description: 'Sampah' }, query: { page: 1, status: 'pending', empty: null } });
  assert.equal(sent.url, 'https://example.test/api/v1/reports?page=1&status=pending');
  assert.equal(sent.headers.get('Authorization'), 'Bearer 1|dummy');
  assert.equal(sent.headers.get('Accept'), 'application/json');
  assert.equal(sent.headers.get('Content-Type'), 'application/json');
  assert.equal(sent.body, '{"description":"Sampah"}');
  assert.equal(sent.credentials, 'omit');
  assert.equal(sent.redirect, 'error');
  assert.equal(result.pagination.total, 0);
});

test('request publik tidak membawa token dan multipart membiarkan fetch mengatur boundary', async () => {
  const { client, storage } = setup({ fetch: async (_url, init) => {
    assert.equal(init.headers.has('Authorization'), false);
    assert.equal(init.headers.has('Content-Type'), false);
    assert.equal(init.body, form);
    return success();
  } });
  const form = new FormData();
  form.append('image', new Blob(['foto'], { type: 'image/png' }), 'foto.png');
  await storage.setToken('1|dummy');
  await client.request('reports', { method: 'POST', body: form, authenticated: false });
});

test('status HTTP dan validasi field dipertahankan, termasuk HTTP error non-JSON', async () => {
  for (const status of [401, 403, 404, 422, 429, 500]) {
    const { client } = setup({ fetch: async () => new Response(JSON.stringify({
      success: false, message: 'Ditolak.', data: null, errors: { image: ['Foto wajib.'] },
    }), { status }) });
    await assert.rejects(client.request('reports'), (error) =>
      error.kind === 'http' && error.status === status && error.errors.image[0] === 'Foto wajib.');
  }
  const { client } = setup({ fetch: async () => new Response('<html>Error</html>', { status: 502 }) });
  await assert.rejects(client.request('reports'), (error) => error.kind === 'http' && error.status === 502);
});

test('JSON rusak dan envelope/pagination tidak valid ditolak', async () => {
  for (const body of ['<html></html>', '{}', '{"success":true,"message":"OK"}', JSON.stringify({ success: true, message: 'OK', data: [], pagination: {} })]) {
    const { client } = setup({ fetch: async () => new Response(body) });
    await assert.rejects(client.request('categories'), (error) => error.kind === 'invalid-response');
  }
});

test('network error tidak membocorkan exception transport', async () => {
  const { client } = setup({ fetch: async () => { throw new Error('detail internal'); } });
  await assert.rejects(client.request('auth/me'), (error) =>
    error.kind === 'network' && !error.message.includes('detail internal'));
});

test('timeout dan pembatalan caller dapat dibedakan', async () => {
  let calls = 0;
  const { client } = setup({ fetch: async (_url, init) => {
    calls++;
    return new Promise((_resolve, reject) => {
      if (init.signal.aborted) return reject(new Error('abort'));
      init.signal.addEventListener('abort', () => reject(new Error('abort')), { once: true });
    });
  } });
  await assert.rejects(client.request('auth/me', { timeoutMs: 10 }), (error) => error.kind === 'timeout');
  const controller = new AbortController();
  const pending = client.request('auth/me', { signal: controller.signal });
  controller.abort();
  await assert.rejects(pending, (error) => error.kind === 'cancelled');
  assert.equal(calls, 2);
});

test('URL konfigurasi/path tidak valid ditolak sebelum transport', async () => {
  const fetch = async () => assert.fail('Transport tidak boleh dipanggil');
  for (const baseUrl of ['', 'bukan-url', 'file:///api/v1', 'https://a.test', 'https://user:pass@a.test/api/v1', 'https://a.test/api/v1?q=1']) {
    await assert.rejects(setup({ fetch, baseUrl }).client.request('reports'));
  }
  const { client } = setup({ fetch });
  for (const route of ['https://evil.test/reports', '//evil.test', '../reports', 'reports/../auth/me', 'reports?x=1']) {
    await assert.rejects(client.request(route));
  }
});

test('token Web hanya di memori, dapat dihapus, dan tidak tersedia di SSR', async () => {
  const { storage, values } = setup();
  assert.equal(await storage.getToken(), null);
  await storage.setToken('1|dummy');
  assert.equal(await storage.getToken(), '1|dummy');
  assert.equal(values.size, 0);
  assert.equal(await setup().storage.getToken(), null);
  await storage.clearToken();
  assert.equal(await storage.getToken(), null);
  const server = setup({ browser: false }).storage;
  await assert.rejects(server.setToken('1|dummy'));
  assert.equal(await server.getToken(), null);
});

test('Android/iOS memakai adapter SecureStore untuk simpan, baca, dan hapus', async () => {
  for (const platform of ['android', 'ios']) {
    const { storage, values } = setup({ platform, browser: false });
    await storage.setToken('1|dummy');
    assert.equal(values.get('ecotrack.access-token'), '1|dummy');
    assert.equal(await storage.getToken(), '1|dummy');
    await storage.clearToken();
    assert.equal(await storage.getToken(), null);
    await assert.rejects(storage.setToken(''));
    await assert.rejects(storage.setToken('bad\ntoken'));
  }
});
