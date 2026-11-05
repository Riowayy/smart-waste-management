const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
const historyData={exports:{}};
const historyDataSource=ts.transpileModule(fs.readFileSync('src/features/reporting/history-data.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
vm.runInNewContext('(function(module,exports){'+historyDataSource+'})')(historyData,historyData.exports);

function loadMedia(file,dependencies) {
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync(file,'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX}}).outputText;
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{URL})(name=>dependencies[name]??require(name),module,module.exports);
 return module.exports;
}
const imageUrl=loadMedia('src/services/report-image-url.ts',{'./api/config':{getApiBaseUrl:()=> 'http://192.168.1.7/smart-waste-management/public/api/v1'}});
const ReportImage=loadMedia('src/components/report-image.tsx',{
 react:{useState:initial=>[initial,()=>{}]},
 'react-native':{View:'view',Text:'text',Pressable:'pressable',StyleSheet:{create:value=>value}},
 'expo-image':{Image:'server-image'},'expo-symbols':{SymbolView:'symbol'},'../services/report-image-url':imageUrl,
}).default;

function screen(submitReport, draftOverrides = {}, locationPermission = true, recovery = {}, gps = async()=>({coords:{latitude:0,longitude:180,accuracy:4}}), locationOptions = {}) {
 const states=[], refs=[]; let stateIndex=0,refIndex=0;
 let pending, navigationLocked = false;
 class ApiError extends Error { constructor(kind) {super('Gagal');this.kind=kind;this.status=null;this.errors={};} }
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/screen.tsx','utf8'),{
  compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX},
 }).outputText;
 const dependencies={'../../components/report-image':{__esModule:true,default:ReportImage},'../../components/eco-modal':{__esModule:true,default:'eco-modal'},'./location':{freshLocation:gps},
  react:{useCallback:callback=>callback,useEffect:()=>{},useRef:initial=>refs[refIndex++]??(refs[refIndex-1]={current:initial}),
   useState:initial=>{const i=stateIndex++;if(!(i in states))states[i]=i===0?{description:'Temuan sampah',categoryId:1,photo:{uri:'file:test.jpg'},location:{latitude:0,longitude:0},...draftOverrides}:i===1?(locationOptions.categories??[{id:1,name:'Kategori dari API'}]):i===2?'ready':initial;
    return [states[i],value=>{states[i]=typeof value==='function'?value(states[i]):value;}];}},
  'react-native':{Platform:{OS:locationOptions.platform??'web'},Alert:{alert:locationOptions.alert??(()=>{})},Image:'image',Pressable:'pressable',Text:'text',TextInput:'input',StyleSheet:{create:value=>value}},
  'react-native-safe-area-context':{SafeAreaView:'safe'},
  'expo-symbols':{SymbolView:'symbol'},
  '../../components/navigation-lock':{useNavigationLock:()=>({acquire:()=>{navigationLocked=true;return ()=>{navigationLocked=false;};}})},
  './location-map':{default:'map'},
  './history-data':historyData.exports,
  './recovery':{pendingReport:{get:()=>pending,set:(_,value)=>{pending=value;},clear:()=>{pending=undefined;}},reportBaseline:async()=>0,findSubmittedReports:async()=>[],...recovery},
  'expo-router':{Link:'link',router:{}},'expo-image-picker':locationOptions.picker??{},'expo-location':{
   requestForegroundPermissionsAsync:async()=>({granted:locationPermission}),
   Accuracy:{Balanced:3},getCurrentPositionAsync:gps,
   reverseGeocodeAsync:locationOptions.reverseGeocodeAsync,
  },
  '../auth/screen':{Action:'action',AuthShell:'shell',styles:{}},
  '../auth/provider':{useAuth:()=>({restore:async()=>{}})},
  '../../services/api/client':{ApiError},
  './service':{validateDraft:()=>({}),validatePhoto:locationOptions.validatePhoto??(()=>null),submitReport},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{Error,AbortController,setTimeout,clearTimeout,requestAnimationFrame:callback=>callback()})(
  name=>name in dependencies?dependencies[name]:require(name),module,module.exports,
 );
 function buttons(node,result=[]) {
  if(Array.isArray(node)){for(const child of node)buttons(child,result);return result;}
  if(node?.type===ReportImage){buttons(ReportImage(node.props),result);return result;}
  if(node&&typeof node==='object'&&node.props){result.push(node.props);buttons(node.props.children,result);}
  return result;
 }
 return {ApiError,states,navigationLocked:()=>navigationLocked,render(){stateIndex=0;refIndex=0;return buttons(module.exports.default());}};
}
const tick=()=>new Promise(resolve=>setImmediate(resolve));

test('dua klik pengiriman bersamaan hanya menjalankan satu submit',async()=>{
 let calls=0,finish;
 const view=screen(()=>{calls++;return new Promise(resolve=>{finish=resolve;});});
 const send=view.render().find(button=>button.label==='Simpan Laporan');
 send.onPress();send.onPress();await tick();
 assert.equal(calls,1);
 assert.equal(view.navigationLocked(),true);
 finish({id:1});await tick();
 assert.equal(view.navigationLocked(),false);
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
 view.render().find(b=>b.label==='Perbarui Lokasi').onPress();await tick();
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
  assert.equal(view.navigationLocked(),false);
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

test('foto recovery memakai URL LAN sementara preview dan payload foto tetap lokal',async()=>{
 for(const uri of ['http://127.0.0.1:8000/storage/waste-reports/a.jpg','/storage/waste-reports/a.jpg',null]) {
  let calls=0,sent;
  const photo={uri:'file:///local/report.jpg'};
  const view=screen(async draft=>{calls++;sent=draft;throw new view.ApiError('timeout');},{photo},true,{
   findSubmittedReports:async()=>[{id:9,description:'Temuan sampah',created_at:null,image_url:uri}],
  });
  view.render().find(b=>b.label==='Simpan Laporan').onPress();await tick();
  view.render().find(b=>b.label==='Periksa Status Pengiriman').onPress();await tick();
  const rendered=view.render();
  const serverPhoto=rendered.find(b=>b.accessibilityLabel==='Foto laporan yang ditemukan');
  if(uri) assert.equal(serverPhoto.source.uri,'http://192.168.1.7/smart-waste-management/public/storage/waste-reports/a.jpg');
  else assert.equal(serverPhoto,undefined);
  assert.equal(rendered.find(b=>b.accessibilityLabel==='Preview foto laporan').source.uri,photo.uri);
  assert.equal(sent.photo,photo);
  rendered.find(b=>b.label==='Ini laporan saya, konfirmasi tersimpan').onPress();
  assert.equal(calls,1);assert.equal(view.states[6],true);
 }
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
 view.render().find(b=>b.label==='Perbarui Lokasi').onPress();await tick();
 assert.equal(view.render().find(b=>b.point).point.latitude,2);assert.equal(view.states[10],3);assert.equal(view.states[12],false);
});

test('ambil ulang GPS gagal mempertahankan lokasi sebelumnya dan melepas loading',async()=>{
 const view=screen(async()=>{},{location:{latitude:-2,longitude:115}},true,{},async()=>{throw new Error('GPS gagal');});
 view.render().find(b=>b.label==='Perbarui Lokasi').onPress();await tick();
 assert.equal(view.states[0].location.latitude,-2);assert.equal(view.states[0].location.longitude,115);
 assert.equal(view.states[12],false);assert.equal(view.states[3],false);assert.match(view.states[13],/gagal/i);
 assert.equal(view.navigationLocked(),false);
});

test('pembaruan GPS menahan klik ganda; pembatalan menjaga koordinat lama dan melepas navigation lock',async()=>{
 let calls=0;
 const view=screen(async()=>{},{location:{latitude:-2,longitude:115}},true,{},signal=>{
  calls++;return new Promise((_,reject)=>signal.addEventListener('abort',()=>reject(new Error('Pengambilan lokasi dibatalkan.')),{once:true}));
 });
 const refresh=view.render().find(p=>p.label==='Perbarui Lokasi');refresh.onPress();refresh.onPress();await tick();
 assert.equal(calls,1);assert.equal(view.navigationLocked(),true);assert.equal(view.states[12],true);
 view.render().find(p=>p.label==='Batalkan GPS').onPress();await tick();
 assert.equal(view.navigationLocked(),false);assert.equal(view.states[3],false);assert.equal(view.states[12],false);
 assert.equal(view.states[0].location.latitude,-2);assert.equal(view.states[0].location.longitude,115);assert.match(view.states[13],/dibatalkan/);
});

test('satu ikon foto membuka sheet; kamera dan galeri memakai picker yang sesuai',async()=>{
 for(const source of ['Kamera','Galeri']) {
  let permissionCalls=0,cameraCalls=0,galleryCalls=0;
  const photo={uri:'file:chosen.jpg',mimeType:'image/jpeg',fileSize:100};
  const view=screen(async()=>{},{photo:null},true,{},undefined,{platform:'android',picker:{
   requestCameraPermissionsAsync:async()=>{permissionCalls++;return {granted:true};},
   launchCameraAsync:async()=>{assert.equal(view.states[14],false);cameraCalls++;return {canceled:false,assets:[photo]};},
   launchImageLibraryAsync:async()=>{assert.equal(view.states[14],false);galleryCalls++;return {canceled:false,assets:[photo]};},
  }});
  const initial=view.render();assert.equal(initial.filter(p=>p.accessibilityLabel==='Tambahkan foto laporan').length,1);assert.equal(initial.some(p=>p.label==='Kamera'),false);
  initial.find(p=>p.accessibilityLabel==='Tambahkan foto laporan').onPress();assert.equal(permissionCalls+cameraCalls+galleryCalls,0);
  view.render().find(p=>p.label===source).onPress();await tick();
  assert.equal(cameraCalls,source==='Kamera'?1:0);assert.equal(galleryCalls,source==='Galeri'?1:0);assert.equal(permissionCalls,source==='Kamera'?1:0);assert.equal(view.states[0].photo,photo);assert.equal(view.states[3],false);assert.equal(view.navigationLocked(),false);
  view.render().find(p=>p.label==='Ganti Foto').onPress();assert.ok(view.render().some(p=>p.label==='Galeri'));
 }
});
test('batal sheet atau picker mempertahankan foto; iOS menunggu dismiss dan izin ditolak memberi pesan',async()=>{
 for(const denied of [false,true]) {
  let launches=0;const oldPhoto={uri:'file:old.jpg'};
  const view=screen(async()=>{},{photo:oldPhoto},true,{},undefined,{platform:'ios',picker:{requestCameraPermissionsAsync:async()=>({granted:!denied}),launchCameraAsync:async()=>{launches++;return {canceled:true};}}});
  view.render().find(p=>p.label==='Ganti Foto').onPress();view.render().find(p=>p.label==='Batal').onPress();assert.equal(view.states[14],false);assert.equal(launches,0);
  view.render().find(p=>p.label==='Ganti Foto').onPress();const sheet=view.render().find(p=>p.sheet);const camera=view.render().find(p=>p.label==='Kamera');camera.onPress();camera.onPress();await tick();assert.equal(launches,0);assert.equal(view.navigationLocked(),true);
  sheet.onDismiss();sheet.onDismiss();await tick();assert.equal(view.states[0].photo,oldPhoto);assert.equal(view.states[3],false);assert.equal(launches,denied?0:1);assert.equal(view.navigationLocked(),false);if(denied)assert.match(view.states[4],/Izin kamera ditolak/);
 }
});
test('Web menampilkan sumber setelah ikon ditekan dan menutup sheet setelah memilih',async()=>{
 let calls=0;const view=screen(async()=>{},{photo:null},true,{},undefined,{picker:{launchImageLibraryAsync:async()=>{assert.equal(view.states[14],false);calls++;return {canceled:true};}}});
 assert.equal(view.render().some(p=>p.label==='Galeri'),false);view.render().find(p=>p.accessibilityLabel==='Tambahkan foto laporan').onPress();assert.equal(view.render().filter(p=>['Kamera','Galeri'].includes(p.label)).length,2);
 view.render().find(p=>p.label==='Galeri').onPress();await tick();assert.equal(calls,1);assert.equal(view.render().some(p=>p.label==='Galeri'),false);assert.equal(view.navigationLocked(),false);
});
test('kategori form diterjemahkan tanpa mengubah ID dan nama sumber API saat submit',async()=>{
 let submitted;
 const categories=[{id:21,name:'Organic Waste',slug:'organic-waste'},{id:22,name:'Plastic Waste',slug:'plastic-waste'},{id:23,name:'Paper Waste',slug:'paper-waste'}];
 const view=screen(async draft=>{submitted=draft;return {id:1};},{categoryId:21},true,{},undefined,{categories});
 assert.ok(view.render().some(p=>p.children==='Sampah Organik'));
 view.render().find(p=>p.accessibilityLabel==='Pilih kategori sampah').onPress();
 let nodes=view.render();for(const label of ['Sampah Organik','Sampah Plastik','Sampah Kertas'])assert.ok(nodes.some(p=>p.children===label));
 nodes.filter(p=>p.accessibilityRole==='radio')[2].onPress();
 assert.equal(view.states[0].categoryId,23);assert.ok(view.render().some(p=>p.children==='Sampah Kertas'));
 view.render().find(p=>p.label==='Simpan Laporan').onPress();await tick();
 assert.equal(submitted.categoryId,23);assert.equal(categories[2].name,'Paper Waste');
});
