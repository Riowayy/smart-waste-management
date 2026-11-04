// Dijalankan oleh ReportingHttpIntegrationTest dengan server/database/storage sementara.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const ts = require('typescript');
const baseUrl = process.env.ECOTRACK_TEST_API;
assert.match(baseUrl ?? '', /^http:\/\/127\.0\.0\.1:\d+\/api\/v1$/);
let token = null;
const cache = new Map();
const context = vm.createContext({URL, Headers, FormData, AbortController, setTimeout, clearTimeout, fetch,
  process: {env: {EXPO_PUBLIC_API_URL: baseUrl}}});
function load(filename) {
  filename = path.resolve(filename);
  if (cache.has(filename)) return cache.get(filename).exports;
  const module = {exports: {}};
  cache.set(filename, module);
  const source = ts.transpileModule(fs.readFileSync(filename, 'utf8'), {
    compilerOptions: {module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022},
  }).outputText;
  const requireModule = name => {
    if (name === 'react-native') return {Platform: {OS: 'web'}};
    if (name === 'expo-file-system') return {File: class {}};
    if (name.endsWith('token-storage')) return {tokenStorage: {getToken: async () => token}};
    return load(path.resolve(path.dirname(filename), name + '.ts'));
  };
  vm.runInContext('(function(require,module,exports){' + source + '\n})', context)(requireModule,module,module.exports);
  return module.exports;
}
async function run() {
  const {apiClient} = load('src/services/api/client.ts');
  const reporting = load('src/features/reporting/service.ts');
  const {getDashboard} = load('src/features/dashboard/service.ts');
  const register = async email => (await apiClient.request('auth/register', {method: 'POST', authenticated: false,
    body: {name: 'Pengguna test',email,password:'test-password',password_confirmation:'test-password'}})).data;
  const owner = await register('owner@example.test');
  token = owner.token;
  const categories=await reporting.getCategories();
  assert.equal(categories.length,3);
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=', 'base64');
  const file = new File([png], 'photo.png', {type:'image/png'});
  const draft = {description:'Temuan sampah '.padEnd(1000,'x'),categoryId:categories[0].id,
    photo:{uri:'blob:test',file},location:{latitude:0,longitude:180}};
  const report = await reporting.submitReport(draft);
  assert.equal(report.status,'pending');
  assert.equal(report.category.id,categories[0].id);
  assert.equal(report.latitude,0); assert.equal(report.longitude,180);
  const image = await fetch(report.image_url);
  assert.equal(image.status,200);
  assert.deepEqual(Buffer.from(await image.arrayBuffer()),png);
  const dashboard = await getDashboard();
  assert.equal(dashboard.statistics.total,1);
  assert.equal(dashboard.statistics.pending,1);
  assert.equal(dashboard.recent_reports[0].id,report.id);
  const list = await apiClient.request('reports',{query:{status:'pending',per_page:1}});
  assert.equal(list.data[0].id,report.id); assert.equal(list.pagination.total,1);
  const detail = await apiClient.request('reports/'+report.id);
  assert.equal(detail.data.description,draft.description); assert.equal(detail.data.status_history.length,0);
  for (const [key,value] of [['latitude',91],['longitude',181],['category_id',99999]]) {
    const body=reporting.createReportBody(draft); body.set(key,String(value));
    await assert.rejects(apiClient.request('reports',{method:'POST',body}),e=>e.status===422);
  }
  const invalid=reporting.createReportBody(draft);
  invalid.set('image',new File(['<?php echo 1;'],'photo.jpg',{type:'image/jpeg'}));
  await assert.rejects(apiClient.request('reports',{method:'POST',body:invalid}),e=>e.status===422);
  const other=await register('other@example.test'); token=other.token;
  assert.equal((await getDashboard()).statistics.total,0);
  assert.equal((await apiClient.request('reports')).data.length,0);
  await assert.rejects(apiClient.request('reports/'+report.id),e=>e.status===404);
  token='invalid';
  await assert.rejects(reporting.submitReport(draft),e=>e.status===401);
  token=owner.token;
  await apiClient.request('auth/logout',{method:'POST'});
  await assert.rejects(apiClient.request('auth/me'),e=>e.status===401);
  console.log('Multipart HTTP, foto, dashboard, riwayat, validasi, ownership dan logout lulus.');
}
run().catch(() => { console.error('Integrasi HTTP pelaporan gagal; kredensial/respons sensitif tidak dicetak.'); process.exitCode=1; });
