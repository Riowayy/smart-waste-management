const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');

function screen(submitReport, draftOverrides = {}, locationPermission = true, recovery = {}, gps = async()=>({coords:{latitude:0,longitude:180,accuracy:4}}), locationOptions = {}) {
 const states=[], refs=[]; let stateIndex=0,refIndex=0;
 let pending;
 class ApiError extends Error { constructor(kind) {super('Gagal');this.kind=kind;this.status=null;this.errors={};} }
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/screen.tsx','utf8'),{
  compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX},
 }).outputText;
 const dependencies={
  react:{useEffect:()=>{},useRef:initial=>refs[refIndex++]??(refs[refIndex-1]={current:initial}),
   useState:initial=>{const i=stateIndex++;if(!(i in states))states[i]=i===0?{description:'Temuan sampah',categoryId:1,photo:{uri:'file:test.jpg'},location:{latitude:0,longitude:0},...draftOverrides}:i===1?[{id:1,name:'Kategori dari API'}]:i===2?'ready':initial;
    return [states[i],value=>{states[i]=typeof value==='function'?value(states[i]):value;}];}},
  'react-native':{Platform:{OS:locationOptions.platform??'web'},Image:'image',Pressable:'pressable',Text:'text',TextInput:'input',StyleSheet:{create:value=>value}},
  'react-native-safe-area-context':{SafeAreaView:'safe'},
  'expo-symbols':{SymbolView:'symbol'},
  '../dashboard/screen':{AppBottomNav:'nav'},
  './location-map':{default:'map'},
  './recovery':{pendingReport:{get:()=>pending,set:(_,value)=>{pending=value;},clear:()=>{pending=undefined;}},reportBaseline:async()=>0,findSubmittedReports:async()=>[],...recovery},
  'expo-router':{Link:'link',router:{}},'expo-image-picker':{},'expo-location':{
   requestForegroundPermissionsAsync:async()=>({granted:locationPermission}),
   Accuracy:{Balanced:3},getCurrentPositionAsync:gps,
   reverseGeocodeAsync:locationOptions.reverseGeocodeAsync,
  },
  '../auth/screen':{Action:'action',AuthShell:'shell',styles:{}},
  '../auth/provider':{useAuth:()=>({restore:async()=>{}})},
  '../../services/api/client':{ApiError},
  './service':{validateDraft:()=>({}),submitReport},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{AbortController,setTimeout,clearTimeout})(
  name=>name in dependencies?dependencies[name]:require(name),module,module.exports,
 );
 function buttons(node,result=[]) {
  if(Array.isArray(node)){for(const child of node)buttons(child,result);return result;}
  if(node&&typeof node==='object'&&node.props){if(node.props.label || node.props.point)result.push(node.props);buttons(node.props.children,result);}
  return result;
 }
 return {ApiError,states,render(){stateIndex=0;refIndex=0;return buttons(module.exports.default());}};
}
const tick=()=>new Promise(resolve=>setImmediate(resolve));

test('dua klik pengiriman bersamaan hanya menjalankan satu submit',async()=>{
 let calls=0,finish;
 const view=screen(()=>{calls++;return new Promise(resolve=>{finish=resolve;});});
 const send=view.render().find(button=>button.label==='Simpan Laporan');
 send.onPress();send.onPress();await tick();
 assert.equal(calls,1);
 finish({id:1});await tick();
 assert.ok(view.render().some(button=>button.label==='Kembali ke dashboard'));
});

test('Android melepas loading GPS sebelum alamat selesai dan menolak alamat dari titik lama',async()=>{
 const lookups=[];let latitude=0;
 const view=screen(async()=>({id:1}),{location:null},true,{},async()=>({coords:{latitude:++latitude,longitude:115,accuracy:4}}),{
  platform:'android',reverseGeocodeAsync:()=>new Promise(resolve=>lookups.push(resolve)),
 });
 view.render().find(b=>b.label==='Ambil Lokasi Saya').onPress();await tick();
 assert.equal(lookups.length,1);assert.equal(view.states[12],false);assert.equal(view.states[3],false);
 assert.equal(view.render().find(b=>b.label==='Simpan Laporan').disabled,false);
 view.render().find(b=>b.label==='Ambil Ulang Lokasi').onPress();await tick();
 assert.equal(lookups.length,2);assert.equal(view.states[0].location.latitude,2);
 lookups[1]([{street:'Alamat terbaru'}]);await tick();
 lookups[0]([{street:'Alamat lama'}]);await tick();
 assert.equal(view.states[9],'Alamat terbaru');
});
test('network, timeout dan konfirmasi rusak menahan submit ulang',async()=>{
 for(const kind of ['network','timeout','invalid-response']) {
  let calls=0;
  const view=screen(async()=>{calls++;throw new view.ApiError(kind);});
  view.render().find(button=>button.label==='Simpan Laporan').onPress();await tick();
  assert.equal(calls,1);
  assert.equal(view.render().some(button=>button.label==='Simpan Laporan'),false);
 }
});

test('form menonaktifkan simpan dan menolak pengiriman tanpa GPS/kategori API',async()=>{
 for(const draft of [{location:null},{categoryId:null},{categoryId:999}]) {
  let calls=0;
  const view=screen(async()=>{calls++;},draft);
  const save=view.render().find(button=>button.label==='Simpan Laporan');
  assert.equal(save.disabled,true);
  save.onPress();await tick();
  assert.equal(calls,0);
 }
});

test('izin GPS ditolak tidak mengisi lokasi atau mengaktifkan Simpan',async()=>{
 const view=screen(async()=>{}, {location:null},false);
 view.render().find(button=>button.label==='Ambil Lokasi Saya').onPress();await tick();
 assert.equal(view.render().find(button=>button.label==='Simpan Laporan').disabled,true);
 assert.equal(view.states[0].location,null);
 assert.match(view.states[13],/Izin lokasi diperlukan/);
});

test('GPS berizin mengaktifkan simpan dan meneruskan koordinat nol ke service',async()=>{
 let sent;
 const view=screen(async draft=>{sent=draft;return {id:1};}, {location:null});
 view.render().find(button=>button.label==='Ambil Lokasi Saya').onPress();await tick();
 const save=view.render().find(button=>button.label==='Simpan Laporan');
 assert.equal(save.disabled,false);
 save.onPress();await tick();
 assert.equal(sent.location.latitude,0);
 assert.equal(sent.location.longitude,180);
});

test('HTTP 422 menampilkan validasi API dan mengizinkan perbaikan serta submit ulang',async()=>{
 let calls=0;
 const view=screen(async()=>{calls++;if(calls===1){const error=new view.ApiError('http');error.status=422;error.errors={description:['Perbaiki deskripsi.']};throw error;}return {id:2};});
 view.render().find(b=>b.label==='Simpan Laporan').onPress();await tick();
 assert.equal(view.states[5].description[0],'Perbaiki deskripsi.');assert.equal(view.states[7],false);
 const save=view.render().find(b=>b.label==='Simpan Laporan');assert.equal(save.disabled,false);
 save.onPress();await tick();assert.equal(calls,2);assert.equal(view.states[6],true);
});

test('timeout dipulihkan melalui laporan yang dikonfirmasi pengguna tanpa POST kedua',async()=>{
 let calls=0;
 const view=screen(async()=>{calls++;throw new view.ApiError('timeout');},{},true,{findSubmittedReports:async()=>[{id:9,description:'Temuan sampah',created_at:null}]});
 view.render().find(b=>b.label==='Simpan Laporan').onPress();await tick();
 view.render().find(b=>b.label==='Periksa Status Pengiriman').onPress();await tick();
 view.render().find(b=>b.label==='Ini laporan saya, konfirmasi tersimpan').onPress();
 assert.equal(calls,1);assert.equal(view.states[6],true);
 view.render().find(b=>b.label==='Buat Laporan Baru').onPress();
 assert.equal(view.states[7],false);assert.equal(view.states[6],false);assert.equal(view.states[0].photo,null);
});

test('hasil kosong saat pemulihan tetap terkunci dan tidak mengulang upload',async()=>{
 let calls=0;const view=screen(async()=>{calls++;throw new view.ApiError('network');});
 view.render().find(b=>b.label==='Simpan Laporan').onPress();await tick();
 view.render().find(b=>b.label==='Periksa Status Pengiriman').onPress();await tick();
 assert.equal(calls,1);assert.equal(view.states[7],true);assert.equal(view.states[3],false);
 assert.equal(view.render().some(b=>b.label==='Simpan Laporan'),false);
});

test('baseline gagal sebelum upload aman dicoba lagi dan tidak mengirim POST',async()=>{
 let calls=0;const view=screen(async()=>{calls++;},{},true,{reportBaseline:async()=>{throw new Error('offline');}});
 view.render().find(b=>b.label==='Simpan Laporan').onPress();await tick();
 assert.equal(calls,0);assert.equal(view.states[7],false);assert.equal(view.render().find(b=>b.label==='Simpan Laporan').disabled,false);
});

test('GPS kedua memperbarui titik preview peta dan akurasi',async()=>{
 let latitude=0;
 const view=screen(async()=>{},{location:null},true,{},async()=>({coords:{latitude:++latitude,longitude:110,accuracy:3}}));
 view.render().find(b=>b.label==='Ambil Lokasi Saya').onPress();await tick();
 assert.equal(view.render().find(b=>b.point).point.latitude,1);
 view.render().find(b=>b.label==='Ambil Ulang Lokasi').onPress();await tick();
 assert.equal(view.render().find(b=>b.point).point.latitude,2);assert.equal(view.states[10],3);assert.equal(view.states[12],false);
});

test('ambil ulang GPS gagal mempertahankan lokasi sebelumnya dan melepas loading',async()=>{
 const view=screen(async()=>{},{location:{latitude:-2,longitude:115}},true,{},async()=>{throw new Error('GPS gagal');});
 view.render().find(b=>b.label==='Ambil Ulang Lokasi').onPress();await tick();
 assert.equal(view.states[0].location.latitude,-2);assert.equal(view.states[0].location.longitude,115);
 assert.equal(view.states[12],false);assert.equal(view.states[3],false);assert.match(view.states[13],/gagal/i);
});
