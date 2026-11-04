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
const { visibleHistory, locationLabel, reportCoordinates, googleMapsUrl, categoryLabel } = moduleValue.exports;
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

test('koordinat detail menerima nol dan batas rentang, menolak pasangan kosong atau tidak valid', () => {
  for (const point of [{ latitude: 0, longitude: 0 }, { latitude: -90, longitude: 180 }]) {
    const valid = reportCoordinates(point);
    assert.equal(valid.latitude, point.latitude);
    assert.equal(valid.longitude, point.longitude);
    const url = new URL(googleMapsUrl(valid));
    assert.equal(url.searchParams.get('query'), `${point.latitude},${point.longitude}`);
  }
  for (const point of [{ latitude: null, longitude: 0 }, { latitude: 0, longitude: undefined }, { latitude: NaN, longitude: 0 }, { latitude: 91, longitude: 0 }, { latitude: 0, longitude: -181 }]) {
    assert.equal(reportCoordinates(point), null);
    assert.equal(locationLabel(point), 'Lokasi tidak tersedia');
  }
});

test('label kategori bersama menerjemahkan slug atau nama standar dan mempertahankan kategori lain', () => {
  for (const [name,slug,label] of [['Organic Waste','organic-waste','Sampah Organik'],['Plastic Waste','plastic-waste','Sampah Plastik'],['Paper Waste','paper-waste','Sampah Kertas']]) {
    assert.equal(categoryLabel({category:{name,slug}}),label);
    assert.equal(categoryLabel({category:{name}}),label);
  }
  assert.equal(categoryLabel({category:{name:'Kategori dari API',slug:'kategori-khusus'}}),'Kategori dari API');
  assert.equal(categoryLabel({category:null}),'Tanpa kategori');
});
