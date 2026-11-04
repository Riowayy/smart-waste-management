const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');

function load(file, dependencies) {
  const module = { exports: {} };
  const source = ts.transpileModule(fs.readFileSync(file, 'utf8'), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX },
  }).outputText;
  vm.runInNewContext('(function(require,module,exports){' + source + '})')(
    name => name in dependencies ? dependencies[name] : require(name), module, module.exports,
  );
  return module.exports;
}
function service(request) {
  return load('src/features/dashboard/service.ts', { '../../services/api/client': { apiClient: { request } } });
}
const fixture = {
  statistics: { total: 5, pending: 2, in_progress: 1, completed: 1, rejected: 1 },
  recent_reports: Array.from({ length: 5 }, (_, i) => ({
    id: i + 1, description: 'Laporan ' + (i + 1),
    image_url: 'https://example.test/laporan-' + (i + 1) + '.jpg',
    category: i ? { name: 'Organic Waste' } : null,
    status: ['pending', 'in_progress', 'completed', 'rejected', 'pending'][i],
    created_at: '2026-11-03T04:00:00Z',
  })),
};
test('dashboard menggunakan client terpusat, endpoint privat, dan sinyal pembatalan', async () => {
  const signal = new AbortController().signal;
  const api = service(async (path, options) => {
    assert.equal(path, 'dashboard');
    assert.equal(options.signal, signal);
    assert.notEqual(options.authenticated, false);
    return { data: fixture };
  });
  assert.equal(await api.getDashboard(signal), fixture);
});
test('kegagalan API diteruskan agar layar dapat retry atau memulihkan sesi', async () => {
  for (const status of [401, 403, 500]) {
    const error = Object.assign(new Error('Gagal'), { status });
    await assert.rejects(service(async () => { throw error; }).getDashboard(), e => e === error);
  }
});
test('tanggal kosong atau rusak tidak merusak tampilan', () => {
  const api = service();
  assert.equal(api.formatReportDate(null), 'Tanggal tidak tersedia');
  assert.equal(api.formatReportDate('invalid'), 'Tanggal tidak tersedia');
  assert.match(api.formatReportDate('2026-11-03T04:00:00Z'), /2026/);
});
function components() {
  const Element = ({ children }) => React.createElement('div', null, children);
  const ImageElement = ({ source }) => React.createElement('img', { src: source?.uri });
  return load('src/features/dashboard/screen.tsx', {
    'react-native': { View: Element, Text: Element, ScrollView: Element, Pressable: Element, ActivityIndicator: Element, RefreshControl: Element, StyleSheet: { create: v => v, hairlineWidth: 1 } },
    'expo-image': { Image: ImageElement }, 'expo-symbols': { SymbolView: Element },
    '../../components/report-image': {__esModule:true,default:({uri})=>React.createElement('img',{src:uri})},
    'react-native-safe-area-context': {}, 'expo-router': { Link: Element },
    '../../constants/theme': { Colors: { light: {} }, Spacing: {}, MaxContentWidth: 800 },
    '../../services/api/client': {}, '../auth/provider': {}, '../auth/screen': {},
    './service': service(),
  });
}
test('konten menampilkan lima laporan, kategori nullable, tanggal dan semua status', () => {
  const { DashboardContent } = components();
  const html = renderToStaticMarkup(React.createElement(DashboardContent, { data: fixture }));
  for (const label of ['Total laporan', 'Menunggu', 'Sedang diproses', 'Selesai', 'Ditolak', 'Tanpa kategori', 'Organic Waste', '2026']) {
    assert.ok(html.includes(label), label);
  }
  for (let i = 1; i <= 5; i++) assert.ok(html.includes('Laporan ' + i));
  assert.ok(html.includes('https://example.test/laporan-1.jpg'));
  assert.ok(html.includes('Lihat semua'));
});
test('dashboard kosong tetap menampilkan statistik nol dan empty state', () => {
  const { DashboardContent } = components();
  const data = { statistics: { total: 0, pending: 0, in_progress: 0, completed: 0, rejected: 0 }, recent_reports: [] };
  const html = renderToStaticMarkup(React.createElement(DashboardContent, { data }));
  assert.ok(html.includes('Belum ada laporan'));
  assert.equal((html.match(/>0</g) || []).length, 6);
});
