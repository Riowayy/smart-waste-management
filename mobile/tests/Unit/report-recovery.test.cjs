const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
function load(file,request) {
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync(file,'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
 vm.runInNewContext('(function(require,module,exports){'+source+'})')(name=>({apiClient:{request}}),module,module.exports);
 return module.exports;
}
const draft={description:'  Botol plastik  ',categoryId:2,location:{latitude:0,longitude:180}};
const report={id:11,description:'Botol plastik',category:{id:2},latitude:0,longitude:180};
test('pemulihan hanya membaca GET berhalaman, mengecualikan laporan lama dan mencari koordinat nol',async()=>{
 const calls=[];
 const api=load('src/features/reporting/recovery.ts',async(path,options)=>{
  calls.push({path,options});
  return options.query.page===1?{data:[{...report,id:12,description:'Berbeda'}],pagination:{last_page:2}}:{data:[report,{...report,id:5}],pagination:{last_page:2}};
 });
 const found=await api.findSubmittedReports({draft,baseline:10});
 assert.equal(found.length,1);assert.equal(found[0].id,11);
 assert.equal(calls.length,2);assert.ok(calls.every(call=>call.path==='reports'&&!call.options.method));
});
test('laporan mirip milik kategori/lokasi berbeda tidak dikonfirmasi dan hasil kosong bukan perintah POST',async()=>{
 const api=load('src/features/reporting/recovery.ts',async()=>({data:[{...report,category:null},{...report,latitude:1}],pagination:{last_page:1}}));
 assert.equal((await api.findSubmittedReports({draft,baseline:10})).length,0);
 api.pendingReport.set(1,{draft,baseline:10});
 assert.equal(api.pendingReport.get(2),undefined);assert.equal(api.pendingReport.get(1).baseline,10);
 api.pendingReport.clear(1);assert.equal(api.pendingReport.get(1),undefined);
});
test('kegagalan pembacaan baseline diteruskan sebelum POST',async()=>{
 const api=load('src/features/reporting/recovery.ts',async()=>{throw new Error('offline');});
 await assert.rejects(api.reportBaseline(),/offline/);
});
const {mapTiles}=load('src/features/reporting/map-data.ts');
test('tile peta tepat mengapit pusat GPS dan berubah ketika GPS diperbarui',()=>{
 const a=mapTiles({latitude:0,longitude:0},350);
 const b=mapTiles({latitude:-6.2,longitude:106.8},350);
 assert.ok(a.some(t=>t.left<=175&&t.left+256>=175&&t.top<=110&&t.top+256>=110));
 assert.ok(a.length<=9);assert.notEqual(a[0].uri,b[0].uri);
 assert.ok(a.every(t=>t.uri.startsWith('https://tile.openstreetmap.org/16/')));
 assert.equal(mapTiles({latitude:90,longitude:0},350).length,0);
 assert.ok(mapTiles({latitude:0,longitude:180},350).length>0);
});