const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');

function lookup(reverseGeocodeAsync) {
 const timers=new Map();let nextTimer=0;
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/address.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{
  setTimeout:(callback,delay)=>{const id=++nextTimer;timers.set(id,{callback,delay});return id;},
  clearTimeout:id=>timers.delete(id),
 })(name=>{assert.equal(name,'expo-location');return {reverseGeocodeAsync};},module,module.exports);
 return {run:module.exports.lookupReportAddress,timers};
}

test('alamat pertama mempertahankan urutan, membuang bagian kosong dan duplikat',async()=>{
 let received;
 const view=lookup(async coordinates=>{received=coordinates;return [
  {street:'Jalan Mawar',streetNumber:'12',district:'Banjarbaru',city:'Banjarbaru',region:null,postalCode:'70711'},
  {street:'Alamat kedua'},
 ];});
 const coordinates={latitude:0,longitude:115};
 assert.equal(await view.run(coordinates),'Jalan Mawar, 12, Banjarbaru, 70711');
 assert.equal(received,coordinates);
 assert.equal(view.timers.size,0);
});

test('hasil kosong dan kegagalan lookup tetap opsional serta membersihkan timer',async()=>{
 for(const geocode of [async()=>[],async()=>{throw new Error('offline');}]) {
  const view=lookup(geocode);
  assert.equal(await view.run({latitude:0,longitude:0}),null);
  assert.equal(view.timers.size,0);
 }
});

test('timeout delapan detik selesai tanpa menunggu native; hasil terlambat tidak mengubahnya',async()=>{
 let finish;
 const view=lookup(()=>new Promise(resolve=>{finish=resolve;}));
 const pending=view.run({latitude:0,longitude:0});
 const [timer]=view.timers.values();
 assert.equal(timer.delay,8000);
 timer.callback();
 assert.equal(await pending,null);
 assert.equal(view.timers.size,0);
 finish([{street:'Hasil terlambat'}]);
 assert.equal(await pending,null);
});
