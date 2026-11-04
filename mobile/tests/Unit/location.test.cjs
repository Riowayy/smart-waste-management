const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const tick = () => new Promise(resolve => setImmediate(resolve));

function location({ enabled = true, delayed = false } = {}) {
  let callback, fail, timeout, finishRegistration, calls = 0, removed = 0;
  const module = { exports: {} };
  const source = ts.transpileModule(fs.readFileSync('src/features/reporting/location.ts', 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS } }).outputText;
  const api = { Accuracy: { Balanced: 3 }, hasServicesEnabledAsync: async () => enabled,
    watchPositionAsync: async (_options, success, error) => {
      calls++; callback = success; fail = error;
      const subscription = { remove: () => { removed++; } };
      if (delayed) return new Promise(resolve => { finishRegistration = () => resolve(subscription); });
      return subscription;
    } };
  vm.runInNewContext('(function(require,module,exports){' + source + '})', {
    Date: { now: () => 100 }, setTimeout: callback => { timeout = callback; return 1; }, clearTimeout: () => {},
  })(() => api, module, module.exports);
  return { ...module.exports, calls: () => calls, removed: () => removed, timeout: () => timeout(),
    registered: () => finishRegistration(), error: () => fail('provider unavailable'),
    point: (timestamp = 101, latitude = 0) => callback({ timestamp, coords: { latitude, longitude: 115, accuracy: 5 } }) };
}

test('GPS menerima fix baru, mengabaikan cache/koordinat invalid dan melepas subscription', async () => {
  const gps = location(); let completed = false;
  const result = gps.freshLocation(new AbortController().signal).then(point => { completed = true; return point; });
  await tick(); gps.point(99); gps.point(101, 100); await tick(); assert.equal(completed, false);
  gps.point(101, -2); assert.equal((await result).coords.latitude, -2); assert.equal(gps.removed(), 1);
  const refresh = gps.freshLocation(new AbortController().signal); await tick(); gps.point(102, -3);
  assert.equal((await refresh).coords.latitude, -3); assert.equal(gps.removed(), 2);
});

test('GPS menolak request paralel dan timeout menghentikan subscription sebelum retry', async () => {
  const gps = location(); const first = gps.freshLocation(new AbortController().signal);
  const rejected = assert.rejects(first, /belum ditemukan/); await tick();
  await assert.rejects(gps.freshLocation(new AbortController().signal), /permintaan sebelumnya/);
  assert.equal(gps.calls(), 1); gps.timeout(); await rejected; assert.equal(gps.removed(), 1);
  const retry = gps.freshLocation(new AbortController().signal); await tick(); gps.point(); await retry; assert.equal(gps.calls(), 2);
});

test('pembatalan melepas GPS dan registrasi yang terlambat tetap dibersihkan tanpa request tumpang tindih', async () => {
  const gps = location({ delayed: true }); const controller = new AbortController();
  const pending = gps.freshLocation(controller.signal); const rejected = assert.rejects(pending, /dibatalkan/);
  await tick(); controller.abort(); await rejected;
  await assert.rejects(gps.freshLocation(new AbortController().signal), /permintaan sebelumnya/);
  gps.registered(); await tick(); assert.equal(gps.removed(), 1);
  const retryController = new AbortController(); const retry = gps.freshLocation(retryController.signal);
  const cancelled = assert.rejects(retry, /dibatalkan/); await tick(); gps.registered(); await tick(); retryController.abort(); await cancelled; assert.equal(gps.removed(), 2);
});

test('GPS nonaktif dan error provider ditangani serta kunci akuisisi dilepas', async () => {
  const disabled = location({ enabled: false }); await assert.rejects(disabled.freshLocation(new AbortController().signal), /GPS tidak aktif/); assert.equal(disabled.calls(), 0);
  const gps = location(); const result = gps.freshLocation(new AbortController().signal);
  const rejected = assert.rejects(result, /Periksa izin/); await tick(); gps.error(); await rejected; assert.equal(gps.removed(), 1);
  const retry = gps.freshLocation(new AbortController().signal); await tick(); gps.point(); await retry;
});
