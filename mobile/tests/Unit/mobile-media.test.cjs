const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
function load(file,deps={},globals={}){
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync(file,'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX}}).outputText;
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{URL,URLSearchParams,...globals})(name=>deps[name]??require(name),module,module.exports);
 return module.exports;
}
const base='http://192.168.1.7/smart-waste-management/public/api/v1';
const {resolveReportImageUrl}=load('src/services/report-image-url.ts',{'./api/config':{getApiBaseUrl:()=>base}});
test('URL storage loopback/relatif menggunakan origin LAN dan subdirektori API',()=>{
 for(const value of ['http://127.0.0.1:8000/storage/waste-reports/a.jpg','http://localhost/storage/waste-reports/a.jpg','/storage/waste-reports/a.jpg','storage/waste-reports/a.jpg','/smart-waste-management/public/storage/waste-reports/a.jpg']){
  assert.equal(resolveReportImageUrl(value),'http://192.168.1.7/smart-waste-management/public/storage/waste-reports/a.jpg');
 }
 assert.equal(resolveReportImageUrl('https://cdn.example.test/photo.jpg'),'https://cdn.example.test/photo.jpg');
 assert.equal(resolveReportImageUrl('data:image/png;base64,xxx'),null);
 assert.equal(resolveReportImageUrl('https://user:secret@example.test/photo.jpg'),null);
 assert.equal(resolveReportImageUrl(null),null);
});
test('foto gagal dimuat menampilkan fallback dan tombol retry dapat mencoba lagi',()=>{
 const states=[];let index=0;
 const component=load('src/components/report-image.tsx',{
  react:{useState:initial=>{const i=index++;if(!(i in states))states[i]=initial;return [states[i],value=>{states[i]=typeof value==='function'?value(states[i]):value;}];}},
  'react-native':{View:'view',Text:'text',Pressable:'button',StyleSheet:{create:value=>value}},
  'expo-image':{Image:'image'},'expo-symbols':{SymbolView:'symbol'},'../services/report-image-url':{resolveReportImageUrl},
 }).default;
 const render=()=>{index=0;return component({uri:'/storage/a.jpg',label:'Foto laporan',style:{width:80,height:80}});};
 const image=render().props.children;assert.equal(image.type,'image');image.props.onError();
 const fallback=render();assert.match(fallback.props.accessibilityLabel,/tidak tersedia/);
 fallback.props.children[1].props.onPress();assert.equal(render().props.children.type,'image');
});
const {mapTiles}=load('src/features/reporting/map-data.ts');
test('preview OpenStreetMap tidak membutuhkan API key dan mengikuti GPS terbaru',()=>{
 const before=mapTiles({latitude:-2,longitude:115},350);
 const after=mapTiles({latitude:-3,longitude:116},350);
 assert.ok(before.length>0);assert.notEqual(before[0].uri,after[0].uri);
 for(const tile of before){const url=new URL(tile.uri);assert.equal(url.host,'tile.openstreetmap.org');assert.equal(url.protocol,'https:');assert.equal(url.search,'');}
});
test('login menunggu decoded background lokal sebelum merender form',()=>{
 let decoded=null;
 const screen=load('src/features/auth/screen.tsx',{
  react:{useState:initial=>[initial,()=>{}],useRef:initial=>({current:initial})},
  'react-native':{View:'view',SafeAreaView:'safe',ActivityIndicator:'loading',StyleSheet:{create:value=>value},Platform:{OS:'android'},useWindowDimensions:()=>({width:390,height:844})},
  'expo-image':{Image:'image',useImage:()=>decoded},'expo-symbols':{},'react-native-safe-area-context':{SafeAreaView:'safe'},'expo-router':{},'../../services/api/client':{},'./provider':{useAuth:()=>({})},'./session':{},
  '../../../assets/images/bg-mobile-optimized.jpg':'local-background','../../../assets/images/ecotrack-mark.svg':'logo',
 });
 const authTree=screen.AuthScreen({mode:'login'});
 const loading=authTree.type(authTree.props);assert.equal(loading.props.children.type,'view');
 decoded={width:941,height:1672};
 const ready=authTree.type(authTree.props);
 assert.ok(Array.isArray(ready.props.children));
 assert.equal(ready.props.children[1].props.children[0].props.source,decoded);
});
