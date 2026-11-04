const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const moduleValue = { exports: {} };
const source = ts.transpileModule(fs.readFileSync('src/features/reporting/history-data.ts', 'utf8'), {
  compilerOptions: { module: ts.ModuleKind.CommonJS },
}).outputText;
vm.runInNewContext('(function(module,exports){' + source + '})')(moduleValue, moduleValue.exports);
const { visibleHistory, locationLabel } = moduleValue.exports;
const reports = [
  { id: 12, category: { slug: 'plastic-waste', name: 'Plastic Waste' }, description: 'Botol', latitude: 0, longitude: 180, created_at: '2026-11-01T08:00:00Z' },
  { id: 13, category: null, description: 'Daun', latitude: null, longitude: null, created_at: '2026-11-02T08:00:00Z' },
];
test('pencarian memakai kategori terjemahan/asli, ID, dan koordinat nyata', () => {
  for (const query of ['plastik', 'PLASTIC', '#ECO-12', '180.00000']) {
    assert.equal(visibleHistory(reports, query, false)[0].id, 12);
    assert.equal(visibleHistory(reports, query, false).length, 1);
  }
  assert.equal(visibleHistory(reports, 'Bandung', false).length, 0);
  assert.equal(locationLabel(reports[0]), '0.00000, 180.00000');
  assert.equal(locationLabel(reports[1]), 'Lokasi tidak tersedia');
});
test('urutan tanggal lokal tidak memutasi data halaman dari API', () => {
  assert.equal(visibleHistory(reports, '', false)[0].id, 13);
  assert.equal(visibleHistory(reports, '', true)[0].id, 12);
  assert.equal(reports[0].id, 12);
});
